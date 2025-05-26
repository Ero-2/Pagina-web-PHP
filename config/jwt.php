<?php

use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

/**
 * Configuración y funciones básicas para JWT (JSON Web Tokens)
 */

// Clave secreta utilizada para firmar los tokens
define('JWT_SECRET_KEY', 'erick123');

// Algoritmo usado para firmar los tokens
define('JWT_ALGORITHM', 'HS256');

// Tiempo de expiración del token (en segundos)
define('JWT_EXPIRE_TIME', 86400); // 24 horas

// Nombre del encabezado donde se espera el token
define('JWT_HEADER_NAME', 'Authorization');

/**
 * Genera un nuevo token JWT para un usuario
 *
 * @param array $data Datos del usuario a incluir (ej: ['id_usuario' => 123])
 * @return string Token codificado
 */
function generate_jwt($data) {
    $issuedAt = time();
    $expire = $issuedAt + JWT_EXPIRE_TIME;

    $payload = [
        'iat'  => $issuedAt,
        'exp'  => $expire,
        'data' => $data
    ];

    return JWT::encode($payload, JWT_SECRET_KEY, JWT_ALGORITHM);
}

/**
 * Decodifica un token JWT y devuelve su contenido
 *
 * @param string $token Token JWT
 * @return object Payload decodificado
 * @throws Exception Si el token es inválido o está expirado
 */
function decode_jwt($token) {
    if (empty($token)) {
        throw new Exception("Token vacío o no proporcionado", 401);
    }

    try {
        return JWT::decode($token, new Key(JWT_SECRET_KEY, JWT_ALGORITHM));
    } catch (\Firebase\JWT\ExpiredException $e) {
        throw new Exception("Token expirado", 401);
    } catch (\Firebase\JWT\SignatureInvalidException $e) {
        throw new Exception("Firma del token inválida", 401);
    } catch (Exception $e) {
        throw new Exception("Token inválido: " . $e->getMessage(), 401);
    }
}
