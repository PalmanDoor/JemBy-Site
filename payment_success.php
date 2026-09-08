<?php
include("db.php");

if (!isset($_SESSION["username"])) {
    header("Location: auth");
    exit();
}

$merchant_id = "YOUR_MERCHANT_ID";
$secret_key = "YOUR_SECRET_KEY";

$amount = $_GET['amount'];
$order_id = $_GET['merchant_order_id'];
$sign = $_GET['sign'];
$username = $_GET['cf']; // Получаем имя пользователя из пользовательского поля

$valid_sign = md5("$merchant_id:$amount:$secret_key:$order_id");

if ($sign === $valid_sign) {
    // Платеж выполнен успешно, добавляем монеты пользователю
    $stmt = $pdo->prepare("UPDATE players SET EE = EE + :amount WHERE username = :username");
    $stmt->execute(['amount' => $amount, 'username' => $username]);

    echo "Пополнение успешно. Монеты зачислены на ваш счет.";
} else {
    echo "Ошибка выполнения платежа.";
}
?>
