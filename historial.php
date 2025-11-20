<?php
// Iniciar sesión de forma segura
ini_set('session.cookie_httponly', true);
ini_set('session.cookie_secure', true);
session_name("GA");
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: /Pedidos_GA/Sesion/login.html");
    exit;
}

require_once __DIR__ . "/Conexiones/Conexion.php";

$sucursalUsuario = $_SESSION["Sucursal"];
$rolUsuario = $_SESSION["Rol"];

$consultaHistorial = null;

if ($rolUsuario === "Admin") {
    $consultaHistorial = $conn->query("
        SELECT 
            h.ID,
            h.Usuario_ID,
            h.Pedido_ID,
            h.Cambio,
            h.Fecha_Hora,
            u.username,
            u.Nombre
        FROM historial_cambios h
        JOIN usuarios u ON h.Usuario_ID = u.username
        ORDER BY h.ID DESC
    ");
}

if (!$consultaHistorial) {   
    header("location: /Pedidos_GA/Sesion/login.html");
    die("Error en la consulta: " . $conn->error);
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial de Cambios</title>

    <link rel="stylesheet" href="styles.css">
    <link rel="icon" type="image/png" href="/Pedidos_GA/Img/Botones%20entregas/ICONOSPAG/ICONOPEDIDOS.png">

   
    <style>
        .container {
            padding: 30px;
        }

        .card-cambio {
            background: #ecf0f1;
            border-radius: 8px;
            padding: 10px 15px;
            margin: 8px 0;
            text-align: left;
            box-shadow: 0px 1px 4px rgba(0,0,0,0.1);
        }

        .card-cambio strong {
            color: #34495e;
        }

        .card-cambio span {
            display: block;
            margin-top: 3px;
        }

        .card-cambio .antes {
            color: #7f8c8d;
        }

        .card-cambio .despues {
            color: #27ae60;
        }

        .col-cambios {
            min-width: 400px;
            width: 45%;
            max-width: 600px;
            text-align: center;
        }

        .table-container {
            overflow-x: auto;
            
        }

        #tablaHistorial {
            width: 100% !important;
            
        }


        /* Tabla general */
#tablaHistorial {
    border-collapse: collapse;
    width: 100%;
    background-color: #fff;
    border-radius: 8px;
    overflow: hidden;
    font-size: 14px;
   
}

/* Encabezados */
#tablaHistorial th {
    background-color: #1F3A93;
    color: white;
    padding: 12px;
    text-align: center;
    
}

/* Celdas */
#tablaHistorial td {
    padding: 10px;
    border-bottom: 1px solid #ddd;
    vertical-align: middle;
    text-align: center;
    
}

/* Hover en filas */
#tablaHistorial tbody tr:hover {
    background-color: #f1f1f1;
}

/* Buscador */
.dataTables_filter input {
    border: 1px solid #ccc;
    border-radius: 6px;
    padding: 6px 10px;
    margin-left: 5px;
    background-color: #f9f9f9;
    transition: 0.3s;
    margin: 12px;
    width: 60%;
}

.dataTables_filter input:focus {
    border-color: #1F3A93;
    outline: none;
}

/* Selector de cantidad */
.dataTables_length select {
    border: 1px solid #ccc;
    border-radius: 6px;
    padding: 4px 8px;
    background-color: #f9f9f9;
    transition: 0.3s;
    margin: 12px;
}

.dataTables_length select:focus {
    border-color: #1F3A93;
    outline: none;
}

/* Paginación */
.dataTables_paginate a {
    background-color: #fff;

    color: #1F3A93;
    padding: 12px;
    border: 1px solid #1F3A93;
    border-radius: 6px;
    margin: 12px;
    transition: 0.3s;
    cursor: pointer;
    font-weight: bold;
    text-decoration: none;
    display: inline-block;
    text-align: center;
   
    

}

.dataTables_paginate a.current,
.dataTables_paginate a:hover {
    background-color: #1F3A93;
    color: white !important;
    border: 1px solid #1F3A93;
}


        
    </style>

    <!-- JQuery + DataTables -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
   
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
   </head>

<body>

<div class="sidebar">
    <ul>
        <li class="corner-left-bottom">
            <a href="Pedidos_GA.php">
                <img src="/Pedidos_GA/Img/Botones%20entregas/Usuario/VOLVAZ.png" alt="Volver" class="icono-Volver" style="max-width: 35%; height: auto;">
            </a>
        </li>
    </ul>
</div>

<div class="container">
    <h1>Historial de Cambios</h1>

    <div class="table-container">
        <table id="tablaHistorial" class="mi-tabla display nowrap">
            <thead>
                <tr>
                    <th>ID &#x2195;</th>
                    <th>Fecha Hora &#x2195;</th>
                    <th>Usuario &#x2195;</th>
                    <th>Nombre Usuario &#x2195;</th>
                    <th>ID Pedido &#x2195;</th>
                    <th class="col-cambios">Cambio Realizado &#x2195;</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($registro = $consultaHistorial->fetch_assoc()) { ?>
                <tr>
                    <td><?= $registro['ID'] ?></td>
                    <td><?= $registro['Fecha_Hora'] ?></td>
                    <td><?= $registro['username'] ?></td>
                    <td><?= $registro['Nombre'] ?></td>
                    <td><?= $registro['Pedido_ID'] ?></td>
                    <td>
                        <?php 
                        $cambios = explode('|', $registro['Cambio']); 
                        foreach($cambios as $cambio){
                            $partes = explode('→', $cambio);
                            if(count($partes) == 2){
                                echo "<div class='card-cambio'>
                                        <strong>" . trim($partes[0]) . "</strong>
                                        <span class='antes'>Antes: " . (isset(explode(':', $partes[0])[1]) ? trim(explode(':', $partes[0])[1]) : '') . "</span>
                                        <span class='despues'>Después: " . trim($partes[1]) . "</span>
                                    </div>";
                            } else {
                                echo "<div class='card-cambio'>" . trim($cambio) . "</div>";
                            }
                        }
                        ?>
                    </td>
                    <td><a href="Inicio.php?id=<?= $registro['Pedido_ID'] ?>">Ver Detalles</a></td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<script>
$(document).ready(function () {
    $('#tablaHistorial').DataTable({
        responsive: true,
        scrollX: true,
        pageLength: 10,
        lengthMenu: [5, 10, 25, 50, 100],
        order: [[0, 'desc']],
        language: {
            search: "Buscar:",
            lengthMenu: "Mostrar _MENU_ registros por página",
            zeroRecords: "No se encontraron cambios",
            info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
            infoEmpty: "No hay registros disponibles",
            infoFiltered: "(filtrado de _MAX_ registros totales)",
            paginate: {
                first: "Primero",
                last: "Último",
                next: "Siguiente",
                previous: "Anterior"
            },
        },
        columnDefs: [
            { targets: 5, width: "45%" },
            { targets: '_all', className: 'dt-center' }
        ]
    });
});
</script>

</body>
</html>
