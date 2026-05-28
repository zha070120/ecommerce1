<?php
// 引入数据库连接、会话、权限公共配置文件
include 'config.php';
// 初始化登录错误信息变量
$error = '';

// 监听表单POST提交请求，处理登录逻辑
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 获取表单提交的邮箱和密码数据
    $email = $_POST['email'];
    $password = $_POST['password'];

    // 使用预处理语句查询客户账号，有效防止SQL注入攻击
    $stmt = $conn->prepare("SELECT * FROM cliente WHERE email = ?");
    // 绑定字符串类型参数
    $stmt->bind_param("s", $email);
    // 执行查询操作
    $stmt->execute();
    // 获取查询结果集
    $result = $stmt->get_result();

    // 判断是否查询到唯一匹配的客户账号
    if ($result->num_rows == 1) {
        // 读取账号完整数据
        $user = $result->fetch_assoc();
        // 校验输入密码与数据库加密密码是否一致
        if (password_verify($password, $user['password'])) {
            // 登录成功，将用户核心信息存入会话，标记登录状态
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['nome'];

            // 根据用户邮箱查询对应的购物车记录
            $stmt_cart = $conn->prepare("SELECT id_carello FROM carello WHERE email = ?");
            $stmt_cart->bind_param("s", $user['email']);
            $stmt_cart->execute();
            $cart_check = $stmt_cart->get_result();

            // 该用户暂无购物车，自动创建一条空购物车记录
            if ($cart_check->num_rows == 0) {
                $stmt_insert = $conn->prepare("INSERT INTO carello (email) VALUES (?)");
                $stmt_insert->bind_param("s", $user['email']);
                $stmt_insert->execute();
                $stmt_insert->close();
            }
            // 重新执行查询，获取最新购物车ID
            $stmt_cart->execute();
            $cart = $stmt_cart->get_result()->fetch_assoc();
            // 将购物车ID存入会话，后续购物操作关联当前用户购物车
            $_SESSION['cart_id'] = $cart['id_carello'];
            $stmt_cart->close();

            // 登录完成跳转网站首页
            header("Location: index.php");
            exit;
        } else {
            // 密码不匹配，赋值错误提示
            $error = "Password errata!";
        }
    } else {
        // 未查询到对应邮箱账号，赋值错误提示
        $error = "Email non trovata!";
    }
    // 关闭数据库查询语句，释放服务器资源
    $stmt->close();
}
?>

<!-- 客户登录页面整体结构 -->
<!DOCTYPE html>
<html lang="it">

<head>
    <!-- 统一网页文字编码格式 -->
    <meta charset="UTF-8">
    <!-- 适配移动端各类屏幕尺寸，自适应布局 -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- 浏览器标签页标题 -->
    <title>Login - E-commerce Doubao</title>
    <!-- 引入字体图标库，提供页面图标样式 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- 引入项目全局自定义样式表 -->
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <!-- 网站顶部导航栏区域 -->
    <header>
        <div class="container">
            <!-- 电商网站品牌名称 -->
            <h1><i class="fas fa-shopping-bag"></i> E-commerce Doubao</h1>
            <nav>
                <!-- 首页跳转链接 -->
                <a href="index.php"><i class="fas fa-home"></i> Home</a>
                <!-- 客户注册页面跳转链接 -->
                <a href="register.php"><i class="fas fa-user-plus"></i> Registrati</a>
            </nav>
        </div>
    </header>

    <!-- 页面主体登录容器 -->
    <main class="container">
        <div class="login-wrap">
            <!-- 登录模块标题 -->
            <h2><i class="fas fa-user"></i> Login Cliente</h2>

            <!-- 登录失败时展示错误提示弹窗 -->
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?= $error ?>
                </div>
            <?php endif; ?>

            <!-- 客户登录表单，以POST方式提交数据 -->
            <form method="POST">
                <!-- 邮箱输入表单项 -->
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" name="email" id="email" required placeholder="Inserisci la tua email">
                </div>
                <!-- 密码输入表单项 -->
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" required placeholder="Inserisci la tua password">
                </div>
                <!-- 登录提交按钮 -->
                <button type="submit" class="btn submit-btn">
                    <i class="fas fa-sign-in-alt"></i> Accedi
                </button>
            </form>
        </div>
    </main>
</body>

</html>