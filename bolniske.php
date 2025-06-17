<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <title>Portal IRIS - Bolniške</title>
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
            
            $sql = "SELECT b.*, z.ime as zdravnik_ime, z.priimek as zdravnik_priimek 
                    FROM bolniske b 
                    LEFT JOIN uporabniki z ON b.zdravnik_id = z.id 
                    WHERE b.uporabnik_id = ? 
                    ORDER BY b.datum_zacetka DESC";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            ?>
            
            <section class="hero">
                <div class="user-info">
                    <p>Prijavljeni ste kot: <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong> | <a href="seja_izbris.php">Odjava</a></p>
                </div>
                <h2>Vaše bolniške</h2>
                <p>Pregled vseh vaših bolniških odsotnosti.</p>
            </section>
            
            <div class="sick-leave-list">
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php while ($sick_leave = mysqli_fetch_assoc($result)): ?>
                        <div class="sick-leave-item">
                            <div class="sick-leave-header">
                                <div>
                                    <h3><?php echo htmlspecialchars($sick_leave['razlog']); ?></h3>
                                    <p class="sick-leave-doctor">
                                        Zdravnik: <?php echo htmlspecialchars($sick_leave['zdravnik_ime'] . ' ' . $sick_leave['zdravnik_priimek']); ?>
                                    </p>
                                </div>
                                <div>
                                    <span class="sick-leave-date">
                                        <?php echo date('d.m.Y', strtotime($sick_leave['datum_zacetka'])); ?>
                                    </span>
                                    <span class="sick-leave-status <?php echo strtotime($sick_leave['datum_konca']) > time() ? 'status-active' : 'status-expired'; ?>">
                                        <?php echo strtotime($sick_leave['datum_konca']) > time() ? 'Aktivna' : 'Potekla'; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="sick-leave-details">
                                <p class="sick-leave-duration">
                                    <?php 
                                    $start = strtotime($sick_leave['datum_zacetka']);
                                    $end = strtotime($sick_leave['datum_konca']);
                                    $days = floor(($end - $start) / (60 * 60 * 24)) + 1;
                                    echo $days . ' dni';
                                    ?>
                                </p>
                                <p><strong>Začetek:</strong> <?php echo date('d.m.Y', strtotime($sick_leave['datum_zacetka'])); ?></p>
                                <p><strong>Konec:</strong> <?php echo date('d.m.Y', strtotime($sick_leave['datum_konca'])); ?></p>
                                <?php if ($sick_leave['opombe']): ?>
                                    <p><strong>Opombe:</strong> <?php echo htmlspecialchars($sick_leave['opombe']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-sick-leaves">
                        <p>Nimate še nobene bolniške.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    <?php include 'footer.php'; ?>
</body>
</html> 