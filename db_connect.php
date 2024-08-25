<?php
include 'config.php';

$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}
?>
