<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <title>Portal IRIS - Registracija</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .register-container {
            max-width: 600px;
            margin: 4rem auto;
            padding: 2rem;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .register-container h2 {
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
        
        .login-link {
            text-align: center;
            margin-top: 1rem;
        }
        
        .login-link a {
            color: #3498db;
            text-decoration: none;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <h2>Registracija v Portal IRIS</h2>
        
        <?php
        session_start();
        
        if (isset($_SESSION['user_id'])) {
            header("Location: index.php");
            exit();
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            include 'baza.php';
            
            $ime = $_POST['ime'];
            $priimek = $_POST['priimek'];
            $email = $_POST['email'];
            $telefon = $_POST['telefon'];
            $naslov = $_POST['naslov'];
            $password = $_POST['password'];
            $password_confirm = $_POST['password_confirm'];
            
            // Validate input
            $errors = [];
            
            if (empty($ime)) $errors[] = "Ime je obvezno.";
            if (empty($priimek)) $errors[] = "Priimek je obvezen.";
            if (empty($email)) $errors[] = "E-poštni naslov je obvezen.";
            if (empty($password)) $errors[] = "Geslo je obvezno.";
            if ($password !== $password_confirm) $errors[] = "Gesli se ne ujemata.";
            
            // Check if email already exists
            $sql = "SELECT id FROM uporabniki WHERE email = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $errors[] = "Uporabnik s tem e-poštnim naslovom že obstaja.";
            }
            
            if (empty($errors)) {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new user with vloga_id set to 3 (pacient)
                $vloga_id = 3; // Default role for new registrations
                $sql = "INSERT INTO uporabniki (ime, priimek, email, telefon, naslov, geslo, vloga_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssssi", $ime, $priimek, $email, $telefon, $naslov, $hashed_password, $vloga_id);
                
                if ($stmt->execute()) {
                    $_SESSION['user_id'] = $stmt->insert_id;
                    $_SESSION['user_name'] = $ime . ' ' . $priimek;
                    $_SESSION['user_role'] = 'pacient';
                    header("Location: index.php");
                    exit();
                } else {
                    $errors[] = "Napaka pri registraciji. Poskusite znova.";
                }
            }
            
            if (!empty($errors)) {
                echo '<div class="error-message">' . implode('<br>', $errors) . '</div>';
            }
        }
        ?>
        
        <form method="POST" action="">
            <div class="form-row">
                <div class="form-group">
                    <label for="ime">Ime:</label>
                    <input type="text" id="ime" name="ime" required value="<?php echo isset($_POST['ime']) ? htmlspecialchars($_POST['ime']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="priimek">Priimek:</label>
                    <input type="text" id="priimek" name="priimek" required value="<?php echo isset($_POST['priimek']) ? htmlspecialchars($_POST['priimek']) : ''; ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="email">E-poštni naslov:</label>
                <input type="email" id="email" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="telefon">Telefon:</label>
                <input type="tel" id="telefon" name="telefon" value="<?php echo isset($_POST['telefon']) ? htmlspecialchars($_POST['telefon']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="naslov">Naslov:</label>
                <input type="text" id="naslov" name="naslov" value="<?php echo isset($_POST['naslov']) ? htmlspecialchars($_POST['naslov']) : ''; ?>">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="password">Geslo:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label for="password_confirm">Potrditev gesla:</label>
                    <input type="password" id="password_confirm" name="password_confirm" required>
                </div>
            </div>
            
            <div class="info-message" style="margin-bottom: 1rem; padding: 1rem; background-color: #f8f9fa; border-radius: 4px;">
                <p>Po registraciji boste dodeljeni kot pacient. Če želite postati zdravnik, se obrnite na administratorja sistema.</p>
            </div>
            
            <button type="submit" class="btn">Registracija</button>
        </form>
        
        <div class="login-link">
            <p>Že imate račun? <a href="prijava.php">Prijavite se</a></p>
        </div>
    </div>
    <?php include 'footer.php'; ?>
</body>
</html> 