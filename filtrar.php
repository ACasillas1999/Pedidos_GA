<?php
session_name("GA");
session_start();

// Verificar si el usuario no está logeado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: /Pedidos_GA/Sesion/login.html");
    exit;
}

// Establecer la conexión a la base de datos
require_once __DIR__ . "/Conexiones/Conexion.php";

$sucursalSesion = strtoupper($_SESSION["Sucursal"] ?? "");
$rolSesion      = $_SESSION["Rol"] ?? "";

// Obtener el offset para la paginación (por defecto 0)
$offset = 0;
if (isset($_POST['offset'])) {
    $offset = intval($_POST['offset']);
}

if ($sucursalSesion === "TODAS") {
    // ADMIN ELIGE DESDE EL SELECT
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['sucursal']) && isset($_POST['estados'])) {
        $sucursalSelect = strtoupper($_POST['sucursal'] ?? "TODAS");
        $estados = json_decode($_POST['estados']); // Decodificar el JSON de los estados

        // Construir la condición para los estados
        $estadoConditions = [];
        foreach ($estados as $estado) {
            $estadoConditions[] = "ESTADO='" . $conn->real_escape_string($estado) . "'";
        }
        $estadoFilter = implode(" OR ", $estadoConditions);

        // Condición para la sucursal (si no es TODAS)
        $sucursalCondition = ($sucursalSelect != 'TODAS') ? "AND SUCURSAL='" . $conn->real_escape_string($sucursalSelect) . "'" : "";

        // Consulta con LIMIT para paginación (100 por página)
        $sql = "SELECT * FROM pedidos WHERE ($estadoFilter) $sucursalCondition
                ORDER BY FECHA_RECEPCION_FACTURA DESC
                LIMIT $offset, 100";
        $result = $conn->query($sql);

        if ($result === false) {
            echo "Error en la consulta: " . $conn->error;
        } else {
            if ($result->num_rows > 0) {
                echo "<table class='mi-tabla' border='1'>";
                echo "<tr>
                        <th>N°</th>
                        <th>Factura (caja)</th>
                        <th>Estado</th>
                        <th>Tipo Envío</th>
                        <th>Sucursal</th>
                        <th>Fecha Recepción Factura</th>
                        <th>Chofer Asignado</th>
                        <th>Vendedor</th>
                        <th>Factura</th>
                        <th>Dirección</th>
                        <th>Nombre Cliente</th>
                        <th>Contacto</th>
                        <th>Acción</th>
                      </tr>";
                while ($row = $result->fetch_assoc()) {
                    $estado = $row["ESTADO"];
                    $colorEstado = "#FFFFFF"; // Color por defecto (blanco)
                    switch (strtoupper($estado)) {
                        case "CANCELADO":    $colorEstado = "#FFCCCC"; break;
                        case "EN TIENDA":    $colorEstado = "#FFFFCC"; break;
                        case "REPROGRAMADO": $colorEstado = "#E6CCFF"; break;
                        case "ACTIVO":       $colorEstado = "#CCE5FF"; break;
                        case "EN RUTA":      $colorEstado = "#FFD699"; break;
                        case "ENTREGADO":    $colorEstado = "#CCFFCC"; break;
                    }

                    $tipo_envio = $row["tipo_envio"] ?? '';
                    $colorEnvio = "#FFFFFF"; // Por defecto
                    switch (strtolower($tipo_envio)) {
                        case "programado":  $colorEnvio = "#e0ffd9ff"; break; // Verde suave
                        case "paquetería":
                        case "paqueteria":  $colorEnvio = "#edc6ffff"; break; // Lila claro
                        case "domicilio":   $colorEnvio = "#e0ffd9ff"; break; // Verde suave
                    }

                    $esPaqueteria = in_array(mb_strtolower($tipo_envio, 'UTF-8'), ['paquetería','paqueteria']);
$btnPaqueteria = '';
if ($esPaqueteria) {
    $btnPaqueteria = "<div style='margin-top:6px'>
                        <a class='btn btn-sm btn-secondary' href='descargar_plantilla_paqueteria.php?id={$row["ID"]}' target='_blank'>
                          Plantilla
                        </a>
                      </div>";
}


                    $choferAsignado = $row["CHOFER_ASIGNADO"];
                    $colorChofer = empty($choferAsignado) ? "#FFCCCC" : "#FFFFFF";

                    $estadoFactura = intval($row["estado_factura_caja"] ?? 0);
                    $badge = '';
                    $accionHtml = '';

                    switch ($estadoFactura) {
                        case 0:
                            $badge = "<span class='badge badge-azul'>En Caja</span>";
                            if (in_array($_SESSION["Rol"], ["Admin","JC"])) {
                                $accionHtml = "<button type='button' class='btn btn-sm btn-primary accion-factura' data-id='{$row["ID"]}' data-accion='entregar_jefe'>Entregar a Jefe</button>";
                            }
                            break;
                        case 1:
                            $badge = "<span class='badge badge-amarillo'>Con Jefe de choferes</span>";
                            if (in_array($_SESSION["Rol"], ["Admin","JC"])) {
                                $accionHtml = "<button type='button' class='btn btn-sm btn-success accion-factura' data-id='{$row["ID"]}' data-accion='devolver_caja'>Devolver a Caja</button>";
                            }
                            break;
                        case 2:
                        default:
                            $badge = "<span class='badge badge-verde'>Devuelta a Caja</span>";
                            break;
                    }

                    echo "<tr>";
                    echo "<td>" . $row["ID"] . "</td>";
                    echo "<td>{$badge}<div style='margin-top:6px'>{$accionHtml}</div></td>";
                    echo "<td style='background-color: $colorEstado;'>" . $estado . "</td>";
                   // echo "<td style='background-color: $colorEnvio;'>" . strtoupper(htmlspecialchars($tipo_envio)) . "</td>";
                   echo "<td style='background-color: $colorEnvio; text-align:center;'>
        " . strtoupper(htmlspecialchars($tipo_envio)) . "
        {$btnPaqueteria}
      </td>";

                    echo "<td>" . $row["SUCURSAL"] . "</td>";
                    echo "<td>" . $row["FECHA_RECEPCION_FACTURA"] . "</td>";
                    echo "<td style='background-color: $colorChofer;'>" . $choferAsignado . "</td>";
                    echo "<td>" . $row["VENDEDOR"] . "</td>";
                    echo "<td>" . $row["FACTURA"] . "</td>";
                    echo "<td>" . $row["DIRECCION"] . "</td>";
                    echo "<td>" . $row["NOMBRE_CLIENTE"] . "</td>";
                    echo "<td>" . $row["CONTACTO"] . "</td>";
                    echo "<td><a href='Inicio.php?id=" . $row["ID"] . "'>Ver Detalles</a></td>";
                    echo "</tr>";
                }
                echo "</table>";

                // COUNT (mismas condiciones)
                $sql_count = "SELECT COUNT(*) as total FROM pedidos WHERE ($estadoFilter) $sucursalCondition";
                $result_count = $conn->query($sql_count);
                $total_rows = 0;
                if ($result_count && $row_count = $result_count->fetch_assoc()) {
                    $total_rows = $row_count["total"];
                }
                $total_pages = ceil($total_rows / 100);
                echo "<div class='pagination-info'>Total de registros: $total_rows, Páginas: $total_pages</div>";
            } else {
                echo "No se encontraron resultados.";
            }
        }
    } else {
        echo "Por favor, seleccione una sucursal y al menos un estado.";
    }
} else {
    // NO ADMIN: FILTRA POR SESIÓN, PERO SI ES JC DE TAPATIA => TAPATIA + ILUMINACION
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['sucursal']) && isset($_POST['estados'])) {
        $estados = json_decode($_POST['estados']);

        $estadoConditions = [];
        foreach ($estados as $estado) {
            $estadoConditions[] = "ESTADO='" . $conn->real_escape_string($estado) . "'";
        }
        $estadoFilter = implode(" OR ", $estadoConditions);

        // ---- AQUI LA MAGIA: sucursales visibles para JC TAPATIA ----
        $sucursalCondition = "";
        if (strtoupper($rolSesion) === 'JC' && $sucursalSesion === 'TAPATIA') {
            $sucursalCondition = "AND SUCURSAL IN ('TAPATIA','ILUMINACION')";
        } else {
            // comportamiento normal (una sola sucursal de sesión)
            if ($sucursalSesion != 'TODAS' && $sucursalSesion != '') {
                $sucursalCondition = "AND SUCURSAL='" . $conn->real_escape_string($sucursalSesion) . "'";
            }
        }

        $sql = "SELECT * FROM pedidos
                WHERE ($estadoFilter) $sucursalCondition
                ORDER BY FECHA_RECEPCION_FACTURA DESC
                LIMIT $offset, 100";
        $result = $conn->query($sql);

        if ($result === false) {
            echo "Error en la consulta: " . $conn->error;
        } else {
            if ($result->num_rows > 0) {
                echo "<table class='mi-tabla' border='1'>";
                echo "<tr>
                        <th>N°</th>
                        <th>Factura (caja)</th>
                        <th>Estado</th>
                        <th>Tipo Envío</th>
                        <th>Sucursal</th>
                        <th>Fecha Recepción Factura</th>
                        <th>Chofer Asignado</th>
                        <th>Vendedor</th>
                        <th>Factura</th>
                        <th>Dirección</th>
                        <th>Nombre Cliente</th>
                        <th>Contacto</th>
                        <th>Acción</th>
                      </tr>";
                while ($row = $result->fetch_assoc()) {
                    $estado = $row["ESTADO"];
                    $colorEstado = "#FFFFFF";
                    switch (strtoupper($estado)) {
                        case "CANCELADO":    $colorEstado = "#FFCCCC"; break;
                        case "EN TIENDA":    $colorEstado = "#FFFFCC"; break;
                        case "REPROGRAMADO": $colorEstado = "#E6CCFF"; break;
                        case "ACTIVO":       $colorEstado = "#CCE5FF"; break;
                        case "EN RUTA":      $colorEstado = "#FFD699"; break;
                        case "ENTREGADO":    $colorEstado = "#CCFFCC"; break;
                    }

                    $tipo_envio = $row["tipo_envio"] ?? '';
                    $colorEnvio = "#FFFFFF";
                    switch (strtolower($tipo_envio)) {
                        case "programado":  $colorEnvio = "#e0ffd9ff"; break;
                        case "paquetería":
                        case "paqueteria":  $colorEnvio = "#edc6ffff"; break;
                        case "domicilio":   $colorEnvio = "#e0ffd9ff"; break;
                    }
                    

                    $choferAsignado = $row["CHOFER_ASIGNADO"];
                    $colorChofer = empty($choferAsignado) ? "#FFCCCC" : "#FFFFFF";

                    $estadoFactura = intval($row["estado_factura_caja"] ?? 0);
                    $badge = '';
                    $accionHtml = '';

                    switch ($estadoFactura) {
                        case 0:
                            $badge = "<span class='badge badge-azul'>En Caja</span>";
                            if (in_array($_SESSION["Rol"], ["Admin","JC"])) {
                                $accionHtml = "<button type='button' class='btn btn-sm btn-primary accion-factura' data-id='{$row["ID"]}' data-accion='entregar_jefe'>Entregar a Jefe</button>";
                            }
                            break;
                        case 1:
                            $badge = "<span class='badge badge-amarillo'>Con Jefe de choferes</span>";
                            if (in_array($_SESSION["Rol"], ["Admin","JC"])) {
                                $accionHtml = "<button type='button' class='btn btn-sm btn-success accion-factura' data-id='{$row["ID"]}' data-accion='devolver_caja'>Devolver a Caja</button>";
                            }
                            break;
                        case 2:
                        default:
                            $badge = "<span class='badge badge-verde'>Devuelta a Caja</span>";
                            break;
                    }

                    echo "<tr>";
                    echo "<td>" . $row["ID"] . "</td>";
                    echo "<td>{$badge}<div style='margin-top:6px'>{$accionHtml}</div></td>";
                    echo "<td style='background-color: $colorEstado;'>" . $estado . "</td>";
                    echo "<td style='background-color: $colorEnvio;'>" . strtoupper(htmlspecialchars($tipo_envio)) . "</td>";
                    echo "<td>" . $row["SUCURSAL"] . "</td>";
                    echo "<td>" . $row["FECHA_RECEPCION_FACTURA"] . "</td>";
                    echo "<td style='background-color: $colorChofer;'>" . $choferAsignado . "</td>";
                    echo "<td>" . $row["VENDEDOR"] . "</td>";
                    echo "<td>" . $row["FACTURA"] . "</td>";
                    echo "<td>" . $row["DIRECCION"] . "</td>";
                    echo "<td>" . $row["NOMBRE_CLIENTE"] . "</td>";
                    echo "<td>" . $row["CONTACTO"] . "</td>";
                    echo "<td><a href='Inicio.php?id=" . $row["ID"] . "'>Ver Detalles</a></td>";
                    echo "</tr>";
                }
                echo "</table>";

                // COUNT (mismas condiciones)
                $sql_count = "SELECT COUNT(*) as total FROM pedidos WHERE ($estadoFilter) $sucursalCondition";
                $result_count = $conn->query($sql_count);
                $total_rows = 0;
                if ($result_count && $row_count = $result_count->fetch_assoc()) {
                    $total_rows = $row_count["total"];
                }
                $total_pages = ceil($total_rows / 100);
                echo "<div class='pagination-info'>Total de registros: $total_rows, Páginas: $total_pages</div>";
            } else {
                echo "No se encontraron resultados.";
            }
        }
    } else {
        echo "Por favor, seleccione una sucursal y al menos un estado.";
    }
}

$conn->close();
?>




    <style>
/* --- La celda toma el color del estado --- */
.mi-tabla td:has(.badge-azul){
  background:#e6f0ff; color:#1247d6;
}
.mi-tabla td:has(.badge-amarillo){
  background:#fff6d6; color:#8a6d00;
}
.mi-tabla td:has(.badge-verde){
  background:#e7f9e7; color:#217a21;
}

/* Respira y alinea mejor contenido dentro de la celda coloreada */
.mi-tabla td:has(.badge-azul),
.mi-tabla td:has(.badge-amarillo),
.mi-tabla td:has(.badge-verde){
  padding:10px 12px;
}

/* El badge ya no pinta fondo: solo texto (para que se vea el color de la celda) */
.badge{ padding:0; border-radius:8px; font-size:12px; font-weight:700; }
.badge-azul, .badge-amarillo, .badge-verde{
  background:transparent; color:inherit;
}

/* Separación entre etiqueta y botón */
.mi-tabla td .badge + div{ margin-top:8px; }

/* Botones legibles sobre fondos claros */
.btn{ border:0; padding:6px 10px; border-radius:8px; cursor:pointer; font-weight:600; }
.btn-primary{ background:#2d6cdf; color:#fff; }
.btn-success{ background:#22a06b; color:#fff; }
.btn:disabled{ opacity:.6; cursor:not-allowed; }

/* (opcional) que los links dentro de la celda sigan siendo visibles */
.mi-tabla td:has(.badge-azul) a,
.mi-tabla td:has(.badge-amarillo) a,
.mi-tabla td:has(.badge-verde) a{ color:inherit; }

.btn-secondary {
  background:#6b7280;
  color:#fff;
  text-decoration:none;
  display:inline-block;
  padding:4px 8px;
  border-radius:6px;
  font-size:12px;
}
.btn-secondary:hover {
  background:#4b5563;
}


</style>


