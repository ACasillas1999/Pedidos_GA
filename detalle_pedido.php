


<!DOCTYPE html>
<html lang="es">
<head>
     <title>Pedidos GA</title>
    <link rel="icon" type="image/png" href="/Pedidos_GA/Img/Botones%20entregas/ICONOSPAG/ICONOPEDIDOS.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="styles1.css">
    <link href='https://api.mapbox.com/mapbox-gl-js/v2.6.1/mapbox-gl.css' rel='stylesheet' />
    <link rel="icon" href="Pedidos_GA/Img/Paquete.ico" type="image/x-icon">
    <script src='https://api.mapbox.com/mapbox-gl-js/v2.6.1/mapbox-gl.js'></script>
    
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
   
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
   
    
    <style>
        .accion-container {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    
    <p></p>
    
    <div class="container">
        
        <?php
        // Establecer la conexión a la base de datos
        require_once __DIR__ . "/Conexiones/Conexion.php";

        // Verificar si se ha enviado el ID del pedido
        if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['id'])) {
            $pedidoId = $_GET['id'];

            // Consulta SQL preparada para obtener los detalles del pedido con el ID proporcionado
            $sql = "SELECT * FROM pedidos WHERE ID = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $pedidoId);
            $stmt->execute();
            $result = $stmt->get_result();

            // Verificar si la consulta fue exitosa
            if ($result->num_rows > 0) {
                // Mostrar los detalles del pedido en forma de tabla con cuadrícula
                $row = $result->fetch_assoc();
        ?>
        
        
        <script>
document.addEventListener("DOMContentLoaded", function() {  
    var iconoActP = document.querySelector(".icono-ActP");
    var iconoImprimir = document.querySelector(".icono-imprimir")
    
    var imgNormalActP = "/Pedidos_GA/Img/Botones%20entregas/Inicio/DETPED/AZTPEDNA.png";
    var imgHoverActP = "/Pedidos_GA/Img/Botones%20entregas/Inicio/DETPED/ACTPEDAZ.png";
//AZTPEDNA
    // Cambiar la imagen al pasar el mouse
    iconoActP.addEventListener("mouseover", function() {
        iconoActP.src = imgHoverActP;
    });

    // Cambiar de vuelta al mover el mouse fuera
    iconoActP.addEventListener("mouseout", function() {
        iconoActP.src = imgNormalActP;
    });
    
    
    var imgNormalImprimir = "/Pedidos_GA/Img/Botones%20entregas/Inicio/DETPED/IMPNA.png";
    var imgHoverImprimir = "/Pedidos_GA/Img/Botones%20entregas/Inicio/DETPED/IMPAZ.png";

    // Cambiar la imagen al pasar el mouse
    iconoImprimir.addEventListener("mouseover", function() {
        iconoImprimir.src = imgHoverImprimir;
    });

    // Cambiar de vuelta al mover el mouse fuera
    iconoImprimir.addEventListener("mouseout", function() {
        iconoImprimir.src = imgNormalImprimir;
    });
    
});
</script>

<?php
$tipoEnvio = $row["tipo_envio"] ?? '';
$colorTipo = "#FFFFFF"; // blanco por defecto

switch (strtolower($tipoEnvio)) {
    case "programado":
        $colorTipo = "#D4EFDF"; // verde claro
        break;
    case "paquetería":
    case "paqueteria":
        $colorTipo = "#D6EAF8"; // azul claro
        break;
    case "domicilio":
        $colorTipo = "#D4EFDF"; // amarillo claro
        break;
}

// Convertir a mayúsculas para mostrarlo estéticamente
$tipoEnvioTexto = strtoupper($tipoEnvio);
?>


        
         <h1>REGISTRO N°  <?php echo $row["ID"]; ?></h1>
        
            <div class="pedido-info">
                    <table>
                        <tr>
                            <th>Sucursal</th>
                            <td colspan="3"><span><?php echo $row["SUCURSAL"]; ?></span></td>
                        </tr>
                        <tr>
    <th>Tipo de Envío</th>
    <td colspan="3" style="background-color: <?= $colorTipo ?>; font-weight: bold;">
        <?= htmlspecialchars($tipoEnvioTexto) ?>
    </td>
</tr>

                        <tr>
                            <th>Estado</th>

                            <?php if (($_SESSION["Rol"] === "JC") OR ($_SESSION["Rol"] === "Admin")): ?>
                             <td>
                                
        <select id="estado" onchange="cambiarEstado()">
            <option value="ACTIVO" <?php if ($row["ESTADO"] === "ACTIVO") echo "selected"; ?>>ACTIVO</option>
            <option value="EN RUTA" <?php if ($row["ESTADO"] === "EN RUTA") echo "selected"; ?>>EN RUTA</option>
             <option value="REPROGRAMADO" <?php if ($row["ESTADO"] === "REPROGRAMADO") echo "selected"; ?>>REPROGRAMADO</option>
            <option value="EN TIENDA" <?php if ($row["ESTADO"] === "EN TIENDA") echo "selected"; ?>>EN TIENDA</option>
            <option value="CANCELADO" <?php if ($row["ESTADO"] === "CANCELADO") echo "selected"; ?>>CANCELADO</option>
            <option value="ENTREGADO" <?php if ($row["ESTADO"] === "ENTREGADO") echo "selected"; ?>>ENTREGADO</option>
        </select>
        <span id="estado-actual" style="display: none;"><?php echo $row["ESTADO"]; ?></span>
    </td>
    <?php endif; ?>

    <?php if ($_SESSION["Rol"] === "VR"): ?>
        <td><span><?php echo $row["ESTADO"]; ?></span></td>
    <?php endif; ?>
                      <th>Factura</th>
                            <td><span><?php echo $row["FACTURA"]; ?></span></td>
                        </tr>

                        <tr>
                            <th>Precio Factura</th>
                            <td colspan="3">
                                <?php
                                $precio_vendedor = isset($row['precio_factura_vendedor']) ? floatval($row['precio_factura_vendedor']) : 0;
                                $precio_real = isset($row['precio_factura_real']) ? floatval($row['precio_factura_real']) : 0;
                                $precio_validado = isset($row['precio_validado_jc']) ? intval($row['precio_validado_jc']) : 0;
                                $hubo_correccion = ($precio_vendedor != $precio_real && $precio_real > 0 && $precio_vendedor > 0);

                                // Mostrar precio real
                                echo '<span style="font-weight: bold; font-size: 1.1em;">$' . number_format($precio_real, 2) . '</span>';

                                // Si hubo corrección, mostrar precio original del vendedor
                                if ($hubo_correccion) {
                                    echo ' <span style="color: #dc3545; font-size: 0.85em; text-decoration: line-through;">($' . number_format($precio_vendedor, 2) . ' original)</span>';
                                }

                                // Mostrar estado de validación
                                if ($precio_validado == 1) {
                                    echo ' <span style="color: #28a745; font-weight: bold; margin-left: 10px;">✓ Validado</span>';
                                } else {
                                    echo ' <span style="color: #dc3545; font-weight: bold; margin-left: 10px;">⚠️ Sin validar</span>';
                                }

                                // Alerta si es menor a $1000
                                if ($precio_real > 0 && $precio_real < 1000) {
                                    echo '<br><span style="color: #856404; font-weight: bold; font-size: 0.9em;">⚠️ Precio menor a $1000 - Flete no conveniente</span>';
                                }
                                ?>
                            </td>
                        </tr>

                        <tr>
                            <th>Fecha Recepción</th>
                            <td><span><?php echo $row["FECHA_RECEPCION_FACTURA"]; ?></span></td>
                            <th>Fecha Entrega</th>
                            <td><span><?php echo $row["FECHA_ENTREGA_CLIENTE"]; ?></span></td>
                        </tr>
                        <tr>
                            <th>Chofer Asignado</th>
                            <td><span><?php echo $row["CHOFER_ASIGNADO"]; ?></span></td>
                            <th>Vendedor</th>
                            <td><span><?php echo $row["VENDEDOR"]; ?></span></td>
                        </tr>
                        <tr>
                            <th>Dirección</th>
                            <td colspan="3"><span><?php echo $row["DIRECCION"]; ?></span></td>
                        </tr>
                         <tr>
                            <th>Coordenada Origen</th>
                            <td><span><?php echo $row["Coord_Origen"]; ?></span></td>
                            <th>Coordenada Destino</th>
                            <td><span><?php echo $row["Coord_Destino"]; ?></span></td>
                        </tr>
                        <tr>
                            <th>Fecha Mínima</th>
                            <td><span><?php echo $row["FECHA_MIN_ENTREGA"]; ?></span></td>
                            <th>Fecha Máxima</th>
                            <td><span><?php echo $row["FECHA_MAX_ENTREGA"]; ?></span></td>
                        </tr>
                        <tr>
                            <th>Ventana Horaria Maxima</th>
                            <td><span><?php echo $row["MIN_VENTANA_HORARIA_1"]; ?></span></td>
                            <th>Ventana Horaria Minima</th>
                            <td><span><?php echo $row["MAX_VENTANA_HORARIA_1"]; ?></span></td>
                        </tr>
                        <tr>
                            <th>Nombre Cliente</th>
                            <td><span><?php echo $row["NOMBRE_CLIENTE"]; ?></span></td>
                            <th>Teléfono</th>
                            <td><span><?php echo $row["TELEFONO"]; ?></span></td>
                        </tr>
                       
                        <tr>
                            <th>Contacto</th>
                            <td colspan="3"><span><?php echo $row["CONTACTO"]; ?></span></td>
                        </tr>
                        <tr>
                            <th>Comentarios</th>
                            <td colspan="3"><span><?php echo $row["COMENTARIOS"]; ?></span></td>
                        </tr>
                        
                      
                    </table>
                    
                    <!-- Formulario para cargar archivos -->
                    
                     <p></p>
                    
                    
                    
                </div>
        
          <p></p>
        
        
        <form action="ActualizarPedido.php" method="GET">
    <input type="hidden" name="id" value="<?php echo $row['ID']; ?>">
    
            <button type="submit" class="icono-ActualizarP" style="background: none; border: none; padding: 0;">
                
        <img src="/Pedidos_GA/Img/Botones%20entregas/Inicio/DETPED/AZTPEDNA.png" alt="Estaditicas" class="icono-ActP" style="max-width: 50%; height: auto;">
                
          </button>
</form>

        
      <script>
            
          
    function cambiarEstado() {
        var nuevoEstado = document.getElementById("estado").value;
        var estadoActual = "<?php echo $row["ESTADO"]; ?>";

        if (confirm("¿Estás seguro de cambiar el estado a " + nuevoEstado + "?")) {
            // Actualizar el estado en la base de datos
            var xhr = new XMLHttpRequest();
            xhr.open("POST", "actualizar_estado.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    // Envía el correo electrónico
                    var correoXHR = new XMLHttpRequest();
                    //correoXHR.open("POST", "Mensaje_WP.php", true);
                    correoXHR.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                    correoXHR.send("estado=" + nuevoEstado);

                    // Actualiza el estado mostrado en la página
                    document.getElementById("estado").value = nuevoEstado;
                    alert("Estado actualizado correctamente.");
                }
            };
            xhr.send("id=<?php echo $pedidoId; ?>&estado=" + nuevoEstado);
        } else {
            // Restaura el valor anterior del estado
            document.getElementById("estado").value = estadoActual;
        }
    }
</script>
         <div class="container2">
        
        <h1>
        <img src="/Pedidos_GA/Img/Botones%20entregas/Inicio/DETPED/DOCAZ.png" alt="Estaditicas" class="icono-ActP" style="max-width: 30%; height: auto;">
             
        </h1>
    
        <div class = "accion-container horizontal"> 
            
            <p></p>
                    
                    <!-- Formulario para cargar archivos -->
<div class="accion-container">
    <h2>            
        
    <img src="/Pedidos_GA/Img/Botones%20entregas/Inicio/DETPED/CARARAZ.png" alt="Estaditicas" class="icono-ActP" style="max-width:60%; height: auto;">
    
    </h2>
    <?php
    // Verificar si ya existe un archivo cargado para este pedido
    $rutaArchivoCargado = obtenerRutaArchivoCargado($pedidoId);
    if ($rutaArchivoCargado) {
        echo '<p>Ya se ha cargado un archivo para este pedido. Si desea cargar uno nuevo, reemplace el archivo actual.</p>';
    } else {
        echo '<p></p>';
    }
    ?>
    <form action="detalle_pedido.php" method="post" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?php echo $pedidoId; ?>">
        <input type="hidden" name="accion" value="cargar">
        <input type="file" name="archivo">
        <input type="submit" value="Cargar">
    </form>
</div>
                    
                    <!-- Formulario para descargar archivos -->
                  <!--  <div class="accion-container">
                        <h2>
                        
                        <img src="/Pedidos_GA/Img/Botones%20entregas/Inicio/DETPED/DESCARAZ.png" alt="Estaditicas" class="icono-ActP" style="max-width: 100%; height: auto;">
                        
                        </h2>
                        <form action="detalle_pedido.php" method="post">
                            <input type="hidden" name="id" value="<?php echo $pedidoId; ?>">
                            <input type="hidden" name="accion" value="descargar">
                            <input type="submit" value="Descargar">
                        </form>
                    </div>-->
                    
                    <!-- Formulario para consultar archivos -->
                    <div class="accion-container">
                        <h2>
                        
                        <img src="/Pedidos_GA/Img/Botones%20entregas/Inicio/DETPED/CONSTARCAZ.png" alt="Estaditicas" class="icono-ActP" style="max-width: 100%; height: auto;">
                        
                        </h2>
                        <form action="detalle_pedido.php?id=<?php echo $pedidoId; ?>" method="post">
                            <input type="hidden" name="id" value="<?php echo $pedidoId; ?>">
                            <input type="hidden" name="accion" value="consultar">
                            <input type="submit" value="Consultar">
                        </form>
                    </div>
                    
                    
                    </div>
             <p></p>
             
             
             <script>
        function imprimirPagina() {
            console.log("Intentando imprimir la página...");
            window.print();
        }
    </script>
             
             
             
             <button class="icono-Imprimir"  style="background: none; border: none; padding: 0;" onclick="imprimirPagina()">
                 
                  <img src="/Pedidos_GA/Img/Botones%20entregas/Inicio/DETPED/IMPNA.png" alt="Estaditicas" class="icono-imprimir" style="max-width: 50%; height: auto;">
                 
                </button>
             </div>
        
        
        
        <!--

 <form action="ActualizarPedido.php" method="GET">
    <input type="hidden" name="id" value="<?php echo $row['ID']; ?>">
    
            <button type="submit" class="icono-ActualizarP" style="background: none; border: none; padding: 0;">
                
        <img src="/Pedidos_GA/Img/Botones%20entregas/Inicio/DETPED/ACTPEDAZ.png" alt="Estaditicas" class="icono-ActP" style="max-width: 50%; height: auto;">
                
          </button>
</form>

-->
        
        
        <?php
            } else {
                echo "No se encontraron detalles para el pedido con el ID proporcionado.";
            }
        } else {
            echo '<img src="\Pedidos_GA\Img\Encabezado.png" alt="Descripción de la imagen">';
            //javascript:history.go(-1)
            echo '<button onclick="javascript:history.go(-1);">Volver</button>';
            
        }

        // Cerrar la conexión a la base de datos
       
        ?>
        
        
        <h1>
        
         <img src="/Pedidos_GA/Img/Botones%20entregas/Inicio/DETPED/MOSTIMAZ.png" alt="Imagen" class="icono-Imagen" style="max-width: 50%; height: auto;">
        
        </h1>
    <div id="imagen-container">
        <img id="miniatura" src="" alt="Miniatura de la imagen de pedido" data-toggle="modal" data-target="#modal-imagen">
    </div>

    <!-- Modal para mostrar la imagen en tamaño completo -->
    <div class="modal fade" id="modal-imagen" tabindex="-1" role="dialog" aria-labelledby="modal-imagen-label" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-imagen-label">Vista previa de la imagen</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <img id="imagen" src="" alt="Imagen de pedido">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary" onclick="zoomIn()">Zoom In</button>
                    <button type="button" class="btn btn-primary" onclick="zoomOut()">Zoom Out</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        var scaleFactor = 1; // Factor de escala inicial

        // Función para hacer zoom in
        function zoomIn() {
            scaleFactor += 0.1; // Incrementa el factor de escala
            aplicarZoom();
        }

        // Función para hacer zoom out
        function zoomOut() {
            scaleFactor -= 0.1; // Decrementa el factor de escala
            aplicarZoom();
        }

        // Función para aplicar el zoom a la imagen
        function aplicarZoom() {
            var imagen = document.getElementById("imagen");
            imagen.style.transform = "scale(" + scaleFactor + ")";
        }

        // Función para obtener la imagen
        function obtenerImagen() {
            var id_pedido = <?php echo $row["ID"]; ?>; // ID del pedido
            var url = "https://pedidos.grupoascencio.com.mx/Pedidos_GA/App/Ver_Foto.php?id_pedido=" + id_pedido; // URL del archivo PHP

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data[0].found) {
                        var ruta_imagen = data[0].ruta_imagen;
                        document.getElementById("miniatura").src = ruta_imagen;
                        document.getElementById("imagen").src = ruta_imagen;
                    } else {
                        document.getElementById("imagen-container").innerText = "Imagen no encontrada";
                    }
                })
                .catch(error => {
                    console.error('Error al obtener la imagen:', error);
                    document.getElementById("imagen-container").innerText = "Error al obtener la imagen";
                });
        }

        window.onload = obtenerImagen;
    </script>

        <p></p>
        
        
         <h2>
        
        <img src="/Pedidos_GA/Img/Botones%20entregas/Inicio/DETPED/DETACTAZ.png" alt="Estaditicas" class="icono-imprimir" style="max-width: 50%; height: auto;">
        
        </h2>
            <table class="table-custom">
                <thead>
                    <tr>
                       <!-- <th>ID</th>-->
                       <!-- <th>ID_Pedido</th>-->
                        <th>Estado</th>
                        <th>Fecha</th>
                        <th>Hora</th>
                        <th>Coordenada</th>
                    </tr>
                </thead>
                <tbody>

                    <?php

// Consulta SQL para obtener los detalles del pedido desde estadopedido
$detalleSql = "SELECT Estado, Fecha, Hora, Coordenada FROM EstadoPedido WHERE ID_Pedido = ?";
$detalleStmt = $conn->prepare($detalleSql);
$detalleStmt->bind_param("i", $pedidoId);

$detalleStmt->execute();
$detalleResult = $detalleStmt->get_result();

// Iterar sobre los resultados y mostrarlos en la tabla
while ($detalleRow = $detalleResult->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $detalleRow["Estado"] . "</td>";
    echo "<td>" . $detalleRow["Fecha"] . "</td>";
    echo "<td>" . $detalleRow["Hora"] . "</td>";

    // Separar las coordenadas en latitud y longitud si están en un solo campo
    $coordenadas = explode(',', $detalleRow["Coordenada"]);
    $latitud = trim($coordenadas[0]);
    $longitud = isset($coordenadas[1]) ? trim($coordenadas[1]) : null;

    if ($latitud && $longitud) {
        // Crear el enlace a Google Maps
        $googleMapsLink = "https://www.google.com/maps/search/?api=1&query=$latitud,$longitud";
        // Agregar un botón que abra el enlace en una nueva pestaña
        echo "<td><a href='$googleMapsLink' target='_blank'>
        <button style=".'background: none; border: none; padding: 0;'."> 
        Ver en Google Maps
          <!-- <img src=".'/Pedidos_GA/Img/Botones%20entregas/Inicio/DETPED/'." alt=".'icono-Maps2'." class=".'icono-Maps2-img'." style=".'max-width: 10%;'.">-->
         
        </button></a></td>";
    } else {
        // Manejar el caso en que las coordenadas no son válidas
        echo "<td>Coordenadas no disponibles</td>";
    }

    echo "</tr>";
}

// Cerrar el statement de detalle y la conexión
$detalleStmt->close();
$conn->close();
?>


                   
                 
                </tbody>
            </table>
        <p></p>
       
        
    </div>
    
     
    
</body>
</html>

<?php

// Función para cargar archivos

function cargarArchivo($pedidoId) {
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['archivo'])) {
        $file = $_FILES['archivo'];

        // Verificar si se cargó un archivo correctamente
        if ($file['error'] === UPLOAD_ERR_OK) {
            $fileName = $file['name'];
            $fileTmpName = $file['tmp_name'];
            $fileSize = $file['size'];
            $fileType = $file['type'];

            // Obtener la extensión del archivo
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            // Permitir solo ciertos tipos de archivos
            $allowedExtensions = array("pdf", "doc", "docx", "txt");
            if (in_array($fileExt, $allowedExtensions)) {
                // Usar ruta relativa desde este archivo PHP
                // realpath convierte la ruta relativa en absoluta correctamente
                $uploadDirFull = realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'Archivos' . DIRECTORY_SEPARATOR;

                // Verificar si el directorio existe, si no, crearlo
                if (!file_exists($uploadDirFull)) {
                    mkdir($uploadDirFull, 0777, true);
                }

                // Ruta relativa que se guardará en la base de datos
                $uploadDirRelative = 'Archivos/';

                // Crear un nombre único para el archivo
                $uniqueName = uniqid("pedido_" . $pedidoId . "_") . "." . $fileExt;

                // Mover el archivo al directorio de carga completo
                $filePathFull = $uploadDirFull . $uniqueName;
                if (move_uploaded_file($fileTmpName, $filePathFull)) {
                    // Ruta relativa del archivo para guardar en la base de datos
                    $filePathRelative = $uploadDirRelative . $uniqueName;

                    // Guardar la ruta relativa del archivo en la base de datos
                    $servername = "18.211.75.118";
                $username = "pedidos_app";                   
                 $password = 'TuContraseñaSegura123!';
                    $database = "gpoascen_pedidos_app";

                    $conn = new mysqli($servername, $username, $password, $database);

                    if ($conn->connect_error) {
                        die("Error de conexión: " . $conn->connect_error);
                    }

                    // Actualizar la ruta del archivo en la base de datos
                    $sql = "UPDATE pedidos SET Ruta = ? WHERE ID = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("si", $filePathRelative, $pedidoId);
                    $stmt->execute();

                    // Mostrar mensaje de éxito
                    echo '<div style="background:#e7f9e7;border:1px solid #cfeacf;padding:16px;border-radius:8px;margin:16px 0;">';
                    echo '<h3 style="color:#217a21;margin:0 0 8px 0;">✅ Operación realizada con éxito</h3>';
                    echo '<p style="margin:0;color:#217a21;">Archivo: <strong>' . htmlspecialchars($fileName) . '</strong></p>';
                    echo '<p style="margin:4px 0 0 0;color:#217a21;">Guardado como: <strong>' . htmlspecialchars($uniqueName) . '</strong></p>';

                    // Mostrar enlace de descarga según el tipo de archivo
$downloadUrl = '/Pedidos_GA/' . $filePathRelative;
                    echo '<p style="margin:8px 0 0 0;"><a href="' . $downloadUrl . '" target="_blank" style="color:#0a66c2;text-decoration:none;font-weight:600;">📄 Ver/Descargar archivo</a></p>';
                    echo '</div>';

                    // Mostrar alerta de éxito utilizando JavaScript
                    echo "<script>alert('El archivo se cargó correctamente.');</script>";
                } else {
                    $phpError = error_get_last();
                    echo "<script>alert('Error al mover el archivo: " . ($phpError ? addslashes($phpError['message']) : 'move_uploaded_file falló') . "');</script>";
                    echo "Hubo un error al mover el archivo. Verifique permisos del directorio.";
                }
            } else {
                echo "<script>alert('Solo se permiten archivos PDF, DOC, DOCX y TXT. Extensión detectada: $fileExt');</script>";
                echo "Solo se permiten archivos PDF, DOC, DOCX y TXT.";
            }
        } else {
            $errorMessages = array(
                UPLOAD_ERR_INI_SIZE => 'El archivo excede el tamaño máximo permitido en php.ini',
                UPLOAD_ERR_FORM_SIZE => 'El archivo excede el tamaño máximo permitido en el formulario',
                UPLOAD_ERR_PARTIAL => 'El archivo se subió parcialmente',
                UPLOAD_ERR_NO_FILE => 'No se seleccionó ningún archivo',
                UPLOAD_ERR_NO_TMP_DIR => 'Falta carpeta temporal',
                UPLOAD_ERR_CANT_WRITE => 'Error al escribir en disco',
                UPLOAD_ERR_EXTENSION => 'Una extensión de PHP detuvo la carga'
            );
            $errorMsg = isset($errorMessages[$file['error']]) ? $errorMessages[$file['error']] : 'Error desconocido: ' . $file['error'];
            echo "<script>alert('Error al cargar archivo: $errorMsg');</script>";
            echo "Hubo un error al cargar el archivo.";
        }
    }
}




// Función para descargar archivos
function descargarArchivo($pedidoId) {
    // Consultar la ruta del archivo en la base de datos
                    $servername = "18.211.75.118";
                $username = "pedidos_app";                  
                  $password = 'TuContraseñaSegura123!';
                    $database = "gpoascen_pedidos_app";
    $conn = new mysqli($servername, $username, $password, $database);

    if ($conn->connect_error) {
        die("Error de conexión: " . $conn->connect_error);
    }

    $sql = "SELECT Ruta FROM pedidos WHERE ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $pedidoId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $rutaArchivo = $row['Ruta'];

        // Descargar el archivo
        if (file_exists($rutaArchivo)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($rutaArchivo) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($rutaArchivo));
            readfile($rutaArchivo);
            exit;
        } else {
            echo "El archivo no existe.";
        }
    } else {
        echo "No se encontró el pedido.";
    }

    $conn->close();
}

// Función para consultar archivos con vista previa
function consultarArchivo($pedidoId) {
    // Configuración de conexión a la base de datos
    $servername = "18.211.75.118";
$username = "pedidos_app";    
$password = 'TuContraseñaSegura123!';
    $database = "gpoascen_pedidos_app";

    // Conectar a la base de datos
    $conn = new mysqli($servername, $username, $password, $database);

    if ($conn->connect_error) {
        die("Error de conexión: " . $conn->connect_error);
    }

    // Consultar la ruta del archivo
    $sql = "SELECT Ruta FROM pedidos WHERE ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $pedidoId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $rutaArchivoRelativa = $row['Ruta'];

        // Si no hay archivo cargado
        if (empty($rutaArchivoRelativa)) {
            echo '<div style="background:#fff6d6;border:1px solid #fde9a8;padding:16px;border-radius:8px;margin:16px 0;">';
            echo '<h3 style="color:#8a6d00;margin:0 0 8px 0;">⚠️ Sin archivo</h3>';
            echo '<p style="margin:0;color:#8a6d00;">No hay ningún archivo cargado para este pedido.</p>';
            echo '</div>';
            return;
        }

        // Construir la ruta completa del archivo
        $rutaArchivoCompleta = realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR . $rutaArchivoRelativa;

        // URL para el navegador
$urlArchivo = '/Pedidos_GA/' . $rutaArchivoRelativa;

        // Verificar si el archivo existe en el servidor
        if (!file_exists($rutaArchivoCompleta)) {
            echo '<div style="background:#ffe4e6;border:1px solid #fecdd3;padding:16px;border-radius:8px;margin:16px 0;">';
            echo '<h3 style="color:#9f1239;margin:0 0 8px 0;">❌ Archivo no encontrado</h3>';
            echo '<p style="margin:0;color:#9f1239;">El archivo no existe en el servidor.</p>';
            echo '<p style="margin:4px 0 0 0;color:#9f1239;font-size:12px;">Ruta buscada: ' . htmlspecialchars($rutaArchivoCompleta) . '</p>';
            echo '</div>';
            return;
        }

        // Obtener la extensión del archivo
        $fileExt = strtolower(pathinfo($rutaArchivoCompleta, PATHINFO_EXTENSION));
        $fileName = basename($rutaArchivoCompleta);

        // Mostrar información del archivo
        echo '<div style="background:#e7f9e7;border:1px solid #cfeacf;padding:16px;border-radius:8px;margin:16px 0;">';
        echo '<h3 style="color:#217a21;margin:0 0 8px 0;">📄 Archivo encontrado</h3>';
        echo '<p style="margin:0;color:#217a21;"><strong>Nombre:</strong> ' . htmlspecialchars($fileName) . '</p>';
        echo '<p style="margin:4px 0 0 0;color:#217a21;"><strong>Tipo:</strong> ' . strtoupper($fileExt) . '</p>';
        echo '<p style="margin:8px 0 0 0;"><a href="' . htmlspecialchars($urlArchivo) . '" target="_blank" style="display:inline-block;background:#0a66c2;color:#fff;padding:10px 16px;border-radius:8px;text-decoration:none;font-weight:600;">📥 Descargar/Ver archivo</a></p>';
        echo '</div>';

        // Mostrar vista previa según el tipo de archivo
        if ($fileExt === "pdf") {
            echo '<div style="margin:16px 0;">';
            echo '<h4 style="margin:0 0 8px 0;">Vista previa del PDF:</h4>';
            echo '<embed src="' . htmlspecialchars($urlArchivo, ENT_QUOTES, 'UTF-8') . '" type="application/pdf" width="100%" height="600px" style="border:1px solid #e5e7eb;border-radius:8px;" />';
            echo '</div>';
        } elseif ($fileExt === "txt") {
            echo '<div style="margin:16px 0;">';
            echo '<h4 style="margin:0 0 8px 0;">Contenido del archivo:</h4>';
            $fileContent = file_get_contents($rutaArchivoCompleta);
            echo '<textarea style="width:100%;min-height:400px;padding:12px;border:1px solid #e5e7eb;border-radius:8px;font-family:monospace;" readonly>' . htmlspecialchars($fileContent, ENT_QUOTES, 'UTF-8') . '</textarea>';
            echo '</div>';
        } elseif (in_array($fileExt, array("doc", "docx"))) {
            echo '<div style="background:#eef2ff;border:1px solid #e5e7eb;padding:16px;border-radius:8px;margin:16px 0;">';
            echo '<p style="margin:0;color:#1e3a8a;">ℹ️ Los archivos de Word (.doc/.docx) no se pueden previsualizar. Por favor descargue el archivo para verlo.</p>';
            echo '</div>';
        } else {
            echo '<div style="background:#eef2ff;border:1px solid #e5e7eb;padding:16px;border-radius:8px;margin:16px 0;">';
            echo '<p style="margin:0;color:#1e3a8a;">ℹ️ No se puede mostrar una vista previa para este tipo de archivo.</p>';
            echo '</div>';
        }
    } else {
        echo '<div style="background:#ffe4e6;border:1px solid #fecdd3;padding:16px;border-radius:8px;margin:16px 0;">';
        echo '<h3 style="color:#9f1239;margin:0;">❌ No se encontró el pedido</h3>';
        echo '</div>';
    }

    // Cerrar la conexión a la base de datos
    $conn->close();
}


// Utilizar las funciones según la acción requerida
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accion']) && isset($_POST['id'])) {
    $pedidoId = $_POST['id'];
    $accion = $_POST['accion'];

    if ($accion === "cargar") {
        cargarArchivo($pedidoId);
    } elseif ($accion === "descargar") {
        descargarArchivo($pedidoId);
    } elseif ($accion === "consultar") {
        consultarArchivo($pedidoId);
    }
}

// Función para obtener la ruta del archivo cargado para un pedido dado
function obtenerRutaArchivoCargado($pedidoId) {
    $rutaArchivo = null;

    // Consultar la ruta del archivo en la base de datos
                    $servername = "18.211.75.118";
                    $username = "pedidos_app";
                    $password = 'TuContraseñaSegura123!';
                    $database = "gpoascen_pedidos_app";

    $conn = new mysqli($servername, $username, $password, $database);

    if ($conn->connect_error) {
        die("Error de conexión: " . $conn->connect_error);
    }

    $sql = "SELECT Ruta FROM pedidos WHERE ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $pedidoId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $rutaArchivo = $row['Ruta'];
    }

    $conn->close();

    return $rutaArchivo;
}

?>