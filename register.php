<?php
// 引入数据库连接、会话与权限公共配置文件
include 'config.php';

// 初始化提示信息变量
$errore = "";    // 存储注册失败错误信息
$successo = ""; // 存储注册成功提示信息

// 判断是否以POST方式提交注册表单
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 获取表单提交数据，去除首尾多余空格，规范数据格式
    $nome = trim($_POST['nome']);
    $cognome = trim($_POST['cognome']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // 基础必填项校验，核心信息不能为空
    if (empty($nome) || empty($cognome) || empty($email) || empty($password)) {
        $errore = "Compila tutti i campi obbligatori!";
    } else {
        // 预处理查询语句，校验邮箱是否已被注册，防止重复账号
        $controllo_email = $conn->prepare("SELECT email FROM cliente WHERE email = ?");
        $controllo_email->bind_param("s", $email);
        $controllo_email->execute();
        $risultato = $controllo_email->get_result();

        // 邮箱已存在，返回注册失败提示
        if ($risultato->num_rows > 0) {
            $errore = "Questa email è già registrata!";
        } else {
            // 对明文密码进行哈希加密，保障账号安全，数据库只存储密文
            $password_crittografata = password_hash($password, PASSWORD_DEFAULT);

            // 预处理插入语句，将新客户信息写入数据表，规避SQL注入风险
            $inserisci = $conn->prepare("INSERT INTO cliente (nome, cognome, email, password) VALUES (?, ?, ?, ?)");
            $inserisci->bind_param("ssss", $nome, $cognome, $email, $password_crittografata);

            // 执行数据插入并判断执行状态
            if ($inserisci->execute()) {
                $successo = "Registrazione avvenuta con successo! Puoi ora effettuare l'accesso.";
            } else {
                $errore = "Errore durante la registrazione: " . $conn->error;
            }
            // 关闭插入语句，释放数据库资源
            $inserisci->close();
        }
        // 关闭查重语句，释放数据库资源
        $controllo_email->close();
    }
}
?>

<!-- 客户注册页面HTML结构 -->
<!DOCTYPE html>
<html lang="it">

<head>
    <!-- 网页文字编码格式，避免乱码 -->
    <meta charset="UTF-8">
    <!-- 移动端自适应布局，适配手机、平板设备 -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- 浏览器标签页标题 -->
    <title>Registrazione Cliente - E-commerce Doubao</title>
    <!-- 引入字体图标库，实现页面图标样式 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- 引入项目全局样式表 -->
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
                <!-- 客户登录页面跳转链接 -->
                <a href="login.php"><i class="fas fa-sign-in-alt"></i> Accedi</a>
            </nav>
        </div>
    </header>

    <!-- 页面主体注册区域 -->
    <main class="container">
        <div class="register-wrap">
            <!-- 页面标题 -->
            <h2><i class="fas fa-user-plus"></i> Registrazione Nuovo Cliente</h2>

            <!-- 注册失败错误提示弹窗 -->
            <?php if ($errore): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?= $errore ?>
                </div>
            <?php endif; ?>

            <!-- 注册成功提示弹窗 -->
            <?php if ($successo): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?= $successo ?>
                </div>
            <?php endif; ?>

            <!-- 客户注册表单，POST方式提交数据 -->
            <form method="POST">
                <!-- 姓名输入框，必填项 -->
                <div class="form-group">
                    <label for="nome">Nome <span class="required">*</span></label>
                    <input type="text" id="nome" name="nome" required placeholder="Inserisci il tuo nome">
                </div>

                <!-- 姓氏输入框，必填项 -->
                <div class="form-group">
                    <label for="cognome">Cognome <span class="required">*</span></label>
                    <input type="text" id="cognome" name="cognome" required placeholder="Inserisci il tuo cognome">
                </div>

                <!-- 邮箱输入框，作为账号唯一标识 -->
                <div class="form-group">
                    <label for="email">Email <span class="required">*</span></label>
                    <input type="email" id="email" name="email" required placeholder="Inserisci la tua email">
                </div>

                <!-- 登录密码输入框 -->
                <div class="form-group">
                    <label for="password">Password <span class="required">*</span></label>
                    <input type="password" id="password" name="password" required placeholder="Crea la tua password">
                </div>

                <!-- 注册提交按钮 -->
                <button type="submit" class="btn btn-success submit-btn">
                    <i class="fas fa-user-check"></i> Completa Registrazione
                </button>
            </form>
        </div>
    </main>
</body>

</html>