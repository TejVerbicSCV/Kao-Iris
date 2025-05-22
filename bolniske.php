<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <title>Portal IRIS - Bolniške</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .sick-leave-list {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        
        .sick-leave-item {
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
        }
        
        .sick-leave-item:last-child {
            border-bottom: none;
        }
        
        .sick-leave-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .sick-leave-date {
            color: #666;
            font-size: 0.9rem;
        }
        
        .sick-leave-doctor {
            color: #2c3e50;
            font-weight: 500;
        }
        
        .sick-leave-details {
            margin-top: 1rem;
        }
        
        .sick-leave-details p {
            margin-bottom: 0.5rem;
        }
        
        .sick-leave-status {
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
        
        .no-sick-leaves {
            text-align: center;
            padding: 2rem;
            color: #666;
        }
        
        .sick-leave-duration {
            font-size: 1.1rem;
            font-weight: 500;
            color: #2c3e50;
            margin-bottom: 0.5rem;
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
            
            // Get user's sick leaves
            $user_id = $_SESSION['user_id'];
            $sql = "SELECT b.*, z.ime as zdravnik_ime, z.priimek as zdravnik_priimek 
                    FROM bolniske b 
                    LEFT JOIN uporabniki z ON b.zdravnik_id = z.id 
                    WHERE b.uporabnik_id = ? 
                    ORDER BY b.datum_zacetka DESC";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            ?>
            
            <section class="hero">
                <div class="user-info">
                    <p>Prijavljeni ste kot: <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong> | <a href="seja_izbris.php">Odjava</a></p>
                </div>
                <h2>Vaše bolniške</h2>
                <p>Pregled vseh vaših bolniških odsotnosti.</p>
            </section>
            
            <div class="sick-leave-list">
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($sick_leave = $result->fetch_assoc()): ?>
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
                                    $start = new DateTime($sick_leave['datum_zacetka']);
                                    $end = new DateTime($sick_leave['datum_konca']);
                                    $interval = $start->diff($end);
                                    echo $interval->days + 1 . ' dni';
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