<?php
session_start();
require_once 'config/db.php';

$categoria_id = isset($_GET['categoria_id']) ? intval($_GET['categoria_id']) : null;

$where = "";
if ($categoria_id !== null && $categoria_id > 0) {
    $where = "WHERE p.id_categoria = $categoria_id";
}

$sql = "SELECT p.id_Producto, p.nombre_Producto, p.Precio, i.url 
        FROM Producto p 
        LEFT JOIN Imagen i ON p.id_Producto = i.id_Producto 
        $where 
        GROUP BY p.id_Producto";

$resultado = $conn->query($sql);

if ($resultado->num_rows > 0) {
    echo "<div class='productos'>";
    while ($producto = $resultado->fetch_assoc()) {
        echo "
        <div class='producto'>
          <img src='{$producto['url']}' alt='Producto'>
          <h3>{$producto['nombre_Producto']}</h3>
          <p>$ {$producto['Precio']}</p>
          <button onclick=\"location.href='producto.php?id={$producto['id_Producto']}'\">Ver más</button>
        </div>
        ";
    }
    echo "</div>";
} else {
    echo "<p>No hay productos disponibles.</p>";
}
?>