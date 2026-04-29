// topbar.js — Interações da topbar
document.addEventListener('DOMContentLoaded', function () {
  const nameEl = document.getElementById('topbar-user-name');
  const nameMenuEl = document.getElementById('topbar-user-name-menu');
  const modalMeusDadosEl = document.getElementById('modalMeusDados');
  const modalEditarEl = document.getElementById('modalEditarConta');
  const modalExcluirEl = document.getElementById('modalExcluirConta');
  const bsModalMeusDados = modalMeusDadosEl ? new bootstrap.Modal(modalMeusDadosEl) : null;
  const bsModalEditar = modalEditarEl ? new bootstrap.Modal(modalEditarEl) : null;
  const bsModalExcluir = modalExcluirEl ? new bootstrap.Modal(modalExcluirEl) : null;

  let perfilCache = null;

  atualizarNomeTopo(AgroApp.getCurrentUserName());
  carregarPerfil().catch(() => {});

  function atualizarNomeTopo(nome) {
    const label = nome || 'Usuário';
    if (nameEl) nameEl.textContent = label;
    if (nameMenuEl) nameMenuEl.textContent = label;
    localStorage.setItem('userName', label);
  }

  async function carregarPerfil(force = false) {
    if (perfilCache && !force) {
      return perfilCache;
    }

    const dados = await AgroApp.fetchJson('/api/usuario');
    perfilCache = dados ?? null;

    if (perfilCache?.nome) {
      atualizarNomeTopo(perfilCache.nome);
    }

    return perfilCache;
  }

  function setLoadingHtml(message = 'Carregando...') {
    return `<div class="text-center py-3"><span class="spinner-border spinner-border-sm me-2"></span>${AgroApp.escapeHtml(message)}</div>`;
  }

  document.getElementById('btn-ver-dados')?.addEventListener('click', async function (e) {
    e.preventDefault();

    const contentEl = document.getElementById('meus-dados-content');
    if (contentEl) {
      contentEl.innerHTML = setLoadingHtml('Carregando dados da conta...');
    }
    bsModalMeusDados?.show();

    try {
      const dados = await carregarPerfil(true);
      if (!contentEl) return;

      contentEl.innerHTML = `
        <div class="d-flex flex-column gap-3">
          <div class="d-flex align-items-center gap-3 p-3 bg-light rounded">
            <i class="bi bi-person-circle fs-2 text-secondary"></i>
            <div>
              <div class="fw-semibold fs-5">${AgroApp.escapeHtml(dados?.nome ?? '—')}</div>
              <div class="text-muted small">${AgroApp.escapeHtml(dados?.email ?? '—')}</div>
            </div>
          </div>
          <div class="row g-2 text-center">
            <div class="col-12">
              <div class="p-2 border rounded">
                <div class="fw-bold text-primary">${AgroApp.escapeHtml(String(dados?.id ?? '—'))}</div>
                <div class="text-muted small">ID da conta</div>
              </div>
            </div>
          </div>
        </div>`;
    } catch (err) {
      if (contentEl) {
        contentEl.innerHTML = `<div class="alert alert-danger py-2">${AgroApp.escapeHtml(err.message)}</div>`;
      }
    }
  });

  document.getElementById('btn-editar-conta')?.addEventListener('click', async function (e) {
    e.preventDefault();

    const feedbackEl = document.getElementById('editar-conta-feedback');
    if (feedbackEl) feedbackEl.innerHTML = '';

    const nomeEl = document.getElementById('editNome');
    const emailEl = document.getElementById('editEmail');
    const senhaEl = document.getElementById('editSenha');

    if (nomeEl) nomeEl.value = '';
    if (emailEl) emailEl.value = '';
    if (senhaEl) senhaEl.value = '';

    bsModalEditar?.show();

    try {
      const dados = await carregarPerfil(true);
      if (nomeEl) nomeEl.value = dados?.nome ?? '';
      if (emailEl) emailEl.value = dados?.email ?? '';
    } catch (err) {
      if (feedbackEl) {
        feedbackEl.innerHTML = `<div class="alert alert-danger py-2">${AgroApp.escapeHtml(err.message)}</div>`;
      }
    }
  });

  document.getElementById('formEditarConta')?.addEventListener('submit', async function (e) {
    e.preventDefault();

    const feedbackEl = document.getElementById('editar-conta-feedback');
    const submitBtn = this.querySelector('button[type="submit"]');
    const nome = document.getElementById('editNome')?.value.trim();
    const email = document.getElementById('editEmail')?.value.trim();
    const senha = document.getElementById('editSenha')?.value ?? '';

    if (!nome || !email) {
      if (feedbackEl) feedbackEl.innerHTML = '<div class="alert alert-danger py-2">Preencha nome e e-mail.</div>';
      return;
    }

    try {
      if (submitBtn) submitBtn.disabled = true;
      await AgroApp.fetchJson('/api/usuario', { method: 'PUT', body: { nome, email } });

      if (senha) {
        await AgroApp.fetchJson('/api/usuario/password', { method: 'PUT', body: { password: senha } });
      }

      perfilCache = null;
      await carregarPerfil(true);
      atualizarNomeTopo(nome);

      if (feedbackEl) {
        feedbackEl.innerHTML = '<div class="alert alert-success py-2">Dados atualizados com sucesso.</div>';
      }

      setTimeout(() => bsModalEditar?.hide(), 900);
    } catch (err) {
      if (feedbackEl) {
        feedbackEl.innerHTML = `<div class="alert alert-danger py-2">${AgroApp.escapeHtml(err.message)}</div>`;
      }
    } finally {
      if (submitBtn) submitBtn.disabled = false;
    }
  });

  document.getElementById('btn-deletar-conta')?.addEventListener('click', function (e) {
    e.preventDefault();
    const feedbackEl = document.getElementById('excluir-conta-feedback');
    if (feedbackEl) feedbackEl.innerHTML = '';
    bsModalExcluir?.show();
  });

  document.getElementById('btn-confirmar-excluir-conta')?.addEventListener('click', async function () {
    const feedbackEl = document.getElementById('excluir-conta-feedback');
    this.disabled = true;

    try {
      await AgroApp.fetchJson('/api/usuario', { method: 'DELETE' });
      AgroApp.clearSession();
      window.location.href = '/';
    } catch (err) {
      if (feedbackEl) {
        feedbackEl.innerHTML = `<div class="alert alert-danger py-2">${AgroApp.escapeHtml(err.message)}</div>`;
      }
      this.disabled = false;
    }
  });

  document.getElementById('btn-logout')?.addEventListener('click', function (e) {
    e.preventDefault();
    AgroApp.clearSession();
    window.location.href = '/';
  });
});
