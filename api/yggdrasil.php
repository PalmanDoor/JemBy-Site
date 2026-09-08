<?php
header('Content-Type: application/json');
include("db.php"); // Подключение к базе данных

// Включаем логирование ошибок
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/yggdrasil_error.log');

// Отладка загрузки скрипта
error_log("Script loaded at: " . date('Y-m-d H:i:s'));

// Отладка запросов
error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);
error_log("Request URI: " . $_SERVER['REQUEST_URI']);

// Функция проверки пользователя
function authenticateUser($username, $password, $pdo_authy) {
    $query = "SELECT uuid, username, password FROM players WHERE username = ? OR email = ?";
    $stmt = $pdo_authy->prepare($query);
    if (!$stmt) {
        error_log("Failed to prepare authenticate query: " . $pdo_authy->errorInfo()[2]);
        return false;
    }
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && hash('sha256', $password) === $user['password']) {
        return $user;
    }
    return false;
}

// Функция нормализации UUID
function normalizeUUID($uuid) {
    $uuid = str_replace('-', '', strtolower($uuid));
    if (strlen($uuid) === 32) {
        return substr($uuid, 0, 8) . '-' . substr($uuid, 8, 4) . '-' . substr($uuid, 12, 4) . '-' . substr($uuid, 16, 4) . '-' . substr($uuid, 20);
    }
    return $uuid;
}

// Функция подготовки профиля с публичным ключом
function prepareProfileResponse($user, $privateKeyPath, $publicKeyPath) {
    $texturesData = [
        'timestamp' => time(),
        'profileId' => $user['uuid'],
        'profileName' => $user['username'],
        'textures' => [
            'SKIN' => ['url' => 'https://project-echo.ru/skins/default.png']
        ]
    ];
    $textures = base64_encode(json_encode($texturesData));

    if (!file_exists($privateKeyPath) || !file_exists($publicKeyPath)) {
        error_log("Key file not found: private=$privateKeyPath, public=$publicKeyPath");
        throw new Exception("Key file not found");
    }

    $privateKey = openssl_pkey_get_private(file_get_contents($privateKeyPath));
    if (!$privateKey) {
        error_log("Failed to load private key: " . openssl_error_string());
        throw new Exception("Failed to load private key");
    }

    // Подпись для textures с SHA256
    if (!openssl_sign($textures, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
        error_log("Failed to sign textures: " . openssl_error_string());
        throw new Exception("Failed to sign textures");
    }
    $signatureBase64 = base64_encode($signature);

    // Подготовка публичного ключа
    $publicKeyPem = file_get_contents($publicKeyPath);
    $publicKeyClean = preg_replace('/-----BEGIN PUBLIC KEY-----|-----END PUBLIC KEY-----|\s+/', '', trim($publicKeyPem));
    $publicKeyDer = base64_decode($publicKeyClean);
    if ($publicKeyDer === false) {
        error_log("Failed to decode public key: $publicKeyClean");
        throw new Exception("Failed to decode public key");
    }

    // Подпись публичного ключа с SHA256
    if (!openssl_sign($publicKeyDer, $publicKeySignature, $privateKey, OPENSSL_ALGO_SHA256)) {
        error_log("Failed to sign public key: " . openssl_error_string());
        throw new Exception("Failed to sign public key");
    }
    $publicKeySignatureBase64 = base64_encode($publicKeySignature);

    return [
        'id' => $user['uuid'],
        'name' => $user['username'],
        'properties' => [
            [
                'name' => 'textures',
                'value' => $textures,
                'signature' => $signatureBase64
            ]
        ],
        'publicKey' => [
            'key' => base64_encode($publicKeyDer),
            'signature' => $publicKeySignatureBase64
        ]
    ];
}

// Разбираем URL-запрос
$request_method = $_SERVER['REQUEST_METHOD'];
$uri_without_params = strtok($_SERVER['REQUEST_URI'], '?');
$request_uri = explode('/', trim($uri_without_params, '/'));

// Отладка массива URI
error_log("Parsed URI: " . print_r($request_uri, true));

// Проверяем, что запрос идет в правильное API
if (in_array('api', $request_uri) && in_array('yggdrasil.php', $request_uri)) {
    $endpoint = $request_uri[array_search('yggdrasil.php', $request_uri) + 1] ?? '';

    // Корневой GET-запрос
    if ($request_method === 'GET' && empty($endpoint)) {
        echo json_encode([
            "meta" => [
                "serverName" => "Project Echo",
                "implementationName" => "Custom Yggdrasil",
                "implementationVersion" => "1.0"
            ],
            "skinDomains" => ["project-echo.ru"],
            "signaturePublickey" => file_get_contents(__DIR__ . "/public.pem")
        ]);
        exit;
    }

    switch ($endpoint) {
        case 'authenticate':
            if ($request_method !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'MethodNotAllowed', 'errorMessage' => 'Method not allowed']);
                exit;
            }

            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data || empty($data['username']) || empty($data['password'])) {
                http_response_code(400);
                echo json_encode(['error' => 'IllegalArgumentException', 'errorMessage' => 'Missing credentials']);
                exit;
            }

            $user = authenticateUser($data['username'], $data['password'], $pdo_authy);
            if ($user) {
                $accessToken = bin2hex(random_bytes(16));
                $clientToken = $data['clientToken'] ?? bin2hex(random_bytes(16));

                $stmt = $pdo_authy->prepare("UPDATE players SET lastlogin = NOW(), timesloggedin = timesloggedin + 1 WHERE username = ?");
                if (!$stmt || !$stmt->execute([$user['username']])) {
                    error_log("Failed to update player: " . $pdo_authy->errorInfo()[2]);
                    http_response_code(500);
                    echo json_encode(['error' => 'InternalServerError', 'errorMessage' => 'Database error']);
                    exit;
                }

                $stmt = $pdo_authy->prepare("INSERT INTO sessions (accessToken, uuid) VALUES (?, ?) ON DUPLICATE KEY UPDATE created_at = NOW()");
                if (!$stmt || !$stmt->execute([$accessToken, $user['uuid']])) {
                    error_log("Failed to save token: " . $pdo_authy->errorInfo()[2]);
                    http_response_code(500);
                    echo json_encode(['error' => 'InternalServerError', 'errorMessage' => 'Database error']);
                    exit;
                }
                error_log("AccessToken saved for authenticate: accessToken=$accessToken, uuid=" . $user['uuid']);

                echo json_encode([
                    'accessToken' => $accessToken,
                    'clientToken' => $clientToken,
                    'selectedProfile' => [
                        'id' => $user['uuid'],
                        'name' => $user['username']
                    ]
                ]);
            } else {
                http_response_code(403);
                echo json_encode(['error' => 'ForbiddenOperationException', 'errorMessage' => 'Invalid credentials']);
            }
            exit;

        case 'refresh':
            if ($request_method !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'MethodNotAllowed', 'errorMessage' => 'Method not allowed']);
                exit;
            }

            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data || empty($data['accessToken']) || empty($data['clientToken'])) {
                http_response_code(400);
                echo json_encode(['error' => 'IllegalArgumentException', 'errorMessage' => 'Missing accessToken or clientToken']);
                exit;
            }

            $newAccessToken = bin2hex(random_bytes(16));
            echo json_encode([
                'accessToken' => $newAccessToken,
                'clientToken' => $data['clientToken']
            ]);
            exit;

        case 'validate':
            if ($request_method !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'MethodNotAllowed', 'errorMessage' => 'Method not allowed']);
                exit;
            }

            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data || empty($data['accessToken'])) {
                http_response_code(400);
                echo json_encode(['error' => 'IllegalArgumentException', 'errorMessage' => 'Missing accessToken']);
                exit;
            }

            http_response_code(204);
            exit;

        case 'invalidate':
            if ($request_method !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'MethodNotAllowed', 'errorMessage' => 'Method not allowed']);
                exit;
            }

            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data || empty($data['accessToken'])) {
                http_response_code(400);
                echo json_encode(['error' => 'IllegalArgumentException', 'errorMessage' => 'Missing accessToken']);
                exit;
            }

            http_response_code(204);
            exit;

        case 'signout':
            if ($request_method !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'MethodNotAllowed', 'errorMessage' => 'Method not allowed']);
                exit;
            }

            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data || empty($data['username']) || empty($data['password'])) {
                http_response_code(400);
                echo json_encode(['error' => 'IllegalArgumentException', 'errorMessage' => 'Missing credentials']);
                exit;
            }

            http_response_code(204);
            exit;

        case 'users':
            if ($request_method === 'GET' &&
                isset($request_uri[array_search('yggdrasil.php', $request_uri) + 2]) &&
                $request_uri[array_search('yggdrasil.php', $request_uri) + 2] === 'profiles' &&
                $request_uri[array_search('yggdrasil.php', $request_uri) + 3] === 'minecraft') {

                $username = $request_uri[array_search('yggdrasil.php', $request_uri) + 4] ?? '';
                if (empty($username)) {
                    http_response_code(400);
                    echo json_encode(['error' => 'IllegalArgumentException', 'errorMessage' => 'Missing username']);
                    exit;
                }

                $query = "SELECT uuid, username FROM players WHERE username = ?";
                $stmt = $pdo_authy->prepare($query);
                if (!$stmt || !$stmt->execute([$username])) {
                    error_log("Failed to fetch profile: " . $pdo_authy->errorInfo()[2]);
                    http_response_code(500);
                    echo json_encode(['error' => 'InternalServerError', 'errorMessage' => 'Database error']);
                    exit;
                }
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user) {
                    echo json_encode([
                        'id' => $user['uuid'],
                        'name' => $user['username']
                    ]);
                } else {
                    http_response_code(204);
                }
                exit;
            }
            break;

        case 'user':
            if ($request_method === 'GET' &&
                isset($request_uri[array_search('yggdrasil.php', $request_uri) + 2]) &&
                $request_uri[array_search('yggdrasil.php', $request_uri) + 2] === 'profiles' &&
                $request_uri[array_search('yggdrasil.php', $request_uri) + 4] === 'names') {

                $uuid = normalizeUUID($request_uri[array_search('yggdrasil.php', $request_uri) + 3] ?? '');
                if (empty($uuid)) {
                    http_response_code(400);
                    echo json_encode(['error' => 'IllegalArgumentException', 'errorMessage' => 'Missing UUID']);
                    exit;
                }

                $query = "SELECT username FROM players WHERE uuid = ?";
                $stmt = $pdo_authy->prepare($query);
                if (!$stmt || !$stmt->execute([$uuid])) {
                    error_log("Failed to fetch names: " . $pdo_authy->errorInfo()[2]);
                    http_response_code(500);
                    echo json_encode(['error' => 'InternalServerError', 'errorMessage' => 'Database error']);
                    exit;
                }
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user) {
                    echo json_encode([['name' => $user['username']]]);
                } else {
                    http_response_code(204);
                }
                exit;
            }
            break;

        case 'session':
            if ($request_method === 'POST' &&
                $request_uri[array_search('yggdrasil.php', $request_uri) + 2] === 'minecraft' &&
                $request_uri[array_search('yggdrasil.php', $request_uri) + 3] === 'join') {

                $data = json_decode(file_get_contents('php://input'), true);
                if (!$data || empty($data['accessToken']) || empty($data['selectedProfile']) || empty($data['serverId'])) {
                    http_response_code(400);
                    echo json_encode(['error' => 'IllegalArgumentException', 'errorMessage' => 'Missing required fields']);
                    exit;
                }

                $uuid = normalizeUUID($data['selectedProfile']);
                $accessToken = $data['accessToken'];
                $serverId = $data['serverId'];

                $query = "SELECT uuid FROM sessions WHERE accessToken = ? AND uuid = ?";
                $stmt = $pdo_authy->prepare($query);
                if (!$stmt || !$stmt->execute([$accessToken, $uuid])) {
                    error_log("Failed to check token: " . $pdo_authy->errorInfo()[2]);
                    http_response_code(500);
                    echo json_encode(['error' => 'InternalServerError', 'errorMessage' => 'Database error']);
                    exit;
                }
                $session = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$session) {
                    error_log("Invalid accessToken or UUID mismatch: accessToken=$accessToken, uuid=$uuid");
                    http_response_code(403);
                    echo json_encode(['error' => 'ForbiddenOperationException', 'errorMessage' => 'Invalid accessToken']);
                    exit;
                }

                $query = "UPDATE sessions SET serverId = ?, created_at = NOW() WHERE accessToken = ? AND uuid = ?";
                $stmt = $pdo_authy->prepare($query);
                if (!$stmt || !$stmt->execute([$serverId, $accessToken, $uuid])) {
                    error_log("Failed to update session: " . $pdo_authy->errorInfo()[2]);
                    http_response_code(500);
                    echo json_encode(['error' => 'InternalServerError', 'errorMessage' => 'Database error']);
                    exit;
                }

                error_log("Session updated: accessToken=$accessToken, uuid=$uuid, serverId=$serverId");
                http_response_code(204);
                exit;
            }
            elseif ($request_method === 'GET' &&
                    $request_uri[array_search('yggdrasil.php', $request_uri) + 2] === 'minecraft' &&
                    $request_uri[array_search('yggdrasil.php', $request_uri) + 3] === 'profile') {

                $uuid = normalizeUUID($request_uri[array_search('yggdrasil.php', $request_uri) + 4] ?? '');
                error_log("Handling /session/minecraft/profile for uuid: $uuid");

                if (empty($uuid)) {
                    error_log("Missing UUID in /session/minecraft/profile request");
                    http_response_code(400);
                    echo json_encode(['error' => 'IllegalArgumentException', 'errorMessage' => 'Missing UUID']);
                    exit;
                }

                $query = "SELECT uuid, username FROM players WHERE uuid = ?";
                $stmt = $pdo_authy->prepare($query);
                if (!$stmt || !$stmt->execute([$uuid])) {
                    error_log("Failed to fetch profile: " . $pdo_authy->errorInfo()[2]);
                    http_response_code(500);
                    echo json_encode(['error' => 'InternalServerError', 'errorMessage' => 'Database error']);
                    exit;
                }
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user) {
                    try {
                        $response = prepareProfileResponse($user, __DIR__ . '/private.pem', __DIR__ . '/public.pem');
                        error_log("Response prepared: " . json_encode($response));
                        echo json_encode($response);
                    } catch (Exception $e) {
                        http_response_code(500);
                        echo json_encode(['error' => 'InternalServerError', 'errorMessage' => $e->getMessage()]);
                    }
                } else {
                    error_log("User not found for uuid: $uuid");
                    http_response_code(204);
                }
                exit;
            } else {
                error_log("Unexpected /session request: " . $_SERVER['REQUEST_URI']);
                http_response_code(404);
                echo json_encode(['error' => 'NotFound', 'errorMessage' => 'Endpoint not found']);
            }
            break;

				case 'publickeys':
						if ($request_method === 'GET') {
								error_log("Handling /publickeys request");
								$publicKeyPath = __DIR__ . '/public.pem';
								$privateKeyPath = __DIR__ . '/private.pem';
								if (!file_exists($publicKeyPath) || !file_exists($privateKeyPath)) {
										error_log("Key file not found: public=$publicKeyPath, private=$privateKeyPath");
										http_response_code(500);
										echo json_encode(['error' => 'InternalServerError', 'errorMessage' => 'Public key file not found']);
										exit;
								}
								$publicKeyPem = file_get_contents($publicKeyPath);
								$publicKeyClean = preg_replace('/-----BEGIN PUBLIC KEY-----|-----END PUBLIC KEY-----|\s+/', '', trim($publicKeyPem));
								$publicKeyDer = base64_decode($publicKeyClean);
								if ($publicKeyDer === false) {
										error_log("Failed to decode Base64 public key: $publicKeyClean");
										http_response_code(500);
										echo json_encode(['error' => 'InternalServerError', 'errorMessage' => 'Failed to decode public key']);
										exit;
								}

								$privateKey = openssl_pkey_get_private(file_get_contents($privateKeyPath));
								if (!$privateKey || !openssl_sign($publicKeyDer, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
										error_log("Failed to sign public key: " . openssl_error_string());
										http_response_code(500);
										echo json_encode(['error' => 'InternalServerError', 'errorMessage' => 'Failed to sign public key']);
										exit;
								}
								$signatureBase64 = base64_encode($signature);

								$response = [
										'profilePropertyKeys' => [
												[
														'publicKey' => base64_encode($publicKeyDer),
														'signature' => $signatureBase64
												]
										]
								];
								error_log("Public keys response prepared: " . json_encode($response));
								echo json_encode($response);
								exit;
						}
						error_log("Invalid method for /publickeys: $request_method");
						http_response_code(405);
						echo json_encode(['error' => 'MethodNotAllowed', 'errorMessage' => 'Method not allowed']);
						exit;

        case 'sessionserver':
            if ($request_method === 'GET' &&
                $request_uri[array_search('yggdrasil.php', $request_uri) + 2] === 'session' &&
                $request_uri[array_search('yggdrasil.php', $request_uri) + 3] === 'minecraft' &&
                $request_uri[array_search('yggdrasil.php', $request_uri) + 4] === 'hasJoined') {

                $username = $_GET['username'] ?? '';
                $serverId = $_GET['serverId'] ?? '';
                error_log("Handling /hasJoined for username: $username, serverId: $serverId");

                if (empty($username) || empty($serverId)) {
                    http_response_code(400);
                    echo json_encode(['error' => 'IllegalArgumentException', 'errorMessage' => 'Missing username or serverId']);
                    exit;
                }

                $query = "SELECT p.uuid, p.username FROM players p
                          INNER JOIN sessions s ON p.uuid = s.uuid
                          WHERE p.username = ? AND s.serverId = ?";
                $stmt = $pdo_authy->prepare($query);
                if (!$stmt || !$stmt->execute([$username, $serverId])) {
                    error_log("Failed to fetch user: " . $pdo_authy->errorInfo()[2]);
                    http_response_code(500);
                    echo json_encode(['error' => 'InternalServerError', 'errorMessage' => 'Database error']);
                    exit;
                }
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user) {
                    try {
                        $response = prepareProfileResponse($user, __DIR__ . '/private.pem', __DIR__ . '/public.pem');
                        error_log("Response prepared for /hasJoined: " . json_encode($response));
                        echo json_encode($response);
                    } catch (Exception $e) {
                        http_response_code(500);
                        echo json_encode(['error' => 'InternalServerError', 'errorMessage' => $e->getMessage()]);
                    }
                } else {
                    error_log("User not found or session invalid for username: $username, serverId: $serverId");
                    http_response_code(204);
                }
                exit;
            }
            elseif ($request_method === 'GET' &&
                    $request_uri[array_search('yggdrasil.php', $request_uri) + 2] === 'session' &&
                    $request_uri[array_search('yggdrasil.php', $request_uri) + 3] === 'minecraft' &&
                    $request_uri[array_search('yggdrasil.php', $request_uri) + 4] === 'profile') {

                $uuid = normalizeUUID($request_uri[array_search('yggdrasil.php', $request_uri) + 5] ?? '');
                error_log("Handling /sessionserver/session/minecraft/profile for uuid: $uuid");

                if (empty($uuid)) {
                    http_response_code(400);
                    echo json_encode(['error' => 'IllegalArgumentException', 'errorMessage' => 'Missing UUID']);
                    exit;
                }

                $query = "SELECT uuid, username FROM players WHERE uuid = ?";
                $stmt = $pdo_authy->prepare($query);
                if (!$stmt || !$stmt->execute([$uuid])) {
                    error_log("Failed to fetch profile: " . $pdo_authy->errorInfo()[2]);
                    http_response_code(500);
                    echo json_encode(['error' => 'InternalServerError', 'errorMessage' => 'Database error']);
                    exit;
                }
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user) {
                    try {
                        $response = prepareProfileResponse($user, __DIR__ . '/private.pem', __DIR__ . '/public.pem');
                        error_log("Response prepared: " . json_encode($response));
                        echo json_encode($response);
                    } catch (Exception $e) {
                        http_response_code(500);
                        echo json_encode(['error' => 'InternalServerError', 'errorMessage' => $e->getMessage()]);
                    }
                } else {
                    error_log("User not found for uuid: $uuid");
                    http_response_code(404);
                    echo json_encode(['error' => 'NotFound', 'errorMessage' => 'User not found']);
                }
                exit;
            } else {
                error_log("Unexpected /sessionserver request: " . $_SERVER['REQUEST_URI']);
                http_response_code(404);
                echo json_encode(['error' => 'NotFound', 'errorMessage' => 'Endpoint not found']);
            }
            break;

        default:
            http_response_code(404);
            echo json_encode(['error' => 'NotFound', 'errorMessage' => 'Endpoint not found']);
            exit;
    }
}

http_response_code(404);
echo json_encode(['error' => 'NotFound', 'errorMessage' => 'Endpoint not found']);
exit;
?>