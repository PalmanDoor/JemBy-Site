<?php
include "db.php";
session_start();

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Необходимо авторизоваться']);
    exit();
}

$itemName = $_POST['itemName'];
$itemDescription = $_POST['itemDescription'];
$itemPrice = $_POST['itemPrice'];
$itemImage = $_POST['itemImage'];
$itemCategory = $_POST['itemCategory'];
$newCategory = $_POST['newCategory'];
$itemCommand = $_POST['itemCommand'];

$category = $newCategory ?: $itemCategory;

$stmt = $pdo->prepare("INSERT INTO shop (title, description, price, image, category, command) VALUES (?, ?, ?, ?, ?, ?)");
$result = $stmt->execute([$itemName, $itemDescription, $itemPrice, $itemImage, $category, $itemCommand]);

if ($result) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Ошибка при добавлении товара']);
}
?>
