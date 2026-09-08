<?php
// Подключаем инициализацию (сессии, БД, функции)
require_once 'init.php';

if (!defined('HEADER_INCLUDED')) {
    define('HEADER_INCLUDED', true);

    // Управление заголовком страницы
    $pageTitle = isset($title) ? htmlspecialchars($title) . " - Project Echo" : "Project Echo";

    // Проверяем, является ли запрос AJAX
    if (isset($_POST['ajax']) && ($_POST['ajax'] === 'login' || $_POST['ajax'] === 'register')) {
        // Для AJAX-запросов не выводим HTML
        return; // Завершаем выполнение, чтобы не выводить HTML
    }
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <title><?php echo $pageTitle; ?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="msapplication-TileImage" content="/images/icosite/ms-icon-144x144.png">
    <meta name="theme-color" content="#ffffff">

    <!-- Иконки -->
    <link rel="apple-touch-icon" sizes="57x57" href="/images/icosite/apple-icon-57x57.png">
    <link rel="apple-touch-icon" sizes="60x60" href="/images/icosite/apple-icon-60x60.png">
    <link rel="apple-touch-icon" sizes="72x72" href="/images/icosite/apple-icon-72x72.png">
    <link rel="apple-touch-icon" sizes="76x76" href="/images/icosite/apple-icon-76x76.png">
    <link rel="apple-touch-icon" sizes="114x114" href="/images/icosite/apple-icon-114x114.png">
    <link rel="apple-touch-icon" sizes="120x120" href="/images/icosite/apple-icon-120x120.png">
    <link rel="apple-touch-icon" sizes="144x144" href="/images/icosite/apple-icon-144x144.png">
    <link rel="apple-touch-icon" sizes="152x152" href="/images/icosite/apple-icon-152x152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/images/icosite/apple-icon-180x180.png">
    <link rel="icon" type="image/png" sizes="192x192" href="/images/icosite/android-icon-192x192.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/images/icosite/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="96x96" href="/images/icosite/favicon-96x96.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/images/icosite/favicon-16x16.png">
    <link rel="manifest" href="/images/icosite/manifest.json">

    <!-- Стили -->
    <link href="/main.css" rel="stylesheet" type="text/css" />
    <link href="/editing.css" rel="stylesheet" type="text/css" />
    <link href="/screenshots.css" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="/css/lightbox.min.css">
    <link rel="stylesheet" href="/css/animate.min.css">
    <link rel="stylesheet" href="/css/firefly.css">
    <link rel="stylesheet" href="/css/add-ee.css">
    <link rel="stylesheet" href="/css/user_index.css">
    <link rel="stylesheet" href="/css/toastr.min.css">
    <link rel="stylesheet" href="/css/plyr.css">

    <!-- Скрипты -->
    <script type="text/javascript" src="/javascript/jquery-3.6.0.min.js"></script>
    <script type="text/javascript" src="/javascript/sweetalert2.all.min.js"></script>
    <script type="text/javascript" src="/javascript/toastr.min.js"></script>
    <script type="text/javascript" src="/javascript/lightbox.min.js"></script>
    <script type="text/javascript" src="/javascript/plyr.js"></script>
    <script type="text/javascript" src="/javascript/axios.min.js"></script>
    <script type="text/javascript" src="/javascript/socket.io.min.js"></script>
    <script type="text/javascript" src="/javascript/vue.min.js"></script>
    <script type="text/javascript" src="/javascript/react.production.min.js"></script>
    <script type="text/javascript" src="/javascript/react-dom.production.min.js"></script>
    <script type="text/javascript" src="/javascript/alpine.min.js" defer></script>
    <script type="text/javascript" src="/javascript/pusher.min.js"></script>
    <script type="text/javascript" src="/javascript/main.js"></script>
    <script type="text/javascript" src="/javascript/lowerheader.js"></script>
    <script type="text/javascript" src="/javascript/footer.js"></script>
    <script type="text/javascript" src="/javascript/login.js"></script>

    <!-- Стили для лоадера -->
    <style>
        .loader-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 9999;
            display: flex;
            justify-content: center;
            align-items: center;
            transition: opacity 0.5s ease;
        }
        .loader-hidden {
            opacity: 0;
            pointer-events: none;
        }
        .loader {
            width: 50px;
            height: 50px;
            border: 5px solid #800080;
            border-top: 5px solid transparent;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="fireflies-container">
        <?php for ($i = 1; $i <= 30; $i++): ?>
            <div class="firefly"></div>
        <?php endfor; ?>
    </div>
    <div class="loader-overlay" id="loader">
        <div class="loader"></div>
    </div>
<?php
}
?>