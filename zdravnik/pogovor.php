<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'zdravnik') {
    header("Location: ../prijava.php");
    exit();
}

include '../baza.php';

if (!isset($_GET['id'])) {
    header("Location: pogovori.php");
    exit();
}

$conversation_id = $_GET['id'];
$doctor_id = $_SESSION['user_id'];
$doctor_name = $_SESSION['user_name'] ?? 'Zdravnik';

// Get all messages for this conversation
$sql = "SELECT p.*, 
               s.ime AS sender_ime,
               s.priimek AS sender_priimek,
               v.naziv AS sender_role
        FROM pogovori p
        LEFT JOIN uporabniki s ON p.posiljatelj_id = s.id
        LEFT JOIN vloge v ON s.vloga_id = v.id
        WHERE p.zadeva = (SELECT zadeva FROM pogovori WHERE id = ?) 
          AND p.zdravnik_id = ?
        ORDER BY p.datum_poslano ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $conversation_id, $doctor_id);
$stmt->execute();
$messages = $stmt->get_result();

// Get conversation subject and patient info
$sql = "SELECT p.zadeva, u.ime as pacient_ime, u.priimek as pacient_priimek, u.id as pacient_id 
        FROM pogovori p 
        JOIN uporabniki u ON p.uporabnik_id = u.id 
        WHERE p.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $conversation_id);
$stmt->execute();
$conversation_info = $stmt->get_result()->fetch_assoc();
$subject = $conversation_info['zadeva'];
$pacient_id = $conversation_info['pacient_id'];

// Mark all messages as read
$sql = "UPDATE pogovori SET prebrano = 1 
        WHERE zadeva = ? AND zdravnik_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $subject, $doctor_id);
$stmt->execute();

// Handle reply
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reply = $_POST['reply'];
    if (!empty($reply)) {
        $sql = "INSERT INTO pogovori (uporabnik_id, zdravnik_id, zadeva, sporocilo, datum_poslano, prebrano, posiljatelj_id) 
                VALUES (?, ?, ?, ?, NOW(), 0, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iissi", $pacient_id, $doctor_id, $subject, $reply, $doctor_id);
        if ($stmt->execute()) {
            header("Location: pogovor.php?id=" . $conversation_id);
            exit();
        } else {
            $error = "Napaka pri pošiljanju odgovora. Poskusite znova.";
        }
    } else {
        $error = "Vnesite sporočilo.";
    }
}
?>
<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <title>Portal IRIS - Pogovor</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .chat-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            height: 600px;
            display: flex;
            flex-direction: column;
        }
        .chat-header {
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
            background-color: #f8f9fa;
        }
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .message {
            max-width: 70%;
            padding: 1rem;
            border-radius: 8px;
            position: relative;
            margin-bottom: 1rem;
        }
        .message-sent {
            align-self: flex-end;
            background-color: #3498db;
            color: white;
            margin-left: auto;
            border-bottom-right-radius: 0;
        }
        .message-received {
            align-self: flex-start;
            background-color: #f1f1f1;
            color: #2c3e50;
            margin-right: auto;
            border-bottom-left-radius: 0;
        }
        .message-meta {
            font-size: 0.8rem;
            margin-top: 0.5rem;
            opacity: 0.8;
        }
        .sender-name {
            font-weight: 500;
        }
        .sender-role {
            font-style: italic;
            opacity: 0.9;
        }
        .message-time {
            opacity: 0.8;
        }
        .reply-form {
            padding: 1.5rem;
            border-top: 1px solid #eee;
            background-color: #f8f9fa;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            min-height: 80px;
            resize: none;
        }
        .form-group textarea:focus {
            outline: none;
            border-color: #3498db;
        }
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.3s;
            text-decoration: none;
        }
        .btn:hover {
            background-color: #2980b9;
        }
        .btn-secondary {
            background-color: #95a5a6;
        }
        .btn-secondary:hover {
            background-color: #7f8c8d;
        }
        .button-group {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }
        .error-message {
            color: #e74c3c;
            margin-bottom: 1rem;
            text-align: center;
        }
        .message-content {
            word-wrap: break-word;
        }
    </style>
</head>
<body>
<div class="container">
    <aside class="sidebar">
        <h2>Zdravnik Panel</h2>
        <nav>
            <ul>
                <li><a href="index.php">Dashboard</a></li>
                <li><a href="pacienti.php">Pacienti</a></li>
                <li><a href="recepti.php">Recepti</a></li>
                <li><a href="napotnice.php">Napotnice</a></li>
                <li><a href="pogovori.php">Pogovori</a></li>
                <li><a href="bolniske.php">Bolniške</a></li>
                <li><a href="../seja_izbris.php">Odjava</a></li>
            </ul>
        </nav>
    </aside>
    <main class="content">
        <section class="hero">
            <div class="user-info">
                <p>Prijavljeni ste kot: <strong><?php echo htmlspecialchars($doctor_name); ?></strong> (Zdravnik) | <a href="../seja_izbris.php">Odjava</a></p>
            </div>
            <h2>Pogovor</h2>
            <p>Pregled pogovora s pacientom <strong><?php echo htmlspecialchars($conversation_info['pacient_ime'] . ' ' . $conversation_info['pacient_priimek']); ?></strong>.</p>
        </section>

        <?php if (isset($error)): ?>
            <div class="error-message">
                <p><?php echo htmlspecialchars($error); ?></p>
            </div>
        <?php endif; ?>

        <div class="chat-container">
            <div class="chat-header">
                <h2><?php echo htmlspecialchars($subject); ?></h2>
            </div>

            <div class="chat-messages">
                <?php while ($message = $messages->fetch_assoc()): ?>
                    <div class="message <?php echo $message['posiljatelj_id'] == $doctor_id ? 'message-sent' : 'message-received'; ?>">
                        <div class="message-content">
                            <?php echo nl2br(htmlspecialchars($message['sporocilo'])); ?>
                        </div>
                        <div class="message-meta">
                            <span class="sender-name"><?php echo htmlspecialchars($message['sender_ime'] ?? 'Neznan'); ?> <?php echo htmlspecialchars($message['sender_priimek'] ?? ''); ?></span>
                            <span class="sender-role">(<?php echo ucfirst(htmlspecialchars($message['sender_role'] ?? 'neznano')); ?>)</span> • 
                            <span class="message-time"><?php echo date('d.m.Y H:i', strtotime($message['datum_poslano'])); ?></span>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <form method="POST" action="" class="reply-form">
                <div class="form-group">
                    <textarea id="reply" name="reply" placeholder="Vnesite vaše sporočilo..." required></textarea>
                </div>

                <div class="button-group">
                    <button type="submit" class="btn">Pošlji</button>
                    <a href="pogovori.php" class="btn btn-secondary">Nazaj na seznam</a>
                </div>
            </form>
        </div>
    </main>
</div>
<?php include '../footer.php'; ?>
<script>
    const chatMessages = document.querySelector('.chat-messages');
    chatMessages.scrollTop = chatMessages.scrollHeight;
</script>
</body>
</html>
