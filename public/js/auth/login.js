// login.js — Lógica da página de login

document.addEventListener('DOMContentLoaded', function () {
  const form       = document.getElementById('loginForm');
  const feedback   = document.getElementById('loginFeedback');
  const emailInput = document.getElementById('loginEmail') || form?.querySelector('input[type="email"]');
  const pwInput    = document.getElementById('loginPassword') || form?.querySelector('input[type="password"]');
  const submitBtn  = document.getElementById('login-submit-btn');
  const btnContent = document.getElementById('login-btn-content');
  const toggleBtn  = document.getElementById('toggleLoginPassword');
  const toggleIcon = document.getElementById('toggleLoginPasswordIcon');
  const emailErrors = document.getElementById('loginEmailErrors');
  const passwordErrors = document.getElementById('loginPasswordErrors');

  if (!form) return;

  if (toggleBtn && pwInput) {
    toggleBtn.addEventListener('click', function (event) {
      event.preventDefault();

      const isHidden = pwInput.type === 'password';
      pwInput.type = isHidden ? 'text' : 'password';
      toggleBtn.setAttribute('aria-label', isHidden ? 'Ocultar senha' : 'Mostrar senha');
      toggleBtn.setAttribute('aria-pressed', String(isHidden));

      if (toggleIcon) {
        toggleIcon.className = isHidden ? 'bi bi-eye-slash' : 'bi bi-eye';
      }
    });
  }

  [emailInput, pwInput].filter(Boolean).forEach(function (input) {
    input.addEventListener('input', function () {
      validateField(input, { keepFeedback: true });
    });

    input.addEventListener('blur', function () {
      validateField(input);
    });
  });

  form.addEventListener('submit', async function (e) {
    e.preventDefault();

    clearFeedback();

    const firstInvalidField = validateForm();

    if (firstInvalidField) {
      showFeedback('Verifique os campos destacados.', 'danger');
      firstInvalidField.focus();
      return;
    }

    const email = emailInput.value.trim();
    const password = pwInput.value;

    setLoading(true);
    showFeedback('Autenticando...', 'info');

    try {
      const data = await AgroApp.fetchJson('/api/login_check', {
        method: 'POST',
        body: { email, password },
        skipAuthRedirect: true,
      });

      const userName = resolveUserName(data, email);
      localStorage.removeItem('jwtToken');
      localStorage.setItem('userName', userName);

      showFeedback('Login realizado com sucesso. Redirecionando...', 'success');
      setTimeout(function () {
        window.location.replace('/dashboard');
      }, 350);
    } catch (err) {
      const msg = normalizeLoginErrorMessage(err.message);
      showFeedback(msg, 'danger');
      pwInput.value = '';
      pwInput?.focus();
      setLoading(false);
    }
  });

  function resolveUserName(data, fallbackEmail) {
    if (data?.token) {
      const payload = parseJwtPayload(data.token);
      return payload?.username || payload?.email || fallbackEmail;
    }

    if (typeof data?.username === 'string' && data.username.trim()) {
      return data.username.trim();
    }

    if (typeof data?.user === 'object') {
      return data.user?.nome || data.user?.name || data.user?.email || fallbackEmail;
    }

    return fallbackEmail;
  }

  function normalizeLoginErrorMessage(message) {
    const normalized = String(message || '').trim();
    const lowered = normalized.toLowerCase();

    if (!normalized) {
      return 'Não foi possível realizar o login. Tente novamente.';
    }

    if (
      lowered.includes('invalid credentials') ||
      lowered.includes('bad credentials') ||
      lowered.includes('credenciais invalidas') ||
      lowered.includes('credenciais inválidas') ||
      lowered.includes('email ou senha') ||
      lowered.includes('e-mail ou senha') ||
      lowered.includes('sessao expirada') ||
      lowered.includes('sessão expirada')
    ) {
      return 'E-mail ou senha incorretos. Tente novamente.';
    }

    if (
      lowered.includes('too many') ||
      lowered.includes('muitas tentativas') ||
      lowered.includes('try again later') ||
      lowered.includes('temporarily blocked')
    ) {
      return 'Muitas tentativas de login. Aguarde um momento e tente novamente.';
    }

    if (
      lowered.includes('networkerror') ||
      lowered.includes('failed to fetch') ||
      lowered.includes('load failed')
    ) {
      return 'Não foi possível conectar ao servidor. Verifique sua conexão e tente novamente.';
    }

    return normalized;
  }

  function parseJwtPayload(token) {
    try {
      const base64 = token.split('.')[1].replace(/-/g, '+').replace(/_/g, '/');
      return JSON.parse(atob(base64));
    } catch {
      return null;
    }
  }

  function validateForm() {
    let firstInvalidField = null;

    [emailInput, pwInput].filter(Boolean).forEach(function (input) {
      const isValid = validateField(input, { keepFeedback: true });

      if (!isValid && !firstInvalidField) {
        firstInvalidField = input;
      }
    });

    return firstInvalidField;
  }

  function validateField(input, options = {}) {
    const message = getValidationMessage(input);

    if (message) {
      setFieldError(input, message);
      return false;
    }

    clearFieldError(input);

    if (!options.keepFeedback && !hasInlineErrors()) {
      clearFeedback();
    }

    return true;
  }

  function getValidationMessage(input) {
    if (!input) {
      return '';
    }

    const value = input.value.trim();

    if (input === emailInput) {
      if (!value) {
        return input.dataset.errorRequired || 'Informe o e-mail.';
      }

      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
        return input.dataset.errorEmail || 'Informe um e-mail válido.';
      }

      return '';
    }

    if (input === pwInput) {
      if (!value) {
        return input.dataset.errorRequired || 'Informe a senha.';
      }

      const minLength = Number(input.getAttribute('minlength') || 0);

      if (minLength > 0 && value.length < minLength) {
        return input.dataset.errorMinlength || `A senha deve ter pelo menos ${minLength} caracteres.`;
      }
    }

    return '';
  }

  function setFieldError(input, message) {
    const container = getErrorContainer(input);

    input.classList.add('is-invalid');
    input.setAttribute('aria-invalid', 'true');

    if (container) {
      container.textContent = message;
    }
  }

  function clearFieldError(input) {
    const container = getErrorContainer(input);

    input.classList.remove('is-invalid');
    input.removeAttribute('aria-invalid');

    if (container) {
      container.textContent = '';
    }
  }

  function getErrorContainer(input) {
    if (input === emailInput) {
      return emailErrors;
    }

    if (input === pwInput) {
      return passwordErrors;
    }

    return null;
  }

  function hasInlineErrors() {
    return Boolean(form.querySelector('.auth-input.is-invalid'));
  }

  function showFeedback(msg, type) {
    feedback.className = 'auth-feedback';
    if (type) feedback.classList.add(`is-${type}`);
    feedback.textContent = msg;
  }

  function clearFeedback() {
    feedback.className = 'auth-feedback';
    feedback.textContent = '';
  }

  function setLoading(loading) {
    submitBtn.disabled = loading;
    btnContent.innerHTML = loading
      ? '<span class="auth-spinner"></span> Autenticando...'
      : '<i class="bi bi-box-arrow-in-right me-1"></i> Entrar no painel';
  }
});
