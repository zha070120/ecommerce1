<?php include 'config.php'; richiedi_login_venditore();

$p_iva_venditore = $_SESSION['venditore_piva'];
$messaggio = '';
$errore = '';

// Recupera dati del venditore
$stmt_profilo = $conn->prepare("SELECT * FROM venditore WHERE p_iva = ?");
$stmt_profilo->bind_param("s", $p_iva_venditore);
$stmt_profilo->execute();
$profilo = $stmt_profilo->get_result()->fetch_assoc();
$stmt_profilo->close();

// Salvataggio modifiche profilo
if (isset($_POST['salva_profilo'])) {
    $ragione_sociale = $conn->real_escape_string($_POST['ragione_sociale']);
    $indirizzo = $conn->real_escape_string($_POST['indirizzo']);
    $cap = $conn->real_escape_string($_POST['cap']);

    $stmt_aggiorna = $conn->prepare("
        UPDATE venditore 
        SET ragione_sociale = ?, indirizzo = ?, cap = ?
        WHERE p_iva = ?
    ");
    $stmt_aggiorna->bind_param("ssss", $ragione_sociale, $indirizzo, $cap, $p_iva_venditore);
    if ($stmt_aggiorna->execute()) {
        $_SESSION['venditore_ragione_sociale'] = $ragione_sociale;
        $messaggio = "Profilo aggiornato con successo!";
        // Ricarica i dati del profilo
        $stmt_profilo = $conn->prepare("SELECT * FROM venditore WHERE p_iva = ?");
        $stmt_profilo->bind_param("s", $p_iva_venditore);
        $stmt_profilo->execute();
        $profilo = $stmt_profilo->get_result()->fetch_assoc();
        $stmt_profilo->close();
    } else {
        $errore = "Errore nell'aggiornamento: " . $conn->error;
    }
    $stmt_aggiorna->close();
}

// Modifica password
if (isset($_POST['cambia_password'])) {
    $password_attuale = $_POST['password_attuale'];
    $nuova_password = $_POST['nuova_password'];
    $conferma_password = $_POST['conferma_password'];

    // Verifica password attuale
    if (password_verify($password_attuale, $profilo['password'])) {
        if ($nuova_password == $conferma_password) {
            $nuova_password_hash = password_hash($nuova_password, PASSWORD_DEFAULT);
            $stmt_password = $conn->prepare("UPDATE venditore SET password = ? WHERE p_iva = ?");
            $stmt_password->bind_param("ss", $nuova_password_hash, $p_iva_venditore);
            if ($stmt_password->execute()) {
                $messaggio = "Password modificata con successo!";
            } else {
                $errore = "Errore nella modifica della password.";
            }
            $stmt_password->close();
        } else {
            $errore = "Le nuove password non coincidono!";
        }
    } else {
        $errore = "La password attuale è errata!";
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Profilo Venditore - Area Venditori</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
        <h1>Area Venditori</h1>
        <nav>
            <span>Ciao, <?= $_SESSION['venditore_ragione_sociale'] ?></span>
            <a href="dashboard_venditore.php">Dashboard</a>
            <a href="gestisci_prodotti.php">Prodotti</a>
            <a href="ordini_venditore.php">Ordini</a>
            <a href="profilo_venditore.php">Profilo</a>
            
            <a href="logout.php" class="btn btn-danger">Logout</a>
        </nav>
    </header>

    <div class="container">
        <h2>Il Tuo Profilo</h2>
        <?php if ($messaggio): ?><div class="alert alert-success"><?= $messaggio ?></div><?php endif; ?>
        <?php if ($errore): ?><div class="alert alert-danger"><?= $errore ?></div><?php endif; ?>

        <!-- Form modifica profilo -->
        <div style="background: white; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem;">
            <h3>Dati Aziendali</h3>
            <form method="POST">
                <div class="form-group">
                    <label>Partita IVA (non modificabile)</label>
                    <input type="text" value="<?= $profilo['p_iva'] ?>" disabled>
                </div>
                <div class="form-group">
                    <label>Ragione Sociale</label>
                    <input type="text" name="ragione_sociale" required value="<?= $profilo['ragione_sociale'] ?>">
                </div>
                <div class="form-group">
                    <label>Indirizzo Sede</label>
                    <input type="text" name="indirizzo" required value="<?= $profilo['indirizzo'] ?>">
                </div>
                <div class="form-group">
                    <label>CAP</label>
                    <input type="text" name="cap" required value="<?= $profilo['cap'] ?>">
                </div>
                <button type="submit" name="salva_profilo" class="btn btn-success">Salva Modifiche</button>
            </form>
        </div>

        <!-- Form modifica password -->
        <div style="background: white; padding: 1.5rem; border-radius: 8px;">
            <h3>Cambia Password</h3>
            <form method="POST">
                <div class="form-group">
                    <label>Password Attuale</label>
                    <input type="password" name="password_attuale" required>
                </div>
                <div class="form-group">
                    <label>Nuova Password</label>
                    <input type="password" name="nuova_password" required>
                </div>
                <div class="form-group">
                    <label>Conferma Nuova Password</label>
                    <input type="password" name="conferma_password" required>
                </div>
                <button type="submit" name="cambia_password" class="btn btn-success">Cambia Password</button>
            </form>
        </div>
    </div>
</body>
</html>
