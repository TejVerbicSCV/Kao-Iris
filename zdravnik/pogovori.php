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

// Handle new message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_message') {
    $patient_id = $_POST['patient_id'];
    $zadeva = $_POST['zadeva'];
    $sporocilo = $_POST['sporocilo'];
    $datum_poslano = date('Y-m-d H:i:s');
    
    $sql = "INSERT INTO pogovori (uporabnik_id, zdravnik_id, zadeva, sporocilo, datum_poslano, prebrano) 
            VALUES (?, ?, ?, ?, ?, 0)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iisss", $patient_id, $doctor_id, $zadeva, $sporocilo, $datum_poslano);
    $stmt->execute();
    
    header("Location: pogovori.php");
    exit();
}

// Get all conversations for this doctor with latest message
$sql = "WITH LatestMessages AS (
            SELECT p.*, 
                   CASE 
                       WHEN p.uporabnik_id = ? THEN u.ime
                       ELSE d.ime
                   END as sender_ime,
                   CASE 
                       WHEN p.uporabnik_id = ? THEN u.priimek
                       ELSE d.priimek
                   END as sender_priimek,
                   u.ime as pacient_ime,
                   u.priimek as pacient_priimek,
                   ROW_NUMBER() OVER (PARTITION BY p.zadeva ORDER BY p.datum_poslano DESC) as rn
            FROM pogovori p 
            JOIN uporabniki u ON p.uporabnik_id = u.id 
            JOIN uporabniki d ON p.zdravnik_id = d.id
            WHERE p.zdravnik_id = ?
        )
        SELECT * FROM LatestMessages WHERE rn = 1
        ORDER BY datum_poslano DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $doctor_id, $doctor_id, $doctor_id);
$stmt->execute();
$conversations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

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
    <title>Portal IRIS - Upravljanje pogovorov</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .conversation-list {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-top: 2rem;
            max-height: 600px;
            overflow-y: auto;
        }
        
        .conversation-item {
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: background-color 0.2s;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .conversation-item:hover {
            background-color: #f8f9fa;
        }
        
        .conversation-item:last-child {
            border-bottom: none;
        }
        
        .conversation-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .conversation-header h3 {
            margin: 0;
            color: #2c3e50;
            font-size: 1.1rem;
        }
        
        .conversation-date {
            color: #666;
            font-size: 0.9rem;
        }
        
        .conversation-preview {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
        }
        
        .conversation-info {
            flex: 1;
        }
        
        .conversation-info p {
            margin: 0;
            color: #666;
            font-size: 0.95rem;
        }
        
        .conversation-info .patient-name {
            color: #2c3e50;
            font-weight: 500;
            margin-bottom: 0.25rem;
        }
        
        .conversation-info .message-preview {
            color: #666;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 500px;
        }
        
        .unread {
            background-color: #f8f9fa;
        }
        
        .unread .conversation-header h3 {
            font-weight: bold;
        }
        
        .unread .message-preview {
            color: #2c3e50;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 4px;
            text-decoration: none;
            color: white;
            background-color: #3498db;
            border: none;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s;
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
            z-index: 1000;
        }
        
        .modal-content {
            background-color: white;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            padding: 2rem;
            border-radius: 8px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #2c3e50;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .form-group textarea {
            height: 150px;
            resize: vertical;
        }
        
        .modal-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 1.5rem;
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
            transition: border-color 0.3s;
        }
        
        .search-box input:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .search-box button {
            padding: 0.75rem 1.5rem;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
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
                <h2>Upravljanje pogovorov</h2>
                <p>Pregled in upravljanje s pogovori s pacientov</p>
            </section>
            
            <button class="btn" onclick="openModal()">Novo sporočilo</button>
            
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="Išči pogovore po zadevi ali pacientu...">
                <button onclick="searchConversations()">Išči</button>
            </div>
            
            <div class="conversation-list">
                <?php foreach ($conversations as $conversation): ?>
                <div class="conversation-item <?php echo $conversation['prebrano'] ? '' : 'unread'; ?>" onclick="window.location.href='pogovor.php?id=<?php echo $conversation['id']; ?>'">
                    <div class="conversation-header">
                        <h3><?php echo htmlspecialchars($conversation['zadeva']); ?></h3>
                        <span class="conversation-date"><?php echo date('d.m.Y H:i', strtotime($conversation['datum_poslano'])); ?></span>
                    </div>
                    <div class="conversation-preview">
                        <div class="conversation-info">
                            <p class="patient-name"><?php echo htmlspecialchars($conversation['pacient_ime'] . ' ' . $conversation['pacient_priimek']); ?></p>
                            <p class="message-preview">
                                <span class="sender"><?php echo htmlspecialchars($conversation['sender_ime'] . ' ' . $conversation['sender_priimek']); ?>:</span>
                                <?php echo htmlspecialchars(substr($conversation['sporocilo'], 0, 100)) . (strlen($conversation['sporocilo']) > 100 ? '...' : ''); ?>
                            </p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>
    
    <!-- Modal for sending new message -->
    <div id="messageModal" class="modal">
        <div class="modal-content">
            <h2>Novo sporočilo</h2>
            <form method="POST" action="pogovori.php">
                <input type="hidden" name="action" value="send_message">
                
                <div class="form-group">
                    <label for="patient_id">Pacient:</label>
                    <select id="patient_id" name="patient_id" required>
                        <option value="">Izberite pacienta</option>
                        <?php foreach ($patients as $patient): ?>
                            <option value="<?php echo $patient['id']; ?>">
                                <?php echo htmlspecialchars($patient['priimek'] . ' ' . $patient['ime']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="zadeva">Zadeva:</label>
                    <input type="text" id="zadeva" name="zadeva" required>
                </div>
                
                <div class="form-group">
                    <label for="sporocilo">Sporočilo:</label>
                    <textarea id="sporocilo" name="sporocilo" required></textarea>
                </div>
                
                <div class="modal-buttons">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Prekliči</button>
                    <button type="submit" class="btn">Pošlji</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function openModal() {
            document.getElementById('messageModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('messageModal').style.display = 'none';
        }
        
        function searchConversations() {
            const searchText = document.getElementById('searchInput').value.toLowerCase();
            const conversations = document.querySelectorAll('.conversation-item');
            
            conversations.forEach(conversation => {
                const text = conversation.textContent.toLowerCase();
                conversation.style.display = text.includes(searchText) ? 'flex' : 'none';
            });
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('messageModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
    <?php include '../footer.php'; ?>
</body>
</html>