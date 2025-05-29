<?php
require __DIR__ . '/../config/db.php';

// CABECERAS PARA CORS
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// RESPUESTA A PRE-FLIGHT
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Solo permitir POST y PUT
if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT'])) {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido. Use POST o PUT.']);
    exit;
}

try {
    // Obtener ID del producto
    $id_producto = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($id_producto <= 0) {
        throw new Exception("ID de producto inválido", 400);
    }

    // Verificar que el producto existe
    $stmt_check = $conn->prepare("SELECT id_Producto FROM Producto WHERE id_Producto = ?");
    $stmt_check->bind_param("i", $id_producto);
    $stmt_check->execute();
    $result = $stmt_check->get_result();
    if ($result->num_rows === 0) {
        throw new Exception("Producto no encontrado", 404);
    }

    // Debug: Log todos los datos POST recibidos
    error_log("Todos los datos POST: " . print_r($_POST, true));
    error_log("Archivos recibidos: " . print_r($_FILES, true));

    // Obtener datos del formulario
    $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
    $precio = isset($_POST['precio']) ? floatval($_POST['precio']) : 0;
    $descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';
    $stock = isset($_POST['stock']) ? intval($_POST['stock']) : 0;
    $id_categoria = isset($_POST['id_categoria']) ? intval($_POST['id_categoria']) : 0;

    // Validaciones básicas
    if (empty($nombre)) {
        throw new Exception("El nombre del producto es requerido", 400);
    }
    if ($precio <= 0) {
        throw new Exception("El precio debe ser mayor a 0", 400);
    }
    if ($stock < 0) {
        throw new Exception("El stock no puede ser negativo", 400);
    }
    if ($id_categoria <= 0) {
        throw new Exception("Debe seleccionar una categoría válida", 400);
    }

    // Verificar que la categoría existe
    $stmt_cat = $conn->prepare("SELECT id_categoria FROM categoria WHERE id_categoria = ?");
    $stmt_cat->bind_param("i", $id_categoria);
    $stmt_cat->execute();
    $cat_result = $stmt_cat->get_result();
    if ($cat_result->num_rows === 0) {
        throw new Exception("La categoría seleccionada no existe", 400);
    }

    // Manejar la imagen si se subió una nueva
    $url_imagen = null;
    $imagen_actualizada = false;

    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $imagen = $_FILES['imagen'];
        $extension = strtolower(pathinfo($imagen['name'], PATHINFO_EXTENSION));
        $tipos_permitidos = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (!in_array($extension, $tipos_permitidos)) {
            throw new Exception("Extensión de archivo no permitida. Use JPG, PNG, GIF o WebP", 400);
        }

        if ($imagen['size'] > 5 * 1024 * 1024) {
            throw new Exception("La imagen es demasiado grande. Máximo 5MB", 400);
        }

        $upload_dir = realpath(__DIR__ . '/../../') . '/imagenes/productos/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $stmt_old_image = $conn->prepare("SELECT url FROM imagen WHERE id_Producto = ?");
        $stmt_old_image->bind_param("i", $id_producto);
        $stmt_old_image->execute();
        $old_image_result = $stmt_old_image->get_result();

        if ($old_image_result->num_rows > 0) {
            $row = $old_image_result->fetch_assoc();
            $old_image_path = realpath(__DIR__ . '/../../') . '/' . $row['url'];
            if (file_exists($old_image_path)) {
                unlink($old_image_path);
            }
        }

        $nombre_archivo = 'producto_' . $id_producto . '_' . time() . '.' . $extension;
        $ruta_completa = $upload_dir . $nombre_archivo;

        if (!move_uploaded_file($imagen['tmp_name'], $ruta_completa)) {
            throw new Exception("Error al subir la imagen", 500);
        }

        $url_imagen = 'imagenes/productos/' . $nombre_archivo;
        $imagen_actualizada = true;
    }

    // Iniciar transacción
    $conn->begin_transaction();

    try {
        // Actualizar el producto
        $stmt = $conn->prepare("UPDATE Producto SET nombre_Producto = ?, Precio = ?, descripcion = ?, stock = ?, id_categoria = ? WHERE id_Producto = ?");
        $stmt->bind_param("sdsiii", $nombre, $precio, $descripcion, $stock, $id_categoria, $id_producto);
        if (!$stmt->execute()) {
            throw new Exception("Error al actualizar el producto", 500);
        }

        // Actualizar la imagen si fue subida
        if ($imagen_actualizada && $url_imagen) {
            $stmt_check_image = $conn->prepare("SELECT id_imagen FROM imagen WHERE id_Producto = ?");
            $stmt_check_image->bind_param("i", $id_producto);
            $stmt_check_image->execute();
            $image_result = $stmt_check_image->get_result();

            if ($image_result->num_rows > 0) {
                $stmt_update_image = $conn->prepare("UPDATE imagen SET url = ? WHERE id_Producto = ?");
                $stmt_update_image->bind_param("si", $url_imagen, $id_producto);
                $stmt_update_image->execute();
            } else {
                $stmt_insert_image = $conn->prepare("INSERT INTO imagen (id_Producto, url) VALUES (?, ?)");
                $stmt_insert_image->bind_param("is", $id_producto, $url_imagen);
                $stmt_insert_image->execute();
            }
        }

        $conn->commit();

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Producto actualizado correctamente',
            'id_producto' => $id_producto,
            'url_imagen' => $url_imagen ?? null
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        if ($imagen_actualizada && $url_imagen) {
            $ruta_imagen_nueva = __DIR__ . '/../../' . $url_imagen;
            if (file_exists($ruta_imagen_nueva)) {
                unlink($ruta_imagen_nueva);
            }
        }
        throw $e;
    }

} catch (Exception $e) {
    error_log("Error en editar_producto.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    $code = $e->getCode();
    if ($code < 100 || $code > 599) $code = 500;
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'code' => $code
    ]);
} finally {
    if (isset($stmt)) $stmt->close();
    if (isset($stmt_check)) $stmt_check->close();
    if (isset($stmt_cat)) $stmt_cat->close();
    if (isset($conn)) $conn->close();
}
?> 