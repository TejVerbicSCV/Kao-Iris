<?php
include 'baza.php';

// Get all conversations with patient and doctor details
$sql = "SELECT p.*, 
        u1.ime as pacient_ime, u1.priimek as pacient_priimek,
        u2.ime as zdravnik_ime, u2.priimek as zdravnik_priimek
        FROM pogovori p 
        JOIN uporabniki u1 ON p.uporabnik_id = u1.id 
        JOIN uporabniki u2 ON p.zdravnik_id = u2.id 
        ORDER BY p.datum_poslano DESC";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "<h2>Seznam vseh pogovorov:</h2>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background-color: #f2f2f2;'>";
    echo "<th>ID</th>";
    echo "<th>Pacient</th>";
    echo "<th>Zdravnik</th>";
    echo "<th>Zadeva</th>";
    echo "<th>Datum</th>";
    echo "<th>Prebrano</th>";
    echo "</tr>";
    
    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['pacient_ime'] . " " . $row['pacient_priimek']) . "</td>";
        echo "<td>" . htmlspecialchars($row['zdravnik_ime'] . " " . $row['zdravnik_priimek']) . "</td>";
        echo "<td>" . htmlspecialchars($row['zadeva']) . "</td>";
        echo "<td>" . date('d.m.Y H:i', strtotime($row['datum_poslano'])) . "</td>";
        echo "<td>" . ($row['prebrano'] ? 'Da' : 'Ne') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Ni najdenih pogovorov.";
}
?> 