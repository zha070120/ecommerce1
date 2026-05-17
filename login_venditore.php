<?php 
include 'config.php';
$errore = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $p_iva = trim($_POST['p_iva']);
    $password = trim($_POST['password']);

    // 预处理语句查询 防SQL注入
    $sql = "SELECT * FROM venditore WHERE p_iva = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $p_iva);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        $vend = $res->fetch_assoc();
        // 验证密码
        if (password_verify($password, $vend['password'])) {
            $_SESSION['venditore_piva'] = $vend['p_iva'];
            $_SESSION['venditore_ragione_sociale'] = $vend['ragione_sociale'];
            header("Location: dashboard_venditore.php");
            exit;
        }
    }
    // 关闭预处理语句 释放资源
    $stmt->close();
    // 统一错误提示
    $errore = "Credenziali errate!";
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Venditore - E-commerce Doubao</title>
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

        .btn-success {
            background-color: var(--success);
        }

        .btn-success:hover {
            background-color: #059669;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
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

        /* 错误提示框统一 */
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

        /* 登录表单居中卡片 */
        .login-wrap {
            max-width: 520px;
            margin: 4rem auto;
            background: #fff;
            padding: 2.5rem;
            border-radius: 16px;
            box-shadow: var(--shadow-md);
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

        .submit-btn {
            width: 100%;
            margin-top: 0.5rem;
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
            .login-wrap {
                margin: 2rem auto;
                padding: 1.8rem;
            }
        }

        @media (max-width: 480px) {
            .btn {
                padding: 0.6rem 1.2rem;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1><i class="fas fa-shopping-bag"></i> E-commerce Doubao</h1>
            <nav>
                <a href="index.php"><i class="fas fa-home"></i> Home</a>
                <a href="register_venditore.php"><i class="fas fa-user-plus"></i> Registrati</a>
            </nav>
        </div>
    </header>

    <div class="container">
        <div class="login-wrap">
            <h2><i class="fas fa-store"></i> Accesso Venditore</h2>

            <?php if ($errore): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?= $errore ?>
                </div>
            <?php endif; ?>

            <form method="post">
                <div class="form-group">
                    <label for="p_iva">Partita IVA</label>
                    <input type="text" name="p_iva" id="p_iva" required placeholder="Inserisci la tua Partita IVA">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" required placeholder="Inserisci la tua password">
                </div>
                <button class="btn btn-success submit-btn" type="submit">
                    <i class="fas fa-sign-in-alt"></i> Accedi
                </button>
            </form>
        </div>
    </div>
</body>
</html>