<?php include 'config.php'; richiedi_login_venditore();

// Recupera dati per la dashboard
$p_iva_venditore = $_SESSION['venditore_piva'];

// Numero di prodotti del venditore
$stmt_prodotti = $conn->prepare("SELECT COUNT(*) AS tot_prodotti FROM prodotto WHERE p_iva = ?");
$stmt_prodotti->bind_param("s", $p_iva_venditore);
$stmt_prodotti->execute();
$tot_prodotti = $stmt_prodotti->get_result()->fetch_assoc()['tot_prodotti'];
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
$tot_ordini = $stmt_ordini->get_result()->fetch_assoc()['tot_ordini'];
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
$fatturato_totale = $stmt_fatturato->get_result()->fetch_assoc()['fatturato_totale'] ?? 0;
$stmt_fatturato->close();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Venditore - E-commerce Doubao</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 2rem; margin: 2rem 0; }
        .stat-card { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); text-align: center; }
        .stat-card h3 { font-size: 2.5rem; color: #3498db; margin: 1rem 0; }
    </style>
</head>
<body>
    <header>
        <h1>Area Venditori</h1>
        <nav>
            <span>Ciao, <?= $_SESSION['venditore_ragione_sociale'] ?></span>
            <a href="dashboard_venditore.php">Dashboard</a>
            <a href="gestisci_prodotti.php">Prodotti</a>
            <a href="ordini_venditore.php">Ordini</a>
            <a href="profilo_venditore.php">Profilo</a>
            <a href="logout.php" class="btn btn-danger">Logout</a>
        </nav>
    </header>

    <div class="container">
        <h2>Benvenuto, <?= $_SESSION['venditore_ragione_sociale'] ?></h2>
        <p>Panoramica del tuo negozio sul nostro e-commerce</p>

        <div class="stats-grid">
            <div class="stat-card">
                <h4>Prodotti in catalogo</h4>
                <h3><?= $tot_prodotti ?></h3>
                <a href="gestisci_prodotti.php" class="btn">Gestisci prodotti</a>
            </div>
            <div class="stat-card">
                <h4>Ordini ricevuti</h4>
                <h3><?= $tot_ordini ?></h3>
                <a href="ordini_venditore.php" class="btn">Vedi ordini</a>
            </div>
            <div class="stat-card">
                <h4>Fatturato totale</h4>
                <h3>€ <?= number_format($fatturato_totale, 2, ',', '.') ?></h3>
            </div>
        </div>
    </div>
</body>
</html>
