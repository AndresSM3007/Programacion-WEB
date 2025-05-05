<?php
session_start();
require 'conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    die("Debes iniciar sesión para subir imágenes");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['imagen'])) {
    $usuario_id = $_SESSION['usuario_id'];
    $pregunta_id = $_POST['pregunta_id'] ?? null;
    $respuesta_id = $_POST['respuesta_id'] ?? null;
    
    $directorio = "uploads/";
    if (!file_exists($directorio)) {
        mkdir($directorio, 0777, true);
    }
    
    $nombre_archivo = uniqid() . '-' . basename($_FILES['imagen']['name']);
    $ruta_archivo = $directorio . $nombre_archivo;
    
    if (move_uploaded_file($_FILES['imagen']['tmp_name'], $ruta_archivo)) {
        $stmt = $conn->prepare("INSERT INTO imagenes (usuario_id, pregunta_id, respuesta_id, nombre_archivo, ruta_archivo) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$usuario_id, $pregunta_id, $respuesta_id, $nombre_archivo, $ruta_archivo]);
        
        echo json_encode(['success' => true, 'ruta' => $ruta_archivo]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error al subir la imagen']);
    }
    exit();
}
?>