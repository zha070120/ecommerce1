<?php
include 'config.php';

$page_title = 'Home';

// ===================== 搜索处理 =====================
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_sql = '';
$search_params = [];
$search_types = '';

if ($search !== '') {
    $search_sql = "WHERE nome LIKE ? OR descrizione LIKE ?";
    $keyword = "%$search%";
    $search_params = [$keyword, $keyword];
    $search_types = 'ss';
}

// ===================== 分页处理 =====================
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 12;
$offset = ($page - 1) * $per_page;

// 统计总数
$count_sql = "SELECT COUNT(*) AS totale FROM prodotto $search_sql";
$stmt_count = $conn->prepare($count_sql);
if ($search_sql && $search_params) {
    $stmt_count->bind_param($search_types, ...$search_params);
}
$stmt_count->execute();
$total_products = $stmt_count->get_result()->fetch_assoc()['totale'];
$total_pages = max(1, ceil($total_products / $per_page));
$stmt_count->close();

// ===================== 查询商品（替换ORDER BY RAND()，改用ID倒序+分页） =====================
$sql = "SELECT id_prodotto, nome, prezzo, quantita_disponibile, indirizzo_img, descrizione 
        FROM prodotto 
        $search_sql 
        ORDER BY id_prodotto DESC 
        LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);
if ($search_sql && $search_params) {
    $search_params[] = $per_page;
    $search_params[] = $offset;
    $search_types .= 'ii';
    $stmt->bind_param($search_types, ...$search_params);
} else {
    $stmt->bind_param("ii", $per_page, $offset);
}
$stmt->execute();
$products = $stmt->get_result();

include 'includes/header.php';
?>

<!-- AJAX动态加载热销商品模块 -->
<h2><i class="fas fa-fire"></i> Più Venduti</h2>
<div id="piu-venduti"></div>

<!-- 搜索框 -->
<section class="search-section">
    <form method="GET" action="index.php" class="search-form">
        <div class="search-input-wrap">
            <i class="fas fa-search search-icon"></i>
            <input type="text" name="search" placeholder="Cerca prodotto..." 
                   value="<?= e($search) ?>" class="search-input">
        </div>
        <button type="submit" class="btn btn-search">Cerca</button>
        <?php if ($search !== ''): ?>
            <a href="index.php" class="btn btn-secondary">Annulla</a>
        <?php endif; ?>
    </form>
    <?php if ($search !== ''): ?>
        <p class="search-result-info">
            <?= $total_products ?> risultato<?= $total_products != 1 ? 'i' : '' ?> per "<?= e($search) ?>"
        </p>
    <?php endif; ?>
</section>

<!-- 全站商品展示 -->
<section id="all-products">
    <h2><i class="fas fa-box"></i> Tutti i Prodotti</h2>

    <?php if ($products && $products->num_rows > 0): ?>
        <div class="product-grid">
            <?php while ($product = $products->fetch_assoc()): ?>
                <div class="product-card">
                    <div class="image-container">
                        <img src="<?= e($product['indirizzo_img']) ?>" 
                             alt="<?= e($product['nome']) ?>"
                             loading="lazy">
                    </div>

                    <h3><?= e($product['nome']) ?></h3>
                    <p>
                        <?php if ($product['quantita_disponibile'] > 0): ?>
                            <i class="fas fa-check-circle in-stock"></i>
                            Disponibilità: <?= $product['quantita_disponibile'] ?> pezzi
                        <?php else: ?>
                            <i class="fas fa-times-circle out-of-stock"></i>
                            Non disponibile
                        <?php endif; ?>
                    </p>
                    <div class="price">€ <?= number_format($product['prezzo'], 2, '.', '') ?></div>

                    <?php if (isset($_SESSION['user_email'])): ?>
                        <?php if ($product['quantita_disponibile'] > 0): ?>
                            <form method="POST" action="cart.php">
                                <?php csrf_field(); ?>
                                <input type="hidden" name="product_id" value="<?= $product['id_prodotto'] ?>">
                                <div class="form-group">
                                    <label for="qty-<?= $product['id_prodotto'] ?>">Quantità</label>
                                    <input type="number" id="qty-<?= $product['id_prodotto'] ?>"
                                           name="quantity" value="1" min="1"
                                           max="<?= $product['quantita_disponibile'] ?>" required>
                                </div>
                                <button type="submit" name="add_to_cart" class="btn">
                                    <i class="fas fa-cart-plus"></i> Aggiungi al carrello
                                </button>
                            </form>
                        <?php else: ?>
                            <div style="padding: 0 1rem 1.5rem;">
                                <button type="button" class="btn btn-disabled" disabled>
                                    <i class="fas fa-times"></i> Esaurito
                                </button>
                            </div>
                        <?php endif; ?>
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

        <!-- 分页 -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?<?= $search ? 'search=' . urlencode($search) . '&' : '' ?>page=<?= $page - 1 ?>" class="btn btn-secondary">
                        <i class="fas fa-chevron-left"></i> Precedente
                    </a>
                <?php endif; ?>

                <span class="page-info">Pagina <?= $page ?> di <?= $total_pages ?></span>

                <?php if ($page < $total_pages): ?>
                    <a href="?<?= $search ? 'search=' . urlencode($search) . '&' : '' ?>page=<?= $page + 1 ?>" class="btn btn-secondary">
                        Successiva <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <div class="empty-box">
            <i class="fas fa-box-open"></i>
            <p>Nessun prodotto trovato</p>
            <?php if ($search): ?>
                <a href="index.php" class="btn">Torna a tutti i prodotti</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</section>

<?php
$products->free();
$stmt->close();
include 'includes/footer.php';
?>
