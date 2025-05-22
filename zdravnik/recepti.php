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

// Handle prescription creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $patient_id = $_POST['patient_id'];
    $zdravilo = $_POST['zdravilo'];
    $doza = $_POST['doza'];
    $navodila = $_POST['navodila'];
    $datum_izdaje = date('Y-m-d');
    $datum_poteka = $_POST['datum_poteka'];
    
    $sql = "INSERT INTO recepti (uporabnik_id, zdravnik_id, zdravilo, doza, navodila, datum_izdaje, datum_poteka) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iisssss", $patient_id, $doctor_id, $zdravilo, $doza, $navodila, $datum_izdaje, $datum_poteka);
    $stmt->execute();
    
    header("Location: recepti.php");
    exit();
}

// Get all prescriptions for this doctor
$sql = "SELECT r.*, u.ime, u.priimek 
        FROM recepti r 
        JOIN uporabniki u ON r.uporabnik_id = u.id 
        WHERE r.zdravnik_id = ? 
        ORDER BY r.datum_izdaje DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$prescriptions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get all patients for this doctor
$sql = "SELECT id, ime, priimek FROM uporabniki WHERE zdravnik_id = ? ORDER BY priimek, ime";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$patients = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <title>Portal IRIS - Upravljanje receptov</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .prescription-list {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-top: 2rem;
        }
        
        .prescription-item {
            padding: 1rem;
            border-bottom: 1px solid #eee;
        }
        
        .prescription-item:last-child {
            border-bottom: none;
        }
        
        .prescription-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        
        .prescription-header h3 {
            margin: 0;
            color: #2c3e50;
        }
        
        .prescription-info {
            color: #666;
        }
        
        .prescription-info p {
            margin: 0.5rem 0;
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
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }
        
        .modal-content {
            background-color: white;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            padding: 2rem;
            border-radius: 8px;
            width: 80%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #2c3e50;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        
        .form-group textarea {
            height: 100px;
            resize: vertical;
        }
        
        .modal-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 1rem;
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
                <h2>Upravljanje receptov</h2>
                <p>Pregled in upravljanje z recepti za vaše paciente</p>
            </section>
            
            <button class="btn" onclick="openModal()">Nov recept</button>
            
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="Išči recepte po zdravilu ali pacientu...">
                <button onclick="searchPrescriptions()">Išči</button>
            </div>
            
            <div class="prescription-list">
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
                        <p><strong>Veljavno do:</strong> <?php echo date('d.m.Y', strtotime($prescription['datum_poteka'])); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>
    
    <!-- Modal for creating new prescription -->
    <div id="prescriptionModal" class="modal">
        <div class="modal-content">
            <h2>Nov recept</h2>
            <form method="POST" action="recepti.php">
                <input type="hidden" name="action" value="create">
                
                <div class="form-group">
                    <label for="patient_id">Pacient:</label>
                    <select name="patient_id" id="patient_id" required>
                        <option value="">Izberite pacienta</option>
                        <?php foreach ($patients as $patient): ?>
                        <option value="<?php echo $patient['id']; ?>">
                            <?php echo htmlspecialchars($patient['ime'] . ' ' . $patient['priimek']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="zdravilo">Zdravilo:</label>
                    <input type="text" name="zdravilo" id="zdravilo" required>
                </div>
                
                <div class="form-group">
                    <label for="doza">Doza:</label>
                    <input type="text" name="doza" id="doza" required>
                </div>
                
                <div class="form-group">
                    <label for="navodila">Navodila:</label>
                    <textarea name="navodila" id="navodila" required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="datum_poteka">Veljavno do:</label>
                    <input type="date" name="datum_poteka" id="datum_poteka" required>
                </div>
                
                <div class="modal-buttons">
                    <button type="button" class="btn" onclick="closeModal()">Prekliči</button>
                    <button type="submit" class="btn">Shrani</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function openModal() {
            document.getElementById('prescriptionModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('prescriptionModal').style.display = 'none';
        }
        
        function searchPrescriptions() {
            const searchInput = document.getElementById('searchInput');
            const searchTerm = searchInput.value.toLowerCase();
            const prescriptionItems = document.querySelectorAll('.prescription-item');
            
            prescriptionItems.forEach(item => {
                const prescriptionInfo = item.textContent.toLowerCase();
                if (prescriptionInfo.includes(searchTerm)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        }
        
        // Add event listener for Enter key
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchPrescriptions();
            }
        });
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('prescriptionModal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
    <?php include '../footer.php'; ?>
</body>
</html> 