<?php
session_start();
?>

<?php require_once 'config/db.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Inicio | Mi E-Commerce</title>
  <link rel="stylesheet" href="estilos/inicio.css">
  <link rel="stylesheet" href="estilos/banner.css">
  <link rel="stylesheet" href="estilos/footer.css">
  <link rel="stylesheet" href="estilos/carrito.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css ">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">


 
</head>

<body>

<header class="header">
  <div class="logo"><i class="fas fa-shopping-bag"></i> Mi E-Commerce</div>
  <div class="acciones">
    <button onclick="location.href='inicio.php'"><i class="fas fa-home"></i> Inicio</button>
    <button onclick="location.href='carrito.php'"><i class="fas fa-shopping-cart"></i> Carrito</button>
    
    <?php if (isset($_SESSION['nombre_usuario'])): ?>
      <button><i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['nombre_usuario']) ?></button>
      <button onclick="location.href='perfil.php'"><i class="fas fa-user-cog"></i> Perfil</button>

      <?php if (isset($_SESSION['id_tipo_de_usuario']) && ($_SESSION['id_tipo_de_usuario'] == 1 || $_SESSION['id_tipo_de_usuario'] == 2)): ?>
        <button onclick="location.href='agregar_producto.php'"><i class="fas fa-plus-circle"></i> Agregar Producto</button>
      <?php endif; ?>

      <button onclick="location.href='logout.php'"><i class="fas fa-sign-out-alt"></i> Cerrar sesión</button>
    <?php else: ?>
      <button onclick="location.href='login.php'"><i class="fas fa-sign-in-alt"></i> Iniciar sesión</button>
    <?php endif; ?>
    
    <div class="filter-menu">
      <button id="filterButton"><i class="fas fa-filter"></i> Filtrar</button>
      <div id="categoryMenu" style="display: none;">
        <?php
        $categorias = $conn->query("SELECT id_categoria, nombre FROM categoria ORDER BY nombre");
        while ($cat = $categorias->fetch_assoc()): ?>
          <a href="#" class="category-item" data-id="<?= $cat['id_categoria'] ?>">
            <?= htmlspecialchars($cat['nombre']) ?>
          </a>
        <?php endwhile; ?>
      </div>
    </div>
  </div>
</header>


  



<div class="banner" id="banner">
  <div class="banner-content" id="banner-content">
    <p id="banner-texto">TODO EN TECNOLOGÍA</p>
    <h2 id="banner-titulo">EN LA MEJOR PLATAFORMA</h2>
    <button onclick="location.href='ver_productos.php'">CONOCE MÁS</button>
  </div>
  <!-- Flechas de navegación -->
  <button class="arrow left" onclick="cambiarBanner(-1)">&#10094;</button>
  <button class="arrow right" onclick="cambiarBanner(1)">&#10095;</button>
</div>

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
document.getElementById('filterButton').addEventListener('click', function() {
  const categoryMenu = document.getElementById('categoryMenu');
  categoryMenu.style.display = categoryMenu.style.display === 'block' ? 'none' : 'block';
});

// Manejar el clic en las categorías
document.querySelectorAll('.category-item').forEach(item => {
  item.addEventListener('click', function() {
    const categoryId = this.getAttribute('data-id');
    fetchProductsByCategory(categoryId);
    // Cerrar el menú después de seleccionar
    document.getElementById('categoryMenu').style.display = 'none';
  });
});

// Función para cargar productos por categoría usando AJAX
function fetchProductsByCategory(categoryId) {
  const xhr = new XMLHttpRequest();
  xhr.open('GET', 'filtro_productos.php?categoria_id=' + categoryId, true);

  xhr.onload = function() {
    if (xhr.status === 200) {
      document.querySelector('main').innerHTML = xhr.responseText;
    } else {
      console.error('Error al cargar productos:', xhr.statusText);
    }
  };

  xhr.onerror = function() {
    console.error('Error de red.');
  };

  xhr.send();
}
</script>

<script>
const banners = [
  {
    imagen: 'banner/computadora.png',
    titulo: 'EN LA MEJOR PLATAFORMA',
    texto: 'TODO EN TECNOLOGÍA'
  },
  {
    imagen: 'banner/celular.jpg',
    titulo: 'INNOVACIÓN EN TUS MANOS',
    texto: 'LOS MEJORES CELULARES'
  },
  {
    imagen: 'banner/ropa.jpg',
    titulo: 'LA MODA EN TUS MANOS',
    texto: 'AL MEJOR PRECIO Y LA MEJOR CALIDAD'
  }
];

let index = 0;

function mostrarBanner(i) {
  const banner = document.getElementById('banner');
  const titulo = document.getElementById('banner-titulo');
  const texto = document.getElementById('banner-texto');

  banner.style.backgroundImage = `url('${banners[i].imagen}')`;
  titulo.textContent = banners[i].titulo;
  texto.textContent = banners[i].texto;
}

function cambiarBanner(direccion) {
  index += direccion;
  if (index < 0) index = banners.length - 1;
  if (index >= banners.length) index = 0;
  mostrarBanner(index);
}

// Inicializar al cargar
document.addEventListener('DOMContentLoaded', () => {
  mostrarBanner(index);
});
</script>

</body>
</html>
