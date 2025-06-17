<?php
include '../baza.php';
checkUserAuth('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $ime = $_POST['ime'];
    $priimek = $_POST['priimek'];
    $email = $_POST['email'];
    $telefon = $_POST['telefon'];
    $naslov = $_POST['naslov'];
    $specializacija = $_POST['specializacija'];
    $geslo = password_hash('zdravnik123', PASSWORD_DEFAULT); // zdravnik123 - geslo
    
    $sql = "INSERT INTO uporabniki (ime, priimek, email, telefon, naslov, geslo, vloga_id, specializacija) 
            VALUES (?, ?, ?, ?, ?, ?, 2, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sssssss", $ime, $priimek, $email, $telefon, $naslov, $geslo, $specializacija);
    mysqli_stmt_execute($stmt);
    
    $doctor_id = mysqli_insert_id($conn);

    if (isset($_POST['patient_ids']) && is_array($_POST['patient_ids'])) {
        $sql = "UPDATE uporabniki SET zdravnik_id = ? WHERE id = ? AND vloga_id = 3";
        $stmt = mysqli_prepare($conn, $sql);
        foreach ($_POST['patient_ids'] as $patient_id) {
            mysqli_stmt_bind_param($stmt, "ii", $doctor_id, $patient_id);
            mysqli_stmt_execute($stmt);
        }
    }
    
    header("Location: zdravniki.php");
    exit();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $doctor_id = $_POST['doctor_id'];
    
    $sql = "DELETE FROM bolniske WHERE zdravnik_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $doctor_id);
    mysqli_stmt_execute($stmt);
  
    $sql = "DELETE FROM napotnice WHERE zdravnik_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $doctor_id);
    mysqli_stmt_execute($stmt);

    $sql = "DELETE FROM recepti WHERE zdravnik_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $doctor_id);
    mysqli_stmt_execute($stmt);
    
    $sql = "UPDATE uporabniki SET zdravnik_id = NULL WHERE zdravnik_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $doctor_id);
    mysqli_stmt_execute($stmt);
    
    $sql = "DELETE FROM uporabniki WHERE id = ? AND vloga_id = 2";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $doctor_id);
    mysqli_stmt_execute($stmt);
    
    header("Location: zdravniki.php");
    exit();
}

// update doktorja
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $doctor_id = $_POST['doctor_id'];
    $ime = $_POST['ime'];
    $priimek = $_POST['priimek'];
    $email = $_POST['email'];
    $telefon = $_POST['telefon'];
    $naslov = $_POST['naslov'];
    $specializacija = $_POST['specializacija'];
    
    $sql = "UPDATE uporabniki SET ime = ?, priimek = ?, email = ?, telefon = ?, naslov = ?, specializacija = ? 
            WHERE id = ? AND vloga_id = 2";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ssssssi", $ime, $priimek, $email, $telefon, $naslov, $specializacija, $doctor_id);
    mysqli_stmt_execute($stmt);
    

    if (isset($_POST['patient_ids']) && is_array($_POST['patient_ids'])) {
        $sql = "UPDATE uporabniki SET zdravnik_id = NULL WHERE zdravnik_id = ? AND vloga_id = 3";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $doctor_id);
        mysqli_stmt_execute($stmt);
        
        $sql = "UPDATE uporabniki SET zdravnik_id = ? WHERE id = ? AND vloga_id = 3";
        $stmt = mysqli_prepare($conn, $sql);
        foreach ($_POST['patient_ids'] as $patient_id) {
            mysqli_stmt_bind_param($stmt, "ii", $doctor_id, $patient_id);
            mysqli_stmt_execute($stmt);
        }
    }
    
    header("Location: zdravniki.php");
    exit();
}

$search_term = isset($_GET['search']) ? $_GET['search'] : '';
$search_condition = '';
if (!empty($search_term)) {
    $search_term = "%$search_term%";
    $search_condition = " AND (ime LIKE ? OR priimek LIKE ? OR email LIKE ? OR specializacija LIKE ?)";
}

$sql = "SELECT * FROM uporabniki WHERE vloga_id = 2" . $search_condition . " ORDER BY priimek, ime";
$stmt = mysqli_prepare($conn, $sql);
if (!empty($search_term)) {
    mysqli_stmt_bind_param($stmt, "ssss", $search_term, $search_term, $search_term, $search_term);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$doctors = mysqli_fetch_all($result, MYSQLI_ASSOC);


$sql = "SELECT id, ime, priimek FROM uporabniki WHERE vloga_id = 3 ORDER BY priimek, ime";
$result = mysqli_query($conn, $sql);
if (!$result) {
    die("Error fetching patients: " . mysqli_error($conn));
}
$patients = mysqli_fetch_all($result, MYSQLI_ASSOC);


$current_patients = array();
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $sql = "SELECT id FROM uporabniki WHERE zdravnik_id = ? AND vloga_id = 3";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $_GET['id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $current_patients[] = $row['id'];
    }
}
?>
<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <title>Portal IRIS - Upravljanje zdravnikov</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <h2>Admin Panel</h2>
            <nav>
                <ul>
                    <li><a href="./index.php">Dashboard</a></li>
                    <li><a href="./uporabniki.php">Uporabniki</a></li>
                    <li><a href="./zdravniki.php">Zdravniki</a></li>
                </ul>
            </nav>
            <div class="logout-link">
                <a href="../seja_izbris.php">Odjava</a>
            </div>
        </aside>
        
        <main class="content">
            <section class="hero">
                <div class="user-info">
                    <p>Prijavljeni ste kot: <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong> (Admin) | <a href="../seja_izbris.php">Odjava</a></p>
                </div>
                <h2>Upravljanje zdravnikov</h2>
                <p>Dodajajte, urejajte in brišite zdravnike v sistemu</p>
            </section>
            
            <a href="?action=new" class="btn">Nov zdravnik</a>
            
            <div class="search-box">
                <form method="GET" action="zdravniki.php">
                    <input type="text" name="search" placeholder="Išči zdravnike..." value="<?php echo htmlspecialchars($search_term); ?>">
                    <button type="submit">Išči</button>
                </form>
            </div>
            
            <div class="doctor-list">
                <?php foreach ($doctors as $doctor): ?>
                <div class="doctor-item">
                    <div class="doctor-info">
                        <h3><?php echo htmlspecialchars($doctor['ime'] . ' ' . $doctor['priimek']); ?></h3>
                        <p><strong>E-pošta:</strong> <?php echo htmlspecialchars($doctor['email']); ?></p>
                        <p><strong>Telefon:</strong> <?php echo htmlspecialchars($doctor['telefon']); ?></p>
                        <p><strong>Specializacija:</strong> <?php echo htmlspecialchars($doctor['specializacija']); ?></p>
                    </div>
                    <div class="doctor-actions">
                        <a href="?action=edit&id=<?php echo $doctor['id']; ?>" class="btn">Uredi</a>
                        <form method="POST" action="zdravniki.php" style="display: inline;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="doctor_id" value="<?php echo $doctor['id']; ?>">
                            <button type="submit" class="btn btn-danger" onclick="return confirm('Ali ste prepričani, da želite izbrisati tega zdravnika?')">Izbriši</button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>
    
    <?php if (isset($_GET['action']) && ($_GET['action'] === 'new' || $_GET['action'] === 'edit')): ?>
    <div class="modal" style="display: block;">
        <div class="modal-content">
            <h2><?php echo $_GET['action'] === 'new' ? 'Nov zdravnik' : 'Uredi zdravnika'; ?></h2>
            <form method="POST" action="zdravniki.php">
                <input type="hidden" name="action" value="<?php echo $_GET['action'] === 'new' ? 'create' : 'update'; ?>">
                <?php if ($_GET['action'] === 'edit'): ?>
                    <input type="hidden" name="doctor_id" value="<?php echo $_GET['id']; ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="ime">Ime:</label>
                    <input type="text" name="ime" id="ime" required value="<?php echo isset($_GET['action']) && $_GET['action'] === 'edit' ? htmlspecialchars($doctor['ime']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="priimek">Priimek:</label>
                    <input type="text" name="priimek" id="priimek" required value="<?php echo isset($_GET['action']) && $_GET['action'] === 'edit' ? htmlspecialchars($doctor['priimek']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="email">E-pošta:</label>
                    <input type="email" name="email" id="email" required value="<?php echo isset($_GET['action']) && $_GET['action'] === 'edit' ? htmlspecialchars($doctor['email']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="telefon">Telefon:</label>
                    <input type="tel" name="telefon" id="telefon" required value="<?php echo isset($_GET['action']) && $_GET['action'] === 'edit' ? htmlspecialchars($doctor['telefon']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="naslov">Naslov:</label>
                    <input type="text" name="naslov" id="naslov" required value="<?php echo isset($_GET['action']) && $_GET['action'] === 'edit' ? htmlspecialchars($doctor['naslov']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="specializacija">Specializacija:</label>
                    <input type="text" name="specializacija" id="specializacija" required value="<?php echo isset($_GET['action']) && $_GET['action'] === 'edit' ? htmlspecialchars($doctor['specializacija']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="patient_ids">Dodeli paciente:</label>
                    <select name="patient_ids[]" id="patient_ids" multiple size="5">
                        <?php 
                        if (empty($patients)) {
                            echo '<option value="" disabled>Ni na voljo pacientov</option>';
                        } else {
                            foreach ($patients as $patient): 
                        ?>
                            <option value="<?php echo $patient['id']; ?>" <?php echo in_array($patient['id'], $current_patients) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($patient['priimek'] . ' ' . $patient['ime']); ?>
                            </option>
                        <?php 
                            endforeach;
                        }
                        ?>
                    </select>
                    <small>Držite Ctrl (ali Cmd na Mac) za izbiro več pacientov</small>
                </div>
                
                <div class="modal-buttons">
                    <a href="zdravniki.php" class="btn">Prekliči</a>
                    <button type="submit" class="btn"><?php echo $_GET['action'] === 'new' ? 'Dodaj' : 'Posodobi'; ?></button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
    
    <?php include '../footer.php'; ?>
</body>
</html> 