<?php
$title = "Возможности Premium";
$_SESSION["prevpage"] = "premium_features";

include_once 'includes/header.php';
include_once 'includes/lowerheader.php';
include 'db.php';

// Проверка статуса премиум
$isPremium = false;
$eeCoins = 0;
$loggedIn = isset($_SESSION["username"]);

if ($loggedIn) {
    $stmt = $pdo_authy->prepare("SELECT premium, EE FROM players WHERE username = :username");
    $stmt->execute(['username' => $_SESSION["username"]]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        $isPremium = $user['premium'] == 1;
        $eeCoins = $user['EE'];
    }
}
?>

<div class="content">
    <h2>Возможности Premium</h2>
    <div class="premium-cards-container">
        <div class="premium-card">
            <h3>/co i</h3>
            <div class="separator"></div>
            <p>Возможность просматривать логи кто сломал блок, кто поставил блок</p>
        </div>
        <div class="premium-card">
            <h3>/skin set</h3>
            <div class="separator"></div>
            <p>Возможность изменять скин не выходя из майнкрафта</p>
        </div>
        <div class="premium-card">
            <h3>Сохранение вещей</h3>
            <div class="separator"></div>
            <p>Не бойтесь смерти, вещи после смерти вы не потеряете</p>
        </div>
        <div class="premium-card">
            <h3>AntiAd</h3>
            <div class="separator"></div>
            <p>Уведомления в чате со звуком вас больше не побеспокоят</p>
        </div>
        <div class="premium-card">
            <h3>Префикс</h3>
            <div class="separator"></div>
            <p>Устанавливайте свой префикс и дайте ему цвет!</p>
        </div>
    </div>
    <p>К тому же есть возможность обходить лимит по игровым слотам</p>

    <?php if ($loggedIn): ?>
        <!-- Кнопка Купить Premium -->
        <a href="#" id="buyPremium" class="button11">Купить Premium</a>
    <?php endif; ?>
    
    <!-- Добавляем изображение жителя -->
    <img src="images/input/village.png" alt="Village" class="village-image">
</div>

<?php include_once 'includes/footer.php'; ?>

<!-- Подключаем toastr для уведомлений -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

<script>
const isPremium = <?php echo json_encode($isPremium); ?>;
const eeCoins = <?php echo json_encode($eeCoins); ?>;

document.addEventListener('DOMContentLoaded', function() {
    const buyPremiumButton = document.getElementById('buyPremium');

    if (buyPremiumButton) {
        buyPremiumButton.addEventListener('click', function() {
            if (isPremium) {
                toastr.warning('Премиум уже куплен.');
            } else {
                if (eeCoins < 150) {
                    toastr.error('Недостаточно монет. Пополните баланс.');
                    setTimeout(() => {
                        window.location.href = 'payment';
                    }, 1500);
                } else {
                    fetch('buy_premium.php')
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                toastr.success(data.message);
                                setTimeout(() => {
                                    location.reload();
                                }, 1500);
                            } else if (data.status === 'error') {
                                toastr.error(data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Ошибка:', error);
                            toastr.error('Произошла ошибка при покупке премиума.');
                        });
                }
            }
        });
    }
});
</script>

<style>
/* Общие стили для контента */
.content {
    text-align: center;
}

h2 {
    color: #ffcc00;
    margin-bottom: 30px;
    font-size: 2.5em; /* Увеличенный размер шрифта для заголовка */
}

/* Контейнер для карточек премиума */
.premium-cards-container {
    display: flex;
    flex-wrap: nowrap;
    overflow-x: auto;
    padding: 20px 0;
    gap: 20px;
    justify-content: center;
}

/* Стиль для карточек премиума */
.premium-card {
    position: relative;
    width: 200px; /* Уменьшенная ширина карточки */
    height: 270px; /* Уменьшенная высота карточки */
    padding: 20px;
    border: 1px solid #444;
    border-radius: 10px;
    background-color: #1e1e1e;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
    transition: transform 0.5s ease, box-shadow 0.5s ease, z-index 0.5s ease; /* Плавный переход */
    flex-shrink: 0;
    overflow: hidden; /* Прячем переполнение контента */
}

.premium-card:hover {
    transform: translateX(5px); /* Сдвиг карточки вправо при наведении */
    box-shadow: 0 12px 24px rgba(0, 0, 0, 0.5); /* Увеличение тени */
    z-index: 10;
}

.premium-card:hover::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    border: 2px solid #ffcc00;
    border-radius: 10px;
    z-index: -1;
    transform: scale(1.1);
    animation: card-outage 0.5s forwards; /* Анимация отсечения */
}

.premium-card h3 {
    margin-bottom: 10px;
    color: #ffcc00;
    font-size: 1.5em; /* Увеличенный размер шрифта для заголовков */
}

.premium-card .separator {
    width: 60px;
    height: 3px;
    background-color: #ffcc00;
    margin: 10px auto;
}

.premium-card p {
    margin: 0;
    font-size: 1em; /* Увеличенный размер шрифта для текста */
}

.premium-card + .premium-card {
    margin-left: -60px; /* Сдвиг карточек влево для эффекта наложения */
}

.village-image {
    position: absolute;
    top: 250px; /* Отступ сверху на 100 пикселей */
    right: 0px; /* Отступ справа */
    width: 250px; /* Ширина изображения */
    height: auto; /* Автоматическая высота для сохранения пропорций */
    z-index: 100;
}

@media (max-width: 768px) {
    .premium-cards-container {
        flex-direction: column;
        align-items: center;
    }

    .premium-card + .premium-card {
        margin-left: 0;
        margin-top: -30px; /* Уменьшенный сдвиг для мобильных устройств */
    }

    .village-image {
        position: relative;
        top: 10px;
        right: 0;
        margin: 20px auto; /* Центрирование изображения на мобильных устройствах */
    }
}

/* Анимация отсечения */
@keyframes card-outage {
    0% {
        opacity: 0;
        transform: scale(0.9);
    }
    100% {
        opacity: 1;
        transform: scale(1.1);
    }
}

/* Стиль для кнопки "Купить Premium" */
a.button11 {
    position: relative;
    z-index: 1;
    color: black;
    font-size: 135%;
    font-weight: 700;
    text-decoration: none;
    padding: 0.25em 0.5em;
    display: inline-block;
    background: linear-gradient(45deg, #d4536d, #c61e40);
    border: 2px solid #c61e40;
    border-radius: 5px;
    overflow: hidden;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
    text-transform: uppercase;
    transition: background-color 0.3s, box-shadow 0.3s, transform 0.3s;
}

/* Эффект искажения правого и левого боков */
a.button11:after {
    content: "Купить";  /* Текст кнопки */
    position: absolute;
    z-index: -1;
    top: -2px;
    bottom: -2px;
    left: -2px;
    width: calc(100% + 6 * (1em * 90 / 135) - 2px * 2 * 2); /* Длина для фона и искажения */
    text-align: right;
    color: #fff;
    font-size: 90%;
    padding: 0.25em 0.5em;
    border-radius: 5px;
    border: 2px solid #c61e40;
    -webkit-transform: skewX(-10deg);
    transform: skewX(-10deg);
    background: linear-gradient(45deg, #d4536d, #c61e40) no-repeat 100% 0;
    background-size: calc(6 * (1em * 90 / 135) + 0.5em) 100%;
    box-shadow: inset calc(-6 * (1em * 90 / 135) - 0.5em) 0 rgba(255, 255, 255, 0);
    transition: background-image 0.3s, box-shadow 0.3s, transform 0.3s;
}

a.button11:hover {
    background-color: #c61e40;
    color: #fff;
}

a.button11:hover:after {
    box-shadow: inset calc(-6 * (1em * 90 / 135) - 0.5em) 0 rgba(255, 255, 255, 0.2);
}

a.button11:active:after {
    background-image: linear-gradient(#c61e40, #d4536d);
    transform: skewX(0deg);
}

@media (max-width: 768px) {
    a.button11 {
        font-size: 120%;
        padding: 0.2em 0.4em;
    }
}
</style>