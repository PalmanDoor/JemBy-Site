<?php
session_start();
include "db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION["editing"]) || $_SESSION["editing"] !== true) {
        echo json_encode(['success' => false, 'message' => 'Editing mode is not enabled']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $section_name = $input['section_name'];
    $section_content = $input['section_content'];

    $stmt = $pdo->prepare("UPDATE serverinfo SET section_content = ? WHERE section_name = ?");
    $success = $stmt->execute([$section_content, $section_name]);

    if ($success) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}
?>
