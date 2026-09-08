<?php include_once 'includes/header.php'; ?>
<div class="footer">
    <!-- Иконки с ссылками -->
    <div class="footer-icons">
        <a href="https://www.youtube.com/@Sonny-5775" aria-label="YouTube"><img src="/images/icons/youtube.png" alt="YouTube" class="footer-icon" draggable="false"></a>
        <a href="https://github.com/PalmanDoor" aria-label="GitHub"><img src="/images/icons/github.png" alt="GitHub" class="footer-icon" draggable="false"></a>
    </div>
    
    <!-- Состояние сервера и информация -->
    <p class="footer-text">
        Project Echo - <?php echo GetServerStatus("45.93.200.203", "25575"); ?>
    </p>

    <!-- Картинка второго игрока -->
    <img src="/images/input/timon3w.png" alt="Player 2" class="player2-image" id="footerPlayer2" draggable="false">

    <!-- Картинка игрока -->
    <img src="/images/input/player.png" alt="Player" class="player-image" id="footerPlayer" draggable="false">
</div>
</body>
</html>