<?php 
include 'config.php'; 
richiedi_login_venditore();

$p_iva_venditore = $_SESSION['venditore_piva'] ?? '';
$messaggio = '';
$errore = '';

// 递归获取商家资料（封装逻辑，避免重复代码）
function caricaProfilo($conn, $p_iva) {
    $stmt = $conn->prepare("SELECT * FROM venditore WHERE p_iva = ?");
    $stmt->bind_param("s", $p_iva);
    $stmt->execute();
    $profilo = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $profilo;
}
$profilo = caricaProfilo($conn, $p_iva_venditore);

// 保存资料修改
if (isset($_POST['salva_profilo'])) {
    // 清理空白字符，数据更规范
    $ragione_sociale = trim($_POST['ragione_sociale']);
    $indirizzo = trim($_POST['indirizzo']);
    $cap = trim($_POST['cap']);

    $stmt_aggiorna = $conn->prepare("
        UPDATE venditore 
        SET ragione_sociale = ?, indirizzo = ?, cap = ?
        WHERE p_iva = ?
    ");
    $stmt_aggiorna->bind_param("ssss", $ragione_sociale, $indirizzo, $cap, $p_iva_venditore);
    
    if ($stmt_aggiorna->execute()) {
        $_SESSION['venditore_ragione_sociale'] = $ragione_sociale;
        $messaggio = "Profilo aggiornato con successo!";
        // 重新加载资料（调用函数，无重复代码）
        $profilo = caricaProfilo($conn, $p_iva_venditore);
    } else {
        $errore = "Errore nell'aggiornamento: " . $conn->error;
    }
    $stmt_aggiorna->close();
}

// 修改密码
if (isset($_POST['cambia_password'])) {
    $password_attuale = trim($_POST['password_attuale']);
    $nuova_password = trim($_POST['nuova_password']);
    $conferma_password = trim($_POST['conferma_password']);

    // 验证当前密码
    if (password_verify($password_attuale, $profilo['password'])) {
        if ($nuova_password === $conferma_password) {
            // 密码强度基础校验（可选，优化点）
            if(strlen($nuova_password) >= 6){
                $nuova_hash = password_hash($nuova_password, PASSWORD_DEFAULT);
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

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profilo Venditore - Area Venditori</title>
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

        h3 {
            font-size: 1.375rem;
            margin-bottom: 1.5rem;
            color: var(--gray-800);
            font-weight: 600;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--gray-200);
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
            margin-bottom: 2rem;
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

        .form-group input:disabled {
            background-color: var(--gray-100);
            cursor: not-allowed;
            color: var(--gray-600);
        }

        .submit-btn {
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
            .form-card {
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
            <h1><i class="fas fa-store"></i> Area Venditori</h1>
            <nav>
                <span><i class="fas fa-user"></i> Ciao, <?= $_SESSION['venditore_ragione_sociale'] ?? 'Utente' ?></span>
                <a href="dashboard_venditore.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="gestisci_prodotti.php"><i class="fas fa-box"></i> Prodotti</a>
                <a href="ordini_venditore.php"><i class="fas fa-file-invoice"></i> Ordini</a>
                <a href="profilo_venditore.php" style="border-bottom: 2px solid #fef3c7;"><i class="fas fa-user-cog"></i> Profilo</a>
                <a href="logout.php" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </div>
    </header>

    <div class="container">
        <h2><i class="fas fa-user-cog"></i> Il Tuo Profilo</h2>
        
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

        <!-- 表单修改资料 -->
        <div class="form-card">
            <h3><i class="fas fa-building"></i> Dati Aziendali</h3>
            <form method="POST">
                <div class="form-group">
                    <label for="p_iva"><i class="fas fa-id-card"></i> Partita IVA (non modificabile)</label>
                    <input type="text" id="p_iva" value="<?= $profilo['p_iva'] ?? '' ?>" disabled>
                </div>
                
                <div class="form-group">
                    <label for="ragione_sociale"><i class="fas fa-signature"></i> Ragione Sociale</label>
                    <input type="text" id="ragione_sociale" name="ragione_sociale" required value="<?= $profilo['ragione_sociale'] ?? '' ?>" placeholder="Inserisci la tua ragione sociale">
                </div>
                
                <div class="form-group">
                    <label for="indirizzo"><i class="fas fa-map-marker-alt"></i> Indirizzo Sede</label>
                    <input type="text" id="indirizzo" name="indirizzo" required value="<?= $profilo['indirizzo'] ?? '' ?>" placeholder="Inserisci l'indirizzo della sede">
                </div>
                
                <div class="form-group">
                    <label for="cap"><i class="fas fa-mail-bulk"></i> CAP</label>
                    <input type="text" id="cap" name="cap" required value="<?= $profilo['cap'] ?? '' ?>" placeholder="Inserisci il CAP">
                </div>
                
                <button type="submit" name="salva_profilo" class="btn btn-success submit-btn">
                    <i class="fas fa-save"></i> Salva Modifiche
                </button>
            </form>
        </div>

        <!-- 表单修改密码 -->
        <div class="form-card">
            <h3><i class="fas fa-key"></i> Cambia Password</h3>
            <form method="POST">
                <div class="form-group">
                    <label for="password_attuale"><i class="fas fa-lock"></i> Password Attuale</label>
                    <input type="password" id="password_attuale" name="password_attuale" required placeholder="Inserisci la password attuale">
                </div>
                
                <div class="form-group">
                    <label for="nuova_password"><i class="fas fa-key"></i> Nuova Password</label>
                    <input type="password" id="nuova_password" name="nuova_password" required placeholder="Inserisci la nuova password">
                </div>
                
                <div class="form-group">
                    <label for="conferma_password"><i class="fas fa-check"></i> Conferma Nuova Password</label>
                    <input type="password" id="conferma_password" name="conferma_password" required placeholder="Conferma la nuova password">
                </div>
                
                <button type="submit" name="cambia_password" class="btn btn-success submit-btn">
                    <i class="fas fa-key"></i> Cambia Password
                </button>
            </form>
        </div>
    </div>
</body>
</html>