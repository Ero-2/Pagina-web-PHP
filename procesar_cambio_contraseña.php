<?php
require_once 'config/db.php';

$correo = $_POST['correo'];
$nueva = $_POST['nueva_contraseña'];
$cifrada = password_hash($nueva, PASSWORD_DEFAULT);

// Verifica que el usuario exista
$check = $conn->prepare("SELECT * FROM Usuario WHERE correo = ?");
$check->bind_param("s", $correo);
$check->execute();
$result = $check->get_result();

if ($result->num_rows === 0) {
  die("No se encontró un usuario con ese correo. <a href='cambiar_contraseña.php'>Volver</a>");
}

// Actualiza la contraseña
$update = $conn->prepare("UPDATE Usuario SET contraseña = ? WHERE correo = ?");
$update->bind_param("ss", $cifrada, $correo);

if ($update->execute()) {
  echo "Contraseña actualizada correctamente. <a href='login.php'>Iniciar sesión</a>";
} else {
  echo "Error al actualizar. Inténtalo de nuevo.";
}
