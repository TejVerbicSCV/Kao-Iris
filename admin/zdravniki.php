<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../prijava.php");
    exit();
}

include '../baza.php';

// Handle doctor creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $ime = $_POST['ime'];
    $priimek = $_POST['priimek'];
    $email = $_POST['email'];
    $telefon = $_POST['telefon'];
    $naslov = $_POST['naslov'];
    $specializacija = $_POST['specializacija'];
    $geslo = password_hash('zdravnik123', PASSWORD_DEFAULT); // Default password
    
    $sql = "INSERT INTO uporabniki (ime, priimek, email, telefon, naslov, geslo, vloga_id, specializacija) 
            VALUES (?, ?, ?, ?, ?, ?, 2, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssss", $ime, $priimek, $email, $telefon, $naslov, $geslo, $specializacija);
    $stmt->execute();
    
    header("Location: zdravniki.php");
    exit();
}

// Handle doctor deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $doctor_id = $_POST['doctor_id'];
    
    // First, update patients assigned to this doctor
    $sql = "UPDATE uporabniki SET zdravnik_id = NULL WHERE zdravnik_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    
    // Then delete the doctor
    $sql = "DELETE FROM uporabniki WHERE id = ? AND vloga_id = 2";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    
    header("Location: zdravniki.php");
    exit();
}

// Get all doctors
$sql = "SELECT * FROM uporabniki WHERE vloga_id = 2 ORDER BY priimek, ime";
$result = $conn->query($sql);
$doctors = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <title>Portal IRIS - Upravljanje zdravnikov</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .doctor-list {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-top: 2rem;
        }
        
        .doctor-item {
            padding: 1rem;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .doctor-item:last-child {
            border-bottom: none;
        }
        
        .doctor-info {
            flex: 1;
        }
        
        .doctor-info h3 {
            margin: 0;
            color: #2c3e50;
        }
        
        .doctor-info p {
            margin: 0.5rem 0;
            color: #666;
        }
        
        .doctor-actions {
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
        
        .btn-danger {
            background-color: #e74c3c;
        }
        
        .btn:hover {
            opacity: 0.9;
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
            <h2>Admin Panel</h2>
            <nav>
                <ul>
                    <li><a href="./index.php">Dashboard</a></li>
                    <li><a href="./uporabniki.php">Uporabniki</a></li>
                    <li><a href="./zdravniki.php">Zdravniki</a></li>
                    <li><a href="../seja_izbris.php">Odjava</a></li>
                </ul>
            </nav>
        </aside>
        
        <main class="content">
            <section class="hero">
                <div class="user-info">
                    <p>Prijavljeni ste kot: <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong> (Admin) | <a href="../seja_izbris.php">Odjava</a></p>
                </div>
                <h2>Upravljanje zdravnikov</h2>
                <p>Dodajajte, urejajte in brišite zdravnike v sistemu</p>
            </section>
            
            <button class="btn" onclick="openModal()">Nov zdravnik</button>
            
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="Išči zdravnike...">
                <button onclick="searchDoctors()">Išči</button>
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
    
    <!-- Modal for adding new doctor -->
    <div id="doctorModal" class="modal">
        <div class="modal-content">
            <h2>Nov zdravnik</h2>
            <form method="POST" action="zdravniki.php">
                <input type="hidden" name="action" value="create">
                
                <div class="form-group">
                    <label for="ime">Ime:</label>
                    <input type="text" name="ime" id="ime" required>
                </div>
                
                <div class="form-group">
                    <label for="priimek">Priimek:</label>
                    <input type="text" name="priimek" id="priimek" required>
                </div>
                
                <div class="form-group">
                    <label for="email">E-pošta:</label>
                    <input type="email" name="email" id="email" required>
                </div>
                
                <div class="form-group">
                    <label for="telefon">Telefon:</label>
                    <input type="tel" name="telefon" id="telefon" required>
                </div>
                
                <div class="form-group">
                    <label for="naslov">Naslov:</label>
                    <input type="text" name="naslov" id="naslov" required>
                </div>
                
                <div class="form-group">
                    <label for="specializacija">Specializacija:</label>
                    <input type="text" name="specializacija" id="specializacija" required>
                </div>
                
                <div class="modal-buttons">
                    <button type="button" class="btn" onclick="closeModal()">Prekliči</button>
                    <button type="submit" class="btn">Dodaj</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function openModal() {
            document.getElementById('doctorModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('doctorModal').style.display = 'none';
        }
        
        function searchDoctors() {
            const searchInput = document.getElementById('searchInput');
            const searchTerm = searchInput.value.toLowerCase();
            const doctorItems = document.querySelectorAll('.doctor-item');
            
            doctorItems.forEach(item => {
                const doctorInfo = item.textContent.toLowerCase();
                if (doctorInfo.includes(searchTerm)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        }
        
        // Add event listener for Enter key
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchDoctors();
            }
        });
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('doctorModal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
    <?php include '../footer.php'; ?>
</body>
</html> 