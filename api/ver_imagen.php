<?php
// api/ver_imagen.php

require_once '../conexion.php';
require_once 'verificar_token.php';

// Verificar autenticación
$usuario_autenticado = verificarToken();
if (!$usuario_autenticado) {
    http_response_code(403); // Prohibido
    // Opcional: mostrar una imagen placeholder de "acceso denegado"
    // header('Content-Type: image/png');
    // readfile('../assets/images/acceso_denegado.png'); 
    echo json_encode(["success" => false, "message" => "Acceso denegado. Debes iniciar sesión para ver esta imagen."]);
    exit;
}

// Obtener el nombre del archivo de la URL
$nombre_archivo = filter_input(INPUT_GET, 'nombre', FILTER_SANITIZE_STRING);

if (!$nombre_archivo) {
    http_response_code(400); // Solicitud incorrecta
    echo json_encode(["success" => false, "message" => "Nombre de archivo no especificado."]);
    exit;
}

// Validar el nombre del archivo para evitar Path Traversal
if (strpos($nombre_archivo, '/') !== false || strpos($nombre_archivo, '..') !== false) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Nombre de archivo inválido."]);
    exit;
}

// Construir la ruta completa al archivo
$directorio_subidas = __DIR__ . '/../uploads/'; // Usar __DIR__ para ruta absoluta
$ruta_archivo = $directorio_subidas . $nombre_archivo;

// Verificar si el archivo existe y es legible
if (file_exists($ruta_archivo) && is_readable($ruta_archivo)) {
    // Determinar el tipo MIME basado en la extensión
    $extension = strtolower(pathinfo($nombre_archivo, PATHINFO_EXTENSION));
    $mime_type = match ($extension) {
        'jpg', 'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        default => 'application/octet-stream', // Tipo genérico si no se reconoce
    };

    // Servir el archivo
    header('Content-Type: ' . $mime_type);
    header('Content-Length: ' . filesize($ruta_archivo));
    // Opcional: Deshabilitar caché si las imágenes cambian frecuentemente
    // header('Cache-Control: no-cache, must-revalidate');
    // header('Expires: 0');
    readfile($ruta_archivo);
    exit;
} else {
    // Archivo no encontrado
    http_response_code(404);
    echo json_encode(["success" => false, "message" => "Imagen no encontrada."]);
    exit;
}

?>
