<?php
session_start();

$host = 'localhost';
$dbname = 'ecommerce_italia';
$username = 'root';
$password = '';

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

function venditore_loggato() {
    return isset($_SESSION['venditore_piva']) && !empty($_SESSION['venditore_piva']);
}
function richiedi_login_venditore() {
    if (!venditore_loggato()) {
        header("Location: login_venditore.php");
        exit;
    }
}
function cliente_loggato() {
    return isset($_SESSION['user_email']) && !empty($_SESSION['user_email']);
}
function richiedi_login_cliente() {
    if (!cliente_loggato()) {
        header("Location: login.php");
        exit;
    }
}
?>
