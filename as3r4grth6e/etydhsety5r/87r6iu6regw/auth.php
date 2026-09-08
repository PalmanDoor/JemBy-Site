<!-- Небольшое примечание, используйте хеширование только на клиенте или на сервере 1 раз но не как на сервере и на клиенте это вызовет ошибку неправильного пароля -->
<?php
header('Content-Type: application/json');

if ($_SERVER['HTTP_USER_AGENT'] !== 'e23695a895a50a389d684375349bd37ab2c0f0a6c4cf63ca84b559f86f0de10') {
    http_response_code(403);
    echo json_encode(['error' => 'Configuration on the server is missing, generation is not possible.']);
    exit;
}

$host = '45.93.200.203';
$user = 'root';
$password = '1php-8hyT23WE5tMy';
$database = 'authy';

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $username = $input['username'];
    $hashedPassword = $input['password']; // Получаем уже хешированный пароль

    $stmt = $conn->prepare('SELECT * FROM players WHERE username = ?');
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        http_response_code(401);
        echo json_encode(['error' => 'User not found']);
        exit;
    }

    $user = $result->fetch_assoc();

    if ($user['password'] !== $hashedPassword) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid password']);
        exit;
    }

    // Генерация UUID
    $uuid = md5($username); // Простой способ генерации UUID

    echo json_encode([
        'access_token' => $uuid,
        'client_token' => $uuid,
        'uuid' => $uuid,
        'name' => $user['username'],
        'user_properties' => '{}'
    ]);
}
$conn->close();
?>