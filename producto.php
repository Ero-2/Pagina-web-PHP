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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de producto | Mi E-Commerce</title>
    <link rel="stylesheet" href="estilos/producto.css">
    <link rel="stylesheet" href="estilos/carrito.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .sin-stock {
            color: #dc3545;
            font-weight: bold;
            background-color: #f8d7da;
            padding: 8px 12px;
            border-radius: 5px;
            border: 1px solid #f5c6cb;
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
            text-decoration: none;
            transition: background-color 0.3s;
        }
        .boton-editar:hover {
            background-color: #1a58c3;
        }
        .info-producto {
            display: flex;
            gap: 20px;
            align-items: flex-start;
            flex-wrap: wrap;
        }
        .info-producto img {
            max-width: 300px;
            width: 100%;
            height: auto;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .acciones button.boton-editar {
            margin-left: 10px;
        }
        .producto-info {
            flex: 1;
            min-width: 300px;
        }
        .cantidad-selector {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 15px 0;
        }
        .cantidad-input {
            width: 60px;
            padding: 5px;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .btn-cantidad {
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            padding: 5px 10px;
            cursor: pointer;
            border-radius: 4px;
            user-select: none;
        }
        .btn-cantidad:hover {
            background-color: #e9ecef;
        }
        .btn-agregar-carrito {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .btn-agregar-carrito:hover {
            background-color: #218838;
        }
        .btn-agregar-carrito:disabled {
            background-color: #6c757d;
            cursor: not-allowed;
        }
        .precio-producto {
            font-size: 24px;
            color: #2575fc;
            font-weight: bold;
            margin: 10px 0;
        }
        .stock-info {
            color: #28a745;
            font-weight: 500;
        }
        .loading {
            text-align: center;
            padding: 20px;
            font-size: 18px;
        }
        .error {
            color: #dc3545;
            background-color: #f8d7da;
            padding: 12px;
            border-radius: 5px;
            border: 1px solid #f5c6cb;
            margin: 10px 0;
        }
        .success {
            color: #155724;
            background-color: #d4edda;
            padding: 12px;
            border-radius: 5px;
            border: 1px solid #c3e6cb;
            margin: 10px 0;
        }
        @media (max-width: 768px) {
            .info-producto {
                flex-direction: column;
            }
            .header .acciones {
                flex-wrap: wrap;
                gap: 5px;
            }
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
    <div class="loading" id="loading">
        <i class="fas fa-spinner fa-spin"></i> Cargando producto...
    </div>
    
    <div class="producto-detalle" id="detalle-producto" style="display: none;"></div>

    <div class="comentarios">
        <h3>Comentarios</h3>
        <div id="lista-comentarios"></div>

        <?php if (isset($_SESSION['nombre_usuario'])): ?>
        <h4>Deja tu comentario:</h4>
        <form id="form-comentario">
            <input type="hidden" name="id_producto" value="<?= $id_producto ?>">
            <input type="hidden" name="id_usuario" value="<?= $_SESSION['id_usuario'] ?? 0 ?>">

            <textarea name="comentario" placeholder="Escribe tu comentario aquí..." required style="width: 100%; min-height: 100px; padding: 10px; border-radius: 5px; border: 1px solid #ddd;"></textarea>
            <br><br>
            <label>Calificación:
                <select name="calificacion" required style="padding: 5px; border-radius: 4px;">
                    <option value="">Selecciona una calificación</option>
                    <option value="5">5 ⭐⭐⭐⭐⭐ Excelente</option>
                    <option value="4">4 ⭐⭐⭐⭐ Muy bueno</option>
                    <option value="3">3 ⭐⭐⭐ Bueno</option>
                    <option value="2">2 ⭐⭐ Regular</option>
                    <option value="1">1 ⭐ Malo</option>
                </select>
            </label>
            <br><br>
            <button type="submit" style="background-color: #2575fc; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">
                <i class="fas fa-paper-plane"></i> Enviar comentario
            </button>
        </form>
        <?php else: ?>
        <p><a href="login.php">Inicia sesión para dejar un comentario</a></p>
        <?php endif; ?>
    </div>
</main>

<script>
const productoId = <?= $id_producto ?>;
const jwtToken = "<?= $jwt_token ?>";
const esAdminOVendedor = <?= $esAdminOVendedor ? 'true' : 'false' ?>;

let productoActual = null;
let cantidadSeleccionada = 1;

// Función para mostrar mensajes
function mostrarMensaje(mensaje, tipo = 'info') {
    const existingAlert = document.querySelector('.alert-message');
    if (existingAlert) {
        existingAlert.remove();
    }

    const alertDiv = document.createElement('div');
    alertDiv.className = `alert-message ${tipo}`;
    alertDiv.innerHTML = `<i class="fas fa-${tipo === 'error' ? 'exclamation-triangle' : tipo === 'success' ? 'check-circle' : 'info-circle'}"></i> ${mensaje}`;
    
    document.querySelector('main').insertBefore(alertDiv, document.querySelector('main').firstChild);
    
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

// Función para realizar peticiones con manejo de errores
async function fetchWithErrorHandling(url, options = {}) {
    try {
        const response = await fetch(url, options);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const contentType = response.headers.get("content-type");
        if (contentType && contentType.includes("application/json")) {
            return await response.json();
        } else {
            throw new Error('Respuesta no es JSON válido');
        }
    } catch (error) {
        console.error('Error en petición:', error);
        throw error;
    }
}

function cargarProducto() {
    const loading = document.getElementById('loading');
    const detalle = document.getElementById('detalle-producto');
    
    loading.style.display = 'block';
    detalle.style.display = 'none';

    fetchWithErrorHandling(`api/producto.php?id=${productoId}`)
        .then(data => {
            if (!data || !data.id_Producto) {
                throw new Error('Producto no encontrado');
            }

            productoActual = data;
            renderizarProducto(data);
            
            loading.style.display = 'none';
            detalle.style.display = 'block';
        })
        .catch(error => {
            console.error("Error al cargar producto:", error);
            loading.innerHTML = `<div class="error">
                <i class="fas fa-exclamation-triangle"></i> 
                Error al cargar el producto: ${error.message}
                <br><br>
                <button onclick="cargarProducto()" style="background-color: #2575fc; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;">
                    <i class="fas fa-redo"></i> Reintentar
                </button>
            </div>`;
        });
}

function renderizarProducto(p) {
    const stockDisponible = p.stock > 0;
    const maxCantidad = Math.min(p.stock, 10); // Máximo 10 unidades por compra

    let html = `
        <div class="info-producto">
            <img src="${p.url || 'imagenes/default.jpg'}" alt="${p.nombre_Producto}" 
                 onerror="this.src='imagenes/default.jpg'" />
            <div class="producto-info">
                <h2>${p.nombre_Producto}</h2>
                <div class="precio-producto">$${parseFloat(p.Precio).toFixed(2)}</div>
                <div class="stock-info">
                    <strong>Stock:</strong> 
                    ${stockDisponible ? 
                        `<span style="color: #28a745;">${p.stock} unidades disponibles</span>` : 
                        `<span class="sin-stock">Producto agotado</span>`
                    }
                </div>
                <div style="margin: 15px 0;">
                    <strong>Descripción:</strong>
                    <p style="margin-top: 8px; line-height: 1.5;">${p.descripcion || 'Sin descripción disponible'}</p>
                </div>`;

    // Selector de cantidad y botón de agregar al carrito
    if (jwtToken) {
        if (stockDisponible) {
            html += `
                <div class="cantidad-selector">
                    <label><strong>Cantidad:</strong></label>
                    <button class="btn-cantidad" onclick="cambiarCantidad(-1)">-</button>
                    <input type="number" class="cantidad-input" id="cantidad" value="1" min="1" max="${maxCantidad}" 
                           onchange="validarCantidad()" onkeyup="validarCantidad()">
                    <button class="btn-cantidad" onclick="cambiarCantidad(1)">+</button>
                    <span style="font-size: 12px; color: #666;">Máx: ${maxCantidad}</span>
                </div>
                <button class="btn-agregar-carrito" onclick="agregarAlCarrito(${p.id_Producto})" id="btn-agregar">
                    <i class="fas fa-shopping-cart"></i> Agregar al carrito
                </button>`;
        } else {
            html += `<div class="sin-stock">
                <i class="fas fa-times-circle"></i> Producto no disponible
            </div>`;
        }
    } else {
        html += `<div style="margin: 15px 0;">
            <a href="login.php" style="background-color: #2575fc; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block;">
                <i class="fas fa-sign-in-alt"></i> Inicia sesión para comprar
            </a>
        </div>`;
    }

    // Botón editar (solo si es admin o vendedor)
    if (esAdminOVendedor) {
        html += `
            <div style="margin-top: 15px;">
                <a href="editar_producto.php?id=${p.id_Producto}" class="boton-editar">
                    <i class="fas fa-edit"></i> Editar Producto
                </a>
            </div>`;
    }

    html += `</div></div>`;
    document.getElementById('detalle-producto').innerHTML = html;
}

function cambiarCantidad(delta) {
    const input = document.getElementById('cantidad');
    const nuevaCantidad = parseInt(input.value) + delta;
    const maxCantidad = parseInt(input.max);
    
    if (nuevaCantidad >= 1 && nuevaCantidad <= maxCantidad) {
        input.value = nuevaCantidad;
        cantidadSeleccionada = nuevaCantidad;
    }
}

function validarCantidad() {
    const input = document.getElementById('cantidad');
    const cantidad = parseInt(input.value);
    const maxCantidad = parseInt(input.max);
    
    if (isNaN(cantidad) || cantidad < 1) {
        input.value = 1;
        cantidadSeleccionada = 1;
    } else if (cantidad > maxCantidad) {
        input.value = maxCantidad;
        cantidadSeleccionada = maxCantidad;
        mostrarMensaje(`Cantidad máxima disponible: ${maxCantidad}`, 'error');
    } else {
        cantidadSeleccionada = cantidad;
    }
}

function cargarComentarios() {
    fetchWithErrorHandling(`api/comentarios.php?id_producto=${productoId}`)
        .then(data => {
            const contenedor = document.getElementById('lista-comentarios');
            
            if (!data.success) {
                contenedor.innerHTML = `<div class="error">Error al cargar comentarios: ${data.message}</div>`;
                return;
            }

            const comentarios = data.comentarios || [];
            if (comentarios.length === 0) {
                contenedor.innerHTML = '<p style="color: #666; font-style: italic;">No hay comentarios aún. ¡Sé el primero en comentar!</p>';
                return;
            }

            const promedio = comentarios.reduce((sum, c) => sum + parseInt(c.calificacion), 0) / comentarios.length;
            const estrellasPromedio = '⭐'.repeat(Math.round(promedio));

            contenedor.innerHTML = `
                <div style="background-color: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <h4 style="margin: 0 0 10px 0;">Calificación promedio: ${promedio.toFixed(1)} ${estrellasPromedio} (${comentarios.length} comentario${comentarios.length !== 1 ? 's' : ''})</h4>
                </div>
                ${comentarios.map(c => `
                    <div class="comentario" style="background-color: #fff; border: 1px solid #e9ecef; border-radius: 8px; padding: 15px; margin-bottom: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                            <strong style="color: #2575fc;">${c.nombre}</strong>
                            <span style="color: #ffc107; font-size: 18px;">${'⭐'.repeat(parseInt(c.calificacion))}</span>
                        </div>
                        <p style="margin: 10px 0; line-height: 1.5;">${c.comentario}</p>
                        <small style="color: #6c757d;">
                            <i class="fas fa-clock"></i> ${new Date(c.fecha_Comentario).toLocaleString('es-ES')}
                        </small>
                    </div>
                `).join('')}
            `;
        })
        .catch(error => {
            console.error("Error al cargar comentarios:", error);
            document.getElementById('lista-comentarios').innerHTML = 
                '<div class="error">Error al cargar comentarios. Por favor, recarga la página.</div>';
        });
}

async function agregarAlCarrito(idProducto) {
    if (!jwtToken) {
        mostrarMensaje("Debes iniciar sesión primero.", 'error');
        setTimeout(() => location.href = 'login.php', 2000);
        return;
    }

    const btnAgregar = document.getElementById('btn-agregar');
    const cantidadInput = document.getElementById('cantidad');
    const cantidad = parseInt(cantidadInput.value) || 1;

    // Deshabilitar botón temporalmente
    btnAgregar.disabled = true;
    btnAgregar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Agregando...';

    try {
        const data = await fetchWithErrorHandling('api/carrito.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + jwtToken
            },
            body: JSON.stringify({
                accion: 'agregar',
                id_producto: idProducto,
                cantidad: cantidad
            })
        });

        if (data.success) {
            mostrarMensaje(`✅ ${data.producto || 'Producto'} agregado al carrito (${cantidad} unidad${cantidad !== 1 ? 'es' : ''})`, 'success');
            
            // Actualizar stock mostrado si es necesario
            if (productoActual) {
                productoActual.stock -= cantidad;
                if (productoActual.stock <= 0) {
                    // Recargar la página si se agotó el stock
                    setTimeout(() => location.reload(), 1500);
                } else {
                    // Actualizar la información de stock
                    const stockInfo = document.querySelector('.stock-info span');
                    if (stockInfo) {
                        stockInfo.textContent = `${productoActual.stock} unidades disponibles`;
                    }
                    
                    // Actualizar máximo en input
                    const maxCantidad = Math.min(productoActual.stock, 10);
                    cantidadInput.max = maxCantidad;
                    if (parseInt(cantidadInput.value) > maxCantidad) {
                        cantidadInput.value = maxCantidad;
                    }
                }
            }
        } else {
            mostrarMensaje("❌ " + (data.message || 'Error al agregar al carrito'), 'error');
        }
    } catch (error) {
        console.error("Error al agregar al carrito:", error);
        mostrarMensaje("❌ Error de conexión: " + error.message, 'error');
    } finally {
        // Rehabilitar botón
        btnAgregar.disabled = false;
        btnAgregar.innerHTML = '<i class="fas fa-shopping-cart"></i> Agregar al carrito';
    }
}

// Manejar envío de comentarios
document.getElementById('form-comentario')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    if (!jwtToken) {
        mostrarMensaje("Debes iniciar sesión para comentar.", 'error');
        return;
    }

    const formData = new FormData(e.target);
    const payload = {
        id_producto: parseInt(formData.get('id_producto')),
        comentario: formData.get('comentario').trim(),
        calificacion: parseInt(formData.get('calificacion'))
    };

    if (!payload.comentario) {
        mostrarMensaje("El comentario no puede estar vacío.", 'error');
        return;
    }

    if (!payload.calificacion) {
        mostrarMensaje("Debes seleccionar una calificación.", 'error');
        return;
    }

    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';

    try {
        const data = await fetchWithErrorHandling('api/comentarios.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + jwtToken
            },
            body: JSON.stringify(payload)
        });

        if (data.success) {
            mostrarMensaje("✅ Comentario guardado correctamente", 'success');
            cargarComentarios();
            e.target.reset();
        } else {
            mostrarMensaje("❌ " + (data.message || 'Error al guardar comentario'), 'error');
        }
    } catch (error) {
        console.error("Error al enviar comentario:", error);
        mostrarMensaje("❌ Error al enviar el comentario: " + error.message, 'error');
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    }
});

// Validar ID de producto
if (productoId <= 0) {
    document.getElementById('loading').innerHTML = `
        <div class="error">
            <i class="fas fa-exclamation-triangle"></i> 
            ID de producto inválido
            <br><br>
            <button onclick="location.href='inicio.php'" style="background-color: #2575fc; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;">
                <i class="fas fa-home"></i> Volver al inicio
            </button>
        </div>
    `;
} else {
    // Cargar contenido al inicio
    cargarProducto();
    cargarComentarios();
}

// Manejar errores globales de JavaScript
window.addEventListener('error', (event) => {
    console.error('Error global:', event.error);
});

// Manejar promesas rechazadas no capturadas
window.addEventListener('unhandledrejection', (event) => {
    console.error('Promesa rechazada no manejada:', event.reason);
});
</script>
</body>
</html>