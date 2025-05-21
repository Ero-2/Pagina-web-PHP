<?php
require_once '../config/db.php';
require_once '../config/auth.php';

verificar_token(); // Verifica si el token es válido

header("Content-Type: application/json");

$sql = "SELECT p.id_Producto, p.nombre_Producto, p.Precio, i.url 
        FROM Producto p 
        LEFT JOIN Imagen i ON p.id_Producto = i.id_Producto 
        GROUP BY p.id_Producto";

$result = $conn->query($sql);
$productos = [];

while ($row = $result->fetch_assoc()) {
    $productos[] = $row;
}

echo json_encode($productos);
?>