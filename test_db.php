<?php
require_once 'db_connection.php';

try {
    $stmt = $conn->query("SELECT 1");
    echo "¡Conexión exitosa!";
} catch(PDOException $e) {
    echo "Error en la DB: " . $e->getMessage();
}
?>