<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../prijava.php");
    exit();
}

include '../baza.php';

// Handle role updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_role') {
        $user_id = $_POST['user_id'];
        $new_role = $_POST['new_role'];
        
        // Update user role
        $sql = "UPDATE uporabniki SET vloga_id = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $new_role, $user_id);
        $stmt->execute();
    }
    
    // Handle doctor assignments
    if ($_POST['action'] === 'assign_doctor') {
        $patient_id = $_POST['patient_id'];
        $doctor_id = $_POST['doctor_id'];
        
        $sql = "UPDATE uporabniki SET zdravnik_id = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $doctor_id, $patient_id);
        $stmt->execute();
    }
}

// Get all users
$sql = "SELECT u.*, v.naziv as vloga_naziv, d.ime as zdravnik_ime, d.priimek as zdravnik_priimek 
        FROM uporabniki u 
        LEFT JOIN vloge v ON u.vloga_id = v.id 
        LEFT JOIN uporabniki d ON u.zdravnik_id = d.id 
        ORDER BY u.priimek, u.ime";
$users = $conn->query($sql);

// Get all roles
$sql = "SELECT * FROM vloge";
$roles = $conn->query($sql);

// Get all doctors
$sql = "SELECT * FROM uporabniki WHERE vloga_id = 2 ORDER BY priimek, ime";
$doctors = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <title>Portal IRIS - Upravljanje uporabnikov</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .user-list {
            margin-top: 2rem;
        }
        
        .user-item {
            background: white;
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .user-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .user-actions {
            display: flex;
            gap: 1rem;
        }
        
        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
        }
        
        .btn-primary {
            background-color: #3498db;
            color: white;
        }
        
        .btn-secondary {
            background-color: #95a5a6;
            color: white;
        }
        
        .btn-danger {
            background-color: #e74c3c;
            color: white;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 15% auto;
            padding: 2rem;
            width: 50%;
            border-radius: 8px;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
        }
        
        .form-group select {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <h2>Portal IRIS</h2>
            <nav>
                <ul>
                    <li><a href="index.php">Nadzorna plošča</a></li>
                    <li><a href="upravljanje_uporabnikov.php">Upravljanje uporabnikov</a></li>
                    <li><a href="../seja_izbris.php">Odjava</a></li>
                </ul>
            </nav>
        </aside>
        
        <main class="content">
            <section class="hero">
                <div class="user-info">
                    <p>Prijavljeni ste kot: <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong> | <a href="../seja_izbris.php">Odjava</a></p>
                </div>
                <h2>Upravljanje uporabnikov</h2>
                <p>Upravljajte vloge uporabnikov in dodelitev zdravnikov.</p>
            </section>
            
            <div class="user-list">
                <?php while ($user = $users->fetch_assoc()): ?>
                    <div class="user-item">
                        <div class="user-header">
                            <div>
                                <h3><?php echo htmlspecialchars($user['ime'] . ' ' . $user['priimek']); ?></h3>
                                <p>Email: <?php echo htmlspecialchars($user['email']); ?></p>
                                <p>Vloga: <?php echo htmlspecialchars($user['vloga_naziv']); ?></p>
                                <?php if ($user['zdravnik_ime']): ?>
                                    <p>Zdravnik: <?php echo htmlspecialchars($user['zdravnik_ime'] . ' ' . $user['zdravnik_priimek']); ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="user-actions">
                                <button class="btn btn-primary" onclick="openRoleModal(<?php echo $user['id']; ?>, '<?php echo $user['vloga_naziv']; ?>')">Spremeni vlogo</button>
                                <?php if ($user['vloga_naziv'] === 'pacient'): ?>
                                    <button class="btn btn-secondary" onclick="openDoctorModal(<?php echo $user['id']; ?>)">Dodeli zdravnika</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </main>
    </div>
    
    <!-- Role Update Modal -->
    <div id="roleModal" class="modal">
        <div class="modal-content">
            <h3>Spremeni vlogo uporabnika</h3>
            <form method="POST" action="">
                <input type="hidden" name="action" value="update_role">
                <input type="hidden" name="user_id" id="role_user_id">
                
                <div class="form-group">
                    <label for="new_role">Nova vloga:</label>
                    <select name="new_role" id="new_role" required>
                        <?php while ($role = $roles->fetch_assoc()): ?>
                            <option value="<?php echo $role['id']; ?>"><?php echo htmlspecialchars($role['naziv']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="button-group">
                    <button type="submit" class="btn btn-primary">Posodobi</button>
                    <button type="button" class="btn btn-secondary" onclick="closeRoleModal()">Prekliči</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Doctor Assignment Modal -->
    <div id="doctorModal" class="modal">
        <div class="modal-content">
            <h3>Dodeli zdravnika</h3>
            <form method="POST" action="">
                <input type="hidden" name="action" value="assign_doctor">
                <input type="hidden" name="patient_id" id="patient_id">
                
                <div class="form-group">
                    <label for="doctor_id">Izberi zdravnika:</label>
                    <select name="doctor_id" id="doctor_id" required>
                        <option value="">-- Izberi zdravnika --</option>
                        <?php while ($doctor = $doctors->fetch_assoc()): ?>
                            <option value="<?php echo $doctor['id']; ?>">
                                <?php echo htmlspecialchars($doctor['priimek'] . ' ' . $doctor['ime'] . ' (' . $doctor['specializacija'] . ')'); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="button-group">
                    <button type="submit" class="btn btn-primary">Dodeli</button>
                    <button type="button" class="btn btn-secondary" onclick="closeDoctorModal()">Prekliči</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function openRoleModal(userId, currentRole) {
            document.getElementById('roleModal').style.display = 'block';
            document.getElementById('role_user_id').value = userId;
        }
        
        function closeRoleModal() {
            document.getElementById('roleModal').style.display = 'none';
        }
        
        function openDoctorModal(patientId) {
            document.getElementById('doctorModal').style.display = 'block';
            document.getElementById('patient_id').value = patientId;
        }
        
        function closeDoctorModal() {
            document.getElementById('doctorModal').style.display = 'none';
        }
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target == document.getElementById('roleModal')) {
                closeRoleModal();
            }
            if (event.target == document.getElementById('doctorModal')) {
                closeDoctorModal();
            }
        }
    </script>
    <?php include '../footer.php'; ?>
</body>
</html> 