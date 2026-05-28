<?php
// 开启会话
session_start();
session_unset();
session_destroy();

// 跳转到首页
header("Location: index.php");
exit;
