<?php
// api/verificar_sesion.php

// Configuración inicial de respuesta y cabeceras
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Inicializar respuesta por defecto
$response = ["autenticado" => false, "message" => "No autorizado"];
http_response_code(401); // Por defecto, no autorizado

// Intentar iniciar sesión PHP
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Suprimir errores de conexión DB para manejarlo aquí
error_reporting(0);
ini_set("display_errors", 0);

try {
    // Intentar incluir la conexión (puede fallar y generar un error que queremos suprimir)
    // La variable $conn puede no estar definida si conexion.php usa die()
    @require_once "../conexion.php";

    // Restaurar manejo de errores normal
    error_reporting(E_ALL);
    ini_set("display_errors", 1);

    // *** Lógica de verificación de sesión ***

    $authHeader = null;
    if (isset($_SERVER["Authorization"])) {
        $authHeader = $_SERVER["Authorization"];
    } elseif (isset($_SERVER["HTTP_AUTHORIZATION"])) {
        $authHeader = $_SERVER["HTTP_AUTHORIZATION"];
    } elseif (function_exists("apache_request_headers")) {
        $requestHeaders = apache_request_headers();
        if (isset($requestHeaders["Authorization"])) {
            $authHeader = $requestHeaders["Authorization"];
        }
    }

    if ($authHeader) {
        list($jwt) = sscanf($authHeader, "Bearer %s");

        if ($jwt) {
            // SIMULACIÓN DE VALIDACIÓN (usando sesión PHP)
            // En un caso real, aquí se validaría el token (JWT o buscando en DB)
            if (isset($_SESSION["auth_token"]) && hash_equals($_SESSION["auth_token"], $jwt)) {
                $response["autenticado"] = true;
                $response["message"] = "Autenticado vía token.";
                if (isset($_SESSION["usuario_id"])) {
                    $response["usuario"] = [
                        "id" => $_SESSION["usuario_id"],
                        "nombre" => $_SESSION["usuario_nombre"] ?? "N/A",
                        "correo" => $_SESSION["usuario_correo"] ?? "N/A"
                    ];
                }
                http_response_code(200);
            } else {
                $response["message"] = "Token inválido o sesión expirada.";
                // No cambiar código HTTP, ya es 401 por defecto
            }
        } else {
            $response["message"] = "Formato de token inválido.";
        }
    } else {
        // Fallback: Verificar si hay una sesión PHP activa (si no se envió token)
        if (isset($_SESSION["usuario_id"])) {
            $response["autenticado"] = true;
            $response["message"] = "Autenticado vía sesión PHP existente.";
            $response["usuario"] = [
                "id" => $_SESSION["usuario_id"],
                "nombre" => $_SESSION["usuario_nombre"] ?? "N/A",
                "correo" => $_SESSION["usuario_correo"] ?? "N/A"
            ];
            http_response_code(200);
        } else {
             $response["message"] = "Encabezado de autorización no encontrado y sin sesión PHP activa.";
        }
    }

} catch (Throwable $e) {
    // Capturar cualquier error/excepción grave durante la lógica
    error_log("Error en verificar_sesion.php: " . $e->getMessage());
    http_response_code(500);
    $response["autenticado"] = false;
    $response["message"] = "Error interno del servidor al verificar la sesión.";
}

// Asegurarse de que siempre se envíe una respuesta JSON válida
echo json_encode($response);
exit; // Terminar explícitamente

?>

