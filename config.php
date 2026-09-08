<?php
function include_file($file) {
    // Ищем файл сначала в директории includes
    if (file_exists(INCLUDES_DIR . '/' . $file)) {
        include INCLUDES_DIR . '/' . $file;
    } else {
        // Если не нашли в includes, ищем в корне
        if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . $file)) {
            include $_SERVER['DOCUMENT_ROOT'] . '/' . $file;
        } else {
            // Если не нашли ни в одной из директорий, выводим ошибку
            echo "Ошибка: файл $file не найден!";
        }
    }
}

?>