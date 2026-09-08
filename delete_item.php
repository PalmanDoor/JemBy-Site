<?php
include "db.php";

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Необходимо авторизоваться']);
    exit();
}

if (!isset($_POST['itemId'])) {
    echo json_encode(['success' => false, 'message' => 'Неверный запрос']);
    exit();
}

$itemId = $_POST['itemId'];

$stmt = $pdo->prepare("DELETE FROM shop WHERE id = ?");
$result = $stmt->execute([$itemId]);

if ($result) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Ошибка при удалении товара']);
}
?>
