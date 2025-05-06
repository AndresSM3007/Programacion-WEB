<?php
// api/respuestas.php

// Permitir solicitudes CORS si es necesario
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once '../conexion.php'; // Ajustar ruta según la estructura final
require_once 'verificar_token.php'; // Script para verificar el token

$response = ["success" => false, "message" => ""];

// --- Manejo de solicitud GET (Obtener respuestas para una pregunta) ---
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $pregunta_id = filter_input(INPUT_GET, 'pregunta_id', FILTER_VALIDATE_INT);

    if (!$pregunta_id) {
        http_response_code(400);
        $response['message'] = 'ID de pregunta inválido o faltante.';
        echo json_encode($response);
        exit;
    }

    try {
        $sql = "SELECT r.*, 
                       COALESCE(u.nombre, 'Anónimo') as autor, 
                       img.nombre_archivo as imagen_nombre 
                FROM respuestas r 
                LEFT JOIN usuarios u ON r.usuario_id = u.id 
                LEFT JOIN imagenes img ON img.respuesta_id = r.id
                WHERE r.pregunta_id = :pregunta_id 
                ORDER BY r.fecha_creacion ASC";
                
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':pregunta_id', $pregunta_id, PDO::PARAM_INT);
        $stmt->execute();
        
        $respuestas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        http_response_code(200);
        echo json_encode($respuestas); 

    } catch (PDOException $e) {
        http_response_code(500);
        $response['message'] = 'Error en la base de datos al obtener respuestas: ' . $e->getMessage();
        echo json_encode($response);
    }
    exit;
}

// --- Manejo de solicitud POST (Crear nueva respuesta) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario_autenticado = verificarToken();
    $usuario_id = $usuario_autenticado ? $usuario_autenticado["id"] : null;

    if (!$usuario_autenticado) {
        http_response_code(401);
        $response['message'] = 'Debes iniciar sesión para responder.';
        echo json_encode($response);
        exit;
    }

    $pregunta_id = filter_input(INPUT_POST, 'pregunta_id', FILTER_VALIDATE_INT);
    $contenido = trim($_POST['contenido'] ?? '');

    $imagen_subida = false;
    $nombre_archivo_imagen = null;
    $ruta_archivo_imagen_servidor = null; // Ruta en el servidor
    $ruta_relativa_bd = null; // Ruta para la BD y el frontend

    if (isset($_FILES['imagen_respuesta']) && $_FILES['imagen_respuesta']['error'] === UPLOAD_ERR_OK) {
        $directorio_subidas_servidor = __DIR__ . '/../uploads/'; // Ruta absoluta en el servidor
        $directorio_subidas_bd = 'uploads/'; // Ruta relativa para la BD
        
        if (!file_exists($directorio_subidas_servidor)) {
            if (!mkdir($directorio_subidas_servidor, 0777, true)) {
                 http_response_code(500);
                 $response['message'] = 'Error al crear el directorio de subidas.';
                 echo json_encode($response);
                 exit;
            }
        }

        $imagen = $_FILES['imagen_respuesta'];
        $nombre_temporal = $imagen['tmp_name'];
        $nombre_original = basename($imagen['name']);
        $extension = strtolower(pathinfo($nombre_original, PATHINFO_EXTENSION));
        $tipos_permitidos = ['jpg', 'jpeg', 'png', 'gif'];
        $tamano_maximo = 2 * 1024 * 1024; // 2MB

        if (!in_array($extension, $tipos_permitidos)) {
            http_response_code(400);
            $response['message'] = 'Tipo de archivo de imagen no permitido (solo JPG, PNG, GIF).';
            echo json_encode($response);
            exit;
        }
        if ($imagen['size'] > $tamano_maximo) {
            http_response_code(400);
            $response['message'] = 'La imagen excede el tamaño máximo permitido (2MB).';
            echo json_encode($response);
            exit;
        }

        $nombre_archivo_imagen = uniqid('img_resp_', true) . '.' . $extension;
        $ruta_archivo_imagen_servidor = $directorio_subidas_servidor . $nombre_archivo_imagen;
        $ruta_relativa_bd = $directorio_subidas_bd . $nombre_archivo_imagen;

        if (move_uploaded_file($nombre_temporal, $ruta_archivo_imagen_servidor)) {
            $imagen_subida = true;
        } else {
            http_response_code(500);
            $response['message'] = 'Error al mover el archivo de imagen subido.';
            echo json_encode($response);
            exit;
        }
    }

    if (!$pregunta_id) {
        http_response_code(400);
        $response['message'] = 'ID de pregunta inválido o faltante.';
        echo json_encode($response);
        exit;
    }
    if (empty($contenido)) {
        http_response_code(400);
        $response['message'] = 'El contenido de la respuesta no puede estar vacío.';
        echo json_encode($response);
        exit;
    }

    try {
        $stmt_check = $conn->prepare("SELECT id FROM preguntas WHERE id = :pregunta_id");
        $stmt_check->bindParam(':pregunta_id', $pregunta_id, PDO::PARAM_INT);
        $stmt_check->execute();
        if ($stmt_check->rowCount() === 0) {
            http_response_code(404);
            $response['message'] = 'La pregunta a la que intentas responder no existe.';
            if ($imagen_subida && file_exists($ruta_archivo_imagen_servidor)) unlink($ruta_archivo_imagen_servidor);
            echo json_encode($response);
            exit;
        }
    } catch (PDOException $e) {
        http_response_code(500);
        $response['message'] = 'Error verificando la pregunta: ' . $e->getMessage();
        if ($imagen_subida && file_exists($ruta_archivo_imagen_servidor)) unlink($ruta_archivo_imagen_servidor);
        echo json_encode($response);
        exit;
    }

    try {
        $conn->beginTransaction();

        $stmt_respuesta = $conn->prepare("INSERT INTO respuestas (pregunta_id, usuario_id, contenido) VALUES (:pregunta_id, :usuario_id, :contenido)");
        $stmt_respuesta->bindParam(':pregunta_id', $pregunta_id, PDO::PARAM_INT);
        $stmt_respuesta->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
        $stmt_respuesta->bindParam(':contenido', $contenido);
        
        if (!$stmt_respuesta->execute()) {
             throw new PDOException("Error al insertar la respuesta.");
        }
        $respuesta_id = $conn->lastInsertId();

        if ($imagen_subida && $respuesta_id) {
            $stmt_imagen = $conn->prepare("INSERT INTO imagenes (usuario_id, respuesta_id, nombre_archivo, ruta_archivo) VALUES (:usuario_id, :respuesta_id, :nombre_archivo, :ruta_archivo)");
            $stmt_imagen->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
            $stmt_imagen->bindParam(':respuesta_id', $respuesta_id, PDO::PARAM_INT);
            $stmt_imagen->bindParam(':nombre_archivo', $nombre_archivo_imagen);
            $stmt_imagen->bindParam(':ruta_archivo', $ruta_relativa_bd); 
            
            if (!$stmt_imagen->execute()) {
                throw new PDOException("Error al guardar la información de la imagen para la respuesta.");
            }
        }

        $conn->commit();

        http_response_code(201);
        $response['success'] = true;
        $response['message'] = 'Respuesta publicada exitosamente.';
        $response['respuesta_id'] = $respuesta_id;
        if ($nombre_archivo_imagen) {
             $response['imagen_url'] = $ruta_relativa_bd;
        }

    } catch (PDOException $e) {
        $conn->rollBack();
        http_response_code(500);
        $response['message'] = 'Error en la base de datos al guardar la respuesta: ' . $e->getMessage();
        if ($imagen_subida && file_exists($ruta_archivo_imagen_servidor)) {
            unlink($ruta_archivo_imagen_servidor);
        }
    } catch (Exception $e) {
        http_response_code(500);
        $response['message'] = 'Error inesperado: ' . $e->getMessage();
         if ($imagen_subida && file_exists($ruta_archivo_imagen_servidor)) {
            unlink($ruta_archivo_imagen_servidor);
        }
    }

    echo json_encode($response);
    exit;
}

http_response_code(405);
$response['message'] = 'Método no permitido.';
echo json_encode($response);

?>
