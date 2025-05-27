<?php
require_once '../config/db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $stmt = $conn->prepare("SELECT id_categoria, nombre FROM categoria ORDER BY nombre");
    $stmt->execute();
    $result = $stmt->get_result();

    $categorias = [];
    while ($row = $result->fetch_assoc()) {
        $categorias[] = $row;
    }

    if (empty($categorias)) {
        throw new Exception("No hay categorías disponibles", 404);
    }

    echo json_encode([
        'success' => true,
        'categorias' => $categorias
    ]);

} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>