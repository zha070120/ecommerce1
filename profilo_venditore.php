<?php
// 引入数据库连接与公共权限配置文件
include 'config.php';
// 校验卖家登录状态，未登录则强制跳转登录页
richiedi_login_venditore();

// 从会话中获取当前登录卖家税号，空值兜底防止报错
$p_iva_venditore = $_SESSION['venditore_piva'] ?? '';
// 定义操作成功、错误提示信息变量
$messaggio = '';
$errore = '';


function caricaProfilo($conn, $p_iva)
{
    // 预处理查询语句，防止SQL注入
    $stmt = $conn->prepare("SELECT * FROM venditore WHERE p_iva = ?");
    // 绑定字符串类型参数
    $stmt->bind_param("s", $p_iva);
    // 执行查询
    $stmt->execute();
    // 获取单条用户资料数据
    $profilo = $stmt->get_result()->fetch_assoc();
    // 关闭语句释放资源
    $stmt->close();
    return $profilo;
}

// 调用函数加载当前卖家个人资料
$profilo = caricaProfilo($conn, $p_iva_venditore);

// ===================== 保存个人资料修改逻辑 =====================
if (isset($_POST['salva_profilo'])) {
    // 获取表单数据，去除首尾空白字符，规范数据格式
    $ragione_sociale = trim($_POST['ragione_sociale']);
    $indirizzo = trim($_POST['indirizzo']);
    $cap = trim($_POST['cap']);

    // 预处理更新语句，修改卖家企业信息
    $stmt_aggiorna = $conn->prepare("
        UPDATE venditore 
        SET ragione_sociale = ?, indirizzo = ?, cap = ?
        WHERE p_iva = ?
    ");
    // 绑定四个字符串参数
    $stmt_aggiorna->bind_param("ssss", $ragione_sociale, $indirizzo, $cap, $p_iva_venditore);

    // 判断数据库更新执行结果
    if ($stmt_aggiorna->execute()) {
        // 同步更新会话中的店铺名称，页面实时生效
        $_SESSION['venditore_ragione_sociale'] = $ragione_sociale;
        $messaggio = "Profilo aggiornato con successo!";
        // 重新加载最新资料数据
        $profilo = caricaProfilo($conn, $p_iva_venditore);
    } else {
        // 捕获数据库执行异常信息
        $errore = "Errore nell'aggiornamento: " . $conn->error;
    }
    // 关闭数据库语句
    $stmt_aggiorna->close();
}

// ===================== 账号密码修改逻辑 =====================
if (isset($_POST['cambia_password'])) {
    // 获取表单提交的密码数据并去除空格
    $password_attuale = trim($_POST['password_attuale']);
    $nuova_password = trim($_POST['nuova_password']);
    $conferma_password = trim($_POST['conferma_password']);

    // 校验输入的原密码与数据库加密密码是否匹配
    if (password_verify($password_attuale, $profilo['password'])) {
        // 校验两次输入的新密码是否一致
        if ($nuova_password === $conferma_password) {
            // 基础密码强度校验：密码长度至少6位
            if (strlen($nuova_password) >= 6) {
                // 对新密码进行哈希加密，明文不存入数据库
                $nuova_hash = password_hash($nuova_password, PASSWORD_DEFAULT);
                // 预处理语句更新数据库密码字段
                $stmt_password = $conn->prepare("UPDATE venditore SET password = ? WHERE p_iva = ?");
                $stmt_password->bind_param("ss", $nuova_hash, $p_iva_venditore);

                if ($stmt_password->execute()) {
                    $messaggio = "Password modificata con successo!";
                } else {
                    $errore = "Errore nella modifica della password.";
                }
                $stmt_password->close();
            } else {
                $errore = "La password deve essere di almeno 6 caratteri!";
            }
        } else {
            $errore = "Le nuove password non coincidono!";
        }
    } else {
        $errore = "La password attuale è errata!";
    }
}
?>

<!-- 卖家个人资料页面 HTML 结构 -->
<!DOCTYPE html>
<html lang="it">

<head>
    <!-- 网页编码格式 -->
    <meta charset="UTF-8">
    <!-- 移动端自适应布局适配 -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- 浏览器页面标题 -->
    <title>Profilo Venditore - Area Venditori</title>
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
                <span><i class="fas fa-user"></i> Ciao, <?= $_SESSION['venditore_ragione_sociale'] ?? 'Utente' ?></span>
                <!-- 后台功能导航菜单 -->
                <a href="dashboard_venditore.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="gestisci_prodotti.php"><i class="fas fa-box"></i> Prodotti</a>
                <a href="ordini_venditore.php"><i class="fas fa-file-invoice"></i> Ordini</a>
                <!-- 当前个人资料页面高亮标识 -->
                <a href="profilo_venditore.php" style="border-bottom: 2px solid #fef3c7;"><i class="fas fa-user-cog"></i> Profilo</a>
                <!-- 退出登录按钮 -->
                <a href="logout.php" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </div>
    </header>

    <!-- 页面主体内容容器 -->
    <main class="container">
        <h2><i class="fas fa-user-cog"></i> Il Tuo Profilo</h2>

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

        <!-- 企业基础资料修改表单卡片 -->
        <div class="form-card">
            <h3><i class="fas fa-building"></i> Dati Aziendali</h3>
            <!-- POST方式提交资料修改数据 -->
            <form method="POST">
                <!-- 税号字段，固定不可编辑 -->
                <div class="form-group">
                    <label for="p_iva"><i class="fas fa-id-card"></i> Partita IVA (non modificabile)</label>
                    <input type="text" id="p_iva" value="<?= $profilo['p_iva'] ?? '' ?>" disabled>
                </div>

                <!-- 公司名称输入框 -->
                <div class="form-group">
                    <label for="ragione_sociale"><i class="fas fa-signature"></i> Ragione Sociale</label>
                    <input type="text" id="ragione_sociale" name="ragione_sociale" required value="<?= $profilo['ragione_sociale'] ?? '' ?>" placeholder="Inserisci la tua ragione sociale">
                </div>

                <!-- 营业地址输入框 -->
                <div class="form-group">
                    <label for="indirizzo"><i class="fas fa-map-marker-alt"></i> Indirizzo Sede</label>
                    <input type="text" id="indirizzo" name="indirizzo" required value="<?= $profilo['indirizzo'] ?? '' ?>" placeholder="Inserisci l'indirizzo della sede">
                </div>

                <!-- 邮政编码输入框 -->
                <div class="form-group">
                    <label for="cap"><i class="fas fa-mail-bulk"></i> CAP</label>
                    <input type="text" id="cap" name="cap" required value="<?= $profilo['cap'] ?? '' ?>" placeholder="Inserisci il CAP">
                </div>

                <!-- 保存资料提交按钮 -->
                <button type="submit" name="salva_profilo" class="btn btn-success submit-btn">
                    <i class="fas fa-save"></i> Salva Modifiche
                </button>
            </form>
        </div>

        <!-- 密码修改表单卡片 -->
        <div class="form-card">
            <h3><i class="fas fa-key"></i> Cambia Password</h3>
            <form method="POST">
                <!-- 原密码输入框 -->
                <div class="form-group">
                    <label for="password_attuale"><i class="fas fa-lock"></i> Password Attuale</label>
                    <input type="password" id="password_attuale" name="password_attuale" required placeholder="Inserisci la password attuale">
                </div>

                <!-- 新密码输入框 -->
                <div class="form-group">
                    <label for="nuova_password"><i class="fas fa-key"></i> Nuova Password</label>
                    <input type="password" id="nuova_password" name="nuova_password" required placeholder="Inserisci la nuova password">
                </div>

                <!-- 确认新密码输入框 -->
                <div class="form-group">
                    <label for="conferma_password"><i class="fas fa-check"></i> Conferma Nuova Password</label>
                    <input type="password" id="conferma_password" name="conferma_password" required placeholder="Conferma la nuova password">
                </div>

                <!-- 密码修改提交按钮 -->
                <button type="submit" name="cambia_password" class="btn btn-success submit-btn">
                    <i class="fas fa-key"></i> Cambia Password
                </button>
            </form>
        </div>
    </main>
</body>

</html>