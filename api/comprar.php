<?php
require_once '../config/db.php';
require_once '../vendor/autoload.php';

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

// Obtener el carrito
$stmt = $conn->prepare("SELECT id_Carrito FROM Carrito WHERE id_Usuario = ?");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Carrito no encontrado']);
    exit;
}

$id_carrito = $result->fetch_assoc()['id_Carrito'];

// Obtener productos del carrito
$stmt = $conn->prepare("SELECT cp.id_Producto, cp.cantidad, p.Precio FROM Carrito_Producto cp
                        JOIN Producto p ON cp.id_Producto = p.id_Producto
                        WHERE cp.id_Carrito = ?");
$stmt->bind_param("i", $id_carrito);
$stmt->execute();
$productos = $stmt->get_result();

if ($productos->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Carrito vacío']);
    exit;
}

$total = 0;
$items = [];
while ($row = $productos->fetch_assoc()) {
    $subtotal = $row['Precio'] * $row['cantidad'];
    $total += $subtotal;
    $items[] = [
        'id_Producto' => $row['id_Producto'],
        'cantidad' => $row['cantidad'],
        'precio_unitario' => $row['Precio'],
        'subtotal' => $subtotal
    ];
}

// Crear pedido
$stmt = $conn->prepare("INSERT INTO Pedido (id_Usuario, fecha_Pedido, estado_pedido, total) VALUES (?, NOW(), 'Procesando', ?)");
$stmt->bind_param("id", $id_usuario, $total);
$stmt->execute();
$id_pedido = $conn->insert_id;

// Insertar en detalle_pedido
$stmt = $conn->prepare("INSERT INTO Detalle_Pedido (id_Pedido, id_Producto, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)");
foreach ($items as $item) {
    $stmt->bind_param("iiidd", $id_pedido, $item['id_Producto'], $item['cantidad'], $item['precio_unitario'], $item['subtotal']);
    $stmt->execute();
}

// Limpiar el carrito
$conn->prepare("DELETE FROM Carrito_Producto WHERE id_Carrito = ?")->bind_param("i", $id_carrito)->execute();

echo json_encode(['success' => true, 'message' => 'Compra realizada con éxito']);
