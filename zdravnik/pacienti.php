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

$search_term = isset($_GET['search']) ? $_GET['search'] : '';
$search_condition = '';
if (!empty($search_term)) {
    $search_term = "%$search_term%";
    $search_condition = " AND (ime LIKE ? OR priimek LIKE ? OR email LIKE ? OR telefon LIKE ?)";
}

$query = "SELECT u.*, 
          (SELECT COUNT(*) FROM recepti WHERE uporabnik_id = u.id) as total_prescriptions,
          (SELECT COUNT(*) FROM napotnice WHERE uporabnik_id = u.id) as total_referrals,
          (SELECT COUNT(*) FROM bolniske WHERE uporabnik_id = u.id) as total_sick_leaves
          FROM uporabniki u 
          JOIN vloge v ON u.vloga_id = v.id
          WHERE v.naziv = 'pacient' 
          AND u.zdravnik_id = ?" . $search_condition . " 
          ORDER BY u.priimek, u.ime";

$stmt = mysqli_prepare($conn, $query);

if (!empty($search_term)) {
    mysqli_stmt_bind_param($stmt, "issss", $_SESSION['user_id'], $search_term, $search_term, $search_term, $search_term);
} else {
    mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$patients = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <title>Portal IRIS - Upravljanje pacientov</title>
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
                    <p>Prijavljeni ste kot: <strong><?php echo htmlspecialchars($doctor['ime'] . ' ' . $doctor['priimek']); ?></strong> (Zdravnik) | <a href="../seja_izbris.php">Odjava</a></p>
                </div>
                <h2>Upravljanje pacientov</h2>
                <p>Pregled in upravljanje vaših pacientov</p>
            </section>
            
            <div class="search-box">
                <form method="GET" action="">
                    <input type="text" name="search" placeholder="Išči paciente..." value="<?php echo htmlspecialchars($search_term); ?>">
                    <button type="submit">Išči</button>
                </form>
            </div>
            
            <div class="patient-list">
                <?php if (empty($patients)): ?>
                    <div class="no-patients">
                        <p>Ni najdenih pacientov.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($patients as $patient): ?>
                        <div class="patient-item">
                            <div class="patient-info">
                                <h3><?php echo htmlspecialchars($patient['ime'] . ' ' . $patient['priimek']); ?></h3>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($patient['email']); ?></p>
                                <p><strong>Telefon:</strong> <?php echo htmlspecialchars($patient['telefon']); ?></p>
                                <div class="patient-stats">
                                    <div class="stat-item">
                                        <span class="number"><?php echo $patient['total_prescriptions']; ?></span>
                                        <span class="label">Recepti</span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="number"><?php echo $patient['total_referrals']; ?></span>
                                        <span class="label">Napotnice</span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="number"><?php echo $patient['total_sick_leaves']; ?></span>
                                        <span class="label">Bolniške</span>
                                    </div>
                                </div>
                            </div>
                            <div class="patient-actions">
                                <a href="recepti.php?patient_id=<?php echo $patient['id']; ?>" class="btn">Recepti</a>
                                <a href="napotnice.php?patient_id=<?php echo $patient['id']; ?>" class="btn">Napotnice</a>
                                <a href="bolniske.php?patient_id=<?php echo $patient['id']; ?>" class="btn">Bolniške</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
    <?php include '../footer.php'; ?>
</body>
</html> 