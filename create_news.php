<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    include 'db.php'; // Подключение к базе данных

    if ($dbc) {
        // Получаем текущую дату и время
        $currentDateTime = new DateTime('now', new DateTimeZone('UTC')); // Используем UTC или ваш часовой пояс
        $datum = $currentDateTime->format('Y-m-d');
        $vrijeme = $currentDateTime->format('H:i:s');

        $query = "INSERT INTO vijesti (slika, naslov, sazetak, datum, vrijeme) VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($dbc, $query);
        mysqli_stmt_bind_param($stmt, "sssss", $data['slika'], $data['naslov'], $data['sazetak'], $datum, $vrijeme);
        
        if (mysqli_stmt_execute($stmt)) {
            $newId = mysqli_insert_id($dbc);
            echo json_encode(['success' => true, 'id' => $newId]);
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