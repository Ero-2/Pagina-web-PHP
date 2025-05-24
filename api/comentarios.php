<?php
require_once '../config/db.php';
require_once '../config/auth.php';

verificar_token(); // Verifica si el token es válido

header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!isset($_GET['id_producto'])) {
        echo json_encode(['success' => false, 'message' => 'ID de producto no especificado']);
        exit;
    }

    $id_producto = intval($_GET['id_producto']);

    $sql = "SELECT c.id_Comentario, c.comentario, c.calificacion, c.fecha_Comentario, u.nombre 
            FROM Comentario c
            JOIN Usuario u ON c.id_Usuario = u.id_Usuario
            WHERE c.id_Producto = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_producto);
    $stmt->execute();
    $result = $stmt->get_result();

    $comentarios = [];

    while ($row = $result->fetch_assoc()) {
        $comentarios[] = $row;
    }

    echo json_encode(['success' => true, 'comentarios' => $comentarios]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['id_producto'], $data['id_usuario'], $data['comentario'], $data['calificacion'])) {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
        exit;
    }

    $id_producto = intval($data['id_producto']);
    $id_usuario = intval($data['id_usuario']);
    $comentario = htmlspecialchars($data['comentario']);
    $calificacion = intval($data['calificacion']);

    $sql = "INSERT INTO Comentario (id_Producto, id_Usuario, comentario, calificacion, fecha_Comentario)
            VALUES (?, ?, ?, ?, NOW())";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiis", $id_producto, $id_usuario, $comentario, $calificacion);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Comentario guardado']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al guardar el comentario']);
    }
}
?>