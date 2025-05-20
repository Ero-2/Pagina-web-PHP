<?php
require 'config/db.php';
session_start();

$datos = json_decode(file_get_contents('php://input'), true);

// Validar autenticación
if (!isset($_SESSION['id_usuario'])) {
    http_response_code(403);
    echo "Usuario no autenticado.";
    exit;
}

$id_usuario = $_SESSION['id_usuario'];

// Validación básica (puedes ampliarla)
if (
    empty($datos['calle']) || empty($datos['numero']) || empty($datos['pais']) ||
    empty($datos['estado']) || empty($datos['ciudad']) || empty($datos['codigo_postal'])
) {
    http_response_code(400);
    echo "Todos los campos son obligatorios.";
    exit;
}

// Insertar en base de datos
$stmt = $conn->prepare("INSERT INTO direccion_nueva (id_usuario, calle, numero, pais_nombre, estado_nombre, ciudad_nombre, codigo_postal) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("issssss",
    $id_usuario,
    $datos['calle'],
    $datos['numero'],
    $datos['pais'],
    $datos['estado'],
    $datos['ciudad'],
    $datos['codigo_postal']
);

if ($stmt->execute()) {
    echo "Dirección guardada exitosamente.";
} else {
    http_response_code(500);
    echo "Error al guardar la dirección: " . $stmt->error;
}
?>

