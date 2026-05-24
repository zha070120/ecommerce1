<?php
// 引入数据库连接与公共权限配置文件
include 'config.php';

// 校验客户登录状态，未登录则强制跳转登录页面
richiedi_login_cliente();

// 获取当前登录用户对应的购物车ID
$cart_id = $_SESSION['cart_id'];
// 初始化购物车总金额变量
$total = 0;

// ===================== 添加商品至购物车逻辑 =====================
// 监听添加商品提交动作
if (isset($_POST['add_to_cart'])) {
    // 强制转换为整型，过滤非法提交参数
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);

    // 预处理查询：判断该商品是否已存在于当前用户购物车，防止重复新增
    $stmt_check = $conn->prepare("SELECT * FROM p_c WHERE id_prodotto = ? AND id_carello = ?");
    $stmt_check->bind_param("ii", $product_id, $cart_id);
    $stmt_check->execute();
    $check = $stmt_check->get_result();

    if ($check->num_rows > 0) {
        // 商品已存在：在原有数量基础上累加购买数量
        $stmt_update = $conn->prepare("UPDATE p_c SET pezzi = pezzi + ? WHERE id_prodotto = ? AND id_carello = ?");
        $stmt_update->bind_param("iii", $quantity, $product_id, $cart_id);
        $stmt_update->execute();
        $stmt_update->close();
    } else {
        // 商品不存在：向购物车数据表新增一条记录
        $stmt_insert = $conn->prepare("INSERT INTO p_c (id_prodotto, id_carello, pezzi) VALUES (?, ?, ?)");
        $stmt_insert->bind_param("iii", $product_id, $cart_id, $quantity);
        $stmt_insert->execute();
        $stmt_insert->close();
    }
    // 关闭查询语句，释放数据库资源
    $stmt_check->close();

    // 操作完成刷新购物车页面
    header("Location: cart.php");
    exit;
}

// ===================== 删除购物车商品逻辑 =====================
// 监听移除商品提交请求
if (isset($_POST['remove_from_cart'])) {
    // 获取待删除商品ID并做整型过滤
    $product_id = intval($_POST['product_id']);

    // 预处理删除语句，精准删除当前用户购物车内指定商品
    $stmt_delete = $conn->prepare("DELETE FROM p_c WHERE id_prodotto = ? AND id_carello = ?");
    $stmt_delete->bind_param("ii", $product_id, $cart_id);
    $stmt_delete->execute();
    $stmt_delete->close();

    // 删除后刷新购物车页面
    header("Location: cart.php");
    exit;
}

// ===================== 查询购物车所有商品数据 =====================
// 联表查询：关联购物车表与商品表，获取商品名称、单价等详情信息
$stmt_cart = $conn->prepare("
    SELECT pc.*, p.nome, p.prezzo 
    FROM p_c pc 
    JOIN prodotto p ON pc.id_prodotto = p.id_prodotto 
    WHERE pc.id_carello = ?
");
// 绑定购物车ID查询条件
$stmt_cart->bind_param("i", $cart_id);
$stmt_cart->execute();
// 取回查询结果集
$cart_items = $stmt_cart->get_result();
?>

<!-- 购物车页面整体结构 -->
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <!-- 移动端屏幕自适应适配 -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrello - E-commerce Doubao</title>
    <!-- 引入字体图标样式库 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- 引入项目全局自定义样式 -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- 网站顶部导航栏区域 -->
    <header>
        <div class="container">
            <h1><i class="fas fa-shopping-bag"></i> E-commerce Doubao</h1>
            <nav>
                <a href="index.php"><i class="fas fa-home"></i> Home</a>
                <!-- 当前购物车页面导航高亮标识 -->
                <a href="cart.php" style="border-bottom: 2px solid #fef3c7;"><i class="fas fa-shopping-cart"></i> Carrello</a>
                <a href="orders.php"><i class="fas fa-file-invoice"></i> I miei ordini</a>
                <!-- 展示当前登录客户昵称 -->
                <span><i class="fas fa-user"></i> Ciao, <?= $_SESSION['user_name'] ?></span>
                <!-- 退出登录按钮 -->
                <a href="logout.php" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </div>
    </header>

    <!-- 页面主体内容容器 -->
    <main class="container">
        <h2><i class="fas fa-shopping-cart"></i> Il tuo carrello</h2>

        <!-- 判断购物车是否存在商品 -->
        <?php if ($cart_items->num_rows > 0): ?>
            <!-- 购物车商品数据表格 -->
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
                    <!-- 循环遍历渲染每一件购物车内商品 -->
                    <?php while ($item = $cart_items->fetch_assoc()): ?>
                        <?php
                        // 计算单件商品小计金额
                        $subtotal = $item['prezzo'] * $item['pezzi'];
                        // 累加统计购物车总金额
                        $total += $subtotal;
                        ?>
                        <tr>
                            <td class="product-name"><?= $item['nome'] ?></td>
                            <!-- 格式化欧元金额展示格式 -->
                            <td class="price">€ <?= number_format($item['prezzo'], 2, ',', '.') ?></td>
                            <td class="quantity"><?= $item['pezzi'] ?></td>
                            <td class="subtotal">€ <?= number_format($subtotal, 2, ',', '.') ?></td>
                            <td>
                                <!-- 商品删除提交表单 -->
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
                <!-- 购物车金额合计栏 -->
                <tfoot>
                    <tr>
                        <td colspan="3"><strong>Totale carrello</strong></td>
                        <td><strong>€ <?= number_format($total, 2, ',', '.') ?></strong></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>

            <!-- 跳转订单结算页面按钮 -->
            <div class="checkout-container">
                <a href="checkout.php" class="btn btn-success">
                    <i class="fas fa-credit-card"></i> Procedi al checkout
                </a>
            </div>
        <?php else: ?>
            <!-- 购物车为空时展示提示界面 -->
            <div class="empty-box">
                <i class="fas fa-shopping-cart"></i>
                <p>Il tuo carrello è vuoto</p>
                <a href="index.php" class="btn">
                    <i class="fas fa-arrow-left"></i> Torna ai prodotti
                </a>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
<?php
// 关闭数据库查询语句，释放系统资源
$stmt_cart->close();
?>