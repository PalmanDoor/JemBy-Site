<?php
session_start();
$_SESSION["prevpage"] = "editing.php";
include "db.php";

if (isset($_SESSION["username"])) {
    $username = $_SESSION["username"];

    // Запрос к базе данных для получения данных о пользователе
    $query = "SELECT level, editing FROM authy.players WHERE username = ?";
    $stmt = $pdo_authy->prepare($query);
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $userLevel = $user['level'];
        $userEditing = $user['editing'];

        if ($userLevel >= 5 || ($userLevel == 1 && $userEditing == 1)) {
            $_SESSION["level"] = $userLevel;
            $_SESSION["editing"] = $userEditing;

            echo "<div class='edit-footer'>";
            echo "<p class='edit-info'>Вы находитесь в режиме редактирования сайта. <a class='link-edit' href='exitediting.php'>Выйти?</a></p>";
            echo "<p class='edit-user'>Привет, " . htmlspecialchars($username, ENT_QUOTES, 'UTF-8') . "!</p>";
            echo "<p class='edit-version'>Версия 1.0</p>";
            echo "</form>";
            echo "</div>";
            echo "<script type='text/javascript' src='/javascript/ckeditor/ckeditor.js'></script>";
        } else {
            echo "<p>У вас недостаточно прав для доступа к редактированию сайта.</p>";
        }
    } else {
        echo "<p>Ошибка: Пользователь не найден.</p>";
    }
} else {
    echo "<p>Вы не авторизованы.</p>";
}
?>