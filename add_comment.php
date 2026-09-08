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
    $comment_text = trim($_POST['comment_text'] ?? '');
    $profile_username = $_POST['profile_username'] ?? '';

    if (empty($comment_text)) {
        $response['error'] = 'Комментарий не может быть пустым';
        echo json_encode($response);
        exit();
    }

    if (empty($profile_username)) {
        $response['error'] = 'Неверное имя пользователя профиля';
        echo json_encode($response);
        exit();
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO comments (profile_username, commenter_username, comment_text, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$profile_username, $_SESSION["username"], $comment_text]);

        $comment_id = $pdo->lastInsertId();
        $stmt = $pdo->prepare("SELECT commenter_username, comment_text, created_at, id FROM comments WHERE id = ?");
        $stmt->execute([$comment_id]);
        $new_comment = $stmt->fetch(PDO::FETCH_ASSOC);

        $response = [
            'success' => true,
            'comment' => [
                'id' => $new_comment['id'],
                'commenter_username' => $new_comment['commenter_username'],
                'comment_text' => htmlspecialchars($new_comment['comment_text']),
                'created_at' => formatDate($new_comment['created_at'])
            ]
        ];
    } catch (PDOException $e) {
        $response['error'] = 'Ошибка базы данных: ' . $e->getMessage();
    }
} else {
    $response['error'] = 'Неверный метод запроса';
}

echo json_encode($response);

function formatDate($dateString) {
    $months = ['янв.', 'фев.', 'мар.', 'апр.', 'мая', 'июн.', 'июл.', 'авг.', 'сен.', 'окт.', 'ноя.', 'дек.'];
    $date = new DateTime($dateString);
    return $date->format('j ') . $months[$date->format('n') - 1] . ' ' . $date->format('Y г. в G:i');
}
?>