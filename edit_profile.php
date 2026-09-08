<?php
include_once "db.php"; // Подключение к базе данных

if (!isset($_SESSION["username"])) {
    header("location:login.php");
    exit();
}

$username = $_SESSION["username"]; // Получаем ник пользователя
$title = "Редактирование настроек";
include 'includes/header.php';
include 'includes/lowerheader.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old_password = $_POST['old_password'];
    $new_password = $_POST['new_password'];

    // Проверка старого пароля
    $stmt = $pdo->prepare("SELECT password FROM authy.players WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($old_password, $user['password'])) {
        // Хэшируем новый пароль
        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

        // Обновляем пароль в базе данных
        $update = $pdo->prepare("UPDATE authy.players SET password = ? WHERE username = ?");
        $update->execute([$hashed_password, $username]);

        $message = "Пароль успешно обновлен.";
    } else {
        $message = "Неверный старый пароль.";
    }
}
?>

<div class="settings-container">
    <h2>Редактирование настроек</h2>

    <?php if (isset($message)): ?>
        <p class="message"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <form method="POST" action="">
        <label for="old_password">Старый пароль:</label>
        <input type="password" name="old_password" id="old_password" required>

        <label for="new_password">Новый пароль:</label>
        <input type="password" name="new_password" id="new_password" required>

        <button type="submit">Сменить пароль</button>
    </form>
</div>

<?php include 'includes/footer.php'; ?>