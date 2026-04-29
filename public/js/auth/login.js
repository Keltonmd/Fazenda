// login.js — Lógica da página de login 

document.addEventListener('DOMContentLoaded', function () {
  const form       = document.getElementById('loginForm');
  const feedback   = document.getElementById('loginFeedback');
  const emailInput = document.getElementById('loginEmail');
  const pwInput    = document.getElementById('loginPassword');
  const submitBtn  = document.getElementById('login-submit-btn');
  const btnContent = document.getElementById('login-btn-content');
  const toggleBtn  = document.getElementById('toggleLoginPassword');
  const toggleIcon = document.getElementById('toggleLoginPasswordIcon');

  if (!form) return;

  if (toggleBtn && pwInput) {
    toggleBtn.addEventListener('click', function () {
      const isHidden = pwInput.type === 'password';
      pwInput.type = isHidden ? 'text' : 'password';
      toggleIcon.className = isHidden ? 'bi bi-eye-slash' : 'bi bi-eye';
    });
  }

  // ── Submit ──
  form.addEventListener('submit', async function (e) {
    e.preventDefault();

    const email    = emailInput.value.trim();
    const password = pwInput.value;

    if (!email || !password) {
      showFeedback('Preencha o e-mail e a senha.', 'danger');
      return;
    }

    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      showFeedback('Informe um e-mail válido.', 'danger');
      emailInput.focus();
      return;
    }

    setLoading(true);
    showFeedback('Autenticando...', 'info');

    try {
      const data = await AgroApp.fetchJson('/api/login_check', {
        method: 'POST',
        body: { email, password },
        skipAuthRedirect: true,
      });

      if (data && data.token) {
        localStorage.setItem('jwtToken', data.token);

        const payload = parseJwtPayload(data.token);
        const userName = payload?.username || payload?.email || email;
        localStorage.setItem('userName', userName);

        showFeedback('Login realizado! Redirecionando...', 'success');
        setTimeout(() => { window.location.href = '/dashboard'; }, 800);
      } else {
        showFeedback('E-mail ou senha incorretos. Tente novamente.', 'danger');
        setLoading(false);
      }
    } catch (err) {
      const msg = err.message && !err.message.match(/^\d{3}/)
        ? err.message
        : 'E-mail ou senha incorretos. Tente novamente.';
      showFeedback(msg, 'danger');
      setLoading(false);
    }
  });

  // ── Helpers ──

  function parseJwtPayload(token) {
    try {
      const base64 = token.split('.')[1].replace(/-/g, '+').replace(/_/g, '/');
      return JSON.parse(atob(base64));
    } catch {
      return null;
    }
  }

  function showFeedback(msg, type) {
    feedback.className = 'auth-feedback';
    if (type) feedback.classList.add(`is-${type}`);
    feedback.textContent = msg;
  }

  function setLoading(loading) {
    submitBtn.disabled = loading;
    btnContent.innerHTML = loading
      ? '<span class="auth-spinner"></span> Autenticando...'
      : '<i class="bi bi-box-arrow-in-right me-1"></i> Entrar no painel';
  }
});
