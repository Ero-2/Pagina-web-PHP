<?php
session_start();
require_once 'config/db.php';

$id_producto = isset($_GET['id']) ? intval($_GET['id']) : 0;

$sql = "SELECT p.*, i.url 
        FROM Producto p 
        LEFT JOIN Imagen i ON p.id_Producto = i.id_Producto 
        WHERE p.id_Producto = $id_producto 
        LIMIT 1";
$resultado = $conn->query($sql);
$producto = $resultado->fetch_assoc();

if (!$producto) {
    die("Producto no encontrado");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($producto['nombre_Producto']) ?> | Mi E-Commerce</title>
  <link rel="stylesheet" href="estilos/producto.css">
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
    }
  </style>
</head>
<body>

<header class="header">
  <div class="logo"><i class="fas fa-shopping-bag"></i> Mi E-Commerce</div>

  <form action="buscar.php" method="get" class="busqueda" style="flex-grow: 1; margin: 0 1rem;">
    <input type="text" name="q" placeholder="Buscar productos..." required style="padding: 8px; width: 100%; border-radius: 25px; border: none;">
    <button type="submit" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #2575fc;"><i class="fas fa-search"></i></button>
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

<main>
  <div class="producto-detalle">
    <img src="<?= htmlspecialchars($producto['url']) ?>" alt="Producto">
    <div class="info-producto">
      <h2><?= htmlspecialchars($producto['nombre_Producto']) ?></h2>
      <p><strong>Precio:</strong> $<?= number_format($producto['Precio'], 2) ?></p>
      <p><strong>Stock:</strong> <?= intval($producto['stock']) ?></p>

      <?php if ($producto['stock'] == 0): ?>
        <p class="sin-stock">Producto agotado</p>
      <?php endif; ?>

      <p><strong>Descripción:</strong><br><?= nl2br(htmlspecialchars($producto['descripcion'])) ?></p>

      <?php if ($producto['stock'] > 0): ?>
        <?php if (isset($_SESSION['id_usuario'])): ?>
          <button onclick="agregarAlCarrito(<?= $producto['id_Producto'] ?>)">Agregar al carrito</button>
        <?php else: ?>
          <p><a href="login.php">Inicia sesión para agregar al carrito</a></p>
        <?php endif; ?>
      <?php endif; ?>

      <!-- BOTÓN EDITAR PRODUCTO -->
      <?php if (isset($_SESSION['id_usuario'])): ?>
        <?php
        // Verificar si el usuario es administrador o vendedor
        $id_usuario = $_SESSION['id_usuario'];
        $tipo_usuario_sql = "SELECT id_tipo_de_usuario FROM usuario WHERE id_Usuario = $id_usuario";
        $result_tipo = $conn->query($tipo_usuario_sql);
        $tipo_usuario = $result_tipo->fetch_assoc();

        // Tipos permitidos: por ejemplo, 1 = admin, 2 = vendedor
        $tipos_permitidos = [1, 2]; // Cambia según tus IDs reales
        if (in_array($tipo_usuario['id_tipo_de_usuario'], $tipos_permitidos)): ?>
          <a href="editar_producto.php?id=<?= $producto['id_Producto'] ?>" class="boton-editar">Editar Producto</a>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>

  <div class="comentarios">
    <h3>Comentarios</h3>
    <?php
    $coment_sql = "SELECT c.comentario, c.calificacion, c.fecha_Comentario, u.nombre 
                   FROM Comentario c 
                   JOIN Usuario u ON c.id_Usuario = u.id_Usuario 
                   WHERE c.id_Producto = $id_producto 
                   ORDER BY c.fecha_Comentario DESC";
    $coment_res = $conn->query($coment_sql);
    if ($coment_res->num_rows > 0) {
      while ($coment = $coment_res->fetch_assoc()) {
        echo "<div class='comentario'>";
        echo "<p><strong>" . htmlspecialchars($coment['nombre']) . "</strong> (" . $coment['calificacion'] . "⭐)</p>";
        echo "<p>" . htmlspecialchars($coment['comentario']) . "</p>";
        echo "<small>" . $coment['fecha_Comentario'] . "</small>";
        echo "</div>";
      }
    } else {
      echo "<p>No hay comentarios aún.</p>";
    }
    ?>

    <h4>Deja tu comentario:</h4>
    <form method="post" action="guardar_comentario.php">
      <input type="hidden" name="id_producto" value="<?= $producto['id_Producto'] ?>">
      <input type="hidden" name="id_usuario" value="<?= $_SESSION['id_usuario'] ?? 0 ?>">

      <textarea name="comentario" required></textarea>
      <br>
      <label>Calificación:
        <select name="calificacion" required>
          <option value="5">5 ⭐</option>
          <option value="4">4 ⭐</option>
          <option value="3">3 ⭐</option>
          <option value="2">2 ⭐</option>
          <option value="1">1 ⭐</option>
        </select>
      </label>
      <br><br>
      <input type="submit" value="Enviar comentario">
    </form>
  </div>
</main>

<script>
function agregarAlCarrito(idProducto) {
    const idUsuario = <?= $_SESSION['id_usuario'] ?? 0 ?>;

    if (idUsuario === 0) {
        alert("Debes iniciar sesión para agregar productos al carrito.");
        return;
    }

    fetch('api/carrito.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            accion: 'agregar',
            id_usuario: idUsuario,
            id_producto: idProducto,
            cantidad: 1
        })
    })
    .then(res => res.json())
    .then(data => {
        alert(data.message);
    });
}
</script>

</body>
</html>
