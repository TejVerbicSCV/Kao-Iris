<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: prijava.php");
    exit();
}

include 'baza.php';

// Get user's conversations grouped by subject
$user_id = $_SESSION['user_id'];
$sql = "WITH LatestMessages AS (
    SELECT 
        p.*,
        z.ime as zdravnik_ime,
        z.priimek as zdravnik_priimek,
        ROW_NUMBER() OVER (PARTITION BY p.zadeva ORDER BY p.datum_poslano DESC) as rn
    FROM pogovori p 
    LEFT JOIN uporabniki z ON p.zdravnik_id = z.id 
    WHERE p.uporabnik_id = ?
)
SELECT * FROM LatestMessages WHERE rn = 1
ORDER BY datum_poslano DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <title>Portal IRIS - Pogovori</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .conversation-list {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        
        .conversation-item {
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .conversation-item:hover {
            background-color: #f8f9fa;
        }
        
        .conversation-item:last-child {
            border-bottom: none;
        }
        
        .conversation-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .conversation-date {
            color: #666;
            font-size: 0.9rem;
        }
        
        .conversation-doctor {
            color: #2c3e50;
            font-weight: 500;
            margin-top: 0.5rem;
        }
        
        .conversation-preview {
            color: #666;
            font-size: 0.9rem;
            margin-top: 0.5rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 500px;
        }
        
        .conversation-status {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 4px;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .status-unread {
            background-color: #e3f2fd;
            color: #1565c0;
        }
        
        .status-read {
            background-color: #f5f5f5;
            color: #616161;
        }
        
        .no-conversations {
            text-align: center;
            padding: 2rem;
            color: #666;
        }
        
        .new-message-btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-bottom: 1rem;
            transition: background-color 0.3s;
        }
        
        .new-message-btn:hover {
            background-color: #2980b9;
        }

        .conversation-subject {
            font-size: 1.2rem;
            color: #2c3e50;
            font-weight: 500;
        }

        .conversation-meta {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-top: 0.5rem;
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
            <section class="hero">
                <div class="user-info">
                    <p>Prijavljeni ste kot: <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong> | <a href="seja_izbris.php">Odjava</a></p>
                </div>
                <h2>Vaši pogovori</h2>
                <p>Pregled vseh vaših pogovorov z zdravniki.</p>
            </section>
            
            <a href="nov_pogovor.php" class="new-message-btn">Novo sporočilo</a>
            
            <div class="conversation-list">
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($conversation = $result->fetch_assoc()): ?>
                        <a href="pogovor.php?id=<?php echo $conversation['id']; ?>" class="conversation-item">
                            <div class="conversation-header">
                                <div>
                                    <h3 class="conversation-subject"><?php echo htmlspecialchars($conversation['zadeva']); ?></h3>
                                    <div class="conversation-meta">
                                        <p class="conversation-doctor">
                                            Zdravnik: <?php echo htmlspecialchars($conversation['zdravnik_ime'] . ' ' . $conversation['zdravnik_priimek']); ?>
                                        </p>
                                        <span class="conversation-date">
                                            <?php echo date('d.m.Y', strtotime($conversation['datum_poslano'])); ?>
                                        </span>
                                    </div>
                                    <div class="conversation-preview">
                                        <?php echo htmlspecialchars($conversation['sporocilo']); ?>
                                    </div>
                                </div>
                                <div>
                                    <span class="conversation-status <?php echo $conversation['prebrano'] ? 'status-read' : 'status-unread'; ?>">
                                        <?php echo $conversation['prebrano'] ? 'Prebrano' : 'Neprebrano'; ?>
                                    </span>
                                </div>
                            </div>
                        </a>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-conversations">
                        <p>Nimate še nobenega pogovora.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    <?php include 'footer.php'; ?>
</body>
</html> 