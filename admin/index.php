<?php
session_start();
include '../baza.php';
checkUserAuth('admin');
?>
<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <title>Portal IRIS - Admin Dashboard</title>
    <link rel="stylesheet" href="../style.css">
    
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
                <h2>Admin Dashboard</h2>
                <p>Upravljanje sistema IRIS</p>
            </section>
            
            <div class="stats">
                <?php
             
                $sql = "SELECT COUNT(*) as total FROM uporabniki WHERE vloga_id = 3";
                $result = mysqli_query($conn, $sql);
                $total_users = mysqli_fetch_assoc($result)['total'];
                
             
                $sql = "SELECT COUNT(*) as total FROM uporabniki WHERE vloga_id = 2";
                $result = mysqli_query($conn, $sql);
                $total_doctors = mysqli_fetch_assoc($result)['total'];
                ?>
                
                <div class="stat-item">
                    <div class="number"><?php echo $total_users; ?></div>
                    <div class="label">Pacienti</div>
                </div>
                
                <div class="stat-item">
                    <div class="number"><?php echo $total_doctors; ?></div>
                    <div class="label">Zdravniki</div>
                </div>
            </div>
            
            <div class="admin-grid">
                <div class="admin-card">
                    <h3>Upravljanje uporabnikov</h3>
                    <p>Dodajajte, urejajte in brišite uporabnike sistema.</p>
                    <a href="./uporabniki.php" class="btn">Upravljaj uporabnike</a>
                </div>
            </div>
        </main>
    </div>
    <?php include '../footer.php'; ?>
</body>
</html> 