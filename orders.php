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
    <style>
        /* 全局样式重置与基础设置（与全站完全一致） */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            /* 标准化配色系统（与全站完全相同） */
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
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
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

        /* 导航栏样式（与全站完全一致） */
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

        /* 按钮样式（与全站完全一致） */
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

        /* 标题样式（与全站完全一致） */
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

        /* 提示信息样式 */
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

        /* 订单卡片专属美化 */
        .order-card {
            background-color: white;
            border-radius: 12px;
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
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
        }

        .order-header h3 {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--gray-800);
            margin: 0;
        }

        .order-info {
            display: flex;
            flex-wrap: wrap;
            gap: 2rem;
            align-items: center;
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
        }

        .status-in-lavorazione {
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

        /* 订单表格美化 */
        .order-table {
            width: 100%;
            border-collapse: collapse;
        }

        .order-table th {
            background-color: var(--gray-100);
            color: var(--gray-700);
            padding: 1rem 1.5rem;
            text-align: right;
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

        /* 取消按钮容器 */
        .cancel-button-container {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--gray-200);
            text-align: right;
        }

        /* 空订单状态美化 */
        .empty-orders {
            text-align: center;
            padding: 5rem 2rem;
            background-color: white;
            border-radius: 12px;
            box-shadow: var(--shadow-sm);
        }

        .empty-orders i {
            font-size: 5rem;
            color: var(--gray-300);
            margin-bottom: 1.5rem;
        }

        .empty-orders p {
            font-size: 1.25rem;
            color: var(--gray-600);
            margin-bottom: 2rem;
        }

        .empty-orders a {
            display: inline-block;
        }

        /* 响应式设计 */
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
                flex-direction: column;
                align-items: flex-start;
            }

            .order-info {
                flex-direction: column;
                gap: 0.5rem;
                align-items: flex-start;
            }

            .cancel-button-container {
                text-align: left;
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

            .empty-orders {
                padding: 3rem 1.5rem;
            }

            .empty-orders i {
                font-size: 4rem;
            }
        }
    </style>
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
            <div class="empty-orders">
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