<?php include 'config.php'; richiedi_login_venditore();

$p_iva_venditore = $_SESSION['venditore_piva'];
$messaggio = '';
$errore = '';
// Whitelist degli stati consentiti (solo questi possono essere salvati)
$stati_consentiti = ['attivo', 'spedito', 'consegnato', 'annullato'];

// GESTIONE AGGIORNAMENTO STATO ORDINE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aggiorna_stato'])) {
    $id_ordine = intval($_POST['id_ordine']);
    $nuovo_stato = trim(strtolower($_POST['stato_ordine']));

    // Controllo 1: verifica che lo stato sia nella whitelist
    if (!in_array($nuovo_stato, $stati_consentiti)) {
        $errore = "Stato ordine non valido! Stati consentiti: attivo, spedito, consegnato, annullato.";
    } else {
        // Controllo 2: verifica che l'ordine contenga i prodotti del venditore (sicurezza anti-accesso non autorizzato)
        $stmt_check = $conn->prepare("
            SELECT DISTINCT o.id_ordine
            FROM ordine o
            JOIN p_o po ON o.id_ordine = po.id_ordine
            JOIN prodotto p ON po.id_prodotto = p.id_prodotto
            WHERE o.id_ordine = ? AND p.p_iva = ?
        ");
        $stmt_check->bind_param("is", $id_ordine, $p_iva_venditore);
        $stmt_check->execute();
        $ordine_valido = $stmt_check->get_result()->num_rows > 0;
        $stmt_check->close();

        if ($ordine_valido) {
            // Controllo 3: aggiorna lo stato nel database
            $stmt_update = $conn->prepare("UPDATE ordine SET stato_ordine = ? WHERE id_ordine = ?");
            $stmt_update->bind_param("si", $nuovo_stato, $id_ordine);
            if ($stmt_update->execute()) {
                $messaggio = "Stato dell'ordine #$id_ordine aggiornato con successo!";
            } else {
                $errore = "Errore durante l'aggiornamento: " . $conn->error;
            }
            $stmt_update->close();
        } else {
            $errore = "Non sei autorizzato a modificare questo ordine!";
        }
    }
}

// Recupera tutti gli ordini con prodotti del venditore
$stmt_ordini = $conn->prepare("
    SELECT DISTINCT o.id_ordine, o.data_ordine, o.stato_ordine, c.nome, c.cognome, c.email
    FROM ordine o
    JOIN cliente c ON o.email = c.email
    JOIN p_o po ON o.id_ordine = po.id_ordine
    JOIN prodotto p ON po.id_prodotto = p.id_prodotto
    WHERE p.p_iva = ?
    ORDER BY o.data_ordine DESC
");
$stmt_ordini->bind_param("s", $p_iva_venditore);
$stmt_ordini->execute();
$ordini = $stmt_ordini->get_result();
$stmt_ordini->close();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>I Miei Ordini - Area Venditori</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .stato-form { display: flex; gap: 0.5rem; align-items: center; margin-top: 0.5rem; }
        .stato-form select { padding: 0.3rem; border-radius: 4px; border: 1px solid #ddd; }
    </style>
</head>
<body>
    <header>
        <h1>Area Venditori</h1>
        <nav>
            <span>Ciao, <?= $_SESSION['venditore_ragione_sociale'] ?></span>
            <a href="dashboard_venditore.php">Dashboard</a>
            <a href="gestisci_prodotti.php">Prodotti</a>
            <a href="ordini_venditore.php">I Miei Ordini</a>
            <a href="profilo_venditore.php">Profilo</a>
            <a href="logout.php" class="btn btn-danger">Logout</a>
        </nav>
    </header>

    <div class="container">
        <h2>Ordini Ricevuti</h2>

        <!-- Messaggi di feedback -->
        <?php if ($messaggio): ?>
            <div class="alert alert-success"><?= $messaggio ?></div>
        <?php endif; ?>
        <?php if ($errore): ?>
            <div class="alert alert-danger"><?= $errore ?></div>
        <?php endif; ?>

        <?php if ($ordini->num_rows > 0): ?>
            <?php while ($ordine = $ordini->fetch_assoc()): ?>
                <?php
                // Colori personalizzati per ogni stato
                $colore_stato = match($ordine['stato_ordine']) {
                    'attivo' => '#3498db',
                    'spedito' => '#f39c12',
                    'consegnato' => '#27ae60',
                    'annullato' => '#e74c3c',
                    default => '#333333'
                };
                ?>
                <div style="background: white; padding: 1.5rem; border-radius: 8px; margin-bottom: 1.5rem;">
                    <h3>Ordine #<?= $ordine['id_ordine'] ?></h3>
                    <p><strong>Data:</strong> <?= date('d/m/Y', strtotime($ordine['data_ordine'])) ?></p>
                    <p>
                        <strong>Stato attuale:</strong>
                        <span style="font-weight: bold; color: <?= $colore_stato ?>;">
                            <?= ucfirst($ordine['stato_ordine']) ?>
                        </span>
                    </p>
                    <p><strong>Cliente:</strong> <?= $ordine['nome'] ?> <?= $ordine['cognome'] ?> (<?= $ordine['email'] ?>)</p>

                    <!-- Form per modificare lo stato ordine -->
                    <form method="POST" class="stato-form">
                        <input type="hidden" name="id_ordine" value="<?= $ordine['id_ordine'] ?>">
                        <select name="stato_ordine" required>
                            <option value="attivo" <?= $ordine['stato_ordine'] == 'attivo' ? 'selected' : '' ?>>Attivo</option>
                            <option value="spedito" <?= $ordine['stato_ordine'] == 'spedito' ? 'selected' : '' ?>>Spedito</option>
                            <option value="consegnato" <?= $ordine['stato_ordine'] == 'consegnato' ? 'selected' : '' ?>>Consegnato</option>
                            <option value="annullato" <?= $ordine['stato_ordine'] == 'annullato' ? 'selected' : '' ?>>Annullato</option>
                        </select>
                        <button type="submit" name="aggiorna_stato" class="btn">Aggiorna Stato</button>
                    </form>

                    <!-- Dettaglio prodotti del venditore in questo ordine -->
                    <?php
                    $id_ordine = $ordine['id_ordine'];
                    $stmt_prodotti_ordine = $conn->prepare("
                        SELECT po.*, p.nome
                        FROM p_o po
                        JOIN prodotto p ON po.id_prodotto = p.id_prodotto
                        WHERE po.id_ordine = ? AND p.p_iva = ?
                    ");
                    $stmt_prodotti_ordine->bind_param("is", $id_ordine, $p_iva_venditore);
                    $stmt_prodotti_ordine->execute();
                    $prodotti_ordine = $stmt_prodotti_ordine->get_result();
                    $totale_ordine = 0;
                    ?>

                    <table style="margin-top: 1rem;">
                        <thead>
                            <tr>
                                <th>Prodotto</th>
                                <th>Prezzo Unitario</th>
                                <th>Quantità</th>
                                <th>Totale Riga</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($prodotto = $prodotti_ordine->fetch_assoc()): ?>
                                <?php $totale_ordine += $prodotto['prezzo_tot']; ?>
                                <tr>
                                    <td><?= $prodotto['nome'] ?></td>
                                    <td>€ <?= number_format($prodotto['prezzo_singolo'], 2, ',', '.') ?></td>
                                    <td><?= $prodotto['pezzi'] ?></td>
                                    <td>€ <?= number_format($prodotto['prezzo_tot'], 2, ',', '.') ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3"><strong>Totale per i tuoi prodotti</strong></td>
                                <td><strong>€ <?= number_format($totale_ordine, 2, ',', '.') ?></strong></td>
                            </tr>
                        </tfoot>
                    </table>
                    <?php $stmt_prodotti_ordine->close(); ?>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>Non hai ancora ricevuto ordini per i tuoi prodotti.</p>
        <?php endif; ?>
    </div>
</body>
</html>