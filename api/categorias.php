<?php
require 'config/db.php';

header('Content-Type: application/json');

try {
    // Validar conexión a la base de datos
    if (!isset($conn) || !$conn) {
        throw new Exception('No hay conexión a la base de datos', 500);
    }

    // Consultar categorías
    $stmt = $conn->prepare("SELECT id_categoria, nombre FROM categoria ORDER BY nombre");
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('No hay categorías disponibles', 404);
    }

    $categorias = [];
    while ($row = $result->fetch_assoc()) {
        $categorias[] = $row;
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