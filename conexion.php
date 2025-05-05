<?php
$host = 'mysql.webcindario.com'; // Prueba también con 'localhost'
$dbname = 'apoya-tec';
$username = 'apoya-tec';
// Para contraseñas con $, usa comillas simples
$password = 'AndresSM30$$$'; 

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Verificación adicional
    $conn->query("SET NAMES 'utf8mb4'");
    $conn->query("SET CHARACTER SET utf8mb4");
    
} catch(PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>