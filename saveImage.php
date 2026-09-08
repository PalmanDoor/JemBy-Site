<?php
header('Access-Control-Allow-Origin: *'); // Поддержка CORS
header('Content-Type: application/json');

// Открываем поток ввода
$fi = fopen("php://input", "rb");
$jsonData = json_decode(fread($fi, 2000)); // Читаем первые 2000 байт (JSON)

// Проверяем, существует ли папка uploads, если нет — создаем
if (!file_exists('uploads')) {
    mkdir('uploads', 0777, true);
}

// Извлекаем имя файла из источника или генерируем уникальное имя
$source = $jsonData->source ?? 'local';
$fname = (strpos($source, 'http') === 0) 
    ? substr($source, strrpos($source, '/') + 1) 
    : 'image_' . time() . '.png';

// Сохраняем файл
$fo = fopen("uploads/" . $fname, "wb");
while ($buf = fread($fi, 50000)) {
    fwrite($fo, $buf);
}
fclose($fi);
fclose($fo);

// Ответ для Photopea
$response = [
    "message" => "Файл успешно сохранен как $fname",
    "newSource" => "https://project-echo.ru/uploads/$fname" // Замените на ваш домен
];
echo json_encode($response);
?>