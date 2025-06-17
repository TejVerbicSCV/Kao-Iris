<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <title>Portal IRIS - Napotnice</title>
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
            
            $sql = "SELECT n.*, z.ime as zdravnik_ime, z.priimek as zdravnik_priimek 
                    FROM napotnice n 
                    LEFT JOIN uporabniki z ON n.zdravnik_id = z.id 
                    WHERE n.uporabnik_id = ? 
                    ORDER BY n.datum_izdaje DESC";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            ?>
            
            <section class="hero">
                <div class="user-info">
                    <p>Prijavljeni ste kot: <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong> | <a href="seja_izbris.php">Odjava</a></p>
                </div>
                <h2>Vaše napotnice</h2>
                <p>Pregled vseh vaših napotnic.</p>
            </section>
            
            <div class="referral-list">
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php while ($referral = mysqli_fetch_assoc($result)): ?>
                        <div class="referral-item">
                            <div class="referral-header">
                                <div>
                                    <h3><?php echo htmlspecialchars($referral['specializacija']); ?></h3>
                                    <p class="referral-doctor">
                                        Zdravnik: <?php echo htmlspecialchars($referral['zdravnik_ime'] . ' ' . $referral['zdravnik_priimek']); ?>
                                    </p>
                                </div>
                                <div>
                                    <span class="referral-date">
                                        <?php echo date('d.m.Y', strtotime($referral['datum_izdaje'])); ?>
                                    </span>
                                    <span class="referral-status status-<?php echo strtolower($referral['status']); ?>">
                                        <?php 
                                        switch($referral['status']) {
                                            case 'pending':
                                                echo 'V čakanju';
                                                break;
                                            case 'completed':
                                                echo 'Opravljeno';
                                                break;
                                            case 'cancelled':
                                                echo 'Preklicano';
                                                break;
                                            default:
                                                echo $referral['status'];
                                        }
                                        ?>
                                    </span>
                                </div>
                            </div>
                            <div class="referral-details">
                                <p><strong>Zadeva:</strong> <?php echo htmlspecialchars($referral['zadeva']); ?></p>
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
                                <?php if ($referral['opombe']): ?>
                                    <p><strong>Opombe:</strong> <?php echo htmlspecialchars($referral['opombe']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-referrals">
                        <p>Nimate še nobene napotnice.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    <?php include 'footer.php'; ?>
</body>
</html> 