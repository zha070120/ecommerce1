<?php
include 'config.php';
richiedi_login_venditore();
verify_csrf();

$page_title = 'Ordini Venditore';
$p_iva_venditore = $_SESSION['venditore_piva'] ?? '';
$messaggio = '';
$errore = '';

$stati_consentiti = ['in_attesa', 'attivo', 'spedito', 'consegnato', 'annullato'];
$stati_non_modificabili = ['consegnato', 'annullato'];

// ===================== 更新订单状态 =====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aggiorna_stato'])) {
    $id_ordine = intval($_POST['id_ordine']);
    $nuovo_stato = trim(strtolower($_POST['stato_ordine']));

    if (!in_array($nuovo_stato, $stati_consentiti, true)) {
        $errore = "Stato ordine non valido!";
    } else {
        $stmt_stato = $conn->prepare("SELECT stato_ordine FROM ordine WHERE id_ordine = ?");
        $stmt_stato->bind_param("i", $id_ordine);
        $stmt_stato->execute();
        $row_stato = $stmt_stato->get_result()->fetch_assoc();
        $stmt_stato->close();

        if (in_array($row_stato['stato_ordine'] ?? '', $stati_non_modificabili, true)) {
            $errore = "Impossibile modificare: ordine già consegnato o annullato!";
        } else {
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
                $stmt_update = $conn->prepare("UPDATE ordine SET stato_ordine = ? WHERE id_ordine = ?");
                $stmt_update->bind_param("si", $nuovo_stato, $id_ordine);
                if ($stmt_update->execute()) {
                    $messaggio = "Stato dell'ordine #$id_ordine aggiornato con successo!";
                } else {
                    $errore = "Errore durante l'aggiornamento.";
                }
                $stmt_update->close();
            } else {
                $errore = "Non sei autorizzato a modificare questo ordine!";
            }
        }
    }
}

// ===================== 查询订单列表 =====================
$stmt_ordini = $conn->prepare("
    SELECT DISTINCT o.id_ordine, o.data_ordine, o.stato_ordine, o.indirizzo_spedizione, o.citta, o.cap,
           c.nome, c.cognome, c.email
    FROM ordine o
    JOIN cliente c ON o.email = c.email
    JOIN p_o po ON o.id_ordine = po.id_ordine
    JOIN prodotto p ON po.id_prodotto = p.id_prodotto
    WHERE p.p_iva = ?
    ORDER BY o.id_ordine DESC
");
$stmt_ordini->bind_param("s", $p_iva_venditore);
$stmt_ordini->execute();
$ordini = $stmt_ordini->get_result();
$stmt_ordini->close();

// 状态标签映射
function stato_label_vendor($stato) {
    $map = [
        'in_attesa' => 'In attesa',
        'attivo' => 'In lavorazione',
        'spedito' => 'Spedito',
        'consegnato' => 'Consegnato',
        'annullato' => 'Annullato',
    ];
    return $map[$stato] ?? ucfirst($stato);
}

include 'includes/header.php';
?>

<h2><i class="fas fa-shopping-bag"></i> Ordini Ricevuti</h2>

<?php if ($messaggio): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> <?= e($messaggio) ?>
    </div>
<?php endif; ?>
<?php if ($errore): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i> <?= e($errore) ?>
    </div>
<?php endif; ?>

<?php if ($ordini->num_rows > 0): ?>
    <?php while ($ordine = $ordini->fetch_assoc()): ?>
        <?php $blocca_modifica = in_array($ordine['stato_ordine'], $stati_non_modificabili); ?>
        <div class="order-card">
            <div class="order-header">
                <h3><i class="fas fa-receipt"></i> Ordine #<?= $ordine['id_ordine'] ?></h3>
                <div class="order-info">
                    <p><i class="fas fa-calendar-alt"></i> <strong>Data:</strong> <?= date('d/m/Y', strtotime($ordine['data_ordine'])) ?></p>
                </div>
                <div class="order-info">
                    <p>
                        <strong>Stato attuale:</strong>
                        <span class="status-badge status-<?= $ordine['stato_ordine'] ?>">
                            <?= stato_label_vendor($ordine['stato_ordine']) ?>
                        </span>
                    </p>
                </div>
                <div class="order-info">
                    <p><i class="fas fa-user"></i> <strong>Cliente:</strong> <?= e($ordine['nome']) ?> <?= e($ordine['cognome']) ?></p>
                    <p><i class="fas fa-envelope"></i> <?= e($ordine['email']) ?></p>
                </div>
                <?php if (!empty($ordine['indirizzo_spedizione'])): ?>
                    <div class="order-info">
                        <p><i class="fas fa-map-marker-alt"></i>
                            <strong>Indirizzo:</strong>
                            <?= e($ordine['indirizzo_spedizione']) ?>, <?= e($ordine['cap']) ?> <?= e($ordine['citta']) ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($blocca_modifica): ?>
                <div class="non-modificabile">
                    <i class="fas fa-lock"></i> Ordine non modificabile
                </div>
            <?php else: ?>
                <form method="POST" class="status-update">
                    <?php csrf_field(); ?>
                    <input type="hidden" name="id_ordine" value="<?= $ordine['id_ordine'] ?>">
                    <label><i class="fas fa-sync-alt"></i> Nuovo stato:</label>
                    <select name="stato_ordine" required>
                        <option value="in_attesa" <?= $ordine['stato_ordine'] === 'in_attesa' ? 'selected' : '' ?>>In attesa</option>
                        <option value="attivo" <?= $ordine['stato_ordine'] === 'attivo' ? 'selected' : '' ?>>In lavorazione</option>
                        <option value="spedito" <?= $ordine['stato_ordine'] === 'spedito' ? 'selected' : '' ?>>Spedito</option>
                        <option value="consegnato" <?= $ordine['stato_ordine'] === 'consegnato' ? 'selected' : '' ?>>Consegnato</option>
                        <option value="annullato" <?= $ordine['stato_ordine'] === 'annullato' ? 'selected' : '' ?>>Annullato</option>
                    </select>
                    <button type="submit" name="aggiorna_stato" class="btn btn-success">
                        <i class="fas fa-check"></i> Aggiorna Stato
                    </button>
                </form>
            <?php endif; ?>

            <div class="table-container">
                <table class="order-table">
                    <thead>
                        <tr>
                            <th>Prodotto</th>
                            <th>Prezzo Unitario</th>
                            <th>Quantità</th>
                            <th>Totale Riga</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $id_ordine = $ordine['id_ordine'];
                        $stmt_prodotti_ordine = $conn->prepare("
                            SELECT po.pezzi, po.prezzo_singolo, po.prezzo_tot, p.nome
                            FROM p_o po
                            JOIN prodotto p ON po.id_prodotto = p.id_prodotto
                            WHERE po.id_ordine = ? AND p.p_iva = ?
                        ");
                        $stmt_prodotti_ordine->bind_param("is", $id_ordine, $p_iva_venditore);
                        $stmt_prodotti_ordine->execute();
                        $prodotti_ordine = $stmt_prodotti_ordine->get_result();
                        $totale_ordine = 0;
                        ?>
                        <?php while ($prodotto = $prodotti_ordine->fetch_assoc()): ?>
                            <?php $totale_ordine += $prodotto['prezzo_tot']; ?>
                            <tr>
                                <td class="product-name"><?= e($prodotto['nome']) ?></td>
                                <td class="price">€ <?= number_format($prodotto['prezzo_singolo'], 2, ',', '.') ?></td>
                                <td class="quantity"><?= $prodotto['pezzi'] ?></td>
                                <td class="subtotal">€ <?= number_format($prodotto['prezzo_tot'], 2, ',', '.') ?></td>
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
        </div>
    <?php endwhile; ?>
<?php else: ?>
    <div class="empty-box">
        <i class="fas fa-shopping-bag"></i>
        <p>Non hai ancora ricevuto ordini per i tuoi prodotti.</p>
        <a href="gestisci_prodotti.php" class="btn">
            <i class="fas fa-box"></i> Gestisci i tuoi prodotti
        </a>
    </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
