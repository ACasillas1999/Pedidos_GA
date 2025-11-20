<?php
// Iniciar la sesión de forma segura
ini_set('session.cookie_httponly', true); // Sólo permitir cookies de sesión vía HTTP
ini_set('session.cookie_secure', true); // Solo enviar cookies de sesión a través de conexiones HTTPS
session_name("GA");
session_start();

// Verificar si el usuario no está logeado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: /Pedidos_GA/Sesion/login.html");
    exit;
}

require_once __DIR__ . "/Conexiones/Conexion.php";

$sucursal = $_SESSION["Sucursal"];
$rol = $_SESSION["Rol"];

$vehiculos = null; // Inicializar la variable para evitar errores en caso de que no entre a ninguna condición

// Obtener lista de vehículos junto con la fecha del último servicio y kilometraje acumulado
if ($rol === "Admin"||$rol === "MEC") {
    $vehiculos = $conn->query("
        SELECT v.*, 
               (SELECT fecha_registro FROM registro_kilometraje 
                WHERE id_vehiculo = v.id_vehiculo AND Tipo_Registro = 'Servicio'
                ORDER BY id_registro DESC LIMIT 1) AS fecha_ultimo_servicio 
        FROM vehiculos v
    ");
} elseif ($rol === "JC") {
    // Escapar la sucursal para evitar inyección SQL
    $sucursal = $conn->real_escape_string($sucursal);
    
    $vehiculos = $conn->query("
        SELECT v.*,
         (SELECT fecha_registro FROM registro_kilometraje 
                WHERE id_vehiculo = v.id_vehiculo AND Tipo_Registro = 'Servicio'
                ORDER BY id_registro DESC LIMIT 1) AS fecha_ultimo_servicio
        
        FROM vehiculos v
        WHERE v.Sucursal = '$sucursal'
    ");
}

// Verificar si la consulta se ejecutó correctamente
if (!$vehiculos) {
    die("Error en la consulta: " . $conn->error);
}

// Mostrar los vehículos obtenidos (Ejemplo de tabla)
?>

<script>
    document.addEventListener("DOMContentLoaded", function() {  
       
        var iconoAddChofer = document.querySelector(".icono-AddChofer");
        var iconoVolver = document.querySelector(".icono-Volver");
        var iconoEstadisticas = document.querySelector(".icono-estadisticas");
    
<<<<<<< HEAD
            var imgNormalAddChoferes ="/Img/Botones%20entregas/Choferes/ADDSERVMECNA.png";
            var imgHoverCAddhoferes = "/Img/Botones%20entregas/Choferes/ADDSERVMECBLANC.png";
=======
            var imgNormalAddChoferes ="/Pedidos_GA/Img/Botones%20entregas/Choferes/ADDSERVMECNA.png";
            var imgHoverCAddhoferes = "/Pedidos_GA/Img/Botones%20entregas/Choferes/ADDSERVMECBLANC.png";
>>>>>>> parent of 5e8b02c (parra amazon Update image paths and SQL table names)

            // Cambiar la imagen al pasar el mouse para Pendientes
            iconoAddChofer.addEventListener("mouseover", function() {
                iconoAddChofer.src = imgHoverCAddhoferes;
            });

            // Cambiar de vuelta al mover el mouse fuera para Pendientes
            iconoAddChofer.addEventListener("mouseout", function() {
                iconoAddChofer.src = imgNormalAddChoferes;
            });
        
<<<<<<< HEAD
        var imgNormalVolver="/Img/Botones%20entregas/Usuario/VOLVAZ.png";
            var imgHoverVolver = "/Img/Botones%20entregas/Usuario/VOLVNA.png";
=======
        var imgNormalVolver="/Pedidos_GA/Img/Botones%20entregas/Usuario/VOLVAZ.png";
            var imgHoverVolver = "/Pedidos_GA/Img/Botones%20entregas/Usuario/VOLVNA.png";
>>>>>>> parent of 5e8b02c (parra amazon Update image paths and SQL table names)

            // Cambiar la imagen al pasar el mouse para Pendientes
            iconoVolver.addEventListener("mouseover", function() {
                iconoVolver.src = imgHoverVolver;
            });

            // Cambiar de vuelta al mover el mouse fuera para Pendientes
            iconoVolver.addEventListener("mouseout", function() {
                iconoVolver.src = imgNormalVolver;
            });
        
<<<<<<< HEAD
      var imgNormalEstadisticas = "/Img/Botones%20entregas/Pedidos_GA/ESTNA2.png";
      var imgHoverEstadisticas = "/Img/Botones%20entregas/Pedidos_GA/ESTBL2.png";
=======
      var imgNormalEstadisticas = "/Pedidos_GA/Img/Botones%20entregas/Pedidos_GA/ESTNA2.png";
      var imgHoverEstadisticas = "/Pedidos_GA/Img/Botones%20entregas/Pedidos_GA/ESTBL2.png";
>>>>>>> parent of 5e8b02c (parra amazon Update image paths and SQL table names)
      iconoEstadisticas.addEventListener("mouseover", function() {
          iconoEstadisticas.src = imgHoverEstadisticas;
      });
      iconoEstadisticas.addEventListener("mouseout", function() {
          iconoEstadisticas.src = imgNormalEstadisticas;
      });
        
     });
    
    </script> 

<!DOCTYPE html>
<html>
<head>
    <title>Gestión de Vehículos</title>
    <link rel="stylesheet" href="styles.css">
<<<<<<< HEAD
    <link rel="icon" type="image/png" href="/Img/Botones%20entregas/ICONOSPAG/ICONOPEDIDOS.png">
=======
    <link rel="icon" type="image/png" href="/Pedidos_GA/Img/Botones%20entregas/ICONOSPAG/ICONOPEDIDOS.png">
>>>>>>> parent of 5e8b02c (parra amazon Update image paths and SQL table names)
   
</head>
<body>


<style>

input[type="text"] {
    width: 100%;
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 16px;
    box-sizing: border-box;
    background-color: #f9f9f9;
}

    </style>
    
<div class="sidebar">
     
     
     <ul>
          <?php if ($_SESSION["Rol"] === "Admin"): ?>
         <li><a href="NuevoVehiculo.php">
             <img src="\Pedidos_GA\Img\Botones entregas\Choferes\ADDSERVMECNA.png" alt="Estaditicas " class = "icono-AddChofer"style="max-width: 80%; height: auto;">
             
            </a></li>
          <?php endif; ?>

          <li><a href="Estadisticas_Vehiculos.php">
          <img src="\Pedidos_GA\Img\Botones entregas\Pedidos_GA\ESTNA2.png" alt="Estaditicas" class="icono-estadisticas" style="max-width: 80%; height: auto;">
             
            </a></li>
         
         
         
                <li class="corner-left-bottom"><a href="Pedidos_GA.php">
<<<<<<< HEAD
             <img src="/Img/Botones%20entregas/Usuario/VOLVAZ.png" alt="Estaditicas " class = "icono-Volver"style="max-width: 35%; height: auto;">
=======
             <img src="/Pedidos_GA/Img/Botones%20entregas/Usuario/VOLVAZ.png" alt="Estaditicas " class = "icono-Volver"style="max-width: 35%; height: auto;">
>>>>>>> parent of 5e8b02c (parra amazon Update image paths and SQL table names)
             
            </a></li>
     </ul>
    
 </div>

 <div class="container">
    <h1>Gestión de Vehículos</h1>
    
  

    <input type="text" id="searchInput" onkeyup="filterTable()" placeholder="Buscar en todos los campos...">
    <p></p>
    <table id = 'employeeTable' class='mi-tabla' border='1'>

        <tr><th onclick="sortTable(0, false)">Placa &#x2195;</th>
            <th onclick="sortTable(1, false)">N°serie &#x2195;</th>
            <th onclick="sortTable(2, false)">Tipo &#x2195;</th>
            <th onclick="sortTable(3, false)">Sucursal &#x2195;</th>
            <th onclick="sortTable(4, false)">Km Actual sin servicio &#x2195;</th>
            <th onclick="sortTable(5, false)">Km Total &#x2195;</th>
            <th onclick="sortTable(6, false)">Último Servicio &#x2195;</th>
            <th>Acción</th>
            <?php if ($_SESSION["Rol"] === "Admin"): ?>
            <th>Acción</th>
            <?php endif;?>
        </tr>
        <?php while ($vehiculo = $vehiculos->fetch_assoc()) { 
            $fecha_ultimo_servicio = $vehiculo['fecha_ultimo_servicio'] ?? "No registrado";
            $kilometraje_faltante = $vehiculo['Km_de_Servicio'] - $vehiculo['Km_Actual'];

if ($kilometraje_faltante <= 0) {
    $color = 'red'; // Ya pasó el mantenimiento
} elseif ($kilometraje_faltante <= 500) {
    $color = 'orange'; // Advertencia: mantenimiento muy próximo
} elseif ($kilometraje_faltante <= 1000) {
    $color = '#007bff'; // Precaución: pronto llegará el mantenimiento
} else {
    $color = 'green'; // Todo en orden
}
        ?>
            <tr>
    <td><?= $vehiculo['placa'] ?></td>
    <td><?= $vehiculo['numero_serie'] ?></td>
    <td><?= $vehiculo['tipo'] ?></td>
    <td><?= $vehiculo['Sucursal'] ?></td>
    <td><span style="color: <?= $color ?>;"> <?= $vehiculo['Km_Actual'] ?> </span></td>
    <td><?= $vehiculo['Km_Total'] ?></td>
    <td><?= $fecha_ultimo_servicio ?></td>
    <td><a href="detalles_vehiculo.php?id=<?= $vehiculo['id_vehiculo'] ?>">Ver Detalles</a></td>
    <?php if ($_SESSION["Rol"] === "Admin"): ?>
    <td>
        <a href="ActualizarVehiculo.php?id=<?= $vehiculo['id_vehiculo'] ?>" class="btn btn-warning">Editar</a>
    </td>
    <?php endif; ?>
</tr>

        <?php } ?>
    </table>
        </div>
</body>
</html>



<script>
        function filterTable() {
            let input = document.getElementById("searchInput").value.toLowerCase();
            let table = document.getElementById("employeeTable");
            let rows = table.getElementsByTagName("tr");

            for (let i = 1; i < rows.length; i++) { // Empieza en 1 para saltarse el encabezado
                let cells = rows[i].getElementsByTagName("td");
                let rowContainsFilter = false;

                for (let j = 0; j < cells.length; j++) {
                    let cellText = cells[j].textContent || cells[j].innerText;
                    if (cellText.toLowerCase().includes(input)) {
                        rowContainsFilter = true;
                        break;
                    }
                }

                rows[i].style.display = rowContainsFilter ? "" : "none";
            }
        }

        function sortTable(columnIndex, isNumeric) {
            let table = document.getElementById("employeeTable");
            let rows = Array.from(table.rows).slice(1); // Obtener todas las filas exceptuando el encabezado
            let ascending = table.getAttribute('data-sort') === 'asc' ? true : false;

            // Si la columna es numérica, comparamos como números
            rows.sort((rowA, rowB) => {
                let cellA = rowA.cells[columnIndex].textContent.trim();
                let cellB = rowB.cells[columnIndex].textContent.trim();

                if (isNumeric) {
                    return ascending ? parseFloat(cellA) - parseFloat(cellB) : parseFloat(cellB) - parseFloat(cellA);
                } else {
                    return ascending ? cellA.localeCompare(cellB) : cellB.localeCompare(cellA);
                }
            });

            // Colocar las filas ordenadas en la tabla
            rows.forEach(row => table.appendChild(row));
            // Alternar la dirección de ordenación
            table.setAttribute('data-sort', ascending ? 'desc' : 'asc');
        }
    </script>