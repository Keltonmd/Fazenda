window.AgroApp = (() => {
  const FLASH_STORAGE_KEY = 'agroPendingFlashes';

  const clearSession = () => {
    sessionStorage.removeItem(FLASH_STORAGE_KEY);
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

  const fetchJson = async (url, options = {}) => {
    const config = {
      credentials: 'same-origin',
      headers: {
        Accept: 'application/json',
      },
      ...options,
    };

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
      const serverDetails = _extractErrorDetails(data);
      const serverMsg = serverDetails.join('\n');
      const friendlyMsg = _friendlyError(response.status, serverMsg);
      const error = new Error(serverDetails.length > 1 ? 'Verifique os campos informados.' : friendlyMsg);
      error.details = serverDetails.length > 0 ? serverDetails : [friendlyMsg];
      throw error;
    }

    return data;
  };

  const _normalizeStringMessages = (raw) => {
    return String(raw)
      .split('\n')
      .map(line => line.trim())
      .filter(Boolean)
      .map(line => line.replace(/^Object\([^)]+\)\.?/, ''))
      .filter(Boolean);
  };

  const normalizeMessages = (value) => {
    if (value instanceof Error) {
      return Array.isArray(value.details)
        ? Array.from(new Set(value.details.flatMap(item => normalizeMessages(item))))
        : normalizeMessages(value.message);
    }

    if (!value) {
      return [];
    }

    if (Array.isArray(value)) {
      return Array.from(new Set(value.flatMap(item => normalizeMessages(item))));
    }

    if (typeof value === 'object') {
      return Array.from(new Set(Object.values(value).flatMap(item => normalizeMessages(item))));
    }

    return Array.from(new Set(_normalizeStringMessages(value)));
  };

  const _extractErrorDetails = (data) => {
    return normalizeMessages(data?.errors ?? data?.error ?? data?.message ?? data?.detail ?? null);
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

  const setFeedback = (container, messages, type = 'danger', options = {}) => {
    if (!container) {
      return;
    }

    const normalizedMessages = normalizeMessages(messages);

    if (normalizedMessages.length === 0) {
      container.innerHTML = '';
      return;
    }

    if (normalizedMessages.length === 1) {
      container.innerHTML = `<div class="alert alert-${type} py-2 mb-0">${escapeHtml(normalizedMessages[0])}</div>`;
      return;
    }

    const title = options.title ?? 'Verifique os itens abaixo:';
    const items = normalizedMessages
      .map(message => `<li>${escapeHtml(message)}</li>`)
      .join('');

    container.innerHTML = `
      <div class="alert alert-${type} py-2 mb-0">
        <div class="fw-semibold mb-1">${escapeHtml(title)}</div>
        <ul class="mb-0 ps-3">
          ${items}
        </ul>
      </div>
    `;
  };

  const persistFlash = (type, message) => {
    const flashes = takePendingFlashes(false);
    flashes.push({ type, message });
    sessionStorage.setItem(FLASH_STORAGE_KEY, JSON.stringify(flashes));
  };

  const takePendingFlashes = (clear = true) => {
    try {
      const raw = sessionStorage.getItem(FLASH_STORAGE_KEY);
      const parsed = raw ? JSON.parse(raw) : [];
      const flashes = Array.isArray(parsed) ? parsed : [];

      if (clear) {
        sessionStorage.removeItem(FLASH_STORAGE_KEY);
      }

      return flashes;
    } catch (_) {
      if (clear) {
        sessionStorage.removeItem(FLASH_STORAGE_KEY);
      }

      return [];
    }
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
    normalizeMessages,
    persistFlash,
    setFeedback,
    takePendingFlashes,
    clearSession,
    parseApiDate,
    toast,
    confirm,
  };
})();
