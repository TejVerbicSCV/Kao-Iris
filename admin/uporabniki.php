<?php
session_start();
include '../baza.php';
checkUserAuth('admin');

if (isset($_POST['delete_user'])) {
    $user_id = $_POST['delete_user'];
    
    mysqli_begin_transaction($conn);
    
    try {
        $tables = ['bolniske', 'recepti', 'napotnice'];
        foreach ($tables as $table) {
            $sql = "DELETE FROM $table WHERE uporabnik_id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            mysqli_stmt_execute($stmt);
        }
        
        $sql = "DELETE FROM uporabniki WHERE id = ? AND vloga_id != 1";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        
        mysqli_commit($conn);
    } catch (Exception $e) {

        mysqli_rollback($conn);
        echo "Napaka pri brisanju uporabnika: " . $e->getMessage();
    }
}

if (isset($_POST['update_role'])) {
    $user_id = $_POST['user_id'];
    $new_role = $_POST['new_role'];
    $sql = "UPDATE uporabniki SET vloga_id = ? WHERE id = ? AND vloga_id != 1";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $new_role, $user_id);
    mysqli_stmt_execute($stmt);
}


if (isset($_POST['assign_doctor'])) {
    $user_id = $_POST['user_id'];
    $doctor_id = $_POST['doctor_id'];
    $sql = "UPDATE uporabniki SET zdravnik_id = ? WHERE id = ? AND vloga_id = 3"; 
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $doctor_id, $user_id);
    mysqli_stmt_execute($stmt);
}

$sql = "SELECT u.*, v.naziv as vloga, z.ime as zdravnik_ime, z.priimek as zdravnik_priimek 
        FROM uporabniki u 
        JOIN vloge v ON u.vloga_id = v.id 
        LEFT JOIN uporabniki z ON u.zdravnik_id = z.id AND z.vloga_id = 2
        ORDER BY u.priimek, u.ime";
$result = mysqli_query($conn, $sql);
$users = $result;

$sql = "SELECT id, ime, priimek, specializacija FROM uporabniki WHERE vloga_id = 2 ORDER BY priimek, ime";
$result = mysqli_query($conn, $sql);
$doctors = $result;

$sql = "SELECT id, naziv FROM vloge ORDER BY id";
$result = mysqli_query($conn, $sql);
$roles = $result;
?>
<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <title>Portal IRIS - Upravljanje uporabnikov</title>
    <link rel="stylesheet" href="../style.css">
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
                </ul>
            </nav>
            <div class="logout-link">
                <a href="../seja_izbris.php">Odjava</a>
            </div>
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
                <?php while ($user = mysqli_fetch_assoc($users)): ?>
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
                            <?php if ($user['vloga_id'] != 1): ?>
                                <a href="?action=role&id=<?php echo $user['id']; ?>" class="btn">Spremeni vlogo</a>
                                <?php if ($user['vloga_id'] == 3):?>
                                    <a href="?action=doctor&id=<?php echo $user['id']; ?>" class="btn">Dodeli zdravnika</a>
                                <?php endif; ?>
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
    
    <?php if (isset($_GET['action']) && $_GET['action'] === 'role'): ?>
    <div class="modal" style="display: block;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Spremeni vlogo uporabnika</h3>
            </div>
            <form method="POST">
                <input type="hidden" name="user_id" value="<?php echo $_GET['id']; ?>">
                <div class="form-group">
                    <label for="new_role">Nova vloga:</label>
                    <select name="new_role" id="new_role" required>
                        <?php 
                        mysqli_data_seek($roles, 0); 
                        while ($role = mysqli_fetch_assoc($roles)): 
                        ?>
                            <option value="<?php echo $role['id']; ?>"><?php echo htmlspecialchars($role['naziv']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="modal-actions">
                    <a href="uporabniki.php" class="btn btn-secondary">Prekliči</a>
                    <button type="submit" name="update_role" class="btn">Posodobi</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <?php if (isset($_GET['action']) && $_GET['action'] === 'doctor'): ?>  
    <div class="modal" style="display: block;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Dodeli zdravnika pacientu</h3>
            </div>
            <form method="POST">
                <input type="hidden" name="user_id" value="<?php echo $_GET['id']; ?>">
                <div class="form-group">
                    <label for="doctor_id">Izberite zdravnika:</label>
                    <select name="doctor_id" id="doctor_id" required>
                        <option value="">-- Izberite zdravnika --</option>
                        <?php 
                        mysqli_data_seek($doctors, 0);
                        while ($doctor = mysqli_fetch_assoc($doctors)): 
                        ?>
                            <option value="<?php echo $doctor['id']; ?>">
                                <?php echo htmlspecialchars($doctor['priimek'] . ' ' . $doctor['ime'] . ' (' . $doctor['specializacija'] . ')'); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="modal-actions">
                    <a href="uporabniki.php" class="btn btn-secondary">Prekliči</a>
                    <button type="submit" name="assign_doctor" class="btn">Dodeli</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <?php include '../footer.php'; ?>
</body>
</html> 