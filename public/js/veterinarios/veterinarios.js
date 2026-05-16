// veterinarios.js

document.addEventListener('DOMContentLoaded', function () {
  const formEl = document.getElementById('formVeterinario');
  const vetFeedback = document.getElementById('vet-form-feedback');
  const deleteFeedback = document.getElementById('delete-vet-feedback');
  const deleteVetNome = document.getElementById('delete-vet-nome');
  const btnConfirmarDelete = document.getElementById('btn-confirmar-delete-vet');
  const fazendasSummary = document.getElementById('vetFazendasSummary');
  const fazendasTrigger = document.getElementById('vetFazendasTrigger');
  const fazendasFiltroInput = document.getElementById('vet-fazendas-filtro');

  const modalVetEl = document.getElementById('modalVeterinario');
  const modalDeleteEl = document.getElementById('modalDeleteVet');
  const modalFazendasEl = document.getElementById('modalVetFazendas');
  const bsModalVet = modalVetEl ? new bootstrap.Modal(modalVetEl) : null;
  const bsModalDelete = modalDeleteEl ? new bootstrap.Modal(modalDeleteEl) : null;
  const bsModalFazendas = modalFazendasEl ? new bootstrap.Modal(modalFazendasEl) : null;

  let vetParaDeleteId = null;
  let vetParaDeleteNomeAtual = '';
  let vetFazendasAtual = null;

  // Fazendas disponíveis carregadas do JSON emitido pelo Twig (sem fetch)
  const todasFazendasJson = document.getElementById('todas-fazendas-json');
  let todasFazendas = [];
  try {
    todasFazendas = todasFazendasJson ? JSON.parse(todasFazendasJson.textContent) : [];
  } catch (_) {
    todasFazendas = [];
  }

  function getField(name) {
    return formEl?.querySelector(`[name$="[${name}]"]`) ?? null;
  }

  function getFazendaInputs() {
    return Array.from(formEl?.querySelectorAll('.js-veterinario-fazenda-option') ?? []);
  }

  bindRowActions();
  bindFazendasPicker();
  updateFazendasPickerSummary();

  document.getElementById('btn-novo-vet')?.addEventListener('click', function () {
    document.getElementById('modalVetTitle').innerHTML =
      '<i class="bi bi-person-badge-fill text-primary me-2"></i>Novo Veterinário';
    document.getElementById('vetId').value = '';
    const nomeField = getField('nome');
    const crmvField = getField('crmv');
    if (nomeField) nomeField.value = '';
    if (crmvField) crmvField.value = '';
    document.getElementById('vetBtnLabel').textContent = 'Cadastrar';
    getFazendaInputs().forEach(input => {
      input.checked = false;
    });
    updateFazendasPickerSummary();

    AgroApp.setFeedback(vetFeedback, []);
  });

  document.getElementById('formVeterinario')?.addEventListener('submit', async function (event) {
    event.preventDefault();

    const nomeField = getField('nome');
    const crmvField = getField('crmv');
    const id = document.getElementById('vetId').value;
    const nome = nomeField?.value.trim() ?? '';
    const crmv = crmvField?.value.trim() ?? '';
    const fazendasIds = getFazendaInputs()
      .filter(input => input.checked)
      .map(input => Number(input.value))
      .filter(Number.isInteger);
    const isEdit = Boolean(id);

    if (!nome || !crmv) {
      setVetFeedback('Preencha todos os campos obrigatórios.');
      return;
    }

    const crmvRegex = /^CRMV-[A-Z]{2}\s\d{4,6}$/;
    if (!crmvRegex.test(crmv)) {
      setVetFeedback('CRMV inválido. Use o formato: CRMV-SP 12345.');
      return;
    }

    const submitBtn = this.querySelector('button[type="submit"]');

    try {
      if (submitBtn) {
        submitBtn.disabled = true;
      }

      if (isEdit) {
        await AgroApp.fetchJson(`/api/veterinarios/${id}`, {
          method: 'PUT',
          body: { nome, crmv, fazendasIds },
        });
      } else {
        await AgroApp.fetchJson('/api/veterinarios', {
          method: 'POST',
          body: { nome, crmv, fazendasIds },
        });
      }

      AgroApp.persistFlash(
        'success',
        isEdit ? 'Veterinário atualizado com sucesso.' : 'Veterinário criado com sucesso.'
      );
      bsModalVet?.hide();
      window.location.reload();
    } catch (err) {
      setVetFeedback(err);
    } finally {
      if (submitBtn) {
        submitBtn.disabled = false;
      }
    }
  });

  btnConfirmarDelete?.addEventListener('click', async function () {
    if (!vetParaDeleteId) {
      return;
    }

    try {
      btnConfirmarDelete.disabled = true;
      await AgroApp.fetchJson(`/api/veterinarios/${vetParaDeleteId}`, { method: 'DELETE' });
      AgroApp.persistFlash('success', `Veterinário "${vetParaDeleteNomeAtual}" excluído com sucesso.`);
      bsModalDelete?.hide();
      vetParaDeleteId = null;
      vetParaDeleteNomeAtual = '';
      window.location.reload();
    } catch (err) {
      AgroApp.setFeedback(deleteFeedback, err);
    } finally {
      btnConfirmarDelete.disabled = false;
    }
  });

  document.getElementById('btn-add-fazenda-vet')?.addEventListener('click', async function () {
    const select = document.getElementById('vetFazendaParaAdicionar');
    const fazendaId = select?.value ?? '';
    const fazendaObj = todasFazendas.find(f => String(f.id) === String(fazendaId));

    if (!vetFazendasAtual || !fazendaId || !fazendaObj) {
      setFazendasFeedback('Selecione uma fazenda.', 'warning');
      return;
    }

    try {
      await AgroApp.fetchJson(`/api/veterinarios/${vetFazendasAtual.id}/fazendas/${fazendaId}`, {
        method: 'POST',
      });

      AgroApp.persistFlash('success', 'Fazenda vinculada ao veterinário com sucesso.');
      window.location.reload();
    } catch (err) {
      setFazendasFeedback(err);
    }
  });

  function bindRowActions() {
    document.querySelectorAll('.btn-editar-vet').forEach(btn => {
      btn.addEventListener('click', function () {
        abrirModalEdicao(btn.dataset);
      });
    });

    document.querySelectorAll('.btn-delete-vet').forEach(btn => {
      btn.addEventListener('click', function () {
        vetParaDeleteId = btn.dataset.id;
        vetParaDeleteNomeAtual = btn.dataset.nome ?? '';

        if (deleteVetNome) {
          deleteVetNome.textContent = btn.dataset.nome;
        }

        AgroApp.setFeedback(deleteFeedback, []);
        bsModalDelete?.show();
      });
    });

    document.querySelectorAll('.btn-ger-fazendas-vet').forEach(btn => {
      btn.addEventListener('click', function () {
        abrirModalFazendas(btn.dataset, 'gerenciar');
      });
    });

    document.querySelectorAll('.btn-consultar-fazendas-vet').forEach(btn => {
      btn.addEventListener('click', function () {
        abrirModalFazendas(btn.dataset, 'consultar');
      });
    });
  }

  function abrirModalEdicao(data) {
    document.getElementById('modalVetTitle').innerHTML =
      '<i class="bi bi-person-badge-fill text-primary me-2"></i>Editar Veterinário';
    document.getElementById('vetId').value = data.id ?? '';
    const nomeField = getField('nome');
    const crmvField = getField('crmv');
    if (nomeField) nomeField.value = data.nome ?? '';
    if (crmvField) crmvField.value = data.crmv ?? '';
    document.getElementById('vetBtnLabel').textContent = 'Salvar alterações';
    const fazendasIds = new Set(splitIds(data.fazendasIds));
    getFazendaInputs().forEach(input => {
      input.checked = fazendasIds.has(input.value);
    });
    updateFazendasPickerSummary();

    AgroApp.setFeedback(vetFeedback, []);

    bsModalVet?.show();
  }

  function abrirModalFazendas(data, modo = 'gerenciar') {
    vetFazendasAtual = {
      id: data.id,
      nome: data.nome,
      fazendas: parseFazendas(data.fazendas),
      modo,
    };

    const nomeEl = document.getElementById('vet-fazendas-nome');
    const titleEl = document.getElementById('modalVetFazendasTitle');
    if (nomeEl) {
      nomeEl.textContent = data.nome ?? '';
    }
    if (titleEl) {
      titleEl.innerHTML = modo === 'consultar'
        ? '<i class="bi bi-eye text-secondary" aria-hidden="true"></i>Fazendas do Veterinário'
        : '<i class="bi bi-house-door-fill text-success" aria-hidden="true"></i>Fazendas do Veterinário';
    }

    renderizarFazendasDoVet();
    popularSelectAdicionar();
    limparFazendasFeedback();
    if (fazendasFiltroInput) {
      fazendasFiltroInput.value = '';
    }
    alternarModoModalFazendas();
    bsModalFazendas?.show();
  }

  function renderizarFazendasDoVet() {
    const listEl = document.getElementById('vet-fazendas-vinculadas-list');
    const resumoEl = document.getElementById('vet-fazendas-resumo');

    if (!listEl || !vetFazendasAtual) {
      return;
    }

    if (vetFazendasAtual.fazendas.length === 0) {
      listEl.innerHTML = '<p class="text-muted small mb-0">Nenhuma fazenda vinculada.</p>';
      if (resumoEl) {
        resumoEl.textContent = 'Nenhuma fazenda vinculada.';
      }
      return;
    }

    if (resumoEl) {
      resumoEl.textContent = vetFazendasAtual.fazendas.length === 1
        ? '1 fazenda vinculada.'
        : `${vetFazendasAtual.fazendas.length} fazendas vinculadas.`;
    }

    listEl.innerHTML = vetFazendasAtual.fazendas.map(fazenda => `
      <div class="vet-fazenda-card" data-search="${AgroApp.escapeHtml(fazenda.nome.toLowerCase())}">
        <span class="vet-fazenda-card-name"><i class="bi bi-house-door text-success me-2"></i>${AgroApp.escapeHtml(fazenda.nome)}</span>
        <button class="btn btn-sm btn-outline-danger btn-remove-fazenda-vet"
                data-fazenda-id="${AgroApp.escapeHtml(String(fazenda.id))}"
                data-fazenda-nome="${AgroApp.escapeHtml(fazenda.nome)}"
                type="button">
          <i class="bi bi-x-lg"></i> Remover
        </button>
      </div>
    `).join('');

    listEl.querySelectorAll('.btn-remove-fazenda-vet').forEach(btn => {
      btn.addEventListener('click', function () {
        removerFazendaDoVet(btn.dataset.fazendaId, btn.dataset.fazendaNome);
      });
    });

    aplicarFiltroFazendasVinculadas();
  }

  function popularSelectAdicionar() {
    const select = document.getElementById('vetFazendaParaAdicionar');
    const addButton = document.getElementById('btn-add-fazenda-vet');

    if (!select || !vetFazendasAtual) {
      return;
    }

    const vinculadasIds = new Set(vetFazendasAtual.fazendas.map(fazenda => String(fazenda.id)));
    const disponiveis = todasFazendas.filter(fazenda => !vinculadasIds.has(String(fazenda.id)));

    select.innerHTML = '<option value="">Selecione a fazenda...</option>' +
      disponiveis.map(fazenda =>
        `<option value="${AgroApp.escapeHtml(String(fazenda.id))}">${AgroApp.escapeHtml(fazenda.nome)}</option>`
      ).join('');

    select.disabled = disponiveis.length === 0;
    if (addButton) {
      addButton.disabled = vetFazendasAtual.modo === 'consultar' || disponiveis.length === 0;
    }
  }

  async function removerFazendaDoVet(fazendaId, fazendaNome) {
    if (!vetFazendasAtual) {
      return;
    }

    const ok = await AgroApp.confirm(
      `Remover a fazenda "${fazendaNome}" deste veterinário?`,
      'O vínculo será desfeito. Esta ação pode ser refeita depois.',
      'warning'
    );

    if (!ok) {
      return;
    }

    try {
      await AgroApp.fetchJson(`/api/veterinarios/${vetFazendasAtual.id}/fazendas/${fazendaId}`, {
        method: 'DELETE',
      });

      AgroApp.persistFlash('success', 'Fazenda removida do veterinário com sucesso.');
      window.location.reload();
    } catch (err) {
      setFazendasFeedback(err);
    }
  }

  function bindFazendasPicker() {
    getFazendaInputs().forEach(input => {
      input.addEventListener('change', updateFazendasPickerSummary);
    });

    document.getElementById('vetFazendasClear')?.addEventListener('click', function () {
      getFazendaInputs().forEach(input => {
        input.checked = false;
      });

      updateFazendasPickerSummary();
    });
  }

  function alternarModoModalFazendas() {
    const addSectionButton = document.getElementById('btn-add-fazenda-vet');
    const addSelect = document.getElementById('vetFazendaParaAdicionar');
    const feedbackHelp = document.getElementById('vet-fazendas-feedback');
    const modeNote = document.getElementById('vet-fazendas-mode-note');
    const addSection = document.getElementById('vet-fazendas-add-section');
    const isConsulta = vetFazendasAtual?.modo === 'consultar';

    document.querySelectorAll('.btn-remove-fazenda-vet').forEach(button => {
      button.disabled = isConsulta;
      button.classList.toggle('disabled', isConsulta);
    });

    if (addSectionButton) {
      addSectionButton.disabled = isConsulta || addSectionButton.disabled;
    }

    if (addSelect) {
      addSelect.disabled = isConsulta || addSelect.disabled;
    }

    if (modeNote) {
      modeNote.hidden = isConsulta;
    }

    if (addSection) {
      addSection.hidden = isConsulta;
    }

    if (feedbackHelp) {
      AgroApp.setFeedback(
        feedbackHelp,
        [],
        'info'
      );
    }
  }

  function aplicarFiltroFazendasVinculadas() {
    const listEl = document.getElementById('vet-fazendas-vinculadas-list');

    if (!listEl) {
      return;
    }

    const termo = (fazendasFiltroInput?.value ?? '').trim().toLowerCase();
    const cards = Array.from(listEl.querySelectorAll('.vet-fazenda-card'));
    let visiveis = 0;

    cards.forEach(card => {
      const corresponde = !termo || (card.dataset.search ?? '').includes(termo);
      card.style.display = corresponde ? '' : 'none';

      if (corresponde) {
        visiveis += 1;
      }
    });

    const emptyState = listEl.querySelector('.vet-fazendas-filter-empty');

    if (emptyState) {
      emptyState.remove();
    }

    if (cards.length > 0 && visiveis === 0) {
      const message = document.createElement('p');
      message.className = 'text-muted small mb-0 vet-fazendas-filter-empty';
      message.textContent = 'Nenhuma fazenda encontrada para este filtro.';
      listEl.appendChild(message);
    }
  }

  function splitIds(value) {
    return (value ?? '')
      .split(',')
      .map(item => item.trim())
      .filter(Boolean);
  }

  function parseFazendas(value) {
    if (!value) {
      return [];
    }

    try {
      const parsed = JSON.parse(value);
      return Array.isArray(parsed) ? parsed : [];
    } catch (_) {
      return [];
    }
  }

  function setVetFeedback(message, type = 'danger') {
    AgroApp.setFeedback(vetFeedback, message, type);
  }

  function setFazendasFeedback(message, type = 'danger') {
    const feedbackEl = document.getElementById('vet-fazendas-feedback');

    AgroApp.setFeedback(feedbackEl, message, type);
  }

  function limparFazendasFeedback() {
    const feedbackEl = document.getElementById('vet-fazendas-feedback');

    AgroApp.setFeedback(feedbackEl, []);
  }

  function updateFazendasPickerSummary() {
    const inputs = getFazendaInputs();
    const selectedLabels = inputs
      .filter(input => input.checked)
      .map(input => input.closest('.relation-picker-option')?.dataset.label ?? '')
      .filter(Boolean);

    const summaryText = inputs.length === 0
      ? 'Nenhuma fazenda disponível para vincular.'
      : formatSelectionSummary(selectedLabels, 'Nenhuma fazenda selecionada.', 'fazendas');

    if (fazendasSummary) {
      fazendasSummary.textContent = summaryText;
    }

    if (fazendasTrigger) {
      fazendasTrigger.textContent = summaryText;
    }
  }

  function formatSelectionSummary(labels, emptyText, pluralLabel) {
    if (labels.length === 0) {
      return emptyText;
    }

    if (labels.length <= 2) {
      return labels.join(', ');
    }

    return `${labels.length} ${pluralLabel} selecionadas`;
  }

  fazendasFiltroInput?.addEventListener('input', aplicarFiltroFazendasVinculadas);
});
