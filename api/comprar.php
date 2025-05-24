<?php
require_once '../config/db.php';
require_once '../config/jwt.php'; // el archivo que mostraste

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$headers = getallheaders();
$token = isset($headers[JWT_HEADER_NAME]) ? str_replace('Bearer ', '', $headers[JWT_HEADER_NAME]) : '';

try {
    $decoded = decode_jwt($token);
    $id_usuario = $decoded->id_usuario ?? null;

    if (!$id_usuario) {
        throw new Exception('ID de usuario no encontrado en el token');
    }

    // Obtener productos del carrito
    $stmt = $conn->prepare("SELECT cp.id_Producto, cp.cantidad, p.Precio
                            FROM Carrito_Producto cp
                            JOIN Carrito c ON cp.id_Carrito = c.id_Carrito
                            JOIN Producto p ON p.id_Producto = cp.id_Producto
                            WHERE c.id_Usuario = ?");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'El carrito está vacío']);
        exit;
    }

    // Crear pedido
    $conn->begin_transaction();
    $conn->query("INSERT INTO Pedido (id_Usuario, fecha_Pedido) VALUES ($id_usuario, NOW())");
    $id_pedido = $conn->insert_id;

    $stmt_detalle = $conn->prepare("INSERT INTO Detalle_Pedido (id_Pedido, id_Producto, cantidad, subtotal) VALUES (?, ?, ?, ?)");

    while ($row = $result->fetch_assoc()) {
        $subtotal = $row['Precio'] * $row['cantidad'];
        $stmt_detalle->bind_param("iiid", $id_pedido, $row['id_Producto'], $row['cantidad'], $subtotal);
        $stmt_detalle->execute();
    }

    // Vaciar el carrito
    $stmt_clear = $conn->prepare("DELETE cp FROM Carrito_Producto cp
                                  JOIN Carrito c ON cp.id_Carrito = c.id_Carrito
                                  WHERE c.id_Usuario = ?");
    $stmt_clear->bind_param("i", $id_usuario);
    $stmt_clear->execute();

    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'Compra finalizada correctamente']);
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
