<?php
session_start();
if (!isset($_SESSION['id_usuario'])) {
    http_response_code(401);
    echo "Usuario no autenticado";
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['metodo']) || !isset($data['detalles'])) {
    http_response_code(400);
    echo "Faltan datos";
    exit();
}

require 'conexion.php'; // Cambia esto por el nombre real de tu archivo de conexión

$id_usuario = $_SESSION['id_usuario'];
$metodo = $data['metodo'];
$detalles = $data['detalles'];

$stmt = $conn->prepare("INSERT INTO metodo_pago (metodo, Detalles, id_Usuario) VALUES (?, ?, ?)");
$stmt->bind_param("ssi", $metodo, $detalles, $id_usuario);

if ($stmt->execute()) {
    echo "Método de pago guardado correctamente.";
} else {
    http_response_code(500);
    echo "Error al guardar el método de pago.";
}

$stmt->close();
$conn->close();
?>
