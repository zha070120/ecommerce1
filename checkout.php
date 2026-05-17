<?php include 'config.php';

// 验证登录
if (!isset($_SESSION['user_email'])) {
    header("Location: login.php");
    exit;
}

$cart_id = $_SESSION['cart_id'];
$user_email = $_SESSION['user_email'];
$error = '';

// 一次性查询购物车商品（优化：避免重复查询）
$stmt_cart = $conn->prepare("
    SELECT pc.*, p.nome, p.prezzo, p.quantita_disponibile 
    FROM p_c pc 
    JOIN prodotto p ON pc.id_prodotto = p.id_prodotto 
    WHERE pc.id_carello = ?
");
$stmt_cart->bind_param("i", $cart_id);
$stmt_cart->execute();
$cart_items = $stmt_cart->get_result();

// 购物车为空直接跳转
if ($cart_items->num_rows == 0) {
    $stmt_cart->close();
    header("Location: cart.php");
    exit;
}

// 提交订单
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $conn->begin_transaction();
    try {
        // 1. 创建订单（预处理语句，防注入）
        $stmt_order = $conn->prepare("INSERT INTO ordine (email, data_ordine, stato_ordine) VALUES (?, CURDATE(), 'attivo')");
        $stmt_order->bind_param("s", $user_email);
        $stmt_order->execute();
        $order_id = $conn->insert_id;
        $stmt_order->close();

        // 重置购物车查询指针
        $cart_items->data_seek(0);
        
        // 2. 循环添加订单商品 + 扣减库存
        while ($item = $cart_items->fetch_assoc()) {
            $product_id = $item['id_prodotto'];
            $quantity = $item['pezzi'];
            $price = $item['prezzo'];
            $total_item = $price * $quantity;

            // 插入订单商品（预处理）
            $stmt_item = $conn->prepare("INSERT INTO p_o (id_prodotto, id_ordine, pezzi, prezzo_singolo, prezzo_tot) VALUES (?, ?, ?, ?, ?)");
            $stmt_item->bind_param("iiidd", $product_id, $order_id, $quantity, $price, $total_item);
            $stmt_item->execute();
            $stmt_item->close();

            // 扣减库存（预处理 + 防超卖）
            $stmt_stock = $conn->prepare("UPDATE prodotto SET quantita_disponibile = quantita_disponibile - ? WHERE id_prodotto = ? AND quantita_disponibile >= ?");
            $stmt_stock->bind_param("iii", $quantity, $product_id, $quantity);
            $stmt_stock->execute();
            
            // 库存不足抛出异常
            if ($stmt_stock->affected_rows === 0) {
                throw new Exception("Prodotto '" . $item['nome'] . "' ha finito le scorte!");
            }
            $stmt_stock->close();
        }

        // 3. 清空购物车（预处理）
        $stmt_clear = $conn->prepare("DELETE FROM p_c WHERE id_carello = ?");
        $stmt_clear->bind_param("i", $cart_id);
        $stmt_clear->execute();
        $stmt_clear->close();

        // 提交事务
        $conn->commit();
        header("Location: orders.php?success=1");
        exit;

    } catch (Exception $e) {
        // 失败回滚
        $conn->rollback();
        $error = "Errore durante l'ordine: " . $e->getMessage();
    }
}

// 计算订单总价
$total = 0;
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - E-commerce Doubao</title>
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

        .btn-secondary {
            background-color: var(--gray-600);
        }

        .btn-secondary:hover {
            background-color: var(--gray-700);
            box-shadow: 0 4px 12px rgba(75, 85, 99, 0.3);
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

        /* 错误提示样式 */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            font-weight: 500;
        }

        .alert-danger {
            background-color: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .alert-danger i {
            margin-right: 0.5rem;
        }

        /* 订单表格美化（与购物车表格风格一致） */
        .order-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background-color: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            margin-bottom: 2rem;
        }

        .order-table th {
            background-color: var(--primary);
            color: white;
            padding: 1rem 1.5rem;
            text-align: left;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .order-table td {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--gray-200);
            vertical-align: middle;
        }

        .order-table tbody tr {
            transition: background-color 0.2s ease;
        }

        .order-table tbody tr:hover {
            background-color: var(--gray-50);
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
            font-weight: 600;
            border-top: 2px solid var(--gray-200);
        }

        .order-table tfoot strong {
            color: var(--gray-800);
            font-size: 1.2rem;
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
            font-size: 1.1rem;
        }

        /* 结账按钮容器 */
        .checkout-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-bottom: 4rem;
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

            .checkout-actions {
                flex-direction: column;
                align-items: stretch;
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
            <h1><i class="fas fa-shopping-bag"></i> E-commerce Doubao</h1>
            <nav>
                <a href="index.php"><i class="fas fa-home"></i> Home</a>
                <a href="cart.php"><i class="fas fa-shopping-cart"></i> Carrello</a>
                <a href="orders.php"><i class="fas fa-file-invoice"></i> I miei ordini</a>
                <span><i class="fas fa-user"></i> Ciao, <?= $_SESSION['user_name'] ?></span>
                <a href="logout.php" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </div>
    </header>

    <div class="container">
        <h2><i class="fas fa-credit-card"></i> Conferma ordine</h2>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> <?= $error ?>
            </div>
        <?php endif; ?>

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
                <?php 
                $cart_items->data_seek(0);
                while ($item = $cart_items->fetch_assoc()): 
                    $subtotal = $item['prezzo'] * $item['pezzi']; 
                    $total += $subtotal; 
                ?>
                    <tr>
                        <td class="product-name"><?= $item['nome'] ?></td>
                        <td class="price">€ <?= number_format($item['prezzo'], 2, ',', '.') ?></td>
                        <td class="quantity"><?= $item['pezzi'] ?></td>
                        <td class="subtotal">€ <?= number_format($subtotal, 2, ',', '.') ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3"><strong>Totale ordine</strong></td>
                    <td><strong>€ <?= number_format($total, 2, ',', '.') ?></strong></td>
                </tr>
            </tfoot>
        </table>

        <form method="POST" class="checkout-actions">
            <a href="cart.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Annulla
            </a>
            <button type="submit" class="btn btn-success">
                <i class="fas fa-check-circle"></i> Conferma e paga
            </button>
        </form>
    </div>
</body>
</html>
<?php
// 释放资源
$stmt_cart->close();
?>