<?php
$host = "localhost";   // usually localhost
$user = "root";        // your MySQL username
$pass = "";            // your MySQL password
$db   = "volunteer_db";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
