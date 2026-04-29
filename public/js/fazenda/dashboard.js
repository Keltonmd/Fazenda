// dashboard.js 

document.addEventListener('DOMContentLoaded', async function () {

  function setText(el, value) {
    if (el) el.textContent = value;
  }

  function formatNum(n) {
    return Number(n).toLocaleString('pt-BR', { maximumFractionDigits: 1 });
  }

  // Referências aos elementos do DOM — Linha 1 (contagem geral)
  const statFazendas     = document.getElementById('stat-fazendas');
  const statGados        = document.getElementById('stat-gados');
  const statVeterinarios = document.getElementById('stat-veterinarios');
  const statAbatidos     = document.getElementById('stat-abatidos');

  // Referências — Linha 2 (produção semanal)
  const statLeiteSemanal     = document.getElementById('stat-leite-semanal');
  const statLeiteDetalhe     = document.getElementById('stat-leite-detalhe');
  const statRacaoSemanal     = document.getElementById('stat-racao-semanal');
  const statRacaoDetalhe     = document.getElementById('stat-racao-detalhe');
  const statJovensAltoConsumo = document.getElementById('stat-jovens-alto-consumo');
  const statJovensDetalhe    = document.getElementById('stat-jovens-detalhe');

  // Referências — Tabelas e listas
  const gadosTbody   = document.getElementById('dashboard-gados-tbody');
  const fazendasList = document.getElementById('dashboard-fazendas-list');
  const vetsList     = document.getElementById('dashboard-vets-list');

  // ── FETCH ──
  const [fazendas, gados, veterinarios, resumo, quantVet, quantFazendas] = await Promise.allSettled([
    AgroApp.fetchJson('/api/fazendas/ultimos-cadastros'),
    AgroApp.fetchJson('/api/gados/ultimos-cadastros'),
    AgroApp.fetchJson('/api/veterinarios/ultimos-cadastros'),
    AgroApp.fetchJson('/api/gados/resumo'),
    AgroApp.fetchJson('/api/veterinarios/contagem'),
    AgroApp.fetchJson('/api/fazendas/contagem'),
  ]);

  const fazendasData     = fazendas.status === 'fulfilled' ? (fazendas.value ?? []) : [];
  const gadosData        = gados.status === 'fulfilled' ? (gados.value ?? []) : [];
  const veterinariosData = veterinarios.status === 'fulfilled' ? (veterinarios.value ?? []) : [];
  const resumoData       = resumo.status === 'fulfilled' ? resumo.value : null;

  const quantFazendasData = quantFazendas.status === 'fulfilled' ? quantFazendas.value : null;
  const quantVetData      = quantVet.status === 'fulfilled' ? quantVet.value : null;

  // ── STATS ──
  if (resumoData) {
    setText(statGados, resumoData.gadosVivos ?? 0);
    setText(statAbatidos, resumoData.gadosAbatidos ?? 0);

    setText(statLeiteSemanal, formatNum(resumoData.leiteSemanal ?? 0) + ' L');
    setText(statLeiteDetalhe, 'Produção semanal total');

    setText(statRacaoSemanal, formatNum(resumoData.racaoSemanal ?? 0) + ' kg');
    setText(statRacaoDetalhe, 'Consumo semanal total');

    setText(statJovensAltoConsumo, resumoData.animaisElegiveis ?? 0);

    setText(
      statJovensDetalhe,
      (resumoData.animaisElegiveis ?? 0) > 0
        ? `${resumoData.animaisElegiveis} animal(is) requer(em) atenção`
        : 'Nenhum animal nesta categoria'
    );
  }

  // Fazendas e Veterinários
  setText(statFazendas, quantFazendasData?.quantidadeFazendas ?? 0);
  setText(statVeterinarios, quantVetData?.quantidadeVeterinarios ?? 0);

  const cardAlert = document.querySelector('.stat-card-alert');
  if (cardAlert && resumoData.animaisElegiveis > 0) {
    cardAlert.classList.add('has-alert');
  }

  // ── TABELA GADOS RECENTES ──
  if (!gadosTbody) {
    return;
  }

  if (resumoData.gadosVivos === 0) {
    gadosTbody.innerHTML = `
      <tr><td colspan="5" class="text-center text-muted py-4">
        <i class="bi bi-inbox fs-3 d-block mb-2"></i>Nenhum gado vivo cadastrado.
      </td></tr>`;
  } else {
    const e = AgroApp.escapeHtml;
    const recentes = gadosData?.data ?? [];

    gadosTbody.innerHTML = recentes.map(g => `
      <tr>
        <td class="fw-semibold">${e(g.codigo ?? '—')}</td>
        <td>${e(g.peso)} kg</td>
        <td>${e(g.leite)} L/sem</td>
        <td>${AgroApp.formatDate(g.nascimento)}</td>
        <td>
          <span class="badge-status ${g.abatido ? 'badge-abatido' : 'badge-ativo'}">
            ${g.abatido ? 'Abatido' : 'Ativo'}
          </span>
        </td>
      </tr>`).join('');
  }

  // ── LISTA DE FAZENDAS ──
  if (!fazendasList) {
    return;
  }

  if (fazendasData.length === 0) {
    fazendasList.innerHTML = `<div class="list-group-item text-center text-muted py-3 text-sm">Nenhuma fazenda cadastrada.</div>`;
  } else {
    const e = AgroApp.escapeHtml;
    // Ordenar por ID decrescente e limitar a 5
    const recentesFazendas = fazendasData?.data ?? [];

    fazendasList.innerHTML = recentesFazendas.map(f => `
      <div class="list-group-item d-flex align-items-center gap-3 py-2 px-3">
        <div class="stat-icon stat-icon-small green"><i class="bi bi-house-door"></i></div>
        <div class="flex-grow-1">
          <div class="fw-semibold text-base">${e(f.nome)}</div>
          <div class="text-muted text-xs">${e(f.responsavel)} · ${formatNum(Number(f.tamanhoHA) || 0)} ha</div>
        </div>
      </div>`).join('');
  }

  // ── LISTA DE VETERINÁRIOS ──
  if (!vetsList) {
    return;
  }

  if (veterinariosData.length === 0) {
    vetsList.innerHTML = `<div class="list-group-item text-center text-muted py-3 text-sm">Nenhum veterinário cadastrado.</div>`;
  } else {
    const e = AgroApp.escapeHtml;
    // Ordenar por ID decrescente e limitar a 5
    const recentesVets = veterinariosData?.data ?? [];

    vetsList.innerHTML = recentesVets.map(v => `
      <div class="list-group-item d-flex align-items-center gap-3 py-2 px-3">
        <div class="stat-icon stat-icon-small blue"><i class="bi bi-person-badge"></i></div>
        <div>
          <div class="fw-semibold text-base">${e(v.nome)}</div>
          <div class="text-muted text-xs">CRMV ${e(v.crmv)}</div>
        </div>
      </div>`).join('');
  }

  function formatNum(n) {
    return Number(n).toLocaleString('pt-BR', { maximumFractionDigits: 1 });
  }
});
