<?php
require_once '../config/db.php';
require_once '../config/auth.php'; // Contiene verificar_token()

header("Content-Type: application/json");

// Solo requerir token si NO es un método GET (ej: POST, PUT, DELETE)
$requiere_auth = false;

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    $requiere_auth = true;
}

if ($requiere_auth) {
    verificar_token(); // Esta función valida el token JWT
}

// Obtener ID del producto desde la URL
$id_producto = isset($_GET['id']) ? intval($_GET['id']) : null;

// Si hay un ID válido, devolver un producto específico
if ($id_producto !== null && $id_producto > 0) {
    $sql = "SELECT p.id_Producto, p.nombre_Producto, p.Precio, p.stock, p.descripcion, i.url 
            FROM Producto p 
            LEFT JOIN Imagen i ON p.id_Producto = i.id_Producto 
            WHERE p.id_Producto = ?
            GROUP BY p.id_Producto
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        echo json_encode(["error" => "Error en la consulta: " . $conn->error]);
        exit;
    }

    $stmt->bind_param("i", $id_producto);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        echo json_encode($row);
    } else {
        echo json_encode(["error" => "Producto no encontrado"]);
    }
} else {
    // No se pasó ID o es inválido → devolver todos los productos
    $sql = "SELECT p.id_Producto, p.nombre_Producto, p.Precio, p.stock, p.descripcion, i.url 
            FROM Producto p 
            LEFT JOIN Imagen i ON p.id_Producto = i.id_Producto 
            GROUP BY p.id_Producto";

    $result = $conn->query($sql);

    if (!$result) {
        echo json_encode(["error" => "Error en la consulta: " . $conn->error]);
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