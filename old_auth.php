<?php
include("db.php");

// Функция для входа пользователя
function user_login($username, $password) {
    global $pdo;
    $query = "SELECT * FROM webusers WHERE login = ? AND password = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$username, $password]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $_SESSION["username"] = $username;
        $_SESSION["level"] = $user["level"];
        $_SESSION["editing"] = $user["editing"];
        return true;
    }
    return false;
}

// Обработка формы входа
if (isset($_POST['login_username']) && isset($_POST['login_password'])) {
    if (user_login($_POST['login_username'], $_POST['login_password'])) {
        header("Location: index.php");
        exit();
    } else {
        $error_message = "Неверный логин или пароль.";
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Echo Enclave - Авторизация</title>
    <link rel="icon" type="image/x-icon" href="https://project-echo.ru/images/icosite/favicon.ico">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        body {
            background-color: #2c2f33;
            color: #ffffff;
        }
        .auth-layout {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
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
            width: 505px; /* Устанавливаем фиксированную ширину 505px */
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
            width: 505px; /* Устанавливаем фиксированную ширину 505px */
            height: 62px;
            margin-top: 20px;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            background-color: #9b4d89;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
            transform: translateY(-2px);
        }
        .btn-primary:disabled {
            background-color: #55395d;
        }
        .checkbox {
            display: flex;
            margin: 20px 0;
            width: 505px; /* Устанавливаем фиксированную ширину 505px */
        }
        .checkbox input[type="checkbox"] {
            width: 25px;
            height: 25px;
            margin-right: 10px;
            appearance: none;
            background-color: #141417;
            border: 2px solid #444;
            border-radius: 4px;
            cursor: pointer;
            position: relative;
        }
        .checkbox input[type="checkbox"]:checked {
            background-color: #ba68c8;
            border-color: #ba68c8;
        }
        .checkbox input[type="checkbox"]:checked::after {
            content: '';
            display: block;
            width: 10px;
            height: 18px;
            border: solid #fff;
            border-width: 0 3px 3px 0;
            transform: rotate(45deg);
            position: absolute;
            top: 2px;
            left: 6px;
        }
        .checkbox label {
            color: #ffffff;
        }
        .checkbox input[type="checkbox"]:hover {
            background-color: #0f1013;
        }
        .form-text {
            margin-top: 20px;
            color: #ffffff;
        }
        .form-text a {
            color: #7289da;
        }
        .error-message {
            color: #ff4d4d;
        }
    </style>
</head>
<body>
    <div class="auth-layout">
        <div class="panel">
            <a href="https://project-echo.ru/">
                <img src="https://project-echo.ru/images/icosite/favicon.ico" alt="Echo Enclave" height="128px">
            </a>
            <form method="post" class="d-flex flex-column align-items-center w-100 mt-3">
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger w-100"><?php echo $error_message; ?></div>
                <?php endif; ?>
                <div class="form-group">
                    <label for="login_username">Имя пользователя или Email:</label>
                    <input type="text" id="login_username" name="login_username" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="login_password">Пароль:</label>
                    <input type="password" id="login_password" name="login_password" class="form-control" required>
                </div>
                <div class="checkbox">
                    <input type="checkbox" id="rules" required>
                    <label for="rules">
                        Я прочитал и принимаю <a href="rules.php">правила</a> проекта
                    </label>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Войти</button>
                <div class="form-text mt-auto">
                    <p>Еще не зарегистрированы? <a href="register.php">Зарегистрироваться</a></p>
                </div>
            </form>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"></script>
    <script>
        $(document).ready(function() {
            // Плагин для эффектов при фокусе на поле ввода
            $('.form-control').on('focus', function() {
                $(this).addClass('animate__animated animate__pulse');
            }).on('blur', function() {
                $(this).removeClass('animate__animated animate__pulse');
            });
        });
    </script>
</body>
</html>