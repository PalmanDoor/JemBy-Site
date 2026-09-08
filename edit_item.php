<?php
include "db.php";

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Необходимо авторизоваться']);
    exit();
}

$itemId = $_POST['editItemId'];
$itemName = $_POST['editItemName'];
$itemDescription = $_POST['editItemDescription'];
$itemPrice = $_POST['editItemPrice'];
$itemImage = $_POST['editItemImage'];
$itemCategory = $_POST['editItemCategory'];
$newCategory = $_POST['editNewCategory'];
$itemCommand = $_POST['editItemCommand'];

$category = $newCategory ?: $itemCategory;

$stmt = $pdo->prepare("UPDATE shop SET title = ?, description = ?, price = ?, image = ?, category = ?, command = ? WHERE id = ?");
$result = $stmt->execute([$itemName, $itemDescription, $itemPrice, $itemImage, $category, $itemCommand, $itemId]);

if ($result) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Ошибка при обновлении товара']);
}
?>
