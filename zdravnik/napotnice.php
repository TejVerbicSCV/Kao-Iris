<?php
session_start();
include '../baza.php';
$doctor_id = checkUserAuth('zdravnik');

$sql = "SELECT * FROM uporabniki WHERE id = ? AND vloga_id = 2";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $doctor_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$doctor = mysqli_fetch_assoc($result);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_referral'])) {
    $patient_id = $_POST['patient_id'];
    $specialist = $_POST['specialist'];
    $ustanova = $_POST['ustanova'];
    $zadeva = $_POST['zadeva'];
    $reason = $_POST['reason'];
    $nujnost = $_POST['nujnost'];
    $datum_pregleda = $_POST['datum_pregleda'];
    $status = $_POST['status'];
    $notes = $_POST['notes'];
    
    $stmt = mysqli_prepare($conn, "INSERT INTO napotnice (uporabnik_id, zdravnik_id, specializacija, ustanova, zadeva, razlog, nujnost, datum_izdaje, datum_pregleda, status, opombe) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "iissssssss", $patient_id, $_SESSION['user_id'], $specialist, $ustanova, $zadeva, $reason, $nujnost, $datum_pregleda, $status, $notes);
    
    if (mysqli_stmt_execute($stmt)) {
        header("Location: napotnice.php?success=1");
        exit();
    }
}

$search_term = isset($_GET['search']) ? $_GET['search'] : '';
$search_condition = '';
if (!empty($search_term)) {
    $search_term = "%$search_term%";
    $search_condition = " AND (u.ime LIKE ? OR u.priimek LIKE ? OR n.specializacija LIKE ? OR n.razlog LIKE ?)";
}

$patient_condition = '';
if (isset($_GET['patient_id'])) {
    $patient_condition = " AND n.uporabnik_id = ?";
}

$query = "SELECT n.*, u.ime, u.priimek 
          FROM napotnice n 
          JOIN uporabniki u ON n.uporabnik_id = u.id 
          WHERE n.zdravnik_id = ?" . $search_condition . $patient_condition . " 
          ORDER BY n.datum_izdaje DESC";

$stmt = mysqli_prepare($conn, $query);

if (!empty($search_term) && isset($_GET['patient_id'])) {
    mysqli_stmt_bind_param($stmt, "issssi", $_SESSION['user_id'], $search_term, $search_term, $search_term, $search_term, $_GET['patient_id']);
} elseif (!empty($search_term)) {
    mysqli_stmt_bind_param($stmt, "issss", $_SESSION['user_id'], $search_term, $search_term, $search_term, $search_term);
} elseif (isset($_GET['patient_id'])) {
    mysqli_stmt_bind_param($stmt, "ii", $_SESSION['user_id'], $_GET['patient_id']);
} else {
    mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$referrals = mysqli_fetch_all($result, MYSQLI_ASSOC);

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
    <title>Portal IRIS - Upravljanje napotnic</title>
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
                <h2>Napotnice</h2>
                <p>Upravljanje napotnic za vaše paciente.</p>
            </section>

            <div class="search-box">
                <form method="GET" action="">
                    <input type="text" name="search" placeholder="Išči po pacientu..." value="<?php echo htmlspecialchars($search_term); ?>">
                    <button type="submit">Išči</button>
                </form>
            </div>

            <div class="button-group" style="margin-bottom: var(--spacing-lg);">
                <button class="btn" onclick="window.location.href='?action=new'">Nova napotnica</button>
            </div>
            
            <div class="referral-list">
                <?php if (empty($referrals)): ?>
                    <div class="no-referrals">
                        <p>Ni najdenih napotnic.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($referrals as $referral): ?>
                        <div class="referral-item">
                            <div class="referral-header">
                                <h3>Napotnica za: <?php echo htmlspecialchars($referral['ime'] . ' ' . $referral['priimek']); ?></h3>
                                <span class="referral-date">Izdano: <?php echo date('d.m.Y', strtotime($referral['datum_izdaje'])); ?></span>
                            </div>
                            <div class="referral-info">
                                <p><strong>Specialist:</strong> <?php echo htmlspecialchars($referral['specializacija']); ?></p>
                                <p><strong>Razlog:</strong> <?php echo htmlspecialchars($referral['razlog']); ?></p>
                                <?php if (!empty($referral['opombe'])): ?>
                                    <p><strong>Opombe:</strong> <?php echo htmlspecialchars($referral['opombe']); ?></p>
                                <?php endif; ?>
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
                <h3>Nova napotnica</h3>
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
                    <label for="specialist">Specializacija:</label>
                    <input type="text" name="specialist" id="specialist" required>
                </div>
                <div class="form-group">
                    <label for="ustanova">Ustanova:</label>
                    <input type="text" name="ustanova" id="ustanova" required>
                </div>
                <div class="form-group">
                    <label for="zadeva">Zadeva:</label>
                    <input type="text" name="zadeva" id="zadeva" required>
                </div>
                <div class="form-group">
                    <label for="reason">Razlog:</label>
                    <textarea name="reason" id="reason" required></textarea>
                </div>
                <div class="form-group">
                    <label for="nujnost">Nujnost:</label>
                    <select name="nujnost" id="nujnost" required>
                        <option value="nujno">Nujno</option>
                        <option value="obstojno">Obstojno</option>
                        <option value="planirano">Planirano</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="datum_pregleda">Datum pregleda:</label>
                    <input type="date" name="datum_pregleda" id="datum_pregleda" required>
                </div>
                <div class="form-group">
                    <label for="status">Status:</label>
                    <select name="status" id="status" required>
                        <option value="pending">V čakanju</option>
                        <option value="completed">Opravljeno</option>
                        <option value="cancelled">Preklicano</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="notes">Opombe:</label>
                    <textarea name="notes" id="notes"></textarea>
                </div>
                <div class="modal-actions">
                    <a href="napotnice.php" class="btn btn-secondary">Prekliči</a>
                    <button type="submit" name="create_referral" class="btn">Ustvari napotnico</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
    <?php include '../footer.php'; ?>
</body>
</html> 