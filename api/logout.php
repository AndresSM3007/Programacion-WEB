<?php
// api/logout.php

// Permitir solicitudes CORS si es necesario
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

session_start();

$response = ["success" => false, "message" => ""];

// Verificar que la solicitud sea POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405); // Método no permitido
    $response["message"] = "Método no permitido.";
    echo json_encode($response);
    exit;
}

// Aquí podrías añadir lógica para invalidar el token si lo estás almacenando
// Por ejemplo, si guardas tokens válidos en una tabla de la BD, podrías marcarlo como inválido.
// Para el token simple en sesión, simplemente destruir la sesión es suficiente.

// Destruir todas las variables de sesión.
$_SESSION = array();

// Si se desea destruir la sesión completamente, borra también la cookie de sesión.
// Nota: ¡Esto destruirá la sesión, y no solo los datos de la sesión!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), 
, time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finalmente, destruir la sesión.
if (session_destroy()) {
    http_response_code(200);
    $response["success"] = true;
    $response["message"] = "Sesión cerrada exitosamente.";
} else {
    http_response_code(500);
    $response["message"] = "Error al cerrar la sesión.";
}

echo json_encode($response);

?>
