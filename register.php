// register.php
<?php
header('Content-Type: application/json');
require_once 'db_connection.php'; // Archivo con la conexión a la DB

$data = json_decode(file_get_contents('php://input'), true);

$nombre = $data['nombre'] ?? '';
$email = $data['email'] ?? '';
$password = $data['password'] ?? '';

// Validaciones básicas
if (empty($nombre) || empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode(['error' => 'Todos los campos son obligatorios']);
    exit;
}

// Hashear la contraseña
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

try {
    // Insertar usuario en la base de datos
    $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, password) VALUES (?, ?, ?)");
    $stmt->execute([$nombre, $email, $passwordHash]);
    
    http_response_code(201);
    echo json_encode(['message' => 'Usuario registrado exitosamente']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al registrar usuario: ' . $e->getMessage()]);
}
?>