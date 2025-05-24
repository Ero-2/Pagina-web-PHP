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
    <div id="carrito-container"></div>
    <div class="total-carrito" id="total-container"></div>
  </section>

  <section class="historial-compras">
    <h2>Tus Compras Realizadas</h2>
    <div id="historial-compras-container"></div>
  </section>
</main>

<script>
const token = localStorage.getItem('token');

document.addEventListener('DOMContentLoaded', () => {
  cargarCarrito();
  cargarHistorial();
});

function cargarCarrito() {
  fetch('api/carrito.php', {
    method: 'GET',
    headers: {
      'Authorization': 'Bearer ' + token
    }
  })
  .then(res => res.json())
  .then(data => {
    const container = document.getElementById('carrito-container');
    const totalContainer = document.getElementById('total-container');

    if (data.success && data.productos.length > 0) {
      let total = 0;
      data.productos.forEach(p => {
        total += p.Precio * p.cantidad;
        container.innerHTML += `
          <div class="carrito-card">
            <img src="${p.url || 'img/placeholder.jpg'}" class="carrito-img">
            <div class="carrito-info">
              <p class="product-name">${p.nombre_Producto}</p>
              <p>Precio: $${p.Precio.toFixed(2)}</p>
              <p>Cantidad: ${p.cantidad}</p>
              <p><strong>Total: $${(p.Precio * p.cantidad).toFixed(2)}</strong></p>
              <button class="btn-eliminar" onclick="eliminarDelCarrito(${p.id_Producto})">
                <i class="fas fa-trash-alt"></i> Eliminar
              </button>
            </div>
          </div>
        `;
      });
      totalContainer.innerHTML = `
        <strong>Total: $${total.toFixed(2)}</strong>
        <button onclick="finalizarCompra()"><i class="fas fa-check-circle"></i> Finalizar Compra</button>
      `;
    } else {
      container.innerHTML = `
        <div class="carrito-vacio">
          <i class="fas fa-shopping-cart fa-4x"></i>
          <p>Tu carrito está vacío. <a href="inicio.php">Volver a comprar</a></p>
        </div>`;
    }
  });
}

function cargarHistorial() {
  fetch('api/compras.php', {
    method: 'GET',
    headers: {
      'Authorization': 'Bearer ' + token
    }
  })
  .then(res => res.json())
  .then(data => {
    const container = document.getElementById('historial-compras-container');

    if (data.success && data.compras.length > 0) {
      data.compras.forEach(compra => {
        container.innerHTML += `
          <div class="compra-card">
            <p class="product-name">${compra.nombre_Producto}</p>
            <p>Cantidad: ${compra.cantidad}</p>
            <p>Precio: $${compra.Precio.toFixed(2)}</p>
            <p>Subtotal: $${compra.subtotal.toFixed(2)}</p>
            <p>Fecha: ${compra.fecha_Pedido}</p>
          </div>
        `;
      });
    } else {
      container.innerHTML = `
        <div class="no-compras">
          <i class="fas fa-shopping-bag fa-3x"></i>
          <p>No tienes compras realizadas.</p>
        </div>`;
    }
  });
}

function eliminarDelCarrito(idProducto) {
  fetch('api/carrito.php', {
    method: 'DELETE',
    headers: {
      'Authorization': 'Bearer ' + token,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ id_producto: idProducto })
  })
  .then(res => res.json())
  .then(data => {
    alert(data.message);
    if (data.success) location.reload();
  });
}

function finalizarCompra() {
  fetch('api/comprar.php', {
    method: 'POST',
    headers: {
      'Authorization': 'Bearer ' + token,
      'Content-Type': 'application/json'
    }
  })
  .then(res => res.json())
  .then(data => {
    alert(data.message);
    if (data.success) location.href = "gracias.php";
  });
}
</script>

</body>
</html>

