<?php
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Perfil de Usuario</title>
  <link rel="stylesheet" href="estilos/perfil2.css">
    <link rel="stylesheet" href="estilos/footer.css">
    <link rel="stylesheet" href="estilos/inicio.css">
    <link rel="stylesheet" href="estilos/carrito.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css ">

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
        if (isset($conn)) {
          $categorias = $conn->query("SELECT id_categoria, nombre FROM categoria ORDER BY nombre");
          while ($cat = $categorias->fetch_assoc()): ?>
            <a href="#" class="category-item" data-id="<?= $cat['id_categoria'] ?>">
              <?= htmlspecialchars($cat['nombre']) ?>
            </a>
        <?php endwhile; } ?>
      </div>
    </div>
  </div>
</header>



<!-- Contenido de Perfil -->
<main class="perfil-contenedor">
  <section class="direccion-section">
    <h2>Agregar Dirección</h2>
    <form id="direccionForm">
      <label>Calle:</label>
      <input type="text" name="calle" required><br>

      <label>Número:</label>
      <input type="text" name="numero" required><br>

      <label>País:</label>
      <select id="pais" name="pais" required></select><br>

      <label>Estado:</label>
      <select id="estado" name="estado" required></select><br>

      <label>Ciudad:</label>
      <select id="ciudad" name="ciudad" required></select><br>

      <label>Código Postal:</label>
      <input type="text" name="codigo_postal" required><br>

      <button type="submit">Guardar Dirección</button>
    </form>
  </section>

  <section class="metodo-pago-section">
    <h2>Agregar Método de Pago</h2>
    <form id="pagoForm">
      <label>Método:</label>
      <select name="metodo" required>
        <option value="Tarjeta de Crédito">Tarjeta de Crédito</option>
        <option value="Tarjeta de Débito">Tarjeta de Débito</option>
        <option value="PayPal">PayPal</option>
        <option value="Transferencia Bancaria">Transferencia Bancaria</option>
      </select><br>

      <label>Detalles:</label>
      <input type="text" name="detalles" required><br>

      <button type="submit">Guardar Método</button>
    </form>
  </section>
</main>

<!-- Footer -->
<footer class="main-footer">
  <div class="footer-container">
    <div class="footer-box">
      <h3>🛍️ Mi E-Commerce</h3>
      <p>Tu tienda online con los mejores productos del mercado. Calidad garantizada al mejor precio.</p>
    </div>
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
    <div class="footer-box">
      <h3>Contacto</h3>
      <ul>
        <li>📧 contacto@miecommerce.com</li>
        <li>📞 +57 300 123 4567</li>
        <li>📍 Bogotá, Colombia</li>
      </ul>
    </div>
    <div class="footer-box">
      <h3>Síguenos</h3>
      <div class="social-links">
        <a href="#">Facebook</a><br>
        <a href="#">Instagram</a><br>
        <a href="#">Twitter</a><br>
        <a href="#">YouTube</a>
      </div>
    </div>
  </div>
  <div class="footer-bottom">
    <p>&copy; <?= date('Y') ?> Mi E-Commerce - Todos los derechos reservados.</p>
  </div>
</footer>

<!-- Scripts -->
<script>
document.addEventListener('DOMContentLoaded', async () => {
  const paisSelect = document.getElementById('pais');
  const estadoSelect = document.getElementById('estado');
  const ciudadSelect = document.getElementById('ciudad');

  const resPaises = await fetch('https://countriesnow.space/api/v0.1/countries/positions');
  const dataPaises = await resPaises.json();
  dataPaises.data.forEach(pais => {
    const option = document.createElement('option');
    option.value = pais.name;
    option.text = pais.name;
    paisSelect.add(option);
  });

  async function cargarEstados(pais) {
    estadoSelect.innerHTML = '';
    ciudadSelect.innerHTML = '';
    if (!pais) return;

    const resEstados = await fetch('https://countriesnow.space/api/v0.1/countries/states', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ country: pais })
    });
    const dataEstados = await resEstados.json();

    if (!dataEstados.data || dataEstados.data.states.length === 0) {
      estadoSelect.innerHTML = '<option>No hay estados disponibles</option>';
      return;
    }

    dataEstados.data.states.forEach(estado => {
      const option = document.createElement('option');
      option.value = estado.name;
      option.text = estado.name;
      estadoSelect.add(option);
    });

    cargarCiudades(estadoSelect.value);
  }

  async function cargarCiudades(estado) {
    ciudadSelect.innerHTML = '';
    const pais = paisSelect.value;
    if (!pais || !estado) return;

    const resCiudades = await fetch('https://countriesnow.space/api/v0.1/countries/state/cities', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ country: pais, state: estado })
    });
    const dataCiudades = await resCiudades.json();

    if (!dataCiudades.data || dataCiudades.data.length === 0) {
      ciudadSelect.innerHTML = '<option>No hay ciudades disponibles</option>';
      return;
    }

    dataCiudades.data.forEach(ciudad => {
      const option = document.createElement('option');
      option.value = ciudad;
      option.text = ciudad;
      ciudadSelect.add(option);
    });
  }

  paisSelect.addEventListener('change', () => {
    cargarEstados(paisSelect.value);
  });

  estadoSelect.addEventListener('change', () => {
    cargarCiudades(estadoSelect.value);
  });

  if (paisSelect.value) {
    cargarEstados(paisSelect.value);
  }

  document.getElementById('direccionForm').addEventListener('submit', async e => {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());

    const res = await fetch('guardar_direccion.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    });
    const result = await res.text();
    alert(result);
  });

  document.getElementById('pagoForm').addEventListener('submit', async e => {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());

    const res = await fetch('guardar_metodo_pago.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    });
    const result = await res.text();
    alert(result);
  });
});
</script>

</body>
</html>
