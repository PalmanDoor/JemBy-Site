<?php
include("db.php");

// Вспомогательные функции для авторизации
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

// Получение данных пользователя
if (isset($_SESSION["username"])) {
    $stmt = $pdo_authy->prepare("SELECT level, premium, EE FROM players WHERE username = :username");
    $stmt->execute(['username' => $_SESSION["username"]]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $_SESSION["level"] = $user['level'];
        $_SESSION["premium"] = $user['premium'];
        $_SESSION["ee_coins"] = $user['EE'];
    }
}

$current_url = basename($_SERVER['REQUEST_URI'], ".php");
?>

<div class="header">
    <div class="navigation">
        <div class="header-left">
            <a href="home" class="logo">
                <span class="logo-text">Project Echo</span>
            </a>
            <button class="menu-toggle">
                <span class="menu-icon"></span>
                <span class="menu-icon"></span>
                <span class="menu-icon"></span>
            </button>
        </div>
        <div class="nav-container">
            <ul class="nav">
                <?php
                $menu_items = [];
                if ($pdo->query("SHOW TABLES LIKE 'lowerheader'")->rowCount() > 0) {
                    $stmt = $pdo->query("SELECT * FROM lowerheader ORDER BY `order` ASC");
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $menu_items[] = $row;
                    }

                    $main_items = array_slice($menu_items, 0, 4);
                    $more_items = array_slice($menu_items, 4);

                    foreach ($main_items as $item) {
                        $is_active = ($current_url == basename($item['url'], ".php")) ? 'active' : '';
                        echo "<li class='{$is_active}'><a href='" . htmlspecialchars($item['url']) . "'>" . htmlspecialchars($item['name']) . "</a></li>";
                    }

                    if (count($more_items) > 0) {
                        echo "<li><a href='#' class='other-toggle'>Другое</a>";
                        echo "<ul class='other-menu'>";
                        foreach ($more_items as $item) {
                            echo "<li><a href='" . htmlspecialchars($item['url']) . "'>" . htmlspecialchars($item['name']) . "</a></li>";
                        }
                        echo "</ul></li>";
                    }
                } else {
                    echo "<li>Таблица lowerheader не найдена.</li>";
                }
                ?>
            </ul>
            <div class="user-section">
                <ul class="user-info">
                    <?php
                    if (isset($_SESSION["username"])) {
                        $username_class = $_SESSION["premium"] ? 'premium-user' : '';
                        $coins_class = $_SESSION["ee_coins"] == 0 ? 'no-coins' : '';
                        echo "<li class='user-name $username_class'><a href='https://project-echo.ru/user'>" . htmlspecialchars($_SESSION["username"]) . "</a></li>";
                        echo "<li class='user-coins $coins_class'><a href='payment'>💰 <span id='user-coins-value'>" . htmlspecialchars($_SESSION["ee_coins"]) . "</span></a></li>";
                    }
                    ?>
                </ul>
                <ul class="user-actions">
                    <?php
                    if (isset($_SESSION["username"])) {
                        if ($_SESSION["level"] >= 4) {
                            echo "<li><a href='controlpanel' class='admin'>Админ</a></li>";
                        }
                        echo "<li><a href='exit' class='logout'>Выйти</a></li>";
                    } else {
                        echo "<li><a href='#' id='auth-trigger'>Войти</a></li>";
                    }
                    ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<div id="low-balance-notification" class="low-balance-notification" style="display: none;">
    У вас закончились монеты! <a href="payment">Пополните баланс</a>.
</div>

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
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Элементы для авторизации
    const modal = document.getElementById('authModal');
    const authTrigger = document.getElementById('auth-trigger');
    const tabs = document.querySelectorAll('.auth-tab');
    const forms = document.querySelectorAll('.auth-form');
    const switches = document.querySelectorAll('.auth-switch');
    const closeBtn = document.querySelector('.auth-modal-close');
    const overlay = document.querySelector('.auth-modal-overlay');
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');

    // Функции для модального окна авторизации
    function openModal() {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    
    function closeModal() {
        modal.classList.remove('active');
        document.body.style.overflow = '';
        clearForms();
    }
    
    function clearForms() {
        if (loginForm) loginForm.reset();
        if (registerForm) registerForm.reset();
        hideErrors();
    }
    
    function hideErrors() {
        const loginError = document.getElementById('loginError');
        const registerError = document.getElementById('registerError');
        if (loginError) loginError.style.display = 'none';
        if (registerError) registerError.style.display = 'none';
    }
    
    function switchForm(formId) {
        tabs.forEach(tab => {
            tab.classList.toggle('active', tab.dataset.tab === formId);
        });
        
        forms.forEach(form => {
            form.classList.toggle('active', form.id === `${formId}-form`);
        });
        
        hideErrors();
    }

    function showError(elementId, message) {
        const errorEl = document.getElementById(elementId);
        if (errorEl) {
            errorEl.textContent = message;
            errorEl.style.display = 'block';
            
            setTimeout(() => {
                errorEl.style.display = 'none';
            }, 5000);
        }
    }

    // Обработчики событий для авторизации
    if (authTrigger) {
        authTrigger.addEventListener('click', function(e) {
            e.preventDefault();
            openModal();
        });
    }
    
    if (closeBtn) {
        closeBtn.addEventListener('click', closeModal);
    }
    
    if (overlay) {
        overlay.addEventListener('click', closeModal);
    }
    
    // Закрытие по Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal && modal.classList.contains('active')) {
            closeModal();
        }
    });
    
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
    if (loginForm) {
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
            
            fetch(window.location.href, {
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
    }
    
    // AJAX обработка формы регистрации
    if (registerForm) {
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
            
            fetch(window.location.href, {
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

    // Обработчики для подменю "Другое"
    otherToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const menu = this.nextElementSibling;
            if (menu && menu.classList.contains('other-menu')) {
                // Закрываем все остальные меню
                document.querySelectorAll('.other-menu').forEach(m => {
                    if (m !== menu) {
                        m.classList.remove('open');
                        m.previousElementSibling.classList.remove('open');
                    }
                });
                
                // Переключаем текущее меню
                menu.classList.toggle('open');
                this.classList.toggle('open');
            }
        });
    });

    // Закрытие подменю при клике вне их
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.other-menu') && !e.target.closest('.other-toggle')) {
            document.querySelectorAll('.other-menu').forEach(menu => {
                menu.classList.remove('open');
                menu.previousElementSibling.classList.remove('open');
            });
        }
    });

    // Показ уведомления о низком балансе
    const userCoinsElement = document.getElementById('user-coins-value');
    const notificationElement = document.getElementById('low-balance-notification');
    
    if (userCoinsElement && notificationElement) {
        const coins = parseInt(userCoinsElement.textContent);
        if (coins === 0) {
            setTimeout(() => {
                notificationElement.style.display = 'block';
                setTimeout(() => {
                    notificationElement.style.display = 'none';
                }, 10000);
            }, 2000);
        }
    }
});
</script>