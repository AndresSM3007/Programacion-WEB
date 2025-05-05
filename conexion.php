<?php
$host = 'mysql.webcindario.com';  // Servidor de miarroba (o podría ser 'localhost')
$dbname = 'apoya-tec';           // Nombre de tu base de datos
$username = 'apoya-tec';         // Usuario de la base de datos
$password = 'AndresSM30$$$';     // Contraseña

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>