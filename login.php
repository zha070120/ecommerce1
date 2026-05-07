<?php include 'config.php'; $error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];

    $result = $conn->query("SELECT * FROM cliente WHERE email='$email'");
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['nome'];
            
            $cart_check = $conn->query("SELECT id_carello FROM carello WHERE email='{$user['email']}'");
            if ($cart_check->num_rows == 0) {
                $conn->query("INSERT INTO carello (email) VALUES ('{$user['email']}')");
            }
            $cart = $conn->query("SELECT id_carello FROM carello WHERE email='{$user['email']}'")->fetch_assoc();
            $_SESSION['cart_id'] = $cart['id_carello'];
            
            header("Location: index.php");
            exit;
        } else {
            $error = "Password errata!";
        }
    } else {
        $error = "Email non trovata!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login - E-commerce</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>E-commerce Italia</h1>
        <nav>
            <a href="index.php">Home</a>
            <a href="register.php">Registrati</a>
        </nav>
    </header>
    <div class="container">
        <h2>Login Cliente</h2>
        <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
        <form method="POST" style="max-width: 500px;">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn">Accedi</button>
        </form>
    </div>
</body>
</html>
