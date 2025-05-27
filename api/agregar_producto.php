<?php
require 'config/db.php';
require 'config/jwt.php';
require 'vendor/autoload.php';

// CABECERAS PARA CORS
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// RESPUESTA A PRE-FLIGHT
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

try {
    // LEER EL TOKEN JWT DESDE Authorization
    $headers = getallheaders();
    
    // Normalizar cabeceras
    $authHeader = '';
    foreach ($headers as $key => $value) {
        if (strtolower($key) === 'authorization') {
            $authHeader = $value;
            break;
        }
    }
    
    if (!$authHeader) {
        throw new Exception("Token no proporcionado", 401);
    }
    
    if (strpos($authHeader, 'Bearer ') !== 0) {
        throw new Exception("Formato de token inválido. Use: Bearer <token>", 401);
    }
    
    $token = trim(str_replace('Bearer ', '', $authHeader));
    
    if (empty($token)) {
        throw new Exception("Token vacío", 401);
    }
    
    // DECODIFICAR TOKEN
    try {
        $decoded = decode_jwt($token);
        $id_usuario = $decoded->data->id_usuario;
    } catch (Exception $e) {
        throw new Exception("Token inválido o expirado: " . $e->getMessage(), 401);
    }
    
    // LEER JSON DEL CUERPO DE LA PETICIÓN
    $input = file_get_contents('php://input');
    if (empty($input)) {
        throw new Exception("No se recibieron datos", 400);
    }
    
    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("JSON inválido: " . json_last_error_msg(), 400);
    }
    
    // VALIDAR CAMPOS OBLIGATORIOS
    $required_fields = ['nombre', 'descripcion', 'precio', 'categoria_id'];
    $missing_fields = [];
    
    foreach ($required_fields as $field) {
        if (empty($data[$field]) || trim($data[$field]) === '') {
            $missing_fields[] = $field;
        }
    }
    
    if (!empty($missing_fields)) {
        throw new Exception("Faltan los siguientes campos: " . implode(', ', $missing_fields), 400);
    }
    
    // LIMPIAR Y VALIDAR DATOS
    $nombre = trim($data['nombre']);
    $descripcion = trim($data['descripcion']);
    $precio = floatval($data['precio']);
    $categoria_id = intval($data['categoria_id']);
    
    // Validaciones adicionales
    if (strlen($nombre) > 255) {
        throw new Exception("El nombre no puede tener más de 255 caracteres", 400);
    }
    
    if (strlen($descripcion) > 1000) {
        throw new Exception("La descripción no puede tener más de 1000 caracteres", 400);
    }
    
    if ($precio <= 0) {
        throw new Exception("El precio debe ser mayor a cero", 400);
    }
    
    // VERIFICAR SI LA CATEGORÍA EXISTE
    $stmt_categoria = $conn->prepare("SELECT id_categoria FROM categoria WHERE id_categoria = ?");
    $stmt_categoria->bind_param("i", $categoria_id);
    $stmt_categoria->execute();
    $stmt_categoria->store_result();
    
    if ($stmt_categoria->num_rows === 0) {
        throw new Exception("La categoría seleccionada no existe", 400);
    }
    
    // INSERTAR PRODUCTO EN LA BASE DE DATOS
    $stmt_producto = $conn->prepare("INSERT INTO Producto (nombre_Producto, descripcion, Precio, id_categoria) VALUES (?, ?, ?, ?)");
    $stmt_producto->bind_param("ssdi", $nombre, $descripcion, $precio, $categoria_id);
    
    if (!$stmt_producto->execute()) {
        throw new Exception('Error al guardar el producto: ' . $stmt_producto->error, 500);
    }
    
    $id_producto = $stmt_producto->insert_id;
    
    // Devolver respuesta exitosa
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
    // Log del error
    error_log("Error en agregar_producto.php: " . $e->getMessage());
    
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
    if (isset($stmt_producto)) {
        $stmt_producto->close();
    }
    if (isset($stmt_categoria)) {
        $stmt_categoria->close();
    }
    $conn->close();
}
?>