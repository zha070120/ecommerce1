<?php include 'config.php';

if (!isset($_SESSION['user_email'])) {
    header("Location: login.php");
    exit;
}

if (isset($_POST['add_to_cart'])) {
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);
    $cart_id = $_SESSION['cart_id'];

    $check = $conn->query("SELECT * FROM p_c WHERE id_prodotto=$product_id AND id_carello=$cart_id");
    if ($check->num_rows > 0) {
        $conn->query("UPDATE p_c SET pezzi = pezzi + $quantity WHERE id_prodotto=$product_id AND id_carello=$cart_id");
    } else {
        $conn->query("INSERT INTO p_c (id_prodotto, id_carello, pezzi) VALUES ($product_id, $cart_id, $quantity)");
    }
    header("Location: cart.php");
    exit;
}
    
if (isset($_POST['remove_from_cart'])) {
    $product_id = intval($_POST['product_id']);
    $cart_id = $_SESSION['cart_id'];
    $conn->query("DELETE FROM p_c WHERE id_prodotto=$product_id AND id_carello=$cart_id");
    header("Location: cart.php");
    exit;
}

$cart_items = $conn->query("
    SELECT pc.*, p.nome, p.prezzo 
    FROM p_c pc 
    JOIN prodotto p ON pc.id_prodotto = p.id_prodotto 
    WHERE pc.id_carello = {$_SESSION['cart_id']}
");
$total = 0;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Carrello - E-commerce Doubao</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>E-commerce Doubao</h1>
        <nav>
            <a href="index.php">Home</a>
            <a href="cart.php">Carrello</a>
            <a href="orders.php">I miei ordini</a>
            <span>Ciao, <?= $_SESSION['user_name'] ?></span>
            <a href="logout.php" class="btn btn-danger">Logout</a>
        </nav>
    </header>
    <div class="container">
        <h2>Il tuo carrello</h2>
        <?php if ($cart_items->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Prodotto</th>
                        <th>Prezzo unitario</th>
                        <th>Quantità</th>
                        <th>Totale</th>
                        <th>Azioni</th>
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
                            <td>
                                <form method="POST">
                                    <input type="hidden" name="product_id" value="<?= $item['id_prodotto'] ?>">
                                    <button type="submit" name="remove_from_cart" class="btn btn-danger">Rimuovi</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3"><strong>Totale carrello</strong></td>
                        <td><strong>€ <?= number_format($total, 2, ',', '.') ?></strong></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
            <a href="checkout.php" class="btn btn-success" style="margin-top: 1rem;">Procedi al checkout</a>
        <?php else: ?>
            <p>Il tuo carrello è vuoto. <a href="index.php">Torna ai prodotti</a></p>
        <?php endif; ?>
    </div>
</body>
</html>
