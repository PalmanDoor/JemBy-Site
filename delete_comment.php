<?php
include_once "db.php";

header('Content-Type: application/json');

$response = ['success' => false];

if (!isset($_SESSION["username"])) {
    $response['error'] = 'Необходимо авторизоваться';
    echo json_encode($response);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $comment_id = $_POST['comment_id'] ?? 0;

    if ($comment_id <= 0) {
        $response['error'] = 'Неверный ID комментария';
        echo json_encode($response);
        exit();
    }

    try {
        // Проверяем, принадлежит ли комментарий текущему пользователю
        $stmt = $pdo->prepare("SELECT commenter_username FROM comments WHERE id = ?");
        $stmt->execute([$comment_id]);
        $comment = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($comment && $comment['commenter_username'] === $_SESSION["username"]) {
            $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
            $stmt->execute([$comment_id]);
            $response['success'] = true;
        } else {
            $response['error'] = 'Вы не можете удалить этот комментарий';
        }
    } catch (PDOException $e) {
        $response['error'] = 'Ошибка базы данных: ' . $e->getMessage();
    }
} else {
    $response['error'] = 'Неверный метод запроса';
}

echo json_encode($response);
?>