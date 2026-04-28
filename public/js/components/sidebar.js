// sidebar.js — Controle responsivo da sidebar

document.addEventListener('DOMContentLoaded', function () {
  const sidebar    = document.getElementById('sidebar');
  const overlay    = document.getElementById('sidebar-overlay');
  const toggleBtn  = document.getElementById('mobileToggleBtn');
  const closeBtn   = document.getElementById('sidebar-close-btn');
  const navLinks   = sidebar ? sidebar.querySelectorAll('.nav-link') : [];

  if (!sidebar) return;

  // ── Abrir / Fechar ──────────────────────────────────────
  function openSidebar() {
    sidebar.classList.add('open');
    if (overlay) overlay.classList.add('active');
    document.body.style.overflow = 'hidden'; // previne scroll do fundo
    if (toggleBtn) toggleBtn.setAttribute('aria-expanded', 'true');
  }

  function closeSidebar() {
    sidebar.classList.remove('open');
    if (overlay) overlay.classList.remove('active');
    document.body.style.overflow = '';
    if (toggleBtn) toggleBtn.setAttribute('aria-expanded', 'false');
  }

  function isMobile() {
    return window.innerWidth <= 768;
  }

  // ── Botão hamburger (topbar) ─────────────────────────────
  if (toggleBtn) {
    toggleBtn.setAttribute('aria-expanded', 'false');
    toggleBtn.setAttribute('aria-controls', 'sidebar');
    toggleBtn.addEventListener('click', function () {
      if (sidebar.classList.contains('open')) {
        closeSidebar();
      } else {
        openSidebar();
      }
    });
  }

  // ── Botão fechar dentro da sidebar ──────────────────────
  if (closeBtn) {
    closeBtn.addEventListener('click', closeSidebar);
  }

  // ── Fechar ao clicar no overlay ──────────────────────────
  if (overlay) {
    overlay.addEventListener('click', closeSidebar);
  }

  // ── Fechar ao pressionar Escape ──────────────────────────
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && sidebar.classList.contains('open')) {
      closeSidebar();
    }
  });

  // ── Fechar ao clicar em link nav no mobile ───────────────
  navLinks.forEach(function (link) {
    link.addEventListener('click', function () {
      if (isMobile() && sidebar.classList.contains('open')) {
        // Pequeno delay para a navegação acontecer visivelmente
        setTimeout(closeSidebar, 120);
      }
    });
  });

  // ── Garantir scroll do body ao redimensionar ────────────
  window.addEventListener('resize', function () {
    if (!isMobile() && sidebar.classList.contains('open')) {
      closeSidebar();
    }
  });
});