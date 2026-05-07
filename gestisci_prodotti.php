<?php include 'config.php'; richiedi_login_venditore();

$p_iva_venditore = $_SESSION['venditore_piva'];
$messaggio = '';
$errore = '';

// 1. Eliminazione prodotto
if (isset($_POST['elimina_prodotto'])) {
    $id_prodotto = intval($_POST['id_prodotto']);
    // Verifica che il prodotto appartenga al venditore
    $stmt_check = $conn->prepare("SELECT * FROM prodotto WHERE id_prodotto = ? AND p_iva = ?");
    $stmt_check->bind_param("is", $id_prodotto, $p_iva_venditore);
    $stmt_check->execute();
    if ($stmt_check->num_rows == 1) {
        $stmt_elimina = $conn->prepare("DELETE FROM prodotto WHERE id_prodotto = ?");
        $stmt_elimina->bind_param("i", $id_prodotto);
        if ($stmt_elimina->execute()) {
            $messaggio = "Prodotto eliminato con successo!";
        } else {
            $errore = "Errore: non puoi eliminare un prodotto presente in ordini attivi.";
        }
        $stmt_elimina->close();
    } else {
        $errore = "Non sei autorizzato a eliminare questo prodotto.";
    }
    $stmt_check->close();
}

// 2. Aggiunta nuovo prodotto
if (isset($_POST['aggiungi_prodotto'])) {
    $nome = $conn->real_escape_string($_POST['nome']);
    $prezzo = floatval($_POST['prezzo']);
    $quantita = intval($_POST['quantita_disponibile']);
    $indirizzo_img = $conn->real_escape_string($_POST['indirizzo_img']);

    $stmt_aggiungi = $conn->prepare("
        INSERT INTO prodotto (nome, prezzo, p_iva, quantita_disponibile, indirizzo_img)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt_aggiungi->bind_param("sdsis", $nome, $prezzo, $p_iva_venditore, $quantita, $indirizzo_img);
    if ($stmt_aggiungi->execute()) {
        $messaggio = "Prodotto aggiunto con successo!";
    } else {
        $errore = "Errore nell'aggiunta del prodotto: " . $conn->error;
    }
    $stmt_aggiungi->close();
}

// 3. Modifica prodotto
$prodotto_da_modificare = null;
if (isset($_GET['modifica'])) {
    $id_prodotto = intval($_GET['modifica']);
    $stmt_modifica = $conn->prepare("SELECT * FROM prodotto WHERE id_prodotto = ? AND p_iva = ?");
    $stmt_modifica->bind_param("is", $id_prodotto, $p_iva_venditore);
    $stmt_modifica->execute();
    $prodotto_da_modificare = $stmt_modifica->get_result()->fetch_assoc();
    $stmt_modifica->close();
}

// 4. Salvataggio modifiche
if (isset($_POST['salva_modifiche'])) {
    $id_prodotto = intval($_POST['id_prodotto']);
    $nome = $conn->real_escape_string($_POST['nome']);
    $prezzo = floatval($_POST['prezzo']);
    $quantita = intval($_POST['quantita_disponibile']);
    $indirizzo_img = $conn->real_escape_string($_POST['indirizzo_img']);

    $stmt_salva = $conn->prepare("
        UPDATE prodotto 
        SET nome = ?, prezzo = ?, quantita_disponibile = ?, indirizzo_img = ?
        WHERE id_prodotto = ? AND p_iva = ?
    ");
    $stmt_salva->bind_param("sdissi", $nome, $prezzo, $quantita, $indirizzo_img, $id_prodotto, $p_iva_venditore);
    if ($stmt_salva->execute()) {
        $messaggio = "Prodotto modificato con successo!";
        $prodotto_da_modificare = null;
    } else {
        $errore = "Errore nella modifica: " . $conn->error;
    }
    $stmt_salva->close();
}

// Recupera tutti i prodotti del venditore
$stmt_prodotti = $conn->prepare("SELECT * FROM prodotto WHERE p_iva = ? ORDER BY id_prodotto DESC");
$stmt_prodotti->bind_param("s", $p_iva_venditore);
$stmt_prodotti->execute();
$prodotti = $stmt_prodotti->get_result();
$stmt_prodotti->close();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Gestisci Prodotti - Area Venditori</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>Area Venditori</h1>
        <nav>
            <a href="dashboard_venditore.php">Dashboard</a>
            <a href="gestisci_prodotti.php">Gestisci Prodotti</a>
            <a href="ordini_venditore.php">I Miei Ordini</a>
            <a href="profilo_venditore.php">Profilo</a>
            <span>Ciao, <?= $_SESSION['venditore_ragione_sociale'] ?></span>
            <a href="logout.php" class="btn btn-danger">Logout</a>
        </nav>
    </header>

    <div class="container">
        <h2><?= $prodotto_da_modificare ? 'Modifica Prodotto' : 'Aggiungi Nuovo Prodotto' ?></h2>
        <?php if ($messaggio): ?><div class="alert alert-success"><?= $messaggio ?></div><?php endif; ?>
        <?php if ($errore): ?><div class="alert alert-danger"><?= $errore ?></div><?php endif; ?>

        <!-- Form aggiunta/modifica prodotto -->
        <form method="POST" style="background: white; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem;">
            <?php if ($prodotto_da_modificare): ?>
                <input type="hidden" name="id_prodotto" value="<?= $prodotto_da_modificare['id_prodotto'] ?>">
            <?php endif; ?>

            <div class="form-group">
                <label>Nome Prodotto</label>
                <input type="text" name="nome" required value="<?= $prodotto_da_modificare['nome'] ?? '' ?>">
            </div>
            <div class="form-group">
                <label>Prezzo (€)</label>
                <input type="number" step="0.01" name="prezzo" required value="<?= $prodotto_da_modificare['prezzo'] ?? '' ?>">
            </div>
            <div class="form-group">
                <label>Quantità Disponibile</label>
                <input type="number" name="quantita_disponibile" required value="<?= $prodotto_da_modificare['quantita_disponibile'] ?? '' ?>">
            </div>
            <div class="form-group">
                <label>URL/Percorso Immagine</label>
                <input type="text" name="indirizzo_img" required value="<?= $prodotto_da_modificare['indirizzo_img'] ?? '' ?>">
            </div>

            <?php if ($prodotto_da_modificare): ?>
                <button type="submit" name="salva_modifiche" class="btn btn-success">Salva Modifiche</button>
                <a href="gestisci_prodotti.php" class="btn">Annulla</a>
            <?php else: ?>
                <button type="submit" name="aggiungi_prodotto" class="btn btn-success">Aggiungi Prodotto</button>
            <?php endif; ?>
        </form>

        <!-- Lista prodotti esistenti -->
        <h2>I Tuoi Prodotti</h2>
        <?php if ($prodotti->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Prezzo</th>
                        <th>Disponibilità</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($prodotto = $prodotti->fetch_assoc()): ?>
                        <tr>
                            <td><?= $prodotto['id_prodotto'] ?></td>
                            <td><?= $prodotto['nome'] ?></td>
                            <td>€ <?= number_format($prodotto['prezzo'], 2, ',', '.') ?></td>
                            <td><?= $prodotto['quantita_disponibile'] ?> pezzi</td>
                            <td>
                                <a href="gestisci_prodotti.php?modifica=<?= $prodotto['id_prodotto'] ?>" class="btn">Modifica</a>
                                <form method="POST" style="display: inline-block;">
                                    <input type="hidden" name="id_prodotto" value="<?= $prodotto['id_prodotto'] ?>">
                                    <button type="submit" name="elimina_prodotto" class="btn btn-danger" onclick="return confirm('Sei sicuro di voler eliminare questo prodotto?')">Elimina</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Non hai ancora aggiunto prodotti al catalogo.</p>
        <?php endif; ?>
    </div>
</body>
</html>
