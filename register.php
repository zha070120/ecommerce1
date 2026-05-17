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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrazione - E-commerce Doubao</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    
</head>
<body>
    <header>
        <div class="container">
            <h1><i class="fas fa-shopping-bag"></i> E-commerce Doubao</h1>
            <nav>
                <a href="index.php"><i class="fas fa-home"></i> Home</a>
                <a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
            </nav>
        </div>
    </header>

    <div class="container">
        <div class="register-wrap">
            <h2><i class="fas fa-user-plus"></i> Registrazione Cliente</h2>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?= $error ?>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?= $success ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required placeholder="Inserisci la tua email">
                </div>
                <div class="form-group">
                    <label for="nome">Nome</label>
                    <input type="text" id="nome" name="nome" required placeholder="Inserisci il tuo nome">
                </div>
                <div class="form-group">
                    <label for="cognome">Cognome</label>
                    <input type="text" id="cognome" name="cognome" required placeholder="Inserisci il tuo cognome">
                </div>
                <div class="form-group">
                    <label for="indirizzo">Indirizzo</label>
                    <input type="text" id="indirizzo" name="indirizzo" required placeholder="Inserisci il tuo indirizzo">
                </div>
                <div class="form-group">
                    <label for="cap">CAP</label>
                    <input type="text" id="cap" name="cap" required placeholder="Inserisci il tuo CAP">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required placeholder="Inserisci la tua password">
                </div>
                <button type="submit" class="btn btn-success submit-btn">
                    <i class="fas fa-user-plus"></i> Registrati
                </button>
            </form>
        </div>
    </div>
</body>
</html>