<?php
session_start();
require_once 'config/db.php';

// Verificar si el usuario está logueado y tiene permiso
if (!isset($_SESSION['id_usuario']) || ($_SESSION['id_tipo_de_usuario'] != 1 && $_SESSION['id_tipo_de_usuario'] != 2)) {
    header("Location: inicio.php");
    exit();
}

// Obtener ID del producto desde GET
$id_producto = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Consultar producto con su categoría asociada
$sql = "SELECT p.*, c.nombre AS categoria_nombre 
        FROM producto p 
        LEFT JOIN categoria c ON p.id_categoria = c.id_categoria 
        WHERE p.id_Producto = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_producto);
$stmt->execute();
$result = $stmt->get_result();
$producto = $result->fetch_assoc();

if (!$producto) {
    die("Producto no encontrado.");
}

// Consultar todas las categorías para mostrarlas en el formulario
$categorias_stmt = $conn->query("SELECT id_categoria, nombre FROM categoria ORDER BY nombre");

// Si se envió el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $conn->real_escape_string($_POST['nombre']);
    $precio = floatval($_POST['precio']);
    $descripcion = $conn->real_escape_string($_POST['descripcion']);
    $stock = intval($_POST['stock']);
    $id_categoria = intval($_POST['id_categoria']);

    // Validación básica
    if (empty($nombre) || $precio <= 0 || $stock < 0 || !$id_categoria) {
        header("Location: editar_producto.php?id=$id_producto&error=1");
        exit;
    }

    // Actualizar producto
    $update_sql = "UPDATE producto SET 
        nombre_Producto = ?, 
        Precio = ?, 
        descripcion = ?, 
        stock = ?, 
        id_categoria = ?
        WHERE id_Producto = ?";
    $update_stmt = $conn->prepare($update_sql);

    // Aquí usamos "d" para double (float)
    $update_stmt->bind_param(
        "sdsiii",
        $nombre,      // s: string
        $precio,      // d: double
        $descripcion, // s: string
        $stock,       // i: int
        $id_categoria,// i: int
        $id_producto  // i: int
    );

    if ($update_stmt->execute()) {
        // Manejar carga de nueva imagen si se proporciona
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = "uploads/";
            $imagen_nombre = basename($_FILES['imagen']['name']);
            $imagen_ruta = $upload_dir . uniqid() . "-" . $imagen_nombre;

            if (move_uploaded_file($_FILES['imagen']['tmp_name'], $imagen_ruta)) {
                // Eliminar imagen anterior si existe
                $imagen_anterior_sql = "SELECT url FROM imagen WHERE id_Producto = ?";
                $img_stmt = $conn->prepare($imagen_anterior_sql);
                $img_stmt->bind_param("i", $id_producto);
                $img_stmt->execute();
                $img_result = $img_stmt->get_result();
                while ($img = $img_result->fetch_assoc()) {
                    if (file_exists($img['url'])) unlink($img['url']);
                }

                // Actualizar o insertar nueva imagen
                $sql_imagen = "INSERT INTO imagen (id_Producto, url, tipo) VALUES (?, ?, ?)
                               ON DUPLICATE KEY UPDATE url = VALUES(url)";
                $stmt_imagen = $conn->prepare($sql_imagen);
                $tipo = mime_content_type($_FILES['imagen']['tmp_name']);
                $stmt_imagen->bind_param("iss", $id_producto, $imagen_ruta, $tipo);
                $stmt_imagen->execute();
                $stmt_imagen->close();
            } else {
                echo "<div class='mensaje-error'>Error al subir la imagen.</div>";
            }
        }

        header("Location: editar_producto.php?id=$id_producto&success=1");
        exit;
    } else {
        header("Location: editar_producto.php?id=$id_producto&error=1");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Editar Producto | Mi E-Commerce</title>
  <link rel="stylesheet" href="estilos/editar_producto.css">
  <style>
    body { font-family: Arial; background: #f4f4f4; padding: 20px; }
    .formulario-editar { background: white; padding: 20px; border-radius: 10px; max-width: 600px; margin: auto; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
    label { display: block; margin-top: 10px; font-weight: bold; }
    input[type="text"], input[type="number"], textarea, select { width: 100%; padding: 8px; margin-top: 5px; border-radius: 5px; border: 1px solid #ccc; }
    button { margin-top: 15px; padding: 10px 20px; background: #2575fc; color: white; border: none; border-radius: 5px; cursor: pointer; }
    .mensaje-exito { color: green; background: #d4edda; padding: 10px; border-radius: 5px; }
    .mensaje-error { color: red; background: #f8d7da; padding: 10px; border-radius: 5px; }
  </style>
</head>
<body>

<div class="formulario-editar">
  <h2>Editar Producto</h2>

  <?php if (isset($_GET['error'])): ?>
    <div class="mensaje-error">Hubo un error al actualizar el producto.</div>
  <?php endif; ?>

  <?php if (isset($_GET['success'])): ?>
    <div class="mensaje-exito">Producto actualizado correctamente.</div>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data">
    <label for="nombre">Nombre del Producto:</label>
    <input type="text" name="nombre" id="nombre" value="<?= htmlspecialchars($producto['nombre_Producto']) ?>" required>

    <label for="precio">Precio:</label>
    <input type="number" step="0.01" name="precio" id="precio" value="<?= $producto['Precio'] ?>" required>

    <label for="descripcion">Descripción:</label>
    <textarea name="descripcion" id="descripcion" rows="4"><?= htmlspecialchars($producto['descripcion']) ?></textarea>

    <label for="stock">Stock:</label>
    <input type="number" name="stock" id="stock" value="<?= $producto['stock'] ?>" min="0" required>

    <label for="id_categoria">Categoría:</label>
    <select name="id_categoria" id="id_categoria" required>
      <option value="">-- Selecciona una categoría --</option>
      <?php while ($categoria = $categorias_stmt->fetch_assoc()): ?>
        <option value="<?= $categoria['id_categoria'] ?>" <?= $producto['id_categoria'] == $categoria['id_categoria'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($categoria['nombre']) ?>
        </option>
      <?php endwhile; ?>
    </select>

    <label for="imagen">Cambiar Imagen:</label>
    <input type="file" name="imagen" id="imagen" accept="image/*">

    <button type="submit">Guardar Cambios</button>
  </form>
</div>

</body>
</html>
