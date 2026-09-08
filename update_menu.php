<?php
include "db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ids = $_POST['id'];
    $names = $_POST['name'];
    $urls = $_POST['url'];
    $orders = $_POST['order'];

    foreach ($ids as $index => $id) {
        $name = htmlspecialchars(trim($names[$index]));
        $url = htmlspecialchars(trim($urls[$index]));
        $order = intval($orders[$index]);

        if ($id) {
            $stmt = $pdo->prepare("UPDATE lowerheader SET name = ?, url = ?, `order` = ? WHERE id = ?");
            $stmt->execute([$name, $url, $order, $id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO lowerheader (name, url, `order`) VALUES (?, ?, ?)");
            $stmt->execute([$name, $url, $order]);
        }
    }
    header("Location: manage_menu.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    parse_str(file_get_contents("php://input"), $requestData);
    $id = intval($requestData['id']);
    $stmt = $pdo->prepare("DELETE FROM lowerheader WHERE id = ?");
    $stmt->execute([$id]);
    echo json_encode(['success' => true]);
    exit();
}
?>
