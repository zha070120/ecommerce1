<?php
// 引入数据库连接、会话权限通用配置文件
include 'config.php';
// 校验卖家登录权限，未登录自动跳转登录页面
richiedi_login_venditore();

// 安全获取当前登录卖家税号，设置空值兜底避免程序报错
$p_iva_venditore = $_SESSION['venditore_piva'] ?? '';
// 定义操作成功、错误提示信息变量
$messaggio = '';
$errore = '';

// 定义系统允许的全部订单状态白名单
$stati_consentiti = ['attivo', 'spedito', 'consegnato', 'annullato'];
// 定义禁止二次修改的订单状态，已完成/已取消订单无法变更
$stati_non_modificabili = ['consegnato', 'annullato'];

// ===================== 订单状态更新处理逻辑 =====================
// 监听表单POST提交，触发订单状态修改操作
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aggiorna_stato'])) {
    // 获取订单ID并强制转为整型，过滤非法参数
    $id_ordine = intval($_POST['id_ordine']);
    // 获取新订单状态，去除首尾空格并统一转为小写格式
    $nuovo_stato = trim(strtolower($_POST['stato_ordine']));

    // 校验提交的状态是否在合法白名单内
    if (!in_array($nuovo_stato, $stati_consentiti, true)) {
        $errore = "Stato ordine non valido! Stati consentiti: attivo, spedito, consegnato, annullato.";
    } else {
        // 查询订单当前原始状态
        $stmt_stato = $conn->prepare("SELECT stato_ordine FROM ordine WHERE id_ordine = ?");
        $stmt_stato->bind_param("i", $id_ordine);
        $stmt_stato->execute();
        $row_stato = $stmt_stato->get_result()->fetch_assoc();
        $stmt_stato->close();

        // 判断订单是否处于不可修改状态
        if (in_array($row_stato['stato_ordine'] ?? '', $stati_non_modificabili, true)) {
            $errore = "Impossibile modificare: ordine già consegnato o annullato!";
        } else {
            // 联表校验订单归属权，仅允许修改自身店铺产生的订单
            $stmt_check = $conn->prepare("
                SELECT DISTINCT o.id_ordine
                FROM ordine o
                JOIN p_o po ON o.id_ordine = po.id_ordine
                JOIN prodotto p ON po.id_prodotto = p.id_prodotto
                WHERE o.id_ordine = ? AND p.p_iva = ?
            ");
            $stmt_check->bind_param("is", $id_ordine, $p_iva_venditore);
            $stmt_check->execute();
            // 判断订单是否归属当前卖家
            $ordine_valido = $stmt_check->get_result()->num_rows > 0;
            $stmt_check->close();

            if ($ordine_valido) {
                // 执行订单状态数据库更新
                $stmt_update = $conn->prepare("UPDATE ordine SET stato_ordine = ? WHERE id_ordine = ?");
                $stmt_update->bind_param("si", $nuovo_stato, $id_ordine);
                if ($stmt_update->execute()) {
                    // 更新成功赋值提示文案
                    $messaggio = "Stato dell'ordine #$id_ordine aggiornato con successo!";
                } else {
                    // 捕获数据库执行错误
                    $errore = "Errore durante l'aggiornamento: " . $conn->error;
                }
                $stmt_update->close();
            } else {
                // 无权限操作他人订单提示
                $errore = "Non sei autorizzato a modificare questo ordine!";
            }
        }
    }
}

// ===================== 查询当前卖家所有关联订单 =====================
// 多表联查：订单表+客户表+订单明细表+商品表，筛选当前卖家的订单数据
$stmt_ordini = $conn->prepare("
    SELECT DISTINCT o.id_ordine, o.data_ordine, o.stato_ordine, c.nome, c.cognome, c.email
    FROM ordine o
    JOIN cliente c ON o.email = c.email
    JOIN p_o po ON o.id_ordine = po.id_ordine
    JOIN prodotto p ON po.id_prodotto = p.id_prodotto
    WHERE p.p_iva = ?
    ORDER BY o.id_ordine DESC
");
// 绑定卖家税号查询条件
$stmt_ordini->bind_param("s", $p_iva_venditore);
$stmt_ordini->execute();
// 获取订单结果集
$ordini = $stmt_ordini->get_result();
// 关闭查询语句，释放数据库资源
$stmt_ordini->close();
?>

<!-- 卖家订单管理页面HTML结构 -->
<!DOCTYPE html>
<html lang="it">

<head>
    <!-- 网页文字编码格式 -->
    <meta charset="UTF-8">
    <!-- 移动端屏幕自适应布局 -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- 浏览器页面标题 -->
    <title>I Miei Ordini - Area Venditori</title>
    <!-- 引入Font Awesome字体图标库 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- 引入项目全局自定义样式表 -->
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <!-- 网站后台顶部导航栏 -->
    <header>
        <div class="container">
            <h1><i class="fas fa-store"></i> Area Venditori</h1>
            <nav>
                <!-- 展示当前登录卖家店铺名称 -->
                <span><i class="fas fa-user"></i> Ciao, <?= $_SESSION['venditore_ragione_sociale'] ?? 'Utente' ?></span>
                <!-- 后台功能导航入口 -->
                <a href="dashboard_venditore.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="gestisci_prodotti.php"><i class="fas fa-box"></i> Prodotti</a>
                <!-- 当前订单页面导航高亮标识 -->
                <a href="ordini_venditore.php" style="border-bottom: 2px solid #fef3c7;"><i class="fas fa-file-invoice"></i> Ordini</a>
                <a href="profilo_venditore.php"><i class="fas fa-user-cog"></i> Profilo</a>
                <!-- 退出登录按钮 -->
                <a href="logout.php" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </div>
    </header>

    <!-- 页面主体内容容器 -->
    <main class="container">
        <h2><i class="fas fa-shopping-bag"></i> Ordini Ricevuti</h2>

        <!-- 操作成功提示弹窗 -->
        <?php if ($messaggio): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= $messaggio ?>
            </div>
        <?php endif; ?>
        <!-- 操作错误提示弹窗 -->
        <?php if ($errore): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?= $errore ?>
            </div>
        <?php endif; ?>

        <!-- 判断是否查询到订单数据 -->
        <?php if ($ordini->num_rows > 0): ?>
            <!-- 循环遍历渲染每一条订单信息 -->
            <?php while ($ordine = $ordini->fetch_assoc()): ?>
                <?php
                // 判断当前订单是否锁定禁止修改
                $blocca_modifica = in_array($ordine['stato_ordine'] ?? '', $stati_non_modificabili, true);
                ?>
                <!-- 单个订单卡片容器 -->
                <div class="order-card">
                    <!-- 订单头部基础信息区域 -->
                    <div class="order-header">
                        <h3><i class="fas fa-receipt"></i> Ordine #<?= $ordine['id_ordine'] ?? '' ?></h3>

                        <!-- 订单下单日期展示 -->
                        <div class="order-info">
                            <p><i class="fas fa-calendar-alt"></i> <strong>Data:</strong> <?= date('d/m/Y', strtotime($ordine['data_ordine'] ?? '')) ?></p>
                        </div>

                        <!-- 订单当前状态展示 -->
                        <div class="order-info">
                            <p>
                                <strong>Stato attuale:</strong>
                                <span class="status-badge status-<?= $ordine['stato_ordine'] ?? '' ?>">
                                    <?= ucfirst($ordine['stato_ordine'] ?? '') ?>
                                </span>
                            </p>
                        </div>

                        <!-- 下单客户个人信息展示 -->
                        <div class="order-info">
                            <p><i class="fas fa-user"></i> <strong>Cliente:</strong> <?= $ordine['nome'] ?? '' ?> <?= $ordine['cognome'] ?? '' ?></p>
                            <p><i class="fas fa-envelope"></i> <?= $ordine['email'] ?? '' ?></p>
                        </div>
                    </div>

                    <!-- 订单锁定提示，已完结/取消订单无法修改状态 -->
                    <?php if ($blocca_modifica): ?>
                        <div class="non-modificabile">
                            <i class="fas fa-lock"></i> Ordine non modificabile (già consegnato o annullato)
                        </div>
                    <?php else: ?>
                        <!-- 订单状态修改表单 -->
                        <form method="POST" class="status-update">
                            <!-- 隐藏域传递订单ID参数 -->
                            <input type="hidden" name="id_ordine" value="<?= $ordine['id_ordine'] ?? '' ?>">
                            <label for="stato_ordine_<?= $ordine['id_ordine'] ?>"><i class="fas fa-sync-alt"></i> Nuovo stato:</label>
                            <!-- 下拉选择框切换订单状态，默认选中当前状态 -->
                            <select id="stato_ordine_<?= $ordine['id_ordine'] ?>" name="stato_ordine" required>
                                <option value="attivo" <?= ($ordine['stato_ordine'] ?? '') === 'attivo' ? 'selected' : '' ?>>Attivo</option>
                                <option value="spedito" <?= ($ordine['stato_ordine'] ?? '') === 'spedito' ? 'selected' : '' ?>>Spedito</option>
                                <option value="consegnato" <?= ($ordine['stato_ordine'] ?? '') === 'consegnato' ? 'selected' : '' ?>>Consegnato</option>
                                <option value="annullato" <?= ($ordine['stato_ordine'] ?? '') === 'annullato' ? 'selected' : '' ?>>Annullato</option>
                            </select>
                            <!-- 状态更新提交按钮 -->
                            <button type="submit" name="aggiorna_stato" class="btn btn-success">
                                <i class="fas fa-check"></i> Aggiorna Stato
                            </button>
                        </form>
                    <?php endif; ?>

                    <!-- 订单商品明细表格区域 -->
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
                                // 查询该订单下属于当前卖家的所有商品明细
                                $stmt_prodotti_ordine = $conn->prepare("
                                    SELECT po.*, p.nome
                                    FROM p_o po
                                    JOIN prodotto p ON po.id_prodotto = p.id_prodotto
                                    WHERE po.id_ordine = ? AND p.p_iva = ?
                                ");
                                $stmt_prodotti_ordine->bind_param("is", $id_ordine, $p_iva_venditore);
                                $stmt_prodotti_ordine->execute();
                                $prodotti_ordine = $stmt_prodotti_ordine->get_result();
                                // 初始化订单合计金额变量
                                $totale_ordine = 0;
                                ?>
                                <!-- 循环渲染订单内每件商品信息 -->
                                <?php while ($prodotto = $prodotti_ordine->fetch_assoc()): ?>
                                    <?php $totale_ordine += $prodotto['prezzo_tot'] ?? 0; ?>
                                    <tr>
                                        <td class="product-name"><?= $prodotto['nome'] ?? '' ?></td>
                                        <!-- 格式化欧元金额展示样式 -->
                                        <td class="price">€ <?= number_format($prodotto['prezzo_singolo'] ?? 0, 2, ',', '.') ?></td>
                                        <td class="quantity"><?= $prodotto['pezzi'] ?? 0 ?></td>
                                        <td class="subtotal">€ <?= number_format($prodotto['prezzo_tot'] ?? 0, 2, ',', '.') ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                            <!-- 订单金额合计行 -->
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
            <!-- 暂无订单空白状态提示 -->
            <div class="empty-box">
                <i class="fas fa-shopping-bag"></i>
                <p>Non hai ancora ricevuto ordini per i tuoi prodotti.</p>
                <a href="gestisci_prodotti.php" class="btn">
                    <i class="fas fa-box"></i> Gestisci i tuoi prodotti
                </a>
            </div>
        <?php endif; ?>
    </main>
</body>

</html>