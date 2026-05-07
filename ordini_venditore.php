<?php include 'config.php'; richiedi_login_venditore();

$p_iva_venditore = $_SESSION['venditore_piva'];

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
        <h2>Ordini Ricevuti</h2>
        <?php if ($ordini->num_rows > 0): ?>
            <?php while ($ordine = $ordini->fetch_assoc()): ?>
                <div style="background: white; padding: 1.5rem; border-radius: 8px; margin-bottom: 1.5rem;">
                    <h3>Ordine #<?= $ordine['id_ordine'] ?></h3>
                    <p><strong>Data:</strong> <?= date('d/m/Y', strtotime($ordine['data_ordine'])) ?></p>
                    <p><strong>Stato:</strong> <span style="font-weight: bold; color: #27ae60;"><?= $ordine['stato_ordine'] ?></span></p>
                    <p><strong>Cliente:</strong> <?= $ordine['nome'] ?> <?= $ordine['cognome'] ?> (<?= $ordine['email'] ?>)</p>

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
