<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'zdravnik') {
    header("Location: ../prijava.php");
    exit();
}

include '../baza.php';

$doctor_id = $_SESSION['user_id'];

// Get doctor info
$sql = "SELECT * FROM uporabniki WHERE id = ? AND vloga_id = 2";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$doctor = $stmt->get_result()->fetch_assoc();

// Get all patients for this doctor
$sql = "SELECT u.*, v.naziv as vloga 
        FROM uporabniki u 
        JOIN vloge v ON u.vloga_id = v.id 
        WHERE u.zdravnik_id = ? 
        ORDER BY u.priimek, u.ime";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$patients = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <title>Portal IRIS - Upravljanje pacientov</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .patient-list {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-top: 2rem;
        }
        
        .patient-item {
            padding: 1rem;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .patient-item:last-child {
            border-bottom: none;
        }
        
        .patient-info {
            flex: 1;
        }
        
        .patient-info h3 {
            margin: 0;
            color: #2c3e50;
        }
        
        .patient-info p {
            margin: 0.5rem 0 0;
            color: #666;
        }
        
        .patient-actions {
            display: flex;
            gap: 1rem;
        }
        
        .btn {
            padding: 0.5rem 1rem;
            border-radius: 4px;
            text-decoration: none;
            color: white;
            background-color: #3498db;
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
        }
        
        .btn:hover {
            background-color: #2980b9;
        }
        
        .btn-danger {
            background-color: #e74c3c;
        }
        
        .btn-danger:hover {
            background-color: #c0392b;
        }
        
        .btn-success {
            background-color: #2ecc71;
        }
        
        .btn-success:hover {
            background-color: #27ae60;
        }
        
        .search-box {
            margin: 2rem 0;
            display: flex;
            gap: 1rem;
        }
        
        .search-box input {
            flex: 1;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        
        .search-box button {
            padding: 0.75rem 1.5rem;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .search-box button:hover {
            background-color: #2980b9;
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
                <h2>Upravljanje pacientov</h2>
                <p>Pregled in upravljanje vaših pacientov</p>
            </section>
            
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="Išči paciente po imenu, priimku ali emailu...">
                <button onclick="searchPatients()">Išči</button>
            </div>
            
            <div class="patient-list">
                <?php foreach ($patients as $patient): ?>
                <div class="patient-item">
                    <div class="patient-info">
                        <h3><?php echo htmlspecialchars($patient['ime'] . ' ' . $patient['priimek']); ?></h3>
                        <p>
                            Email: <?php echo htmlspecialchars($patient['email']); ?><br>
                            Telefon: <?php echo htmlspecialchars($patient['telefon']); ?><br>
                            Naslov: <?php echo htmlspecialchars($patient['naslov']); ?>
                        </p>
                    </div>
                    <div class="patient-actions">
                        <a href="recepti.php?patient_id=<?php echo $patient['id']; ?>" class="btn">Recepti</a>
                        <a href="napotnice.php?patient_id=<?php echo $patient['id']; ?>" class="btn">Napotnice</a>
                        <a href="bolniske.php?patient_id=<?php echo $patient['id']; ?>" class="btn">Bolniške</a>
                        <a href="pogovori.php?patient_id=<?php echo $patient['id']; ?>" class="btn">Pogovori</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>
    <?php include '../footer.php'; ?>
    <script>
        function searchPatients() {
            const searchInput = document.getElementById('searchInput');
            const searchTerm = searchInput.value.toLowerCase();
            const patientItems = document.querySelectorAll('.patient-item');
            
            patientItems.forEach(item => {
                const patientInfo = item.querySelector('.patient-info').textContent.toLowerCase();
                if (patientInfo.includes(searchTerm)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        }
        
        // Add event listener for Enter key
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchPatients();
            }
        });
    </script>
</body>
</html> 