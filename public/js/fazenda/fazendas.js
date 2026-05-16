// fazendas.js

document.addEventListener('DOMContentLoaded', function () {
  const formEl = document.getElementById('formFazendaModal');
  const fazendaFeedback = document.getElementById('fazenda-modal-feedback');
  const deleteFeedback = document.getElementById('delete-fazenda-feedback');
  const deleteFazendaNome = document.getElementById('delete-fazenda-nome');
  const btnConfirmarDelete = document.getElementById('btn-confirmar-delete-fazenda');
  const veterinariosSummary = document.getElementById('fazendaVeterinariosSummary');
  const veterinariosTrigger = document.getElementById('fazendaVeterinariosTrigger');

  const modalFazendaEl = document.getElementById('modalFazenda');
  const modalDeleteEl  = document.getElementById('modalDeleteFazenda');
  const bsModalFazenda = modalFazendaEl ? new bootstrap.Modal(modalFazendaEl) : null;
  const bsModalDelete  = modalDeleteEl ? new bootstrap.Modal(modalDeleteEl) : null;

  let fazendaParaDeleteId = null;
  let fazendaParaDeleteNomeAtual = '';

  function getField(name) {
    return formEl?.querySelector(`[name$="[${name}]"]`) ?? null;
  }

  function getVeterinarioInputs() {
    return Array.from(formEl?.querySelectorAll('.js-fazenda-veterinario-option') ?? []);
  }

  bindRowActions();
  bindVeterinarioPicker();
  updateVeterinarioPickerSummary();

  document.getElementById('btn-nova-fazenda')?.addEventListener('click', function () {
    document.getElementById('modalFazendaTitle').innerHTML = `
      <i class="bi bi-house-door-fill text-success" aria-hidden="true"></i>
      Nova Fazenda
    `;
    document.getElementById('fazendaModalId').value = '';
    const nomeField = getField('nome');
    const responsavelField = getField('responsavel');
    const tamanhoField = getField('tamanhoHA');

    if (nomeField) nomeField.value = '';
    if (responsavelField) responsavelField.value = '';
    if (tamanhoField) tamanhoField.value = '';

    getVeterinarioInputs().forEach(input => {
      input.checked = false;
    });
    document.getElementById('fazendaModalBtnLabel').textContent = 'Cadastrar Fazenda';
    updateVeterinarioPickerSummary();

    AgroApp.setFeedback(fazendaFeedback, []);

    bsModalFazenda?.show();
  });

  document.getElementById('formFazendaModal')?.addEventListener('submit', async function (event) {
    event.preventDefault();

    const nomeField = getField('nome');
    const responsavelField = getField('responsavel');
    const tamanhoField = getField('tamanhoHA');
    const id = document.getElementById('fazendaModalId').value;
    const nome = nomeField?.value.trim() ?? '';
    const responsavel = responsavelField?.value.trim() ?? '';
    const tamanhoHA = parseFloat(tamanhoField?.value ?? '') || 0;
    const veterinariosIds = getVeterinarioInputs()
      .filter(input => input.checked)
      .map(input => Number(input.value))
      .filter(Number.isInteger);
    const isEdit = Boolean(id);

    if (!nome || !responsavel) {
      AgroApp.setFeedback(fazendaFeedback, 'Preencha todos os campos obrigatórios.');
      return;
    }

    const submitBtn = this.querySelector('button[type="submit"]');

    try {
      if (submitBtn) {
        submitBtn.disabled = true;
      }

      if (isEdit) {
        await AgroApp.fetchJson(`/api/fazendas/${id}`, {
          method: 'PUT',
          body: { nome, responsavel, tamanhoHA, veterinariosIds },
        });
      } else {
        await AgroApp.fetchJson('/api/fazendas', {
          method: 'POST',
          body: { nome, responsavel, tamanhoHA, veterinariosIds },
        });
      }

      AgroApp.persistFlash(
        'success',
        isEdit ? 'Fazenda atualizada com sucesso.' : 'Fazenda criada com sucesso.'
      );
      bsModalFazenda?.hide();
      window.location.reload();
    } catch (err) {
      AgroApp.setFeedback(fazendaFeedback, err);
    } finally {
      if (submitBtn) {
        submitBtn.disabled = false;
      }
    }
  });

  btnConfirmarDelete?.addEventListener('click', async function () {
    if (!fazendaParaDeleteId) {
      return;
    }

    try {
      btnConfirmarDelete.disabled = true;
      await AgroApp.fetchJson(`/api/fazendas/${fazendaParaDeleteId}`, { method: 'DELETE' });
      AgroApp.persistFlash('success', `Fazenda "${fazendaParaDeleteNomeAtual}" excluída com sucesso.`);
      bsModalDelete?.hide();
      fazendaParaDeleteId = null;
      fazendaParaDeleteNomeAtual = '';
      window.location.reload();
    } catch (err) {
      AgroApp.setFeedback(deleteFeedback, err);
    } finally {
      btnConfirmarDelete.disabled = false;
    }
  });

  function bindRowActions() {
    document.querySelectorAll('.btn-editar-fazenda').forEach(function (btn) {
      btn.addEventListener('click', function () {
        abrirModalEdicao(btn.dataset);
      });
    });

    document.querySelectorAll('.btn-delete-fazenda').forEach(function (btn) {
      btn.addEventListener('click', function () {
        fazendaParaDeleteId = btn.dataset.id;
        fazendaParaDeleteNomeAtual = btn.dataset.nome ?? '';

        if (deleteFazendaNome) {
          deleteFazendaNome.textContent = btn.dataset.nome;
        }

        AgroApp.setFeedback(deleteFeedback, []);
        bsModalDelete?.show();
      });
    });
  }

  function abrirModalEdicao(data) {
    document.getElementById('modalFazendaTitle').innerHTML = `
      <i class="bi bi-house-door-fill text-success" aria-hidden="true"></i>
      Editar Fazenda
    `;
    document.getElementById('fazendaModalId').value = data.id;
    const nomeField = getField('nome');
    const responsavelField = getField('responsavel');
    const tamanhoField = getField('tamanhoHA');

    if (nomeField) nomeField.value = data.nome ?? '';
    if (responsavelField) responsavelField.value = data.responsavel ?? '';
    if (tamanhoField) tamanhoField.value = data.tamanho ?? '';

    const veterinariosIds = (data.veterinariosIds ?? '')
      .split(',')
      .map(value => value.trim())
      .filter(Boolean);
    getVeterinarioInputs().forEach(input => {
      input.checked = veterinariosIds.includes(input.value);
    });
    document.getElementById('fazendaModalBtnLabel').textContent = 'Salvar Alterações';
    updateVeterinarioPickerSummary();

    AgroApp.setFeedback(fazendaFeedback, []);

    bsModalFazenda?.show();
  }

  function bindVeterinarioPicker() {
    getVeterinarioInputs().forEach(input => {
      input.addEventListener('change', updateVeterinarioPickerSummary);
    });

    document.getElementById('fazendaVeterinariosClear')?.addEventListener('click', function () {
      getVeterinarioInputs().forEach(input => {
        input.checked = false;
      });

      updateVeterinarioPickerSummary();
    });
  }

  function updateVeterinarioPickerSummary() {
    const inputs = getVeterinarioInputs();
    const selectedLabels = inputs
      .filter(input => input.checked)
      .map(input => input.closest('.relation-picker-option')?.dataset.label ?? '')
      .filter(Boolean);

    const summaryText = inputs.length === 0
      ? 'Nenhum veterinário disponível para vincular.'
      : formatSelectionSummary(selectedLabels, 'Nenhum veterinário selecionado.', 'veterinários');

    if (veterinariosSummary) {
      veterinariosSummary.textContent = summaryText;
    }

    if (veterinariosTrigger) {
      veterinariosTrigger.textContent = summaryText;
    }
  }

  function formatSelectionSummary(labels, emptyText, pluralLabel) {
    if (labels.length === 0) {
      return emptyText;
    }

    if (labels.length <= 2) {
      return labels.join(', ');
    }

    return `${labels.length} ${pluralLabel} selecionados`;
  }
});
