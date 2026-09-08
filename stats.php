<?php
include "db.php";
$_SESSION["prevpage"] = "stats.php";

if (!isset($_SESSION["username"])) {
    header("location:login.php");
    exit();
}

$title = "Игроки";
include_once 'includes/header.php';
include_once 'includes/lowerheader.php';
require_once 'rcon.php';

use Thedudeguy\Rcon;

// RCON configuration
$rcon_host = '45.93.200.220';
$rcon_port = 25577;
$rcon_password = '67stad5yguSa3';

function execute_rcon_command($command) {
    global $rcon_host, $rcon_port, $rcon_password;
    $rcon = new Rcon($rcon_host, $rcon_port, $rcon_password, 3);

    if ($rcon->connect()) {
        $response = $rcon->sendCommand($command);
        $rcon->disconnect();
        return $response;
    }
    return false;
}

// Получение списка игроков онлайн через команду RCON
$response = execute_rcon_command("list");
$online_players = [];

if (preg_match('/There are \d+ of a max of \d+ players online: (.*)/', $response, $matches)) {
    $online_players = array_filter(array_map('trim', explode(', ', $matches[1])));
}

// Добавление незнакомых игроков в базу данных
foreach ($online_players as $player) {
    if (!empty($player)) {
        $stmt = $pdo_stats->prepare("SELECT COUNT(*) FROM players WHERE username = :username");
        $stmt->execute(['username' => $player]);
        $count = $stmt->fetchColumn();

        if ($count == 0) {
            $stmt = $pdo_stats->prepare("INSERT INTO players (username, role) VALUES (:username, 'Игрок')");
            $stmt->execute(['username' => $player]);
        }
    }
}

// Получение данных пользователей из базы данных minecraft_stats
$players_per_page = 8;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $players_per_page;

$stmt = $pdo_stats->prepare("SELECT id, username, role FROM players WHERE username != '' ORDER BY id LIMIT :offset, :limit");
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->bindParam(':limit', $players_per_page, PDO::PARAM_INT);
$stmt->execute();
$players = $stmt->fetchAll();

$stmt = $pdo_stats->query("SELECT COUNT(*) FROM players WHERE username != ''");
$total_players = $stmt->fetchColumn();
$total_pages = ceil($total_players / $players_per_page);

// Сопоставление ролей и цветов
$role_colors = [
    'Админ' => 'red',
    'Модератор' => 'blue',
    'Игрок' => 'green',
    'Забанен' => 'black',
    'Спонсор' => 'purple',
    'Сервер' => '#A52A2A'
];

function get_random_color() {
    return sprintf('#%06X', mt_rand(0, 0xFFFFFF));
}
?>

<!-- Конец заголовка -->
<div class="content">
    <h2>История игроков</h2>
    <table class="stats-table">
        <tr>
            <th>Ранг</th>
            <th>Аватар</th>
            <th>Ник</th>
            <th>Статус</th>
            <th>Роль</th>
        </tr>
        <?php foreach ($players as $index => $player): ?>
            <?php 
            if (empty($player['username'])) continue;

            $username = htmlspecialchars($player['username']);
            $role = htmlspecialchars($player['role']);
            $role_color = isset($role_colors[$role]) ? $role_colors[$role] : get_random_color();
            $status = in_array($username, $online_players) ? 'Online' : 'Offline';
            ?>
            <tr>
                <td><?php echo $player['id']; ?></td>
                <td><img src="https://minotar.net/cube/<?php echo $username; ?>" alt="Аватар" width="32" height="32"></td>
                <td><?php echo $username; ?></td>
                <td style="color: <?php echo $status == 'Online' ? 'green' : 'coral'; ?>">
                    <?php echo $status; ?>
                </td>
                <td style="color: <?php echo $role_color; ?>">
                    <?php echo $role; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
    
    <div class="pages">
        <?php if ($page > 1): ?>
            <a href="stats.php?page=<?php echo $page - 1; ?>">Предыдущая</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="stats.php?page=<?php echo $i; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
        
        <?php if ($page < $total_pages): ?>
            <a href="stats.php?page=<?php echo $page + 1; ?>">Следующая</a>
        <?php endif; ?>
    </div>
    
</div>

<style>
/* Общие стили для контента */
/* Стили для таблицы */
.stats-table {
    width: 100%;
    border-collapse: collapse;
    margin: 0 auto;
    background-color: #2b2d31; /* Темный фон таблицы */
    color: #fff; /* Белый текст в таблице */
    border-radius: 10px; /* Скругленные углы таблицы */
    overflow: hidden; /* Обрезка содержимого за пределами границ таблицы */
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.7); /* Тень вокруг таблицы */
	min-width: 920px;
}

.stats-table th, .stats-table td {
    border: 1px solid #444; /* Темные границы ячеек */
    padding: 12px 15px; /* Внутренние отступы ячеек */
    text-align: center;
}

.stats-table th {
    background-color: #333; /* Темный фон для заголовков */
    color: #e0e0e0; /* Светлый цвет текста для заголовков */
    font-size: 16px;
}

.stats-table tr:nth-child(even) {
    background-color: #3a3a3a; /* Темный фон для четных строк */
}

.stats-table tr:nth-child(odd) {
    background-color: #2e2e2e; /* Более темный фон для нечетных строк */
}

.stats-table tr:hover {
    background-color: #4c4c4c; /* Цвет строки при наведении */
}

/* Стили для статуса игрока */
.stats-table td img {
    border-radius: 50%; /* Круглая аватарка */
    border: 2px solid #555; /* Темная граница вокруг аватарки */
}

/* Стили для ссылок навигации по страницам */
.pages {
    display: flex;
    justify-content: center; /* Центрирование по горизонтали */
    margin-top: 20px;
}

.pages a {
    background-color: #444; /* Темный фон для ссылок */
    color: #e0e0e0; /* Светлый текст */
    padding: 10px 15px;
    margin: 0 5px;
    border-radius: 5px;
    text-decoration: none;
    font-family: Arial, sans-serif;
    font-size: 14px;
}

.pages a:hover {
    background-color: #555; /* Более светлый фон при наведении */
}

/* Стили для активной ссылки */
.pages a.active {
    background-color: #333; /* Цвет для активной страницы */
    color: #fff; /* Белый текст для активной ссылки */
    cursor: default;
}

/* Стили для пагинации, если нужно добавить текст */
.pages span {
    background-color: transparent;
    color: #e0e0e0; /* Светлый цвет текста */
    padding: 10px 15px;
    margin: 0 5px;
    border-radius: 5px;
    font-family: Arial, sans-serif;
    font-size: 14px;
}
</style>

<?php 
include_once 'includes/footer.php';
?>