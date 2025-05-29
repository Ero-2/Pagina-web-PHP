<?php
session_start();
$id_producto = isset($_GET['id']) ? intval($_GET['id']) : 0;
$jwt_token = $_SESSION['token'] ?? '';

// Verificar si el usuario es administrador (1) o vendedor (2)
$esAdminOVendedor = isset($_SESSION['id_tipo_de_usuario']) && in_array($_SESSION['id_tipo_de_usuario'], [1, 2]);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Producto | Mi E-Commerce</title>
    <link rel="stylesheet" href="estilos/editar_producto.css">
    <link rel="stylesheet" href="estilos/carrito.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css ">
    <style>
        .sin-stock {
            color: red;
            font-weight: bold;
        }
        .boton-editar {
            background-color: #2575fc;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 10px;
            display: inline-block;
        }
        .boton-editar:hover {
            background-color: #1a58c3;
        }
        .formulario-editar {
            max-width: 600px;
            margin: auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            margin-top: 30px;
        }
        label {
            display: block;
            margin-top: 10px;
            font-weight: bold;
        }
        input[type="text"], input[type="number"], textarea, select {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        button {
            margin-top: 15px;
            padding: 10px 20px;
            background: #2575fc;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .mensaje-exito, .mensaje-error {
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .mensaje-exito {
            background-color: #d4edda;
            color: green;
        }
        .mensaje-error {
            background-color: #f8d7da;
            color: red;
        }
    </style>
</head>
<body>

<header class="header">
    <div class="logo"><i class="fas fa-shopping-bag"></i> Mi E-Commerce</div>
    <form action="buscar.php" method="get" class="busqueda" style="flex-grow: 1; margin: 0 1rem;">
        <input type="text" name="q" placeholder="Buscar productos..." required style="padding: 8px; width: 100%; border-radius: 25px; border: none;">
        <button type="submit" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #2575fc;">
            <i class="fas fa-search"></i>
        </button>
    </form>
    <div class="acciones">
        <button onclick="location.href='inicio.php'"><i class="fas fa-home"></i> Inicio</button>
        <button onclick="location.href='carrito.php'"><i class="fas fa-shopping-cart"></i> Carrito</button>
        <?php if (isset($_SESSION['nombre_usuario'])): ?>
            <button><i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['nombre_usuario']) ?></button>
            <?php if ($esAdminOVendedor): ?>
                <button class="boton-editar" onclick="location.href='editar_producto.php?id=<?= $id_producto ?>'">
                    <i class="fas fa-edit"></i> Editar Producto
                </button>
            <?php endif; ?>
            <button onclick="location.href='logout.php'"><i class="fas fa-sign-out-alt"></i> Cerrar sesión</button>
        <?php else: ?>
            <button onclick="location.href='login.php'"><i class="fas fa-sign-in-alt"></i> Iniciar sesión</button>
        <?php endif; ?>
    </div>
</header>

<main>
    <div class="formulario-editar" id="formulario-editar">
        <h2>Editar Producto</h2>

        <!-- Mostrar mensajes de éxito/error -->
        <div id="mensaje" style="display: none;" class="mensaje-error"></div>

        <!-- Formulario estático -->
        <form id="editarForm" enctype="multipart/form-data" method="post">
            <label for="nombre">Nombre del Producto:</label>
            <input type="text" name="nombre" id="nombre" required>

            <label for="precio">Precio:</label>
            <input type="number" step="0.01" name="precio" id="precio" required>

            <label for="descripcion">Descripción:</label>
            <textarea name="descripcion" id="descripcion" rows="4"></textarea>

            <label for="stock">Stock:</label>
            <input type="number" name="stock" id="stock" min="0" required>

            <label for="id_categoria">Categoría:</label>
            <select name="id_categoria" id="id_categoria" required></select>

            <label for="imagen">Cambiar Imagen:</label>
            <img src="" alt="Imagen actual" id="imagenActual" style="max-width: 200px; margin-bottom: 10px;" />
            <input type="file" name="imagen" id="imagen" accept="image/*">

            <button type="submit">Guardar Cambios</button>
        </form>
    </div>
</main>

<script>
const productoId = <?= $id_producto ?>;
const jwtToken = "<?= $jwt_token ?>";
const esAdminOVendedor = <?= $esAdminOVendedor ? 'true' : 'false' ?>;

function showMessage(text, isError = false) {
    const mensajeDiv = document.getElementById('mensaje');
    mensajeDiv.style.display = 'block';
    mensajeDiv.textContent = text;
    mensajeDiv.className = `mensaje-${isError ? 'error' : 'exito'}`;
}

async function cargarProducto() {
    try {
        const res = await fetch(`api/productos.php?id=${productoId}`);
        const data = await res.json();

        if (!data.success || !data.producto) {
            throw new Error("Producto no encontrado");
        }

        const p = data.producto;

        // Rellenar campos del formulario
        document.getElementById('nombre').value = p.nombre_Producto;
        document.getElementById('precio').value = parseFloat(p.Precio).toFixed(2);
        document.getElementById('descripcion').value = p.descripcion;
        document.getElementById('stock').value = parseInt(p.stock);

        // Cargar categorías dinámicamente
        const catRes = await fetch('api/categorias.php');
        const catData = await catRes.json();
        const categoriaSelect = document.getElementById('id_categoria');

        if (catData.success && Array.isArray(catData.categorias)) {
            catData.categorias.forEach(cat => {
                const option = document.createElement('option');
                option.value = cat.id_categoria;
                option.textContent = cat.nombre;
                if (cat.id_categoria === p.id_categoria) {
                    option.selected = true;
                }
                categoriaSelect.appendChild(option);
            });
        } else {
            showMessage("No se pudieron cargar las categorías", true);
        }

        // Mostrar imagen actual
        const imgElement = document.getElementById('imagenActual');
        if (p.url) {
            imgElement.src = p.url;
            imgElement.style.display = 'block';
        }

    } catch (err) {
        console.error("Error al cargar producto:", err);
        showMessage("Error al cargar los datos del producto", true);
    }
}

document.getElementById('editarForm').addEventListener('submit', async e => {
    e.preventDefault();

    if (!esAdminOVendedor) {
        alert("No tienes permisos para editar este producto.");
        return;
    }

    const form = new FormData(document.getElementById('editarForm'));

    try {
        const response = await fetch(`api/editar_producto.php?id=${productoId}`, {
            method: 'PUT',
            headers: {
                'Authorization': `Bearer ${jwtToken}`
            },
            body: form
        });

        const result = await response.json();

        if (result.success) {
            showMessage("Producto actualizado correctamente.", false);
        } else {
            showMessage(result.error || "Error desconocido", true);
        }

    } catch (err) {
        console.error("Error al actualizar el producto:", err);
        showMessage("Error al conectar con el servidor: " + err.message, true);
    }
});

cargarProducto();
</script>
</body>
</html>