<?php
$conn = new mysqli('mysql.webcindario.com', 'apoya-tec', 'AndresSM30$$$', 'apoya-tec');

if($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Usa $conn para tus consultas...
?>