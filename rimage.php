<?php
// Папка, где хранятся изображения
$imageFolder = 'image-folder/';

// Разрешенные типы файлов
$allowedExtensions = ['jpg', 'jpeg', 'gif', 'png'];

// Получаем и сортируем список подходящих файлов
$files = [];
if ($handle = opendir($imageFolder)) {
    while (false !== ($entry = readdir($handle))) {
        if ($entry != "." && $entry != "..") {
            $extension = strtolower(pathinfo($entry, PATHINFO_EXTENSION));
            if (in_array($extension, $allowedExtensions)) {
                $files[] = $entry;
            }
        }
    }
    closedir($handle);
    // Сортируем по имени (чтобы порядок был постоянный)
    sort($files);
}

if (count($files) > 0) {
    // Получаем параметр img из URL (например: index.php?img=1)
    $index = isset($_GET['img']) ? intval($_GET['img']) : -1;

    // Если индекс валиден — берём соответствующий файл
    if ($index >= 0 && $index < count($files)) {
        $selectedImage = $files[$index];
    } else {
        // Иначе выбираем случайное изображение
        $selectedImage = $files[array_rand($files)];
    }

    $imagePath = $imageFolder . $selectedImage;

    // MIME-типы для заголовков
    $mimeTypes = [
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif'  => 'image/gif',
        'png'  => 'image/png'
    ];
    $extension = strtolower(pathinfo($selectedImage, PATHINFO_EXTENSION));
    $mimeType = $mimeTypes[$extension] ?? 'image/jpeg';

    // Выводим заголовки и изображение
    header('Content-Type: ' . $mimeType);
    header('Content-Length: ' . filesize($imagePath));
    readfile($imagePath);
    exit;
} else {
    // Нет изображений
    header('Content-Type: text/html');
    echo 'Нет изображений в папке.';
}
?>
