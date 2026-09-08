document.addEventListener('DOMContentLoaded', () => {
    const menuToggle = document.querySelector('.menu-toggle');
    const navContainer = document.querySelector('.nav-container');
    const otherToggle = document.querySelector('.other-toggle');
    const otherMenu = document.querySelector('.other-menu');

    // Управление основным меню
    menuToggle.addEventListener('click', () => {
        navContainer.classList.toggle('active');
        menuToggle.classList.toggle('open');
        if (otherMenu) {
            otherMenu.classList.remove('open');
            otherToggle.classList.remove('open');
        }
    });

    // Закрытие основного меню при клике вне
    document.addEventListener('click', (e) => {
        if (!navContainer.contains(e.target) && !menuToggle.contains(e.target)) {
            navContainer.classList.remove('active');
            menuToggle.classList.remove('open');
            if (otherMenu) {
                otherMenu.classList.remove('open');
                otherToggle.classList.remove('open');
            }
        }
    });

    // Управление меню "Другое"
    if (otherToggle && otherMenu) {
        otherToggle.addEventListener('click', (e) => {
            e.preventDefault();
            otherMenu.classList.toggle('open');
            otherToggle.classList.toggle('open');
        });

        // Десктоп: Закрытие при клике вне меню
        document.addEventListener('click', (e) => {
            if (window.innerWidth > 1400 && 
                !otherToggle.contains(e.target) && 
                !otherMenu.contains(e.target)) {
                otherMenu.classList.remove('open');
                otherToggle.classList.remove('open');
            }
        });

        // Десктоп: Показ меню при наведении
        if (window.innerWidth > 1400) {
            otherToggle.parentElement.addEventListener('mouseenter', () => {
                otherMenu.classList.add('open');
                otherToggle.classList.add('open');
            });
            otherToggle.parentElement.addEventListener('mouseleave', () => {
                otherMenu.classList.remove('open');
                otherToggle.classList.remove('open');
            });
        }
    }

    // Обновление поведения при изменении размера окна
    window.addEventListener('resize', () => {
        if (window.innerWidth > 1400 && otherMenu && otherMenu.classList.contains('open')) {
            otherMenu.classList.remove('open');
            otherToggle.classList.remove('open');
        }
    });
});