<?php
session_start();
require_once 'config/db.php';

$id_usuario = $_SESSION['id_usuario'];

$sql_carrito = "SELECT p.id_Producto, p.nombre_Producto, p.Precio, cp.cantidad, i.url 
                FROM Carrito_Producto cp
                JOIN Producto p ON cp.id_Producto = p.id_Producto
                LEFT JOIN Imagen i ON p.id_Producto = i.id_Producto
                JOIN Carrito c ON cp.id_Carrito = c.id_Carrito
                WHERE c.id_Usuario = ?";
$stmt_carrito = $conn->prepare($sql_carrito);
$stmt_carrito->bind_param("i", $id_usuario);
$stmt_carrito->execute();
$result_productos = $stmt_carrito->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Carrito de Compras</title>
<link rel="stylesheet" href="estilos/carrito.css">
</head>
<body>

<header>
  <div class="logo">🛍️  Mi E-Commerce</div>
  <div class="acciones">
    <button onclick="location.href='inicio.php'">🛒 Continuar comprando</button>
    <button onclick="location.href='inicio.php'">Inicio</button>
  </div>
</header>

<main>
  <h2>Carrito de Compras</h2>
  <?php while ($producto = $result_productos->fetch_assoc()): ?>
    <div class="producto">
      <img src="<?= htmlspecialchars($producto['url']) ?>" alt="Imagen del producto">
      <div class="info">
        <h3><?= htmlspecialchars($producto['nombre_Producto']) ?></h3>
        <p>Precio: $<?= number_format($producto['Precio'], 2) ?></p>
        <p>Cantidad: <?= $producto['cantidad'] ?></p>
        <p>Total: $<?= number_format($producto['Precio'] * $producto['cantidad'], 2) ?></p>
        <button onclick="eliminarDelCarrito(<?= $producto['id_Producto'] ?>)">Eliminar</button>
      </div>
    </div>
  <?php endwhile; ?>
</main>

<script>
function eliminarDelCarrito(idProducto) {
    const idUsuario = <?= $_SESSION['id_usuario'] ?? 0 ?>;

  fetch('api/carrito.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      accion: 'eliminar',
      id_usuario: idUsuario,
      id_producto: idProducto
    })
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      alert(data.message);
      window.location.reload();  // Actualiza la página para reflejar los cambios
    } else {
      alert('Error al eliminar el producto');
    }
  });
}
</script>

</body>
</html>
