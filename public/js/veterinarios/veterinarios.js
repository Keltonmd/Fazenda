// veterinarios.js

document.addEventListener('DOMContentLoaded', async function () {

  let paginaAtual = 1;
  const limite = 10;
  let totalPaginas = 1;

  const tbody       = document.getElementById('vets-tbody');
  const badgeCount  = document.getElementById('badge-vets-count');

  const btnPrev = document.getElementById('btn-prev');
  const btnNext = document.getElementById('btn-next');
  const paginationInfo = document.getElementById('pagination-info');

  const filtroQ     = document.getElementById('filtro-vet-q');
  const btnLimpar   = document.getElementById('btn-limpar-filtro-vet');
  const vetFeedback = document.getElementById('vet-form-feedback');
  const deleteVetNome      = document.getElementById('delete-vet-nome');
  const btnConfirmarDelete = document.getElementById('btn-confirmar-delete-vet');

  const modalVetEl      = document.getElementById('modalVeterinario');
  const modalDeleteEl   = document.getElementById('modalDeleteVet');
  const modalFazendasEl = document.getElementById('modalVetFazendas');
  const bsModalVet      = modalVetEl      ? new bootstrap.Modal(modalVetEl)      : null;
  const bsModalDelete   = modalDeleteEl   ? new bootstrap.Modal(modalDeleteEl)   : null;
  const bsModalFazendas = modalFazendasEl ? new bootstrap.Modal(modalFazendasEl) : null;

  let todosVets = [];
  let todasFazendas = [];
  let vetParaDeleteId = null;
  let vetFazendasAtual = null;
  let termoBusca = '';
  let debounceTimer = null;

  function setVetFeedback(message, type = 'danger') {
    if (!vetFeedback) return;
    vetFeedback.innerHTML = `<div class="alert alert-${type} py-2">${AgroApp.escapeHtml(message)}</div>`;
  }

  function setFazendasFeedback(message, type = 'danger') {
    const feedbackEl = document.getElementById('vet-fazendas-feedback');
    if (!feedbackEl) return;
    feedbackEl.innerHTML = `<div class="alert alert-${type} py-2">${AgroApp.escapeHtml(message)}</div>`;
  }

  async function carregarFazendas() {
    try {
      const resFaz = await AgroApp.fetchJson('/api/fazendas/opcoes');
      todasFazendas = resFaz?.data ?? [];
    } catch (_) {
      todasFazendas = [];
    }

    const selectFazenda = document.getElementById('vetFazenda');
    if (selectFazenda) {
      selectFazenda.innerHTML = '<option value="">Sem fazenda inicial</option>' +
        todasFazendas.map(f =>
          `<option value="${AgroApp.escapeHtml(String(f.id))}">${AgroApp.escapeHtml(f.nome)}</option>`
        ).join('');
    }
  }

  async function carregarVets() {
    try {
      const res = await AgroApp.fetchJson(
        `/api/veterinarios?page=${paginaAtual}&limit=${limite}&search=${encodeURIComponent(termoBusca)}`
      );

      const lista = res?.data ?? [];
      const total = res?.pagination?.totalItems ?? 0;

      totalPaginas = Math.max(1, res?.pagination?.totalPages ?? 1);

      todosVets = lista;

      renderizar(lista);
      renderizarPaginacao();

      if (badgeCount) badgeCount.textContent = total;
    } catch (err) {
      todosVets = [];
      if (tbody) {
        tbody.innerHTML = `<tr><td colspan="4" class="text-center text-danger py-5">
          <i class="bi bi-exclamation-circle fs-2 d-block mb-2"></i>${AgroApp.escapeHtml(err.message)}
        </td></tr>`;
      }
    }
  }

  function renderizar(lista) {
    if (!tbody) return;

    if (lista.length === 0) {
      tbody.innerHTML = `<tr><td colspan="4" class="text-center text-muted py-5">
        <i class="bi bi-inbox fs-2 d-block mb-2"></i>Nenhum veterinário encontrado.
      </td></tr>`;
      return;
    }

    const e = AgroApp.escapeHtml;
    tbody.innerHTML = lista.map(v => {
      const fazendasDoVet = v.fazendas ?? [];
      const resumoFazendas = fazendasDoVet.length > 0
        ? fazendasDoVet.map(f =>
            `<span class="badge bg-success-subtle text-success border border-success-subtle me-1 badge-fazenda">${e(f.nome)}</span>`
          ).join('')
        : '<span class="text-muted small">Nenhuma fazenda vinculada</span>';

      return `
        <tr>
          <td class="fw-semibold">${e(v.nome)}</td>
          <td class="crmv-text d-none d-sm-table-cell">${e(v.crmv)}</td>
          <td class="d-none d-md-table-cell">
            <div class="d-flex flex-wrap align-items-center gap-1">
              ${resumoFazendas}
              <button class="btn btn-xs btn-outline-success btn-ger-fazendas-vet"
                      data-id="${e(v.id)}"
                      data-nome="${e(v.nome)}"
                      title="Adicionar ou remover fazendas">
                <i class="bi bi-house-gear me-1"></i>Gerenciar
              </button>
            </div>
          </td>
          <td class="text-end">
            <div class="action-group">
              <button class="btn-action btn-action-edit btn-editar-vet" title="Editar"
                      data-id="${e(v.id)}"
                      data-nome="${e(v.nome)}"
                      data-crmv="${e(v.crmv)}">
                <i class="bi bi-pencil"></i>
              </button>
              <button class="btn-action btn-action-delete btn-delete-vet" title="Excluir"
                      data-id="${e(v.id)}"
                      data-nome="${e(v.nome)}">
                <i class="bi bi-trash3"></i>
              </button>
            </div>
          </td>
        </tr>`;
    }).join('');

    tbody.querySelectorAll('.btn-editar-vet').forEach(btn => {
      btn.addEventListener('click', () => abrirModalEdicao(btn.dataset));
    });

    tbody.querySelectorAll('.btn-delete-vet').forEach(btn => {
      btn.addEventListener('click', () => {
        vetParaDeleteId = btn.dataset.id;
        if (deleteVetNome) deleteVetNome.textContent = btn.dataset.nome;
        bsModalDelete?.show();
      });
    });

    tbody.querySelectorAll('.btn-ger-fazendas-vet').forEach(btn => {
      btn.addEventListener('click', () => abrirModalFazendas(btn.dataset));
    });
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
      carregarVets();
    }
  });

  btnNext?.addEventListener('click', () => {
    if (paginaAtual < totalPaginas) {
      paginaAtual++;
      carregarVets();
    }
  });

  function abrirModalEdicao(data) {
    document.getElementById('modalVetTitle').innerHTML =
      '<i class="bi bi-person-badge-fill text-primary me-2"></i>Editar Veterinário';
    document.getElementById('vetId').value = data.id;
    document.getElementById('vetNome').value = data.nome;
    document.getElementById('vetCrmv').value = data.crmv;
    document.getElementById('vetBtnLabel').textContent = 'Salvar alterações';

    const wrap = document.getElementById('vetFazendaWrap');
    if (wrap) wrap.style.display = 'none';
    if (vetFeedback) vetFeedback.innerHTML = '';

    bsModalVet?.show();
  }

  document.getElementById('btn-novo-vet')?.addEventListener('click', () => {
    document.getElementById('modalVetTitle').innerHTML =
      '<i class="bi bi-person-badge-fill text-primary me-2"></i>Novo Veterinário';
    document.getElementById('vetId').value = '';
    document.getElementById('vetNome').value = '';
    document.getElementById('vetCrmv').value = '';
    document.getElementById('vetBtnLabel').textContent = 'Cadastrar';

    const wrap = document.getElementById('vetFazendaWrap');
    if (wrap) wrap.style.display = '';
    const select = document.getElementById('vetFazenda');
    if (select) select.value = '';
    if (vetFeedback) vetFeedback.innerHTML = '';
  });

  document.getElementById('formVeterinario')?.addEventListener('submit', async function (e) {
    e.preventDefault();

    const id = document.getElementById('vetId').value;
    const nome = document.getElementById('vetNome').value.trim();
    const crmv = document.getElementById('vetCrmv').value.trim();
    const isEdit = !!id;

    if (!nome || !crmv) {
      setVetFeedback('Preencha todos os campos obrigatórios.');
      return;
    }

    const crmvRegex = /^CRMV-[A-Z]{2}\s\d{4,6}$/;
    if (!crmvRegex.test(crmv)) {
      setVetFeedback('CRMV inválido. Use o formato: CRMV-SP 12345.');
      return;
    }

    try {
      if (isEdit) {
        await AgroApp.fetchJson(`/api/veterinarios/${id}`, { method: 'PUT', body: { nome, crmv } });
      } else {
        const idFazendaVal = document.getElementById('vetFazenda')?.value ?? '';
        const idFazenda = idFazendaVal ? Number(idFazendaVal) : null;

        await AgroApp.fetchJson('/api/veterinarios', {
          method: 'POST',
          body: { nome, crmv, idFazenda },
        });
      }

      bsModalVet?.hide();
      await carregarVets();
    } catch (err) {
      setVetFeedback(err.message);
    }
  });

  btnConfirmarDelete?.addEventListener('click', async function () {
    if (!vetParaDeleteId) return;

    try {
      await AgroApp.fetchJson(`/api/veterinarios/${vetParaDeleteId}`, { method: 'DELETE' });
      bsModalDelete?.hide();
      vetParaDeleteId = null;
      await carregarVets();
      AgroApp.toast('Veterinário excluído com sucesso.', 'success');
    } catch (err) {
      AgroApp.toast('Erro ao excluir: ' + err.message, 'error');
    }
  });

  async function abrirModalFazendas(data) {
    vetFazendasAtual = { id: data.id, nome: data.nome };
    document.getElementById('vet-fazendas-nome').textContent = data.nome;

    setFazendasFeedback('', 'success');
    const feedbackEl = document.getElementById('vet-fazendas-feedback');
    if (feedbackEl) feedbackEl.innerHTML = '';

    renderizarFazendasDoVet();
    popularSelectAdicionar();
    bsModalFazendas?.show();
  }

  function renderizarFazendasDoVet() {
    const listEl = document.getElementById('vet-fazendas-vinculadas-list');
    if (!listEl || !vetFazendasAtual) return;

    const vet = todosVets.find(v => String(v.id) === String(vetFazendasAtual.id));
    const fazendas = vet?.fazendas ?? [];

    if (fazendas.length === 0) {
      listEl.innerHTML = '<p class="text-muted small mb-0">Nenhuma fazenda vinculada.</p>';
      return;
    }

    listEl.innerHTML = fazendas.map(f => `
      <div class="d-flex align-items-center justify-content-between mb-2 p-2 border rounded">
        <span><i class="bi bi-house-door text-success me-2"></i>${AgroApp.escapeHtml(f.nome)}</span>
        <button class="btn btn-sm btn-outline-danger btn-remove-fazenda-vet"
                data-fazenda-id="${AgroApp.escapeHtml(String(f.id))}"
                data-fazenda-nome="${AgroApp.escapeHtml(f.nome)}">
          <i class="bi bi-x-lg"></i> Remover
        </button>
      </div>
    `).join('');

    listEl.querySelectorAll('.btn-remove-fazenda-vet').forEach(btn => {
      btn.addEventListener('click', () => removerFazendaDoVet(btn.dataset.fazendaId, btn.dataset.fazendaNome));
    });
  }

  function popularSelectAdicionar() {
    const sel = document.getElementById('vetFazendaParaAdicionar');
    if (!sel || !vetFazendasAtual) return;

    const vet = todosVets.find(v => String(v.id) === String(vetFazendasAtual.id));
    const vinculadasIds = new Set((vet?.fazendas ?? []).map(f => String(f.id)));

    const disponiveis = todasFazendas.filter(f => !vinculadasIds.has(String(f.id)));

    sel.innerHTML = '<option value="">Selecione a fazenda...</option>' +
      disponiveis.map(f =>
        `<option value="${AgroApp.escapeHtml(String(f.id))}">${AgroApp.escapeHtml(f.nome)}</option>`
      ).join('');

    sel.disabled = disponiveis.length === 0;
  }

  document.getElementById('btn-add-fazenda-vet')?.addEventListener('click', async function () {
    const sel = document.getElementById('vetFazendaParaAdicionar');
    const fazendaId = sel?.value ?? '';
    const fazendaObj = todasFazendas.find(f => String(f.id) === String(fazendaId));

    if (!vetFazendasAtual || !fazendaId || !fazendaObj) {
      setFazendasFeedback('Selecione uma fazenda.', 'warning');
      return;
    }

    try {
      await AgroApp.fetchJson(`/api/veterinarios/${vetFazendasAtual.id}/fazendas/${fazendaId}`, {
        method: 'POST',
      });

      await carregarVets();
      abrirModalFazendas(vetFazendasAtual);
      popularSelectAdicionar();

      setFazendasFeedback('Fazenda vinculada com sucesso.', 'success');

    } catch (err) {
      setFazendasFeedback(err.message);
    }
  });

  async function removerFazendaDoVet(fazendaId, fazendaNome) {
    if (!vetFazendasAtual) return;

    const ok = await AgroApp.confirm(
      `Remover a fazenda "${fazendaNome}" deste veterinário?`,
      'O vínculo será desfeito. Esta ação pode ser refeita depois.',
      'warning'
    );
    if (!ok) return;

    try {
      await AgroApp.fetchJson(`/api/veterinarios/${vetFazendasAtual.id}/fazendas/${fazendaId}`, {
        method: 'DELETE',
      });
      setFazendasFeedback('Fazenda removida com sucesso.', 'success');
      await carregarVets();
      abrirModalFazendas(vetFazendasAtual);
      popularSelectAdicionar();
    } catch (err) {
      setFazendasFeedback(err.message);
    }
  }

  filtroQ?.addEventListener('input', async function () {
    termoBusca = this.value.trim();
    paginaAtual = 1;

    clearTimeout(debounceTimer);

    debounceTimer = setTimeout(async () => {
      await carregarVets();
    }, 300);
  });

  btnLimpar?.addEventListener('click', async function () {
    if (filtroQ) filtroQ.value = '';
    termoBusca = '';
    paginaAtual = 1;
    await carregarVets();
  });

  await carregarFazendas();
  await carregarVets();
});
