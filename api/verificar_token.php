<?php
// api/verificar_token.php

// Este script es un helper para verificar el token de autorización.
// No debe ser llamado directamente por el cliente, sino incluido por otros scripts de API.

if (!function_exists('verificarToken')) {
    function verificarToken() {
        // Incluir conexión a BD si es necesario para validar el token contra la BD
        // require_once __DIR__ . '/../conexion.php'; 
        
        // Iniciar sesión si se usa para almacenar parte de la info o el token mismo
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $authHeader = null;
        if (isset($_SERVER['Authorization'])) {
            $authHeader = $_SERVER['Authorization'];
        } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) { // Nginx o FastCGI
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            if (isset($requestHeaders['Authorization'])) {
                $authHeader = $requestHeaders['Authorization'];
            }
        }

        if ($authHeader) {
            list($jwt) = sscanf($authHeader, 'Bearer %s');

            if ($jwt) {
                // *** SIMULACIÓN DE VALIDACIÓN (PARA ESTE EJEMPLO) ***
                // Compara el token recibido con el guardado en la sesión PHP.
                // En una implementación real con JWT, aquí iría la decodificación y validación.
                if (isset($_SESSION['auth_token']) && hash_equals($_SESSION['auth_token'], $jwt) && isset($_SESSION['usuario_id'])) {
                    // Token válido y coincide con la sesión
                    return [
                        'id' => $_SESSION['usuario_id'],
                        'nombre' => $_SESSION['usuario_nombre'] ?? null,
                        'correo' => $_SESSION['usuario_correo'] ?? null
                    ]; // Devuelve la información del usuario autenticado
                } else {
                    // Token inválido o no coincide
                    return false;
                }
                // *** FIN SIMULACIÓN ***

                /*
                // EJEMPLO CON JWT REAL (requiere librería como firebase/php-jwt)
                try {
                    // $secret_key = 'tu_clave_secreta'; // Debe ser la misma usada para firmar
                    // $decoded = JWT::decode($jwt, new Key($secret_key, 'HS256'));
                    // return (array)$decoded->data; // Devuelve los datos del payload del token
                } catch (Exception $e) {
                    // Token inválido (expirado, firma incorrecta, etc.)
                    error_log('Error de validación de token: ' . $e->getMessage());
                    return false;
                }
                */
            } else {
                // Formato de token inválido
                error_log('Formato de token inválido en encabezado Authorization.');
                return false;
            }
        } else {
            // No se proporcionó encabezado de autorización
            // Podríamos verificar si hay una sesión PHP activa como fallback
             if (isset($_SESSION['usuario_id'])) {
                 return [
                        'id' => $_SESSION['usuario_id'],
                        'nombre' => $_SESSION['usuario_nombre'] ?? null,
                        'correo' => $_SESSION['usuario_correo'] ?? null
                    ];
             } 
            error_log('Encabezado Authorization no encontrado.');
            return false;
        }
    }
}
?>
