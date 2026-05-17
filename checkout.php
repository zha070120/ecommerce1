<?php include 'config.php';

// 验证登录
if (!isset($_SESSION['user_email'])) {
    header("Location: login.php");
    exit;
}

$cart_id = $_SESSION['cart_id'];
$user_email = $_SESSION['user_email'];
$error = '';

// 一次性查询购物车商品（优化：避免重复查询）
$stmt_cart = $conn->prepare("
    SELECT pc.*, p.nome, p.prezzo, p.quantita_disponibile 
    FROM p_c pc 
    JOIN prodotto p ON pc.id_prodotto = p.id_prodotto 
    WHERE pc.id_carello = ?
");
$stmt_cart->bind_param("i", $cart_id);
$stmt_cart->execute();
$cart_items = $stmt_cart->get_result();

// 购物车为空直接跳转
if ($cart_items->num_rows == 0) {
    $stmt_cart->close();
    header("Location: cart.php");
    exit;
}

// 提交订单
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $conn->begin_transaction();
    try {
        // 1. 创建订单（预处理语句，防注入）
        $stmt_order = $conn->prepare("INSERT INTO ordine (email, data_ordine, stato_ordine) VALUES (?, CURDATE(), 'attivo')");
        $stmt_order->bind_param("s", $user_email);
        $stmt_order->execute();
        $order_id = $conn->insert_id;
        $stmt_order->close();

        // 重置购物车查询指针
        $cart_items->data_seek(0);
        
        // 2. 循环添加订单商品 + 扣减库存
        while ($item = $cart_items->fetch_assoc()) {
            $product_id = $item['id_prodotto'];
            $quantity = $item['pezzi'];
            $price = $item['prezzo'];
            $total_item = $price * $quantity;

            // 插入订单商品（预处理）
            $stmt_item = $conn->prepare("INSERT INTO p_o (id_prodotto, id_ordine, pezzi, prezzo_singolo, prezzo_tot) VALUES (?, ?, ?, ?, ?)");
            $stmt_item->bind_param("iiidd", $product_id, $order_id, $quantity, $price, $total_item);
            $stmt_item->execute();
            $stmt_item->close();

            // 扣减库存（预处理 + 防超卖）
            $stmt_stock = $conn->prepare("UPDATE prodotto SET quantita_disponibile = quantita_disponibile - ? WHERE id_prodotto = ? AND quantita_disponibile >= ?");
            $stmt_stock->bind_param("iii", $quantity, $product_id, $quantity);
            $stmt_stock->execute();
            
            // 库存不足抛出异常
            if ($stmt_stock->affected_rows === 0) {
                throw new Exception("Prodotto '" . $item['nome'] . "' ha finito le scorte!");
            }
            $stmt_stock->close();
        }

        // 3. 清空购物车（预处理）
        $stmt_clear = $conn->prepare("DELETE FROM p_c WHERE id_carello = ?");
        $stmt_clear->bind_param("i", $cart_id);
        $stmt_clear->execute();
        $stmt_clear->close();

        // 提交事务
        $conn->commit();
        header("Location: orders.php?success=1");
        exit;

    } catch (Exception $e) {
        // 失败回滚
        $conn->rollback();
        $error = "Errore durante l'ordine: " . $e->getMessage();
    }
}

// 计算订单总价
$total = 0;
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - E-commerce Doubao</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    
</head>
<body>
    <header>
        <div class="container">
            <h1><i class="fas fa-shopping-bag"></i> E-commerce Doubao</h1>
            <nav>
                <a href="index.php"><i class="fas fa-home"></i> Home</a>
                <a href="cart.php"><i class="fas fa-shopping-cart"></i> Carrello</a>
                <a href="orders.php"><i class="fas fa-file-invoice"></i> I miei ordini</a>
                <span><i class="fas fa-user"></i> Ciao, <?= $_SESSION['user_name'] ?></span>
                <a href="logout.php" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </div>
    </header>

    <div class="container">
        <h2><i class="fas fa-credit-card"></i> Conferma ordine</h2>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <table class="order-table">
            <thead>
                <tr>
                    <th>Prodotto</th>
                    <th>Prezzo unitario</th>
                    <th>Quantità</th>
                    <th>Totale</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $cart_items->data_seek(0);
                while ($item = $cart_items->fetch_assoc()): 
                    $subtotal = $item['prezzo'] * $item['pezzi']; 
                    $total += $subtotal; 
                ?>
                    <tr>
                        <td class="product-name"><?= $item['nome'] ?></td>
                        <td class="price">€ <?= number_format($item['prezzo'], 2, ',', '.') ?></td>
                        <td class="quantity"><?= $item['pezzi'] ?></td>
                        <td class="subtotal">€ <?= number_format($subtotal, 2, ',', '.') ?></td>
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

        <form method="POST" class="checkout-actions">
            <a href="cart.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Annulla
            </a>
            <button type="submit" class="btn btn-success">
                <i class="fas fa-check-circle"></i> Conferma e paga
            </button>
        </form>
    </div>
</body>
</html>
<?php
// 释放资源
$stmt_cart->close();
?>