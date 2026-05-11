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
    <title>Login Venditore</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<header>
    <h1>E-commerce</h1>
    <nav>
        <a href="index.php">Home</a>
        <a href="register_venditore.php">Registrati</a>
    </nav>
</header>
<div class="container" style="max-width:500px">
    <h2>Accesso Venditore</h2>
    <?php if ($errore): ?>
        <div class="alert alert-danger"><?= $errore ?></div>
    <?php endif; ?>
    <form method="post">
        <div class="form-group">
            <!-- 绑定Label：for 属性对应 input 的 id -->
            <label for="p_iva">Partita IVA</label>
            <input type="text" name="p_iva" id="p_iva" required>
        </div>
        <div class="form-group">
            <!-- 绑定Label -->
            <label for="password">Password</label>
            <input type="password" name="password" id="password" required>
        </div>
        <button class="btn btn-success" type="submit">Accedi</button>
    </form>
</div>
</body>
</html>