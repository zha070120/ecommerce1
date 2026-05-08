<?php include 'config.php';

if (!isset($_SESSION['user_email'])) {
    header("Location: login.php");
    exit;
}

// 处理取消订单
if (isset($_GET['cancel_id'])) {
    $order_id = intval($_GET['cancel_id']);
    $user_email = $_SESSION['user_email'];

    // 先校验订单属于当前用户 且 状态不是已完成/已取消
    $checkOrder = $conn->query("SELECT * FROM ordine WHERE id_ordine = $order_id AND email = '$user_email'");
    if ($checkOrder->num_rows > 0) {
        $ord = $checkOrder->fetch_assoc();
        // 只有 attivo 才能取消
        if ($ord['stato_ordine'] === 'attivo') {
            // 把商品库存加回去
            $items = $conn->query("SELECT id_prodotto, pezzi FROM p_o WHERE id_ordine = $order_id");
            while ($it = $items->fetch_assoc()) {
                $pid = $it['id_prodotto'];
                $qty = $it['pezzi'];
                $conn->query("UPDATE prodotto SET quantita_disponibile = quantita_disponibile + $qty WHERE id_prodotto = $pid");
            }
            // 更新订单状态为已取消
            $conn->query("UPDATE ordine SET stato_ordine = 'annullato' WHERE id_ordine = $order_id");
            header("Location: orders.php?success=2");
            exit;
        }
    }
}

$orders = $conn->query("SELECT * FROM ordine WHERE email='{$_SESSION['user_email']}' ORDER BY data_ordine DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>I miei ordini - E-commerce Doubao</title>
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
        <h2>I miei ordini</h2>
        <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
            <div class="alert alert-success">Ordine completato con successo!</div>
        <?php endif; ?>
        <?php if (isset($_GET['success']) && $_GET['success'] == 2): ?>
            <div class="alert alert-success">Ordine annullato con successo, magazzino ripristinato!</div>
        <?php endif; ?>

        <?php if ($orders->num_rows > 0): ?>
            <?php while ($order = $orders->fetch_assoc()): ?>
                <div style="background: white; padding: 1.5rem; border-radius: 8px; margin-bottom: 1rem;">
                    <h3>Ordine #<?= $order['id_ordine'] ?></h3>
                    <p>Data: <?= date('d/m/Y', strtotime($order['data_ordine'])) ?></p>
                    <p>Stato: 
                        <strong>
                            <?php 
                                if($order['stato_ordine'] == 'attivo') echo 'In lavorazione';
                                elseif($order['stato_ordine'] == 'consegnato') echo 'Consegnato';
                                elseif($order['stato_ordine'] == 'annullato') echo 'Annullato';
                            ?>
                        </strong>
                    </p>

                    <!-- 只有进行中的订单显示取消按钮 -->
                    <?php if($order['stato_ordine'] == 'attivo'): ?>
                        <a href="orders.php?cancel_id=<?= $order['id_ordine'] ?>" 
                           class="btn btn-danger"
                           onclick="return confirm('Sei sicuro di voler annullare questo ordine?')">
                           Annulla Ordine
                        </a>
                    <?php endif; ?>

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