// gados.js — CRUD de gados com listagem renderizada no Twig

document.addEventListener('DOMContentLoaded', function () {
  const formEl = document.getElementById('formGado');
  const gadoFeedback = document.getElementById('gado-form-feedback');
  const deleteGadoNome = document.getElementById('delete-gado-nome');
  const btnConfirmarDelete = document.getElementById('btn-confirmar-delete-gado');

  const modalGadoEl = document.getElementById('modalGado');
  const modalDeleteEl = document.getElementById('modalDeleteGado');
  const bsModalGado = modalGadoEl ? new bootstrap.Modal(modalGadoEl) : null;
  const bsModalDelete = modalDeleteEl ? new bootstrap.Modal(modalDeleteEl) : null;

  let gadoParaDeleteId = null;

  function getField(name) {
    return formEl?.querySelector(`[name$="[${name}]"]`) ?? null;
  }

  bindRowActions();
  applyBirthDateMax();

  document.getElementById('btn-novo-gado')?.addEventListener('click', function () {
    document.getElementById('modalGadoTitle').innerHTML =
      '<i class="fa-solid fa-cow text-warning me-2"></i>Novo Gado';
    document.getElementById('gadoId').value = '';
    ['codigo', 'peso', 'leite', 'racao', 'nascimento', 'fazendaId'].forEach(name => {
      const field = getField(name);
      if (field) {
        field.value = '';
      }
    });
    document.getElementById('gadoBtnLabel').textContent = 'Cadastrar gado';

    if (gadoFeedback) {
      gadoFeedback.innerHTML = '';
    }

    applyBirthDateMax();
  });

  document.getElementById('formGado')?.addEventListener('submit', async function (event) {
    event.preventDefault();

    const codigoField = getField('codigo');
    const pesoField = getField('peso');
    const leiteField = getField('leite');
    const racaoField = getField('racao');
    const nascimentoField = getField('nascimento');
    const fazendaField = getField('fazendaId');
    const id = document.getElementById('gadoId').value;
    const fazendaId = fazendaField?.value ?? '';
    const isEdit = Boolean(id);

    const pesoVal = parseFloat(pesoField?.value ?? '');
    const codigoVal = parseInt(codigoField?.value ?? '', 10) || 0;
    const leiteVal = parseFloat(leiteField?.value ?? '') || 0;
    const racaoVal = parseFloat(racaoField?.value ?? '') || 0;
    const nascimentoVal = nascimentoField?.value ?? '';

    if (!pesoVal || pesoVal <= 0) {
      AgroApp.setFeedback(gadoFeedback, 'Informe o peso do animal.');
      return;
    }

    if (!nascimentoVal) {
      AgroApp.setFeedback(gadoFeedback, 'Informe a data de nascimento.');
      nascimentoField?.focus();
      return;
    }

    const nascDate = new Date(`${nascimentoVal}T00:00:00`);
    const hoje = new Date();
    hoje.setHours(0, 0, 0, 0);

    if (nascDate > hoje) {
      AgroApp.setFeedback(gadoFeedback, 'A data de nascimento não pode ser uma data futura.');
      nascimentoField?.focus();
      return;
    }

    if (!fazendaId) {
      AgroApp.setFeedback(gadoFeedback, 'Selecione uma fazenda.');
      return;
    }

    const body = {
      codigo: codigoVal,
      peso: pesoVal,
      leite: leiteVal,
      racao: racaoVal,
      nascimento: nascimentoVal,
      fazendaId: fazendaId ? Number(fazendaId) : null,
    };

    const submitBtn = this.querySelector('button[type="submit"]');

    try {
      if (submitBtn) {
        submitBtn.disabled = true;
      }

      if (isEdit) {
        await AgroApp.fetchJson(`/api/gados/${id}`, { method: 'PUT', body });
      } else {
        await AgroApp.fetchJson(`/api/fazendas/${fazendaId}/gados`, { method: 'POST', body });
      }

      AgroApp.persistFlash(
        'success',
        isEdit ? 'Gado atualizado com sucesso.' : 'Gado cadastrado com sucesso.'
      );
      bsModalGado?.hide();
      window.location.reload();
    } catch (err) {
      AgroApp.setFeedback(gadoFeedback, err);
    } finally {
      if (submitBtn) {
        submitBtn.disabled = false;
      }
    }
  });

  btnConfirmarDelete?.addEventListener('click', async function () {
    if (!gadoParaDeleteId) {
      return;
    }

    try {
      btnConfirmarDelete.disabled = true;
      await AgroApp.fetchJson(`/api/gados/${gadoParaDeleteId}`, { method: 'DELETE' });
      AgroApp.persistFlash('success', 'Gado removido com sucesso.');
      bsModalDelete?.hide();
      gadoParaDeleteId = null;
      window.location.reload();
    } catch (err) {
      AgroApp.toast('Erro ao excluir gado: ' + err.message, 'error');
    } finally {
      btnConfirmarDelete.disabled = false;
    }
  });

  function bindRowActions() {
    document.querySelectorAll('.btn-editar-gado').forEach(function (btn) {
      btn.addEventListener('click', function () {
        abrirModalEdicao(btn.dataset);
      });
    });

    document.querySelectorAll('.btn-delete-gado').forEach(function (btn) {
      btn.addEventListener('click', function () {
        abrirModalDelete(btn.dataset);
      });
    });
  }

  function abrirModalEdicao(data) {
    document.getElementById('modalGadoTitle').innerHTML =
      `<i class="fa-solid fa-cow text-warning me-2"></i>Editar Gado ${AgroApp.escapeHtml(data.codigo ?? '')}`;
    document.getElementById('gadoId').value = data.id;
    const codigoField = getField('codigo');
    const pesoField = getField('peso');
    const leiteField = getField('leite');
    const racaoField = getField('racao');
    const nascimentoField = getField('nascimento');
    const fazendaField = getField('fazendaId');

    if (codigoField) codigoField.value = data.codigo ?? '';
    if (pesoField) pesoField.value = data.peso ?? '';
    if (leiteField) leiteField.value = data.leite ?? '';
    if (racaoField) racaoField.value = data.racao ?? '';
    if (nascimentoField) nascimentoField.value = data.nascimento ?? '';
    if (fazendaField) fazendaField.value = data.fazendaId ?? '';
    document.getElementById('gadoBtnLabel').textContent = 'Salvar alterações';

    if (gadoFeedback) {
      gadoFeedback.innerHTML = '';
    }

    applyBirthDateMax();
    bsModalGado?.show();
  }

  function abrirModalDelete(data) {
    gadoParaDeleteId = data.id;

    if (deleteGadoNome) {
      deleteGadoNome.textContent = `gado de código ${data.codigo || '—'}`;
    }

    bsModalDelete?.show();
  }

  function applyBirthDateMax() {
    const nascimentoInput = getField('nascimento');

    if (!nascimentoInput) {
      return;
    }

    nascimentoInput.setAttribute('max', new Date().toISOString().split('T')[0]);
  }
});
