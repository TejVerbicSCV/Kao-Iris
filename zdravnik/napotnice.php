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

// Handle referral creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $patient_id = $_POST['patient_id'];
    $specializacija = $_POST['specializacija'];
    $ustanova = $_POST['ustanova'];
    $zadeva = $_POST['zadeva'];
    $razlog = $_POST['razlog'];
    $nujnost = $_POST['nujnost'];
    $datum_izdaje = date('Y-m-d');
    $datum_pregleda = $_POST['datum_pregleda'];
    $status = 'pending';
    
    $sql = "INSERT INTO napotnice (uporabnik_id, zdravnik_id, specializacija, ustanova, zadeva, razlog, nujnost, datum_izdaje, datum_pregleda, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iissssssss", $patient_id, $doctor_id, $specializacija, $ustanova, $zadeva, $razlog, $nujnost, $datum_izdaje, $datum_pregleda, $status);
    $stmt->execute();
    
    header("Location: napotnice.php");
    exit();
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $referral_id = $_POST['referral_id'];
    $new_status = $_POST['status'];
    
    $sql = "UPDATE napotnice SET status = ? WHERE id = ? AND zdravnik_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $new_status, $referral_id, $doctor_id);
    $stmt->execute();
    
    header("Location: napotnice.php");
    exit();
}

// Get all referrals for this doctor
$sql = "SELECT n.*, u.ime, u.priimek 
        FROM napotnice n 
        JOIN uporabniki u ON n.uporabnik_id = u.id 
        WHERE n.zdravnik_id = ? 
        ORDER BY n.datum_izdaje DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$referrals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

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
    <title>Portal IRIS - Upravljanje napotnic</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .referral-list {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-top: 2rem;
        }
        
        .referral-item {
            padding: 1rem;
            border-bottom: 1px solid #eee;
        }
        
        .referral-item:last-child {
            border-bottom: none;
        }
        
        .referral-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        
        .referral-header h3 {
            margin: 0;
            color: #2c3e50;
        }
        
        .referral-info {
            color: #666;
        }
        
        .referral-info p {
            margin: 0.5rem 0;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.9rem;
            color: white;
        }
        
        .status-novo { background-color: #3498db; }
        .status-potrjeno { background-color: #2ecc71; }
        .status-zavrnjeno { background-color: #e74c3c; }
        
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
                <h2>Upravljanje napotnic</h2>
                <p>Pregled in upravljanje z napotnicami za vaše paciente</p>
            </section>
            
            <button class="btn" onclick="openModal()">Nova napotnica</button>
            
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="Išči napotnice po specializaciji ali pacientu...">
                <button onclick="searchReferrals()">Išči</button>
            </div>
            
            <div class="referral-list">
                <?php foreach ($referrals as $referral): ?>
                <div class="referral-item">
                    <div class="referral-header">
                        <h3>Napotnica za: <?php echo htmlspecialchars($referral['ime'] . ' ' . $referral['priimek']); ?></h3>
                        <span class="status-badge status-<?php echo $referral['status']; ?>">
                            <?php echo ucfirst($referral['status']); ?>
                        </span>
                    </div>
                    <div class="referral-info">
                        <p><strong>Zadeva:</strong> <?php echo htmlspecialchars($referral['zadeva']); ?></p>
                        <p><strong>Specializacija:</strong> <?php echo htmlspecialchars($referral['specializacija']); ?></p>
                        <p><strong>Ustanova:</strong> <?php echo htmlspecialchars($referral['ustanova']); ?></p>
                        <p><strong>Nujnost:</strong> 
                            <?php 
                            switch($referral['nujnost']) {
                                case 'nujno':
                                    echo '<span style="color: #e74c3c;">Nujno</span>';
                                    break;
                                case 'obstojno':
                                    echo '<span style="color: #f39c12;">Obstojno</span>';
                                    break;
                                case 'planirano':
                                    echo '<span style="color: #27ae60;">Planirano</span>';
                                    break;
                            }
                            ?>
                        </p>
                        <p><strong>Razlog:</strong> <?php echo htmlspecialchars($referral['razlog']); ?></p>
                        <p><strong>Datum izdaje:</strong> <?php echo date('d.m.Y', strtotime($referral['datum_izdaje'])); ?></p>
                        <p><strong>Datum pregleda:</strong> <?php echo date('d.m.Y', strtotime($referral['datum_pregleda'])); ?></p>
                    </div>
                    <div class="referral-actions">
                        <form method="POST" action="napotnice.php" style="display: inline;">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="referral_id" value="<?php echo $referral['id']; ?>">
                            <select name="status" onchange="this.form.submit()">
                                <option value="novo" <?php echo $referral['status'] === 'novo' ? 'selected' : ''; ?>>Novo</option>
                                <option value="potrjeno" <?php echo $referral['status'] === 'potrjeno' ? 'selected' : ''; ?>>Potrjeno</option>
                                <option value="zavrnjeno" <?php echo $referral['status'] === 'zavrnjeno' ? 'selected' : ''; ?>>Zavrnjeno</option>
                            </select>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>
    
    <!-- Modal for creating new referral -->
    <div id="referralModal" class="modal">
        <div class="modal-content">
            <h2>Nova napotnica</h2>
            <form method="POST" action="napotnice.php">
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
                    <label for="specializacija">Specializacija:</label>
                    <input type="text" name="specializacija" id="specializacija" required>
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
                    <label for="nujnost">Nujnost:</label>
                    <select name="nujnost" id="nujnost" required>
                        <option value="nujno">Nujno</option>
                        <option value="obstojno">Obstojno</option>
                        <option value="planirano">Planirano</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="razlog">Razlog:</label>
                    <textarea name="razlog" id="razlog" required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="datum_pregleda">Datum pregleda:</label>
                    <input type="date" name="datum_pregleda" id="datum_pregleda" required>
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
            document.getElementById('referralModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('referralModal').style.display = 'none';
        }
        
        function searchReferrals() {
            const searchInput = document.getElementById('searchInput');
            const searchTerm = searchInput.value.toLowerCase();
            const referralItems = document.querySelectorAll('.referral-item');
            
            referralItems.forEach(item => {
                const referralInfo = item.textContent.toLowerCase();
                if (referralInfo.includes(searchTerm)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        }
        
        // Add event listener for Enter key
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchReferrals();
            }
        });
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('referralModal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
    <?php include '../footer.php'; ?>
</body>
</html> 