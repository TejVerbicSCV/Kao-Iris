<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <title>Portal IRIS - Domov</title>
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
            
            // Get user info
            $sql = "SELECT u.*, z.ime as zdravnik_ime, z.priimek as zdravnik_priimek, z.specializacija, z.telefon as zdravnik_telefon 
                    FROM uporabniki u 
                    LEFT JOIN uporabniki z ON u.zdravnik_id = z.id 
                    WHERE u.id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $user = mysqli_fetch_assoc($result);
            ?>
            
            <section class="hero">
                <div class="user-info">
                    <p>Prijavljeni ste kot: <strong><?php echo htmlspecialchars($user['ime'] . ' ' . $user['priimek']); ?></strong> | <a href="seja_izbris.php">Odjava</a></p>
                </div>
                <h2>Dobrodošli v Kao IRIS</h2>
                <p>Enostaven dostop do vaših zdravstvenih podatkov.</p>
            </section>
            
            <section class="user-details">
                <h2>Vaši osebni podatki</h2>
                <div class="details-grid">
                    <div>
                        <h3>Osnovni podatki</h3>
                        <p><strong>Ime in priimek:</strong> <?php echo htmlspecialchars($user['ime'] . ' ' . $user['priimek']); ?></p>
                        <p><strong>E-pošta:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                        <p><strong>Telefon:</strong> <?php echo htmlspecialchars($user['telefon']); ?></p>
                        <p><strong>Naslov:</strong> <?php echo htmlspecialchars($user['naslov'] ?? 'Ni podatka'); ?></p>
                    </div>
                </div>
            </section>
            
            <section class="doctor-details">
                <h2>Podatki o vašem zdravniku</h2>
                <div class="details-grid">
                    <div>
                        <h3>Osebni zdravnik</h3>
                        <p><strong>Ime in priimek:</strong> <?php echo htmlspecialchars($user['zdravnik_ime'] . ' ' . $user['zdravnik_priimek']); ?></p>
                        <p><strong>Specializacija:</strong> <?php echo htmlspecialchars($user['specializacija']); ?></p>
                        <p><strong>Telefon:</strong> <?php echo htmlspecialchars($user['zdravnik_telefon']); ?></p>
                    </div>
                </div>
            </section>
        </main>
    </div>
    <?php include 'footer.php'; ?>
</body>
</html>
