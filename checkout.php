<?php
include 'config.php';
richiedi_login_cliente();
verify_csrf();

$page_title = 'Checkout';
$cart_id = $_SESSION['cart_id'];
$user_email = $_SESSION['user_email'];
$error = '';

// ========== 查询购物车商品 ==========
$stmt_cart = $conn->prepare("
    SELECT pc.id_prodotto, pc.pezzi, p.nome, p.prezzo, p.quantita_disponibile 
    FROM p_c pc 
    JOIN prodotto p ON pc.id_prodotto = p.id_prodotto 
    WHERE pc.id_carello = ?
");
$stmt_cart->bind_param("i", $cart_id);
$stmt_cart->execute();
$cart_items = $stmt_cart->get_result();

if ($cart_items->num_rows == 0) {
    $stmt_cart->close();
    redirect('cart.php');
}

// ========== 提交订单 ==========
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 读取收货地址
    $indirizzo_spedizione = trim($_POST['indirizzo_spedizione'] ?? '');
    $citta = trim($_POST['citta'] ?? '');
    $cap = trim($_POST['cap'] ?? '');
    $provincia = trim($_POST['provincia'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $note = trim($_POST['note'] ?? '');

    // 简单校验
    if (!$indirizzo_spedizione || !$citta || !$cap || !$provincia) {
        $error = 'Completa tutti i campi di indirizzo di spedizione.';
    } elseif (!preg_match('/^[0-9]{5}$/', $cap)) {
        $error = 'CAP non valido (5 cifre).';
    } else {
        $conn->begin_transaction();
        try {
            // 新增订单，包含地址信息
            $stmt_order = $conn->prepare("
                INSERT INTO ordine (email, data_ordine, stato_ordine, indirizzo_spedizione, citta, cap, provincia, telefono, note)
                VALUES (?, CURDATE(), 'in_attesa', ?, ?, ?, ?, ?, ?)
            ");
            $stmt_order->bind_param("sssssss", $user_email, $indirizzo_spedizione, $citta, $cap, $provincia, $telefono, $note);
            $stmt_order->execute();
            $order_id = $conn->insert_id;
            $stmt_order->close();

            $cart_items->data_seek(0);

            while ($item = $cart_items->fetch_assoc()) {
                $product_id = $item['id_prodotto'];
                $quantity = $item['pezzi'];
                $price = $item['prezzo'];
                $total_item = $price * $quantity;

                $stmt_item = $conn->prepare("INSERT INTO p_o (id_prodotto, id_ordine, pezzi, prezzo_singolo, prezzo_tot) VALUES (?, ?, ?, ?, ?)");
                $stmt_item->bind_param("iiidd", $product_id, $order_id, $quantity, $price, $total_item);
                $stmt_item->execute();
                $stmt_item->close();

                $stmt_stock = $conn->prepare("UPDATE prodotto SET quantita_disponibile = quantita_disponibile - ? WHERE id_prodotto = ? AND quantita_disponibile >= ?");
                $stmt_stock->bind_param("iii", $quantity, $product_id, $quantity);
                $stmt_stock->execute();

                if ($stmt_stock->affected_rows === 0) {
                    throw new Exception("Prodotto '" . $item['nome'] . "' ha finito le scorte!");
                }
                $stmt_stock->close();
            }

            $stmt_clear = $conn->prepare("DELETE FROM p_c WHERE id_carello = ?");
            $stmt_clear->bind_param("i", $cart_id);
            $stmt_clear->execute();
            $stmt_clear->close();

            $conn->commit();
            redirect('orders.php?success=1');
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Errore durante l'ordine: " . $e->getMessage();
        }
    }
}

$total = 0;

include 'includes/header.php';
?>

<h2><i class="fas fa-credit-card"></i> Conferma ordine</h2>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-triangle"></i> <?= e($error) ?>
    </div>
<?php endif; ?>

<div class="checkout-grid">
    <!-- 订单商品清单 -->
    <div class="checkout-products">
        <h3>Riepilogo ordine</h3>
        <table class="order-table">
            <thead>
                <tr>
                    <th>Prodotto</th>
                    <th>Prezzo unitario</th>
                    <th>Quantità</th>
                    <th>Totale</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $cart_items->data_seek(0);
                while ($item = $cart_items->fetch_assoc()):
                    $subtotal = $item['prezzo'] * $item['pezzi'];
                    $total += $subtotal;
                ?>
                    <tr>
                        <td class="product-name"><?= e($item['nome']) ?></td>
                        <td class="price">€ <?= number_format($item['prezzo'], 2, ',', '.') ?></td>
                        <td class="quantity"><?= $item['pezzi'] ?></td>
                        <td class="subtotal">€ <?= number_format($subtotal, 2, ',', '.') ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3"><strong>Totale ordine</strong></td>
                    <td><strong>€ <?= number_format($total, 2, ',', '.') ?></strong></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- 收货地址表单 -->
    <div class="checkout-address">
        <h3>Indirizzo di spedizione</h3>
        <form method="POST" class="address-form">
            <?php csrf_field(); ?>

            <div class="form-group">
                <label for="indirizzo_spedizione">Indirizzo *</label>
                <input type="text" name="indirizzo_spedizione" id="indirizzo_spedizione" required
                       value="<?= isset($_POST['indirizzo_spedizione']) ? e($_POST['indirizzo_spedizione']) : '' ?>"
                       placeholder="Via Roma 123">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="citta">Città *</label>
                    <input type="text" name="citta" id="citta" required
                           value="<?= isset($_POST['citta']) ? e($_POST['citta']) : '' ?>"
                           placeholder="Roma">
                </div>
                <div class="form-group">
                    <label for="cap">CAP *</label>
                    <input type="text" name="cap" id="cap" required pattern="[0-9]{5}" maxlength="5"
                           value="<?= isset($_POST['cap']) ? e($_POST['cap']) : '' ?>"
                           placeholder="00100">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="provincia">Provincia *</label>
                    <input type="text" name="provincia" id="provincia" required maxlength="2"
                           value="<?= isset($_POST['provincia']) ? e($_POST['provincia']) : '' ?>"
                           placeholder="RM">
                </div>
                <div class="form-group">
                    <label for="telefono">Telefono</label>
                    <input type="tel" name="telefono" id="telefono"
                           value="<?= isset($_POST['telefono']) ? e($_POST['telefono']) : '' ?>"
                           placeholder="+39 123 456 7890">
                </div>
            </div>

            <div class="form-group">
                <label for="note">Note aggiuntive</label>
                <textarea name="note" id="note" rows="3"
                          placeholder="Note per il corriere, citofono, piano..."><?= isset($_POST['note']) ? e($_POST['note']) : '' ?></textarea>
            </div>

            <div class="checkout-actions">
                <a href="cart.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Torna al carrello
                </a>
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-check-circle"></i> Conferma ordine
                </button>
            </div>
        </form>
    </div>
</div>

<?php
$stmt_cart->close();
include 'includes/footer.php';
?>
