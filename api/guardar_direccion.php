<?php
// Configurar manejo de errores para evitar output HTML
require 'vendor/autoload.php';
error_reporting(E_ALL);
ini_set('display_errors', 0); // No mostrar errores en pantalla
ini_set('log_errors', 1);     // Sí registrar errores en log
// Iniciar buffer de salida para capturar cualquier output no deseado
ob_start();
try {
    // CABECERAS PARA CORS (antes que cualquier output)
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    // RESPUESTA A PRE-FLIGHT
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        ob_end_clean(); // Limpiar buffer
        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'CORS preflight OK']);
        exit;
    }
    // Solo permitir POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido', 405);
    }
    // Verificar que los archivos de configuración existen
    if (!file_exists('config/db.php')) {
        throw new Exception('Archivo de configuración de base de datos no encontrado', 500);
    }
    if (!file_exists('config/jwt.php')) {
        throw new Exception('Archivo de configuración JWT no encontrado', 500);
    }
    // Incluir archivos de configuración
    require_once 'config/db.php';
    require_once 'config/jwt.php';
    // Verificar conexión a la base de datos
    if (!isset($conn) || $conn->connect_error) {
        throw new Exception('Error de conexión a la base de datos: ' . (isset($conn) ? $conn->connect_error : 'Variable $conn no definida'), 500);
    }
    // LEER EL TOKEN JWT DESDE Authorization
    $headers = getallheaders();
    if ($headers === false) {
        $headers = [];
        // Fallback para servidores que no soportan getallheaders()
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
                $headers[$header] = $value;
            }
        }
    }
    // Normalizar cabeceras
    $authHeader = '';
    foreach ($headers as $key => $value) {
        if (strtolower($key) === 'authorization') {
            $authHeader = $value;
            break;
        }
    }
    if (!$authHeader) {
        throw new Exception("Token no proporcionado en cabecera Authorization", 401);
    }
    if (strpos($authHeader, 'Bearer ') !== 0) {
        throw new Exception("Formato de token inválido. Debe usar: Bearer <token>", 401);
    }
    $token = trim(str_replace('Bearer ', '', $authHeader));
    if (empty($token)) {
        throw new Exception("Token vacío", 401);
    }
    // DECODIFICAR TOKEN
    if (!function_exists('decode_jwt')) {
        throw new Exception("Función decode_jwt no está disponible. Verifique config/jwt.php", 500);
    }
    try {
        $decoded = decode_jwt($token);
        if (!isset($decoded->data->id_usuario)) {
            throw new Exception("Token no contiene id_usuario válido", 401);
        }
        $id_usuario = $decoded->data->id_usuario;
    } catch (Exception $e) {
        throw new Exception("Token inválido o expirado: " . $e->getMessage(), 401);
    }
    // LEER JSON DEL CUERPO DE LA PETICIÓN
    $input = file_get_contents('php://input');
    if (empty($input)) {
        throw new Exception("No se recibieron datos en el cuerpo de la petición", 400);
    }
    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("JSON inválido: " . json_last_error_msg(), 400);
    }
    // VALIDAR CAMPOS OBLIGATORIOS
    $required_fields = ['calle', 'numero', 'pais', 'estado', 'ciudad', 'codigo_postal'];
    $missing_fields = [];
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || trim($data[$field]) === '') {
            $missing_fields[] = $field;
        }
    }
    if (!empty($missing_fields)) {
        throw new Exception("Faltan los siguientes campos obligatorios: " . implode(', ', $missing_fields), 400);
    }
    // LIMPIAR Y VALIDAR DATOS
    $calle = trim($data['calle']);
    $numero = trim($data['numero']);
    $pais = trim($data['pais']);
    $estado = trim($data['estado']);
    $ciudad = trim($data['ciudad']);
    $codigo_postal = trim($data['codigo_postal']);
    // Validaciones adicionales
    if (strlen($calle) > 255) {
        throw new Exception("La calle no puede tener más de 255 caracteres", 400);
    }
    if (strlen($numero) > 20) {
        throw new Exception("El número no puede tener más de 20 caracteres", 400);
    }
    if (strlen($pais) > 100) {
        throw new Exception("El país no puede tener más de 100 caracteres", 400);
    }
    if (strlen($estado) > 100) {
        throw new Exception("El estado no puede tener más de 100 caracteres", 400);
    }
    if (strlen($ciudad) > 100) {
        throw new Exception("La ciudad no puede tener más de 100 caracteres", 400);
    }
    if (strlen($codigo_postal) > 20) {
        throw new Exception("El código postal no puede tener más de 20 caracteres", 400);
    }
    // VERIFICAR SI EL USUARIO EXISTE
    $check_user = $conn->prepare("SELECT id_usuario FROM usuario WHERE id_usuario = ?");
    if (!$check_user) {
        throw new Exception("Error al preparar consulta de verificación de usuario: " . $conn->error, 500);
    }
    $check_user->bind_param("i", $id_usuario);
    if (!$check_user->execute()) {
        throw new Exception("Error al ejecutar consulta de verificación de usuario: " . $check_user->error, 500);
    }
    if ($check_user->get_result()->num_rows === 0) {
        throw new Exception("Usuario no encontrado con ID: " . $id_usuario, 404);
    }
    // Verificar si la tabla existe
    $table_check = $conn->query("SHOW TABLES LIKE 'direccion_nueva'");
    if ($table_check->num_rows === 0) {
        // Si no existe direccion_nueva, intentar con 'direccion'
        $table_check = $conn->query("SHOW TABLES LIKE 'direccion'");
        if ($table_check->num_rows === 0) {
            throw new Exception("No se encontró la tabla de direcciones. Verifique que existe 'direccion' o 'direccion_nueva'", 500);
        }
        $table_name = 'direccion';
    } else {
        $table_name = 'direccion_nueva';
    }
    // PREPARAR CONSULTA SQL DINÁMICAMENTE
    if ($table_name === 'direccion_nueva') {
        $sql = "INSERT INTO direccion_nueva (
            id_usuario, calle, numero, pais_nombre, estado_nombre, ciudad_nombre, codigo_postal
        ) VALUES (?, ?, ?, ?, ?, ?, ?)";
    } else {
        // Adaptar para tabla 'direccion' (ajusta según tu estructura)
        $sql = "INSERT INTO direccion (
            id_usuario, calle, numero, pais, estado, ciudad, codigo_postal
        ) VALUES (?, ?, ?, ?, ?, ?, ?)";
    }
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error al preparar consulta de inserción: " . $conn->error, 500);
    }
    $stmt->bind_param(
        "issssss",
        $id_usuario,
        $calle,
        $numero,
        $pais,
        $estado,
        $ciudad,
        $codigo_postal
    );
    // EJECUTAR Y VERIFICAR
    if ($stmt->execute()) {
        $insert_id = $stmt->insert_id;
        // Log de éxito
        error_log("Dirección guardada exitosamente para usuario $id_usuario con ID $insert_id");
        // Limpiar buffer antes de enviar respuesta
        ob_end_clean();
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'id' => $insert_id,
            'message' => 'Dirección guardada exitosamente',
            'data' => [
                'id_direccion' => $insert_id,
                'calle' => $calle,
                'numero' => $numero,
                'pais' => $pais,
                'estado' => $estado,
                'ciudad' => $ciudad,
                'codigo_postal' => $codigo_postal,
                'tabla_utilizada' => $table_name
            ]
        ]);
    } else {
        throw new Exception('Error al guardar la dirección: ' . $stmt->error, 500);
    }
} catch (Exception $e) {
    // Limpiar buffer en caso de error
    ob_end_clean();
    // Log del error completo para debugging
    error_log("Error en guardar_direccion.php: " . $e->getMessage() . " | Trace: " . $e->getTraceAsString());
    $code = $e->getCode();
    if ($code < 100 || $code > 599) {
        $code = 500;
    }
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'code' => $code,
        'debug_info' => [
            'file' => basename(__FILE__),
            'line' => $e->getLine(),
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ]);
} catch (Error $e) {
    // Capturar errores fatales de PHP
    ob_end_clean();
    error_log("Error fatal en guardar_direccion.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor: ' . $e->getMessage(),
        'code' => 500,
        'debug_info' => [
            'type' => 'Fatal Error',
            'file' => basename(__FILE__),
            'line' => $e->getLine(),
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ]);
} finally {
    // Cerrar conexiones
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($check_user)) {
        $check_user->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}
?>