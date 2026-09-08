<?php
include("db.php");

if (isset($_SESSION["username"])) {
    $stmt = $pdo_authy->prepare("SELECT EE FROM players WHERE username = :username");
    $stmt->execute(['username' => $_SESSION["username"]]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        echo htmlspecialchars($user['EE']);
    }
}
?>
