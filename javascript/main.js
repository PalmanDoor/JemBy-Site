// === main.js (Очищенная версия, без Google Analytics) ===

// Управление лоадером
window.addEventListener('load', function () {
    const loader = document.getElementById('loader');
    if (loader) {
        loader.classList.add('loader-hidden');
        setTimeout(() => {
            loader.style.display = 'none';
        }, 200);
    }

    const overlays = document.querySelectorAll('[style*="position: fixed"][style*="z-index"]');
    overlays.forEach(overlay => {
        if (!overlay.classList.contains('edit-banner') &&
            !overlay.classList.contains('edit-footer') &&
            !overlay.classList.contains('header')) {
            overlay.style.display = 'none';
        }
    });
});

// Принудительное скрытие через 5 секунд
setTimeout(() => {
    const loader = document.getElementById('loader');
    if (loader) {
        loader.classList.add('loader-hidden');
        loader.style.display = 'none';
    }
    const overlays = document.querySelectorAll('[style*="position: fixed"][style*="z-index"]');
    overlays.forEach(overlay => {
        if (!overlay.classList.contains('edit-banner') &&
            !overlay.classList.contains('edit-footer') &&
            !overlay.classList.contains('header')) {
            overlay.style.display = 'none';
        }
    });
}, 5000);

// Приветик