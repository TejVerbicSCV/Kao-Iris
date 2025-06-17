<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <title>Portal IRIS - Registracija</title>
    <link rel="stylesheet" href="style.css">  
</head>
<body>
    <div class="main-content">
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
                
                $errors = [];
                
                if (empty($ime)) $errors[] = "Ime je obvezno.";
                if (empty($priimek)) $errors[] = "Priimek je obvezen.";
                if (empty($email)) $errors[] = "E-poštni naslov je obvezen.";
                if (empty($password)) $errors[] = "Geslo je obvezno.";
                if ($password !== $password_confirm) $errors[] = "Gesli se ne ujemata.";
            
                $sql = "SELECT id FROM uporabniki WHERE email = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "s", $email);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                if (mysqli_num_rows($result) > 0) {
                    $errors[] = "Uporabnik s tem e-poštnim naslovom že obstaja.";
                }
                
                if (empty($errors)) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    $vloga_id = 3; 
                    $sql = "INSERT INTO uporabniki (ime, priimek, email, telefon, naslov, geslo, vloga_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "ssssssi", $ime, $priimek, $email, $telefon, $naslov, $hashed_password, $vloga_id);
                    
                    if (mysqli_stmt_execute($stmt)) {
                        $_SESSION['user_id'] = mysqli_insert_id($conn);
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
                
                <div class="info-message">
                    <p>Po registraciji boste dodeljeni kot pacient. Če želite postati zdravnik, se obrnite na administratorja sistema.</p>
                </div>
                
                <button type="submit" class="btn">Registracija</button>
            </form>
            <div class="login-link">
                <p>Že imate račun? <a href="prijava.php">Prijavite se</a></p>
            </div>
        </div>
    </div>
    <?php include 'footer.php'; ?>
</body>
</html> 