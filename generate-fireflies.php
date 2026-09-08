<?php
header("Content-type: text/css; charset: UTF-8");
?>

/* Фоновые огоньки */
@keyframes fireflies {
    0% {
        transform: translate(0, 0);
        opacity: 0;
    }
    50% {
        opacity: 1;
    }
    100% {
        transform: translate(calc(100vw * var(--x-direction)), calc(-100vh * var(--y-direction)));
        opacity: 0;
    }
}

/* Энергия черной дыры */
@keyframes black-hole-energy {
    0% {
        transform: scale(1);
        opacity: 0;
    }
    50% {
        transform: scale(2);
        opacity: 1;
    }
    100% {
        transform: scale(1);
        opacity: 0;
    }
}

.fireflies-container {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none; /* Огоньки не мешают кликам по другим элементам */
    overflow: hidden;
    z-index: 0; /* Убедитесь, что фон находится позади всего остального контента */
    background: radial-gradient(circle, rgba(255, 255, 255, 0.1), rgba(0, 0, 0, 0.2));
}

/* Черные дыры */
.black-hole {
    position: absolute;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(0, 0, 0, 0.8) 0%, rgba(0, 0, 0, 0) 60%);
    box-shadow: 0 0 20px rgba(0, 0, 0, 0.7);
    pointer-events: none;
    animation: black-hole-energy linear infinite;
}

/* Генерация черных дыр */
<?php for ($i = 1; $i <= 3; $i++): ?>
.black-hole:nth-child(<?php echo $i; ?>) {
    width: <?php echo rand(40, 60); ?>px;
    height: <?php echo rand(40, 60); ?>px;
    top: <?php echo rand(0, 100); ?>%;
    left: <?php echo rand(0, 100); ?>%;
    --x-direction: <?php echo rand(-2, 2) / 10; ?>;
    --y-direction: <?php echo rand(-2, 2) / 10; ?>;
    animation-duration: <?php echo rand(5, 10); ?>s;
    animation-delay: <?php echo rand(0, 5); ?>s;
    background: radial-gradient(circle, rgba(0, 0, 0, 0.8) 0%, rgba(0, 0, 0, 0.4) 70%);
    box-shadow: 0 0 20px rgba(0, 0, 0, 0.8);
}
<?php endfor; ?>

/* Огоньки */
.firefly {
    position: absolute;
    background: radial-gradient(circle, rgba(255, 255, 255, 0.7) 0%, rgba(255, 255, 255, 0) 80%);
    border-radius: 50%;
    pointer-events: none;
    animation: fireflies linear infinite;
    opacity: 0;
}

/* Генерация множества огоньков */
<?php for ($i = 1; $i <= 30; $i++): ?>
.firefly:nth-child(<?php echo $i + 3; ?>) {
    width: <?php echo rand(7, 14); ?>px;
    height: <?php echo rand(7, 14); ?>px;
    top: <?php echo rand(0, 100); ?>%;
    left: <?php echo rand(0, 100); ?>%;
    --x-direction: <?php echo rand(3, 7) / 10; ?>;
    --y-direction: <?php echo rand(3, 7) / 10; ?>;
    animation-duration: <?php echo rand(5, 10); ?>s;
    animation-delay: <?php echo rand(0, 5); ?>s;
    background: radial-gradient(circle, rgba(<?php echo rand(180, 255); ?>, <?php echo rand(150, 255); ?>, <?php echo rand(180, 255); ?>, 0.7) 0%, rgba(<?php echo rand(140, 200); ?>, <?php echo rand(100, 160); ?>, <?php echo rand(140, 200); ?>, 0) 80%);
    border-radius: 50%;
    pointer-events: none;
    animation: fireflies linear infinite;
    opacity: 0;
}
<?php endfor; ?>
