<?php

/**
 * Configuración y funciones básicas para JWT (JSON Web Tokens)
 */

// Clave secreta utilizada para firmar los tokens
define('JWT_SECRET_KEY', 'erick123');

// Algoritmo usado para firmar los tokens
define('JWT_ALGORITHM', 'HS256');

// Tiempo de expiración del token (en segundos)
define('JWT_EXPIRE_TIME', 3600); // 1 hora

// Nombre del encabezado donde se espera el token
define('JWT_HEADER_NAME', 'Authorization');


use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

/**
 * Genera un nuevo token JWT para un usuario
 *
 * @param array $payload Datos a incluir en el token (ej: ['id_usuario' => 123])
 * @return string Token codificado
 */
function generate_jwt($payload) {
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
    try {
        $decoded = JWT::decode($token, new Key(JWT_SECRET_KEY, JWT_ALGORITHM));
        return $decoded;
    } catch (\Firebase\JWT\SignatureInvalidException $e) {
        throw new Exception("Firma del token inválida");
    } catch (\Firebase\JWT\ExpiredException $e) {
        throw new Exception("Token expirado");
    } catch (Exception $e) {
        throw new Exception("Token inválido: " . $e->getMessage());
    }
}