<?php
$host = 'localhost'; // Prueba también con 'mysql.webcindario.com'
$dbname = 'usuario_apoya-tec'; // El nombre REAL de tu BD
$username = 'usuario_apoya-tec'; // Usuario COMPLETO
$password = 'AndresSM30$$$'; // Contraseña exacta

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "¡Conexión exitosa!"; // Mensaje de prueba
} catch(PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>