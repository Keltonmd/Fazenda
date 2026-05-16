document.addEventListener('DOMContentLoaded', function () {
  const tbody = document.getElementById('abates-tbody');
  const btnBulk = document.getElementById('btn-bulk-abater');
  const bulkCount = document.getElementById('bulk-count');

  const modalCancelarEl = document.getElementById('modalCancelarAbate');
  const bsModalCancelar = modalCancelarEl ? new bootstrap.Modal(modalCancelarEl) : null;
  const cancelarGadoNome = document.getElementById('cancelar-abate-gado-nome');
  const cancelarFeedback = document.getElementById('cancelar-abate-feedback');
  const novoCodigoWrap = document.getElementById('novo-codigo-wrap');
  const novoCodigoInput = document.getElementById('novo-codigo-input');
  const btnConfirmarCancelar = document.getElementById('btn-confirmar-cancelar-abate');

  let gadoParaCancelarId = null;

  setupCheckboxes();
  bindCancelarAbateButtons();

  btnBulk?.addEventListener('click', async function () {
    const ids = Array.from(tbody?.querySelectorAll('.gado-check:checked') ?? []).map(cb => Number(cb.value));

    if (ids.length === 0) {
      return;
    }

    const plural = ids.length === 1 ? '1 animal' : `${ids.length} animais`;
    const ok = await AgroApp.confirm(
      `Confirmar abate de ${plural}?`,
      'Os animais serão movidos para o histórico de abatidos.',
      'warning'
    );

    if (!ok) {
      return;
    }

    try {
      btnBulk.disabled = true;
      await AgroApp.fetchJson('/api/gados/abate', {
        method: 'PUT',
        body: { gados: ids },
      });

      AgroApp.persistFlash('success', `Abate de ${plural} registrado com sucesso.`);
      window.location.reload();
    } catch (err) {
      AgroApp.toast('Erro ao registrar abate: ' + err.message, 'error');
    } finally {
      btnBulk.disabled = false;
    }
  });

  btnConfirmarCancelar?.addEventListener('click', async function () {
    if (!gadoParaCancelarId) {
      return;
    }

    const body = {};

    if (novoCodigoWrap && novoCodigoWrap.style.display !== 'none') {
      const novoCodigo = parseInt(novoCodigoInput?.value ?? '', 10);

      if (!novoCodigo || novoCodigo <= 0) {
        AgroApp.setFeedback(cancelarFeedback, 'Informe um código válido.');
        return;
      }

      body.novoCodigo = novoCodigo;
    }

    try {
      btnConfirmarCancelar.disabled = true;
      await AgroApp.fetchJson(`/api/gados/${gadoParaCancelarId}/abate/cancelar`, {
        method: 'PUT',
        body,
      });

      AgroApp.persistFlash('success', 'Abate cancelado com sucesso. O animal voltou para a lista de gados vivos.');
      bsModalCancelar?.hide();
      window.location.reload();
    } catch (err) {
      AgroApp.setFeedback(cancelarFeedback, err);
    } finally {
      btnConfirmarCancelar.disabled = false;
    }
  });

  function setupCheckboxes() {
    const checkAllBtn = document.getElementById('check-all-abates');
    const rowCheckboxes = tbody?.querySelectorAll('.gado-check') ?? [];

    if (!tbody || rowCheckboxes.length === 0) {
      if (btnBulk) {
        btnBulk.disabled = true;
      }

      return;
    }

    if (checkAllBtn) {
      checkAllBtn.addEventListener('change', function (event) {
        const checked = event.target.checked;

        rowCheckboxes.forEach(cb => {
          cb.checked = checked;
        });

        updateBulkCount();
      });
    }

    rowCheckboxes.forEach(cb => {
      cb.addEventListener('change', function () {
        updateBulkCount();

        if (checkAllBtn) {
          checkAllBtn.checked = Array.from(rowCheckboxes).every(item => item.checked);
        }
      });
    });
  }

  function updateBulkCount() {
    const checked = tbody?.querySelectorAll('.gado-check:checked') ?? [];

    if (bulkCount) {
      bulkCount.textContent = String(checked.length);
    }

    if (btnBulk) {
      btnBulk.disabled = checked.length === 0;
    }
  }

  function bindCancelarAbateButtons() {
    document.querySelectorAll('.btn-cancelar-abate').forEach(btn => {
      btn.addEventListener('click', function () {
        abrirModalCancelar(btn.dataset);
      });
    });
  }

  async function abrirModalCancelar(data) {
    gadoParaCancelarId = data.id;

    if (cancelarGadoNome) {
      cancelarGadoNome.textContent = `Gado de código ${data.codigo || '—'}`;
    }

    AgroApp.setFeedback(cancelarFeedback, []);

    const limiteFormatado = formatDateTime(data.dataLimiteCancelamento);
    if (limiteFormatado !== '—') {
      AgroApp.setFeedback(
        cancelarFeedback,
        `O cancelamento está disponível até ${limiteFormatado}.`,
        'info'
      );
    }

    if (novoCodigoInput) {
      novoCodigoInput.value = '';
    }

    if (novoCodigoWrap) {
      novoCodigoWrap.style.display = 'none';
    }

    const codigo = parseInt(data.codigo ?? '', 10);
    if (codigo > 0) {
      try {
        const res = await AgroApp.fetchJson(`/api/gados/codigo-existe/${codigo}`);

        if (res?.existe) {
          if (novoCodigoWrap) {
            novoCodigoWrap.style.display = '';
          }

          AgroApp.setFeedback(cancelarFeedback, [
            limiteFormatado !== '—' ? `O cancelamento está disponível até ${limiteFormatado}.` : null,
            `O código ${codigo} já está em uso por outro gado vivo. Informe um novo código para este animal.`,
          ].filter(Boolean), 'warning');
        }
      } catch (_) {
      }
    }

    bsModalCancelar?.show();
  }

  function formatDateTime(value) {
    const parsed = AgroApp.parseApiDate(value);

    if (!parsed) {
      return '—';
    }

    const date = new Date(parsed);

    if (Number.isNaN(date.getTime())) {
      return parsed;
    }

    return new Intl.DateTimeFormat('pt-BR', {
      dateStyle: 'short',
      timeStyle: 'short',
    }).format(date);
  }
});
