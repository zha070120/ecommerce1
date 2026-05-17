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
    <style>
        /* 全局统一变量 与全站完全一致 */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --secondary: #f97316;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --shadow-sm: 0 1px 2px 0 rgba(0,0,0,0.05);
            --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.1);
        }

        body {
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: var(--gray-800);
            background-color: var(--gray-50);
        }

        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 24px;
        }

        /* 导航栏 全站统一样式 */
        header {
            background: linear-gradient(135deg, var(--primary) 0%, #3b82f6 100%);
            color: white;
            padding: 1rem 0;
            box-shadow: var(--shadow-md);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        header .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 100%;
            padding: 0 40px;
            width: 100%;
        }

        header h1 {
            font-size: 1.75rem;
            font-weight: 700;
            margin: 0;
            white-space: nowrap;
            flex-shrink: 0;
        }

        nav {
            display: flex;
            align-items: center;
            gap: 2rem;
            margin-left: auto;
            flex-shrink: 0;
        }

        nav a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            padding: 0.5rem 0;
            border-bottom: 2px solid transparent;
            white-space: nowrap;
        }

        nav a:hover {
            color: #fef3c7;
            border-bottom: 2px solid #fef3c7;
        }

        /* 通用按钮样式 */
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.95rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            text-align: center;
            box-shadow: var(--shadow-sm);
        }

        .btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        .btn:active {
            transform: translateY(0);
        }

        .btn-danger {
            background-color: var(--danger);
        }

        .btn-danger:hover {
            background-color: #dc2626;
        }

        /* 标题样式统一 */
        h2 {
            font-size: 1.875rem;
            margin: 3rem 0 0.5rem;
            color: var(--gray-800);
            position: relative;
            padding-bottom: 0.75rem;
            font-weight: 700;
        }

        h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 80px;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            border-radius: 2px;
        }

        .subtitle {
            color: var(--gray-600);
            font-size: 1.1rem;
            margin-bottom: 2rem;
        }

        /* 统计卡片网格美化 */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 2rem;
            margin: 2rem 0 4rem;
        }

        .stat-card {
            background: white;
            padding: 2.5rem 2rem;
            border-radius: 16px;
            box-shadow: var(--shadow-sm);
            text-align: center;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
        }

        .stat-card.products::before {
            background-color: var(--primary);
        }

        .stat-card.orders::before {
            background-color: var(--success);
        }

        .stat-card.revenue::before {
            background-color: var(--warning);
        }

        .stat-icon {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 1.75rem;
        }

        .stat-card.products .stat-icon {
            background-color: rgba(37, 99, 235, 0.1);
            color: var(--primary);
        }

        .stat-card.orders .stat-icon {
            background-color: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }

        .stat-card.revenue .stat-icon {
            background-color: rgba(245, 158, 11, 0.1);
            color: var(--warning);
        }

        .stat-card h4 {
            font-size: 1.1rem;
            color: var(--gray-600);
            margin-bottom: 1rem;
            font-weight: 500;
        }

        .stat-card h3 {
            font-size: 2.75rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            line-height: 1.2;
        }

        .stat-card.products h3 {
            color: var(--primary);
        }

        .stat-card.orders h3 {
            color: var(--success);
        }

        .stat-card.revenue h3 {
            color: var(--warning);
        }

        /* 响应式适配 */
        @media (max-width: 768px) {
            header .container {
                flex-direction: column;
                gap: 1rem;
                padding: 0 24px;
            }
            nav {
                margin-left: 0;
                justify-content: center;
                width: 100%;
                flex-wrap: wrap;
            }
            h2 {
                font-size: 1.5rem;
            }
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            .stat-card {
                padding: 2rem 1.5rem;
            }
            .stat-card h3 {
                font-size: 2.25rem;
            }
        }

        @media (max-width: 480px) {
            .btn {
                padding: 0.6rem 1.2rem;
                font-size: 0.9rem;
            }
        }
    </style>
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