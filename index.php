<?php 
include 'config.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

$sql = "SELECT * FROM prodotto";
$products = $conn->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Home - E-commerce Doubao</title>
    <link rel="stylesheet" href="style.css">
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
        <h2>I nostri prodotti</h2>

        <?php if ($products && $products->num_rows > 0): ?>
            <div class="product-grid">
                <?php while ($product = $products->fetch_assoc()): ?>
                    <div class="product-card">
                        <!-- 显示商品图片 -->
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
    </div>
</body>
</html>