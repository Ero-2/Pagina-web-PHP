<?php
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../config/jwt.php';
require __DIR__ . '/../vendor/autoload.php';

// CABECERAS PARA CORS
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// RESPUESTA A PRE-FLIGHT
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

try {
    // LEER EL TOKEN JWT DESDE Authorization O desde la sesión como fallback
    $id_usuario = null;
    $headers = getallheaders();
    // Intentar obtener token JWT primero
    $authHeader = '';
    foreach ($headers as $key => $value) {
        if (strtolower($key) === 'authorization') {
            $authHeader = $value;
            break;
        }
    }
    if ($authHeader && strpos($authHeader, 'Bearer ') === 0) {
        $token = trim(str_replace('Bearer ', '', $authHeader));
        try {
            $decoded = decode_jwt($token);
            $id_usuario = $decoded->data->id_usuario;
        } catch (Exception $e) {
            // Token inválido, continuar con sesión
        }
    }
    // Si no hay token válido, usar sesión PHP como fallback
    if (!$id_usuario) {
        session_start();
        if (!isset($_SESSION['id_usuario'])) {
            throw new Exception("Usuario no autenticado. Inicie sesión nuevamente.", 401);
        }
        $id_usuario = $_SESSION['id_usuario'];
    }

    // VERIFICAR SI ES MULTIPART (con archivos) O JSON
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (strpos($contentType, 'multipart/form-data') !== false) {
        // Datos con archivo
        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $precio = floatval($_POST['precio'] ?? 0);
        $categoria_id = intval($_POST['id_categoria'] ?? 0);
        $imagen = $_FILES['imagen'] ?? null;
    } else {
        // Datos JSON (sin archivo)
        $input = file_get_contents('php://input');
        if (empty($input)) {
            throw new Exception("No se recibieron datos", 400);
        }
        $data = json_decode($input, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("JSON inválido: " . json_last_error_msg(), 400);
        }
        $nombre = trim($data['nombre'] ?? '');
        $descripcion = trim($data['descripcion'] ?? '');
        $precio = floatval($data['precio'] ?? 0);
        $categoria_id = intval($data['id_categoria'] ?? 0);
        $imagen = null;
    }

    // VALIDAR CAMPOS OBLIGATORIOS
    $missing_fields = [];
    if (empty($nombre)) $missing_fields[] = 'nombre';
    if (empty($descripcion)) $missing_fields[] = 'descripcion';
    if ($precio <= 0) $missing_fields[] = 'precio (debe ser mayor a 0)';
    if ($categoria_id <= 0) $missing_fields[] = 'id_categoria';
    if (!empty($missing_fields)) {
        throw new Exception("Faltan los siguientes campos: " . implode(', ', $missing_fields), 400);
    }

    // Validaciones adicionales
    if (strlen($nombre) > 255) {
        throw new Exception("El nombre no puede tener más de 255 caracteres", 400);
    }
    if (strlen($descripcion) > 1000) {
        throw new Exception("La descripción no puede tener más de 1000 caracteres", 400);
    }

    // VERIFICAR SI LA CATEGORÍA EXISTE
    $stmt_categoria = $conn->prepare("SELECT id_categoria FROM categoria WHERE id_categoria = ?");
    $stmt_categoria->bind_param("i", $categoria_id);
    $stmt_categoria->execute();
    $stmt_categoria->store_result();
    if ($stmt_categoria->num_rows === 0) {
        throw new Exception("La categoría seleccionada no existe", 400);
    }

    // INSERTAR PRODUCTO EN LA BASE DE DATOS
    $stmt_producto = $conn->prepare("INSERT INTO Producto (nombre_Producto, descripcion, Precio, id_categoria) VALUES (?, ?, ?, ?)");
    $stmt_producto->bind_param("ssdi", $nombre, $descripcion, $precio, $categoria_id);
    if (!$stmt_producto->execute()) {
        throw new Exception('Error al guardar el producto: ' . $stmt_producto->error, 500);
    }
    $id_producto = $stmt_producto->insert_id;

    // PROCESAR IMAGEN SI EXISTE
    $ruta_imagen = null;
    if ($imagen && $imagen['error'] === UPLOAD_ERR_OK) {
        // Validar tipo de archivo
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $file_type = mime_content_type($imagen['tmp_name']);
        if (!in_array($file_type, $allowed_types)) {
            throw new Exception("Tipo de archivo no permitido. Use: JPG, PNG, GIF o WebP", 400);
        }
        // Validar tamaño (máximo 5MB)
        if ($imagen['size'] > 5 * 1024 * 1024) {
            throw new Exception("El archivo es demasiado grande. Máximo 5MB", 400);
        }
        // Crear directorio si no existe
        $upload_dir = 'imagenes\productos/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        // Generar nombre único
        $extension = pathinfo($imagen['name'], PATHINFO_EXTENSION);
        $filename = 'producto_' . time() . '_' . uniqid() . '.' . $extension;
        $ruta_imagen = $upload_dir . $filename;
        // Mover archivo
        if (!move_uploaded_file($imagen['tmp_name'], $ruta_imagen)) {
            throw new Exception("Error al subir la imagen", 500);
        }

        // INSERTAR RUTA DE LA IMAGEN EN LA TABLA `imagen`
        $stmt_imagen = $conn->prepare("INSERT INTO imagen (id_Producto, url) VALUES (?, ?)");
        $stmt_imagen->bind_param("is", $id_producto, $ruta_imagen);
        if (!$stmt_imagen->execute()) {
            // Eliminar la imagen del servidor si falla el registro
            unlink($ruta_imagen);
            throw new Exception('Error al registrar la imagen: ' . $stmt_imagen->error, 500);
        }
    }

    // Devolver respuesta exitosa
    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Producto agregado exitosamente',
        'producto' => [
            'id_producto' => $id_producto,
            'nombre' => $nombre,
            'descripcion' => $descripcion,
            'precio' => $precio,
            'categoria_id' => $categoria_id,
            'imagen' => $ruta_imagen ?? null
        ]
    ]);

} catch (Exception $e) {
    // Log del error
    error_log("Error en agregar_producto.php: " . $e->getMessage());
    $code = $e->getCode();
    if ($code < 100 || $code > 599) {
        $code = 500;
    }
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'code' => $code
    ]);
} finally {
    if (isset($stmt_producto)) {
        $stmt_producto->close();
    }
    if (isset($stmt_categoria)) {
        $stmt_categoria->close();
    }
    if (isset($stmt_imagen)) {
        $stmt_imagen->close();
    }
    $conn->close();
}
?>