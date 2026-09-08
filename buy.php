<?php
include "db.php";  // Убедитесь, что `db.php` подключает правильную базу данных `authy`

$rcon_host = '45.93.200.220';
$rcon_port = 25577;
$rcon_password = '67stad5yguSa3';
$mcrcon_path = 'C:/path/to/mcrcon.exe';  // Укажите путь к вашему mcrcon.exe

$data = json_decode(file_get_contents('php://input'), true);
$itemId = $data['itemId'];
$quantity = $data['quantity'];
$totalPrice = $data['totalPrice'];

$username = $_SESSION["username"];

// Проверка наличия монет
$stmt = $pdo->prepare("SELECT EE FROM authy.players WHERE username = :username");
$stmt->execute(['username' => $username]);
$user = $stmt->fetch();

if ($user['EE'] < $totalPrice) {
    echo json_encode(['success' => false, 'message' => 'Недостаточно монет.']);
    exit();
}

// Вычитание монет и выдача предмета
try {
    $pdo->beginTransaction();
    
    // Вычитание монет
    $stmt = $pdo->prepare("UPDATE authy.players SET EE = EE - :totalPrice WHERE username = :username");
    $stmt->execute(['totalPrice' => $totalPrice, 'username' => $username]);
    
    // Получение команды для выдачи предмета
    $stmt = $pdo->prepare("SELECT command FROM `minecraft-website`.shop WHERE id = :itemId");
    $stmt->execute(['itemId' => $itemId]);
    $item = $stmt->fetch();

    if (!$item) {
        throw new Exception("Товар не найден.");
    }

    $command = str_replace("{username}", $username, $item['command']);
    $command = str_replace("{quantity}", $quantity, $command);

    // Проверка и подготовка команды
    if (strpos($command, 'bdonate ') === 0) {
        // Команда уже содержит префикс, выполняем как есть
        $final_command = $command;
    } else {
        // Добавляем префикс к команде
        $final_command = "bdonate give {$username} command $command";
    }

    // Выполнение RCON команды через mcrcon.exe
    $final_command = escapeshellcmd($final_command);  // Безопасное экранирование команды
    $mcrcon_command = "\"$mcrcon_path\" -H $rcon_host -P $rcon_port -p $rcon_password -c \"$final_command\"";

    // Логирование команды для отладки
    file_put_contents('rcon_debug.log', "Executing command: $mcrcon_command\n", FILE_APPEND);

    $output = shell_exec($mcrcon_command);

    // Логирование вывода команды
    file_put_contents('rcon_debug.log', "Command output: $output\n", FILE_APPEND);

    if (strpos($output, "unknown command") !== false) {
        throw new Exception("Неизвестная команда RCON.");
    }

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Покупка успешна!']);
} catch (Exception $e) {
    $pdo->rollBack();
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Ошибка при выполнении покупки: ' . $e->getMessage()]);
}
?>
