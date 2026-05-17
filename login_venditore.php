<?php 
include 'config.php';
$errore = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $p_iva = trim($_POST['p_iva']);
    $password = trim($_POST['password']);

    // 预处理语句查询 防SQL注入
    $sql = "SELECT * FROM venditore WHERE p_iva = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $p_iva);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        $vend = $res->fetch_assoc();
        // 验证密码
        if (password_verify($password, $vend['password'])) {
            $_SESSION['venditore_piva'] = $vend['p_iva'];
            $_SESSION['venditore_ragione_sociale'] = $vend['ragione_sociale'];
            header("Location: dashboard_venditore.php");
            exit;
        }
    }
    // 关闭预处理语句 释放资源
    $stmt->close();
    // 统一错误提示
    $errore = "Credenziali errate!";
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Venditore - E-commerce Doubao</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    
</head>
<body>
    <header>
        <div class="container">
            <h1><i class="fas fa-shopping-bag"></i> E-commerce Doubao</h1>
            <nav>
                <a href="index.php"><i class="fas fa-home"></i> Home</a>
                <a href="register_venditore.php"><i class="fas fa-user-plus"></i> Registrati</a>
            </nav>
        </div>
    </header>

    <div class="container">
        <div class="login-wrap">
            <h2><i class="fas fa-store"></i> Accesso Venditore</h2>

            <?php if ($errore): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?= $errore ?>
                </div>
            <?php endif; ?>

            <form method="post">
                <div class="form-group">
                    <label for="p_iva">Partita IVA</label>
                    <input type="text" name="p_iva" id="p_iva" required placeholder="Inserisci la tua Partita IVA">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" required placeholder="Inserisci la tua password">
                </div>
                <button class="btn btn-success submit-btn" type="submit">
                    <i class="fas fa-sign-in-alt"></i> Accedi
                </button>
            </form>
        </div>
    </div>
</body>
</html>