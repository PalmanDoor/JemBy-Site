<?php
// Определяем разрешение экрана и множитель
$baseWidth = 2560;
$baseHeight = 1440;
$scaleFactor = 0.67;

// Функция для масштабирования значений
function scaleValue($value, $scaleFactor) {
    return round($value * $scaleFactor);
}

// Функция для генерации CSS
function generateCSS($scaleFactor) {
    $headerHeight = scaleValue(60, $scaleFactor);
    $footerHeight = scaleValue(60, $scaleFactor);
    $containerWidth = scaleValue(1366, $scaleFactor);
    $contentWidth = scaleValue(1366, $scaleFactor);
    
    return "
    body {
        font-family: Comic Sans MS, sans-serif;
        background-color: #1a1a1a;
        color: #ffffff;
        margin: 0;
        padding: 0;
        display: grid;
        grid-template-rows: 1fr auto; /* Основной контент и футер */
        min-height: 100vh;
        position: relative; /* Чтобы контейнер с огоньками был абсолютным относительно body */
    }

    .qhd-screen {
        background-color: #f0f0f0;
        font-size: 18px;
    }

    .header {
        background-color: #333;
        padding: 10px 0;
        position: fixed;
        width: 100%;
        top: 0;
        left: 0;
        z-index: 1000;
        height: $headerHeight px;
    }

    @font-face {
        font-family: 'Minecraft Rus';
        src: url('fonts/minecraft.ttf') format('truetype');
    }

    .logo {
        display: flex;
        align-items: center;
        text-decoration: none;
    }

    .logo-text {
        font-family: 'Minecraft Rus', cursive;
        color: #f1c40f;
        font-size: 24px;
        display: flex;
        font-weight: bold;
        text-decoration: none;
        flex-direction: column; /* Выстраиваем текст по вертикали */
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.7); /* Тень текста для большей выразительности */
    }

    .navigation {
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: relative;
    }

    .nav, .user-info, .user-actions {
        list-style-type: none;
        margin: 0;
        padding: 0;
        display: flex;
        gap: 15px;
        align-items: center;
    }

    .nav li, .user-info li, .user-actions li {
        display: inline;
    }

    .nav a, .user-info a, .user-actions a {
        text-decoration: none;
        color: #ffffff;
        padding: 10px 20px;
        transition: background-color 0.3s ease;
    }

    .nav a:hover, .user-info a:hover, .user-actions a:hover {
        background-color: #575757;
    }

    .user-avatar {
        vertical-align: middle;
        margin-right: 5px;
    }

    .admin {
        color: #ff0000;
    }

    .logout {
        color: #ff0000;
    }

    .shop-container {
        display: flex;
        flex-wrap: wrap;
        border: 1px solid #444;
        border-radius: 10px;
        margin-left: 10px;
        margin-right: 10px;
        background-color: #2e2e2e;
        justify-content: center;
        padding: 10px;
        min-width: " . scaleValue(920, $scaleFactor) . "px;
    }

    .content h2 {
        font-family: Comic Sans MS, sans-serif;
        font-size: " . scaleValue(29, $scaleFactor) . "px;
        margin-bottom: 20px;
        color: #e0e0e0;
    }

    .container {
        min-width: $containerWidth px;
        max-width: $containerWidth px;
        margin: 60px auto;
        padding: 20px;
        background-color: #1e1e1e;
        border-radius: 8px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
        position: relative;
    }

    .content {
        background-color: #2a2a2a;
        min-width: $contentWidth px;
        max-width: $contentWidth px;
        margin: 60px auto;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
        position: relative;
    }

    h2, h3, p, ul, li, a {
        color: #ffffff;
    }

    .dz-button {
        color: #000;
    }

    .footer {
        background-color: #1c1c1c;
        color: #e0e0e0;
        padding: 20px 0;
        text-align: center;
        border-top: 1px solid #333;
        box-shadow: 0 -2px 5px rgba(0, 0, 0, 0.7);
        margin-top: auto;
        flex-shrink: 0;
        position: relative;
        height: $footerHeight px;
    }

    .footer .image-link {
        margin-bottom: 15px;
    }

    .footer-icon {
        width: 32px;
        height: 32px;
        margin: 0 10px;
        vertical-align: middle;
        transition: opacity 0.3s;
    }

    .footer-icon:hover {
        opacity: 0.7;
    }

    .footer-text {
        font-size: 14px;
        line-height: 1.5;
    }

    .footer-text a {
        color: #1e90ff;
        text-decoration: none;
    }

    .footer-text a:hover {
        text-decoration: underline;
    }

    .footer-text span {
        font-weight: bold;
        color: #4caf50;
    }

    .player-image {
        position: absolute;
        bottom: 60px;
        right: 30px;
        width: 150px;
        height: auto;
    }

    .player2-image {
        position: absolute;
        bottom: 60px;
        left: 30px;
        width: 250px;
        height: auto;
        z-index: 10;
    }

    .user-profile {
        display: flex;
        align-items: center;
        justify-content: space-between;
        background-color: #333;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }

    .user-avatar {
        border-radius: 50%;
        width: 64px;
        height: 64px;
        margin-right: 20px;
    }

    .user-name {
        font-size: 18px;
        color: #fff;
        margin-right: 20px;
    }

    .user-coins {
        font-size: 16px;
        color: #ccc;
    }

    .user-profile-details {
        display: none;
        position: absolute;
        background-color: #444;
        padding: 15px;
        border-radius: 10px;
        color: #fff;
        top: 100%;
        left: 0;
        width: 220px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.3);
        z-index: 10;
    }

    .user-profile:hover .user-profile-details {
        display: block;
    }

    .usercp-link {
        display: block;
        margin-bottom: 15px;
        color: #1e90ff;
        text-decoration: none;
    }

    .usercp-link:hover {
        text-decoration: underline;
    }

    .premium-link {
        display: block;
        margin-top: 15px;
        padding: 15px;
        background-color: #1e90ff;
        color: #fff;
        text-decoration: none;
        border-radius: 10px;
        text-align: center;
    }

    .premium-link:hover {
        background-color: #1c7ccd;
    }

    .usercp-title {
        font-size: 26px;
        color: #fff;
        margin-bottom: 25px;
    }

    .usercp-password-section, .usercp-prefix-section {
        margin-bottom: 50px;
    }

    .usercp-form {
        display: flex;
        flex-direction: column;
    }

    .usercp-label {
        color: #fff;
        margin-bottom: 10px;
    }

    .usercp-input {
        margin-bottom: 15px;
        padding: 10px;
        border: 1px solid #555;
        border-radius: 10px;
        background-color: #333;
        color: #fff;
    }

    .usercp-submit {
        padding: 12px;
        border: none;
        border-radius: 10px;
        background-color: #35404b;
        color: #fff;
        cursor: pointer;
        margin: 15px;
    }

    .usercp-submit:hover {
        background-color: #25403b;
    }

    .usercp-message {
        padding: 12px;
        border-radius: 10px;
        margin-bottom: 15px;
    }

    .usercp-message.success {
        background-color: #28a745;
        color: #fff;
    }

    .usercp-message.error {
        background-color: #dc3545;
        color: #fff;
    }

    .usercp-color-picker {
        margin-bottom: 15px;
    }

    .color-palette {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        justify-content: center;
        transition: transform 0.3s ease;
    }

    .color-palette:hover {
        transform: scale(1.05);
    }

    .color {
        width: 30px;
        height: 30px;
        cursor: pointer;
        border-radius: 50%;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .color:hover {
        transform: scale(1.2);
        box-shadow: 0 0 10px 2px rgba(255, 255, 255, 0.5);
    }

    .prefix-preview {
        margin-top: 15px;
        padding: 15px;
        border: 1px solid #555;
        border-radius: 10px;
        background-color: #444;
        color: #fff;
        text-align: center;
    }

    #prefixPreview {
        color: #FFFFFF;
    }

    .premium-user {
        color: #fff;
        font-weight: bold;
        position: relative;
        animation: flickerAnimation 1.5s infinite;
    }

    @keyframes flickerAnimation {
        0%   { text-shadow: 0 0 5px #ff0, 0 0 10px #ff0, 0 0 20px #ff0, 0 0 30px #ff0; }
        50%  { text-shadow: 0 0 10px #ff0, 0 0 20px #ff0, 0 0 30px #ff0, 0 0 40px #ff0; }
        100% { text-shadow: 0 0 5px #ff0, 0 0 10px #ff0, 0 0 20px #ff0, 0 0 30px #ff0; }
    }

    .premium-user::after {
        content: '';
        position: absolute;
        top: -10px;
        left: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
        background: url('path/to/fire-animation.gif') center center no-repeat;
        background-size: cover;
        z-index: -1;
        opacity: 0.8;
        animation: particleAnimation 1s infinite;
    }

    @keyframes particleAnimation {
        0%   { transform: translateY(0); }
        50%  { transform: translateY(-10px); }
        100% { transform: translateY(0); }
    }

    .user-coins {
        color: inherit;
        font-weight: normal;
    }
    ";
}

// Устанавливаем заголовок для генерации CSS
header("Content-type: text/css");
echo generateCSS($scaleFactor);
?>