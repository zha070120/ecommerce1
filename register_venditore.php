<?php
include 'config.php';
verify_csrf();

$page_title = 'Registrazione Venditore';
$errore = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $p_iva          = trim($_POST['p_iva'] ?? '');
    $ragione_sociale = trim($_POST['ragione_sociale'] ?? '');
    $indirizzo      = trim($_POST['indirizzo'] ?? '');
    $cap            = trim($_POST['cap'] ?? '');
    $password       = trim($_POST['password'] ?? '');
    $password_confirm = trim($_POST['password_confirm'] ?? '');

    if (empty($p_iva) || empty($ragione_sociale) || empty($password)) {
        $errore = "Compila tutti i campi obbligatori!";
    } elseif (strlen($password) < 6) {
        $errore = "La password deve essere di almeno 6 caratteri.";
    } elseif ($password !== $password_confirm) {
        $errore = "Le password non coincidono.";
    } else {
        $check_stmt = $conn->prepare("SELECT p_iva FROM venditore WHERE p_iva = ?");
        $check_stmt->bind_param("s", $p_iva);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $errore = "Partita IVA già registrata!";
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
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

include 'includes/header.php';
?>

<div class="register-wrap">
    <h2><i class="fas fa-store"></i> Registrazione Venditore</h2>

    <?php if ($errore): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?= e($errore) ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?= e($success) ?>
            <br><a href="login_venditore.php">Vai al login</a>
        </div>
    <?php else: ?>
        <form method="POST">
            <?php csrf_field(); ?>
            <div class="form-group">
                <label for="p_iva">Partita IVA <span class="required">*</span></label>
                <input type="text" id="p_iva" name="p_iva" maxlength="13" required 
                       value="<?= isset($_POST['p_iva']) ? e($_POST['p_iva']) : '' ?>"
                       placeholder="Inserisci la tua Partita IVA">
            </div>

            <div class="form-group">
                <label for="ragione_sociale">Ragione Sociale <span class="required">*</span></label>
                <input type="text" id="ragione_sociale" name="ragione_sociale" required
                       value="<?= isset($_POST['ragione_sociale']) ? e($_POST['ragione_sociale']) : '' ?>"
                       placeholder="Inserisci la tua Ragione Sociale">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="indirizzo">Indirizzo</label>
                    <input type="text" id="indirizzo" name="indirizzo"
                           value="<?= isset($_POST['indirizzo']) ? e($_POST['indirizzo']) : '' ?>"
                           placeholder="Inserisci il tuo indirizzo">
                </div>
                <div class="form-group">
                    <label for="cap">CAP</label>
                    <input type="text" id="cap" name="cap" maxlength="5"
                           value="<?= isset($_POST['cap']) ? e($_POST['cap']) : '' ?>"
                           placeholder="Inserisci il tuo CAP">
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password <span class="required">*</span> (min. 6 caratteri)</label>
                <input type="password" id="password" name="password" required placeholder="Inserisci la tua password">
            </div>

            <div class="form-group">
                <label for="password_confirm">Conferma Password <span class="required">*</span></label>
                <input type="password" id="password_confirm" name="password_confirm" required placeholder="Ripeti la password">
            </div>

            <button type="submit" class="btn btn-success submit-btn">
                <i class="fas fa-user-plus"></i> Registrati
            </button>
        </form>

        <p class="login-footer">
            Hai già un account? <a href="login_venditore.php">Accedi</a>
        </p>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
