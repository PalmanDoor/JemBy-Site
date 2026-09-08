(function () {
    const footer = document.querySelector('.footer');
    const content = document.querySelector('.content') || document.createElement('div');

    // Добавляем классы вместо инлайновых стилей
    content.classList.add('footer-padding');

    const playerImage = document.getElementById('footerPlayer');
    const player2Image = document.getElementById('footerPlayer2');

    const updateImageVisibility = () => {
        const { innerWidth: width, innerHeight: height } = window;
        const isHD = width <= 1400 && height <= 800;
    };

    window.addEventListener('resize', updateImageVisibility);
})();