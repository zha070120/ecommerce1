<?php
include 'config.php';

// 安全登出：销毁session并重定向
$_SESSION = [];
session_destroy();

// 清除session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

redirect('index.php');
