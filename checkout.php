<?php include 'config.php';

if (!isset($_SESSION['user_email'])) {
    header("Location: login.php");
    exit;
}

$cart_id = $_SESSION['cart_id'];
$user_email = $_SESSION['user_email'];

$cart_items = $conn->query("
    SELECT pc.*, p.nome, p.prezzo, p.quantita_disponibile 
    FROM p_c pc 
    JOIN prodotto p ON pc.id_prodotto = p.id_prodotto 
    WHERE pc.id_carello = $cart_id
");

if ($cart_items->num_rows == 0) {
    header("Location: cart.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $conn->begin_transaction();
    try {
        $conn->query("INSERT INTO ordine (email, data_ordine, stato_ordine) VALUES ('$user_email', CURDATE(), 'attivo')");
        $order_id = $conn->insert_id;

        while ($item = $cart_items->fetch_assoc()) {
            $product_id = $item['id_prodotto'];
            $quantity = $item['pezzi'];
            $price = $item['prezzo'];
            $total = $price * $quantity;

            $conn->query("INSERT INTO p_o (id_prodotto, id_ordine, pezzi, prezzo_singolo, prezzo_tot) 
                          VALUES ($product_id, $order_id, $quantity, $price, $total)");

            $conn->query("UPDATE prodotto SET quantita_disponibile = quantita_disponibile - $quantity WHERE id_prodotto = $product_id");
        }

        $conn->query("DELETE FROM p_c WHERE id_carello = $cart_id");

        $conn->commit();
        header("Location: orders.php?success=1");
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Errore durante l'ordine: " . $e->getMessage();
    }
}

$cart_items = $conn->query("
    SELECT pc.*, p.nome, p.prezzo 
    FROM p_c pc 
    JOIN prodotto p ON pc.id_prodotto = p.id_prodotto 
    WHERE pc.id_carello = $cart_id
");
$total = 0;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Checkout - E-commerce Italia</title>
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
        <h2>Conferma ordine</h2>
        <?php if (isset($error)): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
        <table>
            <thead>
                <tr>
                    <th>Prodotto</th>
                    <th>Prezzo unitario</th>
                    <th>Quantità</th>
                    <th>Totale</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($item = $cart_items->fetch_assoc()): ?>
                    <?php $subtotal = $item['prezzo'] * $item['pezzi']; $total += $subtotal; ?>
                    <tr>
                        <td><?= $item['nome'] ?></td>
                        <td>€ <?= number_format($item['prezzo'], 2, ',', '.') ?></td>
                        <td><?= $item['pezzi'] ?></td>
                        <td>€ <?= number_format($subtotal, 2, ',', '.') ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3"><strong>Totale ordine</strong></td>
                    <td><strong>€ <?= number_format($total, 2, ',', '.') ?></strong></td>
                </tr>
            </tfoot>
        </table>
        <form method="POST" style="margin-top: 1rem;">
            <button type="submit" class="btn btn-success">Conferma e paga</button>
            <a href="cart.php" class="btn">Annulla</a>
        </form>
    </div>
</body>
</html>
