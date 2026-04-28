// register.js — Lógica da página de cadastro de usuário

document.addEventListener('DOMContentLoaded', function () {
  const form          = document.getElementById('registerForm');
  const feedback      = document.getElementById('registerFeedback');
  const submitBtn     = document.getElementById('register-submit-btn');
  const btnContent    = document.getElementById('register-btn-content');
  const passwordInput = document.getElementById('registerPassword');
  const strengthBar   = document.getElementById('password-strength-bar');
  const strengthLabel = document.getElementById('password-strength-label');
  const toggleBtn     = document.getElementById('toggleRegisterPassword');
  const toggleIcon    = document.getElementById('toggleRegisterPasswordIcon');

  if (!form) return;

  if (toggleBtn && passwordInput) {
    toggleBtn.addEventListener('click', function () {
      const isHidden = passwordInput.type === 'password';
      passwordInput.type = isHidden ? 'text' : 'password';
      toggleIcon.className = isHidden ? 'bi bi-eye-slash' : 'bi bi-eye';
    });
  }

  if (passwordInput) {
    passwordInput.addEventListener('input', function () {
      const val = this.value;
      atualizarRequisitos(val);
      const level = getPasswordStrength(val);

      strengthBar.className = 'password-strength-bar';
      strengthLabel.className = 'password-strength-label';

      if (!val) {
        strengthBar.style.width = '0';
        strengthLabel.textContent = '';
        return;
      }

      if (level === 'weak') {
        strengthBar.classList.add('weak');
        strengthLabel.classList.add('weak');
        strengthLabel.textContent = 'Senha fraca';
      } else if (level === 'medium') {
        strengthBar.classList.add('medium');
        strengthLabel.classList.add('medium');
        strengthLabel.textContent = 'Senha média';
      } else {
        strengthBar.classList.add('strong');
        strengthLabel.classList.add('strong');
        strengthLabel.textContent = 'Senha forte';
      }
    });
  }

  // ── Submit ──
  form.addEventListener('submit', async function (e) {
    e.preventDefault();

    const nome     = document.getElementById('registerNome').value.trim();
    const email    = document.getElementById('registerEmail').value.trim();
    const password = passwordInput.value;

    if (!nome) {
      showFeedback('Informe seu nome completo.', 'danger');
      document.getElementById('registerNome').focus();
      return;
    }

    if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      showFeedback('Informe um e-mail válido.', 'danger');
      document.getElementById('registerEmail').focus();
      return;
    }

    const pwError = validarSenha(password);
    if (pwError) {
      showFeedback(pwError, 'danger');
      passwordInput.focus();
      return;
    }

    setLoading(true);
    showFeedback('Cadastrando...', 'info');

    try {
      await AgroApp.fetchJson('/api/usuario', {
        method: 'POST',
        body: { nome, email, password },
      });

      showFeedback('Cadastro realizado com sucesso! Redirecionando...', 'success');
      form.reset();
      resetStrengthBar();
      resetRequisitos();
      setTimeout(() => { window.location.href = '/'; }, 1400);
    } catch (err) {
      const msg = (err.message && !err.message.match(/^\d{3}/))
        ? err.message
        : 'Não foi possível concluir o cadastro. Tente novamente.';
      showFeedback(msg, 'danger');
      setLoading(false);
    }
  });

  // ── Helpers ──

  function validarSenha(val) {
    if (!val || val.length < 6)    return 'A senha deve ter pelo menos 6 caracteres.';
    if (!/[A-Z]/.test(val))        return 'A senha deve conter pelo menos uma letra maiúscula.';
    if (!/[a-z]/.test(val))        return 'A senha deve conter pelo menos uma letra minúscula.';
    if (!/[0-9]/.test(val))        return 'A senha deve conter pelo menos um número.';
    if (!/[^A-Za-z0-9]/.test(val)) return 'A senha deve conter pelo menos um símbolo (!@#$%...).';
    return null;
  }

  function getPasswordStrength(val) {
    if (validarSenha(val) !== null) return 'weak';
    if (val.length >= 8) return 'strong';
    return 'medium';
  }

  function atualizarRequisitos(val) {
    setReq('req-length', val.length >= 6);
    setReq('req-upper',  /[A-Z]/.test(val));
    setReq('req-lower',  /[a-z]/.test(val));
    setReq('req-number', /[0-9]/.test(val));
    setReq('req-symbol', /[^A-Za-z0-9]/.test(val));
  }

  function setReq(id, ok) {
    const el = document.getElementById(id);
    if (!el) return;
    const icon = el.querySelector('i');
    el.classList.toggle('ok', ok);
    if (icon) icon.className = ok ? 'bi bi-check-circle-fill' : 'bi bi-circle';
  }

  function resetRequisitos() {
    ['req-length','req-upper','req-lower','req-number','req-symbol'].forEach(id => setReq(id, false));
  }

  function resetStrengthBar() {
    if (strengthBar)  { strengthBar.className = 'password-strength-bar'; strengthBar.style.width = '0'; }
    if (strengthLabel){ strengthLabel.className = 'password-strength-label'; strengthLabel.textContent = ''; }
  }

  function showFeedback(msg, type) {
    feedback.className = 'auth-feedback';
    if (type) feedback.classList.add(`is-${type}`);
    feedback.textContent = msg;
  }

  function setLoading(loading) {
    submitBtn.disabled = loading;
    btnContent.innerHTML = loading
      ? '<span class="auth-spinner"></span> Cadastrando...'
      : '<i class="bi bi-person-plus me-1"></i> Criar conta';
  }
});