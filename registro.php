<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Registro de usuario</title>
  <link rel="stylesheet" href="estilos/crearceunta.css">
</head>
<body>

<div class="register-container">
  <h2>Crear cuenta</h2>
  <form action="guardar_usuario.php" method="post">
    <input type="text" name="nombre" placeholder="Nombre" required>
    <input type="text" name="apellido" placeholder="Apellido" required>
    <input type="email" name="correo" placeholder="Correo electrónico" required>
    <input type="password" name="contraseña" placeholder="Contraseña" required>
    
    <select name="id_tipo_de_usuario" required>
      <option value="">Selecciona tipo de usuario</option>
      <option value="1">Administrador</option>
      <option value="2">Vendedor</option>
      <option value="3">Comprador</option>
    </select>

    <button type="submit">Registrarse</button>
  </form>
  <a class="login-link" href="login.php">¿Ya tienes cuenta? Inicia sesión</a>
</div>

</body>
</html>

