<?php
include "db.php";
$_SESSION["prevpage"] = "admincp.php";

if (!isset($_SESSION["username"])) {
    header("location:username.php");
    exit();
} elseif ($_SESSION["level"] < 4) {
    header("location:index.php");
    exit();
}

$pdo = $GLOBALS['pdo_authy'];

function logUserAction($pdo, $username, $action) {
    try {
        // Проверяем наличие таблицы
        $query = "SHOW TABLES LIKE 'user_logs'";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        
        if ($stmt->rowCount() === 0) {
            // Если таблицы нет, создаем ее
            $createQuery = "
                CREATE TABLE user_logs (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    username VARCHAR(255) NOT NULL,
                    action TEXT NOT NULL,
                    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ";
            $pdo->exec($createQuery);
        }
        
        // Выполняем вставку
        $query = "INSERT INTO user_logs (username, action) VALUES (:username, :action)";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['username' => $username, 'action' => $action]);
        
    } catch (PDOException $e) {
        error_log("Error in logUserAction: " . $e->getMessage());
        // Можно добавить альтернативное логирование, например, в файл
    }
}

// Обработка запросов
$notification = null; // Переменная для хранения уведомлений

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['user']) && isset($_POST['field']) && isset($_POST['value'])) {
        $user = htmlspecialchars(trim($_POST['user']));
        $field = htmlspecialchars(trim($_POST['field']));
        $value = htmlspecialchars(trim($_POST['value']));
        
        if ($field == 'level' && $_SESSION['level'] < 5) {
            $notification = ['type' => 'error', 'message' => 'У вас недостаточно прав для изменения уровня пользователя.'];
        } else {
            if ($field == 'password') {
                $value = password_hash($value, PASSWORD_DEFAULT);
            }
            
            $allowed_fields = ['username', 'password', 'level', 'email', 'EE', 'premium'];
            if (in_array($field, $allowed_fields)) {
                $query = "UPDATE authy.players SET $field = :value WHERE username = :user";
                $stmt = $pdo->prepare($query);
                $stmt->execute(['value' => $value, 'user' => $user]);
                
                logUserAction($pdo, $_SESSION['username'], 'Updated user: ' . $user . ' field: ' . $field . ' to: ' . $value);
                
                $notification = ['type' => 'success', 'message' => 'Изменения успешно сохранены.'];
            } else {
                $notification = ['type' => 'error', 'message' => 'Недопустимое поле для редактирования.'];
            }
        }
        
        // Сохраняем уведомление в сессии перед перенаправлением
        $_SESSION['notification'] = $notification;
        header("Location: admincp.php");
        exit();
        
    } elseif (isset($_POST['delete_user'])) {
        $user = htmlspecialchars(trim($_POST['delete_user']));
        $query = "DELETE FROM authy.players WHERE username = :user";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['user' => $user]);
        
        logUserAction($pdo, $_SESSION['username'], 'Deleted user: ' . $user);
        
        $_SESSION['notification'] = ['type' => 'success', 'message' => 'Пользователь успешно удален.'];
        
        header("Location: admincp.php");
        exit();
        
    } elseif (isset($_POST['editing_mode']) && isset($_POST['user'])) {
        $editing = intval($_POST['editing_mode']);
        $user = htmlspecialchars(trim($_POST['user']));
        
        $query = "UPDATE authy.players SET editing = :editing WHERE username = :user";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['editing' => $editing, 'user' => $user]);
        
        logUserAction($pdo, $_SESSION['username'], 'Changed editing mode to: ' . $editing);
        
        $_SESSION['notification'] = ['type' => 'success', 'message' => 'Режим редактирования изменен.'];
        
        header("Location: admincp.php");
        exit();
    }
}

// Получение всех пользователей для отображения
$query = "SELECT * FROM authy.players";
$stmt = $pdo->prepare($query);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Получаем уведомление из сессии, если оно есть
$notification = isset($_SESSION['notification']) ? $_SESSION['notification'] : null;
unset($_SESSION['notification']);

$title = "Панель управления администратора";
include_once 'includes/header.php';
include_once 'includes/lowerheader.php';
?>

<div class="content">
    <?php if ($notification): ?>
        <div class="notification <?php echo htmlspecialchars($notification['type']); ?>">
            <?php echo htmlspecialchars($notification['message']); ?>
        </div>
    <?php endif; ?>

    <h3>Список пользователей</h3>
    
    <div class="search-container">
        <input type="text" id="searchInput" placeholder="Поиск пользователей...">
    </div>
    
    <form id="userForm" action="admincp.php" method="post">
        <table id="userTable">
            <thead>
                <tr>
                    <th>Логин</th>
                    <th>Допуск</th>
                    <th>Email</th>
                    <th>EE</th>
                    <th>Премиум</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody id="userTableBody">
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                    <td contenteditable="<?php echo ($_SESSION['level'] >= 5) ? 'true' : 'false'; ?>" data-field="level" data-user="<?php echo htmlspecialchars($user['username']); ?>" <?php if ($_SESSION['level'] < 5) echo 'class="readonly"'; ?>><?php echo htmlspecialchars($user['level']); ?></td>
                    <td contenteditable="true" data-field="email" data-user="<?php echo htmlspecialchars($user['username']); ?>"><?php echo htmlspecialchars($user['email']); ?></td>
                    <td contenteditable="true" data-field="EE" data-user="<?php echo htmlspecialchars($user['username']); ?>"><?php echo htmlspecialchars($user['EE']); ?></td>
                    <td contenteditable="true" data-field="premium" data-user="<?php echo htmlspecialchars($user['username']); ?>"><?php echo htmlspecialchars($user['premium']); ?></td>
                    <td class="action-buttons">
                        <button type="button" class="save-button" data-user="<?php echo htmlspecialchars($user['username']); ?>">Сохранить</button>
                        <button type="button" class="delete-button" data-user="<?php echo htmlspecialchars($user['username']); ?>">Удалить</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </form>

    <hr />
    
    <h3>Вход в режим редактирования</h3>
    <form action="admincp.php" method="post" id="edit-mode-form-1">
        <input type="hidden" name="editing_mode" value="1">
        <input type="hidden" name="user" value="<?php echo htmlspecialchars($_SESSION['username']); ?>">
        <input class="editing-mode" type="submit" value="Войти в режим редактирования">
    </form>
    <form action="admincp.php" method="post" id="edit-mode-form-0">
        <input type="hidden" name="editing_mode" value="0">
        <input type="hidden" name="user" value="<?php echo htmlspecialchars($_SESSION['username']); ?>">
        <input class="editing-mode" type="submit" value="Выйти из режима редактирования">
    </form>
    
    <h3>Панель управления</h3>
    <a href="hovermenu" class="btn-edit-menu">Редактировать меню</a>
</div>

<script>
// Поиск в реальном времени
document.getElementById('searchInput').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const rows = document.querySelectorAll('#userTableBody tr');
    
    rows.forEach(row => {
        const username = row.querySelector('td:first-child').textContent.toLowerCase();
        if (username.includes(searchTerm)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});

// Обработка изменения значений в редактируемых ячейках
document.querySelectorAll('td[contenteditable="true"]').forEach(cell => {
    cell.dataset.originalValue = cell.textContent;
});

// Функция для показа уведомлений
function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.classList.add('notification', type);
    notification.textContent = message;
    notification.style.display = 'block';
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.display = 'none';
        notification.remove();
    }, 3000);
}

// Обработка кнопок "Сохранить" и "Удалить"
document.querySelectorAll('.save-button, .delete-button').forEach(button => {
    button.addEventListener('click', function() {
        const user = this.dataset.user;
        const form = document.getElementById('userForm');
        const formData = new FormData(form);

        if (this.classList.contains('save-button')) {
            const row = this.closest('tr');
            const cells = row.querySelectorAll('td[contenteditable="true"]');

            let hasChanges = false;
            cells.forEach(cell => {
                const field = cell.dataset.field;
                const originalValue = cell.dataset.originalValue;
                const currentValue = cell.textContent.trim();

                if (currentValue !== originalValue) {
                    hasChanges = true;
                    formData.append('user', cell.dataset.user);
                    formData.append('field', field);
                    formData.append('value', currentValue);
                }
            });

            if (!hasChanges) {
                showNotification('Нет изменений для сохранения', 'error');
                return;
            }
        } else if (this.classList.contains('delete-button')) {
            formData.append('delete_user', user);
        }

        // Отправка формы
        fetch('admincp.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.text(); // Ожидаем простой текст, так как перенаправление в PHP
        })
        .then(data => {
            // Предполагая, что PHP делает перенаправление, мы просто обновляем страницу
            window.location.href = 'admincp.php';
        })
        .catch(error => {
            console.error('Ошибка при отправке данных:', error);
            showNotification('Произошла ошибка при обработке запроса', 'error');
        });
    });
});
</script>

<style>
.content {
    padding: 20px;
    color: #fff;
}

.search-container {
    position: relative;
    margin-bottom: 20px;
}

#searchInput {
    width: 20%;
		position: unset;
    padding: 10px 10px 10px 40px;
    background-color: #2c2f33;
    color: #fff;
    border: none;
    border-radius: 5px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

#searchInput::placeholder {
    color: #aaa;
}

#searchInput:focus {
    outline: none;
    box-shadow: 0 0 10px #ba68c8;
}

.search-container::before {
    content: '🔍';
    position: absolute;
    left: 10px;
    top: 50%;
    transform: translateY(-50%);
}

#userTable {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}

#userTable th, #userTable td {
    border: 1px solid #333;
    padding: 10px;
    text-align: center;
    color: #ffffff;
}

#userTable th {
    background-color: #333;
}

#userTable tbody tr {
    background-color: #2a2a2a;
}

#userTable tbody tr:hover {
    background-color: #444;
}

.action-buttons {
    display: flex;
    justify-content: center;
    gap: 10px;
}

.save-button, .delete-button {
    background-color: #444;
    color: #fff;
    border: none;
    padding: 5px 10px;
    border-radius: 5px;
    cursor: pointer;
}

.save-button:hover, .delete-button:hover {
    background-color: #555;
}

.delete-form {
    display: none;
}

.editing-mode {
    background-color: #444;
    color: #fff;
    border: none;
    padding: 8px;
    border-radius: 10px;
    cursor: pointer;
    margin-top: 10px;
    display: inline-block;
    margin-right: 10px;
}

.editing-mode:hover {
    background-color: #555;
}

.btn-edit-menu {
    margin-top: 10px;
    padding: 10px 20px;
    background-color: #17a2b8;
    color: #fff;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    text-align: center;
    text-decoration: none;
    display: inline-block;
}

.notification {
    position: absolute;
    top: 20px;
    right: 20px;
    padding: 15px 20px;
    border-radius: 5px;
    z-index: 1000;
}

.notification.success {
    background-color: #4CAF50;
}

.notification.error {
    background-color: #ff4444;
}

.readonly {
    background-color: #555;
    cursor: not-allowed;
}
</style>

<?php include_once 'includes/footer.php'; ?>