<?php
require_once __DIR__ . '/db.php';

// 1. Запускаем сессию
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Очищаем массив сессии
$_SESSION = [];

// 3. Удаляем cookie сессии у клиента
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. Уничтожаем сессию на сервере
session_destroy();

// 5. Редирект на главную
header("Location: index.php");
exit();
