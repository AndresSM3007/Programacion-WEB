<?php
$host = 'mysql.webcindario.com';  // Servidor de Webcindario
$dbname = 'apoya_tec';           // Nombre de tu base de datos
$username = 'tu_usuario';        // Usuario de la base de datos (proporcionado por Webcindario)
$password = 'tu_contraseña';     // Contraseña de la base de datos

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>