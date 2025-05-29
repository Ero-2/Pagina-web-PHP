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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
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
        <input type="text" name="nombre" id="nombre" required maxlength="255"><br><br>

        <label for="descripcion">Descripción:</label>
        <textarea name="descripcion" id="descripcion" required maxlength="1000"></textarea><br><br>

        <label for="precio">Precio:</label>
        <input type="number" step="0.01" name="precio" id="precio" required min="0.01"><br><br>

        <label for="id_categoria">Categoría:</label>
        <select name="id_categoria" id="id_categoria" required>
            <option value="">-- Selecciona una categoría --</option>
        </select><br><br>

        <label for="imagen">Imagen del Producto:</label>
        <input type="file" name="imagen" id="imagen" accept="image/jpeg,image/png,image/gif,image/webp" required><br><br>

        <button type="submit" id="submitButton">
            <i class="fas fa-plus-circle"></i> Agregar Producto
        </button>
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
    text-align: center;
}
.alert.success {
    background-color: #27ae60;
    border: 2px solid #2ecc71;
}
.alert.error {
    background-color: #c0392b;
    border: 2px solid #e74c3c;
}
#submitButton:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}
</style>

<!-- Script principal CORREGIDO -->
<script>
document.addEventListener('DOMContentLoaded', async () => {
    const form = document.getElementById('productoForm');
    const mensaje = document.getElementById('mensaje');
    const submitButton = document.getElementById('submitButton');

    // Función para mostrar mensajes mejorada
    function showMessage(text, isError = false) {
        mensaje.style.display = 'block';
        mensaje.className = `alert ${isError ? 'error' : 'success'}`;
        mensaje.textContent = text;
        
        // Scroll al mensaje
        mensaje.scrollIntoView({ behavior: 'smooth', block: 'center' });
        
        setTimeout(() => {
            mensaje.style.display = 'none';
        }, 6000);
    }

    // Cargar categorías desde la API (sin cambios, funciona bien)
    try {
        console.log('Cargando categorías...');
        const res = await fetch('api/listar_categorias.php');
        
        if (!res.ok) {
            throw new Error(`Error HTTP: ${res.status} - ${res.statusText}`);
        }
        
        const data = await res.json();
        console.log('Respuesta de categorías:', data);

        const categoriaSelect = document.getElementById('id_categoria');
        
        if (data.success && Array.isArray(data.categorias)) {
            categoriaSelect.innerHTML = '<option value="">-- Selecciona una categoría --</option>';

            data.categorias.forEach(cat => {
                const option = document.createElement('option');
                option.value = cat.id_categoria;
                option.textContent = cat.nombre;
                categoriaSelect.appendChild(option);
            });
            
            console.log(`${data.categorias.length} categorías cargadas exitosamente`);
        } else {
            console.warn('No se encontraron categorías:', data.error || 'Sin error específico');
            categoriaSelect.innerHTML = '<option value="" disabled>No hay categorías disponibles</option>';
            showMessage(data.error || 'No se pudieron cargar las categorías.', true);
        }
    } catch (err) {
        console.error('Error al cargar las categorías:', err);
        const categoriaSelect = document.getElementById('id_categoria');
        categoriaSelect.innerHTML = '<option value="" disabled>Error cargando categorías</option>';
        showMessage('Hubo un problema al cargar las categorías: ' + err.message, true);
    }

    // MANEJO DEL FORMULARIO COMPLETAMENTE CORREGIDO
    form.addEventListener('submit', async e => {
        e.preventDefault();
        
        const originalText = submitButton.innerHTML;
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Agregando...';

        try {
            // Crear FormData directamente del formulario
            const formData = new FormData(e.target);
            
            console.log('Datos que se van a enviar:');
            for (let [key, value] of formData.entries()) {
                if (value instanceof File) {
                    console.log(`${key}: ${value.name} (${value.size} bytes, ${value.type})`);
                } else {
                    console.log(`${key}: ${value}`);
                }
            }

            // Configurar headers de autenticación
            const token = localStorage.getItem('token');
            const headers = {};
            
            if (token && token.trim() !== '') {
                headers['Authorization'] = `Bearer ${token}`;
                console.log('Usando token JWT para autenticación');
            } else {
                console.log('Usando sesión PHP para autenticación');
            }

            // NO establecer Content-Type - el navegador lo hace automáticamente para FormData

            // Realizar la petición
            const response = await fetch('api/agregar_producto.php', {
                method: 'POST',
                headers: headers, // Solo Authorization si existe
                body: formData
            });

            console.log('Status de respuesta:', response.status);
            console.log('Headers de respuesta:', Object.fromEntries(response.headers.entries()));

            // VERIFICAR QUE LA RESPUESTA SEA JSON ANTES DE PARSEAR
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const textResponse = await response.text();
                console.error('Respuesta no es JSON:', textResponse);
                throw new Error(`El servidor devolvió ${contentType || 'contenido desconocido'} en lugar de JSON. Posible error PHP.`);
            }

            const result = await response.json();
            console.log('Respuesta del servidor:', result);

            if (result.success) {
                showMessage(result.message || 'Producto agregado exitosamente');
                
                // Limpiar formulario
                form.reset();
                
                // Resetear el select de categorías al estado inicial
                const categoriaSelect = document.getElementById('id_categoria');
                categoriaSelect.selectedIndex = 0;
                
                console.log('Producto agregado:', result.producto);
            } else {
                const errorMsg = result.error || 'Error desconocido al agregar el producto';
                showMessage(errorMsg, true);
                
                // Manejar errores de autenticación
                if (result.code === 401) {
                    setTimeout(() => {
                        if (confirm('Tu sesión expiró. ¿Deseas recargar la página para iniciar sesión nuevamente?')) {
                            window.location.reload();
                        }
                    }, 2000);
                }
            }
        } catch (error) {
            console.error('Error completo:', error);
            
            let errorMessage = 'Error de conexión con el servidor';
            if (error.message.includes('JSON')) {
                errorMessage = 'Error: El servidor devolvió HTML en lugar de JSON. Revisa los logs de PHP.';
            } else if (error.message.includes('Failed to fetch')) {
                errorMessage = 'Error: No se pudo conectar con el servidor. Verifica que la API esté funcionando.';
            } else {
                errorMessage = 'Error: ' + error.message;
            }
            
            showMessage(errorMessage, true);
        } finally {
            // Restaurar botón siempre
            submitButton.disabled = false;
            submitButton.innerHTML = originalText;
        }
    });

    // Validación en tiempo real del archivo
    const inputImagen = document.getElementById('imagen');
    inputImagen.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            // Validar tipo
            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!allowedTypes.includes(file.type)) {
                showMessage('Tipo de archivo no válido. Use JPG, PNG, GIF o WebP.', true);
                this.value = '';
                return;
            }
            
            // Validar tamaño (5MB)
            if (file.size > 5 * 1024 * 1024) {
                showMessage('El archivo es muy grande. Máximo 5MB.', true);
                this.value = '';
                return;
            }
            
            console.log('Archivo válido seleccionado:', file.name, file.size, 'bytes');
        }
    });
});
</script>

</body>
</html>