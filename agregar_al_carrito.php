<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(["success" => false, "message" => "Debes iniciar sesión."]);
    exit;
}

$id_usuario = $_SESSION['id_usuario'];
$id_producto = $_POST['id_producto'];
$cantidad = $_POST['cantidad'];

// Buscar carrito del usuario o crear uno nuevo
$stmt = $conn->prepare("SELECT id_Carrito FROM Carrito WHERE id_Usuario = ?");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $insert = $conn->prepare("INSERT INTO Carrito (id_Usuario) VALUES (?)");
    $insert->bind_param("i", $id_usuario);
    $insert->execute();
    $id_carrito = $insert->insert_id;
} else {
    $row = $result->fetch_assoc();
    $id_carrito = $row['id_Carrito'];
}

// ve si el carrito tiene el producto
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
