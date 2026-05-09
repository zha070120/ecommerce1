<?php 
include 'config.php';
$sql = "SELECT * FROM prodotto";
$products = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <!-- 必须的响应式标签（同时满足响应式要求） -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - E-commerce Doubao</title>
    <link rel="stylesheet" href="style.css">
    <!-- 引入AJAX脚本（defer表示页面加载完再执行） -->
    <script src="js/main.js" defer></script>
    <style>
.product-card img{
    width:100%;
    height:200px;
    object-fit:cover;
    border-radius:6px;
    margin-bottom:10px;
}
    </style>
</head>
<body>
    <header>
        <h1>E-commerce Doubao</h1>
        <nav>
            <a href="index.php">Home</a>
            <?php if (isset($_SESSION['user_email'])): ?>
                <a href="cart.php">Carrello</a>
                <a href="orders.php">I miei ordini</a>
                <span>Ciao, <?= $_SESSION['user_name'] ?></span>
                <a href="logout.php" class="btn btn-danger">Logout</a>
            <?php else: ?>
                <a href="login.php">Area Clienti</a>
                <a href="login_venditore.php">Area Venditori</a>
            <?php endif; ?>
        </nav>
    </header>

    <div class="container">
        <!-- AJAX加载区域：最新商品 -->
        <h2>Più Venduti</h2>
        <div id="piu-venduti"></div>

        
        <!-- 原来的 -->
        <section id="all-products">
            <h2>Tutti i Prodotti</h2>
            <!-- 原有商品列表代码不变 -->
            <?php if ($products && $products->num_rows > 0): ?>
                <div class="product-grid">
                <?php while ($product = $products->fetch_assoc()): ?>
                    <div class="product-card">
                        <img src="<?= $product['indirizzo_img'] ?>" alt="<?= $product['nome'] ?>">
                        <h3><?= $product['nome'] ?></h3>
                        <p>Disponibilità: <?= $product['quantita_disponibile'] ?> pezzi</p>
                        <div class="price">€ <?= number_format($product['prezzo'], 2, '.', '') ?></div>
                        
                        <?php if (isset($_SESSION['user_email'])): ?>
                            <form method="POST" action="cart.php">
                                <input type="hidden" name="product_id" value="<?= $product['id_prodotto'] ?>">
                                <div class="form-group">
                                    <label>Quantità</label>
                                    <input type="number" name="quantity" value="1" min="1" max="<?= $product['quantita_disponibile'] ?>" required>
                                </div>
                                <button type="submit" name="add_to_cart" class="btn">Aggiungi al carrello</button>
                            </form>
                        <?php else: ?>
                            <a href="login.php" class="btn">Accedi per acquistare</a>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p style="color:red;">Nessun prodotto nel database</p>
        <?php endif; ?>
        </section>
        
        
    </div>
</body>
</html>