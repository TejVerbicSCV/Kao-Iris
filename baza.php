<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "iris_db";

$conn = mysqli_connect($servername, $username, $password, $dbname);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8");

if (!function_exists('checkUserAuth')) {
    function checkUserAuth($required_role = null) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            header("Location: prijava.php");
            exit();
        }
        
        if ($required_role !== null) {
            $user_id = $_SESSION['user_id'];
            $role = getUserRole($user_id);
            
            if ($role !== $required_role) {
                header("Location: index.php");
                exit();
            }
        }
        
        return $_SESSION['user_id'];
    }
}

if (!function_exists('getUserRole')) {
    function getUserRole($user_id) {
        global $conn;
        $sql = "SELECT v.naziv FROM uporabniki u 
                JOIN vloge v ON u.vloga_id = v.id 
                WHERE u.id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        return $row ? $row['naziv'] : null;
    }
}
?>





