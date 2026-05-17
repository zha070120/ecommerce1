<?php include 'config.php'; $error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // 预处理语句查询用户，防SQL注入
    $stmt = $conn->prepare("SELECT * FROM cliente WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['nome'];
            
            // 购物车查询预处理
            $stmt_cart = $conn->prepare("SELECT id_carello FROM carello WHERE email = ?");
            $stmt_cart->bind_param("s", $user['email']);
            $stmt_cart->execute();
            $cart_check = $stmt_cart->get_result();

            if ($cart_check->num_rows == 0) {
                // 购物车插入预处理
                $stmt_insert = $conn->prepare("INSERT INTO carello (email) VALUES (?)");
                $stmt_insert->bind_param("s", $user['email']);
                $stmt_insert->execute();
                $stmt_insert->close();
            }
            $stmt_cart->execute();
            $cart = $stmt_cart->get_result()->fetch_assoc();
            $_SESSION['cart_id'] = $cart['id_carello'];
            $stmt_cart->close();
            
            header("Location: index.php");
            exit;
        } else {
            $error = "Password errata!";
        }
    } else {
        $error = "Email non trovata!";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - E-commerce Doubao</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    
</head>
<body>
    <header>
        <div class="container">
            <h1><i class="fas fa-shopping-bag"></i> E-commerce Doubao</h1>
            <nav>
                <a href="index.php"><i class="fas fa-home"></i> Home</a>
                <a href="register.php"><i class="fas fa-user-plus"></i> Registrati</a>
            </nav>
        </div>
    </header>

    <div class="container">
        <div class="login-wrap">
            <h2><i class="fas fa-user"></i> Login Cliente</h2>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?= $error ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" name="email" id="email" required placeholder="Inserisci la tua email">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" required placeholder="Inserisci la tua password">
                </div>
                <button type="submit" class="btn submit-btn">
                    <i class="fas fa-sign-in-alt"></i> Accedi
                </button>
            </form>
        </div>
    </div>
</body>
</html>