<?php
include "db.php";
$_SESSION["prevpage"] = "changepass.php";

if (!isset($_SESSION["username"])) {
    header("location:login.php");
    exit();
}

$username = $_SESSION["username"];

if (isset($_POST['currentpass']) && isset($_POST['newpass1']) && isset($_POST['newpass2'])) {
    $currentpass = $_POST['currentpass'];
    $newpass1 = $_POST['newpass1'];
    $newpass2 = $_POST['newpass2'];

    $pdo = $GLOBALS['pdo'];
    
    // Получаем текущий пароль пользователя из базы данных
    $query = "SELECT password FROM webusers WHERE login = :username";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['username' => $username]);
    $queryresult = $stmt->fetch(PDO::FETCH_ASSOC);
    $password = $queryresult['password'];

    if ($password == $currentpass && $newpass1 == $newpass2) {
        // Обновляем пароль
        $updateQuery = "UPDATE webusers SET password = :newpass WHERE login = :username";
        $updateStmt = $pdo->prepare($updateQuery);
        $updateStmt->execute(['newpass' => $newpass1, 'username' => $username]);
        $_SESSION["message"] = "Пароль был изменен";
        header("location:usercp.php");
        exit();
    } else {
        $_SESSION["message"] = "Текущий пароль неверен или новые пароли не совпадают";
    }
}
?>