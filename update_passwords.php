<?php
require_once 'includes/config.php';

// Function to update user password
function updateUserPassword($email, $password) {
    global $conn;
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE uporabniki SET geslo = ? WHERE email = ?");
    $stmt->bind_param("ss", $hashed_password, $email);
    return $stmt->execute();
}

// Update admin password
updateUserPassword('admin@iris.si', 'admin123');

// Update doctor passwords
$doctors = [
    'janez.novak@zdravstvo.si',
    'maja.kovac@zdravstvo.si',
    'marko.horvat@zdravstvo.si',
    'ana.krajnc@zdravstvo.si',
    'peter.zupan@zdravstvo.si'
];

foreach ($doctors as $email) {
    updateUserPassword($email, 'zdravnik123');
}

// Update test patient password
updateUserPassword('test@example.com', 'test123');

echo "Passwords have been updated successfully!";
?> 