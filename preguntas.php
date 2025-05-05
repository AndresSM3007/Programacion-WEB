<?php
require 'conexion.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $tema = $_GET['tema'] ?? null;
    $orden = $_GET['orden'] ?? 'fecha_creacion DESC';
    
    $sql = "SELECT p.*, u.nombre as autor FROM preguntas p JOIN usuarios u ON p.usuario_id = u.id";
    
    if ($tema && in_array($tema, ['programacion', 'software', 'hardware', 'otro'])) {
        $sql .= " WHERE p.tema = :tema";
    }
    
    $sql .= " ORDER BY $orden";
    
    $stmt = $conn->prepare($sql);
    
    if ($tema) {
        $stmt->bindParam(':tema', $tema);
    }
    
    $stmt->execute();
    $preguntas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($preguntas);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    session_start();
    
    if (!isset($_SESSION['usuario_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'No autorizado']);
        exit();
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $stmt = $conn->prepare("INSERT INTO preguntas (usuario_id, titulo, descripcion, tema) VALUES (?, ?, ?, ?)");
    $stmt->execute([
        $_SESSION['usuario_id'],
        $data['titulo'],
        $data['descripcion'],
        $data['tema']
    ]);
    
    $pregunta_id = $conn->lastInsertId();
    
    echo json_encode(['success' => true, 'pregunta_id' => $pregunta_id]);
}
?>