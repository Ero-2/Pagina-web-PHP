<?php
session_start();
require_once 'config/db.php';

// Verificar si el usuario está logueado y tiene permiso
if (!isset($_SESSION['id_usuario']) || ($_SESSION['id_tipo_de_usuario'] != 1 && $_SESSION['id_tipo_de_usuario'] != 2)) {
    header("Location: inicio.php");
    exit();
}

// Obtener todas las categorías para mostrarlas en el formulario
$cats = [];
$result = $conn->query("SELECT id_categoria, nombre FROM categoria ORDER BY nombre");
while ($row = $result->fetch_assoc()) {
    $cats[] = $row;
}

// Procesar el formulario cuando se envíe
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Datos del producto
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $precio = floatval($_POST['precio']);
    $categoria_id = intval($_POST['categoria_id']); // Aquí recibimos el ID de la categoría

    // Validar campos obligatorios
    if (empty($nombre) || empty($descripcion) || empty($precio) || empty($categoria_id)) {
        die("<p>Todos los campos son obligatorios.</p>");
    }

    // Manejar la imagen
    $imagen = $_FILES['imagen'];

    // Verificar si es una imagen válida
    $check = getimagesize($imagen['tmp_name']);
    if ($check === false) {
        die("<p>El archivo no es una imagen válida.</p>");
    }

    // Limitar tamaño (5MB)
    if ($imagen['size'] > 5000000) {
        die("<p>La imagen es demasiado grande. Máximo permitido: 5MB.</p>");
    }

    // Extensiones permitidas
    $ext = strtolower(pathinfo($imagen['name'], PATHINFO_EXTENSION));
    $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($ext, $extensiones_permitidas)) {
        die("<p>Solo se permiten imágenes en formato: JPG, JPEG, PNG, GIF o WEBP.</p>");
    }

    // Directorio donde se guardarán las imágenes
    $directorio_imagenes = 'imagenes/productos/';
    if (!is_dir($directorio_imagenes)) {
        mkdir($directorio_imagenes, 0777, true);
    }

    // Generar nombre único para la imagen
    $nombre_imagen = uniqid('prod_') . '.' . $ext;
    $ruta_imagen = $directorio_imagenes . $nombre_imagen;

    // Mover la imagen al directorio
    if (!move_uploaded_file($imagen['tmp_name'], $ruta_imagen)) {
        die("<p>Error al subir la imagen.</p>");
    }

    // Insertar el producto en la base de datos
    $sql = "INSERT INTO Producto (nombre_Producto, descripcion, Precio, id_categoria) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssdi", $nombre, $descripcion, $precio, $categoria_id);

    if ($stmt->execute()) {
        $id_producto = $stmt->insert_id;

        // Insertar la imagen asociada al producto
        $sql_imagen = "INSERT INTO Imagen (id_Producto, url) VALUES (?, ?)";
        $stmt_imagen = $conn->prepare($sql_imagen);
        $stmt_imagen->bind_param("is", $id_producto, $ruta_imagen);
        $stmt_imagen->execute();

        echo "<p>Producto agregado exitosamente.</p>";
        // Opcionalmente redirigir a otra página
        // header("Location: ver_productos.php");
        // exit();
    } else {
        echo "<p>Error al agregar el producto: " . $stmt->error . "</p>";
    }
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

      <?php if (isset($_SESSION['id_tipo_de_usuario']) && ($_SESSION['id_tipo_de_usuario'] == 1 || $_SESSION['id_tipo_de_usuario'] == 2)): ?>
        <button onclick="location.href='agregar_producto.php'"><i class="fas fa-plus-circle"></i> Agregar Producto</button>
      <?php endif; ?>

      <button onclick="location.href='logout.php'"><i class="fas fa-sign-out-alt"></i> Cerrar sesión</button>
    <?php else: ?>
      <button onclick="location.href='login.php'"><i class="fas fa-sign-in-alt"></i> Iniciar sesión</button>
    <?php endif; ?>
    
    <div class="filter-menu">
      <button id="filterButton"><i class="fas fa-filter"></i> Filtrar</button>
      <div id="categoryMenu" style="display: none;">
        <?php
        $categorias = $conn->query("SELECT id_categoria, nombre FROM categoria ORDER BY nombre");
        while ($cat = $categorias->fetch_assoc()): ?>
          <a href="#" class="category-item" data-id="<?= $cat['id_categoria'] ?>">
            <?= htmlspecialchars($cat['nombre']) ?>
          </a>
        <?php endwhile; ?>
      </div>
    </div>
  </div>
</header>


<main>
<h2>Agregar Nuevo Producto</h2>

<form action="agregar_producto.php" method="post" enctype="multipart/form-data">
    <label for="nombre">Nombre del Producto:</label>
    <input type="text" name="nombre" id="nombre" required><br><br>

    <label for="descripcion">Descripción:</label>
    <textarea name="descripcion" id="descripcion" required></textarea><br><br>

    <label for="precio">Precio:</label>
    <input type="number" step="0.01" name="precio" id="precio" required><br><br>

    <label for="categoria">Categoría:</label>
    <select name="categoria_id" id="categoria" required>
        <option value="">-- Selecciona una categoría --</option>
        <?php foreach ($cats as $cat): ?>
            <option value="<?= $cat['id_categoria'] ?>">
                <?= htmlspecialchars($cat['nombre']) ?>
            </option>
        <?php endforeach; ?>
    </select><br><br>

    <label for="imagen">Imagen del Producto:</label>
    <input type="file" name="imagen" id="imagen" required><br><br>

    <input type="submit" value="Agregar Producto">
</form>
</main>

</body>
</html>