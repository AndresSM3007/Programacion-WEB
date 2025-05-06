<?php
// api/login.php

// Permitir solicitudes CORS si es necesario (ajustar según el entorno)
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Incluir archivo de conexión
require_once '../conexion.php'; // Ajustar ruta según la estructura final

// Iniciar sesión para manejar mensajes de error o estado
session_start();

$response = ['success' => false, 'message' => ''];

// Verificar que la solicitud sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Método no permitido
    $response['message'] = 'Método no permitido.';
    echo json_encode($response);
    exit;
}

// Obtener datos del formulario (FormData)
$correo = trim($_POST['correo'] ?? '');
$password = $_POST['password'] ?? '';

// Validaciones básicas
if (empty($correo) || empty($password)) {
    http_response_code(400); // Solicitud incorrecta
    $response['message'] = 'Correo electrónico y contraseña son obligatorios.';
    echo json_encode($response);
    exit;
}

if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    $response['message'] = 'Formato de correo electrónico inválido.';
    echo json_encode($response);
    exit;
}

try {
    // Buscar usuario por correo electrónico
    $stmt = $conn->prepare("SELECT id, nombre, password_hash FROM usuarios WHERE correo = :correo LIMIT 1");
    $stmt->bindParam(':correo', $correo);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verificar contraseña
        if (password_verify($password, $usuario['password_hash'])) {
            // Contraseña correcta - Generar un token simple (para este ejemplo)
            // En una aplicación real, usar JWT u otro método seguro
            $token = bin2hex(random_bytes(32)); // Token simple y temporal

            // Guardar información relevante en la sesión (opcional, si se combina con tokens)
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nombre'] = $usuario['nombre'];
            $_SESSION['usuario_correo'] = $correo;
            $_SESSION['auth_token'] = $token; // Guardar token en sesión también puede ser útil

            http_response_code(200);
            $response['success'] = true;
            $response['message'] = 'Inicio de sesión exitoso.';
            $response['token'] = $token; // Enviar token al cliente
            $response['user'] = [ // Enviar info básica del usuario
                'id' => $usuario['id'],
                'nombre' => $usuario['nombre'],
                'correo' => $correo
            ];

        } else {
            // Contraseña incorrecta
            http_response_code(401); // No autorizado
            $response['message'] = 'Credenciales incorrectas.';
        }
    } else {
        // Usuario no encontrado
        http_response_code(404); // No encontrado
        $response['message'] = 'Usuario no registrado.';
    }

} catch (PDOException $e) {
    http_response_code(500); // Error del servidor
    $response['message'] = 'Error en la base de datos: ' . $e->getMessage();
    // Considerar no mostrar $e->getMessage() en producción
} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = 'Error generando el token: ' . $e->getMessage();
}

// Enviar respuesta JSON
echo json_encode($response);

?>
