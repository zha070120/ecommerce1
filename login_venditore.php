<?php
include 'config.php';
verify_csrf();

$page_title = 'Login Venditore';
$errore = "";

// 防暴力破解
$max_attempts = 5;
$lockout_time = 300;
if (!isset($_SESSION['vendor_login_attempts'])) {
    $_SESSION['vendor_login_attempts'] = 0;
    $_SESSION['vendor_lockout'] = 0;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($_SESSION['vendor_lockout'] > time()) {
        $remaining = ceil(($_SESSION['vendor_lockout'] - time()) / 60);
        $errore = "Troppi tentativi. Riprova tra $remaining minuti.";
    } else {
        $p_iva = trim($_POST['p_iva']);
        $password = trim($_POST['password']);

        $stmt = $conn->prepare("SELECT p_iva, ragione_sociale, password FROM venditore WHERE p_iva = ?");
        $stmt->bind_param("s", $p_iva);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows > 0) {
            $vend = $res->fetch_assoc();
            if (password_verify($password, $vend['password'])) {
                $_SESSION['vendor_login_attempts'] = 0;
                $_SESSION['vendor_lockout'] = 0;
                session_regenerate_id(true);

                $_SESSION['venditore_piva'] = $vend['p_iva'];
                $_SESSION['venditore_ragione_sociale'] = $vend['ragione_sociale'];
                redirect('dashboard_venditore.php');
            }
        }
        $stmt->close();

        $_SESSION['vendor_login_attempts']++;
        if ($_SESSION['vendor_login_attempts'] >= $max_attempts) {
            $_SESSION['vendor_lockout'] = time() + $lockout_time;
            $errore = "Troppi tentativi falliti. Account bloccato per 5 minuti.";
        } else {
            $errore = "Credenziali errate!";
        }
    }
}

include 'includes/header.php';
?>

<div class="login-wrap">
    <h2><i class="fas fa-store"></i> Accesso Venditore</h2>

    <?php if ($errore): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?= e($errore) ?>
        </div>
    <?php endif; ?>

    <form method="post">
        <?php csrf_field(); ?>
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

    <p class="login-footer">
        Non hai un account? <a href="register_venditore.php">Registra il tuo negozio</a>
    </p>
</div>

<?php include 'includes/footer.php'; ?>
