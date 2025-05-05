<?php
header('Content-Type: application/json');
require_once 'db_connection.php';

$data = json_decode(file_get_contents('php://input'), true);

$nombre = $data['nombre'] ?? '';
$correo = $data['correo'] ?? '';  // Cambiado de 'email' a 'correo'
$password = $data['password'] ?? '';

if (empty($nombre) || empty($correo) || empty($password)) {
    http_response_code(400);
    echo json_encode(['error' => 'Todos los campos son obligatorios']);
    exit;
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);

try {
    // Ajustado a las columnas de tu tabla
    $stmt = $conn->prepare("INSERT INTO usuarios (nombre, correo, password_hash) VALUES (?, ?, ?)");
    $stmt->execute([$nombre, $correo, $passwordHash]);
    
    http_response_code(201);
    echo json_encode(['message' => 'Usuario registrado exitosamente']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al registrar usuario: ' . $e->getMessage()]);
}
?>