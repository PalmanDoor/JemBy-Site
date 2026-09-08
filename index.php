<?php
$title = "Project Echo - Информация о сервере";
$_SESSION["prevpage"] = "server-info";

include_once 'includes/header.php'; // Подлючаем библиотеки
include_once 'includes/lowerheader.php'; // Подлючаем навигационную панель
include 'db.php'; // Подлючаем базу данных
?>

<style>
/* Героический блок с видео */
.hero-section {
    position: relative;
    height: 100vh;
    overflow: hidden;
    display: flex;
    align-items: center;   /* по вертикали по центру */
    justify-content: center; /* по горизонтали по центру */
}

.hero-video {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    z-index: 1;
}

.hero-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.3);
    z-index: 2;
}

.hero-content {
    position: relative;
    z-index: 4;
    text-align: center;
    color: white;
    max-width: 800px;
    padding: 0 20px;
    margin-top: -200px; /* сдвигаем выше */
}

.hero-logo {
    margin-bottom: 5rem;
    text-shadow: 3px 3px 6px rgba(0, 0, 0, 0.8);
}

.hero-title-image {
    max-width: 1000px; /* Настройте под размер вашего логотипа */
    height: auto;
    display: block;
		justify-self: center;
    image-rendering: -webkit-optimize-contrast;
    image-rendering: pixelated;
}

.hero-subtitle {
    font-family: 'Minecraft Rus';
    src: url('fonts/minecraft.ttf') format('truetype');
    font-size: 1.5rem;
    margin-bottom: 2rem;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.8);
    color: #e0e0e0;
}

/* Волнистый разделитель */
.wave-divider {
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 100px;
    z-index: 3;
    overflow: hidden;
}

.wave-divider svg {
    position: relative;
    display: block;
    width: calc(100% + 1.3px);
    height: 100px;
}

.wave-divider .shape-fill {
    fill: rgba(42, 42, 42);
}

/* Основной контент */
.main-content {
    background: rgba(42, 42, 42, 1);
    backdrop-filter: blur(15px);
    -webkit-backdrop-filter: blur(15px);
    color: #ffffff;
    padding: 80px 0 40px 0;
    position: relative;
}

.content-container {
    max-width: 1366px;
    margin: 0 auto;
    padding: 0 20px;
}

/* Блок информации о сервере */
.server-info-section {
    margin-bottom: 60px;
}

.section-title {
    font-family: 'Comic Sans MS', sans-serif;
    font-size: 29px;
    margin-bottom: 30px;
    color: #e0e0e0;
    text-align: center;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 30px;
    margin-bottom: 40px;
}

.info-card {
    background: rgba(60, 60, 60, 0.8);
    border-radius: 12px;
    padding: 30px;
    text-align: center;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
}

.info-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.4);
}

.info-icon {
    font-size: 2.5rem;
    margin-bottom: 15px;
    color: #ba68c8;
}

.info-title {
    font-family: 'Comic Sans MS', sans-serif;
    font-size: 1.4rem;
    margin-bottom: 15px;
    color: #ba68c8;
    font-weight: bold;
}

.info-text {
    font-family: 'Comic Sans MS', sans-serif;
    font-size: 1rem;
    line-height: 1.6;
    margin-bottom: 20px;
    color: #e0e0e0;
}

.server-ip {
    font-family: 'Comic Sans MS', sans-serif;
    color: #f1c40f;
    font-weight: bold;
    font-size: 1.2rem;
    text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.5);
}

.server-version {
    font-family: 'Comic Sans MS', sans-serif;
    color: #f1c40f;
    font-weight: bold;
    font-size: 1.2rem;
    text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.5);
}

/* Кнопка заявки */
.apply-button {
    background: rgba(60, 60, 60, 0.9);
    color: #ffffff;
    padding: 15px 30px;
    border: 2px solid #ba68c8;
    border-radius: 8px;
    font-family: 'Comic Sans MS', sans-serif;
    font-size: 1.1rem;
    font-weight: bold;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(186, 104, 200, 0.3);
}

.apply-button:hover {
    background: rgba(186, 104, 200, 0.8);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(186, 104, 200, 0.4);
    color: #ffffff;
    text-decoration: none;
}

.telegram-icon {
    width: 20px;
    height: 20px;
    filter: brightness(0) invert(1);
}

/* Блок новостей */
.news-section {
    background: rgba(50, 50, 50, 0.8);
    border-radius: 12px;
    padding: 40px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
}

.news-title {
    font-family: 'Comic Sans MS', sans-serif;
    font-size: 29px;
    color: #e0e0e0;
    text-align: center;
    margin-bottom: 30px;
}

.news-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 25px;
}

.news-item {
    background: rgba(60, 60, 60, 0.6);
    border-radius: 8px;
    padding: 25px;
    transition: all 0.3s ease;
    border: 1px solid rgba(255, 255, 255, 0.05);
}

.news-item:hover {
    background: rgba(70, 70, 70, 0.7);
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
}

.news-date {
    color: #ba68c8;
    font-family: 'Comic Sans MS', sans-serif;
    font-size: 0.9rem;
    font-weight: bold;
    margin-bottom: 10px;
}

.news-item-title {
    font-family: 'Comic Sans MS', sans-serif;
    font-size: 1.2rem;
    color: #f1c40f;
    margin-bottom: 15px;
    font-weight: bold;
}

.news-content {
    font-family: 'Comic Sans MS', sans-serif;
    line-height: 1.6;
    color: #e0e0e0;
    font-size: 0.95rem;
}

/* Адаптивность */
@media (max-width: 1400px) {
    .main-content {
        padding-top: 100px;
    }
}

@media (max-width: 768px) {
    .hero-title {
        font-size: 2.5rem;
    }
    
    .hero-subtitle {
        font-size: 1.2rem;
    }
    
    .content-container {
        padding: 0 15px;
    }
    
    .info-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .news-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .section-title, .news-title {
        font-size: 24px;
    }
    
    .info-card, .news-section {
        padding: 20px;
    }
    
    .apply-button {
        font-size: 1rem;
        padding: 12px 25px;
    }
}

@media (max-width: 480px) {
    .hero-title {
        font-size: 2rem;
    }
    
    .hero-subtitle {
        font-size: 1rem;
    }
    
    .section-title, .news-title {
        font-size: 20px;
    }
    
    .info-card, .news-section {
        padding: 15px;
    }
    
    .apply-button {
        width: 100%;
        justify-content: center;
        padding: 15px;
    }
}
</style>

<!-- Героический блок с видео -->
<section class="hero-section">
    <video class="hero-video" autoplay muted loop playsinline>
        <source src="1st.mp4" type="video/mp4">
        <!-- Если видео недоступно, показываем фоновое изображение -->
        <img src="images/bg7.png" alt="Background" style="width: 100%; height: 100%; object-fit: cover;">
    </video>
    
    <div class="hero-overlay"></div>
    
		<div class="hero-content">
				<div class="hero-logo">
						<img src="images/page_img/index/logo_top.png" class="hero-title-image">
				</div>
				<p class="hero-subtitle">Ванильный сервер для истинных ценителей Minecraft</p>
		</div>
    
    <!-- Волнистый разделитель -->
    <div class="wave-divider">
        <svg data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none">
            <path d="M985.66,92.83C906.67,72,823.78,31,743.84,14.19c-82.26-17.34-168.06-16.33-250.45.39-57.84,11.73-114,31.07-172,41.86A600.21,600.21,0,0,1,0,27.35V120H1200V95.8C1132.19,118.92,1055.71,111.31,985.66,92.83Z" class="shape-fill"></path>
        </svg>
    </div>
</section>

<!-- Основной контент -->
<div class="main-content">
    <div class="content-container">
        <!-- Блок с информацией о сервере -->
        <section class="server-info-section">
            <h2 class="section-title">Информация о сервере</h2>
            
            <div class="info-grid">
                <div class="info-card">
                    <div class="info-icon">🎮</div>
                    <h3 class="info-title">Как попасть на сервер</h3>
                    <p class="info-text">
                        Для входа на наш ванильный сервер вам необходимо подать заявку и получить одобрение администрации.
                    </p>
                    <div class="server-ip">play.project-echo.ru</div>
                </div>
                
                <div class="info-card">
                    <div class="info-icon">⚡</div>
                    <h3 class="info-title">Версия игры</h3>
                    <p class="info-text">
                        Сервер работает на последней стабильной версии Minecraft для лучшего игрового опыта.
                    </p>
                    <div class="server-version">1.21.8</div>
                </div>
                
                <div class="info-card">
                    <div class="info-icon">📝</div>
                    <h3 class="info-title">Подача заявки</h3>
                    <p class="info-text">
                        Подайте заявку через наш Telegram бот для быстрого рассмотрения администрацией.
                    </p>
                    <a href="https://t.me/projectecho_mc_bot" class="apply-button" target="_blank">
                        <svg class="telegram-icon" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/>
                        </svg>
                        Подать заявку
                    </a>
                </div>
            </div>
        </section>
        
        <!-- Блок новостей -->
        <section class="news-section">
            <h2 class="news-title">📰 Последние новости</h2>
            
            <div class="news-grid">
                <div class="news-item">
                    <div class="news-date">15 декабря 2024</div>
                    <h3 class="news-item-title">Открытие сервера Project Echo</h3>
                    <p class="news-content">
                        Мы рады объявить об официальном открытии нашего ванильного сервера! 
                        Приглашаем всех любителей классического Minecraft присоединиться к нашему сообществу.
                    </p>
                </div>
                
                <div class="news-item">
                    <div class="news-date">18 декабря 2024</div>
                    <h3 class="news-item-title">Обновление до версии 1.21.8</h3>
                    <p class="news-content">
                        Сервер успешно обновлен до последней версии Minecraft 1.21.8. 
                        Теперь доступны все новые блоки, предметы и механики!
                    </p>
                </div>
                
                <div class="news-item">
                    <div class="news-date">20 декабря 2024</div>
                    <h3 class="news-item-title">Новогодний ивент</h3>
                    <p class="news-content">
                        Готовится грандиозный новогодний ивент! Следите за обновлениями в нашем Telegram канале. 
                        Ожидайте призы, конкурсы и праздничные украшения на спавне.
                    </p>
                </div>
            </div>
        </section>
    </div>
</div>

<script type="text/javascript" src="/javascript/nonScroll.js"></script>

<?php 
// include_once 'includes/footer.php'; 
?>