<?php
include 'config.php';
richiedi_login_cliente();
verify_csrf();

$page_title = 'Carrello';
$cart_id = $_SESSION['cart_id'];
$total = 0;

// ===================== 添加商品至购物车 =====================
if (isset($_POST['add_to_cart'])) {
    $product_id = intval($_POST['product_id']);
    $quantity = max(1, intval($_POST['quantity']));

    // 先查库存
    $stmt_stock = $conn->prepare("SELECT quantita_disponibile FROM prodotto WHERE id_prodotto = ?");
    $stmt_stock->bind_param("i", $product_id);
    $stmt_stock->execute();
    $stock = $stmt_stock->get_result()->fetch_assoc();
    $stmt_stock->close();

    if (!$stock || $stock['quantita_disponibile'] < 1) {
        $_SESSION['cart_error'] = 'Prodotto non disponibile.';
        redirect('cart.php');
    }

    // 查购物车已有数量
    $stmt_check = $conn->prepare("SELECT pezzi FROM p_c WHERE id_prodotto = ? AND id_carello = ?");
    $stmt_check->bind_param("ii", $product_id, $cart_id);
    $stmt_check->execute();
    $check = $stmt_check->get_result();
    $existing = $check->fetch_assoc();
    $stmt_check->close();

    $new_qty = $existing ? $existing['pezzi'] + $quantity : $quantity;

    // 校验总数量不超过库存
    if ($new_qty > $stock['quantita_disponibile']) {
        $_SESSION['cart_error'] = 'Quantità massima disponibile: ' . $stock['quantita_disponibile'] . ' pezzi.';
        redirect('cart.php');
    }

    if ($existing) {
        $stmt_update = $conn->prepare("UPDATE p_c SET pezzi = pezzi + ? WHERE id_prodotto = ? AND id_carello = ?");
        $stmt_update->bind_param("iii", $quantity, $product_id, $cart_id);
        $stmt_update->execute();
        $stmt_update->close();
    } else {
        $stmt_insert = $conn->prepare("INSERT INTO p_c (id_prodotto, id_carello, pezzi) VALUES (?, ?, ?)");
        $stmt_insert->bind_param("iii", $product_id, $cart_id, $quantity);
        $stmt_insert->execute();
        $stmt_insert->close();
    }

    redirect('cart.php');
}

// ===================== 修改购物车数量 =====================
if (isset($_POST['update_quantity'])) {
    $product_id = intval($_POST['product_id']);
    $quantity = max(1, intval($_POST['quantity']));

    // 校验库存
    $stmt_stock = $conn->prepare("SELECT quantita_disponibile FROM prodotto WHERE id_prodotto = ?");
    $stmt_stock->bind_param("i", $product_id);
    $stmt_stock->execute();
    $stock = $stmt_stock->get_result()->fetch_assoc();
    $stmt_stock->close();

    if (!$stock || $quantity > $stock['quantita_disponibile']) {
        $_SESSION['cart_error'] = 'Quantità non disponibile per questo prodotto.';
        redirect('cart.php');
    }

    $stmt_update = $conn->prepare("UPDATE p_c SET pezzi = ? WHERE id_prodotto = ? AND id_carello = ?");
    $stmt_update->bind_param("iii", $quantity, $product_id, $cart_id);
    $stmt_update->execute();
    $stmt_update->close();

    // AJAX请求返回JSON
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'quantity' => $quantity]);
        exit;
    }

    redirect('cart.php');
}

// ===================== 删除购物车商品 =====================
if (isset($_POST['remove_from_cart'])) {
    $product_id = intval($_POST['product_id']);

    $stmt_delete = $conn->prepare("DELETE FROM p_c WHERE id_prodotto = ? AND id_carello = ?");
    $stmt_delete->bind_param("ii", $product_id, $cart_id);
    $stmt_delete->execute();
    $stmt_delete->close();

    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }

    redirect('cart.php');
}

// ===================== 查询购物车商品 =====================
$stmt_cart = $conn->prepare("
    SELECT pc.id_prodotto, pc.pezzi, p.nome, p.prezzo, p.quantita_disponibile, p.indirizzo_img
    FROM p_c pc 
    JOIN prodotto p ON pc.id_prodotto = p.id_prodotto 
    WHERE pc.id_carello = ?
");
$stmt_cart->bind_param("i", $cart_id);
$stmt_cart->execute();
$cart_items = $stmt_cart->get_result();

// 读取并清除错误消息
$cart_error = isset($_SESSION['cart_error']) ? $_SESSION['cart_error'] : '';
unset($_SESSION['cart_error']);

include 'includes/header.php';
?>

<h2><i class="fas fa-shopping-cart"></i> Il tuo carrello</h2>

<?php if ($cart_error): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i> <?= e($cart_error) ?>
    </div>
<?php endif; ?>

<?php if ($cart_items->num_rows > 0): ?>
    <table class="cart-table">
        <thead>
            <tr>
                <th>Prodotto</th>
                <th>Prezzo unitario</th>
                <th>Quantità</th>
                <th>Totale</th>
                <th>Azioni</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($item = $cart_items->fetch_assoc()): ?>
                <?php
                $subtotal = $item['prezzo'] * $item['pezzi'];
                $total += $subtotal;
                ?>
                <tr data-product-id="<?= $item['id_prodotto'] ?>">
                    <td class="product-name">
                        <?php if ($item['indirizzo_img']): ?>
                            <img src="<?= e($item['indirizzo_img']) ?>" alt="<?= e($item['nome']) ?>" class="cart-thumb" loading="lazy">
                        <?php endif; ?>
                        <?= e($item['nome']) ?>
                    </td>
                    <td class="price">€ <?= number_format($item['prezzo'], 2, ',', '.') ?></td>
                    <td class="quantity">
                        <div class="qty-control">
                            <form method="POST" class="qty-form">
                                <?php csrf_field(); ?>
                                <input type="hidden" name="product_id" value="<?= $item['id_prodotto'] ?>">
                                <button type="button" class="qty-btn qty-minus" data-action="minus">−</button>
                                <input type="number" name="quantity" value="<?= $item['pezzi'] ?>" 
                                       min="1" max="<?= $item['quantita_disponibile'] ?>" 
                                       class="qty-input" readonly>
                                <button type="button" class="qty-btn qty-plus" data-action="plus">+</button>
                                <input type="hidden" name="update_quantity" value="1">
                            </form>
                        </div>
                    </td>
                    <td class="subtotal">€ <?= number_format($subtotal, 2, ',', '.') ?></td>
                    <td>
                        <form method="POST" class="remove-form">
                            <?php csrf_field(); ?>
                            <input type="hidden" name="product_id" value="<?= $item['id_prodotto'] ?>">
                            <button type="submit" name="remove_from_cart" class="btn btn-danger remove-btn">
                                <i class="fas fa-trash-alt"></i> Rimuovi
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3"><strong>Totale carrello</strong></td>
                <td><strong class="cart-total">€ <?= number_format($total, 2, ',', '.') ?></strong></td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <div class="checkout-container">
        <a href="checkout.php" class="btn btn-success">
            <i class="fas fa-credit-card"></i> Procedi al checkout
        </a>
    </div>
<?php else: ?>
    <div class="empty-box">
        <i class="fas fa-shopping-cart"></i>
        <p>Il tuo carrello è vuoto</p>
        <a href="index.php" class="btn">
            <i class="fas fa-arrow-left"></i> Torna ai prodotti
        </a>
    </div>
<?php endif; ?>

<?php
$stmt_cart->close();
include 'includes/footer.php';
?>
