document.addEventListener('DOMContentLoaded', function () {
  renderPendingFlashToasts();
  autoCloseFlashAlerts();
});

function renderPendingFlashToasts() {
  const flashes = window.AgroApp?.takePendingFlashes?.() ?? [];

  if (flashes.length === 0) {
    return;
  }

  flashes.forEach(flash => {
    window.AgroApp?.toast?.(flash.message ?? '', normalizeToastType(flash.type));
  });
}

function autoCloseFlashAlerts() {
  const alerts = document.querySelectorAll('.flash-container .alert');

  alerts.forEach(alert => {
    setTimeout(() => {
      const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
      bsAlert.close();
    }, 5000);
  });
}

function normalizeToastType(type) {
  if (type === 'error') {
    return 'error';
  }

  return type || 'info';
}
