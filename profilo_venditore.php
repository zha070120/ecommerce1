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
    <title>Profilo Venditore - Area Venditori</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
        <h1>Area Venditori</h1>
        <nav>
            <span>Ciao, <?= $_SESSION['venditore_ragione_sociale'] ?? 'Utente' ?></span>
            <a href="dashboard_venditore.php">Dashboard</a>
            <a href="gestisci_prodotti.php">Prodotti</a>
            <a href="ordini_venditore.php">Ordini</a>
            <a href="profilo_venditore.php">Profilo</a>
            <a href="logout.php" class="btn btn-danger">Logout</a>
        </nav>
    </header>

    <div class="container">
        <h2>Il Tuo Profilo</h2>
        <?php if ($messaggio): ?><div class="alert alert-success"><?= $messaggio ?></div><?php endif; ?>
        <?php if ($errore): ?><div class="alert alert-danger"><?= $errore ?></div><?php endif; ?>

        <!-- 表单修改资料 -->
        <div style="background: white; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem;">
            <h3>Dati Aziendali</h3>
            <form method="POST">
                <div class="form-group">
                    <label for="p_iva">Partita IVA (non modificabile)</label>
                    <input type="text" id="p_iva" value="<?= $profilo['p_iva'] ?? '' ?>" disabled>
                </div>
                <div class="form-group">
                    <label for="ragione_sociale">Ragione Sociale</label>
                    <input type="text" id="ragione_sociale" name="ragione_sociale" required value="<?= $profilo['ragione_sociale'] ?? '' ?>">
                </div>
                <div class="form-group">
                    <label for="indirizzo">Indirizzo Sede</label>
                    <input type="text" id="indirizzo" name="indirizzo" required value="<?= $profilo['indirizzo'] ?? '' ?>">
                </div>
                <div class="form-group">
                    <label for="cap">CAP</label>
                    <input type="text" id="cap" name="cap" required value="<?= $profilo['cap'] ?? '' ?>">
                </div>
                <button type="submit" name="salva_profilo" class="btn btn-success">Salva Modifiche</button>
            </form>
        </div>

        <!-- 表单修改密码 -->
        <div style="background: white; padding: 1.5rem; border-radius: 8px;">
            <h3>Cambia Password</h3>
            <form method="POST">
                <div class="form-group">
                    <label for="password_attuale">Password Attuale</label>
                    <input type="password" id="password_attuale" name="password_attuale" required>
                </div>
                <div class="form-group">
                    <label for="nuova_password">Nuova Password</label>
                    <input type="password" id="nuova_password" name="nuova_password" required>
                </div>
                <div class="form-group">
                    <label for="conferma_password">Conferma Nuova Password</label>
                    <input type="password" id="conferma_password" name="conferma_password" required>
                </div>
                <button type="submit" name="cambia_password" class="btn btn-success">Cambia Password</button>
            </form>
        </div>
    </div>
</body>
</html>