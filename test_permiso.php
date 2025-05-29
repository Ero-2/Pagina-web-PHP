<?php
$dir = __DIR__ . '/imagenes/productos/';
if (is_writable($dir)) {
    echo "La carpeta SÍ tiene permisos de escritura.";
} else {
    echo "La carpeta NO tiene permisos de escritura.";
}
?>