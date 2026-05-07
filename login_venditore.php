<?php include 'config.php'; $errore = '';

// Se il venditore è già loggato, reindirizza alla dashboard
if (venditore_loggato()) {
    header("Location: dashboard_venditore.php");
    exit;
}

// Gestione del form di login
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $p_iva = $conn->real_escape_string($_POST['p_iva']);
    $password_inserita = $_POST['password'];

    // Query con prepared statement per sicurezza
    $stmt = $conn->prepare("SELECT * FROM venditore WHERE p_iva = ?");
    $stmt->bind_param("s", $p_iva);
    $stmt->execute();
    $risultato = $stmt->get_result();

    if ($risultato->num_rows == 1) {
        $venditore = $risultato->fetch_assoc();
        // Verifica password (hashata)
        if (password_verify($password_inserita, $venditore['password'])) {
            // Crea sessione venditore
            $_SESSION['venditore_piva'] = $venditore['p_iva'];
            $_SESSION['venditore_ragione_sociale'] = $venditore['ragione_sociale'];
            header("Location: dashboard_venditore.php");
            exit;
        } else {
            $errore = "Password errata!";
        }
    } else {
        $errore = "Partita IVA non trovata!";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Login Venditori - E-commerce Italia</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>E-commerce Italia</h1>
        <nav>
            <a href="index.php">Home</a>
            <a href="login.php">Login Clienti</a>
            <a href="login_venditore.php" class="btn">Area Venditori</a>
        </nav>
    </header>

    <div class="container">
        <h2>Login Area Venditori</h2>
        <?php if ($errore): ?>
            <div class="alert alert-danger"><?= $errore ?></div>
        <?php endif; ?>

        <form method="POST" style="max-width: 500px; margin: 0 auto;">
            <div class="form-group">
                <label>Partita IVA</label>
                <input type="text" name="p_iva" maxlength="13" required placeholder="Inserisci la tua Partita IVA">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-success">Accedi all'Area Venditori</button>
        </form>
    </div>
</body>
</html>
