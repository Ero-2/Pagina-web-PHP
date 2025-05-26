<?php
require_once '../config/db.php';
require_once '../config/auth.php';

header("Content-Type: application/json");

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $id_producto = isset($_GET['id']) ? intval($_GET['id']) : null;

        if ($id_producto && $id_producto > 0) {
            // Obtener un producto específico
            $sql = "SELECT p.id_Producto, p.nombre_Producto, p.Precio, p.stock, p.descripcion, i.url 
                    FROM Producto p 
                    LEFT JOIN Imagen i ON p.id_Producto = i.id_Producto 
                    WHERE p.id_Producto = ?
                    GROUP BY p.id_Producto";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id_producto);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                echo json_encode(['success' => true, 'producto' => $row]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Producto no encontrado']);
            }
        } else {
            // Listar todos los productos
            $sql = "SELECT p.id_Producto, p.nombre_Producto, p.Precio, p.stock, p.descripcion, i.url 
                    FROM Producto p 
                    LEFT JOIN Imagen i ON p.id_Producto = i.id_Producto 
                    GROUP BY p.id_Producto";

            $result = $conn->query($sql);

            if ($result && $result->num_rows > 0) {
                $productos = [];
                while ($row = $result->fetch_assoc()) {
                    $productos[] = $row;
                }
                echo json_encode(['success' => true, 'productos' => $productos]);
            } else {
                echo json_encode(['success' => false, 'message' => 'No hay productos disponibles']);
            }
        }
        break;

    case 'POST':
    case 'PUT':
    case 'DELETE':
        verificar_token(); // Solo aquí se exige el token
        echo json_encode(['success' => false, 'message' => 'Funcionalidad no implementada aún']);
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        break;
}
?>

