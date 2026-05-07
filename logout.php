<header>
    <h1>E-commerce Italia</h1>
    <nav>
        <a href="index.php">Home</a>
        <?php if (isset($_SESSION['user_email'])): ?>
            <a href="cart.php">Carrello</a>
            <a href="orders.php">I miei ordini</a>
            <span>Ciao, <?= $_SESSION['user_name'] ?></span>
            <a href="logout.php" class="btn btn-danger">Logout</a>
        <?php else: ?>
            <a href="login.php">Login Clienti</a>
            <a href="register.php">Registrati</a>
            <a href="login_venditore.php" class="btn">Area Venditori</a>
        <?php endif; ?>
    </nav>
</header>
