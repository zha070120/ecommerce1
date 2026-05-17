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