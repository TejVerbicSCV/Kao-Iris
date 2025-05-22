<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../prijava.php");
    exit();
}

include '../baza.php';
?>
<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <title>Portal IRIS - Admin Dashboard</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .admin-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .admin-card {
            background-color: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .admin-card h3 {
            color: #2c3e50;
            margin-bottom: 1rem;
        }
        
        .admin-card p {
            color: #666;
            margin-bottom: 1rem;
        }
        
        .admin-card .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        
        .admin-card .btn:hover {
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
                <h2>Admin Dashboard</h2>
                <p>Upravljanje sistema IRIS</p>
            </section>
            
            <div class="stats">
                <?php
                // Get total users
                $sql = "SELECT COUNT(*) as total FROM uporabniki WHERE vloga_id = 3";
                $result = $conn->query($sql);
                $total_users = $result->fetch_assoc()['total'];
                
                // Get total doctors
                $sql = "SELECT COUNT(*) as total FROM uporabniki WHERE vloga_id = 2";
                $result = $conn->query($sql);
                $total_doctors = $result->fetch_assoc()['total'];
                
                // Get total prescriptions
                $sql = "SELECT COUNT(*) as total FROM recepti";
                $result = $conn->query($sql);
                $total_prescriptions = $result->fetch_assoc()['total'];
                
                // Get total referrals
                $sql = "SELECT COUNT(*) as total FROM napotnice";
                $result = $conn->query($sql);
                $total_referrals = $result->fetch_assoc()['total'];
                ?>
                
                <div class="stat-item">
                    <div class="number"><?php echo $total_users; ?></div>
                    <div class="label">Pacienti</div>
                </div>
                
                <div class="stat-item">
                    <div class="number"><?php echo $total_doctors; ?></div>
                    <div class="label">Zdravniki</div>
                </div>
                
                <div class="stat-item">
                    <div class="number"><?php echo $total_prescriptions; ?></div>
                    <div class="label">Recepti</div>
                </div>
                
                <div class="stat-item">
                    <div class="number"><?php echo $total_referrals; ?></div>
                    <div class="label">Napotnice</div>
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