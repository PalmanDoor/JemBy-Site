<?php
$title = "Редактор изображений Photopea";
$_SESSION["prevpage"] = "photopea_editor";

include_once 'includes/header.php';
include_once 'includes/lowerheader.php';
?>

<div class="editor-wrapper">
    <iframe id="photopeaIframe" frameborder="0"></iframe>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const photopeaIframe = document.getElementById('photopeaIframe');

    // Устанавливаем высоту и позицию iframe динамически
    function adjustIframePosition() {
        const header = document.querySelector('.header');
        const headerHeight = header ? header.offsetHeight : 0;

        // Устанавливаем отступ сверху для wrapper (полная высота header)
        const editorWrapper = document.querySelector('.editor-wrapper');
        editorWrapper.style.top = `${headerHeight}px`;

        // Устанавливаем высоту iframe
        photopeaIframe.style.height = `${window.innerHeight - headerHeight}px`;
    }

    // Базовая конфигурация Photopea
    const config = {
        "server": {
            "version": 1,
            "url": "https://project-echo.ru/saveImage.php", // Замените на ваш URL
            "formats": ["png", "jpg:0.8"]
        }
    };

    // Устанавливаем начальную конфигурацию (пустой редактор)
    photopeaIframe.src = `https://www.photopea.com#${encodeURIComponent(JSON.stringify(config))}`;
    adjustIframePosition();

    // Обновляем позицию при изменении размера окна
    window.addEventListener('resize', adjustIframePosition);
});
</script>

<style>
/* Убираем стандартные отступы и задаем базовые стили */
body {
    margin: 0;
    padding: 0;
    overflow: hidden; /* Убираем прокрутку */
}

/* Обертка редактора */
.editor-wrapper {
    width: 100%;
    max-width: 2560px; /* Ограничиваем ширину до QHD (2560px) */
    margin: 0 auto; /* Центрируем контейнер */
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
    z-index: 1; /* Устанавливаем ниже header */
}

/* Стили для iframe */
#photopeaIframe {
    width: 100%;
    border: none;
    background-color: #1e1e1e;
    position: relative;
    z-index: 1; /* Устанавливаем ниже header */
}

/* Убеждаемся, что header остается поверх */
.header {
    position: fixed; /* Уже есть в main.css */
    z-index: 3; /* Выше всего остального */
}

/* Медиа-запросы для адаптации к меньшим экранам */
@media screen and (max-width: 2560px) {
    .editor-wrapper {
        max-width: 100%; /* На экранах меньше QHD ширина становится гибкой */
    }
}

@media screen and (max-width: 1920px) {
    .editor-wrapper {
        max-width: 100%; /* Адаптация для Full HD и ниже */
    }
    #photopeaIframe {
        width: 100%;
    }
}

@media screen and (max-width: 1366px) {
    .editor-wrapper {
        max-width: 100%; /* Адаптация для ноутбуков */
    }
}

@media screen and (max-width: 768px) {
    .editor-wrapper {
        max-width: 100%; /* Адаптация для планшетов и мобильных устройств */
    }
}
</style>