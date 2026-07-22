<?php
include 'config.php';
richiedi_login_venditore();

$page_title = 'Dashboard Venditore';
$p_iva_venditore = $_SESSION['venditore_piva'] ?? '';

// 统计商品总数
$stmt_prodotti = $conn->prepare("SELECT COUNT(*) AS tot_prodotti FROM prodotto WHERE p_iva = ?");
$stmt_prodotti->bind_param("s", $p_iva_venditore);
$stmt_prodotti->execute();
$tot_prodotti = (int)($stmt_prodotti->get_result()->fetch_assoc()['tot_prodotti'] ?? 0);
$stmt_prodotti->close();

// 统计订单总数
$stmt_ordini = $conn->prepare("
    SELECT COUNT(DISTINCT o.id_ordine) AS tot_ordini
    FROM ordine o
    JOIN p_o po ON o.id_ordine = po.id_ordine
    JOIN prodotto p ON po.id_prodotto = p.id_prodotto
    WHERE p.p_iva = ?
");
$stmt_ordini->bind_param("s", $p_iva_venditore);
$stmt_ordini->execute();
$tot_ordini = (int)($stmt_ordini->get_result()->fetch_assoc()['tot_ordini'] ?? 0);
$stmt_ordini->close();

// 统计总营收
$stmt_fatturato = $conn->prepare("
    SELECT SUM(po.prezzo_tot) AS fatturato_totale
    FROM p_o po
    JOIN prodotto p ON po.id_prodotto = p.id_prodotto
    WHERE p.p_iva = ?
");
$stmt_fatturato->bind_param("s", $p_iva_venditore);
$stmt_fatturato->execute();
$fatturato_totale = (float)($stmt_fatturato->get_result()->fetch_assoc()['fatturato_totale'] ?? 0);
$stmt_fatturato->close();

// 待处理订单数
$stmt_pending = $conn->prepare("
    SELECT COUNT(DISTINCT o.id_ordine) AS tot_pending
    FROM ordine o
    JOIN p_o po ON o.id_ordine = po.id_ordine
    JOIN prodotto p ON po.id_prodotto = p.id_prodotto
    WHERE p.p_iva = ? AND o.stato_ordine IN ('attivo', 'in_attesa')
");
$stmt_pending->bind_param("s", $p_iva_venditore);
$stmt_pending->execute();
$tot_pending = (int)($stmt_pending->get_result()->fetch_assoc()['tot_pending'] ?? 0);
$stmt_pending->close();

include 'includes/header.php';
?>

<h2><i class="fas fa-tachometer-alt"></i> Benvenuto, <?= e($_SESSION['venditore_ragione_sociale'] ?? '') ?></h2>
<p class="subtitle">Panoramica del tuo negozio sul nostro e-commerce</p>

<div class="stats-grid">
    <div class="stat-card products">
        <div class="stat-icon">
            <i class="fas fa-box"></i>
        </div>
        <h4>Prodotti in catalogo</h4>
        <h3><?= $tot_prodotti ?></h3>
        <a href="gestisci_prodotti.php" class="btn">
            <i class="fas fa-cog"></i> Gestisci prodotti
        </a>
    </div>

    <div class="stat-card orders">
        <div class="stat-icon">
            <i class="fas fa-shopping-bag"></i>
        </div>
        <h4>Ordini ricevuti</h4>
        <h3><?= $tot_ordini ?></h3>
        <a href="ordini_venditore.php" class="btn">
            <i class="fas fa-eye"></i> Vedi ordini
        </a>
    </div>

    <div class="stat-card pending">
        <div class="stat-icon">
            <i class="fas fa-clock"></i>
        </div>
        <h4>Da evadere</h4>
        <h3><?= $tot_pending ?></h3>
        <a href="ordini_venditore.php" class="btn">
            <i class="fas fa-truck"></i> Gestisci
        </a>
    </div>

    <div class="stat-card revenue">
        <div class="stat-icon">
            <i class="fas fa-euro-sign"></i>
        </div>
        <h4>Fatturato totale</h4>
        <h3>€ <?= number_format($fatturato_totale, 2, ',', '.') ?></h3>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
