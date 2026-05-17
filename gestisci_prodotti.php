<?php
include 'config.php';
richiedi_login_venditore();

$p_iva_venditore = $_SESSION['venditore_piva'];
$messaggio = '';
$errore = '';

// 图片上传统一配置
$upload_config = [
    'dir' => 'img/prodotti/',
    'allowed_ext' => ['jpg', 'jpeg', 'png', 'gif'],
    'max_size' => 2 * 1024 * 1024, // 2MB
    'prefix' => 'prod_'
];

// 1. 删除产品 + 同步删除图片
if (isset($_POST['elimina_prodotto'])) {
    $id_prodotto = intval($_POST['id_prodotto']);
    
    $stmt_check = $conn->prepare("SELECT indirizzo_img FROM prodotto WHERE id_prodotto = ? AND p_iva = ?");
    $stmt_check->bind_param("is", $id_prodotto, $p_iva_venditore);
    $stmt_check->execute();
    $result = $stmt_check->get_result();
    
    if ($result->num_rows === 1) {
        $prodotto = $result->fetch_assoc();
        $stmt_elimina = $conn->prepare("DELETE FROM prodotto WHERE id_prodotto = ?");
        $stmt_elimina->bind_param("i", $id_prodotto);
        
        if ($stmt_elimina->execute()) {
            // 删除服务器图片
            if (!empty($prodotto['indirizzo_img']) && file_exists($prodotto['indirizzo_img'])) {
                unlink($prodotto['indirizzo_img']);
            }
            $messaggio = "Prodotto eliminato con successo!";
        } else {
            $errore = "Errore: non puoi eliminare un prodotto presente in ordini attivi.";
        }
        $stmt_elimina->close();
    } else {
        $errore = "Non sei autorizzato a eliminare questo prodotto.";
    }
    $stmt_check->close();
}

// 2. 新增产品
if (isset($_POST['aggiungi_prodotto'])) {
    // 移除冗余real_escape_string，预处理自动防注入
    $nome = $_POST['nome'];
    $prezzo = floatval($_POST['prezzo']);
    $quantita = intval($_POST['quantita_disponibile']);
    $indirizzo_img = '';

    // 处理图片上传
    if (!empty($_FILES['indirizzo_img']['name'])) {
        $file_tmp = $_FILES['indirizzo_img']['tmp_name'];
        $file_ext = strtolower(pathinfo($_FILES['indirizzo_img']['name'], PATHINFO_EXTENSION));

        // 验证格式&大小
        if (!in_array($file_ext, $upload_config['allowed_ext'])) {
            $errore = 'Formato immagine non consentito. Solo JPG, JPEG, PNG e GIF sono accettati.';
        } elseif ($_FILES['indirizzo_img']['size'] > $upload_config['max_size']) {
            $errore = 'Dimensione immagine troppo grande. Massimo 2MB.';
        }

        if (empty($errore)) {
            // 创建目录
            if (!file_exists($upload_config['dir'])) {
                mkdir($upload_config['dir'], 0755, true);
            }
            // 唯一文件名
            $new_file_name = $upload_config['prefix'] . uniqid() . '_' . time() . '.' . $file_ext;
            $indirizzo_img = $upload_config['dir'] . $new_file_name;

            if (!move_uploaded_file($file_tmp, $indirizzo_img)) {
                $errore = 'Impossibile caricare l\'immagine. Verifica i permessi della cartella.';
            }
        }
    }

    // 插入数据库
    if (empty($errore)) {
        $stmt_aggiungi = $conn->prepare("INSERT INTO prodotto (nome, prezzo, p_iva, quantita_disponibile, indirizzo_img) VALUES (?, ?, ?, ?, ?)");
        $stmt_aggiungi->bind_param("sdsis", $nome, $prezzo, $p_iva_venditore, $quantita, $indirizzo_img);
        
        if ($stmt_aggiungi->execute()) {
            $messaggio = "Prodotto aggiunto con successo!";
        } else {
            $errore = "Errore nell'aggiunta del prodotto: " . $conn->error;
            // 回滚图片
            if (!empty($indirizzo_img) && file_exists($indirizzo_img)) {
                unlink($indirizzo_img);
            }
        }
        $stmt_aggiungi->close();
    }
}

// 3. 获取待修改产品
$prodotto_da_modificare = null;
if (isset($_GET['modifica'])) {
    $id_prodotto = intval($_GET['modifica']);
    $stmt_modifica = $conn->prepare("SELECT * FROM prodotto WHERE id_prodotto = ? AND p_iva = ?");
    $stmt_modifica->bind_param("is", $id_prodotto, $p_iva_venditore);
    $stmt_modifica->execute();
    $prodotto_da_modificare = $stmt_modifica->get_result()->fetch_assoc();
    $stmt_modifica->close();
}

// 4. 保存产品修改
if (isset($_POST['salva_modifiche'])) {
    $id_prodotto = intval($_POST['id_prodotto']);
    $nome = $_POST['nome'];
    $prezzo = floatval($_POST['prezzo']);
    $quantita = intval($_POST['quantita_disponibile']);
    $indirizzo_img = null;
    $vecchia_immagine = '';

    // 获取旧图片
    $stmt_old_img = $conn->prepare("SELECT indirizzo_img FROM prodotto WHERE id_prodotto = ? AND p_iva = ?");
    $stmt_old_img->bind_param("is", $id_prodotto, $p_iva_venditore);
    $stmt_old_img->execute();
    $old_result = $stmt_old_img->get_result();
    if ($old_result->num_rows === 1) {
        $vecchia_immagine = $old_result->fetch_assoc()['indirizzo_img'];
    }
    $stmt_old_img->close();

    // 上传新图片
    if (!empty($_FILES['indirizzo_img']['name'])) {
        $file_tmp = $_FILES['indirizzo_img']['tmp_name'];
        $file_ext = strtolower(pathinfo($_FILES['indirizzo_img']['name'], PATHINFO_EXTENSION));

        if (!in_array($file_ext, $upload_config['allowed_ext'])) {
            $errore = 'Formato immagine non consentito.';
        } elseif ($_FILES['indirizzo_img']['size'] > $upload_config['max_size']) {
            $errore = 'Dimensione immagine troppo grande.';
        }

        if (empty($errore)) {
            if (!file_exists($upload_config['dir'])) {
                mkdir($upload_config['dir'], 0755, true);
            }
            $new_file_name = $upload_config['prefix'] . uniqid() . '_' . time() . '.' . $file_ext;
            $indirizzo_img = $upload_config['dir'] . $new_file_name;

            if (!move_uploaded_file($file_tmp, $indirizzo_img)) {
                $errore = 'Impossibile caricare l\'immagine.';
            }
        }
    }

    // 更新数据库
    if (empty($errore)) {
        if ($indirizzo_img !== null) {
            $stmt_salva = $conn->prepare("UPDATE prodotto SET nome = ?, prezzo = ?, quantita_disponibile = ?, indirizzo_img = ? WHERE id_prodotto = ? AND p_iva = ?");
            $stmt_salva->bind_param("sdissi", $nome, $prezzo, $quantita, $indirizzo_img, $id_prodotto, $p_iva_venditore);
        } else {
            $stmt_salva = $conn->prepare("UPDATE prodotto SET nome = ?, prezzo = ?, quantita_disponibile = ? WHERE id_prodotto = ? AND p_iva = ?");
            $stmt_salva->bind_param("sdisi", $nome, $prezzo, $quantita, $id_prodotto, $p_iva_venditore);
        }

        if ($stmt_salva->execute()) {
            $messaggio = "Prodotto modificato con successo!";
            // 删除旧图片
            if ($indirizzo_img !== null && !empty($vecchia_immagine) && file_exists($vecchia_immagine)) {
                unlink($vecchia_immagine);
            }
            $prodotto_da_modificare = null;
        } else {
            $errore = "Errore nella modifica: " . $conn->error;
            // 回滚新图片
            if ($indirizzo_img !== null && file_exists($indirizzo_img)) {
                unlink($indirizzo_img);
            }
        }
        $stmt_salva->close();
    }
}

// 获取所有产品
$stmt_prodotti = $conn->prepare("SELECT * FROM prodotto WHERE p_iva = ? ORDER BY id_prodotto DESC");
$stmt_prodotti->bind_param("s", $p_iva_venditore);
$stmt_prodotti->execute();
$prodotti = $stmt_prodotti->get_result();
$stmt_prodotti->close();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestisci Prodotti - Area Venditori</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* 全局统一变量 与全站完全一致 */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --secondary: #f97316;
            --success: #10b981;
            --danger: #ef4444;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --shadow-sm: 0 1px 2px 0 rgba(0,0,0,0.05);
            --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.1);
        }

        body {
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: var(--gray-800);
            background-color: var(--gray-50);
        }

        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 24px;
        }

        /* 导航栏 全站统一样式 */
        header {
            background: linear-gradient(135deg, var(--primary) 0%, #3b82f6 100%);
            color: white;
            padding: 1rem 0;
            box-shadow: var(--shadow-md);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        header .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 100%;
            padding: 0 40px;
            width: 100%;
        }

        header h1 {
            font-size: 1.75rem;
            font-weight: 700;
            margin: 0;
            white-space: nowrap;
            flex-shrink: 0;
        }

        nav {
            display: flex;
            align-items: center;
            gap: 2rem;
            margin-left: auto;
            flex-shrink: 0;
        }

        nav a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            padding: 0.5rem 0;
            border-bottom: 2px solid transparent;
            white-space: nowrap;
        }

        nav a:hover {
            color: #fef3c7;
            border-bottom: 2px solid #fef3c7;
        }

        /* 通用按钮样式 */
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

        .btn-success {
            background-color: var(--success);
        }

        .btn-success:hover {
            background-color: #059669;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .btn-secondary {
            background-color: var(--gray-600);
        }

        .btn-secondary:hover {
            background-color: var(--gray-700);
            box-shadow: 0 4px 12px rgba(75, 85, 99, 0.3);
        }

        /* 标题样式统一 */
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

        /* 提示框样式统一 */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .alert-danger {
            background-color: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .alert-success {
            background-color: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        /* 表单卡片美化 */
        .form-card {
            background: white;
            padding: 2.5rem;
            border-radius: 16px;
            box-shadow: var(--shadow-sm);
            margin-bottom: 3rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.6rem;
            font-weight: 500;
            color: var(--gray-700);
            font-size: 0.95rem;
        }

        .form-group input {
            width: 100%;
            padding: 0.9rem 1rem;
            border: 1px solid var(--gray-300);
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .form-group small {
            display: block;
            margin-top: 0.5rem;
            color: var(--gray-600);
            font-size: 0.85rem;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        /* 产品图片预览 */
        .product-img-preview {
            max-width: 200px;
            max-height: 200px;
            margin-top: 1rem;
            border-radius: 8px;
            box-shadow: var(--shadow-sm);
            object-fit: cover;
        }

        /* 产品表格美化 */
        .products-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background-color: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            margin-bottom: 4rem;
        }

        .products-table th {
            background-color: var(--primary);
            color: white;
            padding: 1rem 1.5rem;
            text-align: left;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .products-table td {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--gray-200);
            vertical-align: middle;
        }

        .products-table tbody tr {
            transition: background-color 0.2s ease;
        }

        .products-table tbody tr:hover {
            background-color: var(--gray-50);
        }

        .products-table tbody tr:last-child td {
            border-bottom: none;
        }

        .product-img-table {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: var(--shadow-sm);
        }

        .price {
            font-weight: 600;
            color: var(--danger);
        }

        .stock {
            font-weight: 500;
            color: var(--success);
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .action-buttons .btn {
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
        }

        /* 空状态美化 */
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
            margin-bottom: 2rem;
        }

        /* 响应式适配 */
        @media (max-width: 768px) {
            header .container {
                flex-direction: column;
                gap: 1rem;
                padding: 0 24px;
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
            .form-card {
                padding: 1.8rem;
            }
            .form-actions {
                flex-direction: column;
            }
            /* 移动端表格适配 */
            .products-table {
                display: block;
                overflow-x: auto;
            }
            .products-table th,
            .products-table td {
                padding: 1rem;
                white-space: nowrap;
            }
        }

        @media (max-width: 480px) {
            .btn {
                padding: 0.6rem 1.2rem;
                font-size: 0.9rem;
            }
            .empty-state {
                padding: 3rem 1.5rem;
            }
            .empty-state i {
                font-size: 4rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1><i class="fas fa-store"></i> Area Venditori</h1>
            <nav>
                <span><i class="fas fa-user"></i> Ciao, <?= $_SESSION['venditore_ragione_sociale'] ?></span>
                <a href="dashboard_venditore.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="gestisci_prodotti.php" style="border-bottom: 2px solid #fef3c7;"><i class="fas fa-box"></i> Prodotti</a>
                <a href="ordini_venditore.php"><i class="fas fa-file-invoice"></i> Ordini</a>
                <a href="profilo_venditore.php"><i class="fas fa-user-cog"></i> Profilo</a>
                <a href="logout.php" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </div>
    </header>

    <div class="container">
        <h2><i class="fas fa-<?= $prodotto_da_modificare ? 'edit' : 'plus-circle' ?>"></i> <?= $prodotto_da_modificare ? 'Modifica Prodotto' : 'Aggiungi Nuovo Prodotto' ?></h2>
        
        <?php if ($messaggio): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= $messaggio ?>
            </div>
        <?php endif; ?>
        <?php if ($errore): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?= $errore ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="form-card">
            <?php if ($prodotto_da_modificare): ?>
                <input type="hidden" name="id_prodotto" value="<?= $prodotto_da_modificare['id_prodotto'] ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="nome"><i class="fas fa-tag"></i> Nome Prodotto</label>
                <input type="text" id="nome" name="nome" required value="<?= $prodotto_da_modificare['nome'] ?? '' ?>" placeholder="Inserisci il nome del prodotto">
            </div>
            
            <div class="form-group">
                <label for="prezzo"><i class="fas fa-euro-sign"></i> Prezzo (€)</label>
                <input type="number" step="0.01" id="prezzo" name="prezzo" required value="<?= $prodotto_da_modificare['prezzo'] ?? '' ?>" placeholder="Inserisci il prezzo">
            </div>
            
            <div class="form-group">
                <label for="qty"><i class="fas fa-boxes"></i> Quantità Disponibile</label>
                <input type="number" id="qty" name="quantita_disponibile" required value="<?= $prodotto_da_modificare['quantita_disponibile'] ?? '' ?>" placeholder="Inserisci la quantità disponibile">
            </div>

            <div class="form-group">
                <label for="img"><i class="fas fa-image"></i> Immagine Prodotto</label>
                <input type="file" id="img" name="indirizzo_img" accept="image/jpg, image/jpeg, image/png, image/gif">
                <small>Formati consentiti: JPG, JPEG, PNG, GIF | Dimensione massima: 2MB</small>
                
                <?php if ($prodotto_da_modificare && !empty($prodotto_da_modificare['indirizzo_img'])): ?>
                    <br>
                    <small>Immagine attuale:</small>
                    <br>
                    <img src="<?= $prodotto_da_modificare['indirizzo_img'] ?>" class="product-img-preview" alt="Immagine prodotto">
                    <br>
                    <small class="text-muted">Lascia vuoto per mantenere l'immagine attuale</small>
                <?php endif; ?>
            </div>

            <div class="form-actions">
                <?php if ($prodotto_da_modificare): ?>
                    <button type="submit" name="salva_modifiche" class="btn btn-success">
                        <i class="fas fa-save"></i> Salva Modifiche
                    </button>
                    <a href="gestisci_prodotti.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Annulla
                    </a>
                <?php else: ?>
                    <button type="submit" name="aggiungi_prodotto" class="btn btn-success">
                        <i class="fas fa-plus"></i> Aggiungi Prodotto
                    </button>
                <?php endif; ?>
            </div>
        </form>

        <h2><i class="fas fa-boxes"></i> I Tuoi Prodotti</h2>
        <?php if ($prodotti->num_rows > 0): ?>
            <table class="products-table">
                <thead>
                    <tr>
                        <th>Immagine</th>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Prezzo</th>
                        <th>Disponibilità</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($prodotto = $prodotti->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <?php if (!empty($prodotto['indirizzo_img'])): ?>
                                    <img src="<?= $prodotto['indirizzo_img'] ?>" class="product-img-table" alt="<?= $prodotto['nome'] ?>">
                                <?php else: ?>
                                    <span class="text-muted"><i class="fas fa-image"></i> Nessuna</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $prodotto['id_prodotto'] ?></td>
                            <td><?= $prodotto['nome'] ?></td>
                            <td class="price">€ <?= number_format($prodotto['prezzo'], 2, ',', '.') ?></td>
                            <td class="stock"><?= $prodotto['quantita_disponibile'] ?> pezzi</td>
                            <td>
                                <div class="action-buttons">
                                    <a href="gestisci_prodotti.php?modifica=<?= $prodotto['id_prodotto'] ?>" class="btn">
                                        <i class="fas fa-edit"></i> Modifica
                                    </a>
                                    <form method="POST" style="display: inline-block;">
                                        <input type="hidden" name="id_prodotto" value="<?= $prodotto['id_prodotto'] ?>">
                                        <button type="submit" name="elimina_prodotto" class="btn btn-danger" onclick="return confirm('Sei sicuro di voler eliminare questo prodotto?')">
                                            <i class="fas fa-trash"></i> Elimina
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-box-open"></i>
                <p>Non hai ancora aggiunto prodotti al catalogo.</p>
                <a href="#aggiungi" class="btn">
                    <i class="fas fa-plus"></i> Aggiungi il tuo primo prodotto
                </a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>