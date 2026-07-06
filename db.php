<?php
$host = "localhost";
$db = "skinmate_db";
$user = "root"; // default XAMPP user
$pass = "";     // default XAMPP password is empty

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die(json_encode(['status' => 'error', 'message' => 'Database connection failed']));
}
// $conn->set_charset("utf8");
$conn->set_charset("utf8mb4");
?>