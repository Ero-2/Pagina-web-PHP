<?php
session_start();
require_once 'config/db.php';

$termino = isset($_GET['q']) ? $conn->real_escape_string($_GET['q']) : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Resultados de búsqueda</title>
  <link rel="stylesheet" href="estilos/inicio.css">
</head>
<body>

<header>
  <div class="logo">🛍️ Mi E-Commerce</div>
  <div class="acciones"><button onclick="location.href='inicio.php'">inicio</button></div>   
  <form action="buscar.php" method="get" class="busqueda">
    <input type="text" name="q" value="<?= htmlspecialchars($termino) ?>" placeholder="Buscar productos...">
    <button type="submit">🔍</button>
  </form>

  <div class="acciones">
    <button onclick="location.href='carrito.php'">🛒 Carrito</button>
    
    <?php if (isset($_SESSION['nombre_usuario'])): ?>
      <span>👤 <?= htmlspecialchars($_SESSION['nombre_usuario']) ?></span>
      
      <!-- Botón para agregar producto solo si el usuario es admin o vendedor -->
      <?php if ($_SESSION['id_tipo_de_usuario'] == 1 || $_SESSION['id_tipo_de_usuario'] == 2): ?>
        <button onclick="location.href='agregar_producto.php'">Agregar Producto</button>
      <?php endif; ?>
      
      <button onclick="location.href='logout.php'">Cerrar sesión</button>
    <?php else: ?>
      <button onclick="location.href='login.php'">Iniciar sesión</button>
    <?php endif; ?>
  </div>
</header>

<main>
  <h2>Resultados para: "<?= htmlspecialchars($termino) ?>"</h2>

  <div class="productos">
    <?php
    if ($termino) {
        $sql = "SELECT p.*, i.url 
                FROM Producto p 
                LEFT JOIN Imagen i ON p.id_Producto = i.id_Producto 
                WHERE p.nombre_Producto LIKE ? OR p.descripcion LIKE ?";
        $like = "%$termino%";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $like, $like);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                echo '<div class="producto">';
                echo '<a href="producto.php?id=' . $row['id_Producto'] . '">';
                echo '<img src="' . htmlspecialchars($row['url']) . '" alt="' . htmlspecialchars($row['nombre_Producto']) . '">';
                echo '<h3>' . htmlspecialchars($row['nombre_Producto']) . '</h3>';
                echo '<p>$' . number_format($row['Precio'], 2) . '</p>';
                echo '</a>';
                echo '</div>';
            }
        } else {
            echo "<p>No se encontraron productos para '$termino'.</p>";
        }
    } else {
        echo "<p>Ingresa un término para buscar productos.</p>";
    }
    ?>
  </div>
</main>

</body>
</html>
