<?php
include 'config.php';

// 商品查询逻辑（与原代码完全相同）
$stmt = $conn->prepare("SELECT * FROM prodotto");
$stmt->execute();
$products = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - E-commerce Doubao</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <script src="js/main.js"></script>
    <style>
        /* 全局样式重置与基础设置 */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            /* 标准化配色系统（电商专业版） */
            --primary: #2563eb; /* 更现代的蓝色主色 */
            --primary-dark: #1d4ed8;
            --secondary: #f97316; /* 橙色辅助色（促销/强调） */
            --success: #10b981; /* 绿色（有库存/成功） */
            --danger: #ef4444; /* 红色（价格/危险操作） */
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
            max-width: 1280px; /* 加宽容器，更符合现代设计 */
            margin: 0 auto;
            padding: 0 24px;
        }

        /* 导航栏样式最终修复（Logo贴左，导航贴右） */
        header {
            background: linear-gradient(135deg, var(--primary) 0%, #3b82f6 100%);
            color: white;
            padding: 1rem 0;
            box-shadow: var(--shadow-md);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        /* 关键修改：导航栏容器占满整个页面宽度 */
        header .container {
            display: flex;
            justify-content: space-between; /* 左右两端对齐 */
            align-items: center; /* 垂直居中 */
            max-width: 100%; /* 占满整个页面宽度 */
            padding: 0 40px; /* 左右边距，更美观 */
            width: 100%;
        }

        header h1 {
            font-size: 1.75rem;
            font-weight: 700;
            margin: 0;
            white-space: nowrap; /* 防止logo换行 */
            flex-shrink: 0; /* 防止logo被压缩 */
        }

        nav {
            display: flex;
            align-items: center;
            gap: 2rem; /* 导航项之间的间距 */
            margin-left: auto; /* 关键：将导航推到最右边 */
            flex-shrink: 0; /* 防止导航被压缩 */
        }

        nav a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            padding: 0.5rem 0;
            border-bottom: 2px solid transparent;
            white-space: nowrap; /* 防止导航文字换行 */
        }

        nav a:hover {
            color: #fef3c7;
            border-bottom: 2px solid #fef3c7;
        }

        /* 按钮样式统一优化 */
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

        /* 标题样式优化 */
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

        /* 产品网格布局优化 */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2rem;
            margin-bottom: 4rem;
        }

        /* 产品卡片样式全面升级 */
        .product-card {
            background-color: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            position: relative; /* 为角标做准备 */
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        /* 统一商品图片比例为4:3（电商标准） */
        .product-card .image-container {
            width: 100%;
            padding-top: 75%; /* 4:3比例 */
            position: relative;
            overflow: hidden;
        }

        .product-card img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .product-card:hover img {
            transform: scale(1.03);
        }

        /* 商品角标样式（热销/促销） */
        .product-badge {
            position: absolute;
            top: 12px;
            left: 12px;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 700;
            z-index: 10;
        }

        .badge-hot {
            background-color: var(--danger);
            color: white;
        }

        .badge-sale {
            background-color: var(--secondary);
            color: white;
        }

        .product-card h3 {
            font-size: 1.125rem;
            margin: 1rem 1rem 0.5rem;
            color: var(--gray-800);
            font-weight: 600;
            line-height: 1.4;
            height: 3.15rem; /* 固定高度，对齐卡片 */
            overflow: hidden;
        }

        .product-card p {
            margin: 0 1rem 0.5rem;
            color: var(--gray-600);
            font-size: 0.9rem;
        }

        /* 功能色规范应用 */
        .in-stock {
            color: var(--success);
            font-weight: 500;
        }

        .out-of-stock {
            color: var(--gray-400);
            font-weight: 500;
        }

        .price {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--danger);
            margin: 0.5rem 1rem 1rem;
        }

        /* 表单样式优化 */
        .product-card form {
            padding: 0 1rem 1.5rem;
            margin-top: auto;
        }

        .product-card .form-group {
            margin-bottom: 1rem;
        }

        .product-card label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--gray-700);
        }

        .product-card input[type="number"] {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--gray-300);
            border-radius: 6px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .product-card input[type="number"]:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .product-card .btn {
            width: 100%;
        }

        /* 热销商品区域 */
        #piu-venduti {
            margin-bottom: 4rem;
        }

        /* 空状态样式优化 */
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
        }

        /* 页脚样式（意大利语电商标准） */
        footer {
            background-color: var(--gray-800);
            color: white;
            padding: 4rem 0 2rem;
            margin-top: 4rem;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 3rem;
            margin-bottom: 3rem;
        }

        .footer-section h3 {
            font-size: 1.25rem;
            margin-bottom: 1.5rem;
            color: white;
            position: relative;
            padding-bottom: 0.5rem;
        }

        .footer-section h3::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 40px;
            height: 3px;
            background-color: var(--primary);
            border-radius: 2px;
        }

        .footer-section ul {
            list-style: none;
        }

        .footer-section ul li {
            margin-bottom: 0.75rem;
        }

        .footer-section ul li a {
            color: var(--gray-300);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-section ul li a:hover {
            color: white;
        }

        .footer-section p {
            color: var(--gray-300);
            margin-bottom: 1rem;
            line-height: 1.8;
        }

        .social-icons {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .social-icons a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background-color: var(--gray-700);
            color: white;
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .social-icons a:hover {
            background-color: var(--primary);
            transform: translateY(-3px);
        }

        .payment-methods {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
            flex-wrap: wrap;
        }

        .payment-methods i {
            font-size: 2rem;
            color: var(--gray-400);
        }

        .footer-bottom {
            border-top: 1px solid var(--gray-700);
            padding-top: 2rem;
            text-align: center;
            color: var(--gray-400);
            font-size: 0.9rem;
        }

        /* 响应式设计优化 */
        @media (max-width: 768px) {
            header .container {
                flex-direction: column;
                gap: 1rem;
                padding: 0 24px; /* 移动端恢复较小的边距 */
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

            .product-grid {
                grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
                gap: 1.5rem;
            }

            .footer-grid {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .footer-section h3::after {
                left: 50%;
                transform: translateX(-50%);
            }

            .social-icons, .payment-methods {
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .product-grid {
                grid-template-columns: 1fr;
            }

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
                <?php if (isset($_SESSION['user_email'])): ?>
                    <a href="cart.php"><i class="fas fa-shopping-cart"></i> Carrello</a>
                    <a href="orders.php"><i class="fas fa-file-invoice"></i> I miei ordini</a>
                    <span><i class="fas fa-user"></i> Ciao, <?= $_SESSION['user_name'] ?></span>
                    <a href="logout.php" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
                <?php else: ?>
                    <a href="login.php"><i class="fas fa-user"></i> Area Clienti</a>
                    <a href="login_venditore.php"><i class="fas fa-store"></i> Area Venditori</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <div class="container">
        <!-- AJAX热销商品区域 -->
        <h2><i class="fas fa-fire"></i> Più Venduti</h2>
        <div id="piu-venduti"></div>

        <!-- 全部商品列表（与原代码逻辑完全相同） -->
        <section id="all-products">
            <h2><i class="fas fa-box"></i> Tutti i Prodotti</h2>
            <?php if ($products && $products->num_rows > 0): ?>
                <div class="product-grid">
                    <?php while ($product = $products->fetch_assoc()): ?>
                        <div class="product-card">
                            <!-- 商品图片容器（统一4:3比例） -->
                            <div class="image-container">
                                <img src="<?= $product['indirizzo_img'] ?>" alt="<?= $product['nome'] ?>">
                            </div>
                            
                            <h3><?= $product['nome'] ?></h3>
                            <p>
                                <i class="fas fa-check-circle in-stock"></i> 
                                Disponibilità: <?= $product['quantita_disponibile'] ?> pezzi
                            </p>
                            <div class="price">€ <?= number_format($product['prezzo'], 2, '.', '') ?></div>
                            
                            <?php if (isset($_SESSION['user_email'])): ?>
                                <!-- 表单提交逻辑与原代码完全相同 -->
                                <form method="POST" action="cart.php">
                                    <input type="hidden" name="product_id" value="<?= $product['id_prodotto'] ?>">
                                    <div class="form-group">
                                        <label for="qty-<?= $product['id_prodotto'] ?>">Quantità</label>
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
                                    <button type="submit" name="add_to_cart" class="btn">
                                        <i class="fas fa-cart-plus"></i> Aggiungi al carrello
                                    </button>
                                </form>
                            <?php else: ?>
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
                <div class="empty-state">
                    <i class="fas fa-box-open"></i>
                    <p>Nessun prodotto nel database</p>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <!-- 页脚（纯静态，不影响任何功能） -->
    <footer>
        <div class="container">
            <div class="footer-grid">
                <div class="footer-section">
                    <h3>E-commerce Doubao</h3>
                    <p>Il tuo negozio online di fiducia in Italia. Offriamo prodotti di qualità a prezzi competitivi con spedizione veloce in tutta Italia.</p>
                    <div class="social-icons">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>

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

                <div class="footer-section">
                    <h3>Contatti</h3>
                    <p><i class="fas fa-map-marker-alt"></i> Via Roma 123, 00100 Roma, Italia</p>
                    <p><i class="fas fa-phone"></i> +39 06 12345678</p>
                    <p><i class="fas fa-envelope"></i> info@ecommercedoubao.it</p>
                    <p><i class="fas fa-clock"></i> Lun-Ven: 9:00-18:00</p>
                    
                    <div class="payment-methods">
                        <i class="fab fa-cc-visa"></i>
                        <i class="fab fa-cc-mastercard"></i>
                        <i class="fab fa-cc-paypal"></i>
                        <i class="fab fa-cc-amex"></i>
                    </div>
                </div>
            </div>

            <div class="footer-bottom">
                <p>&copy; 2026 E-commerce Doubao. Tutti i diritti riservati.</p>
            </div>
        </div>
    </footer>
</body>
</html>
<?php
// 数据库资源释放（与原代码完全相同）
$products->free();
$stmt->close();
?>