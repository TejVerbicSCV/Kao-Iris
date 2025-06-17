<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <title>Portal IRIS - Recepti</title>
    <link rel="stylesheet" href="style.css">

</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <h2>Kao IRIS</h2>
            <nav>
                <ul>
                    <li><a href="index.php">Dashboard</a></li>
                    <li><a href="recepti.php">Recepti</a></li>
                    <li><a href="napotnice.php">Napotnice</a></li>
                    <li><a href="bolniske.php">Bolniške</a></li>
                </ul>
            </nav>
            <div class="logout-link">
                <a href="seja_izbris.php">Odjava</a>
            </div>
        </aside>
        
        <main class="content">
            <?php
            include 'baza.php';
            $user_id = checkUserAuth();
            
            $sql = "SELECT r.*, z.ime as zdravnik_ime, z.priimek as zdravnik_priimek 
                    FROM recepti r 
                    LEFT JOIN uporabniki z ON r.zdravnik_id = z.id 
                    WHERE r.uporabnik_id = ? 
                    ORDER BY r.datum_izdaje DESC";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            ?>
            
            <section class="hero">
                <div class="user-info">
                    <p>Prijavljeni ste kot: <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong> | <a href="seja_izbris.php">Odjava</a></p>
                </div>
                <h2>Vaši recepti</h2>
                <p>Pregled vseh vaših receptov.</p>
            </section>
            
            <div class="prescription-list">
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php while ($prescription = mysqli_fetch_assoc($result)): ?>
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