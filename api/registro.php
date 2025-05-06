<?php
// api/registro.php

// Permitir solicitudes CORS si es necesario
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Incluir archivo de conexión
require_once '../conexion.php'; // Ajustar ruta según la estructura final

// Iniciar sesión para manejar mensajes de error o estado
session_start();

$response = ["success" => false, "message" => ""];

// Verificar que la solicitud sea POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405); // Método no permitido
    $response["message"] = "Método no permitido.";
    echo json_encode($response);
    exit;
}

// Obtener datos del formulario (FormData)
$nombre = trim($_POST["nombre"] ?? "");
$correo = trim($_POST["correo"] ?? "");
$password = $_POST["password"] ?? "";
$confirm_password = $_POST["confirm_password"] ?? "";

// Validaciones robustas
if (empty($nombre) || empty($correo) || empty($password) || empty($confirm_password)) {
    http_response_code(400);
    $response["message"] = "Todos los campos son obligatorios.";
    echo json_encode($response);
    exit;
}

if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    $response["message"] = "El correo electrónico no es válido.";
    echo json_encode($response);
    exit;
}

if (strlen($password) < 6) { // Ejemplo: requerir al menos 6 caracteres
    http_response_code(400);
    $response["message"] = "La contraseña debe tener al menos 6 caracteres.";
    echo json_encode($response);
    exit;
}

if ($password !== $confirm_password) {
    http_response_code(400);
    $response["message"] = "Las contraseñas no coinciden.";
    echo json_encode($response);
    exit;
}

try {
    // Verificar si el correo ya existe
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE correo = :correo");
    $stmt->bindParam(":correo", $correo);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        http_response_code(409); // Conflicto
        $response["message"] = "El correo electrónico ya está registrado.";
        echo json_encode($response);
        exit;
    }

    // Hash de contraseña seguro
    $passwordHash = password_hash($password, PASSWORD_BCRYPT, ["cost" => 12]);

    if ($passwordHash === false) {
        throw new Exception("Error al generar el hash de la contraseña.");
    }

    // Insertar el nuevo usuario
    $stmt = $conn->prepare("INSERT INTO usuarios (nombre, correo, password_hash) VALUES (:nombre, :correo, :password_hash)");
    $stmt->bindParam(":nombre", $nombre);
    $stmt->bindParam(":correo", $correo);
    $stmt->bindParam(":password_hash", $passwordHash);
    
    if ($stmt->execute()) {
        http_response_code(201); // Creado
        $response["success"] = true;
        $response["message"] = "Usuario registrado exitosamente.";
        // Opcional: Devolver el ID del nuevo usuario
        // $response["id"] = $conn->lastInsertId(); 
    } else {
        throw new PDOException("No se pudo registrar el usuario.");
    }

} catch (PDOException $e) {
    http_response_code(500); // Error del servidor
    $response["message"] = "Error en la base de datos: " . $e->getMessage();
    // Considerar no mostrar $e->getMessage() en producción
} catch (Exception $e) {
    http_response_code(500);
    $response["message"] = "Error en el servidor: " . $e->getMessage();
}

// Enviar respuesta JSON
echo json_encode($response);

?>
