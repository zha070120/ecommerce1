<?php
include 'config.php';
richiedi_login_cliente();
verify_csrf();

$page_title = 'I miei ordini';
$user_email = $_SESSION['user_email'] ?? '';
$messaggio_errore = '';

// ===================== 取消订单（POST更安全） =====================
if (isset($_POST['cancel_order'])) {
    $order_id = intval($_POST['order_id']);
    $conn->begin_transaction();

    try {
        $stmt_check = $conn->prepare("SELECT stato_ordine FROM ordine WHERE id_ordine = ? AND email = ?");
        $stmt_check->bind_param("is", $order_id, $user_email);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows === 0) {
            throw new Exception("Ordine non valido");
        }

        $ordine = $result_check->fetch_assoc();
        $stati_cancellabili = ['attivo', 'in_attesa'];
        if (!in_array($ordine['stato_ordine'], $stati_cancellabili)) {
            throw new Exception("Non puoi annullare questo ordine");
        }
        $stmt_check->close();

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

        $stmt_cancel = $conn->prepare("UPDATE ordine SET stato_ordine = 'annullato' WHERE id_ordine = ?");
        $stmt_cancel->bind_param("i", $order_id);
        $stmt_cancel->execute();
        $stmt_cancel->close();

        $conn->commit();
        redirect('orders.php?success=2');
    } catch (Exception $e) {
        $conn->rollback();
        $messaggio_errore = $e->getMessage();
    }
}

// ===================== 查询所有订单 =====================
$stmt_ordini = $conn->prepare("
    SELECT o.id_ordine, o.data_ordine, o.stato_ordine, o.indirizzo_spedizione, o.citta, o.cap,
           p.nome, po.pezzi, po.prezzo_singolo, po.prezzo_tot
    FROM ordine o
    LEFT JOIN p_o po ON o.id_ordine = po.id_ordine
    LEFT JOIN prodotto p ON po.id_prodotto = p.id_prodotto
    WHERE o.email = ?
    ORDER BY o.id_ordine DESC
");
$stmt_ordini->bind_param("s", $user_email);
$stmt_ordini->execute();
$result_ordini = $stmt_ordini->get_result();

// 分组整理
$ordini_raggruppati = [];
while ($row = $result_ordini->fetch_assoc()) {
    $id_ordine = $row['id_ordine'];
    if (!isset($ordini_raggruppati[$id_ordine])) {
        $ordini_raggruppati[$id_ordine] = [
            'info' => $row,
            'prodotti' => [],
            'totale' => 0
        ];
    }
    if (!empty($row['nome'])) {
        $ordini_raggruppati[$id_ordine]['prodotti'][] = $row;
        $ordini_raggruppati[$id_ordine]['totale'] += $row['prezzo_tot'];
    }
}
$stmt_ordini->close();

// 状态翻译映射
function stato_label($stato) {
    $map = [
        'in_attesa' => ['label' => 'In attesa', 'class' => 'in-attesa'],
        'attivo' => ['label' => 'In lavorazione', 'class' => 'in-lavorazione'],
        'spedito' => ['label' => 'Spedito', 'class' => 'spedito'],
        'consegnato' => ['label' => 'Consegnato', 'class' => 'consegnato'],
        'annullato' => ['label' => 'Annullato', 'class' => 'annullato'],
    ];
    return $map[$stato] ?? ['label' => $stato, 'class' => 'sconosciuto'];
}

include 'includes/header.php';
?>

<h2><i class="fas fa-file-invoice"></i> I miei ordini</h2>

<?php if ($messaggio_errore): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-triangle"></i> <?= e($messaggio_errore) ?>
    </div>
<?php endif; ?>

<?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> Ordine completato con successo!
    </div>
<?php endif; ?>

<?php if (isset($_GET['success']) && $_GET['success'] == 2): ?>
    <div class="alert alert-success">
        <i class="fas fa-undo"></i> Ordine annullato con successo!
    </div>
<?php endif; ?>

<?php if (!empty($ordini_raggruppati)): ?>
    <?php foreach ($ordini_raggruppati as $ordine): ?>
        <?php $stato_info = stato_label($ordine['info']['stato_ordine']); ?>
        <div class="order-card">
            <div class="order-header">
                <h3><i class="fas fa-receipt"></i> Ordine #<?= $ordine['info']['id_ordine'] ?></h3>
                <div class="order-info">
                    <p><i class="fas fa-calendar-alt"></i> Data: <strong><?= date('d/m/Y', strtotime($ordine['info']['data_ordine'])) ?></strong></p>
                    <p>
                        Stato:
                        <span class="status-badge status-<?= $stato_info['class'] ?>">
                            <?= $stato_info['label'] ?>
                        </span>
                    </p>
                </div>
            </div>

            <!-- 收货地址 -->
            <?php if (!empty($ordine['info']['indirizzo_spedizione'])): ?>
                <div class="shipping-info">
                    <p><i class="fas fa-map-marker-alt"></i>
                        <strong>Spedito a:</strong>
                        <?= e($ordine['info']['indirizzo_spedizione']) ?>,
                        <?= e($ordine['info']['cap']) ?> <?= e($ordine['info']['citta']) ?>
                    </p>
                </div>
            <?php endif; ?>

            <?php
            $stato_corrente = $ordine['info']['stato_ordine'];
            $puo_cancellare = in_array($stato_corrente, ['attivo', 'in_attesa']);
            ?>
            <?php if ($puo_cancellare): ?>
                <div class="cancel-button-container">
                    <form method="POST" onsubmit="return confirm('Sei sicuro di voler annullare questo ordine?')">
                        <?php csrf_field(); ?>
                        <input type="hidden" name="order_id" value="<?= $ordine['info']['id_ordine'] ?>">
                        <button type="submit" name="cancel_order" class="btn btn-danger">
                            <i class="fas fa-times"></i> Annulla Ordine
                        </button>
                    </form>
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
                                <td class="product-name"><?= e($item['nome']) ?></td>
                                <td class="price">€ <?= number_format($item['prezzo_singolo'], 2, ',', '.') ?></td>
                                <td class="quantity"><?= $item['pezzi'] ?></td>
                                <td class="subtotal">€ <?= number_format($item['prezzo_tot'], 2, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3">Totale ordine</td>
                            <td><strong>€ <?= number_format($ordine['totale'], 2, ',', '.') ?></strong></td>
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

<?php include 'includes/footer.php'; ?>
