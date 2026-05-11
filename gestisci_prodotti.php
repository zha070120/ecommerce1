<?php include 'config.php'; richiedi_login_venditore();

$p_iva_venditore = $_SESSION['venditore_piva'];
$messaggio = '';
$errore = '';

// 图片上传配置（全局统一管理）
$upload_config = [
    'dir' => 'img/prodotti/',
    'allowed_ext' => ['jpg', 'jpeg', 'png', 'gif'],
    'max_size' => 2 * 1024 * 1024, // 2MB
    'prefix' => 'prod_'
];

// 1. Eliminazione prodotto（新增：同时删除服务器上的图片文件）
if (isset($_POST['elimina_prodotto'])) {
    $id_prodotto = intval($_POST['id_prodotto']);
    // Verifica che il prodotto appartenga al venditore
    $stmt_check = $conn->prepare("SELECT indirizzo_img FROM prodotto WHERE id_prodotto = ? AND p_iva = ?");
    $stmt_check->bind_param("is", $id_prodotto, $p_iva_venditore);
    $stmt_check->execute();
    $result = $stmt_check->get_result();
    
    if ($result->num_rows == 1) {
        $prodotto = $result->fetch_assoc();
        $stmt_elimina = $conn->prepare("DELETE FROM prodotto WHERE id_prodotto = ?");
        $stmt_elimina->bind_param("i", $id_prodotto);
        
        if ($stmt_elimina->execute()) {
            // 删除服务器上的图片文件
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

// 2. Aggiunta nuovo prodotto（新增：图片上传处理）
if (isset($_POST['aggiungi_prodotto'])) {
    $nome = $conn->real_escape_string($_POST['nome']);
    $prezzo = floatval($_POST['prezzo']);
    $quantita = intval($_POST['quantita_disponibile']);
    $indirizzo_img = '';

    // 处理图片上传
    if (!empty($_FILES['indirizzo_img']['name'])) {
        $file_name = $_FILES['indirizzo_img']['name'];
        $file_size = $_FILES['indirizzo_img']['size'];
        $file_tmp = $_FILES['indirizzo_img']['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        // 验证文件类型
        if (!in_array($file_ext, $upload_config['allowed_ext'])) {
            $errore = 'Formato immagine non consentito. Solo JPG, JPEG, PNG e GIF sono accettati.';
        }
        // 验证文件大小
        elseif ($file_size > $upload_config['max_size']) {
            $errore = 'Dimensione immagine troppo grande. Massimo 2MB.';
        }

        if (empty($errore)) {
            // 确保上传目录存在
            if (!file_exists($upload_config['dir'])) {
                mkdir($upload_config['dir'], 0755, true);
            }
            
            // 重命名文件防止重复
            $new_file_name = $upload_config['prefix'] . uniqid() . '_' . time() . '.' . $file_ext;
            $indirizzo_img = $upload_config['dir'] . $new_file_name;

            // 移动文件到目标目录
            if (!move_uploaded_file($file_tmp, $indirizzo_img)) {
                $errore = 'Impossibile caricare l\'immagine. Verifica i permessi della cartella img/prodotti/.';
            }
        }
    }

    // 只有没有错误时才插入数据库
    if (empty($errore)) {
        $stmt_aggiungi = $conn->prepare("
            INSERT INTO prodotto (nome, prezzo, p_iva, quantita_disponibile, indirizzo_img)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt_aggiungi->bind_param("sdsis", $nome, $prezzo, $p_iva_venditore, $quantita, $indirizzo_img);
        if ($stmt_aggiungi->execute()) {
            $messaggio = "Prodotto aggiunto con successo!";
        } else {
            $errore = "Errore nell'aggiunta del prodotto: " . $conn->error;
            // 如果数据库插入失败，删除已上传的图片
            if (!empty($indirizzo_img) && file_exists($indirizzo_img)) {
                unlink($indirizzo_img);
            }
        }
        $stmt_aggiungi->close();
    }
}

// 3. Modifica prodotto（不变）
$prodotto_da_modificare = null;
if (isset($_GET['modifica'])) {
    $id_prodotto = intval($_GET['modifica']);
    $stmt_modifica = $conn->prepare("SELECT * FROM prodotto WHERE id_prodotto = ? AND p_iva = ?");
    $stmt_modifica->bind_param("is", $id_prodotto, $p_iva_venditore);
    $stmt_modifica->execute();
    $prodotto_da_modificare = $stmt_modifica->get_result()->fetch_assoc();
    $stmt_modifica->close();
}

// 4. Salvataggio modifiche（新增：图片上传处理）
if (isset($_POST['salva_modifiche'])) {
    $id_prodotto = intval($_POST['id_prodotto']);
    $nome = $conn->real_escape_string($_POST['nome']);
    $prezzo = floatval($_POST['prezzo']);
    $quantita = intval($_POST['quantita_disponibile']);
    $indirizzo_img = null; // null表示不更新图片
    $vecchia_immagine = '';

    // 获取旧图片路径
    $stmt_old_img = $conn->prepare("SELECT indirizzo_img FROM prodotto WHERE id_prodotto = ? AND p_iva = ?");
    $stmt_old_img->bind_param("is", $id_prodotto, $p_iva_venditore);
    $stmt_old_img->execute();
    $old_result = $stmt_old_img->get_result();
    if ($old_result->num_rows == 1) {
        $vecchia_immagine = $old_result->fetch_assoc()['indirizzo_img'];
    }
    $stmt_old_img->close();

    // 处理新图片上传
    if (!empty($_FILES['indirizzo_img']['name'])) {
        $file_name = $_FILES['indirizzo_img']['name'];
        $file_size = $_FILES['indirizzo_img']['size'];
        $file_tmp = $_FILES['indirizzo_img']['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if (!in_array($file_ext, $upload_config['allowed_ext'])) {
            $errore = 'Formato immagine non consentito. Solo JPG, JPEG, PNG e GIF sono accettati.';
        } elseif ($file_size > $upload_config['max_size']) {
            $errore = 'Dimensione immagine troppo grande. Massimo 2MB.';
        }

        if (empty($errore)) {
            if (!file_exists($upload_config['dir'])) {
                mkdir($upload_config['dir'], 0755, true);
            }
            
            $new_file_name = $upload_config['prefix'] . uniqid() . '_' . time() . '.' . $file_ext;
            $indirizzo_img = $upload_config['dir'] . $new_file_name;

            if (!move_uploaded_file($file_tmp, $indirizzo_img)) {
                $errore = 'Impossibile caricare l\'immagine. Verifica i permessi della cartella img/prodotti/.';
            }
        }
    }

    if (empty($errore)) {
        // 构建SQL语句
        if ($indirizzo_img !== null) {
            // 有新图片，更新图片字段
            $stmt_salva = $conn->prepare("
                UPDATE prodotto 
                SET nome = ?, prezzo = ?, quantita_disponibile = ?, indirizzo_img = ?
                WHERE id_prodotto = ? AND p_iva = ?
            ");
            $stmt_salva->bind_param("sdissi", $nome, $prezzo, $quantita, $indirizzo_img, $id_prodotto, $p_iva_venditore);
        } else {
            // 没有新图片，不更新图片字段
            $stmt_salva = $conn->prepare("
                UPDATE prodotto 
                SET nome = ?, prezzo = ?, quantita_disponibile = ?
                WHERE id_prodotto = ? AND p_iva = ?
            ");
            $stmt_salva->bind_param("sdisi", $nome, $prezzo, $quantita, $id_prodotto, $p_iva_venditore);
        }

        if ($stmt_salva->execute()) {
            $messaggio = "Prodotto modificato con successo!";
            // 更新成功后删除旧图片
            if ($indirizzo_img !== null && !empty($vecchia_immagine) && file_exists($vecchia_immagine)) {
                unlink($vecchia_immagine);
            }
            $prodotto_da_modificare = null;
        } else {
            $errore = "Errore nella modifica: " . $conn->error;
            // 如果数据库更新失败，删除已上传的新图片
            if ($indirizzo_img !== null && file_exists($indirizzo_img)) {
                unlink($indirizzo_img);
            }
        }
        $stmt_salva->close();
    }
}

// Recupera tutti i prodotti del venditore（不变）
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
    <title>Gestisci Prodotti - Area Venditori</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* 新增：图片样式 */
        .product-img-preview {
            max-width: 150px;
            max-height: 150px;
            margin-top: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .product-img-table {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <header>
        <h1>Area Venditori</h1>
        <nav>
            <span>Ciao, <?= $_SESSION['venditore_ragione_sociale'] ?></span>

            <a href="dashboard_venditore.php">Dashboard</a>
            <a href="gestisci_prodotti.php">Prodotti</a>
            <a href="ordini_venditore.php">Ordini</a>
            <a href="profilo_venditore.php">Profilo</a>
            <a href="logout.php" class="btn btn-danger">Logout</a>
        </nav>
    </header>

    <div class="container">
        <h2><?= $prodotto_da_modificare ? 'Modifica Prodotto' : 'Aggiungi Nuovo Prodotto' ?></h2>
        <?php if ($messaggio): ?><div class="alert alert-success"><?= $messaggio ?></div><?php endif; ?>
        <?php if ($errore): ?><div class="alert alert-danger"><?= $errore ?></div><?php endif; ?>

        <!-- 重要：表单必须添加 enctype="multipart/form-data" 才能上传文件 -->
        <form method="POST" enctype="multipart/form-data" style="background: white; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem;">
            <?php if ($prodotto_da_modificare): ?>
                <input type="hidden" name="id_prodotto" value="<?= $prodotto_da_modificare['id_prodotto'] ?>">
            <?php endif; ?>

            <div class="form-group">
                <label>Nome Prodotto</label>
                <input type="text" name="nome" required value="<?= $prodotto_da_modificare['nome'] ?? '' ?>">
            </div>
            <div class="form-group">
                <label>Prezzo (€)</label>
                <input type="number" step="0.01" name="prezzo" required value="<?= $prodotto_da_modificare['prezzo'] ?? '' ?>">
            </div>
            <div class="form-group">
                <label>Quantità Disponibile</label>
                <input type="number" name="quantita_disponibile" required value="<?= $prodotto_da_modificare['quantita_disponibile'] ?? '' ?>">
            </div>
            
            <!-- 替换原来的文本输入框为文件上传字段 -->
            <div class="form-group">
                <label>Immagine Prodotto</label>
                <input type="file" name="indirizzo_img" accept="image/jpg, image/jpeg, image/png, image/gif">
                <small>Formati consentiti: JPG, JPEG, PNG, GIF | Dimensione massima: 2MB</small>
                
                <!-- 编辑时显示当前图片 -->
                <?php if ($prodotto_da_modificare && !empty($prodotto_da_modificare['indirizzo_img'])): ?>
                    <br>
                    <small>Immagine attuale:</small>
                    <br>
                    <img src="<?= $prodotto_da_modificare['indirizzo_img'] ?>" class="product-img-preview" alt="Immagine prodotto">
                    <br>
                    <small class="text-muted">Lascia vuoto per mantenere l'immagine attuale</small>
                <?php endif; ?>
            </div>

            <?php if ($prodotto_da_modificare): ?>
                <button type="submit" name="salva_modifiche" class="btn btn-success">Salva Modifiche</button>
                <a href="gestisci_prodotti.php" class="btn">Annulla</a>
            <?php else: ?>
                <button type="submit" name="aggiungi_prodotto" class="btn btn-success">Aggiungi Prodotto</button>
            <?php endif; ?>
        </form>

        <!-- Lista prodotti esistenti（新增：图片列） -->
        <h2>I Tuoi Prodotti</h2>
        <?php if ($prodotti->num_rows > 0): ?>
            <table>
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
                                    <span class="text-muted">Nessuna</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $prodotto['id_prodotto'] ?></td>
                            <td><?= $prodotto['nome'] ?></td>
                            <td>€ <?= number_format($prodotto['prezzo'], 2, ',', '.') ?></td>
                            <td><?= $prodotto['quantita_disponibile'] ?> pezzi</td>
                            <td>
                                <a href="gestisci_prodotti.php?modifica=<?= $prodotto['id_prodotto'] ?>" class="btn">Modifica</a>
                                <form method="POST" style="display: inline-block;">
                                    <input type="hidden" name="id_prodotto" value="<?= $prodotto['id_prodotto'] ?>">
                                    <button type="submit" name="elimina_prodotto" class="btn btn-danger" onclick="return confirm('Sei sicuro di voler eliminare questo prodotto?')">Elimina</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Non hai ancora aggiunto prodotti al catalogo.</p>
        <?php endif; ?>
    </div>
</body>
</html>