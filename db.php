<?php
$host = "127.0.0.1";
$user = "root";
$pass = "@Pesantren1";
$db   = "pkkm";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}
?>
