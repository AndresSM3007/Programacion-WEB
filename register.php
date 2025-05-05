<?php
// Activamos todos los errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

require 'db_connection.php';

// Obtenemos los datos JSON
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if(json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos JSON inválidos']);
    exit;
}

$nombre = trim($data['nombre'] ?? '');
$correo = trim($data['correo'] ?? '');
$password = $data['password'] ?? '';

// Validaciones robustas
if(empty($nombre) || empty($correo) || empty($password)) {
    http_response_code(400);
    echo json_encode(['error' => 'Todos los campos son obligatorios']);
    exit;
}

if(!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'El correo electrónico no es válido']);
    exit;
}

try {
    // Verificamos si el correo ya existe
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE correo = ?");
    $stmt->execute([$correo]);
    
    if($stmt->rowCount() > 0) {
        http_response_code(409);
        echo json_encode(['error' => 'El correo ya está registrado']);
        exit;
    }

    // Hash de contraseña seguro
    $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    
    if($passwordHash === false) {
        throw new Exception('Error al generar el hash de la contraseña');
    }

    // Insertamos el nuevo usuario
    $stmt = $conn->prepare("INSERT INTO usuarios (nombre, correo, password_hash) VALUES (?, ?, ?)");
    $stmt->execute([$nombre, $correo, $passwordHash]);
    
    http_response_code(201);
    echo json_encode(['message' => 'Usuario registrado exitosamente', 'id' => $conn->lastInsertId()]);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error en la base de datos: ' . $e->getMessage()]);
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>