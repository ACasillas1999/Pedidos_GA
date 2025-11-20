<?php
// Iniciar la sesión
session_name("GA");
session_start();

// Verificar si el usuario no está logeado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    // Si no está logeado, redirigir al formulario de inicio de sesión
    header("location: /Pedidos_GA/Sesion/login.html");
    exit;
}
$id_pedido = $_GET['id'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="/Pedidos_GA/Img/Botones%20entregas/ICONOSPAG/ICONOPEDIDOS.png">
    <link rel="stylesheet" href="styles3.css">
    <title>Formulario de Pedidos</title>
</head>
    
     <script>
    document.addEventListener("DOMContentLoaded", function() {  
        var iconoAP = document.querySelector(".icono-AP img");
        var iconoVolver = document.querySelector(".icono-Volver");
        
        
        var imgNormalAP = "/Pedidos_GA/Img/Botones%20entregas/Inicio/DETPED/AGPEDNA.png";
        var imgHoverAP = "/Pedidos_GA/Img/Botones%20entregas/Inicio/DETPED/AGPEDAZ.png";

        // Cambiar la imagen al pasar el mouse
        iconoAP.addEventListener("mouseover", function() {
            iconoAP.src = imgHoverAP;
        });

        // Cambiar de vuelta al mover el mouse fuera
        iconoAP.addEventListener("mouseout", function() {
            iconoAP.src = imgNormalAP;
        });

        var imgNormalVolver = "/Pedidos_GA/Img/Botones%20entregas/RegistrarChofer/VOLVAZ.png";
    var imgHoverVolver = "/Pedidos_GA/Img/Botones%20entregas/RegistrarChofer/VOLVNA.png";

    // Cambiar la imagen al pasar el mouse para Volver
    if (iconoVolver) {
        iconoVolver.addEventListener("mouseover", function() {
            iconoVolver.src = imgHoverVolver;
        });

        iconoVolver.addEventListener("mouseout", function() {
            iconoVolver.src = imgNormalVolver;
        });
    }

       
    });
    </script>

    
<body>
    
    
     <header class="header">
        <div class="logo">
         
          <img src="/Pedidos_GA/Img/Botones%20entregas/Inicio/DETPED/ACTPED.png" alt="Estaditicas" class="icono-imprimir" style="max-width: 15%; height: auto;">
         
         </div>
        <nav class="navbar">
            <ul>
                <li class="nav-item"><a href='Inicio.php?id=<?php echo $id_pedido?>' class="nav-link">
                    
                <img src="\Pedidos_GA\Img\Botones entregas\RegistrarChofer\VOLVAZ.png" alt="Choferes"class = "icono-Volver"style="max-width: 5%; height: auto; position:absolute; top: 90px; left: 35px;">
                    

                </a></li>
              <!--  <li class="nav-item"><a href="#" class="nav-link">About</a></li>
                <li class="nav-item"><a href="#" class="nav-link">Services</a></li>
                <li class="nav-item"><a href="#" class="nav-link">Team</a></li>
                <li class="nav-item"><a href="#" class="nav-link">Contact</a></li>-->
            </ul>
        </nav>
    </header>
<p></p>

<?php
// Conexión a la base de datos
require_once __DIR__ . "/Conexiones/Conexion.php";

// Obtener el ID del pedido a actualizar


// Consulta SQL para obtener los datos del pedido
$sql = "SELECT * FROM pedidos WHERE ID = $id_pedido";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // Mostrar el formulario con los datos del pedido para su actualización
    $row = $result->fetch_assoc();

    $tipoEnvio = $row['tipo_envio'] ?? '';
$colorTipo = "#FFFFFF"; // Blanco por defecto

switch (strtolower($tipoEnvio)) {
    case "domicilio":
        $colorTipo = "#FFF9C4"; // Amarillo claro
        break;
    case "paquetería":
    case "paqueteria":
        $colorTipo = "#D6EAF8"; // Azul claro
        break;
    case "programado":
        $colorTipo = "#D4EFDF"; // Verde suave
        break;
}


$tipoEnvioTexto = strtoupper($tipoEnvio);

    ?>
    <form action="FuncionActualizarPedido.php" method="POST">
        <input type="hidden" name="id_pedido" value="<?php echo $id_pedido; ?>">
        
        <!--<label for="sucursal">Sucursal:</label><br>
        <input type="text" id="sucursal" name="sucursal" value="<?php echo $row['SUCURSAL']; ?>" required><br><br>-->
        
      <!--------------------------------------------------------------------------------------------------------------------->  
      <?php if ($_SESSION["Rol"] === "VR"): ?>
   
      
      <label for="sucursal">Sucursal:</label><br>
      <input type="text" id="sucursal" name="sucursal" value="<?php echo $row['SUCURSAL']; ?>" readonly><br><br>

<?php endif; ?>

<?php if (($_SESSION["Rol"] === "JC") OR ($_SESSION["Rol"] === "Admin")): ?>
   
      
   <label for="sucursal">Sucursal:</label><br>
<select id="sucursal" name="sucursal" required>
 <option value="">Selecciona una sucursal</option>
 <?php
 $sucursales = ['DIMEGSA', 'DEASA', 'AIESA', 'SEGSA', 'FESA', 'TAPATIA', 'GABSA', 'ILUMINACION', 'VALLARTA', 'CODI', 'QUERETARO'];

 $selectedSucursal = isset($row['SUCURSAL']) ? $row['SUCURSAL'] : '';

 foreach ($sucursales as $sucursal) {
     $selected = ($sucursal === $selectedSucursal) ? 'selected' : '';
     echo "<option value=\"$sucursal\" $selected>$sucursal</option>";
 }
 ?>
</select><br><br>
<?php endif; ?>

<?php if ($_SESSION["Rol"] === "Admin" || $_SESSION["Rol"] === "JC"): ?>
    <label for="tipo_envio">Tipo de Envío:</label><br>
    <select id="tipo_envio" name="tipo_envio" required style="background-color: <?= $colorTipo ?>; font-weight: bold;">
        <option value="">Selecciona una opción</option>
        <option value="domicilio" <?= ($tipoEnvio === 'domicilio') ? 'selected' : '' ?>>DOMICILIO</option>
        <option value="paquetería" <?= ($tipoEnvio === 'paquetería' || $tipoEnvio === 'paqueteria') ? 'selected' : '' ?>>PAQUETERÍA</option>
    </select><br><br>
<?php endif; ?>

<?php if ($_SESSION["Rol"] === "VR"): ?>
    <label for="tipo_envio">Tipo de Envío:</label><br>
    <input type="text" id="tipo_envio" name="tipo_envio" value="<?= $tipoEnvioTexto ?>" readonly style="background-color: <?= $colorTipo ?>; font-weight: bold;"><br><br>
<?php endif; ?>




        <!--------------------------------------------------------------------------------------------------------------------->
     <!--   <label for="estado">Estado:</label><br>
        <input type="text" id="estado" name="estado" value="<?php echo $row['ESTADO']; ?>" readonly><br><br>-->
         <!-- <input type="text" id="estado" name="estado" value="<?php echo $row['ESTADO']; ?>" required><br><br>-->
        
        
        <!--------------------------------------------------------------------------------------------------------------------->  
        <?php if (($_SESSION["Rol"] === "JC") OR ($_SESSION["Rol"] === "Admin")): ?>
   
       
        <label for="estado">Estado:</label><br>
<select id="estado" name="estado" required>
    <option value="">Selecciona un estado</option>
    <option value="ACTIVO" <?php if ($row['ESTADO'] === 'ACTIVO') echo 'selected'; ?>>ACTIVO</option>
    <option value="EN RUTA" <?php if ($row['ESTADO'] === 'EN RUTA') echo 'selected'; ?>>EN RUTA</option>
    <option value="REPROGRAMADO" <?php if ($row['ESTADO'] === 'REPROGRAMADO') echo 'selected'; ?>>REPROGRAMADO</option>
     <option value="EN TIENDA" <?php if ($row['ESTADO'] === 'EN TIENDA') echo 'selected'; ?>>EN TIENDA</option>
    <option value="ENTREGADO" <?php if ($row['ESTADO'] === 'ENTREGADO') echo 'selected'; ?>>ENTREGADO</option>
    <option value="CANCELADO" <?php if ($row['ESTADO'] === 'CANCELADO') echo 'selected'; ?>>CANCELADO</option>
</select><br><br>

        <!--------------------------------------------------------------------------------------------------------------------->
        <?php endif; ?>

        <?php if ($_SESSION["Rol"] === "VR") : ?>
   
       
           
      <label for="estado">Estado:</label><br>
      <input type="text" id="estado" name="estado" value="<?php echo $row['ESTADO']; ?>" readonly><br><br>


   <!--------------------------------------------------------------------------------------------------------------------->
   <?php endif; ?>
        
        <label for="fecha_recepcion_factura">Fecha de Recepción de Factura:</label><br>
        <input type="date" id="fecha_recepcion_factura" name="fecha_recepcion_factura" value="<?php echo $row['FECHA_RECEPCION_FACTURA']; ?>" readonly><br><br>
        
        <label for="fecha_entrega_cliente">Fecha de Entrega al Cliente:</label><br>
        <input type="date" id="fecha_entrega_cliente" name="fecha_entrega_cliente" value="<?php echo $row['FECHA_ENTREGA_CLIENTE']; ?>" required><br><br>
        
<?php
// Valor base: sucursal del pedido
$pedidoSucursal = $row['SUCURSAL'] ?? '';
$sucursales = ['DIMEGSA','DEASA','AIESA','SEGSA','FESA','TAPATIA','GABSA','ILUMINACION','VALLARTA','CODI','QUERETARO'];
?>

<!-- Sucursal para filtrar choferes (sin tocar la sucursal del pedido) -->
<?php if ($_SESSION["Rol"] === "Admin" || $_SESSION["Rol"] === "JC"): ?>
  <label for="sucursal_chofer">Sucursal (para asignar chofer):</label><br>
  <select id="sucursal_chofer" name="sucursal_chofer">
    <?php foreach ($sucursales as $s): ?>
      <option value="<?= $s ?>" <?= $s === $pedidoSucursal ? 'selected' : '' ?>><?= $s ?></option>
    <?php endforeach; ?>
  </select><br><br>
<?php else: /* VR */ ?>
  <label for="sucursal_chofer_vr">Sucursal (para asignar chofer):</label><br>
  <input type="text" id="sucursal_chofer_vr" value="<?= htmlspecialchars($pedidoSucursal) ?>" readonly>
  <input type="hidden" id="sucursal_chofer" name="sucursal_chofer" value="<?= htmlspecialchars($pedidoSucursal) ?>">
  <br><br>
<?php endif; ?>


      <!--  <label for="chofer_asignado">Chofer Asignado:</label><br>
        <input type="text" id="chofer_asignado" name="chofer_asignado" value="<?php echo $row['CHOFER_ASIGNADO']; ?>"><br><br>
-->
        
<script>
document.addEventListener('DOMContentLoaded', function () {
  const sucursalChofer = document.getElementById('sucursal_chofer'); // filtro
  const choferSelect   = document.getElementById('chofer_asignado'); // destino
  const choferPrevio   = choferSelect ? choferSelect.value : '';

  // Verificar si el precio está validado (del PHP)
  const precioValidado = <?php echo isset($row['precio_validado_jc']) && $row['precio_validado_jc'] == 1 ? 'true' : 'false'; ?>;

  function etiquetaAuto(item) {
    return item.placa || item.numero_serie || (item.id_vehiculo ? ('vehículo #' + item.id_vehiculo) : 'sin vehículo');
  }

  function poblarChoferes(sucursal) {
    if (!choferSelect) return;

    // Si el precio no está validado, no cargar choferes
    if (!precioValidado) {
      choferSelect.innerHTML = '<option value="">Valida el precio primero</option>';
      return;
    }

    choferSelect.innerHTML = '<option value="">Cargando choferes…</option>';

    if (!sucursal) {
      choferSelect.innerHTML = '<option value="">Selecciona una sucursal</option>';
      return;
    }

    fetch('obtener_choferes.php?sucursal=' + encodeURIComponent(sucursal))
      .then(r => r.json())
      .then(lista => {
        choferSelect.innerHTML = '';

        // Grupos
        const grpCon = document.createElement('optgroup');
        grpCon.label = 'Con vehículo';
        const grpSin = document.createElement('optgroup');
        grpSin.label = 'Sin vehículo';

        let previoAgregado = false;

        lista.forEach(item => {
          const opt = document.createElement('option');
          // Guardas username en pedidos.CHOFER_ASIGNADO (varchar)
          opt.value = item.username;

          if (item.tiene_vehiculo) {
            opt.textContent = `${item.username} – ${etiquetaAuto(item)}`;
            if (choferPrevio && opt.value === choferPrevio) {
              opt.selected = true;
              previoAgregado = true;
            }
            grpCon.appendChild(opt);
          } else {
            opt.textContent = `${item.username} – sin vehículo`;
            // Deshabilitar (gris). Pero si es el chofer actual, lo dejamos habilitado para no perderlo.
            if (choferPrevio && opt.value === choferPrevio) {
              opt.textContent = `${item.username} – (actual – sin vehículo)`;
              opt.selected = true;
              previoAgregado = true;
              // queda habilitado para que el form envíe el valor
            } else {
              opt.disabled = true; // gris/no seleccionable
            }
            grpSin.appendChild(opt);
          }
        });

        // Placeholder arriba
        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = 'Selecciona un chofer';
        placeholder.hidden = true;
        placeholder.disabled = true;
        // Si no hay previo seleccionado, que el placeholder quede seleccionado
        if (!previoAgregado) placeholder.selected = true;

        choferSelect.appendChild(placeholder);
        choferSelect.appendChild(grpCon);
        choferSelect.appendChild(grpSin);
      })
      .catch(() => {
        choferSelect.innerHTML = '<option value="">Error al cargar choferes</option>';
      });
  }

  if (sucursalChofer && sucursalChofer.value) {
    poblarChoferes(sucursalChofer.value);
  }
  if (sucursalChofer) {
    sucursalChofer.addEventListener('change', () => poblarChoferes(sucursalChofer.value));
  }
});
</script>


<?php if (($_SESSION["Rol"] === "JC") OR ($_SESSION["Rol"] === "Admin")): ?>
<label for="chofer_asignado">Chofer Asignado:</label><br>

<?php
// Verificar si el precio está validado
$precio_esta_validado = isset($row['precio_validado_jc']) && $row['precio_validado_jc'] == 1;
?>

<select id="chofer_asignado" name="chofer_asignado" <?php echo !$precio_esta_validado ? 'disabled' : ''; ?>>
    <option value="<?php echo $row['CHOFER_ASIGNADO']; ?>" selected><?php echo $row['CHOFER_ASIGNADO']; ?></option>
    <!-- Las opciones se cargarán dinámicamente -->
</select>

<?php if (!$precio_esta_validado): ?>
<br><span style="color: #dc3545; font-weight: bold; font-size: 0.9em;">
    ⚠️ Debes validar el precio antes de asignar un chofer
</span>
<?php endif; ?>
<br><br>
<?php endif; ?>


<?php if ($_SESSION["Rol"] === "VR"): ?>
   
      
   <label for="chofer_asignado">Chofer Asignado:</label><br>
   <input type="text" id="chofer_asignado" name="chofer_asignado" value="<?php echo $row['CHOFER_ASIGNADO']; ?>" readonly><br><br>

<?php endif; ?>

        <label for="vendedor">Vendedor:</label><br>
        <input type="text" id="vendedor" name="vendedor" value="<?php echo $row['VENDEDOR']; ?>" required><br><br>
        
        <label for="factura">Factura:</label><br>
        <input type="text" id="factura" name="factura" value="<?php echo $row['FACTURA']; ?>" required><br><br>

        <?php
        $precio_vendedor = isset($row['precio_factura_vendedor']) ? $row['precio_factura_vendedor'] : 0;
        $precio_real = isset($row['precio_factura_real']) ? $row['precio_factura_real'] : 0;
        $precio_validado = isset($row['precio_validado_jc']) ? $row['precio_validado_jc'] : 0;
        $diferencia = $precio_vendedor - $precio_real;
        $hubo_correccion = ($precio_vendedor != $precio_real && $precio_real > 0);
        ?>

        <?php if ($_SESSION["Rol"] === "VR"): ?>
            <!-- Vendedores pueden ver y editar el precio que capturaron -->
            <label for="precio_factura_vendedor">Precio de Factura:</label><br>
            <input type="number" id="precio_factura_vendedor" name="precio_factura_vendedor"
                   value="<?php echo number_format($precio_vendedor, 2, '.', ''); ?>"
                   step="0.01" min="0.01" required
                   style="<?php echo ($precio_vendedor > 0 && $precio_vendedor < 1000) ? 'background-color: #fff3cd;' : ''; ?>">
            <?php if ($precio_vendedor > 0 && $precio_vendedor < 1000): ?>
                <span style="color: #856404; font-weight: bold; margin-left: 10px;">
                    ⚠️ Precio menor a $1000 - Flete no conveniente
                </span>
            <?php endif; ?>
            <br>
            <?php if ($hubo_correccion): ?>
                <span style="color: #dc3545; font-size: 0.9em; font-style: italic;">
                    El Jefe de Choferes corrigió este precio a $<?php echo number_format($precio_real, 2); ?>
                </span>
            <?php endif; ?>
            <br><br>
        <?php endif; ?>

        <?php if ($_SESSION["Rol"] === "JC" || $_SESSION["Rol"] === "Admin"): ?>
            <!-- Jefe de Choferes puede ver ambos precios y validar/corregir -->
            <div style="border: 1px solid #ddd; padding: 15px; margin-bottom: 10px; background-color: #f9f9f9;">
                <label style="font-weight: bold;">Precio Capturado por Vendedor:</label><br>
                <input type="text" value="$<?php echo number_format($precio_vendedor, 2); ?>" readonly
                       style="background-color: #e9ecef; font-weight: bold; width: 150px;">
                <input type="hidden" name="precio_factura_vendedor_original" value="<?php echo $precio_vendedor; ?>">
                <br><br>

                <label for="precio_factura_real" style="font-weight: bold; color: #155724;">Precio Real de Factura:</label><br>
                <input type="number" id="precio_factura_real" name="precio_factura_real"
                       value="<?php echo number_format($precio_real, 2, '.', ''); ?>"
                       step="0.01" min="0.01" required
                       style="<?php echo ($precio_real > 0 && $precio_real < 1000) ? 'background-color: #fff3cd; font-weight: bold;' : 'font-weight: bold;'; ?>">

                <?php if ($precio_real > 0 && $precio_real < 1000): ?>
                    <span style="color: #856404; font-weight: bold; margin-left: 10px;">
                        ⚠️ Precio menor a $1000 - Flete no conveniente
                    </span>
                <?php endif; ?>
                <br><br>

                <?php if ($hubo_correccion): ?>
                    <div style="background-color: #f8d7da; padding: 10px; border-left: 4px solid #dc3545; margin: 10px 0;">
                        <strong style="color: #721c24;">Diferencia detectada:</strong><br>
                        Precio Vendedor: $<?php echo number_format($precio_vendedor, 2); ?><br>
                        Precio Real: $<?php echo number_format($precio_real, 2); ?><br>
                        <strong>Diferencia: $<?php echo number_format(abs($diferencia), 2); ?>
                        <?php echo ($diferencia > 0) ? '(vendedor cobró de más)' : '(vendedor cobró de menos)'; ?></strong>
                    </div>
                <?php endif; ?>

                <label for="precio_validado_jc">
                    <input type="checkbox" id="precio_validado_jc" name="precio_validado_jc" value="1"
                           <?php echo ($precio_validado == 1) ? 'checked' : ''; ?>>
                    <strong style="color: #155724;">Precio Validado por Jefe de Choferes</strong>
                    <?php if ($precio_validado == 1): ?>
                        <span style="color: #28a745; margin-left: 10px;">✓ Validado</span>
                    <?php endif; ?>
                </label>
            </div><br>
        <?php endif; ?>

        <label for="direccion">Dirección:</label><br>
        <input type="text" id="direccion" name="direccion" value="<?php echo $row['DIRECCION']; ?>" required><br><br>
        
        <label for="fecha_min_entrega">Fecha Mínima de Entrega:</label><br>
        <input type="date" id="fecha_min_entrega" name="fecha_min_entrega" value="<?php echo $row['FECHA_MIN_ENTREGA']; ?>" required><br><br>
        
        <label for="fecha_max_entrega">Fecha Máxima de Entrega:</label><br>
        <input type="date" id="fecha_max_entrega" name="fecha_max_entrega" value="<?php echo $row['FECHA_MAX_ENTREGA']; ?>" required><br><br>
        
        <label for="min_ventana_horaria_1">Hora Mínima de Ventana Horaria 1:</label><br>
        <input type="time" id="min_ventana_horaria_1" name="min_ventana_horaria_1" value="<?php echo $row['MIN_VENTANA_HORARIA_1']; ?>" required><br><br>
        
        <label for="max_ventana_horaria_1">Hora Máxima de Ventana Horaria 1:</label><br>
        <input type="time" id="max_ventana_horaria_1" name="max_ventana_horaria_1" value="<?php echo $row['MAX_VENTANA_HORARIA_1']; ?>" required><br><br>
        
        <label for="nombre_cliente">Nombre del Cliente:</label><br>
        <input type="text" id="nombre_cliente" name="nombre_cliente" value="<?php echo $row['NOMBRE_CLIENTE']; ?>" required><br><br>
        
        <label for="telefono">Teléfono:</label><br>
        <input type="text" id="telefono" name="telefono" value="<?php echo $row['TELEFONO']; ?>" required><br><br>
        
        <label for="contacto">Contacto:</label><br>
        <input type="text" id="contacto" name="contacto" value="<?php echo $row['CONTACTO']; ?>" required><br><br>
        
        <label for="comentarios">Comentarios:</label><br>
        <textarea id="comentarios" name="comentarios"><?php echo $row['COMENTARIOS']; ?></textarea><br><br>
        
      <!--  <label for="ruta">Ruta:</label><br>
        <input type="text" id="ruta" name="ruta" value="<?php echo isset($row['Ruta']) ? $row['Ruta'] : ' '; ?>"><br><br>-->

        
      <label for="coord_origen">Coordenadas de Origen:</label><br>
      <input type="text" id="coord_origen" name="coord_origen" value="<?php echo isset($row['Coord_Origen']) ? $row['Coord_Origen'] : ' '; ?>"><br><br>

        
        <label for="coord_destino">Coordenadas de Destino:</label><br>
        <input type="text" id="coord_destino" name="coord_destino" value="<?php echo isset($row['Coord_Destino']) ? $row['Coord_Destino'] : ' '; ?>"><br><br>

        
       <form action="ActualizarPedido.php" method="GET">
            <input type="hidden" name="id" value="<?php echo $row['ID']; ?>">
            <button type="submit" class="icono-AP" style="background: none; border: none; padding: 0;">
                <img src="/Pedidos_GA/Img/Botones%20entregas/Inicio/DETPED/AGPEDNA.png" alt="Actualizar Pedido" style="max-width: 50%; height: auto; Display: Flex;">
            </button>
        </form>

        
       
      <!--<?php print_r($row); ?> -->
    </form>
    <?php
} else {
    echo "No se encontró el pedido.";
}

$conn->close();
?>




</body>
</html>