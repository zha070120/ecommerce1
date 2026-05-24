<?php
// 开启会话功能，用于存储用户登录信息、身份标识、购物车数据
session_start();

// ========== 数据库连接基础配置参数 ==========
$host = 'localhost';                // 数据库服务器地址，本地数据库固定地址
$dbname = 'ecommerce_italia';       // 项目对应的数据库名称
$username = 'root';                 // MySQL数据库登录用户名
$password = '';                     // 数据库登录密码，本地环境默认无密码

// 创建MySQL数据库连接实例
$conn = new mysqli($host, $username, $password, $dbname);

// 判断数据库连接是否异常，连接失败则终止程序并输出错误信息
if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}

// 设置数据库字符编码为utf8mb4，兼容所有文字、特殊符号与图片表情
$conn->set_charset("utf8mb4");

/**
 * 检测卖家是否处于登录状态
 * @return bool true=已登录  false=未登录
 */
function venditore_loggato() {
    // 判断会话中是否存在卖家税号标识且不为空
    return isset($_SESSION['venditore_piva']) && !empty($_SESSION['venditore_piva']);
}

/**
 * 卖家权限校验函数
 * 未登录状态自动跳转至卖家登录页面，禁止访问后台页面
 */
function richiedi_login_venditore() {
    // 调用登录检测函数，未登录则执行跳转退出
    if (!venditore_loggato()) {
        header("Location: login_venditore.php");
        exit;
    }
}

/**
 * 检测普通客户是否处于登录状态
 * @return bool true=已登录  false=未登录
 */
function cliente_loggato() {
    // 判断会话中是否存在客户邮箱标识且不为空
    return isset($_SESSION['user_email']) && !empty($_SESSION['user_email']);
}

/**
 * 客户权限校验函数
 * 未登录状态自动跳转至客户登录页面，禁止访问会员专属页面
 */
function richiedi_login_cliente() {
    // 调用登录检测函数，未登录则执行跳转退出
    if (!cliente_loggato()) {
        header("Location: login.php");
        exit;
    }
}
?>