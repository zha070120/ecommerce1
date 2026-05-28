<?php
// 引入数据库连接、会话权限通用配置文件
include 'config.php';

// 校验客户登录状态，未登录自动拦截跳转登录页
richiedi_login_cliente();
// 安全读取会话中的用户邮箱，默认空值防止报错
$user_email = $_SESSION['user_email'] ?? '';
// 定义订单操作错误提示变量
$messaggio_errore = '';

// ===================== 订单取消业务逻辑 =====================
// 接收地址栏取消订单参数，判断参数合法格式
if (isset($_GET['cancel_id']) && is_numeric($_GET['cancel_id'])) {
    // 强制转为整型，过滤非法参数
    $order_id = (int)$_GET['cancel_id'];
    // 开启数据库事务，保证库存恢复、订单状态修改原子一致性
    $conn->begin_transaction();

    try {
        // 校验订单归属权与当前订单状态，防止越权取消他人订单
        $stmt_check = $conn->prepare("SELECT stato_ordine FROM ordine WHERE id_ordine = ? AND email = ?");
        $stmt_check->bind_param("is", $order_id, $user_email);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        // 未查询到对应订单，抛出异常
        if ($result_check->num_rows === 0) {
            throw new Exception("Ordine non valido");
        }

        // 获取订单状态，仅活跃状态订单允许取消
        $ordine = $result_check->fetch_assoc();
        if ($ordine['stato_ordine'] !== 'attivo') {
            throw new Exception("Non puoi annullare questo ordine");
        }
        $stmt_check->close();

        // 查询该订单下所有购买商品及对应数量
        $stmt_items = $conn->prepare("SELECT id_prodotto, pezzi FROM p_o WHERE id_ordine = ?");
        $stmt_items->bind_param("i", $order_id);
        $stmt_items->execute();
        $items = $stmt_items->get_result();

        // 循环恢复商品原有库存数量
        $stmt_update_stock = $conn->prepare("UPDATE prodotto SET quantita_disponibile = quantita_disponibile + ? WHERE id_prodotto = ?");
        while ($item = $items->fetch_assoc()) {
            $stmt_update_stock->bind_param("ii", $item['pezzi'], $item['id_prodotto']);
            $stmt_update_stock->execute();
        }
        $stmt_items->close();
        $stmt_update_stock->close();

        // 将订单状态修改为已取消
        $stmt_cancel = $conn->prepare("UPDATE ordine SET stato_ordine = 'annullato' WHERE id_ordine = ?");
        $stmt_cancel->bind_param("i", $order_id);
        $stmt_cancel->execute();
        $stmt_cancel->close();

        // 所有操作无异常，提交事务正式写入数据
        $conn->commit();
        // 跳转订单页并携带取消成功标识
        header("Location: orders.php?success=2");
        exit;
    } catch (Exception $e) {
        // 出现任意错误，回滚所有数据库操作，避免数据错乱
        $conn->rollback();
        // 捕获异常信息赋值给错误提示变量
        $messaggio_errore = $e->getMessage();
    }
}

// ===================== 一次性联表查询当前用户全部订单 =====================
// 关联订单表、订单商品明细表、商品表，一次性查询所有数据，优化N+1查询性能问题
$stmt_ordini = $conn->prepare("
    SELECT o.*, p.nome, po.pezzi, po.prezzo_singolo, po.prezzo_tot
    FROM ordine o
    LEFT JOIN p_o po ON o.id_ordine = po.id_ordine
    LEFT JOIN prodotto p ON po.id_prodotto = p.id_prodotto
    WHERE o.email = ?
    ORDER BY o.id_ordine DESC
");
// 绑定用户邮箱查询条件
$stmt_ordini->bind_param("s", $user_email);
$stmt_ordini->execute();
$result_ordini = $stmt_ordini->get_result();

// ===================== 订单数据分组整理 =====================
// 二维数组存储分组后的订单数据，按订单ID归类商品
$ordini_raggruppati = [];
while ($row = $result_ordini->fetch_assoc()) {
    $id_ordine = $row['id_ordine'] ?? 0;
    // 新订单初始化结构：订单基础信息、商品列表、订单总金额
    if (!isset($ordini_raggruppati[$id_ordine])) {
        $ordini_raggruppati[$id_ordine] = [
            'info' => $row,
            'prodotti' => [],
            'totale' => 0
        ];
    }
    // 商品数据非空则加入对应订单，累加订单总金额
    if (!empty($row['nome'])) {
        $ordini_raggruppati[$id_ordine]['prodotti'][] = $row;
        $ordini_raggruppati[$id_ordine]['totale'] += $row['prezzo_tot'] ?? 0;
    }
}
// 关闭查询语句，释放数据库资源
$stmt_ordini->close();
?>

<!-- 个人订单页面HTML结构 -->
<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <!-- 移动端自适应布局适配 -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- 网页标题 -->
    <title>I miei ordini - E-commerce Doubao</title>
    <!-- 引入字体图标样式库 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- 引入项目全局样式文件 -->
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
                <!-- 当前订单页面导航高亮 -->
                <a href="orders.php" style="border-bottom: 2px solid #fef3c7;"><i class="fas fa-file-invoice"></i> I miei ordini</a>
                <!-- 展示登录用户名 -->
                <span><i class="fas fa-user"></i> Ciao, <?= $_SESSION['user_name'] ?? 'Utente' ?></span>
                <!-- 退出登录按钮 -->
                <a href="logout.php" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </div>
    </header>

    <!-- 页面主体内容容器 -->
    <main class="container">
        <h2><i class="fas fa-file-invoice"></i> I miei ordini</h2>

        <!-- 错误操作提示弹窗 -->
        <?php if ($messaggio_errore): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> <?= $messaggio_errore ?>
            </div>
        <?php endif; ?>
        <!-- 下单成功提示 -->
        <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> Ordine completato con successo!
            </div>
        <?php endif; ?>
        <!-- 订单取消成功提示 -->
        <?php if (isset($_GET['success']) && $_GET['success'] == 2): ?>
            <div class="alert alert-success">
                <i class="fas fa-undo"></i> Ordine annullato con successo!
            </div>
        <?php endif; ?>

        <!-- 判断是否存在订单数据 -->
        <?php if (!empty($ordini_raggruppati)): ?>
            <!-- 循环遍历分组后的所有订单 -->
            <?php foreach ($ordini_raggruppati as $ordine): ?>
                <!-- 单个订单卡片容器 -->
                <div class="order-card">
                    <!-- 订单头部编号、日期、状态信息 -->
                    <div class="order-header">
                        <h3><i class="fas fa-receipt"></i> Ordine #<?= $ordine['info']['id_ordine'] ?? '' ?></h3>
                        <div class="order-info">
                            <!-- 格式化订单日期展示 -->
                            <p><i class="fas fa-calendar-alt"></i> Data: <strong><?= date('d/m/Y', strtotime($ordine['info']['data_ordine'] ?? '')) ?></strong></p>
                            <p>
                                Stato:
                                <!-- 根据订单状态绑定样式类，区分不同状态外观 -->
                                <span class="status-badge status-<?= str_replace(' ', '-', strtolower(
                                                                        $ordine['info']['stato_ordine'] == 'attivo' ? 'in-lavorazione' :
                                                                            $ordine['info']['stato_ordine']
                                                                    )) ?>">
                                    <?php
                                    // 状态文本翻译展示
                                    switch ($ordine['info']['stato_ordine'] ?? '') {
                                        case 'attivo':
                                            echo 'In lavorazione';
                                            break;
                                        case 'spedito':
                                            echo 'Spedito';
                                            break;
                                        case 'consegnato':
                                            echo 'Consegnato';
                                            break;
                                        case 'annullato':
                                            echo 'Annullato';
                                            break;
                                        default:
                                            echo 'Sconosciuto';
                                    }
                                    ?>
                                </span>
                            </p>
                        </div>
                    </div>

                    <!-- 仅处理中订单显示取消按钮 -->
                    <?php if (($ordine['info']['stato_ordine'] ?? '') == 'attivo'): ?>
                        <div class="cancel-button-container">
                            <!-- 取消订单链接，附带弹窗二次确认防止误操作 -->
                            <a href="orders.php?cancel_id=<?= $ordine['info']['id_ordine'] ?? '' ?>"
                                class="btn btn-danger"
                                onclick="return confirm('Sei sicuro di voler annullare questo ordine?')">
                                <i class="fas fa-times"></i> Annulla Ordine
                            </a>
                        </div>
                    <?php endif; ?>

                    <!-- 订单商品明细表格 -->
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
                                <!-- 循环渲染当前订单内所有商品 -->
                                <?php foreach ($ordine['prodotti'] as $item): ?>
                                    <tr>
                                        <td class="product-name"><?= $item['nome'] ?? '' ?></td>
                                        <!-- 格式化欧元金额展示 -->
                                        <td class="price">€ <?= number_format($item['prezzo_singolo'] ?? 0, 2, ',', '.') ?></td>
                                        <td class="quantity"><?= $item['pezzi'] ?? 0 ?></td>
                                        <td class="subtotal">€ <?= number_format($item['prezzo_tot'] ?? 0, 2, ',', '.') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <!-- 订单合计金额 -->
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
            <!-- 暂无订单空白提示界面 -->
            <div class="empty-box">
                <i class="fas fa-file-invoice"></i>
                <p>Non hai ancora effettuato ordini</p>
                <a href="index.php" class="btn">
                    <i class="fas fa-shopping-bag"></i> Inizia a fare acquisti
                </a>
            </div>
        <?php endif; ?>
    </main>
</body>

</html>