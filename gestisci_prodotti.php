<?php
/**
 * 卖家商品管理页面
 * 功能：商品新增、修改、删除、图片上传、商品列表展示
 */
include 'config.php';
richiedi_login_venditore();
verify_csrf();

$page_title = 'Gestisci Prodotti';
$p_iva_venditore = $_SESSION['venditore_piva'];

$messaggio = '';
$errore = '';

// 图片上传配置
$upload_config = [
    'dir' => 'img/prodotti/',
    'allowed_ext' => ['jpg', 'jpeg', 'png', 'gif'],
    'max_size' => 3 * 1024 * 1024,
    'prefix' => 'prod_'
];

// ===================== 删除商品 =====================
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

// ===================== 新增商品 =====================
if (isset($_POST['aggiungi_prodotto'])) {
    $nome = trim($_POST['nome']);
    $prezzo = floatval($_POST['prezzo']);
    $quantita = intval($_POST['quantita_disponibile']);
    $descrizione = trim($_POST['descrizione'] ?? '');
    $indirizzo_img = '';

    if ($prezzo <= 0 || $quantita < 0) {
        $errore = 'Prezzo e quantità devono essere validi.';
    } elseif (!empty($_FILES['indirizzo_img']['name'])) {
        $file_tmp = $_FILES['indirizzo_img']['tmp_name'];
        $file_ext = strtolower(pathinfo($_FILES['indirizzo_img']['name'], PATHINFO_EXTENSION));

        if (!in_array($file_ext, $upload_config['allowed_ext'])) {
            $errore = 'Formato immagine non consentito.';
        } elseif ($_FILES['indirizzo_img']['size'] > $upload_config['max_size']) {
            $errore = 'Dimensione immagine troppo grande (max 3MB).';
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

    if (empty($errore)) {
        $stmt_aggiungi = $conn->prepare("INSERT INTO prodotto (nome, descrizione, prezzo, p_iva, quantita_disponibile, indirizzo_img) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt_aggiungi->bind_param("ssdiss", $nome, $descrizione, $prezzo, $p_iva_venditore, $quantita, $indirizzo_img);

        if ($stmt_aggiungi->execute()) {
            $messaggio = "Prodotto aggiunto con successo!";
        } else {
            $errore = "Errore nell'aggiunta del prodotto.";
            if (!empty($indirizzo_img) && file_exists($indirizzo_img)) {
                unlink($indirizzo_img);
            }
        }
        $stmt_aggiungi->close();
    }
}

// ===================== 加载待编辑商品 =====================
$prodotto_da_modificare = null;
if (isset($_GET['modifica'])) {
    $id_prodotto = intval($_GET['modifica']);
    $stmt_modifica = $conn->prepare("SELECT id_prodotto, nome, descrizione, prezzo, quantita_disponibile, indirizzo_img FROM prodotto WHERE id_prodotto = ? AND p_iva = ?");
    $stmt_modifica->bind_param("is", $id_prodotto, $p_iva_venditore);
    $stmt_modifica->execute();
    $prodotto_da_modificare = $stmt_modifica->get_result()->fetch_assoc();
    $stmt_modifica->close();
}

// ===================== 保存商品修改 =====================
if (isset($_POST['salva_modifiche'])) {
    $id_prodotto = intval($_POST['id_prodotto']);
    $nome = trim($_POST['nome']);
    $prezzo = floatval($_POST['prezzo']);
    $quantita = intval($_POST['quantita_disponibile']);
    $descrizione = trim($_POST['descrizione'] ?? '');
    $indirizzo_img = null;
    $vecchia_immagine = '';

    if ($prezzo <= 0 || $quantita < 0) {
        $errore = 'Prezzo e quantità devono essere validi.';
    } else {
        $stmt_old_img = $conn->prepare("SELECT indirizzo_img FROM prodotto WHERE id_prodotto = ? AND p_iva = ?");
        $stmt_old_img->bind_param("is", $id_prodotto, $p_iva_venditore);
        $stmt_old_img->execute();
        $old_result = $stmt_old_img->get_result();
        if ($old_result->num_rows === 1) {
            $vecchia_immagine = $old_result->fetch_assoc()['indirizzo_img'];
        }
        $stmt_old_img->close();

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
    }

    if (empty($errore)) {
        if ($indirizzo_img !== null) {
            $stmt_salva = $conn->prepare("UPDATE prodotto SET nome = ?, descrizione = ?, prezzo = ?, quantita_disponibile = ?, indirizzo_img = ? WHERE id_prodotto = ? AND p_iva = ?");
            $stmt_salva->bind_param("ssdissi", $nome, $descrizione, $prezzo, $quantita, $indirizzo_img, $id_prodotto, $p_iva_venditore);
        } else {
            $stmt_salva = $conn->prepare("UPDATE prodotto SET nome = ?, descrizione = ?, prezzo = ?, quantita_disponibile = ? WHERE id_prodotto = ? AND p_iva = ?");
            $stmt_salva->bind_param("ssdisi", $nome, $descrizione, $prezzo, $quantita, $id_prodotto, $p_iva_venditore);
        }

        if ($stmt_salva->execute()) {
            $messaggio = "Prodotto modificato con successo!";
            if ($indirizzo_img !== null && !empty($vecchia_immagine) && file_exists($vecchia_immagine)) {
                unlink($vecchia_immagine);
            }
            $prodotto_da_modificare = null;
        } else {
            $errore = "Errore nella modifica.";
            if ($indirizzo_img !== null && file_exists($indirizzo_img)) {
                unlink($indirizzo_img);
            }
        }
        $stmt_salva->close();
    }
}

// ===================== 查询商品列表 =====================
$stmt_prodotti = $conn->prepare("SELECT id_prodotto, nome, prezzo, quantita_disponibile, indirizzo_img FROM prodotto WHERE p_iva = ? ORDER BY id_prodotto DESC");
$stmt_prodotti->bind_param("s", $p_iva_venditore);
$stmt_prodotti->execute();
$prodotti = $stmt_prodotti->get_result();
$stmt_prodotti->close();

include 'includes/header.php';
?>

<h2><i class="fas fa-<?= $prodotto_da_modificare ? 'edit' : 'plus-circle' ?>"></i> <?= $prodotto_da_modificare ? 'Modifica Prodotto' : 'Aggiungi Nuovo Prodotto' ?></h2>

<?php if ($messaggio): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> <?= e($messaggio) ?>
    </div>
<?php endif; ?>
<?php if ($errore): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i> <?= e($errore) ?>
    </div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" class="form-card">
    <?php csrf_field(); ?>
    <?php if ($prodotto_da_modificare): ?>
        <input type="hidden" name="id_prodotto" value="<?= $prodotto_da_modificare['id_prodotto'] ?>">
    <?php endif; ?>

    <div class="form-group">
        <label for="nome"><i class="fas fa-tag"></i> Nome Prodotto</label>
        <input type="text" id="nome" name="nome" required 
               value="<?= e($prodotto_da_modificare['nome'] ?? '') ?>" 
               placeholder="Inserisci il nome del prodotto">
    </div>

    <div class="form-group">
        <label for="descrizione"><i class="fas fa-align-left"></i> Descrizione</label>
        <textarea id="descrizione" name="descrizione" rows="3" 
                  placeholder="Descrizione del prodotto"><?= e($prodotto_da_modificare['descrizione'] ?? '') ?></textarea>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label for="prezzo"><i class="fas fa-euro-sign"></i> Prezzo (€)</label>
            <input type="number" step="0.01" id="prezzo" name="prezzo" required min="0.01"
                   value="<?= e($prodotto_da_modificare['prezzo'] ?? '') ?>" 
                   placeholder="Inserisci il prezzo">
        </div>
        <div class="form-group">
            <label for="qty"><i class="fas fa-boxes"></i> Quantità Disponibile</label>
            <input type="number" id="qty" name="quantita_disponibile" required min="0"
                   value="<?= e($prodotto_da_modificare['quantita_disponibile'] ?? '') ?>" 
                   placeholder="Inserisci la quantità">
        </div>
    </div>

    <div class="form-group">
        <label for="img"><i class="fas fa-image"></i> Immagine Prodotto</label>
        <input type="file" id="img" name="indirizzo_img" accept="image/jpg, image/jpeg, image/png, image/gif">
        <small>Formati: JPG, JPEG, PNG, GIF | Max: 3MB</small>

        <?php if ($prodotto_da_modificare && !empty($prodotto_da_modificare['indirizzo_img'])): ?>
            <br><small>Immagine attuale:</small><br>
            <img src="<?= e($prodotto_da_modificare['indirizzo_img']) ?>" class="product-img-preview" alt="Immagine prodotto">
            <br><small class="text-muted">Lascia vuoto per mantenere l'immagine attuale</small>
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

<h2><i class="fas fa-boxes"></i> I Tuoi Prodotti (<?= $prodotti->num_rows ?>)</h2>

<?php if ($prodotti->num_rows > 0): ?>
    <div class="table-container">
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
                                <img src="<?= e($prodotto['indirizzo_img']) ?>" class="product-img-table" alt="<?= e($prodotto['nome']) ?>" loading="lazy">
                            <?php else: ?>
                                <span class="text-muted"><i class="fas fa-image"></i> Nessuna</span>
                            <?php endif; ?>
                        </td>
                        <td><?= $prodotto['id_prodotto'] ?></td>
                        <td><?= e($prodotto['nome']) ?></td>
                        <td class="price">€ <?= number_format($prodotto['prezzo'], 2, ',', '.') ?></td>
                        <td class="stock <?= $prodotto['quantita_disponibile'] == 0 ? 'out-of-stock' : '' ?>">
                            <?= $prodotto['quantita_disponibile'] ?> pezzi
                        </td>
                        <td>
                            <div class="action-buttons">
                                <a href="gestisci_prodotti.php?modifica=<?= $prodotto['id_prodotto'] ?>" class="btn">
                                    <i class="fas fa-edit"></i> Modifica
                                </a>
                                <form method="POST" style="display: inline-block;" onsubmit="return confirm('Sei sicuro di voler eliminare questo prodotto?')">
                                    <?php csrf_field(); ?>
                                    <input type="hidden" name="id_prodotto" value="<?= $prodotto['id_prodotto'] ?>">
                                    <button type="submit" name="elimina_prodotto" class="btn btn-danger">
                                        <i class="fas fa-trash"></i> Elimina
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <div class="empty-box">
        <i class="fas fa-box-open"></i>
        <p>Non hai ancora aggiunto prodotti al catalogo.</p>
    </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
