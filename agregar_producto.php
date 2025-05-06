<?php
session_start();
require_once 'config/db.php';

// Verificar si el usuario está logueado y tiene el rol adecuado
if (!isset($_SESSION['id_usuario']) || ($_SESSION['id_tipo_de_usuario'] != 1 && $_SESSION['id_tipo_de_usuario'] != 2)) {
    header("Location: inicio.php"); // Si no es admin ni vendedor, redirigir al inicio
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Recoger los datos del formulario
    $nombre = $_POST['nombre'];
    $descripcion = $_POST['descripcion'];
    $precio = $_POST['precio'];
    $categoria = $_POST['categoria'];

    // Subir imagen
    $imagen = $_FILES['imagen'];

    // Verificar que la imagen sea válida
    $check = getimagesize($imagen['tmp_name']);
    if ($check === false) {
        echo "<p>El archivo no es una imagen.</p>";
        exit();
    }

    // Comprobar el tamaño de la imagen (5MB como máximo, ajusta según lo necesites)
    if ($imagen['size'] > 5000000) {
        echo "<p>El archivo es demasiado grande. El tamaño máximo permitido es 5MB.</p>";
        exit();
    }

    // Definir la carpeta donde se guardarán las imágenes
    $directorio_imagenes = 'imagenes/productos/';
    
    // Verificar si la carpeta existe, si no, crearla
    if (!is_dir($directorio_imagenes)) {
        mkdir($directorio_imagenes, 0777, true);
    }

    // Obtener la extensión del archivo
    $ext = strtolower(pathinfo($imagen['name'], PATHINFO_EXTENSION));
    $extensiones_permitidas = array('jpg', 'jpeg', 'png', 'gif', 'webp');
    
    // Verificar que la extensión del archivo sea válida
    if (!in_array($ext, $extensiones_permitidas)) {
        echo "<p>Solo se permiten imágenes con las extensiones: jpg, jpeg, png, gif o webp.</p>";
        exit();
    }

    // Crear una ruta única para la imagen
    $ruta_imagen = $directorio_imagenes . uniqid() . '.' . $ext;

    // Subir la imagen
    if (move_uploaded_file($imagen['tmp_name'], $ruta_imagen)) {
        // Insertar el producto en la base de datos
        $sql = "INSERT INTO Producto (nombre_Producto, descripcion, Precio, categoria) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssds", $nombre, $descripcion, $precio, $categoria);
        if ($stmt->execute()) {
            // Obtener el ID del producto recién insertado
            $id_producto = $stmt->insert_id;

            // Insertar la imagen en la tabla Imagen
            $sql_imagen = "INSERT INTO Imagen (id_Producto, url) VALUES (?, ?)";
            $stmt_imagen = $conn->prepare($sql_imagen);
            $stmt_imagen->bind_param("is", $id_producto, $ruta_imagen);
            $stmt_imagen->execute();

            echo "<p>Producto agregado exitosamente.</p>";
        } else {
            echo "<p>Error al agregar el producto.</p>";
        }
    } else {
        echo "<p>Error al subir la imagen.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Agregar Producto</title>
</head>
<body>
<header>
<h2>Agregar Producto</h2>
<div class="acciones"><button onclick="location.href='inicio.php'">inicio</button></div> 

</header>


<form action="agregar_producto.php" method="post" enctype="multipart/form-data">
  <label for="nombre">Nombre del Producto:</label>
  <input type="text" name="nombre" id="nombre" required><br><br>
  
  <label for="descripcion">Descripción:</label>
  <textarea name="descripcion" id="descripcion" required></textarea><br><br>
  
  <label for="precio">Precio:</label>
  <input type="number" step="0.01" name="precio" id="precio" required><br><br>

  <label for="categoria">Categoría:</label>
  <input type="text" name="categoria" id="categoria" required><br><br>

  <label for="imagen">Imagen del Producto:</label>
  <input type="file" name="imagen" id="imagen" required><br><br>

  <input type="submit" value="Agregar Producto">
</form>

</body>
</html>
