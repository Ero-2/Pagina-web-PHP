<?php
require_once '../config/db.php';

// CABECERAS PARA CORS Y RESPUESTA JSON
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// RESPUESTA A PRE-FLIGHT
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Solo permitir GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido. Use GET.']);
    exit;
}

try {
    // Verificar conexión a la base de datos
    if (!$conn) {
        throw new Exception("Error de conexión a la base de datos", 500);
    }
    
    // Preparar consulta
    $stmt = $conn->prepare("SELECT id_categoria, nombre FROM categoria ORDER BY nombre");
    
    if (!$stmt) {
        throw new Exception("Error al preparar la consulta: " . $conn->error, 500);
    }
    
    // Ejecutar consulta
    if (!$stmt->execute()) {
        throw new Exception("Error al ejecutar la consulta: " . $stmt->error, 500);
    }
    
    $result = $stmt->get_result();
    
    if (!$result) {
        throw new Exception("Error al obtener resultados: " . $stmt->error, 500);
    }
    
    $categorias = [];
    while ($row = $result->fetch_assoc()) {
        $categorias[] = [
            'id_categoria' => (int)$row['id_categoria'],
            'nombre' => htmlspecialchars($row['nombre'], ENT_QUOTES, 'UTF-8')
        ];
    }
    
    // Verificar si hay categorías
    if (empty($categorias)) {
        // No lanzar excepción, solo devolver array vacío con mensaje informativo
        echo json_encode([
            'success' => true,
            'categorias' => [],
            'message' => 'No hay categorías disponibles en este momento'
        ]);
    } else {
        // Devolver categorías encontradas
        echo json_encode([
            'success' => true,
            'categorias' => $categorias,
            'total' => count($categorias)
        ]);
    }
    
    // Log de éxito para debugging
    error_log("Categorías cargadas exitosamente: " . count($categorias) . " categorías");
    
} catch (Exception $e) {
    // Log del error para debugging
    error_log("Error en listar_categorias.php: " . $e->getMessage());
    
    $code = $e->getCode();
    if ($code < 100 || $code > 599) {
        $code = 500;
    }
    
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'code' => $code
    ]);
    
} finally {
    // Cerrar statement y conexión
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}
?>