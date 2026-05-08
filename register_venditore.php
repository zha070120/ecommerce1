<?php
session_start();

$host = 'localhost';
$dbname = 'ecommerce_italia';
$username = 'root';
$password = '';

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) die("Connessione fallita");

$conn->set_charset("utf8mb4");

$errore = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $p_iva          = trim($_POST['p_iva']);
    $ragione_sociale= trim($_POST['ragione_sociale']);
    $indirizzo      = trim($_POST['indirizzo']);
    $cap            = trim($_POST['cap']);
    $password       = trim($_POST['password']);

    if(empty($p_iva) || empty($ragione_sociale) || empty($password)){
        $errore = "Compila tutti i campi obbligatori!";
    } else {
        // 检查 VAT 是否已存在
        $check = $conn->query("SELECT p_iva FROM venditore WHERE p_iva = '$p_iva'");
        if($check->num_rows > 0){
            $errore = "Partita IVA già registrata!";
        } else {
            // 密码加密
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            $sql = "INSERT INTO venditore (p_iva, ragione_sociale, indirizzo, cap, password)
                    VALUES ('$p_iva', '$ragione_sociale', '$indirizzo', '$cap', '$password_hash')";

            if($conn->query($sql)){
                $success = "Registrazione completata! Ora puoi accedere.";
            } else {
                $errore = "Errore: ".$conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Registrazione Venditore</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header>
    <h1>E-commerce Doubao</h1>
    <nav>
        <a href="index.php">Home</a>
        <a href="login_venditore.php">Accedi</a>
    </nav>
</header>

<div class="container" style="max-width:600px">
    <h2>Registrazione Venditore</h2>

    <?php if($errore): ?>
        <div class="alert alert-danger"><?= $errore ?></div>
    <?php endif; ?>

    <?php if($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>Partita IVA *</label>
            <input type="text" name="p_iva" maxlength="13" required>
        </div>

        <div class="form-group">
            <label>Ragione Sociale *</label>
            <input type="text" name="ragione_sociale" required>
        </div>

        <div class="form-group">
            <label>Indirizzo</label>
            <input type="text" name="indirizzo">
        </div>

        <div class="form-group">
            <label>CAP</label>
            <input type="text" name="cap">
        </div>

        <div class="form-group">
            <label>Password *</label>
            <input type="password" name="password" required>
        </div>

        <button type="submit" class="btn btn-success">Registrati</button>
    </form>
</div>

</body>
</html>