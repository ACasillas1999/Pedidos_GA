<?php

date_default_timezone_set('America/Mexico_City');
$fechaActual = date('Y-m-d');



// Iniciar la sesión
session_name("GA");
session_start();

// Verificar si el usuario no está logeado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    // Si no está logeado, redirigir al formulario de inicio de sesión
    header("location: /Pedidos_GA/Sesion/login.html");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="/Pedidos_GA/Img/Botones%20entregas/ICONOSPAG/ICONOPEDIDOS.png">
    <link rel="stylesheet" href="styles3.css">
    <title>Pedidos GA</title>
</head>
    
     <script>
   document.addEventListener("DOMContentLoaded", function() {  
    var iconoVolver = document.querySelector(".icono-Volver");
    var iconoFAP = document.querySelector(".icono-FAP-img");

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

    var imgNormalFAP = "/Pedidos_GA/Img/Botones%20entregas/Inicio/DETPED/AGPEDNA.png";
    var imgHoverFAP = "/Pedidos_GA/Img/Botones%20entregas/Inicio/DETPED/AGPEDAZ.png";

    // Cambiar la imagen al pasar el mouse para FAP
    if (iconoFAP) {
        iconoFAP.addEventListener("mouseover", function() {
            iconoFAP.src = imgHoverFAP;
        });

        iconoFAP.addEventListener("mouseout", function() {
            iconoFAP.src = imgNormalFAP;
        });
    }
});

    </script>
    
    
<body>
    
    
     <header class="header">
        <div class="logo"><img src="\Pedidos_GA\Img\Botones entregas\NuevoRegistroInicio\AGPED2.png" alt="iconoVPedidos "class= "icono-registro" style="max-width: 15%; height: auto; "></div>
        <nav class="navbar">
            <ul>
                <li class="nav-item"><a href='Pedidos_GA.php' class="nav-link">
                    
                    
                     <img src="\Pedidos_GA\Img\Botones entregas\RegistrarChofer\VOLVAZ.png" alt="Choferes"class = "icono-Volver"style="max-width: 5%; height: auto; position:absolute; top: 70px; left: 35px;">
                    </a></li>
            </ul>
        </nav>
    </header>

    <p></p>
    
    <form action="NuevoRegistro.php" method="POST">
        
        <label for="sucursal">Sucursal:</label><br>

        <?php if ($_SESSION["Rol"] === "Admin"): ?>

        <select id="sucursal" name="sucursal" required>
            <option value="">Selecciona una sucursal</option>
            <option value="DIMEGSA">DIMEGSA</option>
            <option value="DEASA">DEASA</option>
            <option value="AIESA">AIESA</option>
            <option value="SEGSA">SEGSA</option>
            <option value="FESA">FESA</option>
            <option value="TAPATIA">TAPATIA</option>
            <option value="GABSA">GABSA</option>
            <option value="ILUMINACION">ILUMINACION</option>
            <option value="VALLARTA">VALLARTA</option>
            <option value="CODI">CODI</option>
            <option value="QUERETARO">QUERETARO</option>
        </select><br><br>

        <?php endif; ?>

        <?php if (($_SESSION["Rol"] === "JC")OR ($_SESSION["Rol"] === "VR") ): ?>

            <input type="text" id="sucursal" name="sucursal" value="<?php echo $_SESSION ["Sucursal"]; ?>" readonly><br><br>

        <?php endif; ?>



        <label for="estado">Estado:</label>
        <input type="text" id="estado" name="estado" value="ACTIVO" readonly><br><br>

       <!-- <label for="fecha_recepcion_factura">Fecha de Recepción de Factura:</label>
        <input type="date" id="fecha_recepcion_factura" name="fecha_recepcion_factura" required><br><br>

        <script>
            var fechaRecepcionFactura = document.getElementById('fecha_recepcion_factura');
            var fechaActual = new Date().toISOString().slice(0, 10);
            fechaRecepcionFactura.max = fechaActual;

            fechaRecepcionFactura.addEventListener('change', function() {
                if (this.value > fechaActual) {
                    this.value = fechaActual;
                }
            });
        </script>-->

        <label for="fecha_recepcion_factura">Fecha de Recepción de Factura:</label>
        
        
<!--<input type="date" id="fecha_recepcion_factura" name="fecha_recepcion_factura" readonly >-->

<input type="date" id="fecha_recepcion_factura" name="fecha_recepcion_factura" value="<?php echo $fechaActual; ?>" max="<?php echo $fechaActual; ?>" readonly>



<br><br>

<!--<script>
    document.addEventListener('DOMContentLoaded', (event) => {
        var fechaRecepcionFactura = document.getElementById('fecha_recepcion_factura');
        var fechaActual = new Date().toISOString().slice(0, 10);
        
        // Establecer el valor inicial del input con la fecha actual
        fechaRecepcionFactura.value = fechaActual;
        
        // Establecer el valor máximo permitido para el input con la fecha actual
        fechaRecepcionFactura.max = fechaActual;

        fechaRecepcionFactura.addEventListener('change', function() {
            if (this.value > fechaActual) {
                this.value = fechaActual;
            }
        });
    });
</script>-->




        <label for="fecha_entrega_cliente">Fecha de Entrega al Cliente:</label>
        <input type="date" id="fecha_entrega_cliente" name="fecha_entrega_cliente" ><br><br>
<!-------------------------------------------------------------------->

        <label for="chofer_asignado">Chofer Asignado:</label><br>
        <select id="chofer_asignado" name="chofer_asignado" readonly>
            <option value="">Selecciona un chofer</option>
        </select><br><br>
<!--------------------------------------------------------------------->

        <label for="vendedor">Vendedor:</label>
         <input type="text" id="vendedor" name="vendedor" value="<?php echo $_SESSION ["Nombre"];?>" readonly><br><br>

         <label for="tipo_envio">Tipo de Envío:</label>
        <select id="tipo_envio" name="tipo_envio" required>
            <option value="">Selecciona una opción</option>
            <option value="Programado">Domicilio</option>
            <option value="Paquetería">Paquetería</option>
        </select><br><br>

        <label for="factura">Factura:</label>
        <input type="text" id="factura" name="factura" required><br><br>

        <label for="direccion">Dirección:</label>
        <input type="text" id="direccion" name="direccion" required><br><br>

        <label for="fecha_min_entrega">Fecha Mínima de Entrega:</label>
        <input type="date" id="fecha_min_entrega" name="fecha_min_entrega" required><br><br>

        <label for="fecha_max_entrega">Fecha Máxima de Entrega:</label>
        <input type="date" id="fecha_max_entrega" name="fecha_max_entrega" required><br><br>

        <label for="min_ventana_horaria_1">Hora Mínima de Ventana Horaria :</label>
        <input type="time" id="min_ventana_horaria_1" name="min_ventana_horaria_1" required><br><br>

        <label for="max_ventana_horaria_1">Hora Máxima de Ventana Horaria :</label>
        <input type="time" id="max_ventana_horaria_1" name="max_ventana_horaria_1" required><br><br>

        <label for="nombre_cliente">Nombre del Cliente:</label>
        <input type="text" id="nombre_cliente" name="nombre_cliente" required><br><br>

        <label for="telefono">Teléfono:</label>
        <input type="text" id="telefono" name="telefono" required><br><br>

        <label for="contacto">Contacto:</label>
        <input type="text" id="contacto" name="contacto" required><br><br>

        <label for="comentarios">Comentarios:</label>
        <textarea id="comentarios" name="comentarios"></textarea><br><br>

        <label for="coord_origen">Coordenadas de Origen: </label><br>
        <select id="coord_origen" name="coord_origen" required>
            <option value="">Selecciona una sucursal de Origen</option>
            <option value="20.66086566989181, -103.35498266582181">DIMEGSA</option>
            <option value="20.6626029222324, -103.35564905319303">DEASA</option>
            <option value="20.647442138464317, -103.35303451136468">AIESA</option>
            <option value="20.68075274311637, -103.36740059908436">SEGSA</option>
            <option value="20.680876941064735, -103.36717413301479">FESA</option>
            <option value="20.660080259250343, -103.35598648741274">TAPATIA</option>
            <option value="21.1017480510419, -101.68149017883717">GABSA</option>
            <option value="20.660385131570916, -103.35616304721444">ILUMINACION</option>
            <option value="20.708876281913774, -105.27453124524618">VALLARTA</option>
            <option value="20.652475013556035, -100.43190554642169">QUERETARO</option>
            <option value="20.660511385115246, -103.35846367710215">CODI</option>
        </select><br><br>

        <label for="coord_destino">Coordenadas de Destino:</label>
        <input type="text" id="coord_destino" name="coord_destino"><br><br>

        
        <button type="submit" class="icono-FAP" style="background: none; border: none; padding: 0;">
    <img src="/Pedidos_GA/Img/Botones%20entregas/Inicio/DETPED/AGPEDNA.png" class="icono-FAP-img" alt="Actualizar Pedido" style="max-width: 50%; height: auto; display: flex;">
</button>
    </form>

    <script>
        document.getElementById('sucursal').addEventListener('change', function() {
            const sucursalSeleccionada = this.value;
            const choferSelect = document.getElementById('chofer_asignado');
            choferSelect.innerHTML = '<option value="">Selecciona un chofer</option>';

            if (sucursalSeleccionada) {
                fetch('obtener_choferes.php?sucursal=' + sucursalSeleccionada)
                    .then(response => response.json())
                    .then(data => {
                        data.forEach(chofer => {
                            const option = document.createElement('option');
                            option.value = chofer;
                            option.textContent = chofer;
                            choferSelect.appendChild(option);
                        });
                    })
                    .catch(error => console.error('Error al obtener los choferes:', error));
            }
        });
    </script>

</body>
</html>
