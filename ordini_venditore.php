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

        .btn-success {
            background-color: var(--success);
        }

        .btn-success:hover {
            background-color: #059669;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        /* 标题样式统一 */
        h2 {
            font-size: 1.875rem;
            margin: 3rem 0 1.5rem;
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

        /* 提示框样式统一 */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .alert-danger {
            background-color: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .alert-success {
            background-color: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        /* 订单卡片美化 */
        .order-card {
            background-color: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            margin-bottom: 2rem;
            transition: all 0.3s ease;
        }

        .order-card:hover {
            box-shadow: var(--shadow-md);
        }

        .order-header {
            background-color: var(--gray-50);
            padding: 1.5rem;
            border-bottom: 1px solid var(--gray-200);
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .order-header h3 {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--gray-800);
            margin: 0;
            grid-column: 1 / -1;
        }

        .order-info {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .order-info p {
            margin: 0;
            color: var(--gray-600);
        }

        .order-info strong {
            color: var(--gray-800);
        }

        /* 订单状态标签 */
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
            display: inline-block;
        }

        .status-attivo {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .status-spedito {
            background-color: #fef3c7;
            color: #92400e;
        }

        .status-consegnato {
            background-color: #dcfce7;
            color: #166534;
        }

        .status-annullato {
            background-color: #fee2e2;
            color: #991b1b;
        }

        /* 状态更新表单 */
        .status-update {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .status-update label {
            font-weight: 500;
            color: var(--gray-700);
        }

        .status-update select {
            padding: 0.75rem 1rem;
            border: 1px solid var(--gray-300);
            border-radius: 8px;
            font-size: 0.95rem;
            background-color: white;
            cursor: pointer;
        }

        .status-update select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .non-modificabile {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--gray-200);
            color: var(--gray-600);
            font-style: italic;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* 订单表格美化 */
        .order-table {
            width: 100%;
            border-collapse: collapse;
        }

        .order-table th {
            background-color: var(--gray-100);
            color: var(--gray-700);
            padding: 1rem 1.5rem;
            text-align: left;
            font-weight: 600;
            font-size: 0.9rem;
            border-bottom: 1px solid var(--gray-200);
        }

        .order-table td {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--gray-200);
            vertical-align: middle;
        }

        .order-table tbody tr:last-child td {
            border-bottom: none;
        }

        .order-table tfoot {
            background-color: var(--gray-50);
            font-size: 1.1rem;
        }

        .order-table tfoot td {
            padding: 1.25rem 1.5rem;
            font-weight: 700;
            border-top: 2px solid var(--gray-200);
        }

        .product-name {
            font-weight: 600;
            color: var(--gray-800);
        }

        .price {
            font-weight: 600;
            color: var(--danger);
        }

        .quantity {
            font-weight: 500;
            text-align: center;
        }

        .subtotal {
            font-weight: 700;
            color: var(--danger);
        }

        /* 空状态美化 */
        .empty-state {
            text-align: center;
            padding: 5rem 2rem;
            background-color: white;
            border-radius: 12px;
            box-shadow: var(--shadow-sm);
        }

        .empty-state i {
            font-size: 5rem;
            color: var(--gray-300);
            margin-bottom: 1.5rem;
        }

        .empty-state p {
            font-size: 1.25rem;
            color: var(--gray-600);
            margin-bottom: 2rem;
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
            .order-header {
                grid-template-columns: 1fr;
            }
            .status-update {
                flex-direction: column;
                align-items: stretch;
            }
            /* 移动端表格适配 */
            .order-table {
                display: block;
                overflow-x: auto;
            }
            .order-table th,
            .order-table td {
                padding: 1rem;
                white-space: nowrap;
            }
        }

        @media (max-width: 480px) {
            .btn {
                padding: 0.6rem 1.2rem;
                font-size: 0.9rem;
            }
            .empty-state {
                padding: 3rem 1.5rem;
            }
            .empty-state i {
                font-size: 4rem;
            }
        }
    </style>
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