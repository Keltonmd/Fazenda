window.AgroApp = (() => {
  // ── Token JWT ──
  const getToken = () => localStorage.getItem('jwtToken') ?? null;

  const setToken = (token) => localStorage.setItem('jwtToken', token);

  const clearSession = () => {
    localStorage.removeItem('jwtToken');
    localStorage.removeItem('userName');
  };

 
  // Lê o nome do usuário logado.
  const getCurrentUserName = () => localStorage.getItem('userName') ?? 'Usuário';

  const parseJwtPayload = (token) => {
    try {
      const base64 = token.split('.')[1].replace(/-/g, '+').replace(/_/g, '/');
      return JSON.parse(atob(base64));
    } catch {
      return null;
    }
  };



  const escapeHtml = (value) => {
    return String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  };

  const parseApiDate = (value) => {
    if (!value) {
      return '';
    }

    if (typeof value === 'string') {
      return value;
    }

    if (typeof value === 'object' && value.date) {
      return value.date;
    }

    return '';
  };

  const formatDate = (value) => {
    const parsed = parseApiDate(value);
    if (!parsed) {
      return '-';
    }

    const date = new Date(parsed);
    if (Number.isNaN(date.getTime())) {
      return escapeHtml(parsed);
    }

    return new Intl.DateTimeFormat('pt-BR').format(date);
  };

  const fetchJson = async (url, options = {}) => {
    const token = getToken();

    const config = {
      headers: {
        Accept: 'application/json',
      },
      ...options,
    };

    // Adiciona Authorization se houver token 
    if (token) {
      config.headers['Authorization'] = `Bearer ${token}`;
    }

    if (options.body && !(options.body instanceof FormData)) {
      config.body = JSON.stringify(options.body);
      config.headers = {
        ...config.headers,
        'Content-Type': 'application/json',
      };
    }

    const response = await fetch(url, config);
    let data = null;

    try {
      data = await response.json();
    } catch (error) {
      data = null;
    }

    if (response.status === 401 && !options.skipAuthRedirect) {
      clearSession();
      window.location.href = '/';
      return;
    }

    if (!response.ok) {
      const serverMsg = data?.error || data?.message || data?.detail || null;
      const friendlyMsg = _friendlyError(response.status, serverMsg);
      throw new Error(friendlyMsg);
    }

    return data;
  };

  function _friendlyError(status, serverMsg) {
    if (serverMsg && !/^(\d{3}|internal|exception|trace|stack)/i.test(serverMsg)) {
      return serverMsg;
    }
    switch (status) {
      case 400: return 'Dados inválidos. Verifique as informações e tente novamente.';
      case 401: return 'Sessão expirada. Faça login novamente.';
      case 403: return 'Você não tem permissão para realizar esta ação.';
      case 404: return 'Recurso não encontrado.';
      case 409: return 'Conflito: este registro já existe.';
      case 422: return 'Dados inválidos. Verifique as informações e tente novamente.';
      case 429: return 'Muitas tentativas. Aguarde um momento e tente novamente.';
      case 500:
      case 502:
      case 503: return 'Serviço temporariamente indisponível. Tente novamente em instantes.';
      default:  return 'Ocorreu um erro inesperado. Tente novamente.';
    }
  }

  // ── Toast de feedback visual ──────
  /**
   * @param {string} message
   * @param {'success'|'error'|'warning'|'info'} type
   * @param {number} duration 
   */
  const toast = (message, type = 'success', duration = 4000) => {
    const icons = {
      success: 'bi bi-check-circle-fill',
      error:   'bi bi-x-circle-fill',
      warning: 'bi bi-exclamation-triangle-fill',
      info:    'bi bi-info-circle-fill',
    };
    const titles = {
      success: 'Sucesso',
      error:   'Erro',
      warning: 'Atenção',
      info:    'Informação',
    };

    let container = document.getElementById('agro-toast-container');
    if (!container) {
      container = document.createElement('div');
      container.id = 'agro-toast-container';
      document.body.appendChild(container);
    }

    const el = document.createElement('div');
    el.className = `agro-toast agro-toast--${type}`;
    el.innerHTML = `
      <span class="agro-toast-icon"><i class="${icons[type] || icons.info}"></i></span>
      <div class="agro-toast-body">
        <div class="agro-toast-title">${titles[type] || 'Aviso'}</div>
        <div>${escapeHtml(message)}</div>
      </div>
      <button class="agro-toast-close" aria-label="Fechar"><i class="bi bi-x-lg"></i></button>
    `;

    const close = () => {
      el.classList.add('hiding');
      setTimeout(() => el.remove(), 320);
    };

    el.querySelector('.agro-toast-close').addEventListener('click', close);
    container.appendChild(el);
    setTimeout(close, duration);
  };

  // ── Modal de confirmação visual ──
  /**
   * @param {string}  message 
   * @param {string}  [subtext] 
   * @param {'danger'|'warning'|'info'} [variant] 
   */
  const confirm = (message, subtext = 'Esta ação não pode ser desfeita.', variant = 'danger') => {
    return new Promise((resolve) => {
      const icons = { danger: 'bi bi-trash3-fill', warning: 'bi bi-exclamation-triangle-fill', info: 'bi bi-question-circle-fill' };
      const labels = { danger: 'Confirmar exclusão', warning: 'Confirmar', info: 'Confirmar' };
      const btnClasses = { danger: 'btn-danger', warning: 'btn-warning', info: 'btn-primary' };

      const msgEl      = document.getElementById('modalConfirmMessage');
      const subEl      = document.getElementById('modalConfirmSubtext');
      const iconWrap   = document.getElementById('modalConfirmIconWrap');
      const iconEl     = document.getElementById('modalConfirmIcon');
      const btnOk      = document.getElementById('modalConfirmOk');
      const modalEl    = document.getElementById('modalConfirm');

      if (!modalEl) {
        // Modal não encontrado: rejeita silenciosamente.
        // Inclua {% include 'components/modal_confirm.html.twig' %} na página.
        console.warn('[AgroApp.confirm] #modalConfirm não encontrado no DOM. Inclua o componente na página.');
        resolve(false);
        return;
      }

      if (msgEl)    msgEl.textContent    = message;
      if (subEl)    subEl.textContent    = subtext;
      if (iconWrap) { iconWrap.className = `modal-confirm-icon-wrap ${variant}`; }
      if (iconEl)   { iconEl.className   = icons[variant] || icons.danger; }
      if (btnOk) {
        btnOk.className = `btn ${btnClasses[variant] || 'btn-danger'}`;
        btnOk.textContent = labels[variant] || 'Confirmar';
      }

      const bsModal = new bootstrap.Modal(modalEl);
      let answered = false;

      const onOk = () => {
        answered = true;
        bsModal.hide();
        resolve(true);
      };

      const onHide = () => {
        if (!answered) resolve(false);
        btnOk?.removeEventListener('click', onOk);
        modalEl.removeEventListener('hidden.bs.modal', onHide);
      };

      btnOk?.addEventListener('click', onOk);
      modalEl.addEventListener('hidden.bs.modal', onHide, { once: true });
      bsModal.show();
    });
  };

  return {
    escapeHtml,
    fetchJson,
    formatDate,
    getCurrentUserName,
    getToken,
    setToken,
    clearSession,
    parseApiDate,
    toast,
    confirm,
  };
})();


