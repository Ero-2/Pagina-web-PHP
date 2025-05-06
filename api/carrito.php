<?php
session_start();
require_once '../config/db.php';

// Verifica si se recibe JSON
$datos = json_decode(file_get_contents("php://input"), true);

if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(["success" => false, "message" => "Debes iniciar sesión."]);
    exit;
}

$id_usuario = $_SESSION['id_usuario'];
$accion = $datos['accion'] ?? '';
$id_producto = $datos['id_producto'] ?? 0;
$cantidad = $datos['cantidad'] ?? 1;

// Obtener o crear carrito
$stmt = $conn->prepare("SELECT id_Carrito FROM Carrito WHERE id_Usuario = ?");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $insert = $conn->prepare("INSERT INTO Carrito (id_Usuario) VALUES (?)");
    $insert->bind_param("i", $id_usuario);
    if (!$insert->execute()) {
        echo json_encode(["success" => false, "message" => "Error al crear el carrito"]);
        exit;
    }
    $id_carrito = $insert->insert_id;
} else {
    $row = $result->fetch_assoc();
    $id_carrito = $row['id_Carrito'];
}

if ($accion === 'agregar') {
    // Verificar si ya existe el producto en el carrito
    $check = $conn->prepare("SELECT * FROM Carrito_Producto WHERE id_Carrito = ? AND id_Producto = ?");
    $check->bind_param("ii", $id_carrito, $id_producto);
    $check->execute();
    $resCheck = $check->get_result();

    if ($resCheck->num_rows > 0) {
        // Actualizar cantidad
        $update = $conn->prepare("UPDATE Carrito_Producto SET cantidad = cantidad + ? WHERE id_Carrito = ? AND id_Producto = ?");
        $update->bind_param("iii", $cantidad, $id_carrito, $id_producto);
        $update->execute();
    } else {
        // Insertar nuevo producto
        $insert = $conn->prepare("INSERT INTO Carrito_Producto (id_Carrito, id_Producto, cantidad) VALUES (?, ?, ?)");
        $insert->bind_param("iii", $id_carrito, $id_producto, $cantidad);
        $insert->execute();
    }

    echo json_encode(["success" => true, "message" => "Producto agregado al carrito."]);
    exit;
}

if ($accion === 'eliminar') {
    $delete = $conn->prepare("DELETE FROM Carrito_Producto WHERE id_Carrito = ? AND id_Producto = ?");
    $delete->bind_param("ii", $id_carrito, $id_producto);
    $delete->execute();

    echo json_encode(["success" => true, "message" => "Producto eliminado del carrito."]);
    exit;
}

echo json_encode(["success" => false, "message" => "Acción no válida."]);


