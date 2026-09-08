<?php
// Подключаем db.php для доступа к базе данных и функциям
require_once 'db.php';

// Убедимся, что сессия запущена
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Функции для регистрации
function generateUUID($username) {
    $data = md5($username . uniqid('', true));
    return sprintf('%08s-%04s-%04s-%04s-%12s', substr($data, 0, 8), substr($data, 8, 4), substr($data, 12, 4), substr($data, 16, 4), substr($data, 20, 12));
}

function getUserIP() {
    return $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
}

function isValidUsername($username) {
    return preg_match('/^[a-zA-Z0-9_]{4,16}$/', $username);
}

// AJAX обработка входа
if (isset($_POST['ajax']) && $_POST['ajax'] === 'login') {
    header('Content-Type: application/json; charset=utf-8');
    
    $username = trim($_POST['login_username'] ?? '');
    $password = trim($_POST['login_password'] ?? '');
    
    if (empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Логин и пароль не могут быть пустыми.']);
        exit;
    }
    
    try {
        if (user_login($username, $password)) {
            echo json_encode(['success' => true, 'redirect' => 'home.php']);
        } else {
            echo json_encode(['success' => false, 'message' => $_SESSION["message"] ?? 'Ошибка входа']);
        }
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Произошла ошибка сервера. Попробуйте снова.']);
    }
    exit;
}

// AJAX обработка регистрации
if (isset($_POST['ajax']) && $_POST['ajax'] === 'register') {
    header('Content-Type: application/json; charset=utf-8');
    
    $username = trim($_POST['register_username'] ?? '');
    $password = trim($_POST['register_password'] ?? '');
    $email = trim($_POST['register_email'] ?? '');
    
    if (empty($username) || empty($password) || empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Все поля обязательны для заполнения.']);
        exit;
    }
    
    if (!isValidUsername($username)) {
        echo json_encode(['success' => false, 'message' => 'Логин должен содержать от 4 до 16 символов (буквы, цифры, _).']);
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Некорректный email адрес.']);
        exit;
    }
    
    try {
        global $pdo_authy;
        $stmt = $pdo_authy->prepare("SELECT * FROM players WHERE username = :username");
        $stmt->execute(['username' => $username]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Пользователь с таким именем уже существует.']);
            exit;
        }
        
        $stmt = $pdo_authy->prepare("SELECT * FROM players WHERE email = :email");
        $stmt->execute(['email' => $email]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Пользователь с таким email уже существует.']);
            exit;
        }
        
        $uuid = generateUUID($username);
        $ip = getUserIP();
        $password_hash = hash('sha256', $password);
        
        $stmt = $pdo_authy->prepare("INSERT INTO players (uuid, username, password, email, ip, demo, EE) VALUES (:uuid, :username, :password, :email, :ip, 0, 30)");
        $stmt->execute(['uuid' => $uuid, 'username' => $username, 'password' => $password_hash, 'email' => $email, 'ip' => $ip]);
        
        $_SESSION["username"] = $username;
        $_SESSION["email"] = $email;
        
        echo json_encode(['success' => true, 'redirect' => 'index.php?demo=true']);
    } catch (Exception $e) {
        error_log("Registration error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Произошла ошибка при создании аккаунта.']);
    }
    exit;
}

// Если это не AJAX-запрос, ничего не возвращаем
exit;
?>