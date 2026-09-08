<?php
ob_start();
include_once "db.php";
$_SESSION["prevpage"] = "user_index.php";

// Проверка авторизации
if (!isset($_SESSION["username"])) {
    header("location: /login.php");
    exit();
}

// Получение имени пользователя из URL
$requested_username = isset($_GET['username']) ? trim($_GET['username'], '@/') : $_SESSION["username"];
$requested_username = ltrim($requested_username, '@');

// Проверка существования пользователя
try {
    $stmt = $pdo_authy->prepare("SELECT username FROM players WHERE username = :username");
    $stmt->execute(['username' => $requested_username]);
    $user_exists = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user_exists) {
        header("location: /home");
        exit();
    }
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
    exit();
}

$profile_username = $user_exists['username']; // Имя пользователя профиля из URL или сессии
$current_username = $_SESSION["username"];    // Текущий авторизованный пользователь
$is_own_profile = $profile_username === $current_username;

$title = "$profile_username";
include 'includes/header.php';
include 'includes/lowerheader.php';

// Форматирование даты
function formatDate($dateString) {
    $months = ['янв.', 'фев.', 'мар.', 'апр.', 'мая', 'июн.', 'июл.', 'авг.', 'сен.', 'окт.', 'ноя.', 'дек.'];
    $date = new DateTime($dateString);
    return $date->format('j ') . $months[$date->format('n') - 1] . ' ' . $date->format('Y г. в G:i');
}
?>

<div class="content">
    <div class="user-profile">
        <canvas id="skin_container"></canvas>
        <div class="info-profile">
            <canvas id="info_container"></canvas>
        </div>
    </div>
</div>

<script src="/module/skinview3d.bundle.js"></script>
<script>
    const skinViewer = new skinview3d.SkinViewer({
        canvas: document.getElementById("skin_container"),
        width: 300,
        height: 400,
        skin: `https://minotar.net/skin/<?= urlencode($profile_username) ?>`
    });

    skinViewer.autoRotate = true;
    skinViewer.animation = new skinview3d.WalkingAnimation();
    skinViewer.nameTag = "<?= htmlspecialchars($profile_username) ?>";
    skinViewer.loadCape("/images/cape.png");
    skinViewer.zoom = 0.7;
</script>