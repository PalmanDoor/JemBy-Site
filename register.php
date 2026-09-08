<?php
include("db.php");

// Функция для генерации UUID
function generateUUID($username) {
    $data = md5($username . uniqid('', true));
    return sprintf(
        '%08s-%04s-%04s-%04s-%12s',
        substr($data, 0, 8),
        substr($data, 8, 4),
        substr($data, 12, 4),
        substr($data, 16, 4),
        substr($data, 20, 12)
    );
}

// Функция для получения IP-адреса пользователя
function getUserIP() {
    if (isset($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

// Функция для валидации имени пользователя
function isValidUsername($username) {
    return preg_match('/^[a-zA-Z0-9_]{4,16}$/', $username);
}

if (isset($_POST['register_username']) && isset($_POST['register_password']) && isset($_POST['register_email'])) {
    $username = trim($_POST['register_username']);
    $password = trim($_POST['register_password']);
    $email = trim($_POST['register_email']);
    $uuid = generateUUID($username);
    $ip = getUserIP();

    if (empty($username) || empty($password) || empty($email)) {
        $error_message = "Все поля обязательны для заполнения.";
    } elseif (!isValidUsername($username)) {
        $error_message = "Имя пользователя должно содержать от 4 до 16 символов и может включать только английские буквы, цифры и нижние подчеркивания.";
    } else {
        // Проверка на существование пользователя
        $stmt = $pdo_authy->prepare("SELECT * FROM players WHERE username = :username");
        $stmt->execute(['username' => $username]);
        if ($stmt->fetch()) {
            $error_message = "Пользователь с таким именем уже существует.";
        } else {
            // Хеширование пароля
            $password_hash = hash('sha256', $password);
            // Добавление нового пользователя с demo = 1 и coins = 0
            $stmt = $pdo_authy->prepare("INSERT INTO players (uuid, username, password, email, ip, demo, EE) VALUES (:uuid, :username, :password, :email, :ip, 0, 0)");
            $stmt->execute(['uuid' => $uuid, 'username' => $username, 'password' => $password_hash, 'email' => $email, 'ip' => $ip]);
            $_SESSION["username"] = $username;
            $_SESSION["email"] = $email;

            header("Location: index.php?demo=true");
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Echo Enclave - Регистрация</title>
    <link rel="icon" type="image/x-icon" href="https://project-echo.ru/images/icons/Art_003_Echo_Enclave_Logo.png">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            background: #2c2f33 url('https://project-echo.ru/images/bg9.png') no-repeat center center fixed;
            background-size: cover, contain;
            color: #ffffff;
        }
        .auth-layout {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
        }
        .panel {
            background-color: #1e2023;
            border-radius: 10px;
            width: 625px;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 60px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .form-control {
            background-color: #141417;
            border: none;
            width: 505px;
            height: 56px;
            color: #ffffff;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #ba68c8;
            box-shadow: 0 0 5px #ba68c8;
            outline: none;
        }
        .btn-primary {
            background-color: #ba68c8;
            border: none;
            width: 505px;
            height: 62px;
            margin-top: 20px;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            background-color: #9b4d89;
            transform: translateY(-2px);
        }
        .btn-primary:disabled {
            background-color: #55395d;
        }
        .error-message {
            color: #ff4d4d;
        }
        .form-text {
            margin-top: 20px;
            color: #ffffff;
        }
        .form-text a {
            color: #7289da;
        }
        .alert-success {
            background-color: #ffeb3b;
            color: #d32f2f;
            border-color: #d32f2f;
            position: fixed;
            width: 500px;
            bottom: 20px;
            left: 20px;
            padding: 15px;
            border: 2px solid;
            border-radius: 5px;
            z-index: 1000;
            font-weight: bold;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
            animation: slide-in 0.5s ease-out;
        }
        @keyframes slide-in {
            from {
                transform: translateY(100%);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
    </style>
</head>
<body>
    <div class="auth-layout">
        <div class="panel">
            <a href="https://project-echo.ru/">
                <img src="https://project-echo.ru/images/icons/Art_003_Echo_Enclave_Logo.png" alt="Echo Enclave" height="128px">
            </a>
            <form method="post" class="d-flex flex-column align-items-center w-100 mt-3">
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger w-100"><?php echo $error_message; ?></div>
                <?php endif; ?>
                <div class="form-group w-100">
                    <input type="text" name="register_username" placeholder="Логин" class="form-control" required>
                </div>
                <div class="form-group w-100">
                    <input type="password" name="register_password" placeholder="Пароль" class="form-control" required>
                </div>
                <div class="form-group w-100">
                    <input type="email" name="register_email" placeholder="Email" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary">Зарегистрироваться</button>
            </form>
            <div class="form-text">
                Уже зарегистрированы? <a href="https://project-echo.ru/auth">Войти</a>
            </div>
        </div>
    </div>
    <!-- <div class="alert alert-success">
			<strong>Вы создаете демо-аккаунт!</strong> В демо-аккаунте можно только просматривать сайт. Для полноценного использования необходимо зарегистрироваться на сервере Minecraft по IP: <span style="color: green;">echoenclave.duckdns.org</span> и зайти по этим данным на сайт.
		</div> -->
</body>
</html>
