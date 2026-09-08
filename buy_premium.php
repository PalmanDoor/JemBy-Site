<?php
include 'db.php';

if (!isset($_SESSION["username"])) {
    header("Location: auth");
    exit();
}

$username = $_SESSION["username"];
$stmt = $pdo_authy->prepare("SELECT premium, EE FROM players WHERE username = :username");
$stmt->execute(['username' => $username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    if ($user['premium'] == 1) {
        echo json_encode(['status' => 'error', 'message' => 'Премиум уже куплен.']);
    } else if ($user['EE'] < 150) {
        echo json_encode(['status' => 'error', 'message' => 'Недостаточно монет.']);
    } else {
        $newEE = $user['EE'] - 150;
        $updateStmt = $pdo_authy->prepare("UPDATE players SET premium = 1, EE = :newEE WHERE username = :username");
        $updateStmt->execute(['newEE' => $newEE, 'username' => $username]);

        $_SESSION["premium"] = 1;
        $_SESSION["ee_coins"] = $newEE;

        echo json_encode(['status' => 'success', 'message' => 'Премиум успешно куплен.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Пользователь не найден.']);
}
?>
