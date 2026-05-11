<?php include 'config.php';

// 验证用户登录状态
if (!isset($_SESSION['user_email'])) {
    header("Location: login.php");
    exit;
}

$cart_id = $_SESSION['cart_id'];
$total = 0;

// 添加商品到购物车（预处理语句 防SQL注入）
if (isset($_POST['add_to_cart'])) {
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);

    // 检查商品是否已在购物车
    $stmt_check = $conn->prepare("SELECT * FROM p_c WHERE id_prodotto = ? AND id_carello = ?");
    $stmt_check->bind_param("ii", $product_id, $cart_id);
    $stmt_check->execute();
    $check = $stmt_check->get_result();

    if ($check->num_rows > 0) {
        // 更新商品数量
        $stmt_update = $conn->prepare("UPDATE p_c SET pezzi = pezzi + ? WHERE id_prodotto = ? AND id_carello = ?");
        $stmt_update->bind_param("iii", $quantity, $product_id, $cart_id);
        $stmt_update->execute();
        $stmt_update->close();
    } else {
        // 新增商品到购物车
        $stmt_insert = $conn->prepare("INSERT INTO p_c (id_prodotto, id_carello, pezzi) VALUES (?, ?, ?)");
        $stmt_insert->bind_param("iii", $product_id, $cart_id, $quantity);
        $stmt_insert->execute();
        $stmt_insert->close();
    }
    $stmt_check->close();

    header("Location: cart.php");
    exit;
}

// 移除购物车商品（预处理语句 防SQL注入）
if (isset($_POST['remove_from_cart'])) {
    $product_id = intval($_POST['product_id']);

    $stmt_delete = $conn->prepare("DELETE FROM p_c WHERE id_prodotto = ? AND id_carello = ?");
    $stmt_delete->bind_param("ii", $product_id, $cart_id);
    $stmt_delete->execute();
    $stmt_delete->close();

    header("Location: cart.php");
    exit;
}

// 查询购物车商品（预处理语句 防SQL注入）
$stmt_cart = $conn->prepare("
    SELECT pc.*, p.nome, p.prezzo 
    FROM p_c pc 
    JOIN prodotto p ON pc.id_prodotto = p.id_prodotto 
    WHERE pc.id_carello = ?
");
$stmt_cart->bind_param("i", $cart_id);
$stmt_cart->execute();
$cart_items = $stmt_cart->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Carrello - E-commerce Doubao</title>
    <link rel="stylesheet" href="css/style.css">
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
<?php
// 释放数据库资源
$stmt_cart->close();
?>