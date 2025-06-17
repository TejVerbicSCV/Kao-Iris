<?php
session_start();
include '../baza.php';
getUserRole('zdravnik');


$doctor_id = checkUserAuth('zdravnik');

$sql = "SELECT * FROM uporabniki WHERE id = ? AND vloga_id = 2";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $doctor_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$doctor = mysqli_fetch_assoc($result);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_prescription'])) {
    $patient_id = $_POST['patient_id'];
    $medication = $_POST['medication'];
    $dosage = $_POST['dosage'];
    $instructions = $_POST['instructions'];
    $expiry_date = $_POST['expiry_date'];
    
    $stmt = mysqli_prepare($conn, "INSERT INTO recepti (uporabnik_id, zdravnik_id, zdravilo, doza, navodila, datum_izdaje, datum_poteka) VALUES (?, ?, ?, ?, ?, NOW(), ?)");
    mysqli_stmt_bind_param($stmt, "iissss", $patient_id, $_SESSION['user_id'], $medication, $dosage, $instructions, $expiry_date);
    
    if (mysqli_stmt_execute($stmt)) {
        header("Location: recepti.php?success=1");
        exit();
    }
}

$search_term = isset($_GET['search']) ? $_GET['search'] : '';
$search_condition = '';
if (!empty($search_term)) {
    $search_term = "%$search_term%";
    $search_condition = " AND (u.ime LIKE ? OR u.priimek LIKE ? OR r.zdravilo LIKE ? OR r.doza LIKE ? OR r.navodila LIKE ?)";
}

$patient_condition = '';
if (isset($_GET['patient_id'])) {
    $patient_condition = " AND r.uporabnik_id = ?";
}
$query = "SELECT r.*, u.ime, u.priimek 
          FROM recepti r 
          JOIN uporabniki u ON r.uporabnik_id = u.id 
          WHERE r.zdravnik_id = ?" . $search_condition . $patient_condition . " 
          ORDER BY r.datum_izdaje DESC";

$stmt = mysqli_prepare($conn, $query);

if (!empty($search_term) && isset($_GET['patient_id'])) {
    mysqli_stmt_bind_param($stmt, "isssssi", $_SESSION['user_id'], $search_term, $search_term, $search_term, $search_term, $search_term, $_GET['patient_id']);
} elseif (!empty($search_term)) {
    mysqli_stmt_bind_param($stmt, "isssss", $_SESSION['user_id'], $search_term, $search_term, $search_term, $search_term, $search_term);
} elseif (isset($_GET['patient_id'])) {
    mysqli_stmt_bind_param($stmt, "ii", $_SESSION['user_id'], $_GET['patient_id']);
} else {
    mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$prescriptions = mysqli_fetch_all($result, MYSQLI_ASSOC);

$patient_info = null;
if (isset($_GET['patient_id'])) {
    $sql = "SELECT ime, priimek FROM uporabniki WHERE id = ? AND zdravnik_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $_GET['patient_id'], $doctor_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $patient_info = mysqli_fetch_assoc($result);
}

$sql = "SELECT id, ime, priimek FROM uporabniki WHERE zdravnik_id = ? ORDER BY priimek, ime";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $doctor_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$patients = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <title>Portal IRIS - Upravljanje receptov</title>
    <link rel="stylesheet" href="../style.css">
    
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <h2>Zdravnik Panel</h2>
            <nav>
                <ul>
                    <li><a href="index.php">Dashboard</a></li>
                    <li><a href="pacienti.php">Pacienti</a></li>
                    <li><a href="recepti.php">Recepti</a></li>
                    <li><a href="napotnice.php">Napotnice</a></li>
                    <li><a href="bolniske.php">Bolniške</a></li>
                </ul>
            </nav>
            <div class="logout-link">
                <a href="../seja_izbris.php">Odjava</a>
            </div>
        </aside>
        
        <main class="content">
            <section class="hero">
                <div class="user-info">
                    <p>Prijavljeni ste kot: <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong> | <a href="../seja_izbris.php">Odjava</a></p>
                </div>
                <h2>Recepti</h2>
                <p>Upravljanje receptov za vaše paciente.</p>
            </section>

            <div class="search-box">
                <form method="GET" action="">
                    <input type="text" name="search" placeholder="Išči po pacientu..." value="<?php echo htmlspecialchars($search_term); ?>">
                    <button type="submit">Išči</button>
                </form>
            </div>

            <div class="button-group" style="margin-bottom: var(--spacing-lg);">
                <button class="btn" onclick="window.location.href='?action=new'">Nov recept</button>
            </div>
            
            <div class="prescription-list">
                <?php if (empty($prescriptions)): ?>
                    <div class="no-prescriptions">
                        <p>Ni najdenih receptov.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($prescriptions as $prescription): ?>
                        <div class="prescription-item">
                            <div class="prescription-header">
                                <h3>Recept za: <?php echo htmlspecialchars($prescription['ime'] . ' ' . $prescription['priimek']); ?></h3>
                                <span class="prescription-date">Izdano: <?php echo date('d.m.Y', strtotime($prescription['datum_izdaje'])); ?></span>
                            </div>
                            <div class="prescription-info">
                                <p><strong>Zdravilo:</strong> <?php echo htmlspecialchars($prescription['zdravilo']); ?></p>
                                <p><strong>Doza:</strong> <?php echo htmlspecialchars($prescription['doza']); ?></p>
                                <p><strong>Navodila:</strong> <?php echo htmlspecialchars($prescription['navodila']); ?></p>
                                <p><strong>Datum poteka:</strong> <?php echo date('d.m.Y', strtotime($prescription['datum_poteka'])); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <?php if (isset($_GET['action']) && $_GET['action'] === 'new'): ?>
    <div class="modal" style="display: block;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Nov recept</h3>
            </div>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="patient_id">Pacient:</label>
                    <select name="patient_id" id="patient_id" required>
                        <?php
                        $stmt = mysqli_prepare($conn, "SELECT id, ime, priimek FROM uporabniki WHERE vloga_id = 3 AND zdravnik_id = ? ORDER BY priimek, ime");
                        mysqli_stmt_bind_param($stmt, "i", $doctor_id);
                        mysqli_stmt_execute($stmt);
                        $result = mysqli_stmt_get_result($stmt);
                        $patients = mysqli_fetch_all($result, MYSQLI_ASSOC);
                        foreach ($patients as $patient) {
                            echo "<option value='{$patient['id']}'>{$patient['priimek']}, {$patient['ime']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="medication">Zdravilo:</label>
                    <input type="text" name="medication" id="medication" required>
                </div>
                <div class="form-group">
                    <label for="dosage">Doza:</label>
                    <input type="text" name="dosage" id="dosage" required>
                </div>
                <div class="form-group">
                    <label for="instructions">Navodila:</label>
                    <textarea name="instructions" id="instructions" required></textarea>
                </div>
                <div class="form-group">
                    <label for="expiry_date">Datum poteka:</label>
                    <input type="date" name="expiry_date" id="expiry_date" required>
                </div>
                <div class="modal-buttons">
                    <button type="button" class="btn btn-secondary" onclick="window.location.href='recepti.php'">Prekliči</button>
                    <button type="submit" name="create_prescription" class="btn btn-success">Shrani</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
    <?php include '../footer.php'; ?>
</body>
</html> 