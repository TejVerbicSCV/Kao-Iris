<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../prijava.php");
    exit();
}

include '../baza.php';

// Handle user deletion
if (isset($_POST['delete_user'])) {
    $user_id = $_POST['delete_user'];
    $sql = "DELETE FROM uporabniki WHERE id = ? AND vloga_id != 1"; // Prevent deleting admin
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
}

// Handle user role update
if (isset($_POST['update_role'])) {
    $user_id = $_POST['user_id'];
    $new_role = $_POST['new_role'];
    $sql = "UPDATE uporabniki SET vloga_id = ? WHERE id = ? AND vloga_id != 1"; // Prevent changing admin role
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $new_role, $user_id);
    $stmt->execute();
}

// Handle doctor assignment
if (isset($_POST['assign_doctor'])) {
    $user_id = $_POST['user_id'];
    $doctor_id = $_POST['doctor_id'];
    $sql = "UPDATE uporabniki SET zdravnik_id = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $doctor_id, $user_id);
    $stmt->execute();
}

// Get all users
$sql = "SELECT u.*, v.naziv as vloga, z.ime as zdravnik_ime, z.priimek as zdravnik_priimek 
        FROM uporabniki u 
        JOIN vloge v ON u.vloga_id = v.id 
        LEFT JOIN uporabniki z ON u.zdravnik_id = z.id AND z.vloga_id = 2
        ORDER BY u.priimek, u.ime";
$users = $conn->query($sql);

// Get all doctors for assignment
$sql = "SELECT id, ime, priimek, specializacija FROM uporabniki WHERE vloga_id = 2 ORDER BY priimek, ime";
$doctors = $conn->query($sql);

// Get all roles
$sql = "SELECT id, naziv FROM vloge ORDER BY id";
$roles = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <title>Portal IRIS - Upravljanje uporabnikov</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .user-list {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-top: 2rem;
        }
        
        .user-item {
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .user-item:last-child {
            border-bottom: none;
        }
        
        .user-info {
            flex: 1;
        }
        
        .user-info h3 {
            margin: 0;
            color: #2c3e50;
        }
        
        .user-info p {
            margin: 0.5rem 0 0;
            color: #666;
        }
        
        .user-actions {
            display: flex;
            gap: 1rem;
        }
        
        .btn {
            display: inline-block;
            padding: 0.5rem 1rem;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border: none;
            border-radius: 4px;
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
        
        .btn-secondary {
            background-color: #95a5a6;
        }
        
        .btn-secondary:hover {
            background-color: #7f8c8d;
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
            margin: 10% auto;
            padding: 2rem;
            border-radius: 8px;
            max-width: 500px;
        }
        
        .modal-header {
            margin-bottom: 1.5rem;
        }
        
        .modal-header h3 {
            margin: 0;
            color: #2c3e50;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #34495e;
        }
        
        .form-group select {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <h2>Admin Panel</h2>
            <nav>
                <ul>
                    <li><a href="index.php">Dashboard</a></li>
                    <li><a href="uporabniki.php">Uporabniki</a></li>
                    <li><a href="zdravniki.php">Zdravniki</a></li>
                    <li><a href="../seja_izbris.php">Odjava</a></li>
                </ul>
            </nav>
        </aside>
        
        <main class="content">
            <section class="hero">
                <div class="user-info">
                    <p>Prijavljeni ste kot: <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong> (Admin) | <a href="../seja_izbris.php">Odjava</a></p>
                </div>
                <h2>Upravljanje uporabnikov</h2>
                <p>Dodajajte, urejajte in brišite uporabnike sistema.</p>
            </section>
            
            <div class="user-list">
                <?php while ($user = $users->fetch_assoc()): ?>
                    <div class="user-item">
                        <div class="user-info">
                            <h3><?php echo htmlspecialchars($user['ime'] . ' ' . $user['priimek']); ?></h3>
                            <p>Email: <?php echo htmlspecialchars($user['email']); ?></p>
                            <p>Vloga: <?php echo htmlspecialchars($user['vloga']); ?></p>
                            <?php if ($user['zdravnik_ime']): ?>
                                <p>Zdravnik: <?php echo htmlspecialchars($user['zdravnik_ime'] . ' ' . $user['zdravnik_priimek']); ?></p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="user-actions">
                            <?php if ($user['vloga_id'] != 1): // Don't show actions for admin ?>
                                <button class="btn" onclick="showRoleModal(<?php echo $user['id']; ?>)">Spremeni vlogo</button>
                                <button class="btn" onclick="showDoctorModal(<?php echo $user['id']; ?>)">Dodeli zdravnika</button>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="delete_user" value="<?php echo $user['id']; ?>">
                                    <button type="submit" class="btn btn-danger" onclick="return confirm('Ali ste prepričani, da želite izbrisati tega uporabnika?')">Izbriši</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </main>
    </div>
    
    <!-- Role Modal -->
    <div id="roleModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Spremeni vlogo uporabnika</h3>
            </div>
            <form method="POST">
                <input type="hidden" name="user_id" id="roleUserId">
                <div class="form-group">
                    <label for="new_role">Nova vloga:</label>
                    <select name="new_role" id="new_role" required>
                        <?php while ($role = $roles->fetch_assoc()): ?>
                            <option value="<?php echo $role['id']; ?>"><?php echo htmlspecialchars($role['naziv']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeRoleModal()">Prekliči</button>
                    <button type="submit" name="update_role" class="btn">Posodobi</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Doctor Modal -->
    <div id="doctorModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Dodeli zdravnika uporabniku</h3>
            </div>
            <form method="POST">
                <input type="hidden" name="user_id" id="doctorUserId">
                <div class="form-group">
                    <label for="doctor_id">Izberite zdravnika:</label>
                    <select name="doctor_id" id="doctor_id" required>
                        <option value="">-- Izberite zdravnika --</option>
                        <?php while ($doctor = $doctors->fetch_assoc()): ?>
                            <option value="<?php echo $doctor['id']; ?>">
                                <?php echo htmlspecialchars($doctor['priimek'] . ' ' . $doctor['ime'] . ' (' . $doctor['specializacija'] . ')'); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeDoctorModal()">Prekliči</button>
                    <button type="submit" name="assign_doctor" class="btn">Dodeli</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function showRoleModal(userId) {
            document.getElementById('roleUserId').value = userId;
            document.getElementById('roleModal').style.display = 'block';
        }
        
        function closeRoleModal() {
            document.getElementById('roleModal').style.display = 'none';
        }
        
        function showDoctorModal(userId) {
            document.getElementById('doctorUserId').value = userId;
            document.getElementById('doctorModal').style.display = 'block';
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