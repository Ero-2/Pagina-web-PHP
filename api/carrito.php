<?php
// api/carrito.php - Versión robusta y segura

ini_set('display_errors', 0); // No mostrar errores en pantalla
error_reporting(E_ALL);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// CABECERAS PARA CORS
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    // Obtener token desde header Authorization o sesión como fallback
    $id_usuario = null;
    $headers = getallheaders();

    if (!empty($headers['Authorization']) && str_starts_with($headers['Authorization'], 'Bearer ')) {
        $token = str_replace('Bearer ', '', $headers['Authorization']);
        try {
            $decoded = JWT::decode($token, new Key(JWT_SECRET, JWT_ALGORITHM));
            $id_usuario = $decoded->data->id_usuario;
        } catch (Exception $e) {
            throw new Exception("Token inválido: " . $e->getMessage(), 401);
        }
    } else {
        session_start();
        if (!isset($_SESSION['id_usuario'])) {
            throw new Exception("Usuario no autenticado", 401);
        }
        $id_usuario = $_SESSION['id_usuario'];
    }

    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            $stmt = $conn->prepare("SELECT id_Carrito FROM Carrito WHERE id_Usuario = ?");
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
                SELECT p.id_Producto, p.nombre_Producto, p.Precio, cp.cantidad 
                FROM Carrito_Producto cp
                JOIN Producto p ON cp.id_Producto = p.id_Producto
                WHERE cp.id_Carrito = ?
            ");
            $stmt->bind_param("i", $id_carrito);
            $stmt->execute();
            $productos = $stmt->get_result();

            $items = [];
            while ($row = $productos->fetch_assoc()) {
                $items[] = $row;
            }

            echo json_encode(['success' => true, 'carrito' => $items]);
            break;

        case 'POST':
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
            $data = [];

            if (strpos($contentType, 'multipart/form-data') !== false) {
                $data = $_POST;
            } else {
                $input = file_get_contents("php://input");
                if (!$input) throw new Exception("No se recibieron datos", 400);
                $data = json_decode($input, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception("JSON inválido", 400);
                }
            }

            $accion = $data['accion'] ?? '';
            $id_producto = intval($data['id_producto'] ?? 0);
            $cantidad = max(1, intval($data['cantidad'] ?? 1));

            if (empty($accion) || $id_producto <= 0) {
                throw new Exception("Acción o ID de producto inválido", 400);
            }

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
                    echo json_encode(['success' => false, 'message' => 'Producto sin stock']);
                    exit;
                }

                $stmt = $conn->prepare("INSERT INTO Carrito_Producto (id_Carrito, id_Producto, cantidad)
                                        VALUES (?, ?, ?)
                                        ON DUPLICATE KEY UPDATE cantidad = cantidad + ?");
                $stmt->bind_param("iiii", $id_carrito, $id_producto, $cantidad, $cantidad);

                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Producto agregado al carrito']);
                } else {
                    throw new Exception("Error al agregar al carrito", 500);
                }

            } elseif ($accion === 'eliminar') {
                $stmt = $conn->prepare("DELETE FROM Carrito_Producto WHERE id_Carrito = ? AND id_Producto = ?");
                $stmt->bind_param("ii", $id_carrito, $id_producto);

                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Producto eliminado del carrito']);
                } else {
                    throw new Exception("Error al eliminar del carrito", 500);
                }

            } else {
                throw new Exception("Acción no válida", 400);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            break;
    }

} catch (Exception $e) {
    error_log("Error en carrito.php: " . $e->getMessage());
    $code = $e->getCode();
    http_response_code($code >= 100 && $code <= 599 ? $code : 500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'code' => $code
    ]);
} finally {
    if (isset($stmt)) $stmt->close();
    if (isset($conn)) $conn->close();
}
?>