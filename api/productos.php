<?php
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../config/jwt.php'; // aquí defines tu clave secreta y función decode_jwt()
require __DIR__ . '/../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// CORS
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Respuesta preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Solo GET permitido aquí
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

// Función para validar JWT desde header Authorization
function verificar_token() {
    $headers = getallheaders();
    $authHeader = '';
    foreach ($headers as $key => $value) {
        if (strtolower($key) === 'authorization') {
            $authHeader = $value;
            break;
        }
    }
    if (!$authHeader || strpos($authHeader, 'Bearer ') !== 0) {
        // Aquí puedes decidir si GET requiere autenticación o no.
        // Para este ejemplo, GET es público y no requiere token.
        return null;
        // Para exigir token, lanzar excepción:
        // throw new Exception("Token JWT requerido", 401);
    }
    $token = trim(str_replace('Bearer ', '', $authHeader));
    try {
        return decode_jwt($token); // función que debe devolver el payload o lanzar excepción
    } catch (Exception $e) {
        throw new Exception('Token JWT inválido: ' . $e->getMessage(), 401);
    }
}

try {
    // Si quisieras autenticar GET, descomenta esta línea:
    // $usuario = verificar_token();

    $id_producto = isset($_GET['id']) ? intval($_GET['id']) : null;

    if ($id_producto && $id_producto > 0) {
        // Obtener producto específico, con imagen principal si existe
        $sql = "SELECT p.id_Producto, p.nombre_Producto, p.Precio, p.stock, p.descripcion,
                       (SELECT url FROM imagen WHERE id_Producto = p.id_Producto LIMIT 1) AS url
                FROM Producto p
                WHERE p.id_Producto = ?
                LIMIT 1";

        $stmt = $conn->prepare($sql);
        if (!$stmt) throw new Exception("Error en la consulta: " . $conn->error, 500);

        $stmt->bind_param("i", $id_producto);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($producto = $result->fetch_assoc()) {
            echo json_encode(['success' => true, 'producto' => $producto]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Producto no encontrado']);
        }

        $stmt->close();
    } else {
        // Listar todos los productos con imagen principal
        $sql = "SELECT p.id_Producto, p.nombre_Producto, p.Precio, p.stock, p.descripcion,
                       (SELECT url FROM imagen WHERE id_Producto = p.id_Producto LIMIT 1) AS url
                FROM Producto p";

        $result = $conn->query($sql);
        if (!$result) throw new Exception("Error en la consulta: " . $conn->error, 500);

        $productos = [];
        while ($row = $result->fetch_assoc()) {
            $productos[] = $row;
        }
        echo json_encode(['success' => true, 'productos' => $productos]);
    }
} catch (Exception $e) {
    error_log("Error en listar_productos.php: " . $e->getMessage());
    $code = $e->getCode();
    if ($code < 100 || $code > 599) {
        $code = 500;
    }
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'code' => $code
    ]);
} finally {
    $conn->close();
}
