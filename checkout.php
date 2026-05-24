<?php
// 引入数据库连接、会话权限公共配置文件
include 'config.php';

// 校验客户登录状态，未登录自动跳转登录页面，禁止匿名访问结账页
richiedi_login_cliente();

// 从会话中获取当前用户购物车ID、登录邮箱
$cart_id = $_SESSION['cart_id'];
$user_email = $_SESSION['user_email'];
// 定义订单操作错误信息存储变量
$error = '';

// ========== 一次性联表查询当前用户购物车商品 ==========
// 关联购物车表与商品表，获取商品名称、价格、库存等完整信息
$stmt_cart = $conn->prepare("
    SELECT pc.*, p.nome, p.prezzo, p.quantita_disponibile 
    FROM p_c pc 
    JOIN prodotto p ON pc.id_prodotto = p.id_prodotto 
    WHERE pc.id_carello = ?
");
// 绑定购物车ID整型参数
$stmt_cart->bind_param("i", $cart_id);
// 执行查询语句
$stmt_cart->execute();
// 获取商品结果集
$cart_items = $stmt_cart->get_result();

// 购物车无商品，直接跳转回购物车页面，无法下单
if ($cart_items->num_rows == 0) {
    $stmt_cart->close();
    header("Location: cart.php");
    exit;
}

// ========== 表单提交订单核心逻辑 ==========
// 判断页面以POST方式提交订单
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 开启数据库事务，保证下单、扣库存、清空购物车操作原子性
    $conn->begin_transaction();
    try {
        // 1. 新增主订单记录，记录下单用户、下单日期、初始订单状态
        $stmt_order = $conn->prepare("INSERT INTO ordine (email, data_ordine, stato_ordine) VALUES (?, CURDATE(), 'attivo')");
        $stmt_order->bind_param("s", $user_email);
        $stmt_order->execute();
        // 获取新生成的订单自增编号
        $order_id = $conn->insert_id;
        $stmt_order->close();

        // 重置结果集读取指针，从头遍历购物车商品
        $cart_items->data_seek(0);
        
        // 2. 循环遍历商品，生成订单明细并扣减商品库存
        while ($item = $cart_items->fetch_assoc()) {
            $product_id = $item['id_prodotto'];  // 商品编号
            $quantity = $item['pezzi'];          // 购买数量
            $price = $item['prezzo'];            // 商品单价
            $total_item = $price * $quantity;    // 单品合计金额

            // 插入订单明细表数据，记录每件下单商品信息
            $stmt_item = $conn->prepare("INSERT INTO p_o (id_prodotto, id_ordine, pezzi, prezzo_singolo, prezzo_tot) VALUES (?, ?, ?, ?, ?)");
            $stmt_item->bind_param("iiidd", $product_id, $order_id, $quantity, $price, $total_item);
            $stmt_item->execute();
            $stmt_item->close();

            // 扣减商品库存，校验库存充足才允许扣减，防止超卖
            $stmt_stock = $conn->prepare("UPDATE prodotto SET quantita_disponibile = quantita_disponibile - ? WHERE id_prodotto = ? AND quantita_disponibile >= ?");
            $stmt_stock->bind_param("iii", $quantity, $product_id, $quantity);
            $stmt_stock->execute();
            
            // 影响行数为0说明库存不足，抛出异常终止下单流程
            if ($stmt_stock->affected_rows === 0) {
                throw new Exception("Prodotto '" . $item['nome'] . "' ha finito le scorte!");
            }
            $stmt_stock->close();
        }

        // 3. 下单完成，清空当前用户购物车内所有商品
        $stmt_clear = $conn->prepare("DELETE FROM p_c WHERE id_carello = ?");
        $stmt_clear->bind_param("i", $cart_id);
        $stmt_clear->execute();
        $stmt_clear->close();

        // 所有操作无误，提交事务，数据正式写入数据库
        $conn->commit();
        // 跳转我的订单页面，携带下单成功标识
        header("Location: orders.php?success=1");
        exit;

    } catch (Exception $e) {
        // 出现任意异常，回滚事务，撤销所有数据库操作，保证数据一致性
        $conn->rollback();
        // 捕获异常提示信息
        $error = "Errore durante l'ordine: " . $e->getMessage();
    }
}

// 初始化订单总金额变量
$total = 0;
?>

<!-- 结账确认页面HTML结构 -->
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <!-- 移动端自适应布局适配 -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - E-commerce Doubao</title>
    <!-- 引入字体图标样式库 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- 引入项目公共样式文件 -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- 网站顶部导航栏 -->
    <header>
        <div class="container">
            <h1><i class="fas fa-shopping-bag"></i> E-commerce Doubao</h1>
            <nav>
                <a href="index.php"><i class="fas fa-home"></i> Home</a>
                <a href="cart.php"><i class="fas fa-shopping-cart"></i> Carrello</a>
                <a href="orders.php"><i class="fas fa-file-invoice"></i> I miei ordini</a>
                <!-- 展示当前登录用户名 -->
                <span><i class="fas fa-user"></i> Ciao, <?= $_SESSION['user_name'] ?></span>
                <a href="logout.php" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </div>
    </header>

    <!-- 页面主体内容区域 -->
    <main class="container">
        <h2><i class="fas fa-credit-card"></i> Conferma ordine</h2>
        
        <!-- 订单操作错误提示弹窗 -->
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <!-- 订单商品清单表格 -->
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
                // 重置结果集指针，循环遍历渲染商品列表
                $cart_items->data_seek(0);
                while ($item = $cart_items->fetch_assoc()): 
                    // 计算单品小计金额
                    $subtotal = $item['prezzo'] * $item['pezzi']; 
                    // 累加计算订单总金额
                    $total += $subtotal; 
                ?>
                    <tr>
                        <td class="product-name"><?= $item['nome'] ?></td>
                        <!-- 格式化欧元金额，保留两位小数展示 -->
                        <td class="price">€ <?= number_format($item['prezzo'], 2, ',', '.') ?></td>
                        <td class="quantity"><?= $item['pezzi'] ?></td>
                        <td class="subtotal">€ <?= number_format($subtotal, 2, ',', '.') ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
            <!-- 订单合计统计行 -->
            <tfoot>
                <tr>
                    <td colspan="3"><strong>Totale ordine</strong></td>
                    <td><strong>€ <?= number_format($total, 2, ',', '.') ?></strong></td>
                </tr>
            </tfoot>
        </table>

        <!-- 订单操作按钮表单 -->
        <form method="POST" class="checkout-actions">
            <!-- 返回购物车取消订单 -->
            <a href="cart.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Annulla
            </a>
            <!-- 提交确认下单 -->
            <button type="submit" class="btn btn-success">
                <i class="fas fa-check-circle"></i> Conferma e paga
            </button>
        </form>
    </main>
</body>
</html>
<?php
// 关闭数据库查询语句，释放数据库连接资源
$stmt_cart->close();
?>