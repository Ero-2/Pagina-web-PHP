<?php 
session_start(); 
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Cargando producto... | Mi E-Commerce</title>
  <link rel="stylesheet" href="estilos/producto.css">
  <link rel="stylesheet" href="estilos/carrito.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <style>
    .sin-stock { color: red; font-weight: bold; }
    .boton-editar {
      background-color: #2575fc;
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 5px;
      cursor: pointer;
      margin-top: 10px;
    }
    .error-container {
      background-color: #ffebee;
      border: 1px solid #f44336;
      border-radius: 4px;
      padding: 16px;
      margin: 16px;
      color: #d32f2f;
    }
    .loading {
      text-align: center;
      padding: 2rem;
      color: #666;
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
      <button onclick="location.href='logout.php'"><i class="fas fa-sign-out-alt"></i> Cerrar sesión</button>
    <?php else: ?>
      <button onclick="location.href='login.php'"><i class="fas fa-sign-in-alt"></i> Iniciar sesión</button>
    <?php endif; ?>
  </div>
</header>

<main id="producto-container">
  <div class="loading">
    <i class="fas fa-spinner fa-spin"></i>
    <p>Cargando producto...</p>
  </div>
</main>

<section id="comentarios-container">
  <h3>Comentarios</h3>
  <div id="comentarios-lista">
    <div class="loading">
      <i class="fas fa-spinner fa-spin"></i>
      <p>Cargando comentarios...</p>
    </div>
  </div>
  <h4>Deja tu comentario:</h4>
  <form id="formulario-comentario" style="display: none;">
    <textarea name="comentario" required placeholder="Escribe tu comentario aquí..."></textarea><br>
    <label>Calificación:
      <select name="calificacion" required>
        <option value="5">5 ⭐</option>
        <option value="4">4 ⭐</option>
        <option value="3">3 ⭐</option>
        <option value="2">2 ⭐</option>
        <option value="1">1 ⭐</option>
      </select>
    </label><br><br>
    <input type="submit" value="Enviar comentario">
  </form>
</section>

<script>
// Variables globales
const urlParams = new URLSearchParams(window.location.search);
const idProducto = urlParams.get('id');
const token = '<?= $_SESSION['token'] ?? '' ?>';
const idUsuario = <?= $_SESSION['id_usuario'] ?? 'null' ?>;
const tipoUsuario = <?= $_SESSION['id_tipo_de_usuario'] ?? 'null' ?>;

console.log('Variables iniciales:', {
    idProducto,
    token: token ? 'TOKEN_PRESENT' : 'NO_TOKEN',
    idUsuario,
    tipoUsuario
});

// Función para hacer requests con mejor manejo de errores
async function hacerRequest(url, options = {}) {
    try {
        console.log(`Haciendo request a: ${url}`, options);
        
        const response = await fetch(url, {
            ...options,
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            }
        });

        console.log(`Response status: ${response.status}`);
        
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            console.error('Response no es JSON:', text);
            throw new Error(`Respuesta no válida del servidor. Status: ${response.status}`);
        }

        const data = await response.json();
        console.log('Response data:', data);

        if (!response.ok) {
            throw new Error(data.error || `Error HTTP: ${response.status}`);
        }

        return data;
    } catch (error) {
        console.error('Error en request:', error);
        throw error;
    }
}

// Cargar producto con mejor manejo de errores
async function cargarProducto() {
    const container = document.getElementById('producto-container');
    
    if (!idProducto) {
        container.innerHTML = `
            <div class="error-container">
                <h3>Producto no especificado</h3>
                <p>No se ha proporcionado un ID de producto válido.</p>
                <button onclick="location.href='inicio.php'">Volver al inicio</button>
            </div>
        `;
        return;
    }

    try {
        // Intentar cargar el producto SIN token primero (para productos públicos)
        const producto = await hacerRequest(`/api/producto.php?id=${idProducto}`);

        if (!producto || !producto.id_Producto) {
            throw new Error(producto.error || 'Producto no encontrado');
        }

        // Generar HTML del producto
        let editarBtn = '';
        if (tipoUsuario && [1, 2].includes(tipoUsuario)) {
            editarBtn = `<a href="editar_producto.php?id=${producto.id_Producto}" class="boton-editar">
                <i class="fas fa-edit"></i> Editar Producto
            </a>`;
        }

        const stockBadge = producto.stock > 0 
            ? `<span style="color: green;">✅ ${producto.stock} disponibles</span>`
            : `<span class="sin-stock">❌ Producto agotado</span>`;

        const actionButton = producto.stock > 0 && idUsuario 
            ? `<button onclick="agregarAlCarrito(${producto.id_Producto})" class="btn-primary">
                <i class="fas fa-cart-plus"></i> Agregar al carrito
               </button>` 
            : idUsuario 
                ? `<button disabled class="btn-disabled">Producto sin stock</button>`
                : `<a href="login.php" class="btn-secondary">
                    <i class="fas fa-sign-in-alt"></i> Inicia sesión para comprar
                   </a>`;

        const imagenUrl = producto.url && producto.url.trim() !== '' 
            ? producto.url 
            : '/imagenes/placeholder.jpg';

        const html = `
            <div class="producto-detalle">
                <div class="producto-imagen">
                    <img src="${imagenUrl}" alt="${producto.nombre_Producto}" 
                         onerror="this.src='/imagenes/placeholder.jpg'">
                </div>
                <div class="info-producto">
                    <h1>${producto.nombre_Producto}</h1>
                    <div class="precio">
                        <span class="precio-actual">$${Number(producto.Precio).toFixed(2)}</span>
                    </div>
                    <div class="stock-info">
                        <strong>Stock:</strong> ${stockBadge}
                    </div>
                    <div class="descripcion">
                        <h3>Descripción</h3>
                        <p>${producto.descripcion || 'Sin descripción disponible.'}</p>
                    </div>
                    <div class="acciones-producto">
                        ${actionButton}
                        ${editarBtn}
                    </div>
                </div>
            </div>
        `;

        container.innerHTML = html;
        await cargarComentarios();

    } catch (error) {
        console.error("Error al cargar producto:", error);
        container.innerHTML = `
            <div class="error-container">
                <h3>Error al cargar el producto</h3>
                <p>${error.message}</p>
                <p>Esto puede deberse a:</p>
                <ul>
                    <li>El producto no existe</li>
                    <li>Problemas de conexión con el servidor</li>
                    <li>Error en la configuración de la API</li>
                </ul>
                <button onclick="cargarProducto()" class="btn-retry">
                    <i class="fas fa-redo"></i> Reintentar
                </button>
                <button onclick="location.href='inicio.php'" class="btn-secondary">
                    <i class="fas fa-home"></i> Volver al inicio
                </button>
            </div>
        `;
    }
}

// Cargar comentarios
async function cargarComentarios() {
    const container = document.getElementById('comentarios-lista');
    
    try {
        const comentarios = await hacerRequest(`/api/comentarios.php?id_producto=${idProducto}`);
        
        if (!Array.isArray(comentarios) || comentarios.length === 0) {
            container.innerHTML = '<p><i class="fas fa-comment-slash"></i> No hay comentarios aún. ¡Sé el primero en comentar!</p>';
            mostrarFormularioComentario();
            return;
        }

        container.innerHTML = '';
        comentarios.forEach(c => {
            const div = document.createElement('div');
            div.className = 'comentario';
            div.innerHTML = `
                <div class="comentario-header">
                    <strong>${c.nombre}</strong>
                    <span class="calificacion">${'⭐'.repeat(c.calificacion)}</span>
                </div>
                <p class="comentario-texto">${c.comentario}</p>
                <small class="comentario-fecha">
                    <i class="fas fa-calendar"></i> ${new Date(c.fecha_Comentario).toLocaleDateString()}
                </small>
            `;
            container.appendChild(div);
        });

        mostrarFormularioComentario();
        
    } catch (error) {
        console.error('Error al cargar comentarios:', error);
        container.innerHTML = `
            <p style="color: #f44336;">
                <i class="fas fa-exclamation-triangle"></i> 
                Error al cargar comentarios: ${error.message}
            </p>
        `;
        mostrarFormularioComentario();
    }
}

// Mostrar formulario de comentarios
function mostrarFormularioComentario() {
    if (idUsuario) {
        const form = document.getElementById('formulario-comentario');
        form.style.display = 'block';

        // Remover event listeners previos
        const newForm = form.cloneNode(true);
        form.parentNode.replaceChild(newForm, form);

        newForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('input[type="submit"]');
            const originalText = submitBtn.value;
            
            try {
                submitBtn.value = 'Enviando...';
                submitBtn.disabled = true;

                const data = await hacerRequest('/api/comentarios.php', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id_producto: idProducto,
                        id_usuario: idUsuario,
                        comentario: formData.get('comentario'),
                        calificacion: formData.get('calificacion')
                    })
                });

                alert(data.success ? data.message : 'Error al enviar el comentario');
                if (data.success) {
                    this.reset();
                    await cargarComentarios();
                }
                
            } catch (error) {
                console.error('Error al enviar comentario:', error);
                alert(`Error al enviar comentario: ${error.message}`);
            } finally {
                submitBtn.value = originalText;
                submitBtn.disabled = false;
            }
        });
    }
}

// Agregar al carrito
async function agregarAlCarrito(idProducto) {
    if (!token) {
        alert('Debes iniciar sesión para agregar productos al carrito');
        location.href = 'login.php';
        return;
    }

    try {
        const data = await hacerRequest('/api/carrito.php', {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                accion: 'agregar',
                id_producto: idProducto,
                cantidad: 1
            })
        });

        alert(data.success ? data.message : `Error: ${data.error || 'Error desconocido'}`);
        
    } catch (error) {
        console.error('Error al agregar al carrito:', error);
        alert(`Error al agregar al carrito: ${error.message}`);
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener("DOMContentLoaded", async () => {
    console.log('DOM cargado, iniciando carga de producto...');
    await cargarProducto();
});
</script>

</body>
</html>