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
    <title>Agregar Producto</title>
    <link rel="stylesheet" href="estilos/agregar_producto.css">
    <link rel="stylesheet" href="estilos/inicio.css">
    <link rel="stylesheet" href="estilos/carrito.css">
    <link rel="stylesheet" href="estilos/footer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css   ">
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
      
      <?php if ($_SESSION['id_tipo_de_usuario'] == 1 || $_SESSION['id_tipo_de_usuario'] == 2): ?>
        <button onclick="location.href='agregar_producto.php'" class="active">
          <i class="fas fa-plus-circle"></i> Agregar Producto
        </button>
      <?php endif; ?>

      <button onclick="location.href='logout.php'"><i class="fas fa-sign-out-alt"></i> Cerrar sesión</button>
    <?php else: ?>
      <button onclick="location.href='login.php'"><i class="fas fa-sign-in-alt"></i> Iniciar sesión</button>
    <?php endif; ?>
  </div>
</header>

<main>
    <h2>Agregar Nuevo Producto</h2>
    <div id="mensaje" class="alert" style="display: none;"></div>

    <form id="productoForm">
        <label for="nombre">Nombre del Producto:</label>
        <input type="text" name="nombre" id="nombre" required><br><br>

        <label for="descripcion">Descripción:</label>
        <textarea name="descripcion" id="descripcion" required></textarea><br><br>

        <label for="precio">Precio:</label>
        <input type="number" step="0.01" name="precio" id="precio" required><br><br>

        <label for="id_categoria">Categoría:</label>
        <select name="id_categoria" id="id_categoria" required>
            <option value="">-- Selecciona una categoría --</option>
        </select><br><br>

        <label for="imagen">Imagen del Producto:</label>
        <input type="file" name="imagen" id="imagen" accept="image/*" required><br><br>

        <button type="submit">Agregar Producto</button>
    </form>
</main>

<!-- Footer -->
<footer class="main-footer">
  <div class="footer-container">
    <div class="footer-box">
      <h3>🛍️ Mi E-Commerce</h3>
      <p>Tu tienda online con los mejores productos del mercado. Calidad garantizada.</p>
    </div>
    <div class="footer-bottom">
      <p>&copy; <?= date('Y') ?> Mi E-Commerce - Todos los derechos reservados.</p>
    </div>
  </div>
</footer>

<!-- Estilos dinámicos para mensajes -->
<style>
.alert {
    padding: 15px;
    margin-top: 20px;
    border-radius: 5px;
    color: white;
    font-weight: bold;
}
.alert.success {
    background-color: #2ecc71;
}
.alert.error {
    background-color: #e74c3c;
}
</style>

<!-- Script principal -->
<script>
document.addEventListener('DOMContentLoaded', async () => {
    const form = document.getElementById('productoForm');
    const mensaje = document.getElementById('mensaje');

    // Cargar categorías desde la API
    try {
        const res = await fetch('api/listar_categorias.php'); // Endpoint RESTful
        const data = await res.json();

        const categoriaSelect = document.getElementById('categoria_id');
        if (data.success && Array.isArray(data.categorias)) {
            // Limpiar opciones iniciales
            categoriaSelect.innerHTML = '<option value="">-- Selecciona una categoría --</option>';

            // Rellenar con categorías desde el servidor
            data.categorias.forEach(cat => {
                const option = document.createElement('option');
                option.value = cat.id_categoria;
                option.textContent = cat.nombre;
                categoriaSelect.appendChild(option);
            });
        } else {
            console.warn('No se encontraron categorías:', data.error);
            categoriaSelect.innerHTML = '<option disabled>No hay categorías disponibles</option>';
            mensaje.style.display = 'block';
            mensaje.className = 'alert error';
            mensaje.textContent = 'No se pudieron cargar las categorías.';
        }
    } catch (err) {
        console.error('Error al cargar las categorías:', err);
        categoriaSelect.innerHTML = '<option value="">Error cargando categorías</option>';
        mensaje.style.display = 'block';
        mensaje.className = 'alert error';
        mensaje.textContent = 'Hubo un problema al cargar las categorías.';
    }

    // Manejar envío del formulario
    form.addEventListener('submit', async e => {
        e.preventDefault();
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData.entries());

        const token = localStorage.getItem('token');
        if (!token) {
            mensaje.style.display = 'block';
            mensaje.className = 'alert error';
            mensaje.textContent = 'No hay token disponible. Por favor, vuelve a iniciar sesión.';
            setTimeout(() => window.location.href = 'login.php', 2000);
            return;
        }

        try {
            const response = await fetch('api/agregar_producto.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token}`
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                showMessage(result.message || 'Producto agregado exitosamente');
                e.target.reset(); // Limpiar formulario
            } else {
                showMessage(result.error || 'Error desconocido al agregar el producto', true);
            }
        } catch (error) {
            console.error('Error detallado:', error);
            showMessage(error.message || 'Error al conectar con el servidor', true);
        } finally {
            // Rehabilitar botón
            submitButton.disabled = false;
            submitButton.textContent = originalText;
        }
    });
});
</script>

</body>
</html>