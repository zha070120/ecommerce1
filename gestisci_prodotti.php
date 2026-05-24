<?php
/**
 * 卖家商品管理页面
 * 功能：商品新增、修改、删除、图片上传、商品列表展示
 * 权限：仅已登录卖家可访问
 */

// 引入数据库连接、会话权限公共配置文件
include 'config.php';

// 校验卖家登录状态，未登录自动跳转登录页，拦截非法访问
richiedi_login_venditore();

// 获取当前登录卖家的税号标识，用于数据权限隔离
$p_iva_venditore = $_SESSION['venditore_piva'];

// 定义消息提示变量
$messaggio = ''; // 操作成功提示文本
$errore = '';    // 操作失败错误文本

// ===================== 商品图片上传全局配置 =====================
$upload_config = [
    'dir' => 'img/prodotti/',               // 商品图片存储目录路径
    'allowed_ext' => ['jpg', 'jpeg', 'png', 'gif'], // 允许上传的图片后缀格式
    'max_size' => 3 * 1024 * 1024,          // 单张图片最大限制 3MB
    'prefix' => 'prod_'                     // 图片文件名统一前缀，区分业务文件
];

// ===================== 1. 商品删除功能逻辑 =====================
if (isset($_POST['elimina_prodotto'])) {
    // 强制转为整型，过滤非法参数，规避安全风险
    $id_prodotto = intval($_POST['id_prodotto']);
    
    // 先校验商品归属权，只能删除自己上架的商品，同时查询关联图片路径
    $stmt_check = $conn->prepare("SELECT indirizzo_img FROM prodotto WHERE id_prodotto = ? AND p_iva = ?");
    $stmt_check->bind_param("is", $id_prodotto, $p_iva_venditore);
    $stmt_check->execute();
    $result = $stmt_check->get_result();
    
    // 校验通过，商品属于当前卖家
    if ($result->num_rows === 1) {
        $prodotto = $result->fetch_assoc();
        // 执行数据库商品删除操作
        $stmt_elimina = $conn->prepare("DELETE FROM prodotto WHERE id_prodotto = ?");
        $stmt_elimina->bind_param("i", $id_prodotto);
        
        if ($stmt_elimina->execute()) {
            // 数据库删除成功后，同步删除服务器本地存储的商品图片
            if (!empty($prodotto['indirizzo_img']) && file_exists($prodotto['indirizzo_img'])) {
                unlink($prodotto['indirizzo_img']);
            }
            $messaggio = "Prodotto eliminato con successo!";
        } else {
            // 删除失败，通常因商品已绑定有效订单，受数据库外键约束限制
            $errore = "Errore: non puoi eliminare un prodotto presente in ordini attivi.";
        }
        $stmt_elimina->close();
    } else {
        // 无权限操作他人商品
        $errore = "Non sei autorizzato a eliminare questo prodotto.";
    }
    $stmt_check->close();
}

// ===================== 2. 新增商品功能逻辑 =====================
if (isset($_POST['aggiungi_prodotto'])) {
    // 获取表单提交数据，并规范数据类型
    $nome = $_POST['nome'];
    $prezzo = floatval($_POST['prezzo']);               // 价格转为浮点小数
    $quantita = intval($_POST['quantita_disponibile']); // 库存转为整数
    $indirizzo_img = '';                                // 初始化图片存储路径

    // 判断是否上传商品图片
    if (!empty($_FILES['indirizzo_img']['name'])) {
        $file_tmp = $_FILES['indirizzo_img']['tmp_name']; // 文件临时缓存路径
        // 获取文件后缀并统一转为小写，格式校验统一标准
        $file_ext = strtolower(pathinfo($_FILES['indirizzo_img']['name'], PATHINFO_EXTENSION));

        // 校验图片格式合法性
        if (!in_array($file_ext, $upload_config['allowed_ext'])) {
            $errore = 'Formato immagine non consentito. Solo JPG, JPEG, PNG e GIF sono accettati.';
        }
        // 校验图片体积是否超出限制
        elseif ($_FILES['indirizzo_img']['size'] > $upload_config['max_size']) {
            $errore = 'Dimensione immagine troppo grande. Massimo 3MB.';
        }

        // 格式大小校验无误，执行图片保存
        if (empty($errore)) {
            // 目录不存在则自动递归创建文件夹
            if (!file_exists($upload_config['dir'])) {
                mkdir($upload_config['dir'], 0755, true);
            }
            // 拼接唯一文件名，防止同名文件覆盖丢失
            $new_file_name = $upload_config['prefix'] . uniqid() . '_' . time() . '.' . $file_ext;
            $indirizzo_img = $upload_config['dir'] . $new_file_name;

            // 将临时文件迁移至正式存储目录
            if (!move_uploaded_file($file_tmp, $indirizzo_img)) {
                $errore = 'Impossibile caricare l\'immagine. Verifica i permessi della cartella.';
            }
        }
    }

    // 无错误则将商品信息写入数据库
    if (empty($errore)) {
        // 预处理语句，有效防止SQL注入攻击
        $stmt_aggiungi = $conn->prepare("INSERT INTO prodotto (nome, prezzo, p_iva, quantita_disponibile, indirizzo_img) VALUES (?, ?, ?, ?, ?)");
        $stmt_aggiungi->bind_param("sdsis", $nome, $prezzo, $p_iva_venditore, $quantita, $indirizzo_img);
        
        if ($stmt_aggiungi->execute()) {
            $messaggio = "Prodotto aggiunto con successo!";
        } else {
            // 数据库写入失败，删除已上传的图片，避免服务器产生冗余垃圾文件
            $errore = "Errore nell'aggiunta del prodotto: " . $conn->error;
            if (!empty($indirizzo_img) && file_exists($indirizzo_img)) {
                unlink($indirizzo_img);
            }
        }
        $stmt_aggiungi->close();
    }
}

// ===================== 3. 获取待编辑商品数据 =====================
$prodotto_da_modificare = null;
// 页面携带修改参数，进入商品编辑模式
if (isset($_GET['modifica'])) {
    $id_prodotto = intval($_GET['modifica']);
    // 查询当前卖家名下指定ID的商品信息
    $stmt_modifica = $conn->prepare("SELECT * FROM prodotto WHERE id_prodotto = ? AND p_iva = ?");
    $stmt_modifica->bind_param("is", $id_prodotto, $p_iva_venditore);
    $stmt_modifica->execute();
    $prodotto_da_modificare = $stmt_modifica->get_result()->fetch_assoc();
    $stmt_modifica->close();
}

// ===================== 4. 保存商品修改数据 =====================
if (isset($_POST['salva_modifiche'])) {
    // 获取编辑表单数据并规范类型
    $id_prodotto = intval($_POST['id_prodotto']);
    $nome = $_POST['nome'];
    $prezzo = floatval($_POST['prezzo']);
    $quantita = intval($_POST['quantita_disponibile']);
    $indirizzo_img = null;  // 标记是否更换新图片
    $vecchia_immagine = ''; // 存储商品原有图片路径

    // 查询数据库，获取旧图片地址
    $stmt_old_img = $conn->prepare("SELECT indirizzo_img FROM prodotto WHERE id_prodotto = ? AND p_iva = ?");
    $stmt_old_img->bind_param("is", $id_prodotto, $p_iva_venditore);
    $stmt_old_img->execute();
    $old_result = $stmt_old_img->get_result();
    if ($old_result->num_rows === 1) {
        $vecchia_immagine = $old_result->fetch_assoc()['indirizzo_img'];
    }
    $stmt_old_img->close();

    // 处理新图片上传逻辑
    if (!empty($_FILES['indirizzo_img']['name'])) {
        $file_tmp = $_FILES['indirizzo_img']['tmp_name'];
        $file_ext = strtolower(pathinfo($_FILES['indirizzo_img']['name'], PATHINFO_EXTENSION));

        // 校验图片格式与体积
        if (!in_array($file_ext, $upload_config['allowed_ext'])) {
            $errore = 'Formato immagine non consentito.';
        } elseif ($_FILES['indirizzo_img']['size'] > $upload_config['max_size']) {
            $errore = 'Dimensione immagine troppo grande.';
        }

        if (empty($errore)) {
            // 自动创建存储目录
            if (!file_exists($upload_config['dir'])) {
                mkdir($upload_config['dir'], 0755, true);
            }
            // 生成全新唯一文件名
            $new_file_name = $upload_config['prefix'] . uniqid() . '_' . time() . '.' . $file_ext;
            $indirizzo_img = $upload_config['dir'] . $new_file_name;

            if (!move_uploaded_file($file_tmp, $indirizzo_img)) {
                $errore = 'Impossibile caricare l\'immagine.';
            }
        }
    }

    // 数据校验通过，执行数据库更新
    if (empty($errore)) {
        // 区分是否上传新图片，拼接不同更新语句
        if ($indirizzo_img !== null) {
            // 更换图片，同步更新图片字段
            $stmt_salva = $conn->prepare("UPDATE prodotto SET nome = ?, prezzo = ?, quantita_disponibile = ?, indirizzo_img = ? WHERE id_prodotto = ? AND p_iva = ?");
            $stmt_salva->bind_param("sdissi", $nome, $prezzo, $quantita, $indirizzo_img, $id_prodotto, $p_iva_venditore);
        } else {
            // 保留原图，仅修改商品基础信息
            $stmt_salva = $conn->prepare("UPDATE prodotto SET nome = ?, prezzo = ?, quantita_disponibile = ? WHERE id_prodotto = ? AND p_iva = ?");
            $stmt_salva->bind_param("sdisi", $nome, $prezzo, $quantita, $id_prodotto, $p_iva_venditore);
        }

        if ($stmt_salva->execute()) {
            $messaggio = "Prodotto modificato con successo!";
            // 修改成功后删除废弃旧图片
            if ($indirizzo_img !== null && !empty($vecchia_immagine) && file_exists($vecchia_immagine)) {
                unlink($vecchia_immagine);
            }
            // 退出编辑模式，回到新增商品页面状态
            $prodotto_da_modificare = null;
        } else {
            // 更新失败，删除刚上传的新图片，清理冗余文件
            $errore = "Errore nella modifica: " . $conn->error;
            if ($indirizzo_img !== null && file_exists($indirizzo_img)) {
                unlink($indirizzo_img);
            }
        }
        $stmt_salva->close();
    }
}

// ===================== 查询当前卖家全部商品 =====================
// 按商品ID倒序排列，最新上架商品优先展示
$stmt_prodotti = $conn->prepare("SELECT * FROM prodotto WHERE p_iva = ? ORDER BY id_prodotto DESC");
$stmt_prodotti->bind_param("s", $p_iva_venditore);
$stmt_prodotti->execute();
$prodotti = $stmt_prodotti->get_result();
$stmt_prodotti->close();
?>

<!-- 页面HTML结构 -->
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <!-- 移动端设备自适应布局 -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestisci Prodotti - Area Venditori</title>
    <!-- 引入字体图标样式库 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- 引入项目全局样式文件 -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- 后台顶部导航栏 -->
    <header>
        <div class="container">
            <h1><i class="fas fa-store"></i> Area Venditori</h1>
            <nav>
                <!-- 展示当前登录卖家店铺名称 -->
                <span><i class="fas fa-user"></i> Ciao, <?= $_SESSION['venditore_ragione_sociale'] ?></span>
                <!-- 后台功能导航菜单 -->
                <a href="dashboard_venditore.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <!-- 当前商品管理页面高亮标记 -->
                <a href="gestisci_prodotti.php" style="border-bottom: 2px solid #fef3c7;"><i class="fas fa-box"></i> Prodotti</a>
                <a href="ordini_venditore.php"><i class="fas fa-file-invoice"></i> Ordini</a>
                <a href="profilo_venditore.php"><i class="fas fa-user-cog"></i> Profilo</a>
                <!-- 退出登录按钮 -->
                <a href="logout.php" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </div>
    </header>

    <main class="container">
        <!-- 根据编辑/新增状态动态切换页面标题与图标 -->
        <h2><i class="fas fa-<?= $prodotto_da_modificare ? 'edit' : 'plus-circle' ?>"></i> <?= $prodotto_da_modificare ? 'Modifica Prodotto' : 'Aggiungi Nuovo Prodotto' ?></h2>
        
        <!-- 操作成功提示弹窗 -->
        <?php if ($messaggio): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= $messaggio ?>
            </div>
        <?php endif; ?>
        <!-- 操作错误提示弹窗 -->
        <?php if ($errore): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?= $errore ?>
            </div>
        <?php endif; ?>

        <!-- 商品新增/编辑表单，multipart格式支持文件上传 -->
        <form method="POST" enctype="multipart/form-data" class="form-card">
            <!-- 编辑模式隐藏域，存储商品ID -->
            <?php if ($prodotto_da_modificare): ?>
                <input type="hidden" name="id_prodotto" value="<?= $prodotto_da_modificare['id_prodotto'] ?>">
            <?php endif; ?>

            <!-- 商品名称输入项 -->
            <div class="form-group">
                <label for="nome"><i class="fas fa-tag"></i> Nome Prodotto</label>
                <input type="text" id="nome" name="nome" required value="<?= $prodotto_da_modificare['nome'] ?? '' ?>" placeholder="Inserisci il nome del prodotto">
            </div>
            
            <!-- 商品价格输入项，支持两位小数 -->
            <div class="form-group">
                <label for="prezzo"><i class="fas fa-euro-sign"></i> Prezzo (€)</label>
                <input type="number" step="0.01" id="prezzo" name="prezzo" required value="<?= $prodotto_da_modificare['prezzo'] ?? '' ?>" placeholder="Inserisci il prezzo">
            </div>
            
            <!-- 商品库存数量输入项 -->
            <div class="form-group">
                <label for="qty"><i class="fas fa-boxes"></i> Quantità Disponibile</label>
                <input type="number" id="qty" name="quantita_disponibile" required value="<?= $prodotto_da_modificare['quantita_disponibile'] ?? '' ?>" placeholder="Inserisci la quantità disponibile">
            </div>

            <!-- 商品图片上传区域 -->
            <div class="form-group">
                <label for="img"><i class="fas fa-image"></i> Immagine Prodotto</label>
                <input type="file" id="img" name="indirizzo_img" accept="image/jpg, image/jpeg, image/png, image/gif">
                <small>Formati consentiti: JPG, JPEG, PNG, GIF | Dimensione massima: 2MB</small>
                
                <!-- 编辑模式展示原有商品预览图 -->
                <?php if ($prodotto_da_modificare && !empty($prodotto_da_modificare['indirizzo_img'])): ?>
                    <br>
                    <small>Immagine attuale:</small>
                    <br>
                    <img src="<?= $prodotto_da_modificare['indirizzo_img'] ?>" class="product-img-preview" alt="Immagine prodotto">
                    <br>
                    <small class="text-muted">Lascia vuoto per mantenere l'immagine attuale</small>
                <?php endif; ?>
            </div>

            <!-- 表单操作按钮，区分新增、编辑两种场景 -->
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

        <!-- 个人商品列表展示区域 -->
        <h2><i class="fas fa-boxes"></i> I Tuoi Prodotti</h2>
        <!-- 判断是否存在上架商品 -->
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
                    <!-- 循环遍历渲染所有商品数据 -->
                    <?php while ($prodotto = $prodotti->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <!-- 有图展示缩略图，无图显示默认图标 -->
                                <?php if (!empty($prodotto['indirizzo_img'])): ?>
                                    <img src="<?= $prodotto['indirizzo_img'] ?>" class="product-img-table" alt="<?= $prodotto['nome'] ?>">
                                <?php else: ?>
                                    <span class="text-muted"><i class="fas fa-image"></i> Nessuna</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $prodotto['id_prodotto'] ?></td>
                            <td><?= $prodotto['nome'] ?></td>
                            <!-- 格式化欧元金额展示样式 -->
                            <td class="price">€ <?= number_format($prodotto['prezzo'], 2, ',', '.') ?></td>
                            <td class="stock"><?= $prodotto['quantita_disponibile'] ?> pezzi</td>
                            <td>
                                <div class="action-buttons">
                                    <!-- 跳转商品编辑页面 -->
                                    <a href="gestisci_prodotti.php?modifica=<?= $prodotto['id_prodotto'] ?>" class="btn">
                                        <i class="fas fa-edit"></i> Modifica
                                    </a>
                                    <!-- 删除商品表单，附带弹窗二次确认 -->
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
            <!-- 暂无商品空白状态提示 -->
            <div class="empty-box">
                <i class="fas fa-box-open"></i>
                <p>Non hai ancora aggiunto prodotti al catalogo.</p>
                <a href="#aggiungi" class="btn">
                    <i class="fas fa-plus"></i> Aggiungi il tuo primo prodotto
                </a>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>