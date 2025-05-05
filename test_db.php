<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'db_connection.php';

try {
    // Prueba de conexión básica
    $conn->query("SELECT 1");
    
    // Verifica si la tabla usuarios existe
    $tableExists = $conn->query("SHOW TABLES LIKE 'usuarios'")->rowCount() > 0;
    
    if(!$tableExists) {
        die("Error: La tabla 'usuarios' no existe en la base de datos.");
    }
    
    echo "¡Conexión exitosa y tabla verificada!";
    
} catch(PDOException $e) {
    die("Error crítico: " . $e->getMessage());
}
?>