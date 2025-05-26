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
            <?php if ($_SESSION['id_tipo_de_usuario'] == 1 || $_SESSION['id_tipo_de_usuario'] == 2): ?>
                <button onclick="location.href='agregar_producto.php'" class="active"><i class="fas fa-plus-circle"></i> Agregar Producto</button>
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

        <label for="categoria_id">Categoría:</label>
        <select name="categoria_id" id="categoria_id" required>
            <option value="">-- Selecciona una categoría --</option>
            <!-- Las categorías se cargarán dinámicamente -->
        </select><br><br>

        <label for="imagen">Imagen del Producto:</label>
        <input type="file" name="imagen" id="imagen" accept="image/*" required><br><br>

        <button type="submit">Agregar Producto</button>
    </form>
</main>

<script>
document.addEventListener('DOMContentLoaded', async () => {
    const form = document.getElementById('productoForm');
    const mensaje = document.getElementById('mensaje');

    // Cargar categorías desde la API
    try {
        const res = await fetch('api/listar_categorias.php'); // Asegúrate de tener este endpoint
        const data = await res.json();

        const categoriaSelect = document.getElementById('categoria_id');
        if (data.categorias && data.categorias.length > 0) {
            data.categorias.forEach(cat => {
                const option = document.createElement('option');
                option.value = cat.id_categoria;
                option.textContent = cat.nombre;
                categoriaSelect.appendChild(option);
            });
        } else {
            categoriaSelect.innerHTML = '<option>No hay categorías disponibles</option>';
        }
    } catch (err) {
        console.error('Error al cargar las categorías:', err);
        alert('No se pudieron cargar las categorías');
    }

    // Enviar formulario
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());

        const token = localStorage.getItem('token');
        if (!token) {
            mensaje.style.display = 'block';
            mensaje.className = 'alert error';
            mensaje.textContent = 'No hay token disponible. Por favor, inicia sesión.';
            setTimeout(() => {
                window.location.href = 'login.php';
            }, 2000);
            return;
        }

        try {
            const response = await fetch('api/agregar-producto-api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token}`
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                mensaje.style.display = 'block';
                mensaje.className = 'alert success';
                mensaje.textContent = result.message || 'Producto agregado exitosamente';
                form.reset();
            } else {
                mensaje.style.display = 'block';
                mensaje.className = 'alert error';
                mensaje.textContent = result.error || 'Error desconocido';
            }
        } catch (err) {
            console.error(err);
            mensaje.style.display = 'block';
            mensaje.className = 'alert error';
            mensaje.textContent = 'Error al conectar con el servidor';
        }
    });
});
</script>

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

</body>
</html>