<?php include 'config.php'; $error = ''; $success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $conn->real_escape_string($_POST['email']);
    $nome = $conn->real_escape_string($_POST['nome']);
    $cognome = $conn->real_escape_string($_POST['cognome']);
    $indirizzo = $conn->real_escape_string($_POST['indirizzo']);
    $cap = $conn->real_escape_string($_POST['cap']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $check = $conn->query("SELECT email FROM cliente WHERE email='$email'");
    if ($check->num_rows > 0) {
        $error = "Email già registrata!";
    } else {
        $sql = "INSERT INTO cliente (email, nome, cognome, indirizzo, cap, password) 
                VALUES ('$email', '$nome', '$cognome', '$indirizzo', '$cap', '$password')";
        if ($conn->query($sql)) {
            $success = "Registrazione completata! Puoi effettuare il login.";
        } else {
            $error = "Errore: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Registrazione - E-commerce</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
        <h1>E-commerce Doubao</h1>
        <nav>
            
            <a href="index.php">Home</a>
            <a href="login.php">Login</a>

        </nav>
    </header>
    <div class="container">
        <h2>Registrazione Cliente</h2>
        <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
        <form method="POST" style="max-width: 500px;">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>
            <div class="form-group">
                <label>Nome</label>
                <input type="text" name="nome" required>
            </div>
            <div class="form-group">
                <label>Cognome</label>
                <input type="text" name="cognome" required>
            </div>
            <div class="form-group">
                <label>Indirizzo</label>
                <input type="text" name="indirizzo" required>
            </div>
            <div class="form-group">
                <label>CAP</label>
                <input type="text" name="cap" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-success">Registrati</button>
        </form>
    </div>
</body>
</html>
