<?php
include 'config.php';
verify_csrf();

$page_title = 'Registrazione';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $cognome = trim($_POST['cognome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    // 校验
    if (!$nome || !$cognome || !$email || !$password) {
        $error = 'Completa tutti i campi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email non valida.';
    } elseif (strlen($password) < 6) {
        $error = 'La password deve essere di almeno 6 caratteri.';
    } elseif ($password !== $password_confirm) {
        $error = 'Le password non coincidono.';
    } else {
        // 检查邮箱是否已存在
        $stmt_check = $conn->prepare("SELECT email FROM cliente WHERE email = ?");
        $stmt_check->bind_param("s", $email);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows > 0) {
            $error = 'Email già registrata.';
        } else {
            // 哈希密码
            $hashed = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("INSERT INTO cliente (nome, cognome, email, password) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $nome, $cognome, $email, $hashed);

            if ($stmt->execute()) {
                $success = "Registrazione completata! Puoi ora effettuare il login.";
            } else {
                $error = 'Errore durante la registrazione.';
            }
            $stmt->close();
        }
        $stmt_check->close();
    }
}

include 'includes/header.php';
?>

<div class="login-wrap">
    <h2><i class="fas fa-user-plus"></i> Registrazione Cliente</h2>

    <?php if ($error): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?= e($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?= e($success) ?>
            <br><a href="login.php">Vai al login</a>
        </div>
    <?php else: ?>
        <form method="POST">
            <?php csrf_field(); ?>
            <div class="form-row">
                <div class="form-group">
                    <label for="nome">Nome</label>
                    <input type="text" name="nome" id="nome" required value="<?= isset($_POST['nome']) ? e($_POST['nome']) : '' ?>">
                </div>
                <div class="form-group">
                    <label for="cognome">Cognome</label>
                    <input type="text" name="cognome" id="cognome" required value="<?= isset($_POST['cognome']) ? e($_POST['cognome']) : '' ?>">
                </div>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" required value="<?= isset($_POST['email']) ? e($_POST['email']) : '' ?>">
            </div>
            <div class="form-group">
                <label for="password">Password (min. 6 caratteri)</label>
                <input type="password" name="password" id="password" required>
            </div>
            <div class="form-group">
                <label for="password_confirm">Conferma Password</label>
                <input type="password" name="password_confirm" id="password_confirm" required>
            </div>
            <button type="submit" class="btn submit-btn">
                <i class="fas fa-user-plus"></i> Registrati
            </button>
        </form>

        <p class="login-footer">
            Hai già un account? <a href="login.php">Accedi</a>
        </p>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
