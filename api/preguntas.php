<?php
// api/preguntas.php

// Permitir solicitudes CORS si es necesario
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once '../conexion.php'; // Ajustar ruta según la estructura final
require_once 'verificar_token.php'; // Script para verificar el token

$response = ["success" => false, "message" => ""];

// --- Manejo de solicitud GET (Obtener preguntas) ---
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $tema = $_GET['tema'] ?? null;
    $orden = $_GET['orden'] ?? 'p.fecha_creacion DESC'; // Ordenar por fecha por defecto
    $pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
    $por_pagina = isset($_GET['por_pagina']) ? (int)$_GET['por_pagina'] : 5; // 5 preguntas por página por defecto

    $ordenes_permitidos = ['p.fecha_creacion DESC', 'p.fecha_creacion ASC', 'p.votos DESC', 'p.votos ASC'];
    if (!in_array($orden, $ordenes_permitidos)) {
        $orden = 'p.fecha_creacion DESC';
    }

    $offset = ($pagina - 1) * $por_pagina;

    try {
        $sql_base = "FROM preguntas p LEFT JOIN usuarios u ON p.usuario_id = u.id LEFT JOIN imagenes img ON p.id = img.pregunta_id";
        $where_clause = "";
        $params = [];

        if ($tema && in_array($tema, ['programacion', 'software', 'hardware', 'otro'])) {
            $where_clause = " WHERE p.tema = :tema";
            $params[':tema'] = $tema;
        }

        $sql_count = "SELECT COUNT(DISTINCT p.id) as total " . $sql_base . $where_clause;
        $stmt_count = $conn->prepare($sql_count);
        $stmt_count->execute($params);
        $total_preguntas = $stmt_count->fetch(PDO::FETCH_ASSOC)['total'];

        // MODIFICACIÓN: Se usa SUBSTRING_INDEX para obtener solo el primer nombre de archivo si GROUP_CONCAT devuelve varios.
        // Esto asume que solo nos interesa la primera imagen asociada si hubiera múltiples (aunque el diseño actual parece ser una por pregunta).
        $sql_preguntas = "SELECT p.*, COALESCE(u.nombre, 'Anónimo') as autor, COUNT(DISTINCT r.id) as num_respuestas, 
                       SUBSTRING_INDEX(GROUP_CONCAT(img.nombre_archivo SEPARATOR ','), ',', 1) as imagen_nombre " 
                       // El SEPARATOR es opcional si el default es coma, pero explícito es mejor.
                       . $sql_base 
                       . " LEFT JOIN respuestas r ON p.id = r.pregunta_id "
                       . $where_clause 
                       . " GROUP BY p.id ORDER BY $orden LIMIT :limit OFFSET :offset";
                       
        $stmt_preguntas = $conn->prepare($sql_preguntas);
        if ($tema) {
            $stmt_preguntas->bindParam(':tema', $params[':tema']);
        }
        $stmt_preguntas->bindParam(':limit', $por_pagina, PDO::PARAM_INT);
        $stmt_preguntas->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt_preguntas->execute();
        $preguntas = $stmt_preguntas->fetchAll(PDO::FETCH_ASSOC);

        // No es necesario procesar $imagen_nombre aquí si la consulta SQL ya lo hace.
        // Sin embargo, si se quisiera hacer en PHP, sería algo como:
        // foreach ($preguntas as $key => $pregunta) {
        //     if (!empty($pregunta['imagen_nombre']) && strpos($pregunta['imagen_nombre'], ',') !== false) {
        //         $nombres_imagenes = explode(',', $pregunta['imagen_nombre']);
        //         $preguntas[$key]['imagen_nombre'] = trim($nombres_imagenes[0]);
        //     }
        // }

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'preguntas' => $preguntas,
            'total' => (int)$total_preguntas,
            'pagina' => $pagina,
            'por_pagina' => $por_pagina,
            'total_paginas' => ceil($total_preguntas / $por_pagina)
        ]);

    } catch (PDOException $e) {
        http_response_code(500);
        $response['message'] = 'Error en la base de datos: ' . $e->getMessage();
        echo json_encode($response);
    }
    exit;
}

// --- Manejo de solicitud POST (Crear nueva pregunta) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario_autenticado = verificarToken();
    $usuario_id = $usuario_autenticado ? $usuario_autenticado["id"] : null;

    if (!$usuario_autenticado) {
        http_response_code(401);
        $response['message'] = 'Debes iniciar sesión para crear una pregunta.';
        echo json_encode($response);
        exit;
    }

    $titulo = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $tema = $_POST['tema'] ?? 'otro';

    if (empty($titulo) || empty($descripcion)) {
        http_response_code(400);
        $response['message'] = 'Título y descripción son obligatorios.';
        echo json_encode($response);
        exit;
    }
    if (!in_array($tema, ['programacion', 'software', 'hardware', 'otro'])) {
        $tema = 'otro';
    }

    $imagen_subida = false;
    $nombre_archivo_imagen = null;
    $ruta_archivo_imagen_servidor = null; // Ruta en el servidor
    $ruta_relativa_bd = null; // Ruta para la BD y el frontend

    if (isset($_FILES["imagen"]) && $_FILES["imagen"]["error"] === UPLOAD_ERR_OK) {
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

        $imagen_file = $_FILES['imagen'];
        $nombre_temporal = $imagen_file['tmp_name'];
        $nombre_original = basename($imagen_file['name']);
        $extension = strtolower(pathinfo($nombre_original, PATHINFO_EXTENSION));
        $tipos_permitidos = ['jpg', 'jpeg', 'png', 'gif'];
        $tamano_maximo = 2 * 1024 * 1024; // 2MB

        if (!in_array($extension, $tipos_permitidos)) {
            http_response_code(400);
            $response['message'] = 'Tipo de archivo de imagen no permitido (solo JPG, PNG, GIF).';
            echo json_encode($response);
            exit;
        }
        if ($imagen_file['size'] > $tamano_maximo) {
            http_response_code(400);
            $response['message'] = 'La imagen excede el tamaño máximo permitido (2MB).';
            echo json_encode($response);
            exit;
        }

        $nombre_archivo_imagen = uniqid('img_preg_', true) . '.' . $extension;
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

    try {
        $conn->beginTransaction();

        $stmt_pregunta = $conn->prepare("INSERT INTO preguntas (usuario_id, titulo, descripcion, tema) VALUES (:usuario_id, :titulo, :descripcion, :tema)");
        $stmt_pregunta->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
        $stmt_pregunta->bindParam(':titulo', $titulo);
        $stmt_pregunta->bindParam(':descripcion', $descripcion);
        $stmt_pregunta->bindParam(':tema', $tema);
        
        if (!$stmt_pregunta->execute()) {
             throw new PDOException("Error al insertar la pregunta.");
        }
        
        $pregunta_id = $conn->lastInsertId();

        if ($imagen_subida && $pregunta_id) {
            $stmt_imagen = $conn->prepare("INSERT INTO imagenes (usuario_id, pregunta_id, nombre_archivo, ruta_archivo) VALUES (:usuario_id, :pregunta_id, :nombre_archivo, :ruta_archivo)");
            $stmt_imagen->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
            $stmt_imagen->bindParam(':pregunta_id', $pregunta_id, PDO::PARAM_INT);
            $stmt_imagen->bindParam(':nombre_archivo', $nombre_archivo_imagen);
            $stmt_imagen->bindParam(':ruta_archivo', $ruta_relativa_bd); 
            
            if (!$stmt_imagen->execute()) {
                throw new PDOException("Error al guardar la información de la imagen para la pregunta.");
            }
        }

        $conn->commit();

        http_response_code(201);
        $response['success'] = true;
        $response['message'] = 'Pregunta publicada exitosamente.';
        $response['pregunta_id'] = $pregunta_id;
        if ($nombre_archivo_imagen) {
             $response['imagen_url'] = $ruta_relativa_bd;
        }

    } catch (PDOException $e) {
        $conn->rollBack();
        http_response_code(500);
        $response['message'] = 'Error en la base de datos al publicar la pregunta: ' . $e->getMessage();
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
