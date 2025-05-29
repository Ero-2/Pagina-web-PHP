<?php
require __DIR__ . '/../config/db.php';

// CABECERAS PARA CORS
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// RESPUESTA A PRE-FLIGHT
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Solo permitir GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

try {
    $id_producto = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if ($id_producto <= 0) {
        throw new Exception("ID de producto inválido", 400);
    }

    // Verificar que el producto exista
    $stmt_check = $conn->prepare("SELECT id_Producto, nombre_Producto, Precio, descripcion, stock, id_categoria, url FROM Producto WHERE id_Producto = ?");
    $stmt_check->bind_param("i", $id_producto);
    $stmt_check->execute();
    $result = $stmt_check->get_result();
    $producto = $result->fetch_assoc();

    if (!$producto) {
        throw new Exception("Producto no encontrado", 404);
    }

    // Devolver respuesta exitosa
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'producto' => $producto
    ]);

} catch (Exception $e) {
    error_log("Error en productos.php: " . $e->getMessage());
    $code = $e->getCode();
    if ($code < 100 || $code > 599) $code = 500;
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'code' => $code
    ]);
} finally {
    $conn->close();
}
?>