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
    SELECT p.*, 
           CASE 
               WHEN p.uporabnik_id = ? THEN u.ime
               ELSE d.ime
           END as sender_ime,
           CASE 
               WHEN p.uporabnik_id = ? THEN u.priimek
               ELSE d.priimek
           END as sender_priimek,
           d.ime as zdravnik_ime,
           d.priimek as zdravnik_priimek,
           CASE 
               WHEN p.uporabnik_id = ? THEN 'pacient'
               ELSE 'zdravnik'
           END as sender_role,
           ROW_NUMBER() OVER (PARTITION BY p.zadeva ORDER BY p.datum_poslano DESC) as rn
    FROM pogovori p 
    JOIN uporabniki u ON p.uporabnik_id = u.id 
    JOIN uporabniki d ON p.zdravnik_id = d.id
    WHERE p.uporabnik_id = ? OR p.zdravnik_id = ?
)
SELECT * FROM LatestMessages WHERE rn = 1
ORDER BY datum_poslano DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iiiii", $user_id, $user_id, $user_id, $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Handle modal state
$show_modal = isset($_GET['show_modal']) && $_GET['show_modal'] === 'true';

// Handle search
$search_text = isset($_GET['search']) ? strtolower($_GET['search']) : '';
$filtered_conversations = $result->fetch_all(MYSQLI_ASSOC);
if (!empty($search_text)) {
    $filtered_conversations = array_filter($filtered_conversations, function($conversation) use ($search_text) {
        $text = strtolower($conversation['zadeva'] . ' ' . 
                          $conversation['zdravnik_ime'] . ' ' . 
                          $conversation['zdravnik_priimek'] . ' ' . 
                          $conversation['sporocilo']);
        return strpos($text, $search_text) !== false;
    });
}
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
            text-decoration: none;
            color: inherit;
            display: block;
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

        .search-box {
            margin: 2rem 0;
            display: flex;
            gap: 1rem;
        }
        
        .search-box input {
            flex: 1;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .search-box input:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .search-box button {
            padding: 0.75rem 1.5rem;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .search-box button:hover {
            background-color: #2980b9;
        }
    </style>
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <h2>Pacient Panel</h2>
            <nav>
                <ul>
                    <li><a href="index.php">Dashboard</a></li>
                    <li><a href="recepti.php">Recepti</a></li>
                    <li><a href="napotnice.php">Napotnice</a></li>
                    <li><a href="pogovori.php">Pogovori</a></li>
                    <li><a href="bolniske.php">Bolniške</a></li>
                    <li><a href="seja_izbris.php">Odjava</a></li>
                </ul>
            </nav>
        </aside>
        
        <main class="content">
            <section class="hero">
                <div class="user-info">
                    <p>Prijavljeni ste kot: <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong> (Pacient) | <a href="seja_izbris.php">Odjava</a></p>
                </div>
                <h2>Vaši pogovori</h2>
                <p>Pregled vseh vaših pogovorov z zdravniki.</p>
            </section>
            
            <a href="nov_pogovor.php" class="new-message-btn">Novo sporočilo</a>
            
            <form method="GET" action="" class="search-box">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search_text); ?>" placeholder="Išči pogovore po zadevi ali zdravniku...">
                <button type="submit">Išči</button>
            </form>
            
            <div class="conversation-list">
                <?php if (!empty($filtered_conversations)): ?>
                    <?php foreach ($filtered_conversations as $conversation): ?>
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
                                        <span class="sender"><?php echo htmlspecialchars($conversation['sender_ime'] . ' ' . $conversation['sender_priimek']); ?> (<?php echo $conversation['sender_role'] === 'zdravnik' ? 'Zdravnik' : 'Pacient'; ?>):</span>
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
                    <?php endforeach; ?>
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