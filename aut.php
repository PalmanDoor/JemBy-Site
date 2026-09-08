<?php

// Путь к файлу с заблокированными IP
$blocked_ips_file = __DIR__ . '/blocked_ips.txt';

// Получение IP-адреса пользователя
$user_ip = $_SERVER['REMOTE_ADDR'];

// Проверка, заблокирован ли IP
$blocked_ips = file_exists($blocked_ips_file) ? file($blocked_ips_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
if (in_array($user_ip, $blocked_ips)) {
    http_response_code(403);
    echo json_encode(['status' => 'blocked', 'message' => 'Your IP address has been blocked due to multiple incorrect password attempts.']);
    exit;
}

// Установка пароля
$site_password = 'AVADSER9';

// Инициализация счётчика попыток
if (!isset($_SESSION['password_attempts'])) {
    $_SESSION['password_attempts'] = 0;
}

// Обработка AJAX-запроса
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $password = $input['password'] ?? '';

    if ($password === $site_password) {
        $_SESSION['site_access'] = true;
        $_SESSION['password_attempts'] = 0;
        echo json_encode(['status' => 'success']);
    } else {
        $_SESSION['password_attempts']++;
        if ($_SESSION['password_attempts'] >= 3) {
            file_put_contents($blocked_ips_file, $user_ip . PHP_EOL, FILE_APPEND | LOCK_EX);
            http_response_code(403);
            echo json_encode(['status' => 'blocked', 'message' => 'Your IP address has been blocked due to multiple incorrect password attempts.']);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'incorrect password!',
                'attempts_remaining' => 3 - $_SESSION['password_attempts']
            ]);
        }
    }
} else {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}
?>