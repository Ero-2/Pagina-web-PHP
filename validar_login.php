<?php
session_start();
require_once 'config/db.php';

// Verificar si se enviaron los datos del formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $correo = $_POST['correo'];
    $contraseña = $_POST['contraseña'];

    // Consultar el usuario por correo
    $sql = "SELECT * FROM Usuario WHERE correo = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $result = $stmt->get_result();
    $usuario = $result->fetch_assoc();

    // Verificar si el usuario existe y si la contraseña es correcta
    if ($usuario && password_verify($contraseña, $usuario['contraseña'])) {
        // Almacenar información del usuario en la sesión
        $_SESSION['id_usuario'] = $usuario['id_Usuario'];
        $_SESSION['nombre_usuario'] = $usuario['nombre'];
        $_SESSION['id_tipo_de_usuario'] = $usuario['id_tipo_de_usuario'];  // Almacenar el tipo de usuario en la sesión

        // Redirigir al inicio o a la página correspondiente según el rol
        if ($_SESSION['id_tipo_de_usuario'] == 1) {
            // Admin
            header("Location: inicio.php");
        } elseif ($_SESSION['id_tipo_de_usuario'] == 2) {
            // Vendedor
            header("Location: inicio.php");
        } else {
            // Si el usuario no es admin ni vendedor, redirigir a la página de inicio general
            header("Location: inicio.php");
        }
    } else {
        // Si las credenciales no son correctas
        header("Location: login.php?error=1");
    }
}
?>
