<?php
header("Access-Control-Allow-Origin: https://project-echo.ru");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, User-Agent");

session_start();
include_once "db.php";

// Проверка наличия сессии и пользователя
if (isset($_SESSION["username"])) {
    $username = $_SESSION["username"];
    $query = "SELECT editing FROM players WHERE username = ?";
    $stmt = $pdo_authy->prepare($query);
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && $user['editing'] == 1) {
        include_once "editing.php";
    }
}

// Функция GetServerStatus
if (!function_exists('GetServerStatus')) {
    function GetServerStatus($site, $port) {
        $status = ["OFFLINE", "ONLINE"];
        $fp = @fsockopen($site, $port, $errno, $errstr, 2);
        if (!$fp) {
            return $status[0];
        } else {
            fclose($fp);
            return $status[1];
        }
    }
}
?>