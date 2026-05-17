<?php include 'config.php';
richiedi_login_venditore();

// 安全获取会话数据，兜底防报错
$p_iva_venditore = $_SESSION['venditore_piva'] ?? '';
$messaggio = '';
$errore = '';

// 允许的订单状态白名单
$stati_consentiti = ['attivo', 'spedito', 'consegnato', 'annullato'];
// 不可修改的状态
$stati_non_modificabili = ['consegnato', 'annullato'];

// 处理订单状态更新
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aggiorna_stato'])) {
    $id_ordine = intval($_POST['id_ordine']);
    $nuovo_stato = trim(strtolower($_POST['stato_ordine']));

    // 校验新状态是否合法
    if (!in_array($nuovo_stato, $stati_consentiti, true)) {
        $errore = "Stato ordine non valido! Stati consentiti: attivo, spedito, consegnato, annullato.";
    } else {
        // 获取订单原始状态
        $stmt_stato = $conn->prepare("SELECT stato_ordine FROM ordine WHERE id_ordine = ?");
        $stmt_stato->bind_param("i", $id_ordine);
        $stmt_stato->execute();
        $row_stato = $stmt_stato->get_result()->fetch_assoc();
        $stmt_stato->close();

        // 阻止修改已完成/取消的订单
        if (in_array($row_stato['stato_ordine'] ?? '', $stati_non_modificabili, true)) {
            $errore = "Impossibile modificare: ordine già consegnato o annullato!";
        } else {
            // 校验订单归属权
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
                // 更新订单状态
                $stmt_update = $conn->prepare("UPDATE ordine SET stato_ordine = ? WHERE id_ordine = ?");
                $stmt_update->bind_param("si", $nuovo_stato, $id_ordine);
                if ($stmt_update->execute()) {
                    $messaggio = "Stato dell'ordine #$id_ordine aggiornato con successo!";
                } else {
                    $errore = "Errore durante l'aggiornamento: " . $conn->error;
                }
                $stmt_update->close();
            } else {
                $errore = "Non sei autorizzato a modificare questo ordine!";
            }
        }
    }
}

// 查询商家所有订单
$stmt_ordini = $conn->prepare("
    SELECT DISTINCT o.id_ordine, o.data_ordine, o.stato_ordine, c.nome, c.cognome, c.email
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
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>I Miei Ordini - Area Venditori</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    
</head>
<body>
    <header>
        <div class="container">
            <h1><i class="fas fa-store"></i> Area Venditori</h1>
            <nav>
                <span><i class="fas fa-user"></i> Ciao, <?= $_SESSION['venditore_ragione_sociale'] ?? 'Utente' ?></span>
                <a href="dashboard_venditore.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="gestisci_prodotti.php"><i class="fas fa-box"></i> Prodotti</a>
                <a href="ordini_venditore.php" style="border-bottom: 2px solid #fef3c7;"><i class="fas fa-file-invoice"></i> Ordini</a>
                <a href="profilo_venditore.php"><i class="fas fa-user-cog"></i> Profilo</a>
                <a href="logout.php" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </div>
    </header>

    <div class="container">
        <h2><i class="fas fa-shopping-bag"></i> Ordini Ricevuti</h2>

        <?php if ($messaggio): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= $messaggio ?>
            </div>
        <?php endif; ?>
        <?php if ($errore): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?= $errore ?>
            </div>
        <?php endif; ?>

        <?php if ($ordini->num_rows > 0): ?>
            <?php while ($ordine = $ordini->fetch_assoc()): ?>
                <?php
                $blocca_modifica = in_array($ordine['stato_ordine'] ?? '', $stati_non_modificabili, true);
                ?>
                <div class="order-card">
                    <div class="order-header">
                        <h3><i class="fas fa-receipt"></i> Ordine #<?= $ordine['id_ordine'] ?? '' ?></h3>
                        
                        <div class="order-info">
                            <p><i class="fas fa-calendar-alt"></i> <strong>Data:</strong> <?= date('d/m/Y', strtotime($ordine['data_ordine'] ?? '')) ?></p>
                        </div>
                        
                        <div class="order-info">
                            <p>
                                <strong>Stato attuale:</strong>
                                <span class="status-badge status-<?= $ordine['stato_ordine'] ?? '' ?>">
                                    <?= ucfirst($ordine['stato_ordine'] ?? '') ?>
                                </span>
                            </p>
                        </div>
                        
                        <div class="order-info">
                            <p><i class="fas fa-user"></i> <strong>Cliente:</strong> <?= $ordine['nome'] ?? '' ?> <?= $ordine['cognome'] ?? '' ?></p>
                            <p><i class="fas fa-envelope"></i> <?= $ordine['email'] ?? '' ?></p>
                        </div>
                    </div>

                    <?php if($blocca_modifica): ?>
                        <div class="non-modificabile">
                            <i class="fas fa-lock"></i> Ordine non modificabile (già consegnato o annullato)
                        </div>
                    <?php else: ?>
                        <form method="POST" class="status-update">
                            <input type="hidden" name="id_ordine" value="<?= $ordine['id_ordine'] ?? '' ?>">
                            <label for="stato_ordine_<?= $ordine['id_ordine'] ?>"><i class="fas fa-sync-alt"></i> Nuovo stato:</label>
                            <select id="stato_ordine_<?= $ordine['id_ordine'] ?>" name="stato_ordine" required>
                                <option value="attivo" <?= ($ordine['stato_ordine'] ?? '') === 'attivo' ? 'selected' : '' ?>>Attivo</option>
                                <option value="spedito" <?= ($ordine['stato_ordine'] ?? '') === 'spedito' ? 'selected' : '' ?>>Spedito</option>
                                <option value="consegnato" <?= ($ordine['stato_ordine'] ?? '') === 'consegnato' ? 'selected' : '' ?>>Consegnato</option>
                                <option value="annullato" <?= ($ordine['stato_ordine'] ?? '') === 'annullato' ? 'selected' : '' ?>>Annullato</option>
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
                                $id_ordine = $ordine['id_ordine'] ?? 0;
                                $stmt_prodotti_ordine = $conn->prepare("
                                    SELECT po.*, p.nome
                                    FROM p_o po
                                    JOIN prodotto p ON po.id_prodotto = p.id_prodotto
                                    WHERE po.id_ordine = ? AND p.p_iva = ?
                                ");
                                $stmt_prodotti_ordine->bind_param("is", $id_ordine, $p_iva_venditore);
                                $stmt_prodotti_ordine->execute();
                                $prodotti_ordine = $stmt_prodotti_ordine->get_result();
                                $totale_ordine = 0; // 强制初始化
                                ?>
                                <?php while ($prodotto = $prodotti_ordine->fetch_assoc()): ?>
                                    <?php $totale_ordine += $prodotto['prezzo_tot'] ?? 0; ?>
                                    <tr>
                                        <td class="product-name"><?= $prodotto['nome'] ?? '' ?></td>
                                        <td class="price">€ <?= number_format($prodotto['prezzo_singolo'] ?? 0, 2, ',', '.') ?></td>
                                        <td class="quantity"><?= $prodotto['pezzi'] ?? 0 ?></td>
                                        <td class="subtotal">€ <?= number_format($prodotto['prezzo_tot'] ?? 0, 2, ',', '.') ?></td>
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
                    </div>
                    <?php $stmt_prodotti_ordine->close(); ?>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-shopping-bag"></i>
                <p>Non hai ancora ricevuto ordini per i tuoi prodotti.</p>
                <a href="gestisci_prodotti.php" class="btn">
                    <i class="fas fa-box"></i> Gestisci i tuoi prodotti
                </a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>