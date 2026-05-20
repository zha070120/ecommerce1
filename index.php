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
    
    <main class="container">
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
                <div class="empty-box">
                    <i class="fas fa-box-open"></i>
                    <p>Nessun prodotto nel database</p>
                </div>
            <?php endif; ?>
        </section>
            </main>

    <!-- 页脚（纯静态，不影响任何功能） -->
    <footer>
        <div class="container">
            <div class="footer-grid">
                <div class="footer-section">
                    <h3>E-commerce Doubao</h3>
                    <p>Il tuo negozio online di fiducia in Italia. Offriamo prodotti di qualità a prezzi competitivi con spedizione veloce in tutta Italia.</p>
                    <div class="social-icons">
                        <a href="#"><i class="fab fa-facebook-f" aria-label="facebook"></i></a>
                        <a href="#"><i class="fab fa-instagram" aria-label="instagram"></i></a>
                        <a href="#"><i class="fab fa-x" aria-label="x"></i></a>
                        <a href="#"><i class="fab fa-youtube" aria-label="youtube"></i></a>
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
                    <p><i class="fas fa-clock"></i> Lun-Dom: 00:00-23:59</p>
                    
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