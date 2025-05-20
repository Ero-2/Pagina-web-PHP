<?php 
session_start(); 
require_once 'config/db.php';

// Verificar si se enviaron los datos del formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $correo = $_POST['correo'];
    $contraseña = $_POST['contraseña'];
    
    // Depuración: Registrar el correo para verificar
    error_log("Intento de inicio de sesión para: " . $correo);
    
    // Consultar el usuario por correo
    $sql = "SELECT * FROM usuario WHERE correo = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $result = $stmt->get_result();
    $usuario = $result->fetch_assoc();
    
    // Depuración: Verificar si se encontró el usuario
    if ($usuario) {
        error_log("Usuario encontrado con ID: " . $usuario['id_Usuario']);
        
        // Depuración: Verificar la contraseña
        $verificacion = password_verify($contraseña, $usuario['contraseña']);
        error_log("Resultado de verificación de contraseña: " . ($verificacion ? "EXITOSO" : "FALLIDO"));
        
        if ($verificacion) {
            // Almacenar información del usuario en la sesión
            $_SESSION['id_usuario'] = $usuario['id_Usuario'];
            $_SESSION['nombre_usuario'] = $usuario['nombre'];
            $_SESSION['id_tipo_de_usuario'] = $usuario['id_tipo_de_usuario'];
            
            // Redirigir según el rol
            header("Location: inicio.php");
            exit;
        } else {
            // Depuración: Mostrar caracteres iniciales del hash para verificar formato
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