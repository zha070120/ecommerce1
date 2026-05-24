<?php
// 引入数据库连接、会话权限等公共配置文件
include 'config.php';

// ===================== 后端商品数据查询 =====================
// 使用预处理语句查询数据库中所有商品信息，规避SQL注入风险
$stmt = $conn->prepare("SELECT * FROM prodotto ORDER BY RAND()");
// 执行查询指令
$stmt->execute();
// 获取查询到的全部商品结果集
$products = $stmt->get_result();
?>

<!-- 电商网站首页页面结构 -->
<!DOCTYPE html>
<html lang="it">
<head>
    <!-- 网页基础编码格式 -->
    <meta charset="UTF-8">
    <!-- 适配手机、平板等移动端设备屏幕尺寸 -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- 网页标题 -->
    <title>Home - E-commerce Doubao</title>
    <!-- 引入Font Awesome字体图标库，提供各类图标样式 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- 引入项目自定义全局样式表 -->
    <link rel="stylesheet" href="css/style.css">
    <!-- 引入页面交互JS脚本文件，处理前端动态逻辑 -->
    <script src="js/main.js"></script>
</head>
<body>
    <!-- 网站顶部导航栏区域 -->
    <header>
        <div class="container">
            <!-- 网站品牌标题 -->
            <h1><i class="fas fa-shopping-bag"></i> E-commerce Doubao</h1>
            <nav>
                <!-- 首页导航入口 -->
                <a href="index.php"><i class="fas fa-home"></i> Home</a>

                <!-- 根据用户登录状态动态展示导航菜单 -->
                <?php if (isset($_SESSION['user_email'])): ?>
                    <!-- 已登录客户：展示购物车、个人订单、用户名、退出登录 -->
                    <a href="cart.php"><i class="fas fa-shopping-cart"></i> Carrello</a>
                    <a href="orders.php"><i class="fas fa-file-invoice"></i> I miei ordini</a>
                    <span><i class="fas fa-user"></i> Ciao, <?= $_SESSION['user_name'] ?></span>
                    <a href="logout.php" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
                <?php else: ?>
                    <!-- 未登录游客：展示客户登录、卖家登录入口 -->
                    <a href="login.php"><i class="fas fa-user"></i> Area Clienti</a>
                    <a href="login_venditore.php"><i class="fas fa-store"></i> Area Venditori</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
    
    <!-- 页面主体内容容器 -->
    <main class="container">
        <!-- AJAX动态加载热销商品模块 -->
        <h2><i class="fas fa-fire"></i> Più Venduti</h2>
        <!-- 预留DOM节点，由JS异步请求填充热销商品数据 -->
        <div id="piu-venduti"></div>

        <!-- 全站全部商品展示区域 -->
        <section id="all-products">
            <h2><i class="fas fa-box"></i> Tutti i Prodotti</h2>
            <!-- 判断是否查询到商品数据 -->
            <?php if ($products && $products->num_rows > 0): ?>
                <!-- 商品网格布局容器，卡片式排列商品 -->
                <div class="product-grid">
                    <!-- 循环遍历结果集，逐个渲染商品卡片 -->
                    <?php while ($product = $products->fetch_assoc()): ?>
                        <div class="product-card">
                            <!-- 商品图片容器，固定4:3展示比例 -->
                            <div class="image-container">
                                <img src="<?= $product['indirizzo_img'] ?>" alt="<?= $product['nome'] ?>">
                            </div>
                            
                            <!-- 商品名称 -->
                            <h3><?= $product['nome'] ?></h3>
                            <!-- 商品库存数量展示 -->
                            <p>
                                <i class="fas fa-check-circle in-stock"></i> 
                                Disponibilità: <?= $product['quantita_disponibile'] ?> pezzi
                            </p>
                            <!-- 格式化欧元价格展示 -->
                            <div class="price">€ <?= number_format($product['prezzo'], 2, '.', '') ?></div>
                            
                            <!-- 区分登录状态展示不同操作按钮 -->
                            <?php if (isset($_SESSION['user_email'])): ?>
                                <!-- 已登录：加入购物车表单，提交至购物车处理页面 -->
                                <form method="POST" action="cart.php">
                                    <!-- 隐藏域传递当前商品ID -->
                                    <input type="hidden" name="product_id" value="<?= $product['id_prodotto'] ?>">
                                    <div class="form-group">
                                        <label for="qty-<?= $product['id_prodotto'] ?>">Quantità</label>
                                        <!-- 购买数量选择框，限制最小1、最大为现有库存，防止超量下单 -->
                                        <input 
                                            type="number" 
                                            id="qty-<?= $product['id_prodotto'] ?>" 
                                            name="quantity" 
                                            value="1" 
                                            min="1" 
                                            max="<?= $product['quantita_disponibile'] ?>" 
                                            required
                                        >
                                    </div>
                                    <!-- 加入购物车提交按钮 -->
                                    <button type="submit" name="add_to_cart" class="btn">
                                        <i class="fas fa-cart-plus"></i> Aggiungi al carrello
                                    </button>
                                </form>
                            <?php else: ?>
                                <!-- 未登录：提示用户登录后才可购买 -->
                                <div style="padding: 0 1rem 1.5rem;">
                                    <a href="login.php" class="btn">
                                        <i class="fas fa-sign-in-alt"></i> Accedi per acquistare
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <!-- 数据库暂无商品时的空白提示界面 -->
                <div class="empty-box">
                    <i class="fas fa-box-open"></i>
                    <p>Nessun prodotto nel database</p>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <!-- 网站底部页脚区域 -->
    <footer>
        <div class="container">
            <!-- 页脚多列网格布局 -->
            <div class="footer-grid">
                <!-- 店铺品牌简介板块 -->
                <div class="footer-section">
                    <h3>E-commerce Doubao</h3>
                    <p>Il tuo negozio online di fiducia in Italia. Offriamo prodotti di qualità a prezzi competitivi con spedizione veloce in tutta Italia.</p>
                    <!-- 社交媒体图标入口 -->
                    <div class="social-icons">
                        <a href="#"><i class="fab fa-facebook-f" aria-label="facebook"></i></a>
                        <a href="#"><i class="fab fa-instagram" aria-label="instagram"></i></a>
                        <a href="#"><i class="fab fa-x" aria-label="x"></i></a>
                        <a href="#"><i class="fab fa-youtube" aria-label="youtube"></i></a>
                    </div>
                </div>

                <!-- 实用导航链接板块 -->
                <div class="footer-section">
                    <h3>Link Utili</h3>
                    <ul>
                        <li><a href="#">Chi Siamo</a></li>
                        <li><a href="#">Contattaci</a></li>
                        <li><a href="#">FAQ</a></li>
                        <li><a href="#">Termini e Condizioni</a></li>
                        <li><a href="#">Politica sulla Privacy</a></li>
                    </ul>
                </div>

                <!-- 客户服务相关链接板块 -->
                <div class="footer-section">
                    <h3>Servizi Clienti</h3>
                    <ul>
                        <li><a href="#">Spedizioni e Consegne</a></li>
                        <li><a href="#">Resi e Rimborsi</a></li>
                        <li><a href="#">Traccia il tuo Ordine</a></li>
                        <li><a href="#">Assistenza Clienti</a></li>
                        <li><a href="#">Metodi di Pagamento</a></li>
                    </ul>
                </div>

                <!-- 店铺联系方式板块 -->
                <div class="footer-section">
                    <h3>Contatti</h3>
                    <p><i class="fas fa-map-marker-alt"></i> Via Roma 123, 00100 Roma, Italia</p>
                    <p><i class="fas fa-phone"></i> +39 06 12345678</p>
                    <p><i class="fas fa-envelope"></i> info@ecommercedoubao.it</p>
                    <p><i class="fas fa-clock"></i> Lun-Dom: 00:00-23:59</p>
                    
                    <!-- 支持的支付方式图标 -->
                    <div class="payment-methods">
                        <i class="fab fa-cc-visa"></i>
                        <i class="fab fa-cc-mastercard"></i>
                        <i class="fab fa-cc-paypal"></i>
                        <i class="fab fa-cc-amex"></i>
                    </div>
                </div>
            </div>

            <!-- 底部版权声明 -->
            <div class="footer-bottom">
                <p>&copy; 2026 E-commerce Doubao. Tutti i diritti riservati.</p>
            </div>
        </div>
    </footer>
</body>
</html>
<?php
// 释放查询结果集内存资源
$products->free();
// 关闭数据库预处理语句，释放连接资源
$stmt->close();
?>