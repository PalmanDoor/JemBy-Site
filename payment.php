<?php
include("db.php");

if (!isset($_SESSION["username"])) {
    header("Location: auth");
    exit();
}

$username = $_SESSION["username"];
$amount = $_POST['amount']; // Количество монет, которые хочет купить пользователь
$payment_method = $_POST['payment_method']; // Метод оплаты (Enot.io)

if ($payment_method == 'enot') {
    $merchant_id = "YOUR_MERCHANT_ID"; // Ваш merchant_id в Enot.io
    $secret_key = "YOUR_SECRET_KEY"; // Ваш secret_key в Enot.io
    $order_id = uniqid(); // Уникальный идентификатор заказа
    $sign = md5("$merchant_id:$amount:$secret_key:$order_id");

    $url = "https://enot.io/pay.php?m=$merchant_id&oa=$amount&o=$order_id&s=$sign&cf=$username&c=RUB";

    header("Location: $url");
    exit();
}
?>
