<?php
// 引入数据库连接与公共权限配置文件
include 'config.php';

// 初始化错误提示文本变量
$errore = "";

// 判断是否为表单POST提交请求
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 获取表单提交的税号与密码，去除首尾空白字符
    $p_iva = trim($_POST['p_iva']);
    $password = trim($_POST['password']);

    // 使用预处理语句查询卖家信息，有效防止SQL注入攻击
    $sql = "SELECT * FROM venditore WHERE p_iva = ?";
    $stmt = $conn->prepare($sql);
    // 绑定字符串类型参数
    $stmt->bind_param("s", $p_iva);
    // 执行查询操作
    $stmt->execute();
    // 获取查询结果集
    $res = $stmt->get_result();

    // 判断是否查询到对应卖家账号
    if ($res->num_rows > 0) {
        // 读取卖家账号数据
        $vend = $res->fetch_assoc();
        // 校验输入密码与数据库加密密码是否匹配
        if (password_verify($password, $vend['password'])) {
            // 登录成功，将卖家身份信息存入会话
            $_SESSION['venditore_piva'] = $vend['p_iva'];
            $_SESSION['venditore_ragione_sociale'] = $vend['ragione_sociale'];
            // 跳转至卖家后台仪表盘页面
            header("Location: dashboard_venditore.php");
            exit;
        }
    }
    // 关闭预处理语句，释放数据库资源
    $stmt->close();
    // 账号或密码错误，统一返回错误提示
    $errore = "Credenziali errate!";
}
?>

<!-- 卖家登录页面结构 -->
<!DOCTYPE html>
<html lang="it">

<head>
    <!-- 网页编码格式 -->
    <meta charset="UTF-8">
    <!-- 移动端自适应布局适配 -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- 浏览器页面标题 -->
    <title>Login Venditore - E-commerce Doubao</title>
    <!-- 引入字体图标样式库 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- 引入项目全局样式文件 -->
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <!-- 网站顶部导航栏 -->
    <header>
        <div class="container">
            <h1><i class="fas fa-shopping-bag"></i> E-commerce Doubao</h1>
            <nav>
                <!-- 首页跳转链接 -->
                <a href="index.php"><i class="fas fa-home"></i> Home</a>
                <!-- 卖家注册页面跳转链接 -->
                <a href="register_venditore.php"><i class="fas fa-user-plus"></i> Registrati</a>
            </nav>
        </div>
    </header>

    <!-- 页面主体登录区域 -->
    <main class="container">
        <div class="login-wrap">
            <h2><i class="fas fa-store"></i> Accesso Venditore</h2>

            <!-- 登录失败错误提示弹窗 -->
            <?php if ($errore): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?= $errore ?>
                </div>
            <?php endif; ?>

            <!-- 卖家登录表单，POST方式提交数据 -->
            <form method="post">
                <!-- 税号输入框 -->
                <div class="form-group">
                    <label for="p_iva">Partita IVA</label>
                    <input type="text" name="p_iva" id="p_iva" required placeholder="Inserisci la tua Partita IVA">
                </div>
                <!-- 密码输入框 -->
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" required placeholder="Inserisci la tua password">
                </div>
                <!-- 登录提交按钮 -->
                <button class="btn btn-success submit-btn" type="submit">
                    <i class="fas fa-sign-in-alt"></i> Accedi
                </button>
            </form>
        </div>
    </main>
</body>

</html>