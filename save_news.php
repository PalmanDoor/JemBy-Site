<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    include 'db.php'; // Подключение к базе данных

    if ($dbc) {
        $id = intval($data['id']);
        $changes = $data['changes'];
        
        $fields = array_keys($changes);
        $placeholders = implode(', ', array_map(function($field) { return "$field = ?"; }, $fields));
        $values = array_values($changes);

        $stmt = mysqli_prepare($dbc, "UPDATE vijesti SET $placeholders WHERE id = ?");
        array_push($values, $id);
        mysqli_stmt_bind_param($stmt, str_repeat('s', count($values)), ...$values);
        $success = mysqli_stmt_execute($stmt);

        if ($success) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => mysqli_error($dbc)]);
        }
        mysqli_stmt_close($stmt);
    } else {
        echo json_encode(['success' => false, 'error' => 'Database connection failed.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
}
?>