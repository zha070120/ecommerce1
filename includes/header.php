<?php
// 确保session已启动（config.php已调用，此处兜底）
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 计算购物车商品总数（用于导航角标）
$cart_count = 0;
if (isset($_SESSION['cart_id']) && isset($conn)) {
    $stmt_cc = $conn->prepare("SELECT COALESCE(SUM(pezzi), 0) AS totale FROM p_c WHERE id_carello = ?");
    $stmt_cc->bind_param("i", $_SESSION['cart_id']);
    $stmt_cc->execute();
    $res_cc = $stmt_cc->get_result()->fetch_assoc();
    $cart_count = intval($res_cc['totale']);
    $stmt_cc->close();
}

// 当前页面文件名，用于导航高亮
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? e($page_title) . ' - ' : '' ?>E-commerce Doubao</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <script src="js/main.js" defer></script>
</head>
<body>
    <header>
        <div class="container">
            <h1><i class="fas fa-shopping-bag"></i> E-commerce Doubao</h1>
            <nav>
                <a href="index.php" class="<?= $current_page === 'index.php' ? 'nav-active' : '' ?>">
                    <i class="fas fa-home"></i> Home
                </a>

                <?php if (isset($_SESSION['user_email'])): ?>
                    <a href="cart.php" class="cart-link <?= $current_page === 'cart.php' ? 'nav-active' : '' ?>">
                        <i class="fas fa-shopping-cart"></i> Carrello
                        <?php if ($cart_count > 0): ?>
                            <span class="cart-badge"><?= $cart_count ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="orders.php" class="<?= $current_page === 'orders.php' ? 'nav-active' : '' ?>">
                        <i class="fas fa-file-invoice"></i> I miei ordini
                    </a>
                    <span><i class="fas fa-user"></i> Ciao, <?= e($_SESSION['user_name']) ?></span>
                    <a href="logout.php" class="btn btn-danger">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                <?php elseif (isset($_SESSION['venditore_piva'])): ?>
                    <a href="dashboard_venditore.php" class="<?= $current_page === 'dashboard_venditore.php' ? 'nav-active' : '' ?>">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    <a href="gestisci_prodotti.php" class="<?= $current_page === 'gestisci_prodotti.php' ? 'nav-active' : '' ?>">
                        <i class="fas fa-box"></i> Prodotti
                    </a>
                    <a href="ordini_venditore.php" class="<?= $current_page === 'ordini_venditore.php' ? 'nav-active' : '' ?>">
                        <i class="fas fa-shipping-fast"></i> Ordini
                    </a>
                    <a href="profilo_venditore.php" class="<?= $current_page === 'profilo_venditore.php' ? 'nav-active' : '' ?>">
                        <i class="fas fa-user"></i> Profilo
                    </a>
                    <a href="logout.php" class="btn btn-danger">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                <?php else: ?>
                    <a href="login.php" class="<?= $current_page === 'login.php' ? 'nav-active' : '' ?>">
                        <i class="fas fa-user"></i> Area Clienti
                    </a>
                    <a href="login_venditore.php" class="<?= $current_page === 'login_venditore.php' ? 'nav-active' : '' ?>">
                        <i class="fas fa-store"></i> Area Venditori
                    </a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
    <main class="container">
