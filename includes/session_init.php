<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('__Host-PHPSESSID');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}
?>