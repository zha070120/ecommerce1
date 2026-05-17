<?php
include 'config.php';
richiedi_login_venditore();

// Recupera dati per la dashboard
$p_iva_venditore = $_SESSION['venditore_piva'] ?? '';
// 防护：会话缺失自动跳转登录
if (empty($p_iva_venditore)) {
    header("Location: login_venditore.php");
    exit;
}

// Numero di prodotti del venditore
$stmt_prodotti = $conn->prepare("SELECT COUNT(*) AS tot_prodotti FROM prodotto WHERE p_iva = ?");
$stmt_prodotti->bind_param("s", $p_iva_venditore);
$stmt_prodotti->execute();
$res_prodotti = $stmt_prodotti->get_result();
$tot_prodotti = (int)($res_prodotti->fetch_assoc()['tot_prodotti'] ?? 0);
$stmt_prodotti->close();

// Numero di ordini con prodotti del venditore
$stmt_ordini = $conn->prepare("
    SELECT COUNT(DISTINCT o.id_ordine) AS tot_ordini
    FROM ordine o
    JOIN p_o po ON o.id_ordine = po.id_ordine
    JOIN prodotto p ON po.id_prodotto = p.id_prodotto
    WHERE p.p_iva = ?
");
$stmt_ordini->bind_param("s", $p_iva_venditore);
$stmt_ordini->execute();
$res_ordini = $stmt_ordini->get_result();
$tot_ordini = (int)($res_ordini->fetch_assoc()['tot_ordini'] ?? 0);
$stmt_ordini->close();

// Fatturato totale
$stmt_fatturato = $conn->prepare("
    SELECT SUM(po.prezzo_tot) AS fatturato_totale
    FROM p_o po
    JOIN prodotto p ON po.id_prodotto = p.id_prodotto
    WHERE p.p_iva = ?
");
$stmt_fatturato->bind_param("s", $p_iva_venditore);
$stmt_fatturato->execute();
$res_fatturato = $stmt_fatturato->get_result();
$fatturato_totale = (float)($res_fatturato->fetch_assoc()['fatturato_totale'] ?? 0);
$stmt_fatturato->close();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Venditore - E-commerce Doubao</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    
</head>
<body>
    <header>
        <div class="container">
            <h1><i class="fas fa-store"></i> Area Venditori</h1>
            <nav>
                <span><i class="fas fa-user"></i> Ciao, <?= $_SESSION['venditore_ragione_sociale'] ?? '' ?></span>
                <a href="dashboard_venditore.php" style="border-bottom: 2px solid #fef3c7;"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="gestisci_prodotti.php"><i class="fas fa-box"></i> Prodotti</a>
                <a href="ordini_venditore.php"><i class="fas fa-file-invoice"></i> Ordini</a>
                <a href="profilo_venditore.php"><i class="fas fa-user-cog"></i> Profilo</a>
                <a href="logout.php" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </div>
    </header>

    <div class="container">
        <h2><i class="fas fa-tachometer-alt"></i> Benvenuto, <?= $_SESSION['venditore_ragione_sociale'] ?? '' ?></h2>
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
            
            <div class="stat-card revenue">
                <div class="stat-icon">
                    <i class="fas fa-euro-sign"></i>
                </div>
                <h4>Fatturato totale</h4>
                <h3>€ <?= number_format($fatturato_totale, 2, ',', '.') ?></h3>
            </div>
        </div>
    </div>
</body>
</html>