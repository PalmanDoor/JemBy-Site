<?php 
include "db.php";
$title = "Обратная связь";
$_SESSION["prevpage"] = "contact.php";
include_once 'includes/lowerheader.php';
include_once 'includes/header.php';
?>

<div class="content">
    <?php
    if(isset($_SESSION["message"])){
        echo '<h4 class="contact-message">' . $_SESSION["message"] . '</h4>';
        $_SESSION["message"] = NULL;
    }
    ?>

    <div class="contact-info">
        <p>Если у вас возникли какие-либо вопросы или проблемы, пожалуйста, свяжитесь с нами через форму ниже.</p>
        <form id="contact-form" action="send_feedback.php" method="POST" class="contact-form">
            <label for="type" class="contact-label">Тип сообщения:</label>
            <select id="type" name="type" class="contact-select" required>
                <option value="report">Репорт на игрока</option>
                <option value="help">Запрос помощи</option>
            </select>
            <label for="subject" class="contact-label">Тема:</label>
            <input type="text" id="subject" name="subject" class="contact-input" required>
            <label for="message" class="contact-label">Сообщение:</label>
            <textarea id="message" name="message" class="contact-textarea" required></textarea>
            <button type="submit" class="contact-button">Отправить</button>
        </form>
    </div>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

<script>
$(document).ready(function() {
    $('#contact-form').on('submit', function(e) {
        e.preventDefault();
        const form = $(this);
        const formData = form.serialize();

        $.post('send_feedback.php', formData, function(response) {
            Swal.fire({
                title: 'Успешно!',
                text: 'Ваше сообщение было отправлено.',
                icon: 'success',
                confirmButtonText: 'ОК'
            }).then(() => {
                location.reload();
            });
        }).fail(function() {
            Swal.fire({
                title: 'Ошибка!',
                text: 'Произошла ошибка при отправке сообщения. Пожалуйста, попробуйте позже.',
                icon: 'error',
                confirmButtonText: 'ОК'
            });
        });
    });
});
</script>

<style>
/* Основные стили для формы обратной связи */
.contact-message {
    color: #00c853; /* Зеленый цвет для успешных сообщений */
    text-align: center;
    margin-bottom: 20px;
}

.contact-info {
    font-size: 16px;
    line-height: 1.6;
    margin-bottom: 20px;
    color: #ddd; /* Светло-серый цвет текста */
}

.contact-form {
    display: flex;
    flex-direction: column;
    max-width: 600px; /* Установить максимальную ширину формы */
    margin: 0 auto 20px; /* Центрирование формы */
    padding: 20px;
    background-color: #333; /* Темный фон формы */
    border-radius: 8px; /* Скругленные углы */
}

.contact-label {
    font-weight: bold;
    margin-bottom: 5px;
    color: #f1f1f1; /* Цвет меток */
}

.contact-select,
.contact-input,
.contact-textarea {
    padding: 10px;
    border: 1px solid #444; /* Темные границы */
    border-radius: 5px;
    margin-bottom: 15px;
    font-size: 16px;
    color: #fff; /* Белый текст */
    background-color: #222; /* Темный фон для полей ввода */
}

.contact-textarea {
    height: 100px; /* Установка высоты текстового поля */
    resize: vertical; /* Вертикальное изменение размера */
}

.contact-button {
    padding: 10px;
    border: none;
    background-color: #4285f4; /* Цвет кнопки */
    color: #fff; /* Белый текст */
    font-size: 16px;
    border-radius: 5px;
    cursor: pointer;
}

.contact-button:hover {
    background-color: #357ae8; /* Цвет кнопки при наведении */
}
</style>

<?php include_once 'includes/footer.php'; ?>
