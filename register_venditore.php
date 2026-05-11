<?php
include 'config.php';

$errore = '';
$success = '';

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 接收并格式化数据
    $p_iva          = trim($_POST['p_iva']);
    $ragione_sociale= trim($_POST['ragione_sociale']);
    $indirizzo      = trim($_POST['indirizzo']);
    $cap            = trim($_POST['cap']);
    $password       = trim($_POST['password']);

    // 必填项验证
    if (empty($p_iva) || empty($ragione_sociale) || empty($password)) {
        $errore = "Compila tutti i campi obbligatori!";
    } else {
        // 检查Partita IVA已存在
        $check_stmt = $conn->prepare("SELECT p_iva FROM venditore WHERE p_iva = ?");
        $check_stmt->bind_param("s", $p_iva);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $errore = "Partita IVA già registrata!";
        } else {
            // 密码加密
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            // 插入商家数据
            $insert_stmt = $conn->prepare("INSERT INTO venditore (p_iva, ragione_sociale, indirizzo, cap, password) VALUES (?, ?, ?, ?, ?)");
            $insert_stmt->bind_param("sssss", $p_iva, $ragione_sociale, $indirizzo, $cap, $password_hash);
            
            if ($insert_stmt->execute()) {
                $success = "Registrazione completata! Ora puoi accedere.";
            } else {
                $errore = "Errore: " . $insert_stmt->error;
            }
            $insert_stmt->close();
        }
        $check_stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Registrazione Venditore</title>
    <link rel="stylesheet" href="css/style.css">
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
            <label for="p_iva">Partita IVA *</label>
            <input type="text" id="p_iva" name="p_iva" maxlength="13" required>
        </div>

        <div class="form-group">
            <label for="ragione_sociale">Ragione Sociale *</label>
            <input type="text" id="ragione_sociale" name="ragione_sociale" required>
        </div>

        <div class="form-group">
            <label for="indirizzo">Indirizzo</label>
            <input type="text" id="indirizzo" name="indirizzo">
        </div>

        <div class="form-group">
            <label for="cap">CAP</label>
            <input type="text" id="cap" name="cap">
        </div>

        <div class="form-group">
            <label for="password">Password *</label>
            <input type="password" id="password" name="password" required>
        </div>

        <button type="submit" class="btn btn-success">Registrati</button>
    </form>
</div>
</body>
</html>