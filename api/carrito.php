<?php
// Mostrar errores (solo en local, no en producción)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Conexión a la base de datos
require_once '../config/db.php'; // Asegúrate de que este path sea correcto
require_once '../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

header("Content-Type: application/json");

$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? null;

if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
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
    echo json_encode(['success' => false, 'message' => 'Token inválido: ' . $e->getMessage()]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$accion = $data['accion'] ?? '';
$id_producto = $data['id_producto'] ?? 0;
$cantidad = $data['cantidad'] ?? 1;

if ($accion === 'agregar') {
    // Verifica que el producto existe y tiene stock
    $checkStmt = $conn->prepare("SELECT stock FROM Producto WHERE id_Producto = ?");
    $checkStmt->bind_param("i", $id_producto);
    $checkStmt->execute();
    $stockResult = $checkStmt->get_result();
    if ($stockResult->num_rows === 0 || $stockResult->fetch_assoc()['stock'] <= 0) {
        echo json_encode(['success' => false, 'message' => 'Producto no disponible']);
        exit;
    }

    // Obtener o crear carrito
    $stmt = $conn->prepare("SELECT id_Carrito FROM Carrito WHERE id_Usuario = ?");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $stmt = $conn->prepare("INSERT INTO Carrito (id_Usuario) VALUES (?)");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $id_carrito = $conn->insert_id;
    } else {
        $row = $result->fetch_assoc();
        $id_carrito = $row['id_Carrito'];
    }

    // Insertar o actualizar producto en el carrito
    $stmt = $conn->prepare("INSERT INTO Carrito_Producto (id_Carrito, id_Producto, cantidad)
                            VALUES (?, ?, ?)
                            ON DUPLICATE KEY UPDATE cantidad = cantidad + ?");
    $stmt->bind_param("iiii", $id_carrito, $id_producto, $cantidad, $cantidad);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Producto agregado al carrito']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al agregar al carrito']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Acción no válida']);
}
