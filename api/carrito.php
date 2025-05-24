<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config/db.php';
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

// Manejo de petición GET → listar carrito
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $conn->prepare("SELECT c.id_Carrito FROM Carrito c WHERE c.id_Usuario = ?");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        echo json_encode(['success' => true, 'carrito' => []]);
        exit;
    }

    $carrito = $res->fetch_assoc();
    $id_carrito = $carrito['id_Carrito'];

    $stmt = $conn->prepare("
        SELECT p.id_Producto, p.nombre_Producto, p.Precio, cp.cantidad, i.url
        FROM Carrito_Producto cp
        JOIN Producto p ON cp.id_Producto = p.id_Producto
        LEFT JOIN Imagen i ON p.id_Producto = i.id_Producto
        WHERE cp.id_Carrito = ?
    ");
    $stmt->bind_param("i", $id_carrito);
    $stmt->execute();
    $productos = $stmt->get_result();

    $carrito_items = [];
    while ($row = $productos->fetch_assoc()) {
        $carrito_items[] = $row;
    }

    echo json_encode(['success' => true, 'carrito' => $carrito_items]);
    exit;
}

// POST para agregar o eliminar producto
$data = json_decode(file_get_contents("php://input"), true);
$accion = $data['accion'] ?? '';
$id_producto = $data['id_producto'] ?? 0;
$cantidad = $data['cantidad'] ?? 1;

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

if ($accion === 'agregar') {
    $checkStmt = $conn->prepare("SELECT stock FROM Producto WHERE id_Producto = ?");
    $checkStmt->bind_param("i", $id_producto);
    $checkStmt->execute();
    $stockResult = $checkStmt->get_result();
    if ($stockResult->num_rows === 0 || $stockResult->fetch_assoc()['stock'] <= 0) {
        echo json_encode(['success' => false, 'message' => 'Producto no disponible']);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO Carrito_Producto (id_Carrito, id_Producto, cantidad)
                            VALUES (?, ?, ?)
                            ON DUPLICATE KEY UPDATE cantidad = cantidad + ?");
    $stmt->bind_param("iiii", $id_carrito, $id_producto, $cantidad, $cantidad);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Producto agregado al carrito']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al agregar al carrito']);
    }

} elseif ($accion === 'eliminar') {
    $stmt = $conn->prepare("DELETE FROM Carrito_Producto WHERE id_Carrito = ? AND id_Producto = ?");
    $stmt->bind_param("ii", $id_carrito, $id_producto);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Producto eliminado del carrito']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar producto']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Acción no válida']);
}
