<?php include 'config.php';

// 验证用户登录状态
if (!isset($_SESSION['user_email'])) {
    header("Location: login.php");
    exit;
}

$cart_id = $_SESSION['cart_id'];
$total = 0;

// 添加商品到购物车（预处理语句 防SQL注入）
if (isset($_POST['add_to_cart'])) {
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);

    // 检查商品是否已在购物车
    $stmt_check = $conn->prepare("SELECT * FROM p_c WHERE id_prodotto = ? AND id_carello = ?");
    $stmt_check->bind_param("ii", $product_id, $cart_id);
    $stmt_check->execute();
    $check = $stmt_check->get_result();

    if ($check->num_rows > 0) {
        // 更新商品数量
        $stmt_update = $conn->prepare("UPDATE p_c SET pezzi = pezzi + ? WHERE id_prodotto = ? AND id_carello = ?");
        $stmt_update->bind_param("iii", $quantity, $product_id, $cart_id);
        $stmt_update->execute();
        $stmt_update->close();
    } else {
        // 新增商品到购物车
        $stmt_insert = $conn->prepare("INSERT INTO p_c (id_prodotto, id_carello, pezzi) VALUES (?, ?, ?)");
        $stmt_insert->bind_param("iii", $product_id, $cart_id, $quantity);
        $stmt_insert->execute();
        $stmt_insert->close();
    }
    $stmt_check->close();

    header("Location: cart.php");
    exit;
}

// 移除购物车商品（预处理语句 防SQL注入）
if (isset($_POST['remove_from_cart'])) {
    $product_id = intval($_POST['product_id']);

    $stmt_delete = $conn->prepare("DELETE FROM p_c WHERE id_prodotto = ? AND id_carello = ?");
    $stmt_delete->bind_param("ii", $product_id, $cart_id);
    $stmt_delete->execute();
    $stmt_delete->close();

    header("Location: cart.php");
    exit;
}

// 查询购物车商品（预处理语句 防SQL注入）
$stmt_cart = $conn->prepare("
    SELECT pc.*, p.nome, p.prezzo 
    FROM p_c pc 
    JOIN prodotto p ON pc.id_prodotto = p.id_prodotto 
    WHERE pc.id_carello = ?
");
$stmt_cart->bind_param("i", $cart_id);
$stmt_cart->execute();
$cart_items = $stmt_cart->get_result();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrello - E-commerce Doubao</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* 全局样式重置与基础设置（与首页完全一致） */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            /* 标准化配色系统（与首页完全相同） */
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

        /* 导航栏样式（与首页完全一致） */
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

        /* 按钮样式（与首页完全一致） */
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

        /* 标题样式（与首页完全一致） */
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

        /* 购物车表格专属美化 */
        .cart-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background-color: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            margin-bottom: 2rem;
        }

        .cart-table th {
            background-color: var(--primary);
            color: white;
            padding: 1rem 1.5rem;
            text-align: left;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .cart-table td {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--gray-200);
            vertical-align: middle;
        }

        .cart-table tbody tr {
            transition: background-color 0.2s ease;
        }

        .cart-table tbody tr:hover {
            background-color: var(--gray-50);
        }

        .cart-table tbody tr:last-child td {
            border-bottom: none;
        }

        .cart-table tfoot {
            background-color: var(--gray-50);
            font-size: 1.1rem;
        }

        .cart-table tfoot td {
            padding: 1.25rem 1.5rem;
            font-weight: 600;
            border-top: 2px solid var(--gray-200);
        }

        .cart-table tfoot strong {
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

        .remove-btn {
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
        }

        /* 空购物车状态美化 */
        .empty-cart {
            text-align: center;
            padding: 5rem 2rem;
            background-color: white;
            border-radius: 12px;
            box-shadow: var(--shadow-sm);
        }

        .empty-cart i {
            font-size: 5rem;
            color: var(--gray-300);
            margin-bottom: 1.5rem;
        }

        .empty-cart p {
            font-size: 1.25rem;
            color: var(--gray-600);
            margin-bottom: 2rem;
        }

        .empty-cart a {
            display: inline-block;
        }

        /* 结账按钮容器 */
        .checkout-container {
            text-align: right;
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
            .cart-table {
                display: block;
                overflow-x: auto;
            }

            .cart-table th,
            .cart-table td {
                padding: 1rem;
                white-space: nowrap;
            }

            .checkout-container {
                text-align: center;
            }
        }

        @media (max-width: 480px) {
            .btn {
                padding: 0.6rem 1.2rem;
                font-size: 0.9rem;
            }

            .empty-cart {
                padding: 3rem 1.5rem;
            }

            .empty-cart i {
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
                <a href="cart.php" style="border-bottom: 2px solid #fef3c7;"><i class="fas fa-shopping-cart"></i> Carrello</a>
                <a href="orders.php"><i class="fas fa-file-invoice"></i> I miei ordini</a>
                <span><i class="fas fa-user"></i> Ciao, <?= $_SESSION['user_name'] ?></span>
                <a href="logout.php" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </div>
    </header>

    <div class="container">
        <h2><i class="fas fa-shopping-cart"></i> Il tuo carrello</h2>
        
        <?php if ($cart_items->num_rows > 0): ?>
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Prodotto</th>
                        <th>Prezzo unitario</th>
                        <th>Quantità</th>
                        <th>Totale</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($item = $cart_items->fetch_assoc()): ?>
                        <?php $subtotal = $item['prezzo'] * $item['pezzi']; $total += $subtotal; ?>
                        <tr>
                            <td class="product-name"><?= $item['nome'] ?></td>
                            <td class="price">€ <?= number_format($item['prezzo'], 2, ',', '.') ?></td>
                            <td class="quantity"><?= $item['pezzi'] ?></td>
                            <td class="subtotal">€ <?= number_format($subtotal, 2, ',', '.') ?></td>
                            <td>
                                <form method="POST">
                                    <input type="hidden" name="product_id" value="<?= $item['id_prodotto'] ?>">
                                    <button type="submit" name="remove_from_cart" class="btn btn-danger remove-btn">
                                        <i class="fas fa-trash-alt"></i> Rimuovi
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3"><strong>Totale carrello</strong></td>
                        <td><strong>€ <?= number_format($total, 2, ',', '.') ?></strong></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
            
            <div class="checkout-container">
                <a href="checkout.php" class="btn btn-success">
                    <i class="fas fa-credit-card"></i> Procedi al checkout
                </a>
            </div>
        <?php else: ?>
            <div class="empty-cart">
                <i class="fas fa-shopping-cart"></i>
                <p>Il tuo carrello è vuoto</p>
                <a href="index.php" class="btn">
                    <i class="fas fa-arrow-left"></i> Torna ai prodotti
                </a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
<?php
// 释放数据库资源
$stmt_cart->close();
?>