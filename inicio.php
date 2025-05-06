<?php
session_start();
?>

<?php require_once 'config/db.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Inicio | Mi E-Commerce</title>
  <link rel="stylesheet" href="estilos.css">
</head>
<link rel="stylesheet" href="estilos/inicio.css">
<body>

<header>
  <div class="logo">🛍️ Mi E-Commerce</div>
  <div class="acciones"><button onclick="location.href='inicio.php'">inicio</button></div> 
  <form action="buscar.php" method="get" class="busqueda">
  <input type="text" name="q" placeholder="Buscar productos..." required>
  <button type="submit" >🔍</button>
</form>

  <div class="acciones">
    <button onclick="location.href='carrito.php'">🛒 Carrito</button>
    
    <?php if (isset($_SESSION['nombre_usuario'])): ?>
      <span>👤 <?= htmlspecialchars($_SESSION['nombre_usuario']) ?></span>
      
      <!-- Botón para agregar producto solo si el usuario es admin o vendedor -->
      <?php if ($_SESSION['id_tipo_de_usuario'] == 1 || $_SESSION['id_tipo_de_usuario'] == 2): ?>
        <button onclick="location.href='agregar_producto.php'">Agregar Producto</button>
      <?php endif; ?>
      
      <button onclick="location.href='logout.php'">Cerrar sesión</button>
    <?php else: ?>
      <button onclick="location.href='login.php'">Iniciar sesión</button>
    <?php endif; ?>
  </div>
</header>


<main>
<?php
$sql = "SELECT p.id_Producto, p.nombre_Producto, p.Precio, i.url 
        FROM Producto p 
        LEFT JOIN Imagen i ON p.id_Producto = i.id_Producto 
        GROUP BY p.id_Producto"; // Una imagen por producto

$resultado = $conn->query($sql);

if ($resultado->num_rows > 0) {
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
} else {
  echo "<p>No hay productos disponibles.</p>";
}
?>
</main>

</body>
</html>
