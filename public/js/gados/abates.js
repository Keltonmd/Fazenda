// abates.js — Relatório e Gerenciamento de Abates

document.addEventListener('DOMContentLoaded', async function () {

  let paginaAtual = 1;
  const limite = 10;
  let totalPaginas = 1;

  const tbody = document.getElementById('abates-tbody');
  const thead = document.getElementById('abates-thead');

  const btnPrev = document.getElementById('btn-prev');
  const btnNext = document.getElementById('btn-next');
  const paginationInfo = document.getElementById('pagination-info');
  
  const badgeCount = document.getElementById('badge-abates-count');
  const badgeParaAbate = document.getElementById('badge-para-abate');

  const summaryTotal = document.getElementById('summary-total-abates');
  const summaryPeso = document.getElementById('summary-peso-medio');
  const summaryLeite = document.getElementById('summary-leite-total');

  const filtroQ = document.getElementById('filtro-abate-q');
  const filtroFazenda = document.getElementById('filtro-abate-fazenda');
  const filtroCondicao = document.getElementById('filtro-abate-condicao');
  const btnLimpar = document.getElementById('btn-limpar-filtro-abate');

  const tabParaAbate = document.getElementById('tab-para-abate');
  const tabAbatidos = document.getElementById('tab-abatidos');

  const bulkWrap = document.getElementById('bulk-action-wrap');
  const btnBulk = document.getElementById('btn-bulk-abater');
  const bulkCount = document.getElementById('bulk-count');

  const modalCancelarEl = document.getElementById('modalCancelarAbate');
  const bsModalCancelar = modalCancelarEl ? new bootstrap.Modal(modalCancelarEl) : null;
  const cancelarGadoNome = document.getElementById('cancelar-abate-gado-nome');
  const cancelarFeedback = document.getElementById('cancelar-abate-feedback');
  const novoCodigoWrap = document.getElementById('novo-codigo-wrap');
  const novoCodigoInput = document.getElementById('novo-codigo-input');
  const btnConfirmarCancelar = document.getElementById('btn-confirmar-cancelar-abate');

  let todasFazendas = [];
  let todosGados = [];
  let todosParaAbate = [];
  let todosAbatidos = [];
  let currentMode = 'para_abate';
  let gadoParaCancelarId = null;
  let resumo = {};
  let termoBusca = '';
  let fazendaFiltro = '';
  let condicaoFiltro = '';
  let debounceTimer = null;

  async function inicializar() {
    setLoading(true);
    await carregarDados()
  }

  async function carregarDados() {
    try {
      const params = new URLSearchParams({
        page: String(paginaAtual),
        limit: String(limite),
        search: termoBusca,
        fazendaId: fazendaFiltro,
        condicao: condicaoFiltro,
      });

      const [fazendasRes, gadosRes, resumoRes] = await Promise.all([
        AgroApp.fetchJson('/api/fazendas/opcoes'),
        AgroApp.fetchJson(
          currentMode === 'para_abate'
            ? `/api/gados/abate?${params.toString()}`
            : `/api/gados/abatidos?${params.toString()}`
        ),
        AgroApp.fetchJson('/api/gados/abates/resumo')
      ]);

      resumo = resumoRes ?? {};
      const fazendas = fazendasRes?.data ?? [];
      const lista = gadosRes?.data ?? [];
      const pagination = gadosRes?.pagination ?? {};

      totalPaginas = pagination.totalPages ?? 1;

      todasFazendas = fazendas;
      todosGados = enriquecerGadosComFazenda(lista, fazendas);
      if (currentMode === 'para_abate') {
        todosParaAbate = todosGados;
        todosAbatidos = [];
      } else {
        todosAbatidos = todosGados;
        todosParaAbate = [];
      }
      renderizar(todosGados);
      renderizarPaginacao();
    } catch (err) {
      todasFazendas = [];
      todosGados = [];
      todosParaAbate = [];
      todosAbatidos = [];
      if (tbody) {
        tbody.innerHTML = `<tr><td colspan="7" class="text-center text-danger py-5">
          ${AgroApp.escapeHtml(err.message)}
        </td></tr>`;
      }
    }

    popularFiltroFazendas();

    if (badgeParaAbate) badgeParaAbate.textContent = resumo.quantidadeParaAbate ?? 0;
    if (badgeCount) badgeCount.textContent = resumo.quantidadeAbatidos ?? 0;
    atualizarResumos(resumo);
  }

  function enriquecerGadosComFazenda(gados, fazendas) {
    const mapaFazendas = new Map((fazendas ?? []).map(f => [String(f.id), f.nome]));

    return (gados ?? []).map(g => ({
      ...g,
      fazendaNome: mapaFazendas.get(String(g.fazendaId ?? '')) ?? '',
    }));
  }

  function renderizarPaginacao() {
    if (paginationInfo) {
      paginationInfo.textContent = `Página ${paginaAtual} de ${totalPaginas}`;
    }

    if (btnPrev) btnPrev.disabled = paginaAtual <= 1;
    if (btnNext) btnNext.disabled = paginaAtual >= totalPaginas;
  }

  btnPrev?.addEventListener('click', async () => {
    if (paginaAtual > 1) {
      paginaAtual--;
      setLoading(true);
      await carregarDados();
    }
  });

  btnNext?.addEventListener('click', async () => {
    if (paginaAtual < totalPaginas) {
      paginaAtual++;
      setLoading(true);
      await carregarDados();
    }
  });

  function popularFiltroFazendas() {
    if (!filtroFazenda) return;

    const valorAtual = filtroFazenda.value;
    filtroFazenda.innerHTML = '<option value="">Todas as fazendas</option>' +
      todasFazendas.map(f =>
        `<option value="${AgroApp.escapeHtml(String(f.id))}">${AgroApp.escapeHtml(f.nome)}</option>`
      ).join('');

    filtroFazenda.value = todasFazendas.some(f => String(f.id) === valorAtual) ? valorAtual : '';
  }

  function atualizarResumos(resumo) {
    if (!resumo) return;

    if (summaryTotal) {
      summaryTotal.textContent = resumo.quantidadeAbatidos ?? 0;
    }

    if (summaryPeso) {
      summaryPeso.textContent = formatNum(resumo.pesoMedioAbatidos) + ' kg';
    }

    if (summaryLeite) {
      summaryLeite.textContent = formatNum(resumo.leitePerdidoAbatidos) + ' L/sem';
    }
  }

  function renderizar(lista) {
    const isParaAbate = currentMode === 'para_abate';

    if (bulkWrap) bulkWrap.style.display = isParaAbate ? 'block' : 'none';
    if (btnBulk) btnBulk.disabled = true;
    if (bulkCount) bulkCount.textContent = '0';

    if (thead) {
      thead.innerHTML = isParaAbate ? `
        <tr>
          <th><input type="checkbox" id="check-all-abates" class="form-check-input"></th>
          <th>Código</th>
          <th>Peso (kg)</th>
          <th>Leite (L/sem)</th>
          <th>Ração (kg/sem)</th>
          <th>Nascimento</th>
          <th>Fazenda</th>
        </tr>` : `
        <tr>
          <th>Código</th>
          <th>Peso (kg)</th>
          <th>Leite (L/sem)</th>
          <th>Ração (kg/sem)</th>
          <th>Nascimento</th>
          <th>Data do abate</th>
          <th>Fazenda</th>
          <th></th>
        </tr>`;
    }

    if (!tbody) return;

    if (lista.length === 0) {
      const filtrosAtivos = Boolean(
        (filtroQ?.value ?? '').trim() ||
        (filtroFazenda?.value ?? '') ||
        (filtroCondicao?.value ?? '')
      );
      const mensagem = filtrosAtivos
        ? 'Nenhum gado encontrado para os filtros selecionados.'
        : (isParaAbate
          ? 'Nenhum gado listado para abate.'
          : 'Nenhum gado abatido encontrado.');
      const colspan = isParaAbate ? 7 : 8;

      tbody.innerHTML = `
        <tr>
          <td colspan="${colspan}" class="abates-empty">
            <i class="bi bi-inbox"></i>
            ${mensagem}
          </td>
        </tr>`;
      return;
    }

    const e = AgroApp.escapeHtml;

    tbody.innerHTML = lista.map(g => {
      const peso = Number(g.peso) || 0;
      const arrobas = calcularArroba(peso);
      const fazendaNome = g.fazendaNome
        ? e(g.fazendaNome)
        : '<span class="text-muted small">—</span>';

      if (isParaAbate) {
        return `
          <tr class="abate-row-highlight" data-id="${e(g.id)}">
            <td><input type="checkbox" class="form-check-input gado-check" value="${e(g.id)}"></td>
            <td>${e(g.codigo ?? '—')}</td>
            <td>
              <div class="fw-semibold">${formatNum(peso)} kg</div>
              <div class="abate-meta">${formatNum(arrobas)} arrobas</div>
            </td>
            <td>${formatNum(g.leite)} L/sem</td>
            <td>${formatNum(g.racao)} kg/sem</td>
            <td>${AgroApp.formatDate(g.nascimento)}</td>
            <td>${fazendaNome}</td>
          </tr>`;
      }

      return `
        <tr class="abate-row-highlight">
          <td>${e(g.codigo ?? '—')}</td>
          <td>
            <div class="fw-semibold">${formatNum(peso)} kg</div>
            <div class="abate-meta">${formatNum(arrobas)} arrobas</div>
          </td>
          <td>${formatNum(g.leite)} L/sem</td>
          <td>${formatNum(g.racao)} kg/sem</td>
          <td>${AgroApp.formatDate(g.nascimento)}</td>
          <td>
            <div class="fw-semibold">${formatDateTime(g.dataAbate)}</div>
            <div class="abate-meta">${formatPrazoCancelamento(g)}</div>
          </td>
          <td>${fazendaNome}</td>
          <td class="text-end">
            ${g.podeCancelarAbate ? `
              <button class="btn btn-sm btn-outline-warning btn-cancelar-abate"
                      title="Cancelar abate"
                      data-id="${e(g.id)}"
                      data-codigo="${e(g.codigo ?? '')}"
                      data-data-abate="${e(AgroApp.parseApiDate(g.dataAbate))}"
                      data-data-limite-cancelamento="${e(AgroApp.parseApiDate(g.dataLimiteCancelamentoAbate))}">
                <i class="bi bi-arrow-counterclockwise me-1"></i>Cancelar
              </button>
            ` : `
              <span class="text-muted small">Prazo encerrado</span>
            `}
          </td>
        </tr>`;
    }).join('');

    if (isParaAbate) {
      setupCheckboxes();
      return;
    }

    tbody.querySelectorAll('.btn-cancelar-abate').forEach(btn => {
      btn.addEventListener('click', () => abrirModalCancelar(btn.dataset));
    });
  }


  function atendeCondicao(gado, condicao) {
    const idade = calcularIdadeAnos(gado.nascimento);
    const leite = Number(gado.leite) || 0;
    const racaoPorDia = calcularRacaoPorDia(Number(gado.racao) || 0);
    const arrobas = calcularArroba(Number(gado.peso) || 0);

    if (condicao === 'idade_maior_5') return idade > 5;
    if (condicao === 'leite_menor_40') return leite < 40;
    if (condicao === 'leite_menor_70_racao_maior_50') return leite < 70 && racaoPorDia > 50;
    if (condicao === 'arroba_maior_18') return arrobas >= 18;

    return true;
  }

  function deveIrParaAbate(gado) {
    return atendeCondicao(gado, 'idade_maior_5')
      || atendeCondicao(gado, 'leite_menor_40')
      || atendeCondicao(gado, 'leite_menor_70_racao_maior_50')
      || atendeCondicao(gado, 'arroba_maior_18');
  }

  function calcularIdadeAnos(nascimento) {
    const valor = AgroApp.parseApiDate(nascimento);
    if (!valor) return 0;

    const data = new Date(valor);
    if (Number.isNaN(data.getTime())) return 0;

    const hoje = new Date();
    let idade = hoje.getFullYear() - data.getFullYear();
    const mes = hoje.getMonth() - data.getMonth();

    if (mes < 0 || (mes === 0 && hoje.getDate() < data.getDate())) {
      idade -= 1;
    }

    return idade;
  }

  function calcularArroba(peso) {
    return (peso * 0.5) / 15;
  }

  function calcularRacaoPorDia(racaoSemanal) {
    return racaoSemanal / 7;
  }

  function setupCheckboxes() {
    const checkAllBtn = document.getElementById('check-all-abates');
    const rowCheckboxes = tbody.querySelectorAll('.gado-check');

    if (checkAllBtn) {
      checkAllBtn.addEventListener('change', e => {
        const checked = e.target.checked;
        rowCheckboxes.forEach(cb => {
          cb.checked = checked;
        });
        updateBulkCount();
      });
    }

    rowCheckboxes.forEach(cb => {
      cb.addEventListener('change', () => {
        updateBulkCount();
        if (checkAllBtn) {
          checkAllBtn.checked = Array.from(rowCheckboxes).every(item => item.checked);
        }
      });
    });
  }

  function updateBulkCount() {
    const checked = tbody.querySelectorAll('.gado-check:checked');
    if (bulkCount) bulkCount.textContent = checked.length;
    if (btnBulk) btnBulk.disabled = checked.length === 0;
  }

  btnBulk?.addEventListener('click', async () => {
    const ids = Array.from(tbody.querySelectorAll('.gado-check:checked')).map(cb => Number(cb.value));
    if (ids.length === 0) return;

    const plural = ids.length === 1 ? '1 animal' : `${ids.length} animais`;
    const ok = await AgroApp.confirm(
      `Confirmar abate de ${plural}?`,
      'Os animais serão movidos para o histórico de abatidos.',
      'warning'
    );
    if (!ok) return;

    btnBulk.disabled = true;
    try {
      await AgroApp.fetchJson('/api/gados/abate', {
        method: 'PUT',
        body: { gados: ids },
      });
      AgroApp.toast(`Abate de ${plural} registrado com sucesso.`, 'success');
      setLoading(true);
      await carregarDados();
    } catch (err) {
      AgroApp.toast('Erro ao registrar abate: ' + err.message, 'error');
    } finally {
      btnBulk.disabled = false;
    }
  });

  async function abrirModalCancelar(data) {
    gadoParaCancelarId = data.id;
    if (cancelarGadoNome) {
      cancelarGadoNome.textContent = `Gado de código ${data.codigo || '—'}`;
    }
    if (cancelarFeedback) {
      const limiteFormatado = formatDateTime(data.dataLimiteCancelamento);
      cancelarFeedback.innerHTML = limiteFormatado !== '—'
        ? `<div class="alert alert-info py-2 mb-2">O cancelamento está disponível até <strong>${AgroApp.escapeHtml(limiteFormatado)}</strong>.</div>`
        : '';
    }
    if (novoCodigoInput) novoCodigoInput.value = '';
    if (novoCodigoWrap) novoCodigoWrap.style.display = 'none';

    const codigo = parseInt(data.codigo, 10);
    if (codigo > 0) {
      try {
        const res = await AgroApp.fetchJson(`/api/gados/codigo-existe/${codigo}`);
        if (res?.existe) {
          if (novoCodigoWrap) novoCodigoWrap.style.display = '';
          if (cancelarFeedback) {
            cancelarFeedback.innerHTML = `<div class="alert alert-warning py-2">
              <i class="bi bi-exclamation-triangle me-1"></i>
              O código <strong>${AgroApp.escapeHtml(String(codigo))}</strong> já está em uso por outro gado vivo.
              Informe um novo código para este animal.
            </div>`;
          }
        }
      } catch (_) {
      }
    }

    bsModalCancelar?.show();
  }

  btnConfirmarCancelar?.addEventListener('click', async function () {
    if (!gadoParaCancelarId) return;

    const body = {};

    if (novoCodigoWrap && novoCodigoWrap.style.display !== 'none') {
      const novoCodigo = parseInt(novoCodigoInput?.value, 10);
      if (!novoCodigo || novoCodigo <= 0) {
        if (cancelarFeedback) {
          cancelarFeedback.innerHTML = '<div class="alert alert-danger py-2">Informe um código válido.</div>';
        }
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
      bsModalCancelar?.hide();
      gadoParaCancelarId = null;
      setLoading(true);
      await carregarDados();
    } catch (err) {
      if (cancelarFeedback) {
        cancelarFeedback.innerHTML = `<div class="alert alert-danger py-2">${AgroApp.escapeHtml(err.message)}</div>`;
      }
    } finally {
      btnConfirmarCancelar.disabled = false;
    }
  });

  function setLoading(on) {
    if (!tbody || !on) return;

    tbody.innerHTML = `
      <tr>
        <td colspan="${currentMode === 'para_abate' ? 7 : 8}" class="text-center text-muted py-5">
          <span class="spinner-border spinner-border-sm me-2"></span>Carregando...
        </td>
      </tr>`;
  }

  function formatNum(n) {
    return Number(n ?? 0).toLocaleString('pt-BR', { maximumFractionDigits: 1 });
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

  function formatPrazoCancelamento(gado) {
    if (gado.podeCancelarAbate) {
      return `Cancelar até ${formatDateTime(gado.dataLimiteCancelamentoAbate)}`;
    }

    return 'Prazo de cancelamento encerrado';
  }

  tabParaAbate?.addEventListener('change', async () => {
    if (!tabParaAbate.checked) return;
    currentMode = 'para_abate';
    paginaAtual = 1;
    setLoading(true);
    await carregarDados()
  });

  tabAbatidos?.addEventListener('change', async () => {
    if (!tabAbatidos.checked) return;
    currentMode = 'abatidos';
    paginaAtual = 1;
    setLoading(true);
    await carregarDados()
  });

  filtroQ?.addEventListener('input', async function () { 
    termoBusca = this.value.trim();
    paginaAtual = 1;

    clearTimeout(debounceTimer);

    debounceTimer = setTimeout(() => {
      setLoading(true);
      carregarDados();
    }, 300);
  });

  filtroFazenda?.addEventListener('change', async function () { 
    fazendaFiltro = this.value;
    paginaAtual = 1;

    setLoading(true);
    await carregarDados();
  });

  filtroCondicao?.addEventListener('change', async function () { 
    condicaoFiltro = this.value;
    paginaAtual = 1;

    setLoading(true);
    await carregarDados();
  });

  btnLimpar?.addEventListener('click', async () => {
    filtroQ.value = '';
    filtroFazenda.value = '';
    filtroCondicao.value = '';

    termoBusca = '';
    fazendaFiltro = '';
    condicaoFiltro = '';
    paginaAtual = 1;

    setLoading(true);
    await carregarDados();
  });

  await inicializar();
});
