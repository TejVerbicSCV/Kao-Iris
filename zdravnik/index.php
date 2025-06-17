<?php
session_start();
include '../baza.php';
$doctor_id = checkUserAuth('zdravnik');


$doctor_id = $_SESSION['user_id'];
$sql = "SELECT * FROM uporabniki WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $doctor_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$doctor = mysqli_fetch_assoc($result);

$sql = "SELECT COUNT(*) as total FROM uporabniki WHERE zdravnik_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $doctor_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$total_patients = mysqli_fetch_assoc($result)['total'];

$sql = "SELECT COUNT(*) as total FROM recepti WHERE zdravnik_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $doctor_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$total_prescriptions = mysqli_fetch_assoc($result)['total'];

$sql = "SELECT COUNT(*) as total FROM napotnice WHERE zdravnik_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $doctor_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$total_referrals = mysqli_fetch_assoc($result)['total'];

$sql = "SELECT COUNT(*) as total FROM bolniske WHERE zdravnik_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $doctor_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$total_sick_leaves = mysqli_fetch_assoc($result)['total'];
?>
<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <title>Portal IRIS - Zdravnik Dashboard</title>
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
                <h2>Zdravnik Dashboard</h2>
                <p>Upravljanje vaših pacientov in zdravstvenih dokumentov</p>
            </section>
            
            <div class="stats">
                <div class="stat-item">
                    <div class="number"><?php echo $total_patients; ?></div>
                    <div class="label">Pacienti</div>
                </div>
                
                <div class="stat-item">
                    <div class="number"><?php echo $total_prescriptions; ?></div>
                    <div class="label">Recepti</div>
                </div>
                
                <div class="stat-item">
                    <div class="number"><?php echo $total_referrals; ?></div>
                    <div class="label">Napotnice</div>
                </div>
                
                <div class="stat-item">
                    <div class="number"><?php echo $total_sick_leaves; ?></div>
                    <div class="label">Bolniške</div>
                </div>
            </div>
            
            <div class="doctor-grid">
                <div class="doctor-card">
                    <h3>Upravljanje pacientov</h3>
                    <p>Pregledujte in upravljajte s svojimi pacientov.</p>
                    <a href="pacienti.php" class="btn">Upravljaj paciente</a>
                </div>
                
                <div class="doctor-card">
                    <h3>Upravljanje receptov</h3>
                    <p>Izdajajte in upravljajte z recepti za vaše paciente.</p>
                    <a href="recepti.php" class="btn">Upravljaj recepte</a>
                </div>
                
                <div class="doctor-card">
                    <h3>Upravljanje napotnic</h3>
                    <p>Izdajajte in upravljajte z napotnicami za vaše paciente.</p>
                    <a href="napotnice.php" class="btn">Upravljaj napotnice</a>
                </div>
                
                <div class="doctor-card">
                    <h3>Upravljanje bolniških</h3>
                    <p>Izdajajte in upravljajte z bolniškimi za vaše paciente.</p>
                    <a href="bolniske.php" class="btn">Upravljaj bolniške</a>
                </div>
            </div>
        </main>
    </div>
    <?php include '../footer.php'; ?>
</body>
</html> 