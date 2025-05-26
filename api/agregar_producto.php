<?php
require 'vendor/autoload.php';
require 'config/db.php';
require 'config/jwt.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Manejar preflight para CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    // 1. Obtener y validar token JWT
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (empty($authHeader)) {
        throw new Exception("Token no proporcionado", 401);
    }

    if (strpos($authHeader, 'Bearer ') !== 0) {
        throw new Exception("Formato de token inválido", 401);
    }

    $token = str_replace('Bearer ', '', $authHeader);
    $decoded = decode_jwt($token);

    // 2. Verificar rol del usuario
    if (!isset($_SESSION['id_tipo_de_usuario']) || ($_SESSION['id_tipo_de_usuario'] != 1 && $_SESSION['id_tipo_de_usuario'] != 2)) {
        throw new Exception("No tienes permiso para agregar productos", 403);
    }

    // 3. Leer datos del cuerpo
    $input = file_get_contents('php://input');
    if (!$input) {
        throw new Exception("Datos incompletos o vacíos", 400);
    }

    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("JSON inválido: " . json_last_error_msg(), 400);
    }

    // 4. Validar campos obligatorios
    $required_fields = ['nombre', 'descripcion', 'precio', 'categoria_id'];
    $missing = [];

    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || trim($data[$field]) === '') {
            $missing[] = $field;
        }
    }

    if (!empty($missing)) {
        throw new Exception("Faltan campos: " . implode(', ', $missing), 400);
    }

    // 5. Limpiar y validar datos
    $nombre = trim($data['nombre']);
    $descripcion = trim($data['descripcion']);
    $precio = floatval($data['precio']);
    $categoria_id = intval($data['categoria_id']);

    if (strlen($nombre) > 255) {
        throw new Exception("Nombre demasiado largo (máximo 255 caracteres)", 400);
    }

    if (strlen($descripcion) > 1000) {
        throw new Exception("Descripción demasiado larga (máximo 1000 caracteres)", 400);
    }

    if ($precio <= 0) {
        throw new Exception("Precio debe ser mayor a cero", 400);
    }

    // 6. Insertar producto
    $stmt = $conn->prepare("INSERT INTO Producto (nombre_Producto, descripcion, Precio, id_categoria) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssdi", $nombre, $descripcion, $precio, $categoria_id);

    if (!$stmt->execute()) {
        throw new Exception("Error al guardar el producto: " . $stmt->error, 500);
    }

    $id_producto = $stmt->insert_id;

    // 7. Devolver respuesta exitosa
    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Producto agregado exitosamente',
        'producto' => [
            'id_producto' => $id_producto,
            'nombre' => $nombre,
            'descripcion' => $descripcion,
            'precio' => $precio,
            'categoria_id' => $categoria_id
        ]
    ]);

} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'code' => $e->getCode()
    ]);
}
?>