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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>

<header>
    <div class="logo"><i class="fas fa-shopping-bag"></i> Mi E-Commerce</div>
    <div class="acciones">
        <button onclick="location.href='inicio.php'"><i class="fas fa-home"></i> Inicio</button>
        <button onclick="location.href='carrito.php'"><i class="fas fa-shopping-cart"></i> Carrito</button>
        <button onclick="location.href='login.php'"><i class="fas fa-sign-in-alt"></i> Iniciar sesión</button>
    </div>
</header>

<main>
    <div class="formulario-editar" id="formulario-editar">
        <h2>Editar Producto</h2>
        <div id="mensaje" style="display:none;"></div>
        <div id="loading" class="loading">
            <i class="fas fa-spinner fa-spin"></i> Cargando datos del producto...
        </div>
        <form id="editarForm" enctype="multipart/form-data" method="post" style="display:none;">
            <label for="nombre">Nombre del Producto:</label>
            <input type="text" name="nombre" id="nombre" required>

            <label for="precio">Precio:</label>
            <input type="number" step="0.01" name="precio" id="precio" required min="0">

            <label for="descripcion">Descripción:</label>
            <textarea name="descripcion" id="descripcion" rows="4"></textarea>

            <label for="stock">Stock:</label>
            <input type="number" name="stock" id="stock" min="0" required>

            <label for="id_categoria">Categoría:</label>
            <select name="id_categoria" id="id_categoria" required>
                <option value="">Seleccione una categoría...</option>
            </select>

            <label for="imagen">Cambiar Imagen:</label>
            <img src="" alt="Imagen actual" id="imagenActual" style="display: none;" />
            <input type="file" name="imagen" id="imagen" accept="image/*">
            <small style="color: #666;">Formatos permitidos: JPG, PNG, GIF, WebP. Máximo 5MB.</small>

            <button type="submit">Guardar Cambios</button>
            <button type="button" onclick="window.history.back()" style="background: #6c757d; margin-left: 10px;">Cancelar</button>
        </form>
    </div>
</main>

<script>
const productoId = <?= isset($_GET['id']) ? intval($_GET['id']) : 0 ?>;
const jwtToken = "<?= $_SESSION['token'] ?? '' ?>";
const esAdminOVendedor = <?= $esAdminOVendedor ? 'true' : 'false' ?>;

function showMessage(text, isError = false) {
    const mensajeDiv = document.getElementById('mensaje');
    mensajeDiv.style.display = 'block';
    mensajeDiv.textContent = text;
    mensajeDiv.className = `mensaje-${isError ? 'error' : 'exito'}`;
    setTimeout(() => mensajeDiv.style.display = 'none', 5000);
}

function hideLoading() {
    document.getElementById('loading').style.display = 'none';
    document.getElementById('editarForm').style.display = 'block';
}

function showLoading() {
    document.getElementById('loading').style.display = 'block';
    document.getElementById('editarForm').style.display = 'none';
}

async function cargarProducto() {
    if (productoId <= 0) {
        showMessage("ID de producto inválido", true);
        return;
    }

    try {
        console.log(`Cargando producto ID: ${productoId}`);
        const res = await fetch(`api/productos.php?id=${productoId}`);
        
        if (!res.ok) {
            throw new Error(`Error HTTP: ${res.status}`);
        }
        
        const data = await res.json();
        console.log('Datos recibidos:', data);
        
        if (!data.success) {
            throw new Error(data.error || "Error al obtener los datos del producto");
        }
        
        const p = data.producto;
        if (!p) {
            throw new Error("No se encontraron datos del producto");
        }

        // Llenar los campos del formulario
        document.getElementById('nombre').value = p.nombre_Producto || '';
        document.getElementById('precio').value = p.Precio ? parseFloat(p.Precio).toFixed(2) : '0.00';
        document.getElementById('descripcion').value = p.descripcion || '';
        document.getElementById('stock').value = p.stock ? parseInt(p.stock) : 0;

        // Cargar categorías y seleccionar la actual
        await cargarCategorias(p.id_categoria);

        // Mostrar imagen actual si existe
        const imgElement = document.getElementById('imagenActual');
        if (p.url && p.url.trim() !== '') {
            imgElement.src = p.url;
            imgElement.style.display = 'block';
        } else {
            imgElement.style.display = 'none';
        }

        hideLoading();
        
    } catch (err) {
        console.error('Error al cargar producto:', err);
        showMessage(`Error al cargar los datos del producto: ${err.message}`, true);
        hideLoading();
    }
}

async function cargarCategorias(categoriaSeleccionada = null) {
    try {
        console.log('Cargando categorías...');
        const catRes = await fetch('api/listar_categorias.php');
        
        if (!catRes.ok) {
            throw new Error(`Error HTTP: ${catRes.status}`);
        }
        
        const catData = await catRes.json();
        console.log('Categorías recibidas:', catData);
        
        const categoriaSelect = document.getElementById('id_categoria');
        
        // Limpiar opciones excepto la primera
        while (categoriaSelect.children.length > 1) {
            categoriaSelect.removeChild(categoriaSelect.lastChild);
        }
        
        if (catData.success && Array.isArray(catData.categorias)) {
            catData.categorias.forEach(cat => {
                const option = document.createElement('option');
                option.value = cat.id_categoria;
                option.textContent = cat.nombre;
                if (categoriaSeleccionada && cat.id_categoria == categoriaSeleccionada) {
                    option.selected = true;
                }
                categoriaSelect.appendChild(option);
            });
        } else {
            throw new Error("Formato de respuesta de categorías inválido");
        }
        
    } catch (err) {
        console.error('Error al cargar categorías:', err);
        showMessage("No se pudieron cargar las categorías", true);
    }
}

// Event listener para el formulario
document.getElementById('editarForm').addEventListener('submit', async e => {
    e.preventDefault();
    
    if (!esAdminOVendedor) {
        showMessage("No tienes permisos para editar este producto.", true);
        return;
    }

    const submitButton = e.target.querySelector('button[type="submit"]');
    const originalText = submitButton.textContent;
    submitButton.disabled = true;
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';

    try {
        const form = new FormData(document.getElementById('editarForm'));
        console.log('Enviando formulario...');
        
        const response = await fetch(`api/editar_producto.php?id=${productoId}`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${jwtToken}`
            },
            body: form
        });

        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status}`);
        }

        const result = await response.json();
        console.log('Respuesta del servidor:', result);
        
        if (result.success) {
            showMessage("Producto actualizado correctamente.", false);
            setTimeout(() => {
                showLoading();
                cargarProducto();
            }, 1000);
        } else {
            throw new Error(result.error || "Error desconocido al actualizar");
        }
        
    } catch (err) {
        console.error('Error al actualizar producto:', err);
        showMessage(`Error al actualizar el producto: ${err.message}`, true);
    } finally {
        submitButton.disabled = false;
        submitButton.textContent = originalText;
    }
});

// Preview de imagen
document.getElementById('imagen').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            showMessage("Tipo de archivo no permitido. Use JPG, PNG, GIF o WebP", true);
            e.target.value = '';
            return;
        }
        if (file.size > 5 * 1024 * 1024) {
            showMessage("El archivo es demasiado grande. Máximo 5MB", true);
            e.target.value = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            const imgElement = document.getElementById('imagenActual');
            imgElement.src = e.target.result;
            imgElement.style.display = 'block';
        };
        reader.readAsDataURL(file);
    }
});

// Inicializar la página
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM cargado');
    console.log('Producto ID:', productoId);
    console.log('Es admin o vendedor:', esAdminOVendedor);
    
    if (!esAdminOVendedor) {
        showMessage("No tienes permisos para editar productos.", true);
        document.getElementById('editarForm').style.display = 'none';
        document.getElementById('loading').style.display = 'none';
        return;
    }
    
    if (productoId <= 0) {
        showMessage("ID de producto inválido", true);
        document.getElementById('loading').style.display = 'none';
        return;
    }
    
    cargarProducto();
});
</script>

</body>
</html>