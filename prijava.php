<?php
require_once 'baza.php';
//session_start();
//echo $_SESSION['message'];

session_start();

if (isset($_SESSION['user_id'])) {
    // Redirect based on role
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
    include 'baza.php';
    
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    // Get user with role
    $sql = "SELECT u.*, v.naziv as vloga_naziv FROM uporabniki u JOIN vloge v ON u.vloga_id = v.id WHERE u.email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['geslo'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['ime'] . ' ' . $user['priimek'];
            $_SESSION['user_role'] = $user['vloga_naziv'];
            
            // Redirect based on role
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
    <style>
        .login-container {
            max-width: 400px;
            margin: 4rem auto;
            padding: 2rem;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .login-container h2 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 2rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #34495e;
        }
        
        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .btn {
            display: block;
            width: 100%;
            padding: 0.75rem;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: #2980b9;
        }
        
        .error-message {
            color: #e74c3c;
            margin-bottom: 1rem;
            text-align: center;
        }
        
        .register-link {
            text-align: center;
            margin-top: 1rem;
        }
        
        .register-link a {
            color: #3498db;
            text-decoration: none;
        }
        
        .register-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
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
    <?php include 'footer.php'; ?>
</body>
</html>

