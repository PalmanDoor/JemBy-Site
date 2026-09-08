<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['image'])) {
    include 'db.php'; // Подключение к базе данных

    if (!$dbc) {
        echo json_encode(['success' => false, 'error' => 'Ошибка подключения к базе данных.']);
        exit;
    }

    $news_id = intval($_POST['news_id']);
    $uploadDir = 'img/';
    $fileName = basename($_FILES['image']['name']);
    $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);
    $baseName = pathinfo($fileName, PATHINFO_FILENAME);

    // Проверка на уникальность имени файла
    $i = 1;
    $newFileName = $baseName . '.' . $fileExt;
    while (file_exists($uploadDir . $newFileName)) {
        $newFileName = $baseName . '_' . $i++ . '.' . $fileExt;
    }

    $targetPath = $uploadDir . $newFileName;

    if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
        // Обновление пути к изображению в базе данных
        $stmt = mysqli_prepare($dbc, "UPDATE vijesti SET slika = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "si", $newFileName, $news_id);
        $success = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        if ($success) {
            echo json_encode(['success' => true, 'filename' => $newFileName]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Ошибка при обновлении базы данных: ' . mysqli_error($dbc)]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Ошибка при загрузке файла.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Неверный запрос или отсутствует файл.']);
}
?>