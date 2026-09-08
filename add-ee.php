<?php
include 'db.php';

// Проверка, авторизован ли пользователь и не находится ли он в демо-режиме
if (!isset($_SESSION["username"]) || $_SESSION["demo"] == 1) {
    header("Location: /home");
    exit();
}

$title = "Пополнение монет";
$_SESSION["prevpage"] = "add-ee";

include_once 'includes/header.php';
include_once 'includes/lowerheader.php';
?>

<div class="content">
	<div>
		<h1 class="textee">Здесь ничего нет :(</h1>
	</div>
</div>

<style>
.textee {
		color: #fff;
    justify-self: center;
		margin-top: 300px;
}
</style>