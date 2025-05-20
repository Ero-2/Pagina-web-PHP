<?php
session_start();
require_once 'config/db.php';

// Verificar si el usuario es admin o vendedor
if (!isset($_SESSION['id_usuario']) || ($_SESSION['id_tipo_de_usuario'] != 1 && $_SESSION['id_tipo_de_usuario'] != 2)) {
    header("Location: inicio.php");
    exit();
}

$mensaje = "";

// Agregar categoría
if (isset($_POST['accion']) && $_POST['accion'] === 'agregar') {
    $nombre = trim($_POST['nombre']);
    if (!empty($nombre)) {
        $stmt = $conn->prepare("INSERT INTO categoria (nombre) VALUES (?)");
        $stmt->bind_param("s", $nombre);
        if ($stmt->execute()) {
            $mensaje = "Categoría agregada con éxito.";
        } else {
            $mensaje = "Error al agregar la categoría.";
        }
    } else {
        $mensaje = "El nombre de la categoría no puede estar vacío.";
    }
}

// Editar categoría
if (isset($_POST['accion']) && $_POST['accion'] === 'editar') {
    $id_categoria = intval($_POST['id_categoria']);
    $nuevo_nombre = trim($_POST['nombre_edit']);
    if (!empty($nuevo_nombre)) {
        $stmt = $conn->prepare("UPDATE categoria SET nombre = ? WHERE id_categoria = ?");
        $stmt->bind_param("si", $nuevo_nombre, $id_categoria);
        if ($stmt->execute()) {
            $mensaje = "Categoría actualizada con éxito.";
        } else {
            $mensaje = "Error al actualizar la categoría.";
        }
    } else {
        $mensaje = "El nombre de la categoría no puede estar vacío.";
    }
}

// Eliminar categoría
if (isset($_GET['eliminar'])) {
    $id_categoria = intval($_GET['eliminar']);
    // Verificar si hay productos asociados
    $stmt = $conn->prepare("SELECT COUNT(*) FROM Producto WHERE id_categoria = ?");
    $stmt->bind_param("i", $id_categoria);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count > 0) {
        $mensaje = "No se puede eliminar esta categoría porque tiene productos asociados.";
    } else {
        $stmt = $conn->prepare("DELETE FROM categoria WHERE id_categoria = ?");
        $stmt->bind_param("i", $id_categoria);
        if ($stmt->execute()) {
            $mensaje = "Categoría eliminada con éxito.";
        } else {
            $mensaje = "Error al eliminar la categoría.";
        }
    }
}

// Obtener todas las categorías
$categorias = [];
$result = $conn->query("SELECT id_categoria, nombre FROM categoria ORDER BY nombre");
while ($row = $result->fetch_assoc()) {
    $categorias[] = $row;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestionar Categorías</title>
    <link rel="stylesheet" href="estilos/inicio.css">
</head>
<body>

<header>
    <div class="logo">🛠️ Gestión de Categorías</div>
    <div class="acciones"><button onclick="location.href='inicio.php'">Inicio</button></div> 
    <form action="buscar.php" method="get" class="busqueda">
        <input type="text" name="q" placeholder="Buscar productos..." required>
        <button type="submit">🔍</button>
    </form>
    <div class="acciones">
        <button onclick="location.href='carrito.php'">🛒 Carrito</button>
        <?php if (isset($_SESSION['nombre_usuario'])): ?>
            <span>👤 <?= htmlspecialchars($_SESSION['nombre_usuario']) ?></span>
            <button onclick="location.href='logout.php'">Cerrar sesión</button>
        <?php else: ?>
            <button onclick="location.href='login.php'">Iniciar sesión</button>
        <?php endif; ?>
    </div>
</header>

<main>
<h2>Gestionar Categorías</h2>

<?php if ($mensaje): ?>
    <p><?= $mensaje ?></p>
<?php endif; ?>

<!-- Formulario para agregar nueva categoría -->
<h3>Agregar Categoría</h3>
<form method="post">
    <input type="hidden" name="accion" value="agregar">
    <label for="nombre">Nombre:</label>
    <input type="text" name="nombre" required>
    <button type="submit">Agregar</button>
</form>

<hr>

<!-- Listado de categorías -->
<h3>Listado de Categorías</h3>
<table border="1" cellpadding="10">
    <thead>
        <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($categorias as $cat): ?>
        <tr>
            <td><?= $cat['id_categoria'] ?></td>
            <td><?= htmlspecialchars($cat['nombre']) ?></td>
            <td>
                <!-- Botón de editar -->
                <form method="post" style="display:inline;">
                    <input type="hidden" name="accion" value="editar">
                    <input type="hidden" name="id_categoria" value="<?= $cat['id_categoria'] ?>">
                    <input type="text" name="nombre_edit" value="<?= htmlspecialchars($cat['nombre']) ?>" required>
                    <button type="submit">Actualizar</button>
                </form>
                <!-- Botón de eliminar -->
                <a href="?eliminar=<?= $cat['id_categoria'] ?>" onclick="return confirm('¿Estás seguro?')">Eliminar</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

</main>
</body>
</html>