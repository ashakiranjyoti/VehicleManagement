<?php
require_once __DIR__ . '/../includes/auth.php';
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'vehicle_management';

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>