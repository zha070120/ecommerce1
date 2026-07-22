<?php
// ===================== 调试开关 =====================
define('DEBUG', true); // 开发环境设true，生产环境设false

if (DEBUG) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

// ===================== Session 安全加固 =====================
ini_set('session.cookie_httponly', 1);    // 禁止JS读取session cookie
ini_set('session.cookie_samesite', 'Lax'); // 防CSRF基础
ini_set('session.use_only_cookies', 1);    // 仅用cookie传递session id
ini_set('session.use_strict_mode', 1);     // 禁止未初始化的session

session_start();

// ===================== 数据库连接 =====================
$host = 'localhost';
$dbname = 'ecommerce_italia';
$username = 'root';
$password = '';

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    if (DEBUG) {
        die("Connessione fallita: " . $conn->connect_error);
    } else {
        die("Errore di sistema. Riprova più tardi.");
    }
}

$conn->set_charset("utf8mb4");

// ===================== XSS 转义快捷函数 =====================
/**
 * HTML安全转义，防XSS
 * @param mixed $str
 * @return string
 */
function e($str)
{
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

// ===================== CSRF 防护 =====================
/**
 * 生成并存储CSRF Token
 * @return string
 */
function csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * 输出CSRF hidden input字段
 */
function csrf_field()
{
    echo '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * 校验CSRF Token，不通过直接终止
 */
function verify_csrf()
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            http_response_code(403);
            die("Richiesta non valida (CSRF).");
        }
    }
}

// ===================== 登录状态检测函数 =====================
function venditore_loggato()
{
    return isset($_SESSION['venditore_piva']) && !empty($_SESSION['venditore_piva']);
}

function richiedi_login_venditore()
{
    if (!venditore_loggato()) {
        header("Location: login_venditore.php");
        exit;
    }
}

function cliente_loggato()
{
    return isset($_SESSION['user_email']) && !empty($_SESSION['user_email']);
}

function richiedi_login_cliente()
{
    if (!cliente_loggato()) {
        header("Location: login.php");
        exit;
    }
}

// ===================== 工具函数 =====================
/**
 * 安全重定向
 */
function redirect($url)
{
    header("Location: $url");
    exit;
}

/**
 * 格式化欧元价格
 */
function fmt_euro($val)
{
    return '€ ' . number_format((float)$val, 2, ',', '.');
}
