<?php
session_start();
require_once 'config/db.php';

$nombre = $_POST['nombre'];
$apellido = $_POST['apellido'];
$correo = $_POST['correo'];
$contraseña = password_hash($_POST['contraseña'], PASSWORD_DEFAULT);
$id_tipo = $_POST['id_tipo_de_usuario'];

$fecha = date("Y-m-d H:i:s");

// que no exista el correo
$check = $conn->prepare("SELECT * FROM Usuario WHERE correo = ?");
$check->bind_param("s", $correo);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
  die("Este correo ya está registrado. <a href='registro.php'>Volver</a>");
}

// Inserta usuario
$stmt = $conn->prepare("INSERT INTO Usuario (nombre, apellido, correo, contraseña, fecha_Registro, id_tipo_de_usuario) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssssi", $nombre, $apellido, $correo, $contraseña, $fecha, $id_tipo);

if ($stmt->execute()) {
  $_SESSION['id_usuario'] = $stmt->insert_id;
  $_SESSION['nombre_usuario'] = $nombre;
  header("Location: inicio.php");
} else {
  echo "Error al registrar usuario.";
}
