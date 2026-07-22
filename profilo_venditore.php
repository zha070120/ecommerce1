<?php
include 'config.php';
richiedi_login_venditore();
verify_csrf();

$page_title = 'Profilo Venditore';
$p_iva_venditore = $_SESSION['venditore_piva'] ?? '';
$messaggio = '';
$errore = '';

function caricaProfilo($conn, $p_iva)
{
    $stmt = $conn->prepare("SELECT p_iva, ragione_sociale, indirizzo, cap, password FROM venditore WHERE p_iva = ?");
    $stmt->bind_param("s", $p_iva);
    $stmt->execute();
    $profilo = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $profilo;
}

$profilo = caricaProfilo($conn, $p_iva_venditore);

// ===================== 保存资料修改 =====================
if (isset($_POST['salva_profilo'])) {
    $ragione_sociale = trim($_POST['ragione_sociale']);
    $indirizzo = trim($_POST['indirizzo']);
    $cap = trim($_POST['cap']);

    if (!$ragione_sociale) {
        $errore = 'La ragione sociale è obbligatoria.';
    } else {
        $stmt_aggiorna = $conn->prepare("UPDATE venditore SET ragione_sociale = ?, indirizzo = ?, cap = ? WHERE p_iva = ?");
        $stmt_aggiorna->bind_param("ssss", $ragione_sociale, $indirizzo, $cap, $p_iva_venditore);

        if ($stmt_aggiorna->execute()) {
            $_SESSION['venditore_ragione_sociale'] = $ragione_sociale;
            $messaggio = "Profilo aggiornato con successo!";
            $profilo = caricaProfilo($conn, $p_iva_venditore);
        } else {
            $errore = "Errore nell'aggiornamento.";
        }
        $stmt_aggiorna->close();
    }
}

// ===================== 修改密码 =====================
if (isset($_POST['cambia_password'])) {
    $password_attuale = trim($_POST['password_attuale']);
    $nuova_password = trim($_POST['nuova_password']);
    $conferma_password = trim($_POST['conferma_password']);

    if (password_verify($password_attuale, $profilo['password'])) {
        if ($nuova_password === $conferma_password) {
            if (strlen($nuova_password) >= 6) {
                $nuova_hash = password_hash($nuova_password, PASSWORD_DEFAULT);
                $stmt_password = $conn->prepare("UPDATE venditore SET password = ? WHERE p_iva = ?");
                $stmt_password->bind_param("ss", $nuova_hash, $p_iva_venditore);

                if ($stmt_password->execute()) {
                    $messaggio = "Password modificata con successo!";
                } else {
                    $errore = "Errore nella modifica della password.";
                }
                $stmt_password->close();
            } else {
                $errore = "La password deve essere di almeno 6 caratteri!";
            }
        } else {
            $errore = "Le nuove password non coincidono!";
        }
    } else {
        $errore = "La password attuale è errata!";
    }
}

include 'includes/header.php';
?>

<h2><i class="fas fa-user-cog"></i> Il Tuo Profilo</h2>

<?php if ($messaggio): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> <?= e($messaggio) ?>
    </div>
<?php endif; ?>
<?php if ($errore): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i> <?= e($errore) ?>
    </div>
<?php endif; ?>

<div class="form-card">
    <h3><i class="fas fa-building"></i> Dati Aziendali</h3>
    <form method="POST">
        <?php csrf_field(); ?>
        <div class="form-group">
            <label><i class="fas fa-id-card"></i> Partita IVA (non modificabile)</label>
            <input type="text" value="<?= e($profilo['p_iva'] ?? '') ?>" disabled>
        </div>

        <div class="form-group">
            <label for="ragione_sociale"><i class="fas fa-signature"></i> Ragione Sociale</label>
            <input type="text" id="ragione_sociale" name="ragione_sociale" required 
                   value="<?= e($profilo['ragione_sociale'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="indirizzo"><i class="fas fa-map-marker-alt"></i> Indirizzo Sede</label>
            <input type="text" id="indirizzo" name="indirizzo" 
                   value="<?= e($profilo['indirizzo'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="cap"><i class="fas fa-mail-bulk"></i> CAP</label>
            <input type="text" id="cap" name="cap" maxlength="5"
                   value="<?= e($profilo['cap'] ?? '') ?>">
        </div>

        <button type="submit" name="salva_profilo" class="btn btn-success submit-btn">
            <i class="fas fa-save"></i> Salva Modifiche
        </button>
    </form>
</div>

<div class="form-card">
    <h3><i class="fas fa-key"></i> Cambia Password</h3>
    <form method="POST">
        <?php csrf_field(); ?>
        <div class="form-group">
            <label for="password_attuale"><i class="fas fa-lock"></i> Password Attuale</label>
            <input type="password" id="password_attuale" name="password_attuale" required>
        </div>

        <div class="form-group">
            <label for="nuova_password"><i class="fas fa-key"></i> Nuova Password (min. 6 caratteri)</label>
            <input type="password" id="nuova_password" name="nuova_password" required>
        </div>

        <div class="form-group">
            <label for="conferma_password"><i class="fas fa-check"></i> Conferma Nuova Password</label>
            <input type="password" id="conferma_password" name="conferma_password" required>
        </div>

        <button type="submit" name="cambia_password" class="btn btn-success submit-btn">
            <i class="fas fa-key"></i> Cambia Password
        </button>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
