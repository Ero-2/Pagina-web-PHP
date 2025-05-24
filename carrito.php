<?php
session_start();
if (!isset($_SESSION['token'])) {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Carrito de Compras</title>
  <link rel="stylesheet" href="estilos/carrito.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<header class="header">
  <div class="logo"><i class="fas fa-shopping-bag"></i> Mi E-Commerce</div>
  <div class="acciones">
    <button onclick="location.href='inicio.php'"><i class="fas fa-shopping-cart"></i> Continuar comprando</button>
    <button onclick="location.href='inicio.php'"><i class="fas fa-home"></i> Inicio</button>
  </div>
</header>

<main class="main-content">
  <section class="carrito-section">
    <h2>Tu Carrito</h2>
    <div id="carrito-items" class="carrito-items"></div>
    <div class="total-carrito" id="carrito-total"></div>
  </section>

  <section class="historial-compras-section">
    <h2>Historial de Compras</h2>
    <div id="historial-compras"></div>
  </section>
</main>

<script>
const token = "<?= $_SESSION['token'] ?>";

async function cargarCarrito() {
  const res = await fetch('api/carrito.php', {
    headers: { 'Authorization': 'Bearer ' + token }
  });
  const data = await res.json();

  const contenedor = document.getElementById('carrito-items');
  const totalContainer = document.getElementById('carrito-total');
  contenedor.innerHTML = '';
  totalContainer.innerHTML = '';

  if (!data.success || data.carrito.length === 0) {
    contenedor.innerHTML = `<div class="carrito-vacio">
      <i class="fas fa-shopping-cart fa-4x"></i>
      <p>Tu carrito está vacío. <a href="inicio.php">Volver a comprar</a></p>
    </div>`;
    return;
  }

  let total = 0;
  data.carrito.forEach(p => {
    const subtotal = p.Precio * p.cantidad;
    total += subtotal;

    contenedor.innerHTML += `
      <div class="carrito-card">
        <img src="${p.url || 'img/placeholder.jpg'}" alt="${p.nombre_Producto}" class="carrito-img">
        <div class="carrito-info">
          <div>
            <p class="product-name">${p.nombre_Producto}</p>
            <p>Precio: $${p.Precio.toFixed(2)}</p>
            <p>Cantidad: ${p.cantidad}</p>
            <p><strong>Total: $${subtotal.toFixed(2)}</strong></p>
          </div>
          <button class="btn-eliminar" onclick="eliminarDelCarrito(${p.id_Producto})">
            <i class="fas fa-trash-alt"></i> Eliminar
          </button>
        </div>
      </div>`;
  });

  totalContainer.innerHTML = `
    <strong>Total: $${total.toFixed(2)}</strong>
    <button id="btn-comprar" onclick="finalizarCompra()">
      <i class="fas fa-check-circle"></i> Finalizar Compra
    </button>`;
}

async function eliminarDelCarrito(idProducto) {
  const res = await fetch('api/carrito.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
    body: JSON.stringify({ accion: 'eliminar', id_producto: idProducto })
  });
  const data = await res.json();
  if (data.success) {
    alert('Producto eliminado');
    cargarCarrito();
  } else {
    alert('Error al eliminar el producto.');
  }
}

async function finalizarCompra() {
  const res = await fetch('api/comprar.php', {
    method: 'POST',
    headers: { 'Authorization': 'Bearer ' + token }
  });
  const data = await res.json();
  if (data.success) {
    alert('Compra realizada con éxito');
    location.href = 'gracias.php';
  } else {
    alert('Error al procesar compra: ' + data.message);
  }
}

async function cargarHistorialCompras() {
  const res = await fetch("api/compras.php", {
    headers: {
      "Authorization": "Bearer " + token
    }
  });
  const data = await res.json();

  const container = document.getElementById("historial-compras");
  container.innerHTML = '';

  if (!data.success || !data.compras || data.compras.length === 0) {
    container.innerHTML = "<p>No tienes compras anteriores.</p>";
    return;
  }

  data.compras.forEach(pedido => {
    // Ajusta según cómo envíe tu API la fecha y el estado
    const fecha = new Date(pedido.fecha_Pedido || pedido.fecha).toLocaleString();
    const estado = pedido.estado_pedido || pedido.estado || 'Desconocido';

    let html = `
      <div class="card border p-3 mb-3">
        <h4>Pedido del ${fecha}</h4>
        <p><strong>Estado:</strong> ${estado}</p>
        <ul>`;

    // Asegúrate que tu API envía un array con los productos del pedido
    (pedido.productos || []).forEach(p => {
      html += `<li>${p.nombre_Producto || p.nombre} — ${p.cantidad} × $${p.precio_unitario || p.Precio} = $${p.subtotal.toFixed(2)}</li>`;
    });

    html += `</ul>
        <p><strong>Total del pedido:</strong> $${pedido.total.toFixed(2)}</p>
      </div>
    `;

    container.innerHTML += html;
  });
}

document.addEventListener('DOMContentLoaded', () => {
  cargarCarrito();
  cargarHistorialCompras();  // <== Ejecutamos la carga del historial al inicio
});
</script>

</body>
</html>
