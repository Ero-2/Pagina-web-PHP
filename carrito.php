<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit;
}

$id_usuario = $_SESSION['id_usuario'];

// Obtener productos del carrito
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

// Calcular total general
$total_general = 0;
$productos = [];

while ($producto = $result_productos->fetch_assoc()) {
    $total_producto = $producto['Precio'] * $producto['cantidad'];
    $total_general += $total_producto;
    $productos[] = $producto;
}

// Obtener compras realizadas anteriores
$sql_compras = "SELECT p.id_Pedido, p.fecha_Pedido, pd.id_Producto, pd.nombre_Producto, pd.Precio, dp.cantidad, dp.subtotal 
                FROM Pedido p
                JOIN Detalle_Pedido dp ON p.id_Pedido = dp.id_Pedido
                JOIN Producto pd ON dp.id_Producto = pd.id_Producto
                WHERE p.id_Usuario = ?
                ORDER BY p.fecha_Pedido DESC";
$stmt_compras = $conn->prepare($sql_compras);
$stmt_compras->bind_param("i", $id_usuario);
$stmt_compras->execute();
$result_compras = $stmt_compras->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Carrito de Compras</title>
  <link rel="stylesheet" href="estilos/carrito.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

<header class="header">
  <div class="logo"><i class="fas fa-shopping-bag"></i> Mi E-Commerce</div>
  <div class="acciones">
    <button onclick="location.href='inicio.php'"><i class="fas fa-shopping-cart"></i> Continuar comprando</button>
    <button onclick="location.href='inicio.php'"><i class="fas fa-home"></i> Inicio</button>
  </div>
</header>

<main class="main-content">

  <!-- Sección del Carrito -->
  <section class="carrito-section">
    <h2>Tu Carrito</h2>
    <?php if (count($productos) > 0): ?>
      <div class="carrito-items">
        <?php foreach ($productos as $producto): ?>
          <div class="carrito-card">
            <img src="<?= htmlspecialchars($producto['url'] ?? 'img/placeholder.jpg') ?>" alt="<?= htmlspecialchars($producto['nombre_Producto']) ?>" class="carrito-img">
            <div class="carrito-info">
              <div>
                <p class="product-name"><?= htmlspecialchars($producto['nombre_Producto']) ?></p>
                <p>Precio: $<?= number_format($producto['Precio'], 2) ?></p>
                <p>Cantidad: <?= $producto['cantidad'] ?></p>
                <p><strong>Total: $<?= number_format($producto['Precio'] * $producto['cantidad'], 2) ?></strong></p>
              </div>
              <button class="btn-eliminar" onclick="eliminarDelCarrito(<?= $producto['id_Producto'] ?>)">
                <i class="fas fa-trash-alt"></i> Eliminar
              </button>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="total-carrito">
        <strong>Total: $<?= number_format($total_general, 2) ?></strong>
        <button id="btn-comprar" onclick="finalizarCompra()">
          <i class="fas fa-check-circle"></i> Finalizar Compra
        </button>
      </div>
    <?php else: ?>
      <div class="carrito-vacio">
        <i class="fas fa-shopping-cart fa-4x"></i>
        <p>Tu carrito está vacío. <a href="inicio.php">Volver a comprar</a></p>
      </div>
    <?php endif; ?>
  </section>

  <!-- Sección de Compras Realizadas -->
  <section class="historial-compras">
    <h2>Tus Compras Realizadas</h2>
    <?php if ($result_compras->num_rows > 0): ?>
      <div class="compras-lista">
        <?php while ($compra = $result_compras->fetch_assoc()): ?>
          <div class="compra-card">
            <div>
              <p class="product-name"><?= htmlspecialchars($compra['nombre_Producto']) ?></p>
              <p>Cantidad: <?= $compra['cantidad'] ?></p>
              <p>Precio Unitario: $<?= number_format($compra['Precio'], 2) ?></p>
              <p>Subtotal: $<?= number_format($compra['subtotal'], 2) ?></p>
              <p>Fecha: <?= date('d/m/Y', strtotime($compra['fecha_Pedido'])) ?></p>
            </div>
          </div>
        <?php endwhile; ?>
      </div>
    <?php else: ?>
      <div class="no-compras">
        <i class="fas fa-shopping-bag fa-3x"></i>
        <p>No tienes compras realizadas.</p>
      </div>
    <?php endif; ?>
  </section>

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
      window.location.reload();
    } else {
      alert('Error al eliminar el producto');
    }
  });
}

function finalizarCompra() {
  const idUsuario = <?= $_SESSION['id_usuario'] ?? 0 ?>;

  fetch('api/comprar.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      id_usuario: idUsuario
    })
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      alert(data.message);
      window.location.href = 'gracias.php';
    } else {
      alert('Error al procesar la compra: ' + data.message);
    }
  });
}
</script>

</body>
</html>