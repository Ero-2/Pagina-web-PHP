<?php
require_once 'config/db.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Cambiar contraseña</title>
  <style>
    body { font-family: Arial; padding: 30px; }
    form { max-width: 400px; margin: auto; display: flex; flex-direction: column; }
    input { margin-bottom: 10px; padding: 10px; font-size: 16px; }
  </style>
</head>
<body>

<h2>Actualizar Contraseña</h2>
<form action="procesar_cambio_contraseña.php" method="post">
  <input type="email" name="correo" placeholder="Correo del usuario" required>
  <input type="password" name="nueva_contraseña" placeholder="Nueva contraseña" required>
  <input type="submit" value="Actualizar">
</form>

</body>
</html>
