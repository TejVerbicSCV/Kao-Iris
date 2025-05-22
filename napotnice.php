<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <title>Portal IRIS - Napotnice</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .referral-list {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        
        .referral-item {
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
        }
        
        .referral-item:last-child {
            border-bottom: none;
        }
        
        .referral-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .referral-date {
            color: #666;
            font-size: 0.9rem;
        }
        
        .referral-doctor {
            color: #2c3e50;
            font-weight: 500;
        }
        
        .referral-details {
            margin-top: 1rem;
        }
        
        .referral-details p {
            margin-bottom: 0.5rem;
        }
        
        .referral-status {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 4px;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .status-pending {
            background-color: #fff3e0;
            color: #ef6c00;
        }
        
        .status-completed {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        
        .status-cancelled {
            background-color: #ffebee;
            color: #c62828;
        }
        
        .no-referrals {
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
            
            // Get user's referrals
            $user_id = $_SESSION['user_id'];
            $sql = "SELECT n.*, z.ime as zdravnik_ime, z.priimek as zdravnik_priimek 
                    FROM napotnice n 
                    LEFT JOIN uporabniki z ON n.zdravnik_id = z.id 
                    WHERE n.uporabnik_id = ? 
                    ORDER BY n.datum_izdaje DESC";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            ?>
            
            <section class="hero">
                <div class="user-info">
                    <p>Prijavljeni ste kot: <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong> | <a href="seja_izbris.php">Odjava</a></p>
                </div>
                <h2>Vaše napotnice</h2>
                <p>Pregled vseh vaših napotnic.</p>
            </section>
            
            <div class="referral-list">
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($referral = $result->fetch_assoc()): ?>
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