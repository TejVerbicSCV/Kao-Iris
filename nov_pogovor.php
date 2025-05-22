<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <title>Portal IRIS - Nov pogovor</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .message-form {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #34495e;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        
        .form-group textarea {
            min-height: 200px;
            resize: vertical;
        }
        
        .form-group input:focus,
        .form-group select:focus,
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
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $user_id = $_SESSION['user_id'];
                $zdravnik_id = $_POST['zdravnik_id'];
                $zadeva = $_POST['zadeva'];
                $sporocilo = $_POST['sporocilo'];
                
                // Validate input
                $errors = [];
                
                if (empty($zdravnik_id)) $errors[] = "Izberite zdravnika.";
                if (empty($zadeva)) $errors[] = "Vnesite zadevo.";
                if (empty($sporocilo)) $errors[] = "Vnesite sporočilo.";
                
                if (empty($errors)) {
                    $sql = "INSERT INTO pogovori (uporabnik_id, zdravnik_id, zadeva, sporocilo, datum_poslano, prebrano) 
                            VALUES (?, ?, ?, ?, NOW(), 0)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("iiss", $user_id, $zdravnik_id, $zadeva, $sporocilo);
                    
                    if ($stmt->execute()) {
                        header("Location: pogovori.php");
                        exit();
                    } else {
                        $errors[] = "Napaka pri pošiljanju sporočila. Poskusite znova.";
                    }
                }
            }
            
            // Get list of doctors
            $sql = "SELECT id, ime, priimek, specializacija FROM uporabniki WHERE vloga_id = 2 ORDER BY priimek, ime";
            $result = $conn->query($sql);
            ?>
            
            <section class="hero">
                <div class="user-info">
                    <p>Prijavljeni ste kot: <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong> | <a href="seja_izbris.php">Odjava</a></p>
                </div>
                <h2>Nov pogovor</h2>
                <p>Pošljite novo sporočilo zdravniku.</p>
            </section>
            
            <?php if (!empty($errors)): ?>
                <div class="error-message">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="message-form">
                <div class="form-group">
                    <label for="zdravnik_id">Izberite zdravnika:</label>
                    <select id="zdravnik_id" name="zdravnik_id" required>
                        <option value="">-- Izberite zdravnika --</option>
                        <?php while ($doctor = $result->fetch_assoc()): ?>
                            <option value="<?php echo $doctor['id']; ?>">
                                <?php echo htmlspecialchars($doctor['priimek'] . ' ' . $doctor['ime'] . ' (' . $doctor['specializacija'] . ')'); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="zadeva">Zadeva:</label>
                    <input type="text" id="zadeva" name="zadeva" required value="<?php echo isset($_POST['zadeva']) ? htmlspecialchars($_POST['zadeva']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="sporocilo">Sporočilo:</label>
                    <textarea id="sporocilo" name="sporocilo" required><?php echo isset($_POST['sporocilo']) ? htmlspecialchars($_POST['sporocilo']) : ''; ?></textarea>
                </div>
                
                <div class="button-group">
                    <button type="submit" class="btn">Pošlji sporočilo</button>
                    <a href="pogovori.php" class="btn btn-secondary">Prekliči</a>
                </div>
            </form>
        </main>
    </div>
    <?php include 'footer.php'; ?>
</body>
</html> 