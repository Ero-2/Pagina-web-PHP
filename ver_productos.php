<?php
session_start();
require_once 'config/db.php';

// Consulta para obtener todos los productos con sus categorías
$sql = "SELECT p.id_producto, p.nombre_Producto, p.descripcion, p.Precio, i.url AS imagen, c.nombre AS categoria
        FROM Producto p
        LEFT JOIN Imagen i ON p.id_producto = i.id_Producto
        LEFT JOIN categoria c ON p.id_categoria = c.id_categoria
        ORDER BY p.nombre_Producto ASC";
$result = $conn->query($sql);

$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ver Productos</title>
    <link rel="stylesheet" href="estilos/carrito.css">
    <link rel="stylesheet" href="estilos/ver_productos.css">
    <link rel="stylesheet" href="estilos/footer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css ">

</head>
<body>

<!-- Header igual al de carrito.php -->
<header class="header">
  <div class="logo"><i class="fas fa-shopping-bag"></i> Mi E-Commerce</div>
  <div class="acciones">
    <!-- Botón filtro también puede ir aquí -->
    <button id="filterButton"><i class="fas fa-filter"></i> Filtrar</button>
    <button onclick="location.href='inicio.php'"><i class="fas fa-home"></i> Inicio</button>
    <button onclick="location.href='carrito.php'"><i class="fas fa-shopping-cart"></i> Carrito</button>
    <?php if (isset($_SESSION['nombre_usuario'])): ?>
      <button><i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['nombre_usuario']) ?></button>
      <button onclick="location.href='logout.php'"><i class="fas fa-sign-out-alt"></i> Cerrar sesión</button>
    <?php else: ?>
      <button onclick="location.href='login.php'"><i class="fas fa-sign-in-alt"></i> Iniciar sesión</button>
    <?php endif; ?>
  </div>
</header>

<main class="main-content">
    <h2>Todos los Productos</h2>

    <!-- Menú desplegable para categorías -->
    <div id="categoryMenu" style="display:none; margin-bottom: 1rem;">
        <!-- Aquí puedes insertar dinámicamente las categorías -->
        <!-- <div class="category-item" data-id="1">Tecnología</div> -->
    </div>

    <?php if (empty($products)): ?>
        <p>No hay productos disponibles.</p>
    <?php else: ?>
        <div class="grid-productos">
            <?php foreach ($products as $product): ?>
                <div class="card-producto">
                    <img src="<?= htmlspecialchars($product['imagen']) ?>" alt="<?= htmlspecialchars($product['nombre_Producto']) ?>">
                    <h3><?= htmlspecialchars($product['nombre_Producto']) ?></h3>
                    <p class="precio">$<?= number_format($product['Precio'], 2) ?></p>
                    <p class="categoria"><?= htmlspecialchars($product['categoria']) ?></p>
                    <a href="detalle_producto.php?id=<?= $product['id_producto'] ?>" class="btn-detalle">Ver Detalle</a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<footer class="main-footer">
  <div class="footer-container">
    
    <!-- Información de la tienda -->
    <div class="footer-box">
      <h3>🛍️ Mi E-Commerce</h3>
      <p>Tu tienda online con los mejores productos del mercado. Calidad garantizada al mejor precio.</p>
    </div>

    <!-- Enlaces rápidos -->
    <div class="footer-box">
      <h3>Enlaces Rápidos</h3>
      <ul>
        <li><a href="inicio.php">Inicio</a></li>
        <li><a href="colecciones.php">Colecciones</a></li>
        <li><a href="carrito.php">Carrito</a></li>
        <li><a href="login.php">Iniciar Sesión</a></li>
        <li><a href="registro.php">Registrarse</a></li>
      </ul>
    </div>

    <!-- Contacto -->
    <div class="footer-box">
      <h3>Contacto</h3>
      <ul>
        <li>📧 contacto@miecommerce.com</li>
        <li>📞 +57 300 123 4567</li>
        <li>📍 Bogotá, Colombia</li>
      </ul>
    </div>

    <!-- Redes sociales -->
    <div class="footer-box">
      <h3>Síguenos</h3>
      <div class="social-links">
        <a href="#"><i class="fab fa-facebook-f"></i> Facebook</a><br>
        <a href="#"><i class="fab fa-instagram"></i> Instagram</a><br>
        <a href="#"><i class="fab fa-twitter"></i> Twitter</a><br>
        <a href="#"><i class="fab fa-youtube"></i> YouTube</a>
      </div>
    </div>

  </div>

  <!-- Derechos de autor -->
  <div class="footer-bottom">
    <p>&copy; <?= date('Y') ?> Mi E-Commerce - Todos los derechos reservados.</p>
  </div>
</footer>

<script>
document.getElementById('filterButton').addEventListener('click', function () {
  const menu = document.getElementById('categoryMenu');
  menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
});

document.querySelectorAll('.category-item').forEach(item => {
  item.addEventListener('click', function () {
    const categoryId = this.getAttribute('data-id');
    window.location.href = 'inicio.php?categoria_id=' + categoryId;
  });
});
</script>

</body>
</html>
