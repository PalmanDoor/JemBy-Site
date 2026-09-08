<?php
session_start();

// Подключение к базе данных minecraft-website
$host = '92.38.222.153';
$db = 'minecraft-website';
$user = 'root';
$pass = '1php-8hyT23WE5tMy';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    throw new PDOException($e->getMessage(), (int)$e->getCode());
}

// Подключение к базе данных authy
$host_authy = '92.38.222.153';
$db_authy = 'authy';
$user_authy = 'root';
$pass_authy = '1php-8hyT23WE5tMy';

$dsn_authy = "mysql:host=$host_authy;dbname=$db_authy;charset=utf8";
$options_authy = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo_authy = new PDO($dsn_authy, $user_authy, $pass_authy, $options_authy);
} catch (PDOException $e) {
    die('Подключение к базе данных authy не удалось: ' . $e->getMessage());
}

if (!function_exists('user_logout')) {
    function user_logout() {
        session_destroy();
        echo "<p>Вы вышли из системы.</p>";
    }
}

if (!function_exists('user_login')) {
    function user_login($username, $password, $pin) {
        global $pdo_authy;

        // Предотвращение SQL-инъекций
        $username = htmlspecialchars($username);
        $password = htmlspecialchars($password);
        $pin = htmlspecialchars($pin);

        // Проверка существования пользователя в таблице players
        $stmt_authy = $pdo_authy->prepare("SELECT * FROM players WHERE username = :username");
        $stmt_authy->execute(['username' => $username]);
        $user = $stmt_authy->fetch();

        if ($user) {
            // Проверка пароля и ПИН-кода
            $password_hash = hash('sha256', $password);
            if ($password_hash === $user['password'] && ($pin === '' || $pin === $user['pin'])) {
                // Вход пользователя
                $_SESSION["username"] = $user['username'];
                $_SESSION["email"] = $user['email'];

                // Обновление времени последнего входа в таблице players
                $stmt_authy = $pdo_authy->prepare("UPDATE players SET lastlogin = NOW(), timesloggedin = timesloggedin + 1 WHERE username = :username");
                $stmt_authy->execute(['username' => $username]);

                return true;
            } else {
                $_SESSION["message"] = "Неверный пароль или ПИН-код.";
            }
        } else {
            $_SESSION["message"] = "Пользователь не найден.";
        }
        return false;
    }
}

if (!function_exists('check_username')) {
    function check_username($username) {
        global $pdo_authy;

        $stmt = $pdo_authy->prepare("SELECT username FROM players WHERE username = :username");
        $stmt->execute(['username' => $username]);

        return $stmt->rowCount() > 0;
    }
}
?>
