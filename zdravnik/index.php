<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'zdravnik') {
    header("Location: ../prijava.php");
    exit();
}

include '../baza.php';

// Get doctor info
$doctor_id = $_SESSION['user_id'];
$sql = "SELECT * FROM uporabniki WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$doctor = $stmt->get_result()->fetch_assoc();

// Get total patients
$sql = "SELECT COUNT(*) as total FROM uporabniki WHERE zdravnik_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$total_patients = $stmt->get_result()->fetch_assoc()['total'];

// Get total prescriptions
$sql = "SELECT COUNT(*) as total FROM recepti WHERE zdravnik_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$total_prescriptions = $stmt->get_result()->fetch_assoc()['total'];

// Get total referrals
$sql = "SELECT COUNT(*) as total FROM napotnice WHERE zdravnik_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$total_referrals = $stmt->get_result()->fetch_assoc()['total'];

// Get total sick leaves
$sql = "SELECT COUNT(*) as total FROM bolniske WHERE zdravnik_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$total_sick_leaves = $stmt->get_result()->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <title>Portal IRIS - Zdravnik Dashboard</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .doctor-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .doctor-card {
            background-color: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .doctor-card h3 {
            color: #2c3e50;
            margin-bottom: 1rem;
        }
        
        .doctor-card p {
            color: #666;
            margin-bottom: 1rem;
        }
        
        .doctor-card .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        
        .doctor-card .btn:hover {
            background-color: #2980b9;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .stat-item {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 4px;
            text-align: center;
        }
        
        .stat-item .number {
            font-size: 2rem;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .stat-item .label {
            color: #666;
            margin-top: 0.5rem;
        }
    </style>
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
                    <li><a href="pogovori.php">Pogovori</a></li>
                    <li><a href="bolniske.php">Bolniške</a></li>
                    <li><a href="../seja_izbris.php">Odjava</a></li>
                </ul>
            </nav>
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