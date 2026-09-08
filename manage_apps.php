<?php
include "db.php";

if (!isset($_SESSION['editing']) || !$_SESSION['editing']) {
    http_response_code(403);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$apps_dir = "my_app";

if ($data['action'] === 'add') {
    $folder = preg_replace("/[^a-zA-Z0-9_-]/", "", $data['name']);
    $path = "$apps_dir/$folder";

    if (!is_dir($path)) {
        mkdir($path, 0777, true);
        file_put_contents("$path/info.json", json_encode([
            "name" => $data['name'],
            "version" => "1.0",
            "description" => "Описание приложения"
        ]));
    }
} elseif ($data['action'] === 'delete') {
    $folder = preg_replace("/[^a-zA-Z0-9_-]/", "", $data['folder']);
    $path = "$apps_dir/$folder";
    if (is_dir($path)) {
        array_map('unlink', glob("$path/*"));
        rmdir($path);
    }
} elseif ($data['action'] === 'edit') {
    $folder = $data['folder'];
    $field = $data['field'];
    $value = $data['value'];

    $info_file = "$apps_dir/$folder/info.json";
    if (file_exists($info_file)) {
        $info = json_decode(file_get_contents($info_file), true);
        $info[$field] = $value;
        file_put_contents($info_file, json_encode($info, JSON_PRETTY_PRINT));
    }
}
?>