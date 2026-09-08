<?php
include("db.php"); // Подключаем базу данных

// Проверяем, залогинен ли пользователь
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

$username = $_SESSION['username'];

// Обновляем статус редактирования пользователя на 0
$stmt = $pdo->prepare("UPDATE authy.players SET editing = 0 WHERE username = :username");

try {
    $stmt->execute(['username' => $username]);
    $_SESSION["editing"] = 0; // Устанавливаем редактирование в 0 для текущей сессии
    $redirectUrl = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php'; // Получаем URL страницы, с которой пришли, или перенаправляем на главную страницу
    header("Location: $redirectUrl"); // Перенаправляем обратно на ту же страницу
    exit();
} catch (PDOException $e) {
    die("Ошибка при выполнении запроса: " . $e->getMessage());
}
?>
