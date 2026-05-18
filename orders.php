<?php
include 'config.php';

// 验证登录状态，安全获取会话数据
if (!isset($_SESSION['user_email'])) {
    header("Location: login.php");
    exit;
}
$user_email = $_SESSION['user_email'] ?? '';
$messaggio_errore = '';

// 处理订单取消
if (isset($_GET['cancel_id']) && is_numeric($_GET['cancel_id'])) {
    $order_id = (int)$_GET['cancel_id'];
    $conn->begin_transaction();

    try {
        // 校验订单归属与状态
        $stmt_check = $conn->prepare("SELECT stato_ordine FROM ordine WHERE id_ordine = ? AND email = ?");
        $stmt_check->bind_param("is", $order_id, $user_email);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows === 0) {
            throw new Exception("Ordine non valido");
        }

        $ordine = $result_check->fetch_assoc();
        if ($ordine['stato_ordine'] !== 'attivo') {
            throw new Exception("Non puoi annullare questo ordine");
        }
        $stmt_check->close();

        // 恢复商品库存
        $stmt_items = $conn->prepare("SELECT id_prodotto, pezzi FROM p_o WHERE id_ordine = ?");
        $stmt_items->bind_param("i", $order_id);
        $stmt_items->execute();
        $items = $stmt_items->get_result();

        $stmt_update_stock = $conn->prepare("UPDATE prodotto SET quantita_disponibile = quantita_disponibile + ? WHERE id_prodotto = ?");
        while ($item = $items->fetch_assoc()) {
            $stmt_update_stock->bind_param("ii", $item['pezzi'], $item['id_prodotto']);
            $stmt_update_stock->execute();
        }
        $stmt_items->close();
        $stmt_update_stock->close();

        // 更新订单状态为已取消
        $stmt_cancel = $conn->prepare("UPDATE ordine SET stato_ordine = 'annullato' WHERE id_ordine = ?");
        $stmt_cancel->bind_param("i", $order_id);
        $stmt_cancel->execute();
        $stmt_cancel->close();

        $conn->commit();
        header("Location: orders.php?success=2");
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        $messaggio_errore = $e->getMessage();
    }
}

// 一次性查询所有订单（优化N+1查询问题）
$stmt_ordini = $conn->prepare("
    SELECT o.*, p.nome, po.pezzi, po.prezzo_singolo, po.prezzo_tot
    FROM ordine o
    LEFT JOIN p_o po ON o.id_ordine = po.id_ordine
    LEFT JOIN prodotto p ON po.id_prodotto = p.id_prodotto
    WHERE o.email = ?
    ORDER BY o.id_ordine DESC
");
$stmt_ordini->bind_param("s", $user_email);
$stmt_ordini->execute();
$result_ordini = $stmt_ordini->get_result();

// 分组订单数据
$ordini_raggruppati = [];
while ($row = $result_ordini->fetch_assoc()) {
    $id_ordine = $row['id_ordine'] ?? 0;
    if (!isset($ordini_raggruppati[$id_ordine])) {
        $ordini_raggruppati[$id_ordine] = [
            'info' => $row,
            'prodotti' => [],
            'totale' => 0
        ];
    }
    if (!empty($row['nome'])) {
        $ordini_raggruppati[$id_ordine]['prodotti'][] = $row;
        $ordini_raggruppati[$id_ordine]['totale'] += $row['prezzo_tot'] ?? 0;
    }
}
$stmt_ordini->close();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>I miei ordini - E-commerce Doubao</title>
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
                <a href="orders.php" style="border-bottom: 2px solid #fef3c7;"><i class="fas fa-file-invoice"></i> I miei ordini</a>
                <span><i class="fas fa-user"></i> Ciao, <?= $_SESSION['user_name'] ?? 'Utente' ?></span>
                <a href="logout.php" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </div>
    </header>

    <div class="container">
        <h2><i class="fas fa-file-invoice"></i> I miei ordini</h2>

        <!-- 提示信息 -->
        <?php if ($messaggio_errore): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> <?= $messaggio_errore ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> Ordine completato con successo!
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['success']) && $_GET['success'] == 2): ?>
            <div class="alert alert-success">
                <i class="fas fa-undo"></i> Ordine annullato con successo, magazzino ripristinato!
            </div>
        <?php endif; ?>

        <?php if (!empty($ordini_raggruppati)): ?>
            <?php foreach ($ordini_raggruppati as $ordine): ?>
                <div class="order-card">
                    <div class="order-header">
                        <h3><i class="fas fa-receipt"></i> Ordine #<?= $ordine['info']['id_ordine'] ?? '' ?></h3>
                        <div class="order-info">
                            <p><i class="fas fa-calendar-alt"></i> Data: <strong><?= date('d/m/Y', strtotime($ordine['info']['data_ordine'] ?? '')) ?></strong></p>
                            <p>
                                Stato: 
                                <span class="status-badge status-<?= str_replace(' ', '-', strtolower(
                                    $ordine['info']['stato_ordine'] == 'attivo' ? 'in-lavorazione' : 
                                    $ordine['info']['stato_ordine']
                                )) ?>">
                                    <?php 
                                    switch($ordine['info']['stato_ordine'] ?? ''){
                                        case 'attivo': echo 'In lavorazione'; break;
                                        case 'spedito': echo 'Spedito'; break;
                                        case 'consegnato': echo 'Consegnato'; break;
                                        case 'annullato': echo 'Annullato'; break;
                                        default: echo 'Sconosciuto';
                                    }
                                    ?>
                                </span>
                            </p>
                        </div>
                    </div>
                    
                    <?php if(($ordine['info']['stato_ordine'] ?? '') == 'attivo'): ?>
                        <div class="cancel-button-container">
                            <a href="orders.php?cancel_id=<?= $ordine['info']['id_ordine'] ?? '' ?>" 
                               class="btn btn-danger"
                               onclick="return confirm('Sei sicuro di voler annullare questo ordine?')">
                               <i class="fas fa-times"></i> Annulla Ordine
                            </a>
                        </div>
                    <?php endif; ?>
                    
                    <div class="table-container">
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
                                <?php foreach ($ordine['prodotti'] as $item): ?>
                                    <tr>
                                        <td class="product-name"><?= $item['nome'] ?? '' ?></td>
                                        <td class="price">€ <?= number_format($item['prezzo_singolo'] ?? 0, 2, ',', '.') ?></td>
                                        <td class="quantity"><?= $item['pezzi'] ?? 0 ?></td>
                                        <td class="subtotal">€ <?= number_format($item['prezzo_tot'] ?? 0, 2, ',', '.') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3">Totale ordine</td>
                                    <td>€ <?= number_format($ordine['totale'], 2, ',', '.') ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-box">
                <i class="fas fa-file-invoice"></i>
                <p>Non hai ancora effettuato ordini</p>
                <a href="index.php" class="btn">
                    <i class="fas fa-shopping-bag"></i> Inizia a fare acquisti
                </a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>