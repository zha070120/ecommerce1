<?php
include 'config.php';
verify_csrf();

$page_title = 'Login Cliente';
$error = '';

// 登录失败次数限制（防暴力破解）
$max_attempts = 5;
$lockout_time = 300; // 5分钟

if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['login_lockout'] = 0;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 检查是否被锁定
    if ($_SESSION['login_lockout'] > time()) {
        $remaining = ceil(($_SESSION['login_lockout'] - time()) / 60);
        $error = "Troppi tentativi. Riprova tra $remaining minuti.";
    } else {
        $email = trim($_POST['email']);
        $password = $_POST['password'];

        $stmt = $conn->prepare("SELECT email, nome, password FROM cliente WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                // 登录成功：重置失败计数 + 刷新session id
                $_SESSION['login_attempts'] = 0;
                $_SESSION['login_lockout'] = 0;
                session_regenerate_id(true);

                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_name'] = $user['nome'];

                // 获取或创建购物车
                $stmt_cart = $conn->prepare("SELECT id_carello FROM carello WHERE email = ?");
                $stmt_cart->bind_param("s", $user['email']);
                $stmt_cart->execute();
                $cart_check = $stmt_cart->get_result();

                if ($cart_check->num_rows == 0) {
                    $stmt_insert = $conn->prepare("INSERT INTO carello (email) VALUES (?)");
                    $stmt_insert->bind_param("s", $user['email']);
                    $stmt_insert->execute();
                    $stmt_insert->close();
                }
                $stmt_cart->execute();
                $cart = $stmt_cart->get_result()->fetch_assoc();
                $_SESSION['cart_id'] = $cart['id_carello'];
                $stmt_cart->close();

                redirect('index.php');
            } else {
                $error = "Password errata!";
                $_SESSION['login_attempts']++;
            }
        } else {
            $error = "Email non trovata!";
            $_SESSION['login_attempts']++;
        }

        // 达到最大尝试次数，锁定
        if ($_SESSION['login_attempts'] >= $max_attempts) {
            $_SESSION['login_lockout'] = time() + $lockout_time;
            $error = "Troppi tentativi falliti. Account bloccato per 5 minuti.";
        }

        $stmt->close();
    }
}

include 'includes/header.php';
?>

<div class="login-wrap">
    <h2><i class="fas fa-user"></i> Login Cliente</h2>

    <?php if ($error): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?= e($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <?php csrf_field(); ?>
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

    <p class="login-footer">
        Non hai un account? <a href="register.php">Registrati</a>
    </p>
</div>

<?php include 'includes/footer.php'; ?>
