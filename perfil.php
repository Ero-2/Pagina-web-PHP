<?php
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Perfil de Usuario</title>
  <link rel="stylesheet" href="estilos/perfil2.css">
  <link rel="stylesheet" href="estilos/footer.css">
  <link rel="stylesheet" href="estilos/inicio.css">
  <link rel="stylesheet" href="estilos/carrito.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <style>
    .loading {
      display: none;
      text-align: center;
      padding: 10px;
      color: #666;
    }
    .error-message {
      color: #e74c3c;
      background: #ffeaea;
      padding: 10px;
      border-radius: 5px;
      margin: 10px 0;
      display: none;
    }
    .success-message {
      color: #27ae60;
      background: #eafaf1;
      padding: 10px;
      border-radius: 5px;
      margin: 10px 0;
      display: none;
    }
    .debug-info {
      background: #f8f9fa;
      border: 1px solid #dee2e6;
      border-radius: 5px;
      padding: 10px;
      margin: 10px 0;
      font-family: monospace;
      font-size: 12px;
      display: none;
    }
  </style>
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
        // Asegúrate de tener una conexión abierta a la BD
        if (isset($conn)) {
          $categorias = $conn->query("SELECT id_categoria, nombre FROM categoria ORDER BY nombre");
          while ($cat = $categorias->fetch_assoc()): ?>
            <a href="#" class="category-item" data-id="<?= $cat['id_categoria'] ?>">
              <?= htmlspecialchars($cat['nombre']) ?>
            </a>
          <?php endwhile;
        } ?>
      </div>
    </div>
  </div>
</header>

<!-- Contenido de Perfil -->
<main class="perfil-contenedor">
  <!-- Información de Debug -->
  <div id="debugInfo" class="debug-info">
    <h4>Información de Debug:</h4>
    <div id="debugContent"></div>
    <button type="button" onclick="toggleDebug()">Mostrar/Ocultar Debug</button>
  </div>

  <section class="direccion-section">
    <h2>Agregar Dirección</h2>
    <div id="direccionError" class="error-message"></div>
    <div id="direccionSuccess" class="success-message"></div>
    <div id="direccionLoading" class="loading">Guardando dirección...</div>
    
    <form id="direccionForm">
      <label>Calle:</label>
      <input type="text" name="calle" required><br>
      <label>Número:</label>
      <input type="text" name="numero" required><br>
      <label>País:</label>
      <select id="pais" name="pais" required>
        <option value="">Seleccione un país</option>
      </select><br>
      <label>Estado:</label>
      <select id="estado" name="estado" required>
        <option value="">Seleccione un estado</option>
      </select><br>
      <label>Ciudad:</label>
      <select id="ciudad" name="ciudad" required>
        <option value="">Seleccione una ciudad</option>
      </select><br>
      <label>Código Postal:</label>
      <input type="text" name="codigo_postal" required><br>
      <button type="submit">Guardar Dirección</button>
    </form>
  </section>

  <section class="metodo-pago-section">
    <h2>Agregar Método de Pago</h2>
    <div id="pagoError" class="error-message"></div>
    <div id="pagoSuccess" class="success-message"></div>
    <div id="pagoLoading" class="loading">Guardando método de pago...</div>
    
    <form id="pagoForm">
      <label>Método:</label>
      <select name="metodo" required>
        <option value="">Seleccione un método</option>
        <option value="Tarjeta de Crédito">Tarjeta de Crédito</option>
        <option value="Tarjeta de Débito">Tarjeta de Débito</option>
        <option value="PayPal">PayPal</option>
        <option value="Transferencia Bancaria">Transferencia Bancaria</option>
      </select><br>
      <label>Detalles:</label>
      <input type="text" name="detalles" required><br>
      <button type="submit">Guardar Método</button>
    </form>
  </section>
</main>

<!-- Footer -->
<footer class="main-footer">
  <div class="footer-container">
    <div class="footer-box">
      <h3>🛍️ Mi E-Commerce</h3>
      <p>Tu tienda online con los mejores productos del mercado. Calidad garantizada al mejor precio.</p>
    </div>
    <div class="footer-box">
      <h3>Enlaces Rápidos</h3>
      <ul>
        <li><a href="inicio.php">Inicio</a></li>
        <li><a href="colecciones.php">Colecciones</a></li>
        <li><a href="carrito.php">Carrito</a></li>
        <li><a href="login.php">Iniciar Sesión</a></li>
        <li><a href="registro.php">Registrarse</a></li>
      </ul>
    </div>
    <div class="footer-box">
      <h3>Contacto</h3>
      <ul>
        <li>📧 contacto@miecommerce.com</li>
        <li>📞 +57 300 123 4567</li>
        <li>📍 Bogotá, Colombia</li>
      </ul>
    </div>
    <div class="footer-box">
      <h3>Síguenos</h3>
      <div class="social-links">
        <a href="#">Facebook</a><br>
        <a href="#">Instagram</a><br>
        <a href="#">Twitter</a><br>
        <a href="#">YouTube</a>
      </div>
    </div>
  </div>
  <div class="footer-bottom">
    <p>&copy; <?= date('Y') ?> Mi E-Commerce - Todos los derechos reservados.</p>
  </div>
</footer>

<!-- Script para inyectar token desde PHP -->
<script>
// Variables globales para debug
let debugMode = false;
let debugInfo = [];

// Función para mostrar/ocultar debug
function toggleDebug() {
    debugMode = !debugMode;
    const debugDiv = document.getElementById('debugInfo');
    debugDiv.style.display = debugMode ? 'block' : 'none';
    updateDebugInfo();
}

// Función para actualizar info de debug
function updateDebugInfo() {
    if (debugMode) {
        const debugContent = document.getElementById('debugContent');
        debugContent.innerHTML = debugInfo.map(info => `<p>${info}</p>`).join('');
    }
}

// Función para agregar info de debug
function addDebugInfo(message) {
    const timestamp = new Date().toLocaleTimeString();
    debugInfo.push(`[${timestamp}] ${message}`);
    updateDebugInfo();
    console.log(`[DEBUG] ${message}`);
}

// Guardamos el token en localStorage desde PHP
<?php if (isset($_SESSION['token'])): ?>
const userToken = '<?= addslashes($_SESSION['token']) ?>';
if (userToken) {
    localStorage.setItem('token', userToken);
    addDebugInfo('Token guardado en localStorage');
} else {
    addDebugInfo('Token vacío desde PHP');
}
<?php else: ?>
addDebugInfo('No hay token en la sesión PHP');
<?php endif; ?>

// Función para mostrar mensajes
function showMessage(type, elementId, message) {
    const element = document.getElementById(elementId);
    element.textContent = message;
    element.style.display = 'block';
    
    // Ocultar después de 5 segundos
    setTimeout(() => {
        element.style.display = 'none';
    }, 5000);
}

// Función para ocultar todos los mensajes de una sección
function hideMessages(prefix) {
    document.getElementById(prefix + 'Error').style.display = 'none';
    document.getElementById(prefix + 'Success').style.display = 'none';
    document.getElementById(prefix + 'Loading').style.display = 'none';
}

// Funcionalidad de formularios
document.addEventListener('DOMContentLoaded', async () => {
  addDebugInfo('DOM cargado, iniciando...');
  
  const paisSelect = document.getElementById('pais');
  const estadoSelect = document.getElementById('estado');
  const ciudadSelect = document.getElementById('ciudad');

  // Cargar países
  try {
    addDebugInfo('Cargando países...');
    const resPaises = await fetch('https://countriesnow.space/api/v0.1/countries/positions');
    const dataPaises = await resPaises.json();
    
    if (dataPaises.data && dataPaises.data.length > 0) {
      dataPaises.data.forEach(pais => {
        const option = document.createElement('option');
        option.value = pais.name;
        option.text = pais.name;
        paisSelect.add(option);
      });
      addDebugInfo(`Países cargados: ${dataPaises.data.length}`);
    } else {
      addDebugInfo('No se encontraron países');
    }
  } catch (error) {
    addDebugInfo(`Error cargando países: ${error.message}`);
    console.error('Error cargando países:', error);
  }

  // Cargar estados
  async function cargarEstados(pais) {
    estadoSelect.innerHTML = '<option value="">Seleccione un estado</option>';
    ciudadSelect.innerHTML = '<option value="">Seleccione una ciudad</option>';
    
    if (!pais) return;
    
    try {
      addDebugInfo(`Cargando estados para: ${pais}`);
      const resEstados = await fetch('https://countriesnow.space/api/v0.1/countries/states', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ country: pais })
      });
      const dataEstados = await resEstados.json();
      
      if (!dataEstados.data || !dataEstados.data.states || dataEstados.data.states.length === 0) {
        estadoSelect.innerHTML = '<option>No hay estados disponibles</option>';
        addDebugInfo(`No hay estados para ${pais}`);
        return;
      }
      
      dataEstados.data.states.forEach(estado => {
        const option = document.createElement('option');
        option.value = estado.name;
        option.text = estado.name;
        estadoSelect.add(option);
      });
      
      addDebugInfo(`Estados cargados: ${dataEstados.data.states.length}`);
    } catch (error) {
      addDebugInfo(`Error cargando estados: ${error.message}`);
      console.error('Error cargando estados:', error);
    }
  }

  // Cargar ciudades
  async function cargarCiudades(estado) {
    ciudadSelect.innerHTML = '<option value="">Seleccione una ciudad</option>';
    const pais = paisSelect.value;
    
    if (!pais || !estado) return;
    
    try {
      addDebugInfo(`Cargando ciudades para: ${estado}, ${pais}`);
      const resCiudades = await fetch('https://countriesnow.space/api/v0.1/countries/state/cities', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ country: pais, state: estado })
      });
      const dataCiudades = await resCiudades.json();
      
      if (!dataCiudades.data || dataCiudades.data.length === 0) {
        ciudadSelect.innerHTML = '<option>No hay ciudades disponibles</option>';
        addDebugInfo(`No hay ciudades para ${estado}`);
        return;
      }
      
      dataCiudades.data.forEach(ciudad => {
        const option = document.createElement('option');
        option.value = ciudad;
        option.text = ciudad;
        ciudadSelect.add(option);
      });
      
      addDebugInfo(`Ciudades cargadas: ${dataCiudades.data.length}`);
    } catch (error) {
      addDebugInfo(`Error cargando ciudades: ${error.message}`);
      console.error('Error cargando ciudades:', error);
    }
  }

  paisSelect.addEventListener('change', () => {
    cargarEstados(paisSelect.value);
  });

  estadoSelect.addEventListener('change', () => {
    cargarCiudades(estadoSelect.value);
  });

  // Formulario de dirección
  document.getElementById('direccionForm').addEventListener('submit', async e => {
    e.preventDefault();
    hideMessages('direccion');
    document.getElementById('direccionLoading').style.display = 'block';

    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());
    
    addDebugInfo(`Datos del formulario: ${JSON.stringify(data)}`);

    const token = localStorage.getItem('token');
    if (!token) {
      hideMessages('direccion');
      showMessage('error', 'direccionError', 'No hay token disponible. Por favor, vuelve a iniciar sesión.');
      addDebugInfo('Token no encontrado en localStorage');
      setTimeout(() => {
        localStorage.removeItem('token');
        window.location.href = 'login.php';
      }, 2000);
      return;
    }

    addDebugInfo(`Token encontrado: ${token.substring(0, 20)}...`);

    try {
      addDebugInfo('Enviando petición a guardar_direccion.php...');
      
      const res = await fetch('guardar_direccion.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify(data)
      });

      addDebugInfo(`Respuesta recibida: Status ${res.status}`);
      
      const contentType = res.headers.get('content-type');
      addDebugInfo(`Content-Type: ${contentType}`);
      
      if (!contentType || !contentType.includes('application/json')) {
        const textResponse = await res.text();
        addDebugInfo(`Respuesta no JSON: ${textResponse}`);
        throw new Error('La respuesta del servidor no es JSON válido');
      }

      const result = await res.json();
      addDebugInfo(`Resultado: ${JSON.stringify(result)}`);

      hideMessages('direccion');

      if (result.error || !result.success) {
        const errorMsg = result.error || 'Error desconocido';
        showMessage('error', 'direccionError', errorMsg);
        
        if (errorMsg.includes('Token') || errorMsg.includes('token')) {
          setTimeout(() => {
            localStorage.removeItem('token');
            window.location.href = 'login.php';
          }, 2000);
        }
      } else {
        showMessage('success', 'direccionSuccess', result.message || 'Dirección guardada exitosamente');
        document.getElementById('direccionForm').reset();
      }
      
    } catch (err) {
      hideMessages('direccion');
      const errorMessage = `Error al conectar con el servidor: ${err.message}`;
      addDebugInfo(errorMessage);
      showMessage('error', 'direccionError', errorMessage);
      console.error('Error completo:', err);
    }
  });

  // Formulario de método de pago
  document.getElementById('pagoForm').addEventListener('submit', async e => {
    e.preventDefault();
    hideMessages('pago');
    document.getElementById('pagoLoading').style.display = 'block';

    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());
    
    addDebugInfo(`Datos del pago: ${JSON.stringify(data)}`);

    const token = localStorage.getItem('token');
    if (!token) {
      hideMessages('pago');
      showMessage('error', 'pagoError', 'No hay token disponible. Por favor, vuelve a iniciar sesión.');
      addDebugInfo('Token no encontrado para método de pago');
      setTimeout(() => {
        localStorage.removeItem('token');
        window.location.href = 'login.php';
      }, 2000);
      return;
    }

    try {
      addDebugInfo('Enviando petición a guardar_metodo_pago.php...');
      
      const res = await fetch('guardar_metodo_pago.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify(data)
      });

      addDebugInfo(`Respuesta pago: Status ${res.status}`);
      
      const contentType = res.headers.get('content-type');
      if (!contentType || !contentType.includes('application/json')) {
        const textResponse = await res.text();
        addDebugInfo(`Respuesta pago no JSON: ${textResponse}`);
        throw new Error('La respuesta del servidor no es JSON válido');
      }

      const result = await res.json();
      addDebugInfo(`Resultado pago: ${JSON.stringify(result)}`);

      hideMessages('pago');

      if (result.error || !result.success) {
        const errorMsg = result.error || 'Error desconocido';
        showMessage('error', 'pagoError', errorMsg);
        
        if (errorMsg.includes('Token') || errorMsg.includes('token')) {
          setTimeout(() => {
            localStorage.removeItem('token');
            window.location.href = 'login.php';
          }, 2000);
        }
      } else {
        showMessage('success', 'pagoSuccess', result.message || 'Método de pago guardado exitosamente');
        document.getElementById('pagoForm').reset();
      }
      
    } catch (err) {
      hideMessages('pago');
      const errorMessage = `Error al conectar con el servidor: ${err.message}`;
      addDebugInfo(errorMessage);
      showMessage('error', 'pagoError', errorMessage);
      console.error('Error completo:', err);
    }
  });
});
</script>

</body>
</html>