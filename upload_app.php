<?php
include "db.php";

if (!isset($_SESSION['editing']) || !$_SESSION['editing']) {
    http_response_code(403);
    exit("Доступ запрещен.");
}

$folder = $_POST['folder'];
$apps_dir = "my_app";
$upload_dir = "$apps_dir/$folder";
$target_file = "$upload_dir/app.zip";
$maxFileSize = 100 * 1024 * 1024; // 100 MB

if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Проверяем, был ли загружен файл
if (!isset($_FILES["zipfile"])) {
    http_response_code(400);
    exit("Файл не был загружен.");
}

// Проверяем размер файла
if ($_FILES["zipfile"]["size"] > $maxFileSize) {
    http_response_code(413);
    exit("Файл превышает допустимый размер 100 МБ. Загрузите вручную или обратитесь к администратору.");
}

// Проверяем ошибки загрузки
if ($_FILES["zipfile"]["error"] !== UPLOAD_ERR_OK) {
    http_response_code(500);
    exit("Ошибка загрузки: " . $_FILES["zipfile"]["error"]);
}

// Перемещаем файл
if (move_uploaded_file($_FILES["zipfile"]["tmp_name"], $target_file)) {
    echo "Файл загружен.";
} else {
    http_response_code(500);
    exit("Ошибка при сохранении файла.");
}
?>