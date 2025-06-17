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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_sick_leave'])) {
    $patient_id = $_POST['patient_id'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $reason = $_POST['reason'];
    $notes = $_POST['notes'];
    
    $stmt = mysqli_prepare($conn, "INSERT INTO bolniske (uporabnik_id, zdravnik_id, datum_zacetka, datum_konca, razlog, opombe) VALUES (?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "iissss", $patient_id, $_SESSION['user_id'], $start_date, $end_date, $reason, $notes);
    
    if (mysqli_stmt_execute($stmt)) {
        header("Location: bolniske.php?success=1");
        exit();
    }
}

$search_term = isset($_GET['search']) ? $_GET['search'] : '';
$search_condition = '';
if (!empty($search_term)) {
    $search_term = "%$search_term%";
    $search_condition = " AND (u.ime LIKE ? OR u.priimek LIKE ? OR b.razlog LIKE ? OR b.opombe LIKE ?)";
}

// direkt pacient
$patient_condition = '';
if (isset($_GET['patient_id'])) {
    $patient_condition = " AND b.uporabnik_id = ?";
}

$query = "SELECT b.*, u.ime, u.priimek 
          FROM bolniske b 
          JOIN uporabniki u ON b.uporabnik_id = u.id 
          WHERE b.zdravnik_id = ?" . $search_condition . $patient_condition . " 
          ORDER BY b.datum_zacetka DESC";

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
$sick_leaves = mysqli_fetch_all($result, MYSQLI_ASSOC);

//en
$patient_info = null;
if (isset($_GET['patient_id'])) {
    $sql = "SELECT ime, priimek FROM uporabniki WHERE id = ? AND zdravnik_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $_GET['patient_id'], $doctor_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $patient_info = mysqli_fetch_assoc($result);
}

//vsi
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
    <title>Portal IRIS - Upravljanje bolniških</title>
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
        </aside>
        
        <main class="content">
            <section class="hero">
                <div class="user-info">
                    <p>Prijavljeni ste kot: <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong> | <a href="../seja_izbris.php">Odjava</a></p>
                </div>
                <h2>Bolniške</h2>
                <p>Upravljanje bolniških odsotnosti za vaše paciente.</p>
            </section>

            <div class="search-box">
                <form method="GET" action="">
                    <input type="text" name="search" placeholder="Išči po pacientu..." value="<?php echo htmlspecialchars($search_term); ?>">
                    <button type="submit">Išči</button>
                </form>
            </div>

            <div class="button-group" style="margin-bottom: var(--spacing-lg);">
                <button class="btn" onclick="window.location.href='?action=new'">Nova bolniška</button>
            </div>
            
            <div class="sick-leave-list">
                <?php if (empty($sick_leaves)): ?>
                    <div class="no-sick-leaves">
                        <p>Ni najdenih bolniških odsotnosti.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($sick_leaves as $sick_leave): ?>
                        <div class="sick-leave-item">
                            <div class="sick-leave-header">
                                <h3>Bolniska za: <?php echo htmlspecialchars($sick_leave['ime'] . ' ' . $sick_leave['priimek']); ?></h3>
                                <span class="sick-leave-date">
                                    <?php echo date('d.m.Y', strtotime($sick_leave['datum_zacetka'])); ?> - 
                                    <?php echo date('d.m.Y', strtotime($sick_leave['datum_konca'])); ?>
                                </span>
                            </div>
                            <div class="sick-leave-info">
                                <p><strong>Razlog:</strong> <?php echo htmlspecialchars($sick_leave['razlog']); ?></p>
                                <?php if (!empty($sick_leave['opombe'])): ?>
                                    <p><strong>Opombe:</strong> <?php echo htmlspecialchars($sick_leave['opombe']); ?></p>
                                <?php endif; ?>
                                <p><strong>Trajanje:</strong> 
                                    <?php
                                    $start = strtotime($sick_leave['datum_zacetka']);
                                    $end = strtotime($sick_leave['datum_konca']);
                                    $days = floor(($end - $start) / (60 * 60 * 24)) + 1;
                                    echo $days . ' dni';
                                    ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if (isset($_GET['action']) && $_GET['action'] === 'new'): ?>
            <div class="modal" style="display: block;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Nova bolniška odsotnost</h3>
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
                            <label for="start_date">Datum začetka:</label>
                            <input type="date" name="start_date" id="start_date" required>
                        </div>
                        <div class="form-group">
                            <label for="end_date">Datum konca:</label>
                            <input type="date" name="end_date" id="end_date" required>
                        </div>
                        <div class="form-group">
                            <label for="reason">Razlog:</label>
                            <textarea name="reason" id="reason" required></textarea>
                        </div>
                        <div class="form-group">
                            <label for="notes">Opombe:</label>
                            <textarea name="notes" id="notes"></textarea>
                        </div>
                        <div class="modal-buttons">
                            <button type="button" class="btn btn-secondary" onclick="window.location.href='bolniske.php'">Prekliči</button>
                            <button type="submit" name="create_sick_leave" class="btn btn-success">Shrani</button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
    <?php include '../footer.php'; ?>
</body>
</html> 