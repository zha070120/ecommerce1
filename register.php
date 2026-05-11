<?php include 'config.php';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 获取表单数据，移除手动转义
    $email = $_POST['email'];
    $nome = $_POST['nome'];
    $cognome = $_POST['cognome'];
    $indirizzo = $_POST['indirizzo'];
    $cap = $_POST['cap'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // 预处理语句：检查邮箱是否已注册（防SQL注入）
    $stmt_check = $conn->prepare("SELECT email FROM cliente WHERE email = ?");
    $stmt_check->bind_param("s", $email);
    $stmt_check->execute();
    $check = $stmt_check->get_result();

    if ($check->num_rows > 0) {
        $error = "Email già registrata!";
    } else {
        // 预处理语句：插入用户数据（防SQL注入）
        $stmt_insert = $conn->prepare("INSERT INTO cliente (email, nome, cognome, indirizzo, cap, password) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt_insert->bind_param("ssssss", $email, $nome, $cognome, $indirizzo, $cap, $password);
        
        if ($stmt_insert->execute()) {
            $success = "Registrazione completata! Puoi effettuare il login.";
        } else {
            $error = "Errore: " . $conn->error;
        }
        $stmt_insert->close();
    }
    $stmt_check->close();
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <title>Registrazione - E-commerce</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
        <h1>E-commerce Doubao</h1>
        <nav>
            <a href="index.php">Home</a>
            <a href="login.php">Login</a>
        </nav>
    </header>
    <div class="container">
        <h2>Registrazione Cliente</h2>
        <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
        
        <form method="POST" style="max-width: 500px;">
            <div class="form-group">
                <!-- Label 绑定 -->
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="nome">Nome</label>
                <input type="text" id="nome" name="nome" required>
            </div>
            <div class="form-group">
                <label for="cognome">Cognome</label>
                <input type="text" id="cognome" name="cognome" required>
            </div>
            <div class="form-group">
                <label for="indirizzo">Indirizzo</label>
                <input type="text" id="indirizzo" name="indirizzo" required>
            </div>
            <div class="form-group">
                <label for="cap">CAP</label>
                <input type="text" id="cap" name="cap" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-success">Registrati</button>
        </form>
    </div>
</body>
</html>