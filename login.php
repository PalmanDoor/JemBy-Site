<?php
include 'db.php';

// Вспомогательные функции
function generateUUID($username) {
    $data = md5($username . uniqid('', true));
    return sprintf('%08s-%04s-%04s-%04s-%12s', substr($data, 0, 8), substr($data, 8, 4), substr($data, 12, 4), substr($data, 16, 4), substr($data, 20, 12));
}

function getUserIP() {
    return $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
}

function isValidUsername($username) {
    return preg_match('/^[a-zA-Z0-9_]{4,16}$/', $username);
}

// Проверка авторизации
if (isset($_SESSION["username"])) {
    header("Location: home.php");
    exit();
}

// AJAX обработка входа
if (isset($_POST['ajax']) && $_POST['ajax'] === 'login') {
    header('Content-Type: application/json');
    
    $username = trim($_POST['login_username'] ?? '');
    $password = trim($_POST['login_password'] ?? '');
    
    if (empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Логин и пароль не могут быть пустыми.']);
        exit();
    }
    
    if (user_login($username, $password)) {
        echo json_encode(['success' => true, 'redirect' => 'home.php']);
    } else {
        echo json_encode(['success' => false, 'message' => $_SESSION["message"] ?? 'Ошибка входа']);
    }
    exit();
}

// AJAX обработка регистрации
if (isset($_POST['ajax']) && $_POST['ajax'] === 'register') {
    header('Content-Type: application/json');
    
    $username = trim($_POST['register_username'] ?? '');
    $password = trim($_POST['register_password'] ?? '');
    $email = trim($_POST['register_email'] ?? '');
    
    if (empty($username) || empty($password) || empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Все поля обязательны для заполнения.']);
        exit();
    }
    
    if (!isValidUsername($username)) {
        echo json_encode(['success' => false, 'message' => 'Логин должен содержать от 4 до 16 символов (буквы, цифры, _).']);
        exit();
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Некорректный email адрес.']);
        exit();
    }
    
    $stmt = $pdo_authy->prepare("SELECT * FROM players WHERE username = :username");
    $stmt->execute(['username' => $username]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Пользователь с таким именем уже существует.']);
        exit();
    }
    
    $stmt = $pdo_authy->prepare("SELECT * FROM players WHERE email = :email");
    $stmt->execute(['email' => $email]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Пользователь с таким email уже существует.']);
        exit();
    }
    
    $uuid = generateUUID($username);
    $ip = getUserIP();
    $password_hash = hash('sha256', $password);
    
    try {
        $stmt = $pdo_authy->prepare("INSERT INTO players (uuid, username, password, email, ip, demo, EE) VALUES (:uuid, :username, :password, :email, :ip, 0, 30)");
        $stmt->execute(['uuid' => $uuid, 'username' => $username, 'password' => $password_hash, 'email' => $email, 'ip' => $ip]);
        
        $_SESSION["username"] = $username;
        $_SESSION["email"] = $email;
        
        echo json_encode(['success' => true, 'redirect' => 'index.php?demo=true']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Ошибка при создании аккаунта.']);
    }
    exit();
}

function user_login($username, $password) {
    global $pdo_authy;

    $password_hash = hash('sha256', $password);

    $stmt = $pdo_authy->prepare("SELECT * FROM players WHERE username = :username");
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();

    if ($user && $password_hash === $user['password']) {
        $_SESSION["username"] = $user['username'];
        $_SESSION["email"] = $user['email'];

        $stmt = $pdo_authy->prepare("UPDATE players SET lastlogin = NOW(), timesloggedin = timesloggedin + 1 WHERE username = :username");
        $stmt->execute(['username' => $username]);

        return true;
    } else {
        $_SESSION["message"] = $user ? "Неверный пароль." : "Пользователь не найден.";
        return false;
    }
}

$title = "Главная";
$_SESSION["prevpage"] = "auth";

include_once 'includes/header.php';
include_once 'includes/lowerheader.php';
?>

<!-- Модальное окно авторизации -->
<div id="authModal" class="auth-modal">
    <div class="auth-modal-overlay"></div>
    <div class="auth-modal-content">
        <button class="auth-modal-close">&times;</button>
        
        <div class="auth-card">
            <div class="auth-tabs">
                <button class="auth-tab active" data-tab="login">Вход</button>
                <button class="auth-tab" data-tab="register">Регистрация</button>
            </div>
            
            <div class="auth-content">
                <!-- Форма входа -->
                <div class="auth-form active" id="login-form">
                    <h2>Добро пожаловать!</h2>
                    <p class="auth-subtitle">Войдите в свой аккаунт</p>
                    
                    <form id="loginForm" class="form">
                        <div class="error-message" id="loginError" style="display: none;"></div>
                        
                        <div class="input-group">
                            <input type="text" id="login_username" name="login_username" required autocomplete="username">
                            <label for="login_username">Логин</label>
                            <div class="input-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                            </div>
                        </div>
                        
                        <div class="input-group">
                            <input type="password" id="login_password" name="login_password" required autocomplete="current-password">
                            <label for="login_password">Пароль</label>
                            <div class="input-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                </svg>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn-auth">
                            <span>Войти</span>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M5 12h14M12 5l7 7-7 7"></path>
                            </svg>
                        </button>
                    </form>
                    
                    <div class="auth-footer">
                        <p>Нет аккаунта? <a href="#" class="auth-switch" data-target="register">Создать аккаунт</a></p>
                    </div>
                </div>
                
                <!-- Форма регистрации -->
                <div class="auth-form" id="register-form">
                    <h2>Создать аккаунт</h2>
                    <p class="auth-subtitle">Присоединяйтесь к нашему сообществу</p>
                    
                    <form id="registerForm" class="form">
                        <div class="error-message" id="registerError" style="display: none;"></div>
                        
                        <div class="input-group">
                            <input type="text" id="register_username" name="register_username" required>
                            <label for="register_username">Логин</label>
                            <div class="input-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                            </div>
                        </div>
                        
                        <div class="input-group">
                            <input type="email" id="register_email" name="register_email" required>
                            <label for="register_email">Email</label>
                            <div class="input-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                    <polyline points="22,6 12,13 2,6"></polyline>
                                </svg>
                            </div>
                        </div>
                        
                        <div class="input-group">
                            <input type="password" id="register_password" name="register_password" required minlength="6">
                            <label for="register_password">Пароль</label>
                            <div class="input-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                </svg>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn-auth">
                            <span>Создать аккаунт</span>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="8.5" cy="7" r="4"></circle>
                                <line x1="20" y1="8" x2="20" y2="14"></line>
                                <line x1="23" y1="11" x2="17" y2="11"></line>
                            </svg>
                        </button>
                    </form>
                    
                    <div class="auth-footer">
                        <p>Уже есть аккаунт? <a href="#" class="auth-switch" data-target="login">Войти</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Основное содержимое страницы -->
<main class="main-content">
    <div class="container">
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('authModal');
    const tabs = document.querySelectorAll('.auth-tab');
    const forms = document.querySelectorAll('.auth-form');
    const switches = document.querySelectorAll('.auth-switch');
    const closeBtn = document.querySelector('.auth-modal-close');
    const overlay = document.querySelector('.auth-modal-overlay');
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');
    
    // Открытие модального окна при клике на "Войти"
    document.addEventListener('click', function(e) {
        if (e.target.closest('a[href="auth"]')) {
            e.preventDefault();
            openModal();
        }
    });
    
    // Закрытие модального окна
    function closeModal() {
        modal.classList.remove('active');
        document.body.style.overflow = '';
        // Очистка форм
        clearForms();
    }
    
    function openModal() {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    
    function clearForms() {
        loginForm.reset();
        registerForm.reset();
        hideErrors();
    }
    
    function hideErrors() {
        document.getElementById('loginError').style.display = 'none';
        document.getElementById('registerError').style.display = 'none';
    }
    
    closeBtn.addEventListener('click', closeModal);
    overlay.addEventListener('click', closeModal);
    
    // Закрытие по Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.classList.contains('active')) {
            closeModal();
        }
    });
    
    // Функция переключения между формами
    function switchForm(formId) {
        tabs.forEach(tab => {
            tab.classList.toggle('active', tab.dataset.tab === formId);
        });
        
        forms.forEach(form => {
            form.classList.toggle('active', form.id === `${formId}-form`);
        });
        
        hideErrors();
    }
    
    // Обработчики для табов
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            switchForm(tab.dataset.tab);
        });
    });
    
    // Обработчики для ссылок переключения
    switches.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            switchForm(link.dataset.target);
        });
    });
    
    // AJAX обработка формы входа
    loginForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData();
        formData.append('ajax', 'login');
        formData.append('login_username', document.getElementById('login_username').value);
        formData.append('login_password', document.getElementById('login_password').value);
        
        const submitBtn = this.querySelector('.btn-auth');
        const originalText = submitBtn.querySelector('span').textContent;
        submitBtn.querySelector('span').textContent = 'Вход...';
        submitBtn.disabled = true;
        
        fetch('auth.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = data.redirect;
            } else {
                showError('loginError', data.message);
            }
        })
        .catch(error => {
            showError('loginError', 'Произошла ошибка. Попробуйте снова.');
        })
        .finally(() => {
            submitBtn.querySelector('span').textContent = originalText;
            submitBtn.disabled = false;
        });
    });
    
    // AJAX обработка формы регистрации
    registerForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData();
        formData.append('ajax', 'register');
        formData.append('register_username', document.getElementById('register_username').value);
        formData.append('register_email', document.getElementById('register_email').value);
        formData.append('register_password', document.getElementById('register_password').value);
        
        const submitBtn = this.querySelector('.btn-auth');
        const originalText = submitBtn.querySelector('span').textContent;
        submitBtn.querySelector('span').textContent = 'Создание...';
        submitBtn.disabled = true;
        
        fetch('auth.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = data.redirect;
            } else {
                showError('registerError', data.message);
            }
        })
        .catch(error => {
            showError('registerError', 'Произошла ошибка. Попробуйте снова.');
        })
        .finally(() => {
            submitBtn.querySelector('span').textContent = originalText;
            submitBtn.disabled = false;
        });
    });
    
    function showError(elementId, message) {
        const errorEl = document.getElementById(elementId);
        errorEl.textContent = message;
        errorEl.style.display = 'block';
        
        // Автоскрытие ошибки через 5 секунд
        setTimeout(() => {
            errorEl.style.display = 'none';
        }, 5000);
    }
    
    // Анимация полей ввода
    const inputs = document.querySelectorAll('.input-group input');
    inputs.forEach(input => {
        input.addEventListener('input', function() {
            if (this.value) {
                this.classList.add('has-value');
            } else {
                this.classList.remove('has-value');
            }
        });
    });
});
</script>

<style>
/* Основные стили для модального окна */
.auth-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
}

.auth-modal.active {
    opacity: 1;
    visibility: visible;
}

.auth-modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(10px);
}

.auth-modal-content {
    position: relative;
    z-index: 10;
    max-width: 450px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    transform: scale(0.8) translateY(30px);
    transition: transform 0.3s ease;
}

.auth-modal.active .auth-modal-content {
    transform: scale(1) translateY(0);
}

.auth-modal-close {
    position: absolute;
    top: -40px;
    right: 0;
    background: none;
    border: none;
    color: white;
    font-size: 24px;
    cursor: pointer;
    z-index: 11;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.1);
    transition: background 0.3s ease;
}

.auth-modal-close:hover {
    background: rgba(255, 255, 255, 0.2);
}

.auth-card {
    background: rgba(42, 42, 42, 0.95);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 
        0 20px 40px rgba(0, 0, 0, 0.5),
        0 0 0 1px rgba(255, 255, 255, 0.1),
        0 0 30px rgba(186, 104, 200, 0.2);
}

.auth-tabs {
    display: flex;
    background: rgba(34, 34, 34, 0.8);
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.auth-tab {
    flex: 1;
    padding: 18px;
    background: none;
    border: none;
    color: #fff;
    font-family: 'Comic Sans MS', sans-serif;
    font-size: 16px;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
}

.auth-tab:hover {
    background: rgba(255, 255, 255, 0.05);
}

.auth-tab.active {
    color: #ba68c8;
}

.auth-tab.active::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 20%;
    width: 60%;
    height: 3px;
    background: linear-gradient(90deg, transparent, #ba68c8, transparent);
    border-radius: 3px;
}

.auth-content {
    padding: 30px;
}

.auth-form {
    display: none;
    opacity: 0;
    transform: translateX(20px);
    transition: all 0.4s ease;
}

.auth-form.active {
    display: block;
    opacity: 1;
    transform: translateX(0);
}

.auth-form h2 {
    text-align: center;
    margin-bottom: 8px;
    font-size: 28px;
    color: #fff;
    text-shadow: 0 0 10px rgba(186, 104, 200, 0.3);
}

.auth-subtitle {
    text-align: center;
    color: #ccc;
    margin-bottom: 30px;
    font-size: 14px;
}

.form {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.input-group {
    position: relative;
    margin-bottom: 10px;
}

.input-group input {
    width: 100%;
    padding: 16px 48px 16px 16px;
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    color: #fff;
    font-family: 'Comic Sans MS', sans-serif;
    font-size: 16px;
    transition: all 0.3s ease;
    outline: none;
    box-sizing: border-box;
}

.input-group input:focus,
.input-group input.has-value {
    border-color: #ba68c8;
    box-shadow: 0 0 0 2px rgba(186, 104, 200, 0.2);
    background: rgba(255, 255, 255, 0.12);
}

.input-group input:focus + label,
.input-group input.has-value + label {
    top: -8px;
    left: 12px;
    font-size: 12px;
    background: linear-gradient(180deg, #2a2a2a 50%, transparent 50%);
    padding: 0 8px;
    color: #ba68c8;
}

.input-group label {
    position: absolute;
    top: 16px;
    left: 16px;
    color: #999;
    font-size: 16px;
    pointer-events: none;
    transition: all 0.3s ease;
}

.input-icon {
    position: absolute;
    right: 16px;
    top: 50%;
    transform: translateY(-50%);
    color: #999;
    transition: color 0.3s ease;
    pointer-events: none;
}

.input-group input:focus ~ .input-icon,
.input-group input.has-value ~ .input-icon {
    color: #ba68c8;
}

.btn-auth {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    width: 100%;
    padding: 16px;
    background: linear-gradient(135deg, #ba68c8, #9c27b0);
    border: none;
    border-radius: 8px;
    color: #fff;
    font-family: 'Comic Sans MS', sans-serif;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-top: 10px;
}

.btn-auth:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 
        0 8px 20px rgba(186, 104, 200, 0.4),
        0 0 0 1px rgba(255, 255, 255, 0.1);
}

.btn-auth:active {
    transform: translateY(0);
}

.btn-auth:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

.auth-footer {
    text-align: center;
    margin-top: 25px;
    padding-top: 20px;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.auth-footer p {
    color: #ccc;
    font-size: 14px;
    margin: 0;
}

.auth-footer a {
    color: #ba68c8;
    text-decoration: none;
    transition: color 0.3s ease;
}

.auth-footer a:hover {
    color: #d1a3d6;
    text-decoration: underline;
}

.error-message {
    background: rgba(255, 68, 68, 0.1);
    border: 1px solid rgba(255, 68, 68, 0.3);
    color: #ff6b6b;
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 14px;
    text-align: center;
    animation: shake 0.5s ease-in-out;
}

@keyframes shake {
    0%, 20%, 40%, 60%, 80%, 100% { transform: translateX(0); }
    10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
}

/* Основное содержимое страницы */
.main-content {
    padding: 2rem;
    min-height: calc(100vh - 120px);
}

.container {
    max-width: 1200px;
    margin: 0 auto;
}

/* Адаптивность */
@media screen and (max-width: 768px) {
    .auth-modal-content {
        max-width: 95%;
    }
    
    .auth-content {
        padding: 20px;
    }
    
    .auth-form h2 {
        font-size: 24px;
    }
    
    .input-group input {
        padding: 14px 44px 14px 14px;
    }
    
    .auth-modal-close {
        top: -35px;
        right: -5px;
    }
}

@media screen and (max-width: 480px) {
    .auth-tab {
        padding: 14px;
        font-size: 14px;
    }
    
    .auth-content {
        padding: 15px;
    }
    
    .auth-modal-content {
        max-width: 100%;
        margin: 0 10px;
    }
}
</style>