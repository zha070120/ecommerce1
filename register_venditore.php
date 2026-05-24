<?php
// 引入数据库连接、基础公共配置文件
include 'config.php';

// 初始化错误提示、注册成功提示变量
$errore = '';
$success = '';

// 监听POST表单提交请求，处理卖家注册业务逻辑
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 获取表单提交数据，去除首尾多余空白字符，规整数据格式
    $p_iva          = trim($_POST['p_iva']);                // 企业税号
    $ragione_sociale= trim($_POST['ragione_sociale']);      // 企业名称
    $indirizzo      = trim($_POST['indirizzo']);            // 营业地址
    $cap            = trim($_POST['cap']);                  // 邮政编码
    $password       = trim($_POST['password']);             // 登录密码

    // 基础必填项校验，税号、企业名、密码不能为空
    if (empty($p_iva) || empty($ragione_sociale) || empty($password)) {
        $errore = "Compila tutti i campi obbligatori!";
    } else {
        // 预处理语句查询税号，检测账号是否已注册，防止重复注册
        $check_stmt = $conn->prepare("SELECT p_iva FROM venditore WHERE p_iva = ?");
        $check_stmt->bind_param("s", $p_iva);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        // 税号已存在，返回注册失败提示
        if ($check_result->num_rows > 0) {
            $errore = "Partita IVA già registrata!";
        } else {
            // 对明文密码进行哈希加密，数据库仅存储加密字符串，保障账号安全
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            // 预处理语句插入卖家账号数据，规避SQL注入风险
            $insert_stmt = $conn->prepare("INSERT INTO venditore (p_iva, ragione_sociale, indirizzo, cap, password) VALUES (?, ?, ?, ?, ?)");
            // 绑定字符串类型参数
            $insert_stmt->bind_param("sssss", $p_iva, $ragione_sociale, $indirizzo, $cap, $password_hash);
            
            // 执行数据插入并判断执行结果
            if ($insert_stmt->execute()) {
                $success = "Registrazione completata! Ora puoi accedere.";
            } else {
                // 捕获数据库执行异常信息
                $errore = "Errore: " . $insert_stmt->error;
            }
            // 关闭新增语句，释放数据库资源
            $insert_stmt->close();
        }
        // 关闭查重语句，释放数据库资源
        $check_stmt->close();
    }
}
?>

<!-- 卖家注册页面整体结构 -->
<!DOCTYPE html>
<html lang="it">
<head>
    <!-- 统一网页文字编码格式 -->
    <meta charset="UTF-8">
    <!-- 适配移动端各类屏幕，实现自适应布局 -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- 浏览器标签页标题 -->
    <title>Registrazione Venditore - E-commerce Doubao</title>
    <!-- 引入字体图标库，提供页面图标样式 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- 引入项目全局自定义样式表 -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- 网站顶部导航栏 -->
    <header>
        <div class="container">
            <!-- 电商平台品牌名称 -->
            <h1><i class="fas fa-shopping-bag"></i> E-commerce Doubao</h1>
            <nav>
                <!-- 首页跳转链接 -->
                <a href="index.php"><i class="fas fa-home"></i> Home</a>
                <!-- 卖家登录页面跳转链接 -->
                <a href="login_venditore.php"><i class="fas fa-sign-in-alt"></i> Accedi</a>
            </nav>
        </div>
    </header>

    <!-- 页面主体注册区域 -->
    <main class="container">
        <div class="register-wrap">
            <!-- 页面标题 -->
            <h2><i class="fas fa-store"></i> Registrazione Venditore</h2>

            <!-- 注册失败错误提示弹窗 -->
            <?php if($errore): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?= $errore ?>
                </div>
            <?php endif; ?>

            <!-- 注册成功提示弹窗 -->
            <?php if($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?= $success ?>
                </div>
            <?php endif; ?>

            <!-- 注册表单，POST方式提交数据至当前页面处理 -->
            <form method="POST">
                <!-- 企业税号输入框，必填项，限制最大字符长度 -->
                <div class="form-group">
                    <label for="p_iva">Partita IVA <span class="required">*</span></label>
                    <input type="text" id="p_iva" name="p_iva" maxlength="13" required placeholder="Inserisci la tua Partita IVA">
                </div>

                <!-- 企业名称输入框，必填项 -->
                <div class="form-group">
                    <label for="ragione_sociale">Ragione Sociale <span class="required">*</span></label>
                    <input type="text" id="ragione_sociale" name="ragione_sociale" required placeholder="Inserisci la tua Ragione Sociale">
                </div>

                <!-- 营业地址输入框，选填项 -->
                <div class="form-group">
                    <label for="indirizzo">Indirizzo</label>
                    <input type="text" id="indirizzo" name="indirizzo" placeholder="Inserisci il tuo indirizzo">
                </div>

                <!-- 邮政编码输入框，选填项 -->
                <div class="form-group">
                    <label for="cap">CAP</label>
                    <input type="text" id="cap" name="cap" placeholder="Inserisci il tuo CAP">
                </div>

                <!-- 登录密码输入框，必填项 -->
                <div class="form-group">
                    <label for="password">Password <span class="required">*</span></label>
                    <input type="password" id="password" name="password" required placeholder="Inserisci la tua password">
                </div>

                <!-- 注册提交按钮 -->
                <button type="submit" class="btn btn-success submit-btn">
                    <i class="fas fa-user-plus"></i> Registrati
                </button>
            </form>
        </div>
    </main>
</body>
</html>