<?php
require_once 'config/db.php';

$id_producto = $_POST['id_producto'];
$id_usuario = $_POST['id_usuario']; // Simulado
$comentario = $conn->real_escape_string($_POST['comentario']);
$calificacion = intval($_POST['calificacion']);
$fecha = date('Y-m-d H:i:s');

$sql = "INSERT INTO Comentario (id_Usuario, id_Producto, comentario, calificacion, fecha_Comentario) 
        VALUES ($id_usuario, $id_producto, '$comentario', $calificacion, '$fecha')";

if ($conn->query($sql)) {
  header("Location: producto.php?id=$id_producto");
} else {
  echo "Error al guardar comentario.";
}
?>
