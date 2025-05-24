<?php 
session_start(); 
require_once 'config/db.php';
require_once 'vendor/autoload.php'; // Cargar Composer

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// Clave secreta (debe estar en config/jwt.php o definida aquí)
define('JWT_SECRET_KEY', 'erick123');
define('JWT_ALGORITHM', 'HS256');
define('JWT_EXPIRE_TIME', 3600); // 1 hora

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $correo = $_POST['correo'];
    $contraseña = $_POST['contraseña'];
    
    error_log("Intento de inicio de sesión para: " . $correo);
    
    $sql = "SELECT * FROM usuario WHERE correo = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $result = $stmt->get_result();
    $usuario = $result->fetch_assoc();

    if ($usuario) {
        error_log("Usuario encontrado con ID: " . $usuario['id_Usuario']);
        
        $verificacion = password_verify($contraseña, $usuario['contraseña']);
        error_log("Resultado de verificación de contraseña: " . ($verificacion ? "EXITOSO" : "FALLIDO"));

        if ($verificacion) {
            // Guardar información básica en sesión
            $_SESSION['id_usuario'] = $usuario['id_Usuario'];
            $_SESSION['nombre_usuario'] = $usuario['nombre'];
            $_SESSION['id_tipo_de_usuario'] = $usuario['id_tipo_de_usuario'];

            // 🚀 Generar token JWT
            $tokenId = base64_encode(random_bytes(50));
            $issuedAt = time();
            $notBefore = $issuedAt + 10;          // Puede usarse después de 10 segundos
            $expire = $issuedAt + JWT_EXPIRE_TIME; // Expira en 1 hora

            $tokenData = [
                'iat' => $issuedAt,
                'nbf' => $notBefore,
                'exp' => $expire,
                'data' => [
                    'id_usuario' => $usuario['id_Usuario'],
                    'nombre' => $usuario['nombre'],
                    'tipo_usuario' => $usuario['id_tipo_de_usuario']
                ]
            ];

            $secretKey = JWT_SECRET_KEY;
            $jwt = JWT::encode($tokenData, $secretKey, JWT_ALGORITHM);

            // Guardar token en sesión para pasarlo al frontend
            $_SESSION['token'] = $jwt;

            header("Location: inicio.php");
            exit;
        } else {
            error_log("Primeros 20 caracteres del hash almacenado: " . substr($usuario['contraseña'], 0, 20));
            header("Location: login.php?error=1");
            exit;
        }
    } else {
        error_log("Usuario no encontrado con el correo: " . $correo);
        header("Location: login.php?error=1");
        exit;
    }
}
?>