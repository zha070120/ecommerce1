<?php include 'config.php';

if (!isset($_SESSION['user_email'])) {
    header("Location: login.php");
    exit;
}

$orders = $conn->query("SELECT * FROM ordine WHERE email='{$_SESSION['user_email']}' ORDER BY data_ordine DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>I miei ordini - E-commerce Italia</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>E-commerce Italia</h1>
        <nav>
            <a href="index.php">Home</a>
            <a href="cart.php">Carrello</a>
            <a href="orders.php">I miei ordini</a>
            <span>Ciao, <?= $_SESSION['user_name'] ?></span>
            <a href="logout.php" class="btn btn-danger">Logout</a>
        </nav>
    </header>
    <div class="container">
        <h2>I miei ordini</h2>
        <?php if (isset($_GET['success'])): ?><div class="alert alert-success">Ordine completato con successo!</div><?php endif; ?>
        <?php if ($orders->num_rows > 0): ?>
            <?php while ($order = $orders->fetch_assoc()): ?>
                <div style="background: white; padding: 1.5rem; border-radius: 8px; margin-bottom: 1rem;">
                    <h3>Ordine #<?= $order['id_ordine'] ?></h3>
                    <p>Data: <?= date('d/m/Y', strtotime($order['data_ordine'])) ?></p>
                    <p>Stato: <strong><?= $order['stato_ordine'] ?></strong></p>
                    <?php
                    $order_items = $conn->query("
                        SELECT po.*, p.nome 
                        FROM p_o po 
                        JOIN prodotto p ON po.id_prodotto = p.id_prodotto 
                        WHERE po.id_ordine = {$order['id_ordine']}
                    ");
                    $order_total = 0;
                    ?>
                    <table style="margin-top: 1rem;">
                        <thead>
                            <tr>
                                <th>Prodotto</th>
                                <th>Prezzo unitario</th>
                                <th>Quantità</th>
                                <th>Totale</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($item = $order_items->fetch_assoc()): ?>
                                <?php $order_total += $item['prezzo_tot']; ?>
                                <tr>
                                    <td><?= $item['nome'] ?></td>
                                    <td>€ <?= number_format($item['prezzo_singolo'], 2, ',', '.') ?></td>
                                    <td><?= $item['pezzi'] ?></td>
                                    <td>€ <?= number_format($item['prezzo_tot'], 2, ',', '.') ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3"><strong>Totale ordine</strong></td>
                                <td><strong>€ <?= number_format($order_total, 2, ',', '.') ?></strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>Non hai ancora effettuato ordini. <a href="index.php">Inizia a fare acquisti</a></p>
        <?php endif; ?>
    </div>
</body>
</html>
