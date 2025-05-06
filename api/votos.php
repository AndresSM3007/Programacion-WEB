<?php
// api/votos.php

// Permitir solicitudes CORS si es necesario
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__ . '/../conexion.php'; // Ajustar ruta según la estructura final
require_once __DIR__ . '/verificar_token.php'; // Script para verificar el token

$response = ["success" => false, "message" => "", "nuevos_votos" => 0];

// Verificar autenticación (¡Obligatorio para votar!)
$usuario_autenticado = verificarToken();
if (!$usuario_autenticado) {
    http_response_code(401);
    $response["message"] = "Acceso no autorizado. Token inválido o ausente.";
    echo json_encode($response);
    exit;
}
$usuario_id = $usuario_autenticado["id"];

// Verificar que la solicitud sea POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405); // Método no permitido
    $response["message"] = "Método no permitido.";
    echo json_encode($response);
    exit;
}

// Obtener datos del cuerpo de la solicitud (esperando JSON)
$data = json_decode(file_get_contents("php://input"), true);

$pregunta_id = filter_var($data["pregunta_id"] ?? null, FILTER_VALIDATE_INT);
$respuesta_id = filter_var($data["respuesta_id"] ?? null, FILTER_VALIDATE_INT);
$tipo_voto_str = strtolower(trim($data["tipo_voto"] ?? "")); // "up" o "down"

// Validaciones
if ((!$pregunta_id && !$respuesta_id) || ($pregunta_id && $respuesta_id)) {
    http_response_code(400);
    $response["message"] = "Debe proporcionar un ID de pregunta O un ID de respuesta, pero no ambos.";
    echo json_encode($response);
    exit;
}

if ($tipo_voto_str !== "up" && $tipo_voto_str !== "down") {
    http_response_code(400);
    $response["message"] = "Tipo de voto inválido (debe ser \"up\" o \"down\").";
    echo json_encode($response);
    exit;
}

$tipo_voto = ($tipo_voto_str === "up") ? 1 : -1;
$tabla_votos = $pregunta_id ? "votos_preguntas" : "votos_respuestas";
$columna_id = $pregunta_id ? "pregunta_id" : "respuesta_id";
$id_item = $pregunta_id ?: $respuesta_id;
$tabla_item = $pregunta_id ? "preguntas" : "respuestas";

try {
    $conn->beginTransaction();

    // 1. Verificar si el usuario ya votó por este item
    $sql_check = "SELECT tipo_voto FROM $tabla_votos WHERE usuario_id = :usuario_id AND $columna_id = :item_id";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bindParam(":usuario_id", $usuario_id, PDO::PARAM_INT);
    $stmt_check->bindParam(":item_id", $id_item, PDO::PARAM_INT);
    $stmt_check->execute();
    $voto_existente = $stmt_check->fetch(PDO::FETCH_ASSOC);

    $cambio_votos = 0;

    if ($voto_existente) {
        // El usuario ya votó
        if ((int)$voto_existente["tipo_voto"] === $tipo_voto) {
            // Está votando lo mismo: quitar el voto
            $sql_delete = "DELETE FROM $tabla_votos WHERE usuario_id = :usuario_id AND $columna_id = :item_id";
            $stmt_delete = $conn->prepare($sql_delete);
            $stmt_delete->bindParam(":usuario_id", $usuario_id, PDO::PARAM_INT);
            $stmt_delete->bindParam(":item_id", $id_item, PDO::PARAM_INT);
            $stmt_delete->execute();
            $cambio_votos = -$tipo_voto; // Se resta el voto que se quitó
            $response["message"] = "Voto eliminado.";
        } else {
            // Está cambiando el voto (ej: de down a up)
            $sql_update = "UPDATE $tabla_votos SET tipo_voto = :tipo_voto WHERE usuario_id = :usuario_id AND $columna_id = :item_id";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bindParam(":tipo_voto", $tipo_voto, PDO::PARAM_INT);
            $stmt_update->bindParam(":usuario_id", $usuario_id, PDO::PARAM_INT);
            $stmt_update->bindParam(":item_id", $id_item, PDO::PARAM_INT);
            $stmt_update->execute();
            $cambio_votos = $tipo_voto * 2; // Se quita el voto anterior (-(-1)=+1) y se suma el nuevo (+1), total +2 (o -2 si es de up a down)
            $response["message"] = "Voto actualizado.";
        }
    } else {
        // El usuario no ha votado: insertar nuevo voto
        $sql_insert = "INSERT INTO $tabla_votos (usuario_id, $columna_id, tipo_voto) VALUES (:usuario_id, :item_id, :tipo_voto)";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bindParam(":usuario_id", $usuario_id, PDO::PARAM_INT);
        $stmt_insert->bindParam(":item_id", $id_item, PDO::PARAM_INT);
        $stmt_insert->bindParam(":tipo_voto", $tipo_voto, PDO::PARAM_INT);
        $stmt_insert->execute();
        $cambio_votos = $tipo_voto; // Se suma el nuevo voto
        $response["message"] = "Voto registrado.";
    }

    // 2. Actualizar el contador de votos en la tabla principal (preguntas o respuestas)
    if ($cambio_votos !== 0) {
        $sql_update_count = "UPDATE $tabla_item SET votos = votos + :cambio_votos WHERE id = :item_id";
        $stmt_update_count = $conn->prepare($sql_update_count);
        $stmt_update_count->bindParam(":cambio_votos", $cambio_votos, PDO::PARAM_INT);
        $stmt_update_count->bindParam(":item_id", $id_item, PDO::PARAM_INT);
        $stmt_update_count->execute();
    }

    // 3. Obtener el nuevo total de votos para devolverlo
    $sql_get_votos = "SELECT votos FROM $tabla_item WHERE id = :item_id";
    $stmt_get_votos = $conn->prepare($sql_get_votos);
    $stmt_get_votos->bindParam(":item_id", $id_item, PDO::PARAM_INT);
    $stmt_get_votos->execute();
    $nuevos_votos = $stmt_get_votos->fetchColumn();

    $conn->commit();

    http_response_code(200);
    $response["success"] = true;
    $response["nuevos_votos"] = (int)$nuevos_votos;

} catch (PDOException $e) {
    $conn->rollBack();
    http_response_code(500);
    $response["message"] = "Error DB: " . $e->getMessage(); // More specific DB error
} catch (Exception $e) {
    $conn->rollBack();
    http_response_code(500);
    $response["message"] = "Error inesperado: " . $e->getMessage();
}

echo json_encode($response);

?>
