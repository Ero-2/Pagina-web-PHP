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
    
    // Normalizar cabeceras (diferentes servidores pueden usar mayúsculas/minúsculas diferentes)
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
    $required_fields = ['metodo', 'detalles'];
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
    $metodo = trim($data['metodo']);
    $detalles = trim($data['detalles']);
    
    // Validaciones adicionales
    $metodos_validos = ['Tarjeta de Crédito', 'Tarjeta de Débito', 'PayPal', 'Transferencia Bancaria'];
    if (!in_array($metodo, $metodos_validos)) {
        throw new Exception("Método de pago no válido. Debe ser uno de: " . implode(', ', $metodos_validos), 400);
    }
    
    if (strlen($detalles) > 500) {
        throw new Exception("Los detalles no pueden tener más de 500 caracteres", 400);
    }
    
    if (strlen($detalles) < 3) {
        throw new Exception("Los detalles deben tener al menos 3 caracteres", 400);
    }
    
    // VERIFICAR SI EL USUARIO EXISTE
    $check_user = $conn->prepare("SELECT id_usuario FROM usuario WHERE id_usuario = ?");
    $check_user->bind_param("i", $id_usuario);
    $check_user->execute();
    
    if ($check_user->get_result()->num_rows === 0) {
        throw new Exception("Usuario no encontrado", 404);
    }
    
    // PREPARAR CONSULTA SQL - Asegúrate de que esta tabla exista
    $stmt = $conn->prepare("INSERT INTO metodo_pago (
        id_usuario, metodo, detalles
    ) VALUES (?, ?, ?)");
    
    if (!$stmt) {
        throw new Exception("Error al preparar consulta: " . $conn->error, 500);
    }
    
    $stmt->bind_param(
        "iss",
        $id_usuario,
        $metodo,
        $detalles
    );
    
    // EJECUTAR Y VERIFICAR
    if ($stmt->execute()) {
        $insert_id = $stmt->insert_id;
        
        // Log de éxito (opcional)
        error_log("Método de pago guardado exitosamente para usuario $id_usuario con ID $insert_id");
        
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'id' => $insert_id,
            'message' => 'Método de pago guardado exitosamente',
            'data' => [
                'id_metodo_pago' => $insert_id,
                'metodo' => $metodo,
                'detalles' => $detalles
            ]
        ]);
    } else {
        throw new Exception('Error al guardar el método de pago: ' . $stmt->error, 500);
    }
    
} catch (Exception $e) {
    // Log del error para debugging
    error_log("Error en guardar_metodo_pago.php: " . $e->getMessage());
    
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
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($check_user)) {
        $check_user->close();
    }
    $conn->close();
}
?>