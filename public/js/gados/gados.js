// gados.js — CRUD completo de gados

document.addEventListener('DOMContentLoaded', async function () {
  let paginaAtual = 1;
  const limite = 10;
  let totalPaginas = 1;

  const tbody         = document.getElementById('gados-tbody');
  const badgeCount    = document.getElementById('badge-gados-count');

  const btnPrev = document.getElementById('btn-prev');
  const btnNext = document.getElementById('btn-next');
  const paginationInfo = document.getElementById('pagination-info');
  
  const filtroQ       = document.getElementById('filtro-gado-q');
  const filtroFazenda = document.getElementById('filtro-gado-fazenda');
  const btnLimpar     = document.getElementById('btn-limpar-filtro-gado');
  const selectFazenda = document.getElementById('gadoFazenda');
  const gadoFeedback  = document.getElementById('gado-form-feedback');
  const deleteGadoNome = document.getElementById('delete-gado-nome');
  const btnConfirmarDelete = document.getElementById('btn-confirmar-delete-gado');

  // Modais
  const modalGadoEl      = document.getElementById('modalGado');
  const modalDeleteEl    = document.getElementById('modalDeleteGado');
  const bsModalGado      = modalGadoEl      ? new bootstrap.Modal(modalGadoEl)      : null;
  const bsModalDelete    = modalDeleteEl    ? new bootstrap.Modal(modalDeleteEl)    : null;

  let gadoParaDeleteId = null;
  let todasFazendas = [];
  let termoBusca = '';
  let fazendaFiltro = '';
  let debounceTimer = null;

  async function carregarGados() {
    try {
      const params = new URLSearchParams({
        page: String(paginaAtual),
        limit: String(limite),
        search: termoBusca,
        fazendaId: fazendaFiltro,
      });

      const res = await AgroApp.fetchJson(`/api/gados/vivos?${params.toString()}`);

      const resFazendas = await AgroApp.fetchJson('/api/fazendas/opcoes');
      todasFazendas = resFazendas?.data ?? [];

      popularSelectFazendas();

      const lista = res?.data ?? [];
      const total = res?.pagination?.totalItems ?? 0;

      totalPaginas = Math.max(1, Math.ceil(total / limite));

      renderizar(lista);
      renderizarPaginacao();

      if (badgeCount) badgeCount.textContent = total;

    } catch (err) {
      tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger py-5">
        ${AgroApp.escapeHtml(err.message)}
      </td></tr>`;
    }
  }

  function renderizarPaginacao() {
    if (paginationInfo) {
      paginationInfo.textContent = `Página ${paginaAtual} de ${totalPaginas}`;
    }

    if (btnPrev) btnPrev.disabled = paginaAtual <= 1;
    if (btnNext) btnNext.disabled = paginaAtual >= totalPaginas;
  }

  btnPrev?.addEventListener('click', () => {
    if (paginaAtual > 1) {
      paginaAtual--;
      carregarGados();
    }
  });

  btnNext?.addEventListener('click', () => {
    if (paginaAtual < totalPaginas) {
      paginaAtual++;
      carregarGados();
    }
  });

  // ── Renderizar tabela ──
  function renderizar(lista) {
    if (lista.length === 0) {
      tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-5">
        <i class="bi bi-inbox fs-2 d-block mb-2"></i>Nenhum gado encontrado.
      </td></tr>`;
      return;
    }

    const e = AgroApp.escapeHtml;
    tbody.innerHTML = lista.map(g => `
      <tr>
        <td>${e(g.codigo ?? '—')}</td>
        <td class="fw-semibold">${e(g.peso)}</td>
        <td class="d-none d-md-table-cell">${e(g.leite)}</td>
        <td class="d-none d-md-table-cell">${e(g.racao)}</td>
        <td class="d-none d-sm-table-cell">${AgroApp.formatDate(g.nascimento)}</td>
        <td class="text-end">
          <div class="action-group">
            <button class="btn-action btn-action-edit btn-editar-gado" title="Editar"
                    data-id="${e(g.id)}"
                    data-codigo="${e(g.codigo ?? '')}"
                    data-peso="${e(g.peso)}"
                    data-leite="${e(g.leite)}"
                    data-racao="${e(g.racao)}"
                    data-nascimento="${g.nascimento ? AgroApp.parseApiDate(g.nascimento).slice(0,10) : ''}"
                    data-fazenda-id="${e(g.fazendaId ?? '')}">
              <i class="bi bi-pencil"></i>
            </button>
            <button class="btn-action btn-action-delete btn-delete-gado" title="Excluir"
                    data-id="${e(g.id)}"
                    data-codigo="${e(g.codigo ?? '')}">
              <i class="bi bi-trash3"></i>
            </button>
          </div>
        </td>
      </tr>`).join('');

    // Bind editar
    tbody.querySelectorAll('.btn-editar-gado').forEach(btn => {
      btn.addEventListener('click', () => abrirModalEdicao(btn.dataset));
    });

    tbody.querySelectorAll('.btn-delete-gado').forEach(btn => {
      btn.addEventListener('click', () => abrirModalDelete(btn.dataset));
    });
  }

  function popularSelectFazendas() {
    const e = AgroApp.escapeHtml;

    if (selectFazenda) {
      const fazendaFormulario = selectFazenda.value;

      selectFazenda.innerHTML =
        '<option value="">Selecione a fazenda</option>' +
        (todasFazendas ?? []).map(f =>
          `<option value="${e(String(f.id))}">${e(f.nome)}</option>`
        ).join('');

      selectFazenda.value = todasFazendas.some(f => String(f.id) === fazendaFormulario)
        ? fazendaFormulario
        : '';
    }

    if (filtroFazenda) {
      const valorFiltro = fazendaFiltro || filtroFazenda.value;

      filtroFazenda.innerHTML =
        '<option value="">Fazenda</option>' +
        (todasFazendas ?? []).map(f =>
          `<option value="${e(String(f.id))}">${e(f.nome)}</option>`
        ).join('');

      filtroFazenda.value = todasFazendas.some(f => String(f.id) === valorFiltro)
        ? valorFiltro
        : '';
    }
  }

  // ── Abrir modal no modo edição ──
  function abrirModalEdicao(data) {
    document.getElementById('modalGadoTitle').innerHTML =
      `<i class="fa-solid fa-cow text-warning me-2"></i>Editar Gado ${AgroApp.escapeHtml(data.codigo ?? '')}`;
    document.getElementById('gadoId').value       = data.id;
    document.getElementById('gadoCodigo').value   = data.codigo ?? '';
    document.getElementById('gadoPeso').value     = data.peso;
    document.getElementById('gadoLeite').value    = data.leite;
    document.getElementById('gadoRacao').value    = data.racao;
    document.getElementById('gadoNascimento').value = data.nascimento ?? '';
    document.getElementById('gadoFazenda').value  = data.fazendaId ?? '';
    document.getElementById('gadoBtnLabel').textContent = 'Salvar alterações';
    if (gadoFeedback) gadoFeedback.innerHTML = '';
    bsModalGado?.show();
  }

  // ── Resetar modal para criação ──
  document.getElementById('btn-novo-gado')?.addEventListener('click', () => {
    document.getElementById('modalGadoTitle').innerHTML =
      '<i class="fa-solid fa-cow text-warning me-2"></i>Novo Gado';
    document.getElementById('gadoId').value = '';
    ['gadoCodigo','gadoPeso','gadoLeite','gadoRacao','gadoNascimento'].forEach(id => {
      document.getElementById(id).value = '';
    });
    // Limita data de nascimento para não aceitar datas futuras
    const todayStr = new Date().toISOString().split('T')[0];
    document.getElementById('gadoNascimento').setAttribute('max', todayStr);
    document.getElementById('gadoFazenda').value = '';
    document.getElementById('gadoBtnLabel').textContent = 'Cadastrar gado';
    if (gadoFeedback) gadoFeedback.innerHTML = '';
  });

  function abrirModalDelete(data) {
    gadoParaDeleteId = data.id;
    if (deleteGadoNome) {
      deleteGadoNome.textContent = `gado de código ${data.codigo || '—'}`;
    }
    bsModalDelete?.show();
  }

  // ── Submit gado (criar ou editar) ──
  document.getElementById('formGado')?.addEventListener('submit', async function (e) {
    e.preventDefault();

    const id        = document.getElementById('gadoId').value;
    const fazendaId = document.getElementById('gadoFazenda').value;
    const isEdit    = !!id;

    const pesoVal      = parseFloat(document.getElementById('gadoPeso').value);
    const codigoVal    = parseInt(document.getElementById('gadoCodigo').value) || 0;
    const leiteVal     = parseFloat(document.getElementById('gadoLeite').value) || 0;
    const racaoVal     = parseFloat(document.getElementById('gadoRacao').value) || 0;
    const nascimentoVal = document.getElementById('gadoNascimento').value;

    // Validação: peso obrigatório
    if (!pesoVal || pesoVal <= 0) {
      if (gadoFeedback) gadoFeedback.innerHTML =
        '<div class="alert alert-danger py-2">Informe o peso do animal.</div>';
      return;
    }

    if (!nascimentoVal) {
      if (gadoFeedback) gadoFeedback.innerHTML =
        '<div class="alert alert-danger py-2">Informe a data de nascimento.</div>';
      document.getElementById('gadoNascimento').focus();
      return;
    }

    const nascDate = new Date(nascimentoVal + 'T00:00:00');
    const agora    = new Date(); agora.setHours(0, 0, 0, 0);
    if (nascDate > agora) {
      if (gadoFeedback) gadoFeedback.innerHTML =
        '<div class="alert alert-danger py-2">A data de nascimento não pode ser uma data futura.</div>';
      document.getElementById('gadoNascimento').focus();
      return;
    }

    if (!fazendaId && !isEdit) {
      if (gadoFeedback) gadoFeedback.innerHTML =
        '<div class="alert alert-danger py-2">Selecione uma fazenda.</div>';
      return;
    }

    const body = {
      codigo:     codigoVal,
      peso:       pesoVal,
      leite:      leiteVal,
      racao:      racaoVal,
      nascimento: nascimentoVal,
    };

    try {
      if (isEdit) {
        await AgroApp.fetchJson(`/api/gados/${id}`, { method: 'PUT', body });
      } else {
        await AgroApp.fetchJson(`/api/fazendas/${fazendaId}/gados`, { method: 'POST', body });
      }
      bsModalGado?.hide();
      await recarregarGados();
    } catch (err) {
      if (gadoFeedback) gadoFeedback.innerHTML =
        `<div class="alert alert-danger py-2">${AgroApp.escapeHtml(err.message)}</div>`;
    }
  });

  btnConfirmarDelete?.addEventListener('click', async function () {
    if (!gadoParaDeleteId) return;

    try {
      btnConfirmarDelete.disabled = true;
      await AgroApp.fetchJson(`/api/gados/${gadoParaDeleteId}`, { method: 'DELETE' });
      bsModalDelete?.hide();
      gadoParaDeleteId = null;
      await recarregarGados();
      AgroApp.toast('Gado excluído com sucesso.', 'success');
    } catch (err) {
      AgroApp.toast('Erro ao excluir gado: ' + err.message, 'error');
    } finally {
      btnConfirmarDelete.disabled = false;
    }
  });

  // ── Recarregar lista ──
  async function recarregarGados() {
    await carregarGados();
  }

  filtroQ?.addEventListener('input', async function () { 
    termoBusca = this.value.trim();
    paginaAtual = 1;

    clearTimeout(debounceTimer);

    debounceTimer = setTimeout(() => {
      carregarGados();
    }, 300);
  });

  filtroFazenda?.addEventListener('change', async function () { 
    fazendaFiltro = this.value;
    paginaAtual = 1;

    carregarGados();
  });
  
  btnLimpar?.addEventListener('click', async function () {
    if (filtroQ) filtroQ.value = '';
    if (filtroFazenda) filtroFazenda.value = '';

    termoBusca = '';
    fazendaFiltro = '';
    paginaAtual = 1;

    carregarGados();
  });

  await carregarGados();
});
