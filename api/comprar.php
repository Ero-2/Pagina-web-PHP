<?php
session_start();
require_once '../config/db.php';

// Verifica si el usuario está autenticado
if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(["success" => false, "message" => "Debes iniciar sesión."]);
    exit;
}

$id_usuario = $_SESSION['id_usuario'];

// Obtener todos los productos del carrito
$sql_carrito = "SELECT cp.id_Carrito_Producto, p.id_Producto, p.nombre_Producto, p.Precio, cp.cantidad
                FROM Carrito_Producto cp
                JOIN Producto p ON cp.id_Producto = p.id_Producto
                JOIN Carrito c ON cp.id_Carrito = c.id_Carrito
                WHERE c.id_Usuario = ?";
$stmt_carrito = $conn->prepare($sql_carrito);
$stmt_carrito->bind_param("i", $id_usuario);
$stmt_carrito->execute();
$result_productos = $stmt_carrito->get_result();

// Calcular el total general del pedido
$total_general = 0;

while ($producto = $result_productos->fetch_assoc()) {
    $subtotal = $producto['Precio'] * $producto['cantidad'];
    $total_general += $subtotal;
}

// Insertar el nuevo pedido en la tabla Pedido
$sql_pedido = "INSERT INTO Pedido (id_Usuario, fecha_Pedido, estado_pedido, total)
               VALUES (?, NOW(), 'pendiente', ?)";
$stmt_pedido = $conn->prepare($sql_pedido);
$stmt_pedido->bind_param("di", $id_usuario, $total_general);

if (!$stmt_pedido->execute()) {
    echo json_encode(["success" => false, "message" => "Error al crear el pedido."]);
    exit;
}

$id_pedido = $stmt_pedido->insert_id;

// Mover productos del carrito a Detalle_Pedido
$result_productos->data_seek(0); // Reiniciar el cursor del resultado

while ($producto = $result_productos->fetch_assoc()) {
    $subtotal = $producto['Precio'] * $producto['cantidad'];

    // Insertar detalle del pedido
    $sql_detalle = "INSERT INTO Detalle_Pedido (id_Pedido, id_Producto, cantidad, precio_unitario, subtotal)
                    VALUES (?, ?, ?, ?, ?)";
    $stmt_detalle = $conn->prepare($sql_detalle);
    $stmt_detalle->bind_param("iiidd", $id_pedido, $producto['id_Producto'], $producto['cantidad'], $producto['Precio'], $subtotal);

    if (!$stmt_detalle->execute()) {
        echo json_encode(["success" => false, "message" => "Error al insertar detalles del pedido."]);
        exit;
    }
}

// Vaciar el carrito
$sql_vaciar_carrito = "DELETE FROM Carrito_Producto WHERE id_Carrito IN (
                       SELECT id_Carrito FROM Carrito WHERE id_Usuario = ?
                     )";
$stmt_vaciar_carrito = $conn->prepare($sql_vaciar_carrito);
$stmt_vaciar_carrito->bind_param("i", $id_usuario);

if (!$stmt_vaciar_carrito->execute()) {
    echo json_encode(["success" => false, "message" => "Error al vaciar el carrito."]);
    exit;
}

echo json_encode(["success" => true, "message" => "Compra realizada exitosamente.", "pedido_id" => $id_pedido]);
?>