// fazendas.js

document.addEventListener('DOMContentLoaded', async function () {

  let paginaAtual = 1;
  const limite = 10;
  let totalPaginas = 1;

  const tbody             = document.getElementById('fazendas-tbody');
  const badgeCount        = document.getElementById('badge-fazendas-count');

  const btnPrev = document.getElementById('btn-prev');
  const btnNext = document.getElementById('btn-next');
  const paginationInfo = document.getElementById('pagination-info');

  const deleteFazendaNome = document.getElementById('delete-fazenda-nome');
  const btnConfirmarDelete = document.getElementById('btn-confirmar-delete-fazenda');

  const filtroInput       = document.getElementById('filtro-fazenda');
  const btnLimpar         = document.getElementById('btn-limpar-filtro');
  const fazendaFeedback   = document.getElementById('fazenda-modal-feedback');

  // Modais
  const modalFazendaEl = document.getElementById('modalFazenda');
  const modalDeleteEl  = document.getElementById('modalDeleteFazenda');
  const bsModalFazenda = modalFazendaEl ? new bootstrap.Modal(modalFazendaEl) : null;
  const bsModalDelete  = modalDeleteEl  ? new bootstrap.Modal(modalDeleteEl)  : null;

  let fazendaParaDeleteId = null;
  let termoBusca = '';
  let debounceTimer = null;

  // ── Carregar fazendas ──
  async function carregarFazendas() {
    try {
      const res = await AgroApp.fetchJson(`/api/fazendas?page=${paginaAtual}&limit=${limite}&search=${encodeURIComponent(termoBusca)}`);

      const lista = res?.data ?? [];
      const total = res?.pagination?.totalItems ?? 0;

      totalPaginas = Math.max(1, Math.ceil(total / limite));

      renderizar(lista);
      renderizarPaginacao();

    } catch (err) {
      tbody.innerHTML = `<tr><td colspan="4" class="text-center text-danger py-5">
        ${AgroApp.escapeHtml(err.message)}
      </td></tr>`;
    }
  }

  // ── Renderizar tabela ──
  function renderizar(lista) {
    if (badgeCount) badgeCount.textContent = lista.length;

    if (lista.length === 0) {
      tbody.innerHTML = `<tr><td colspan="4">
        <div class="table-empty-state">
          <i class="bi bi-house-door"></i>
          <p>Nenhuma fazenda cadastrada.</p>
        </div>
      </td></tr>`;
      return;
    }

    const e = AgroApp.escapeHtml;

    tbody.innerHTML = lista.map(f => `
      <tr>
        <td class="fw-semibold">${e(f.nome)}</td>
        <td class="d-none d-sm-table-cell">${e(f.responsavel)}</td>
        <td class="d-none d-md-table-cell">${e(f.tamanhoHA)} ha</td>
        <td class="text-end">
          <div class="action-group">
            <button class="btn-action btn-action-edit btn-editar-fazenda"
                    title="Editar fazenda"
                    data-id="${e(f.id)}"
                    data-nome="${e(f.nome)}"
                    data-responsavel="${e(f.responsavel)}"
                    data-tamanho="${e(f.tamanhoHA)}">
              <i class="bi bi-pencil"></i>
            </button>
            <button class="btn-action btn-action-delete btn-delete-fazenda"
                    title="Excluir fazenda"
                    data-id="${e(f.id)}"
                    data-nome="${e(f.nome)}">
              <i class="bi bi-trash3"></i>
            </button>
          </div>
        </td>
      </tr>`).join('');

    // Bind editar
    tbody.querySelectorAll('.btn-editar-fazenda').forEach(btn => {
      btn.addEventListener('click', () => abrirModalEdicao(btn.dataset));
    });

    // Bind delete
    tbody.querySelectorAll('.btn-delete-fazenda').forEach(btn => {
      btn.addEventListener('click', () => {
        fazendaParaDeleteId = btn.dataset.id;
        if (deleteFazendaNome) deleteFazendaNome.textContent = btn.dataset.nome;
        bsModalDelete?.show();
      });
    });
  }

  // ── PAGINAÇÃO ──
  function renderizarPaginacao() {
    if (paginationInfo) {
      paginationInfo.textContent = `Página ${paginaAtual} de ${totalPaginas}`;
    }

    if (btnPrev) btnPrev.disabled = paginaAtual <= 1;
    if (btnNext) btnNext.disabled = paginaAtual >= totalPaginas;
  }

  // ── Abrir modal criação ──
  document.getElementById('btn-nova-fazenda')?.addEventListener('click', () => {
    document.getElementById('modalFazendaTitle').textContent = 'Nova Fazenda';
    document.getElementById('fazendaModalId').value = '';
    document.getElementById('fazendaModalNome').value = '';
    document.getElementById('fazendaModalResponsavel').value = '';
    document.getElementById('fazendaModalTamanho').value = '';
    document.getElementById('fazendaModalBtnLabel').textContent = 'Cadastrar Fazenda';
    if (fazendaFeedback) fazendaFeedback.innerHTML = '';
    bsModalFazenda?.show();
  });

  // ── Abrir modal edição ──
  function abrirModalEdicao(data) {
    document.getElementById('modalFazendaTitle').textContent = 'Editar Fazenda';
    document.getElementById('fazendaModalId').value = data.id;
    document.getElementById('fazendaModalNome').value = data.nome ?? '';
    document.getElementById('fazendaModalResponsavel').value = data.responsavel ?? '';
    document.getElementById('fazendaModalTamanho').value = data.tamanho ?? '';
    document.getElementById('fazendaModalBtnLabel').textContent = 'Salvar Alterações';
    if (fazendaFeedback) fazendaFeedback.innerHTML = '';
    bsModalFazenda?.show();
  }

  // ── Submit do formulário (criar ou editar) ──
  document.getElementById('formFazendaModal')?.addEventListener('submit', async function (e) {
    e.preventDefault();

    const id          = document.getElementById('fazendaModalId').value;
    const nome        = document.getElementById('fazendaModalNome').value.trim();
    const responsavel = document.getElementById('fazendaModalResponsavel').value.trim();
    const tamanhoHA   = parseFloat(document.getElementById('fazendaModalTamanho').value) || 0;
    const isEdit      = !!id;

    if (!nome || !responsavel) {
      if (fazendaFeedback) {
        fazendaFeedback.innerHTML = '<div class="alert alert-danger py-2">Preencha todos os campos obrigatórios.</div>';
      }
      return;
    }

    const submitBtn = this.querySelector('button[type="submit"]');
    if (submitBtn) submitBtn.disabled = true;

    try {
      if (isEdit) {
        await AgroApp.fetchJson(`/api/fazendas/${id}`, {
          method: 'PUT',
          body: { nome, responsavel, tamanhoHA },
        });
        AgroApp.toast('Fazenda atualizada com sucesso.', 'success');
      } else {
        await AgroApp.fetchJson('/api/fazendas', {
          method: 'POST',
          body: { nome, responsavel, tamanhoHA },
        });
        AgroApp.toast('Fazenda cadastrada com sucesso.', 'success');
      }
      bsModalFazenda?.hide();
      await carregarFazendas();
    } catch (err) {
      if (fazendaFeedback) {
        fazendaFeedback.innerHTML = `<div class="alert alert-danger py-2">${AgroApp.escapeHtml(err.message)}</div>`;
      }
    } finally {
      if (submitBtn) submitBtn.disabled = false;
    }
  });

  filtroInput?.addEventListener('input', async function () {
    termoBusca = this.value.trim();
    paginaAtual = 1;

    clearTimeout(debounceTimer);

    debounceTimer = setTimeout(async () => {
      await carregarFazendas();
    }, 300);
  });

  btnLimpar?.addEventListener('click', async function () {
    termoBusca = '';
    paginaAtual = 1;

    if (filtroInput) {
      filtroInput.value = '';
    }

    await carregarFazendas();
  });

  // ── Confirmar delete ──
  btnConfirmarDelete?.addEventListener('click', async function () {
    if (!fazendaParaDeleteId) return;

    try {
      btnConfirmarDelete.disabled = true;
      await AgroApp.fetchJson(`/api/fazendas/${fazendaParaDeleteId}`, { method: 'DELETE' });
      bsModalDelete?.hide();
      fazendaParaDeleteId = null;
      await carregarFazendas();
      AgroApp.toast('Fazenda excluída com sucesso.', 'success');
    } catch (err) {
      AgroApp.toast('Erro ao excluir fazenda: ' + err.message, 'error');
    } finally {
      btnConfirmarDelete.disabled = false;
    }
  });

  await carregarFazendas();
});