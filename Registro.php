<?php
require 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'];
    $correo = $_POST['correo'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    
    try {
        $stmt = $conn->prepare("INSERT INTO usuarios (nombre, correo, password_hash) VALUES (?, ?, ?)");
        $stmt->execute([$nombre, $correo, $password]);
        
        header("Location: login.php?registro=exitoso");
        exit();
    } catch(PDOException $e) {
        if ($e->errorInfo[1] == 1062) {
            $error = "Este correo ya está registrado";
        } else {
            $error = "Error al registrar: " . $e->getMessage();
        }
    }
}
?>