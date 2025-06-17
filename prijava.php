<?php
require_once 'baza.php';


session_start();

if (isset($_SESSION['user_id'])) {
    switch ($_SESSION['user_role']) {
        case 'admin':
            header("Location: admin/index.php");
            break;
        case 'zdravnik':
            header("Location: zdravnik/index.php");
            break;
        default:
            header("Location: index.php");
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    $sql = "SELECT u.*, v.naziv as vloga_naziv FROM uporabniki u JOIN vloge v ON u.vloga_id = v.id WHERE u.email = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) === 1) {
        $user = mysqli_fetch_assoc($result);
        
        if (password_verify($password, $user['geslo'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['ime'] . ' ' . $user['priimek'];
            $_SESSION['user_role'] = $user['vloga_naziv'];
            
            switch ($user['vloga_naziv']) {
                case 'admin':
                    header("Location: admin/index.php");
                    break;
                case 'zdravnik':
                    header("Location: zdravnik/index.php");
                    break;
                default:
                    header("Location: index.php");
            }
            exit();
        } else {
            $error = "Napačno geslo.";
        }
    } else {
        $error = "Uporabnik s tem e-poštnim naslovom ne obstaja.";
    }
}
?>
<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <title>Portal IRIS - Prijava</title>
    <link rel="stylesheet" href="style.css">
    
</head>
<body>
    <div class="main-content">
        <div class="login-container">
            <h2>Prijava v Portal IRIS</h2>
            
            <?php if (isset($error)): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">E-poštni naslov:</label>
                    <input type="email" id="email" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Geslo:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn">Prijava</button>
            </form>
            
            <div class="register-link">
                <p>Še nimate računa? <a href="registracija.php">Registrirajte se</a></p>
            </div>
        </div>
    </div>
    <?php include 'footer.php'; ?>
</body>
</html>

