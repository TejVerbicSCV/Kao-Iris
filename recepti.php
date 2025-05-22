<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <title>Portal IRIS - Recepti</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .prescription-list {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        
        .prescription-item {
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
        }
        
        .prescription-item:last-child {
            border-bottom: none;
        }
        
        .prescription-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .prescription-date {
            color: #666;
            font-size: 0.9rem;
        }
        
        .prescription-doctor {
            color: #2c3e50;
            font-weight: 500;
        }
        
        .prescription-details {
            margin-top: 1rem;
        }
        
        .prescription-details p {
            margin-bottom: 0.5rem;
        }
        
        .prescription-status {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 4px;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .status-active {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        
        .status-expired {
            background-color: #ffebee;
            color: #c62828;
        }
        
        .no-prescriptions {
            text-align: center;
            padding: 2rem;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <h2>Kao IRIS</h2>
            <nav>
                <ul>
                    <li><a href="index.php">Domov</a></li>
                    <li><a href="recepti.php">Recepti</a></li>
                    <li><a href="napotnice.php">Napotnice</a></li>
                    <li><a href="pogovori.php">Pogovori</a></li>
                    <li><a href="bolniske.php">Bolniške</a></li>
                </ul>
            </nav>
        </aside>
        
        <main class="content">
            <?php
            session_start();
            if (!isset($_SESSION['user_id'])) {
                header("Location: prijava.php");
                exit();
            }
            
            include 'baza.php';
            
            // Get user's prescriptions
            $user_id = $_SESSION['user_id'];
            $sql = "SELECT r.*, z.ime as zdravnik_ime, z.priimek as zdravnik_priimek 
                    FROM recepti r 
                    LEFT JOIN uporabniki z ON r.zdravnik_id = z.id 
                    WHERE r.uporabnik_id = ? 
                    ORDER BY r.datum_izdaje DESC";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            ?>
            
            <section class="hero">
                <div class="user-info">
                    <p>Prijavljeni ste kot: <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong> | <a href="seja_izbris.php">Odjava</a></p>
                </div>
                <h2>Vaši recepti</h2>
                <p>Pregled vseh vaših receptov.</p>
            </section>
            
            <div class="prescription-list">
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($prescription = $result->fetch_assoc()): ?>
                        <div class="prescription-item">
                            <div class="prescription-header">
                                <div>
                                    <h3><?php echo htmlspecialchars($prescription['zdravilo']); ?></h3>
                                    <p class="prescription-doctor">
                                        Zdravnik: <?php echo htmlspecialchars($prescription['zdravnik_ime'] . ' ' . $prescription['zdravnik_priimek']); ?>
                                    </p>
                                </div>
                                <div>
                                    <span class="prescription-date">
                                        <?php echo date('d.m.Y', strtotime($prescription['datum_izdaje'])); ?>
                                    </span>
                                    <span class="prescription-status <?php echo strtotime($prescription['datum_poteka']) > time() ? 'status-active' : 'status-expired'; ?>">
                                        <?php echo strtotime($prescription['datum_poteka']) > time() ? 'Aktiven' : 'Potekel'; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="prescription-details">
                                <p><strong>Doza:</strong> <?php echo htmlspecialchars($prescription['doza']); ?></p>
                                <p><strong>Navodila:</strong> <?php echo htmlspecialchars($prescription['navodila']); ?></p>
                                <p><strong>Datum poteka:</strong> <?php echo date('d.m.Y', strtotime($prescription['datum_poteka'])); ?></p>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-prescriptions">
                        <p>Nimate še nobenega recepta.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    <?php include 'footer.php'; ?>
</body>
</html> 