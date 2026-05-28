<?php
// 引入数据库连接与公共权限函数文件
include 'config.php';
// 校验卖家登录权限，未登录自动跳转登录页面
richiedi_login_venditore();

// 获取当前登录卖家的税号信息
$p_iva_venditore = $_SESSION['venditore_piva'] ?? '';
// 二次安全校验，税号为空则强制跳转登录页，防止会话异常访问
if (empty($p_iva_venditore)) {
    header("Location: login_venditore.php");
    exit;
}

// ===================== 统计当前卖家上架商品总数 =====================
// 预处理查询语句，统计该卖家名下所有商品数量
$stmt_prodotti = $conn->prepare("SELECT COUNT(*) AS tot_prodotti FROM prodotto WHERE p_iva = ?");
// 绑定卖家税号字符串参数
$stmt_prodotti->bind_param("s", $p_iva_venditore);
// 执行查询
$stmt_prodotti->execute();
// 获取查询结果
$res_prodotti = $stmt_prodotti->get_result();
// 提取商品总数，无数据默认赋值0并转为整型
$tot_prodotti = (int)($res_prodotti->fetch_assoc()['tot_prodotti'] ?? 0);
// 关闭语句，释放数据库资源
$stmt_prodotti->close();

// ===================== 统计卖家关联的有效订单总数 =====================
// 联表查询：订单表+订单明细表+商品表，去重统计包含本店商品的订单数量
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
// 赋值订单总数，默认空值为0
$tot_ordini = (int)($res_ordini->fetch_assoc()['tot_ordini'] ?? 0);
$stmt_ordini->close();

// ===================== 统计店铺整体营业总收入 =====================
// 汇总所有订单中本店商品的合计金额，计算累计营收
$stmt_fatturato = $conn->prepare("
    SELECT SUM(po.prezzo_tot) AS fatturato_totale
    FROM p_o po
    JOIN prodotto p ON po.id_prodotto = p.id_prodotto
    WHERE p.p_iva = ?
");
$stmt_fatturato->bind_param("s", $p_iva_venditore);
$stmt_fatturato->execute();
$res_fatturato = $stmt_fatturato->get_result();
// 提取总营收金额，转为浮点型，无数据默认为0
$fatturato_totale = (float)($res_fatturato->fetch_assoc()['fatturato_totale'] ?? 0);
$stmt_fatturato->close();
?>

<!-- 卖家后台仪表盘页面结构 -->
<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <!-- 移动端自适应布局适配 -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Venditore - E-commerce Doubao</title>
    <!-- 引入字体图标样式库 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- 引入项目全局自定义样式表 -->
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <!-- 网站顶部导航栏区域 -->
    <header>
        <div class="container">
            <h1><i class="fas fa-store"></i> Area Venditori</h1>
            <nav>
                <!-- 展示当前登录卖家店铺名称 -->
                <span><i class="fas fa-user"></i> Ciao, <?= $_SESSION['venditore_ragione_sociale'] ?? '' ?></span>
                <!-- 当前仪表盘页面导航高亮 -->
                <a href="dashboard_venditore.php" style="border-bottom: 2px solid #fef3c7;"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="gestisci_prodotti.php"><i class="fas fa-box"></i> Prodotti</a>
                <a href="ordini_venditore.php"><i class="fas fa-file-invoice"></i> Ordini</a>
                <a href="profilo_venditore.php"><i class="fas fa-user-cog"></i> Profilo</a>
                <!-- 退出登录按钮 -->
                <a href="logout.php" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </div>
    </header>

    <!-- 页面主体数据统计区域 -->
    <main class="container">
        <!-- 页面欢迎标题 -->
        <h2><i class="fas fa-tachometer-alt"></i> Benvenuto, <?= $_SESSION['venditore_ragione_sociale'] ?? '' ?></h2>
        <p class="subtitle">Panoramica del tuo negozio sul nostro e-commerce</p>

        <!-- 数据卡片网格布局 -->
        <div class="stats-grid">
            <!-- 商品数量统计卡片 -->
            <div class="stat-card products">
                <div class="stat-icon">
                    <i class="fas fa-box"></i>
                </div>
                <h4>Prodotti in catalogo</h4>
                <h3><?= $tot_prodotti ?></h3>
                <!-- 跳转商品管理页面按钮 -->
                <a href="gestisci_prodotti.php" class="btn">
                    <i class="fas fa-cog"></i> Gestisci prodotti
                </a>
            </div>

            <!-- 订单数量统计卡片 -->
            <div class="stat-card orders">
                <div class="stat-icon">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <h4>Ordini ricevuti</h4>
                <h3><?= $tot_ordini ?></h3>
                <!-- 跳转订单查看页面按钮 -->
                <a href="ordini_venditore.php" class="btn">
                    <i class="fas fa-eye"></i> Vedi ordini
                </a>
            </div>

            <!-- 店铺总营收统计卡片 -->
            <div class="stat-card revenue">
                <div class="stat-icon">
                    <i class="fas fa-euro-sign"></i>
                </div>
                <h4>Fatturato totale</h4>
                <!-- 格式化欧元金额展示，保留两位小数 -->
                <h3>€ <?= number_format($fatturato_totale, 2, ',', '.') ?></h3>
            </div>
        </div>
    </main>
</body>

</html>