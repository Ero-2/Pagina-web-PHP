<?php
require_once '../config/db.php';
require_once '../vendor/autoload.php';
require 'vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

header("Content-Type: application/json");

// Validar token
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? '';
if (!str_starts_with($authHeader, 'Bearer ')) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Token no proporcionado']);
    exit;
}
$token = str_replace('Bearer ', '', $authHeader);
try {
    $decoded = JWT::decode($token, new Key('erick123', 'HS256'));
    $id_usuario = $decoded->data->id_usuario;
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Token inválido']);
    exit;
}

// Obtener compras anteriores
$stmt = $conn->prepare("SELECT p.id_Pedido, p.fecha_Pedido, p.total, p.estado_pedido,
       dp.id_Producto, prod.nombre_Producto, dp.cantidad, dp.precio_unitario, dp.subtotal
FROM Pedido p
JOIN Detalle_Pedido dp ON p.id_Pedido = dp.id_Pedido
JOIN Producto prod ON dp.id_Producto = prod.id_Producto
WHERE p.id_Usuario = ?
ORDER BY p.fecha_Pedido DESC");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result = $stmt->get_result();

$compras = [];
while ($row = $result->fetch_assoc()) {
    $id_pedido = $row['id_Pedido'];
    if (!isset($compras[$id_pedido])) {
        $compras[$id_pedido] = [
            'fecha' => $row['fecha_Pedido'],
            'estado' => $row['estado_pedido'],
            'total' => $row['total'],
            'productos' => []
        ];
    }
    $compras[$id_pedido]['productos'][] = [
        'nombre' => $row['nombre_Producto'],
        'cantidad' => $row['cantidad'],
        'precio_unitario' => $row['precio_unitario'],
        'subtotal' => $row['subtotal']
    ];
}

echo json_encode(['success' => true, 'compras' => array_values($compras)]);
