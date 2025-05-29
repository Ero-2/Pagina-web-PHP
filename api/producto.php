<?php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/jwt.php';

header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Solo verificar token si no es una solicitud GET sin ID (listar todos)
$requiere_auth = false;
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    $requiere_auth = true;
}

if ($requiere_auth) {
    // Obtener el token desde el header Authorization
    $headers = getallheaders();
    if (!isset($headers['Authorization'])) {
        http_response_code(401);
        echo json_encode(["error" => "Token no proporcionado"]);
        exit;
    }

    $authHeader = $headers['Authorization'];
    if (strpos($authHeader, 'Bearer ') !== 0) {
        http_response_code(401);
        echo json_encode(["error" => "Formato de token inválido"]);
        exit;
    }

    $token = trim(str_replace('Bearer ', '', $authHeader));

    try {
        $key = "tu_clave_secreta"; // Debe coincidir con la usada para firmar los tokens
        $decoded = JWT::decode($token, new Key($key, 'HS256'));
    } catch (Exception $e) {
        http_response_code(401);
        echo json_encode(["error" => "Token inválido", "detalle" => $e->getMessage()]);
        exit;
    }
}

$id_producto = isset($_GET['id']) ? intval($_GET['id']) : null;

if ($id_producto !== null && $id_producto > 0) {
    // Devolver un producto específico
    $sql = "SELECT p.id_Producto, p.nombre_Producto, p.Precio, p.stock, p.descripcion, i.url 
            FROM Producto p 
            LEFT JOIN Imagen i ON p.id_Producto = i.id_Producto 
            WHERE p.id_Producto = ?
            GROUP BY p.id_Producto
            LIMIT 1";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        http_response_code(500);
        echo json_encode(["error" => "Error en la consulta SQL: " . $conn->error]);
        exit;
    }

    $stmt->bind_param("i", $id_producto);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        echo json_encode($row);
    } else {
        http_response_code(404);
        echo json_encode(["error" => "Producto no encontrado"]);
    }

} else {
    // Devolver todos los productos
    $sql = "SELECT p.id_Producto, p.nombre_Producto, p.Precio, p.stock, p.descripcion, i.url 
            FROM Producto p 
            LEFT JOIN Imagen i ON p.id_Producto = i.id_Producto 
            GROUP BY p.id_Producto";

    $result = $conn->query($sql);

    if (!$result) {
        http_response_code(500);
        echo json_encode(["error" => "Error en la consulta SQL: " . $conn->error]);
        exit;
    }

    if ($result->num_rows > 0) {
        $productos = [];
        while ($row = $result->fetch_assoc()) {
            $productos[] = $row;
        }
        echo json_encode($productos);
    } else {
        echo json_encode(["error" => "No hay productos disponibles"]);
    }
}
?>