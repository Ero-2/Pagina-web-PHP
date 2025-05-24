<?php
require_once '../../vendor/autoload.php'; // Ruta correcta a autoload.php
require_once '../../config/db.php';       // Si necesitas la conexión en tu API

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

function verificar_token() {
    // Obtener encabezados
    $headers = getallheaders();

    // Verificar si existe el encabezado Authorization
    if (!isset($headers['Authorization'])) {
        header("HTTP/1.1 401 Unauthorized");
        echo json_encode(["error" => "Token no proporcionado"]);
        exit;
    }

    $authHeader = $headers['Authorization'];

    // Verificar formato del token (Bearer XXXXX)
    if (!str_starts_with($authHeader, 'Bearer ')) {
        header("HTTP/1.1 401 Unauthorized");
        echo json_encode(["error" => "Formato de token inválido"]);
        exit;
    }

    // Extraer el token
    $token = trim(substr($authHeader, 7));

    // Clave secreta (debe coincidir con la usada al generar el token)
    define('JWT_SECRET_KEY', 'erick123');
    define('JWT_ALGORITHM', 'HS256');

    try {
        // Decodificar el token
        $decoded = JWT::decode($token, new Key(JWT_SECRET_KEY, JWT_ALGORITHM));

        // Opcional: Guardar datos decodificados como constantes o variables globales
        define('AUTH_USER_ID', $decoded->data->id_usuario);
        define('AUTH_NOMBRE', $decoded->data->nombre);
        define('AUTH_TIPO_USUARIO', $decoded->data->tipo_usuario);

    } catch (Exception $e) {
        header("HTTP/1.1 401 Unauthorized");
        echo json_encode([
            "error" => "Token inválido",
            "detalle" => $e->getMessage()
        ]);
        exit;
    }
}

// Ejecutar la verificación inmediatamente al incluir este archivo
verificar_token();
?>