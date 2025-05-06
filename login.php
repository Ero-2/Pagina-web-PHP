<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Login</title>
  <link rel="stylesheet" href="estilos/iniciarsesion.css">
</head>
<body>

<div class="login-container">
  <h2>Iniciar sesión</h2>
  <form action="validar_login.php" method="post">
    <input type="email" name="correo" placeholder="Correo" required>
    <input type="password" name="contraseña" placeholder="Contraseña" required>
    <button type="submit">Entrar</button>
  </form>

  <a class="create-account-link" href="registro.php">Crear Cuenta</a>

  <?php
  if (isset($_GET['error'])) {
    echo "<p style='color:red; margin-top:10px;'>Correo o contraseña incorrectos</p>";
  }
  ?>
</div>

</body>
</html>

