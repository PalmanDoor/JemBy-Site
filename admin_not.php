<?php
include 'db.php';

// Проверка авторизации
if (!isset($_SESSION["username"]) || $_SESSION["demo"] == 1) {
    header("Location: /home");
    exit();
}

// Получаем уровень доступа пользователя из базы данных authy
$username = $_SESSION["username"];
$stmt = $pdo_authy->prepare("SELECT level FROM players WHERE username = ?");
$stmt->execute([$username]);
$userData = $stmt->fetch(PDO::FETCH_ASSOC);

// Проверка, существует ли уже заявка от пользователя
$stmt = $pdo->prepare("SELECT COUNT(*) FROM admin_applications WHERE username = ?");
$stmt->execute([$username]);
$existingApplications = $stmt->fetchColumn();
$hasApplication = ($existingApplications > 0);

// Если пользователь не найден в базе данных, перенаправляем на главную страницу
if (!$userData) {
    header("Location: /home");
    exit();
}

$title = "Заявки на администрацию";
$_SESSION["prevpage"] = "admin-applications";

// Подключение необходимых файлов
include_once 'includes/header.php';
include_once 'includes/lowerheader.php';

// Обработка формы подачи заявки
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_application']) && !$hasApplication) {
    $age = (int)$_POST['age'];
    $occupation = htmlspecialchars($_POST['occupation']);
    $has_microphone = isset($_POST['has_microphone']) ? 1 : 0;
    $additional_info = htmlspecialchars($_POST['additional_info']);
    $discord = htmlspecialchars($_POST['discord']);
    
    // Определяем статус заявки
    $status = 'pending';
    $is_test = 0;
    
    // Если пользователь с уровнем 5, заявка становится тестовой и сразу отклоняется
    if ($userData['level'] == 5) {
        $status = 'rejected';
        $is_test = 1;
    }

    // Проверка возраста
    if ($age < 14) {
        $error = "Вам должно быть не менее 14 лет для подачи заявки.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO admin_applications (username, age, occupation, has_microphone, additional_info, discord, status, is_test, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $result = $stmt->execute([$username, $age, $occupation, $has_microphone, $additional_info, $discord, $status, $is_test]);

        if ($result) {
            $successMessage = "Заявка успешно подана!";
            if ($is_test) {
                $successMessage .= " (Тестовая заявка автоматически отклонена)";
            }
            
            // Сохраняем сообщение об успехе в сессии
            $_SESSION['success_message'] = $successMessage;
            
            // Редирект для предотвращения повторной отправки формы
						echo "<script>window.location.href='" . $_SERVER['REQUEST_URI'] . "';</script>";
						exit();
        } else {
            $error = "Произошла ошибка при подаче заявки.";
        }
    }
}

// Получаем сообщение об успехе из сессии, если оно есть
$success = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : null;
// Удаляем сообщение из сессии после его отображения
unset($_SESSION['success_message']);

// Обработка изменения статуса заявки (доступно только для уровня 6 и выше)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status']) && $userData['level'] >= 6) {
    $application_id = (int)$_POST['application_id'];
    $new_status = htmlspecialchars($_POST['status']);

    $stmt = $pdo->prepare("UPDATE admin_applications SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $application_id]);

    if ($new_status === 'accepted') {
        $stmt = $pdo->prepare("SELECT username FROM admin_applications WHERE id = ?");
        $stmt->execute([$application_id]);
        $application = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt_authy = $pdo_authy->prepare("UPDATE players SET level = 5 WHERE username = ?");
        $stmt_authy->execute([$application['username']]);
        
        // Редирект для предотвращения повторной отправки
				echo "<script>window.location.href='" . $_SERVER['REQUEST_URI'] . "';</script>";
				exit();
    }
}

// Получение всех заявок (доступно только для уровня 6 и выше)
$applications = [];
if ($userData['level'] >= 6) {
    $stmt = $pdo->query("SELECT * FROM admin_applications ORDER BY created_at DESC");
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="content">
    <div class="applications-container">
        <div class="left-column">
            <div class="application-form-container">
                <h3>Подать заявку</h3>
                
                <?php if (isset($success)): ?>
                    <div class="success-message"><?php echo $success; ?></div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if ($hasApplication): ?>
                    <div class="warning-message">
                        Вы уже подали заявку. Повторная подача невозможна.
                    </div>
                <?php else: ?>
                    <?php if ($userData['level'] == 5): ?>
                        <div class="warning-message">
                            Внимание! Ваша заявка будет помечена как тестовая и автоматически отклонена.
                        </div>
                    <?php endif; ?>

                    <form action="" method="post" class="application-form">
                        <div class="form-group">
                            <label for="age">Возраст:</label>
                            <input type="number" id="age" name="age" min="14" max="100" placeholder="Например: 18" required>
                        </div>
                        <div class="form-group">
                            <label for="occupation">Род занятий:</label>
                            <input type="text" id="occupation" name="occupation" placeholder="Например: Студент, программист" required>
                        </div>
                        <div class="form-group">
                            <label for="discord">Ваш Discord:</label>
                            <input type="text" id="discord" name="discord" placeholder="Например: User#1234" required>
                        </div>
                        <div class="form-group">
                            <label for="additional_info">Дополнительная информация:</label>
                            <textarea id="additional_info" name="additional_info" placeholder="Расскажите о своем опыте, мотивации и навыках. Чем больше деталей, тем лучше! Например, какие проекты вы администрировали, какие задачи выполняли, и почему хотите стать частью нашей команды." required></textarea>
                            <div class="form-hint">Не стесняйтесь писать больше текста — это поможет нам лучше понять ваши способности и мотивацию!</div>
                        </div>
                        <div class="form-group">
                            <label for="has_microphone">
                                <input type="checkbox" id="has_microphone" name="has_microphone">
                                Имеется ли микрофон?
                            </label>
                        </div>
                        <button type="submit" name="submit_application" class="btn btn-primary">Подать заявку</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($userData['level'] >= 6): ?>
            <div class="right-column">
                <h3>Список заявок</h3>
                <div class="applications-list">
                    <?php foreach ($applications as $application): ?>
                        <div class="application-item <?php echo $application['status'] !== 'pending' ? 'status-' . $application['status'] : ''; ?>">
                            <div class="application-header">
                                <h4><?php echo htmlspecialchars($application['username']); ?></h4>
                                <div class="status-info">
                                    <span class="status"><?php echo getStatusText($application['status']); ?></span>
                                    <?php if ($application['is_test']): ?>
                                        <span class="test-badge">Тестовая</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="application-details">
                                <p><strong>Возраст:</strong> <?php echo $application['age']; ?></p>
                                <p><strong>Род занятий:</strong> <?php echo htmlspecialchars($application['occupation']); ?></p>
                                <p><strong>Discord:</strong> <?php echo htmlspecialchars($application['discord']); ?></p>
                                <p><strong>Микрофон:</strong> <?php echo $application['has_microphone'] ? 'Да' : 'Нет'; ?></p>
                                <p><strong>Дополнительная информация:</strong></p>
                                <div class="additional-info">
                                    <?php echo nl2br(htmlspecialchars($application['additional_info'])); ?>
                                </div>
                            </div>
                            
                            <form action="" method="post" class="status-form">
                                <input type="hidden" name="application_id" value="<?php echo $application['id']; ?>">
                                <select name="status">
                                    <option value="pending" <?php echo $application['status'] === 'pending' ? 'selected' : ''; ?>>В ожидании</option>
                                    <option value="accepted" <?php echo $application['status'] === 'accepted' ? 'selected' : ''; ?>>Принято</option>
                                    <option value="rejected" <?php echo $application['status'] === 'rejected' ? 'selected' : ''; ?>>Отклонено</option>
                                </select>
                                <button type="submit" name="update_status" class="btn btn-small">Обновить статус</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>

<style>
.applications-container {
    display: flex;
    gap: 30px;
}

.left-column,
.right-column {
    flex: 1;
    background-color: #1e2023;
    padding: 30px; /* Увеличили внутренние отступы для большего пространства */
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
}

h3 {
    color: #ba68c8;
    margin-bottom: 30px;
    font-size: 24px;
    border-bottom: 2px solid #ba68c8;
    padding-bottom: 10px;
}

.form-group {
    margin-bottom: 25px; /* Увеличили отступ между полями формы */
}

input[type="number"],
input[type="text"],
textarea {
    width: 95%;
    padding: 15px; /* Увеличили внутренние отступы для полей ввода */
    margin-top: 10px; /* Увеличили отступ сверху */
    border: none;
    border-radius: 8px; /* Слегка увеличили радиус скругления углов */
    background-color: #2c2f33;
    color: #fff;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1); /* Добавили более мягкую тень */
    transition: all 0.3s ease;
}

textarea {
    min-height: 200px;
    resize: vertical;
}

.form-hint {
    color: #888;
    font-size: 14px;
    margin-top: 10px; /* Увеличили отступ для подсказки */
    font-style: italic;
}

input[type="number"]:focus,
input[type="text"]:focus,
textarea:focus {
    outline: none;
    box-shadow: 0 0 12px #ba68c8; /* Увеличили размер тени при фокусе */
    transform: scale(1.01);
}

.btn {
    background-color: #ba68c8;
    border: none;
    padding: 14px 28px; /* Слегка увеличили размер кнопки */
    border-radius: 6px;
    color: #fff;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 16px;
}

.btn:hover {
    background-color: #9b4d89;
    transform: translateY(-2px);
    box-shadow: 0 5px 10px rgba(0, 0, 0, 0.2);
}

.application-item {
    margin: 25px 0; /* Увеличили отступы между заявками */
    padding: 25px; /* Увеличили внутренние отступы */
    background-color: #2c2f33;
    border-radius: 10px;
    transition: all 0.3s ease;
}

.application-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
}

.application-item.status-accepted,
.application-item.status-rejected {
    opacity: 0.7;
    background-color: rgba(44, 47, 51, 0.7);
}

.application-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px; /* Увеличили отступ */
    border-bottom: 1px solid #ba68c8;
    padding-bottom: 15px; /* Увеличили отступ */
}

.application-header h4 {
    color: #ba68c8;
    margin: 0;
}

.status-info {
    display: flex;
    gap: 10px;
    align-items: center;
}

.status {
    padding: 6px 12px; /* Увеличили размер статусных меток */
    border-radius: 6px;
    font-weight: bold;
}

.test-badge {
    background-color: #ff9800;
    padding: 6px 12px;
    border-radius: 6px;
    font-weight: bold;
}

.application-item.status-accepted .status {
    background-color: #4caf50;
}

.application-item.status-rejected .status {
    background-color: #f44336;
}

.application-item.status-pending .status {
    background-color: #ff9800;
}

.application-details p {
    margin: 12px 0; /* Увеличили отступы между строками */
    line-height: 1.6;
}

.additional-info {
    background-color: #34373c;
    padding: 20px; /* Увеличили внутренние отступы */
    border-radius: 8px;
    margin-top: 15px; /* Увеличили отступ сверху */
    line-height: 1.6;
}

.status-form {
    margin-top: 25px; /* Увеличили отступ */
    display: flex;
    gap: 15px; /* Увеличили зазор между элементами */
}

.success-message,
.error-message,
.warning-message {
    margin-bottom: 25px; /* Увеличили отступ */
    padding: 15px; /* Увеличили внутренние отступы */
    border-radius: 8px;
}

.success-message {
    color: #4caf50;
    background-color: rgba(76, 175, 80, 0.1);
}

.error-message {
    color: #f44336;
    background-color: rgba(244, 67, 54, 0.1);
}

.warning-message {
    color: #ff9800;
    background-color: rgba(255, 152, 0, 0.1);
}
</style>

<?php
function getStatusText($status) {
    switch ($status) {
        case 'pending':
            return 'В ожидании';
        case 'accepted':
            return 'Принято';
        case 'rejected':
            return 'Отклонено';
        default:
            return 'Неизвестный статус';
    }
}
?>