<?php
session_name("GA");
session_start();

// Verificar si el usuario está logeado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: /Pedidos_GA/Sesion/login.html");
    exit;
}

// Solo Admin y JC pueden ver este reporte
if (!in_array($_SESSION["Rol"], ["Admin", "JC"])) {
    echo "<script>alert('No tienes permisos para acceder a este reporte.'); window.location.href='Pedidos_GA.php';</script>";
    exit;
}

require_once __DIR__ . "/Conexiones/Conexion.php";

// Obtener parámetros de filtro
$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : date('Y-m-01'); // Primer día del mes actual
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : date('Y-m-d'); // Hoy
$sucursal_filtro = isset($_GET['sucursal']) ? $_GET['sucursal'] : 'TODAS';
$vendedor_filtro = isset($_GET['vendedor']) ? $_GET['vendedor'] : 'TODOS';
$estado_filtro = isset($_GET['estado']) ? $_GET['estado'] : 'ACTIVOS'; // Default: ACTIVOS
$seccion = isset($_GET['seccion']) ? $_GET['seccion'] : 'resumen';

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="/Pedidos_GA/Img/Botones%20entregas/ICONOSPAG/ICONOPEDIDOS.png">
    <link rel="stylesheet" href="styles.css">
    <title>Reporte de Precios de Facturas - Pedidos GA</title>
    <style>
        .reporte-container {
            max-width: 1400px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .reporte-header {
            background: #005aa3;
            color: white;
            padding: 20px;
            border-radius: 8px 8px 0 0;
            margin: -20px -20px 20px -20px;
        }

        .filtros-container {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #ddd;
        }

        .tab {
            padding: 10px 20px;
            background: #f0f0f0;
            border: none;
            cursor: pointer;
            border-radius: 5px 5px 0 0;
            transition: all 0.3s;
        }

        .tab.active {
            background: #667eea;
            color: white;
        }

        .tab:hover {
            background: #764ba2;
            color: white;
        }

        .tabla-reporte {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .tabla-reporte th {
            background: #667eea;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: bold;
        }

        .tabla-reporte td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }

        .tabla-reporte tr:hover {
            background: #f5f5f5;
        }

        .card-stat {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
            margin-bottom: 20px;
        }

        .card-stat .numero {
            font-size: 2.5em;
            font-weight: bold;
            color: #667eea;
        }

        .card-stat .etiqueta {
            color: #666;
            margin-top: 10px;
        }

        .grid-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .btn-export {
            background: #28a745;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
        }

        .btn-export:hover {
            background: #218838;
        }

        .precio-incorrecto {
            background-color: #f8d7da;
            color: #721c24;
            font-weight: bold;
        }

        .precio-correcto {
            background-color: #d4edda;
            color: #155724;
        }

        .badge-stat {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 0.9em;
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="reporte-container">
    <div class="reporte-header">
        <h1>📊 Reporte de Precios de Facturas</h1>
        <p>Análisis y seguimiento de precios capturados por vendedores</p>
    </div>

    <!-- Filtros -->
    <div class="filtros-container">
        <form method="GET" action="reporte_precios_facturas.php">
            <input type="hidden" name="seccion" value="<?php echo $seccion; ?>">

            <label for="fecha_inicio">Fecha Inicio:</label>
            <input type="date" id="fecha_inicio" name="fecha_inicio" value="<?php echo $fecha_inicio; ?>">

            <label for="fecha_fin">Fecha Fin:</label>
            <input type="date" id="fecha_fin" name="fecha_fin" value="<?php echo $fecha_fin; ?>">

            <label for="sucursal">Sucursal:</label>
            <select id="sucursal" name="sucursal">
                <option value="TODAS" <?php echo ($sucursal_filtro == 'TODAS') ? 'selected' : ''; ?>>TODAS</option>
                <?php
                $sucursales = ['DIMEGSA', 'DEASA', 'AIESA', 'SEGSA', 'FESA', 'TAPATIA', 'GABSA', 'ILUMINACION', 'VALLARTA', 'CODI', 'QUERETARO'];
                foreach ($sucursales as $suc) {
                    $selected = ($sucursal_filtro == $suc) ? 'selected' : '';
                    echo "<option value='$suc' $selected>$suc</option>";
                }
                ?>
            </select>

            <label for="vendedor">Vendedor:</label>
            <select id="vendedor" name="vendedor">
                <option value="TODOS" <?php echo ($vendedor_filtro == 'TODOS') ? 'selected' : ''; ?>>TODOS</option>
                <?php
                $sql_vendedores = "SELECT DISTINCT VENDEDOR FROM pedidos WHERE VENDEDOR IS NOT NULL AND VENDEDOR != '' ORDER BY VENDEDOR";
                $result_vendedores = $conn->query($sql_vendedores);
                while ($row_vend = $result_vendedores->fetch_assoc()) {
                    $selected = ($vendedor_filtro == $row_vend['VENDEDOR']) ? 'selected' : '';
                    echo "<option value='{$row_vend['VENDEDOR']}' $selected>{$row_vend['VENDEDOR']}</option>";
                }
                ?>
            </select>

            <label for="estado">Estado:</label>
            <select id="estado" name="estado">
                <option value="TODOS" <?php echo ($estado_filtro == 'TODOS') ? 'selected' : ''; ?>>TODOS</option>
                <option value="ACTIVOS" <?php echo ($estado_filtro == 'ACTIVOS') ? 'selected' : ''; ?>>ACTIVOS (En Ruta, Tienda, etc)</option>
                <option value="ENTREGADOS" <?php echo ($estado_filtro == 'ENTREGADOS') ? 'selected' : ''; ?>>ENTREGADOS</option>
                <option value="CANCELADOS" <?php echo ($estado_filtro == 'CANCELADOS') ? 'selected' : ''; ?>>CANCELADOS</option>
            </select>

            <input type="submit" value="Filtrar" style="margin-left: 10px;">
            <a href="export_reporte_precios.php?fecha_inicio=<?php echo $fecha_inicio; ?>&fecha_fin=<?php echo $fecha_fin; ?>&sucursal=<?php echo $sucursal_filtro; ?>&vendedor=<?php echo $vendedor_filtro; ?>&estado=<?php echo $estado_filtro; ?>" class="btn-export">📥 Exportar a Excel</a>
        </form>
    </div>

    <!-- Tabs de navegación -->
    <div class="tabs">
        <button class="tab <?php echo ($seccion == 'resumen') ? 'active' : ''; ?>" onclick="window.location.href='?seccion=resumen&fecha_inicio=<?php echo $fecha_inicio; ?>&fecha_fin=<?php echo $fecha_fin; ?>&sucursal=<?php echo $sucursal_filtro; ?>&vendedor=<?php echo $vendedor_filtro; ?>'">
            Resumen General
        </button>
        <button class="tab <?php echo ($seccion == 'menores_1000') ? 'active' : ''; ?>" onclick="window.location.href='?seccion=menores_1000&fecha_inicio=<?php echo $fecha_inicio; ?>&fecha_fin=<?php echo $fecha_fin; ?>&sucursal=<?php echo $sucursal_filtro; ?>&vendedor=<?php echo $vendedor_filtro; ?>'">
            Pedidos < $1000
        </button>
        <button class="tab <?php echo ($seccion == 'correcciones') ? 'active' : ''; ?>" onclick="window.location.href='?seccion=correcciones&fecha_inicio=<?php echo $fecha_inicio; ?>&fecha_fin=<?php echo $fecha_fin; ?>&sucursal=<?php echo $sucursal_filtro; ?>&vendedor=<?php echo $vendedor_filtro; ?>'">
            Correcciones JC
        </button>
        <button class="tab <?php echo ($seccion == 'vendedores') ? 'active' : ''; ?>" onclick="window.location.href='?seccion=vendedores&fecha_inicio=<?php echo $fecha_inicio; ?>&fecha_fin=<?php echo $fecha_fin; ?>&sucursal=<?php echo $sucursal_filtro; ?>&vendedor=<?php echo $vendedor_filtro; ?>'">
            Por Vendedor
        </button>
        <button class="tab <?php echo ($seccion == 'pendientes') ? 'active' : ''; ?>" onclick="window.location.href='?seccion=pendientes&fecha_inicio=<?php echo $fecha_inicio; ?>&fecha_fin=<?php echo $fecha_fin; ?>&sucursal=<?php echo $sucursal_filtro; ?>&vendedor=<?php echo $vendedor_filtro; ?>&estado=<?php echo $estado_filtro; ?>'">
            Pendientes Validar
        </button>
        <button class="tab <?php echo ($seccion == 'grupos') ? 'active' : ''; ?>" onclick="window.location.href='?seccion=grupos&fecha_inicio=<?php echo $fecha_inicio; ?>&fecha_fin=<?php echo $fecha_fin; ?>&sucursal=<?php echo $sucursal_filtro; ?>&vendedor=<?php echo $vendedor_filtro; ?>&estado=<?php echo $estado_filtro; ?>'">
            📊 Análisis de Grupos
        </button>
    </div>

    <!-- Contenido según sección seleccionada -->
    <?php
    // Construir condiciones de filtro
    $where_conditions = ["FECHA_RECEPCION_FACTURA BETWEEN '$fecha_inicio' AND '$fecha_fin'"];

    if ($sucursal_filtro != 'TODAS') {
        $where_conditions[] = "SUCURSAL = '$sucursal_filtro'";
    }

    if ($vendedor_filtro != 'TODOS') {
        $where_conditions[] = "pedidos.VENDEDOR = '$vendedor_filtro'"; // Usar alias 'pedidos' por precaución
    }

    if ($estado_filtro == 'ACTIVOS') {
        $where_conditions[] = "pedidos.ESTADO IN ('EN TIENDA', 'REPROGRAMADO', 'ACTIVO', 'EN RUTA')";
    } elseif ($estado_filtro == 'ENTREGADOS') {
        $where_conditions[] = "pedidos.ESTADO = 'ENTREGADO'";
    } elseif ($estado_filtro == 'CANCELADOS') {
        $where_conditions[] = "pedidos.ESTADO = 'CANCELADO'";
    }

    // Asegurar que las fechas también usen el alias 'pedidos' si es necesario más adelante, 
    // pero por ahora el reemplazo en secciones específicas lo maneja.
    // MODIFICACION: Actualizar la condición de fecha para usar alias si existe join
    $where_conditions[0] = "pedidos.FECHA_RECEPCION_FACTURA BETWEEN '$fecha_inicio' AND '$fecha_fin'";
    
    // Y si filtro de sucursal
    if ($sucursal_filtro != 'TODAS') {
         // Reemplazar la condición anterior q no tenía alias
         $where_conditions[1] = "pedidos.SUCURSAL = '$sucursal_filtro'";
    } else {
        // Ajustar índice si no había sucursal
    }

    // RE-GENERAR where_clause GENÉRICO SIN ALIAS (para secciones que no hacen JOIN)
    // Para no romper lo existente, haremos un hack:
    // Las secciones existentes usan $where_clause simple.
    // Vamos a reconstruir $where_clause simple y $where_clause_joined.

    $where_simple_arr = ["FECHA_RECEPCION_FACTURA BETWEEN '$fecha_inicio' AND '$fecha_fin'"];
    if ($sucursal_filtro != 'TODAS') $where_simple_arr[] = "SUCURSAL = '$sucursal_filtro'";
    if ($vendedor_filtro != 'TODOS') $where_simple_arr[] = "VENDEDOR = '$vendedor_filtro'";
    
    if ($estado_filtro == 'ACTIVOS') {
        $where_simple_arr[] = "ESTADO IN ('EN TIENDA', 'REPROGRAMADO', 'ACTIVO', 'EN RUTA')";
    } elseif ($estado_filtro == 'ENTREGADOS') {
        $where_simple_arr[] = "ESTADO = 'ENTREGADO'";
    } elseif ($estado_filtro == 'CANCELADOS') {
        $where_simple_arr[] = "ESTADO = 'CANCELADO'";
    }
    
    $where_clause = implode(" AND ", $where_simple_arr);

    // SECCIÓN: RESUMEN GENERAL
    if ($seccion == 'resumen') {
        ?>
        <h2>Resumen General</h2>

        <?php
        // Estadísticas generales
        $sql_stats = "SELECT
            COUNT(*) as total_pedidos,
            SUM(CASE WHEN precio_factura_real > 0 THEN 1 ELSE 0 END) as pedidos_con_precio,
            SUM(CASE WHEN precio_factura_real < 1000 AND precio_factura_real > 0 THEN 1 ELSE 0 END) as pedidos_bajo_1000,
            SUM(CASE WHEN precio_validado_jc = 1 THEN 1 ELSE 0 END) as pedidos_validados,
            SUM(CASE WHEN precio_factura_vendedor != precio_factura_real AND precio_factura_real > 0 THEN 1 ELSE 0 END) as pedidos_corregidos,
            SUM(precio_factura_real) as total_facturado,
            AVG(precio_factura_real) as promedio_factura
        FROM pedidos
        WHERE $where_clause";

        $result_stats = $conn->query($sql_stats);
        $stats = $result_stats->fetch_assoc();

        $porcentaje_bajo_1000 = ($stats['pedidos_con_precio'] > 0) ?
            round(($stats['pedidos_bajo_1000'] / $stats['pedidos_con_precio']) * 100, 2) : 0;

        $porcentaje_validados = ($stats['pedidos_con_precio'] > 0) ?
            round(($stats['pedidos_validados'] / $stats['pedidos_con_precio']) * 100, 2) : 0;

        $porcentaje_corregidos = ($stats['pedidos_con_precio'] > 0) ?
            round(($stats['pedidos_corregidos'] / $stats['pedidos_con_precio']) * 100, 2) : 0;
        ?>

        <div class="grid-stats">
            <div class="card-stat">
                <div class="numero"><?php echo number_format($stats['total_pedidos']); ?></div>
                <div class="etiqueta">Total Pedidos</div>
            </div>

            <div class="card-stat">
                <div class="numero" style="color: #28a745;">$<?php echo number_format($stats['total_facturado'], 2); ?></div>
                <div class="etiqueta">Total Facturado</div>
            </div>

            <div class="card-stat">
                <div class="numero" style="color: #17a2b8;">$<?php echo number_format($stats['promedio_factura'], 2); ?></div>
                <div class="etiqueta">Promedio por Factura</div>
            </div>

            <div class="card-stat">
                <div class="numero" style="color: #ffc107;"><?php echo $stats['pedidos_bajo_1000']; ?></div>
                <div class="etiqueta">Pedidos < $1000 (<?php echo $porcentaje_bajo_1000; ?>%)</div>
            </div>

            <div class="card-stat">
                <div class="numero" style="color: #28a745;"><?php echo $stats['pedidos_validados']; ?></div>
                <div class="etiqueta">Validados por JC (<?php echo $porcentaje_validados; ?>%)</div>
            </div>

            <div class="card-stat">
                <div class="numero" style="color: #dc3545;"><?php echo $stats['pedidos_corregidos']; ?></div>
                <div class="etiqueta">Corregidos por JC (<?php echo $porcentaje_corregidos; ?>%)</div>
            </div>
        </div>

        <?php
    }

    // SECCIÓN: PEDIDOS MENORES A $1000
    elseif ($seccion == 'menores_1000') {
        $filtro_grupo = isset($_GET['filtro_grupo']) ? $_GET['filtro_grupo'] : 'todos';
        ?>
        <h2>⚠️ Pedidos con Precio Menor a $1000 (No Convenientes)</h2>

        <!-- Filtros de Grupo -->
        <div style="margin-bottom: 20px;">
            <a href="?seccion=menores_1000&fecha_inicio=<?php echo $fecha_inicio; ?>&fecha_fin=<?php echo $fecha_fin; ?>&sucursal=<?php echo $sucursal_filtro; ?>&vendedor=<?php echo $vendedor_filtro; ?>&filtro_grupo=todos" 
               class="btn-export" style="background: <?php echo ($filtro_grupo == 'todos') ? '#005aa3' : '#6c757d'; ?>;">Todos</a>
            
            <a href="?seccion=menores_1000&fecha_inicio=<?php echo $fecha_inicio; ?>&fecha_fin=<?php echo $fecha_fin; ?>&sucursal=<?php echo $sucursal_filtro; ?>&vendedor=<?php echo $vendedor_filtro; ?>&filtro_grupo=con_grupo" 
               class="btn-export" style="background: <?php echo ($filtro_grupo == 'con_grupo') ? '#005aa3' : '#6c757d'; ?>;">Con Grupo</a>
            
            <a href="?seccion=menores_1000&fecha_inicio=<?php echo $fecha_inicio; ?>&fecha_fin=<?php echo $fecha_fin; ?>&sucursal=<?php echo $sucursal_filtro; ?>&vendedor=<?php echo $vendedor_filtro; ?>&filtro_grupo=sin_grupo" 
               class="btn-export" style="background: <?php echo ($filtro_grupo == 'sin_grupo') ? '#005aa3' : '#6c757d'; ?>;">Sin Grupo</a>
        </div>

        <?php
        // Preparar condiciones con alias para evitar ambigüedad en el JOIN
        // Preparar condiciones con alias para evitar ambigüedad en el JOIN
        $where_clause_joined = str_replace(
            ['SUCURSAL', 'VENDEDOR', 'FECHA_RECEPCION_FACTURA', 'precio_factura_real', 'ESTADO'], 
            ['pedidos.SUCURSAL', 'pedidos.VENDEDOR', 'pedidos.FECHA_RECEPCION_FACTURA', 'pedidos.precio_factura_real', 'pedidos.ESTADO'], 
            $where_clause
        );

        // Subquery para contar miembros del grupo
        $sql_menores = "SELECT pedidos.ID, pedidos.SUCURSAL, pedidos.VENDEDOR, pedidos.FACTURA, pedidos.NOMBRE_CLIENTE, 
                               pedidos.precio_factura_real, pedidos.precio_validado_jc, pedidos.FECHA_RECEPCION_FACTURA,
                               grupos_rutas.nombre_grupo, grupos_rutas.id as grupo_id,
                               stats_grupo.total_grupo
        FROM pedidos
        LEFT JOIN pedidos_grupos ON pedidos.ID = pedidos_grupos.pedido_id
        LEFT JOIN grupos_rutas ON pedidos_grupos.grupo_id = grupos_rutas.id AND grupos_rutas.estado = 'ACTIVO'
        LEFT JOIN (
            SELECT grupo_id, COUNT(*) as total_grupo 
            FROM pedidos_grupos 
            GROUP BY grupo_id
        ) as stats_grupo ON grupos_rutas.id = stats_grupo.grupo_id
        WHERE $where_clause_joined AND pedidos.precio_factura_real < 1000 AND pedidos.precio_factura_real > 0";

        // Aplicar filtro de grupo
        if ($filtro_grupo == 'con_grupo') {
            $sql_menores .= " AND grupos_rutas.id IS NOT NULL";
        } elseif ($filtro_grupo == 'sin_grupo') {
            $sql_menores .= " AND grupos_rutas.id IS NULL";
        }

        $sql_menores .= " ORDER BY pedidos.precio_factura_real ASC";

        $result_menores = $conn->query($sql_menores);

        if ($result_menores && $result_menores->num_rows > 0) {
            echo "<table class='tabla-reporte'>";
            echo "<tr>
                    <th>ID</th>
                    <th>Fecha</th>
                    <th>Sucursal</th>
                    <th>Vendedor</th>
                    <th>Factura</th>
                    <th>Cliente</th>
                    <th>Precio</th>
                    <th>Grupo</th>
                    <th>Validado</th>
                  </tr>";

            while ($row = $result_menores->fetch_assoc()) {
                $validado_icon = ($row['precio_validado_jc'] == 1) ?
                    "<span style='color: #28a745;'>✓ Sí</span>" :
                    "<span style='color: #ffc107;'>⏳ No</span>";

                // Badge de Grupo con Conteo
                $badge_grupo = "-";
                if (!empty($row['nombre_grupo'])) {
                     $count = $row['total_grupo'];
                     $badge_grupo = "<span style='background: #17a2b8; color: white; padding: 3px 8px; border-radius: 10px; font-size: 0.85em;' title='Grupo con $count pedidos'>🚚 " . htmlspecialchars($row['nombre_grupo']) . " ($count)</span>";
                }

                echo "<tr>";
                echo "<td><a href='Inicio.php?id={$row['ID']}'>{$row['ID']}</a></td>";
                echo "<td>{$row['FECHA_RECEPCION_FACTURA']}</td>";
                echo "<td>{$row['SUCURSAL']}</td>";
                echo "<td>{$row['VENDEDOR']}</td>";
                echo "<td>{$row['FACTURA']}</td>";
                echo "<td>{$row['NOMBRE_CLIENTE']}</td>";
                echo "<td style='background-color: #fff3cd; color: #856404; font-weight: bold;'>$" . number_format($row['precio_factura_real'], 2) . "</td>";
                echo "<td>{$badge_grupo}</td>";
                echo "<td>{$validado_icon}</td>";
                echo "</tr>";
            }

            echo "</table>";
        } else {
            if(!$result_menores) {
                echo "<p style='color:red;'>Error en consulta: " . $conn->error . "</p>";
            } else {
                echo "<p style='text-align: center; color: #28a745; padding: 20px;'>✓ No hay pedidos con precio menor a $1000 que coincidan con los filtros.</p>";
            }
        }
    }

    // SECCIÓN: CORRECCIONES POR JC
    elseif ($seccion == 'correcciones') {
        ?>
        <h2>✏️ Correcciones de Precio por Jefe de Choferes</h2>

        <?php
        $sql_correcciones = "SELECT ID, SUCURSAL, VENDEDOR, FACTURA, NOMBRE_CLIENTE,
                                    precio_factura_vendedor, precio_factura_real,
                                    (precio_factura_vendedor - precio_factura_real) as diferencia,
                                    FECHA_RECEPCION_FACTURA, usuario_validacion_precio, fecha_validacion_precio
        FROM pedidos
        WHERE $where_clause
        AND precio_factura_vendedor != precio_factura_real
        AND precio_factura_real > 0
        ORDER BY ABS(precio_factura_vendedor - precio_factura_real) DESC";

        $result_correcciones = $conn->query($sql_correcciones);

        if ($result_correcciones->num_rows > 0) {
            echo "<table class='tabla-reporte'>";
            echo "<tr>
                    <th>ID</th>
                    <th>Fecha</th>
                    <th>Vendedor</th>
                    <th>Factura</th>
                    <th>Precio Vendedor</th>
                    <th>Precio Real (JC)</th>
                    <th>Diferencia</th>
                    <th>% Error</th>
                    <th>Validado Por</th>
                  </tr>";

            while ($row = $result_correcciones->fetch_assoc()) {
                $diferencia = $row['diferencia'];
                $porcentaje_error = ($row['precio_factura_vendedor'] > 0) ?
                    round(($diferencia / $row['precio_factura_vendedor']) * 100, 2) : 0;

                $color_diff = ($diferencia > 0) ? '#dc3545' : '#17a2b8';
                $texto_diff = ($diferencia > 0) ? 'Cobró de más' : 'Cobró de menos';

                echo "<tr>";
                echo "<td><a href='Inicio.php?id={$row['ID']}'>{$row['ID']}</a></td>";
                echo "<td>{$row['FECHA_RECEPCION_FACTURA']}</td>";
                echo "<td>{$row['VENDEDOR']}</td>";
                echo "<td>{$row['FACTURA']}</td>";
                echo "<td>$" . number_format($row['precio_factura_vendedor'], 2) . "</td>";
                echo "<td style='font-weight: bold;'>$" . number_format($row['precio_factura_real'], 2) . "</td>";
                echo "<td style='color: $color_diff; font-weight: bold;'>$" . number_format(abs($diferencia), 2) . " <br><small>($texto_diff)</small></td>";
                echo "<td style='color: $color_diff; font-weight: bold;'>" . abs($porcentaje_error) . "%</td>";
                echo "<td>{$row['usuario_validacion_precio']}<br><small>{$row['fecha_validacion_precio']}</small></td>";
                echo "</tr>";
            }

            echo "</table>";
        } else {
            echo "<p style='text-align: center; color: #28a745; padding: 20px;'>✓ No hay correcciones de precio en el período seleccionado</p>";
        }
    }

    // SECCIÓN: ESTADÍSTICAS POR VENDEDOR
    elseif ($seccion == 'vendedores') {
        ?>
        <h2>📈 Estadísticas por Vendedor</h2>

        <?php
        $sql_vendedores_stats = "SELECT
            VENDEDOR,
            COUNT(*) as total_pedidos,
            SUM(CASE WHEN precio_factura_vendedor = precio_factura_real THEN 1 ELSE 0 END) as precios_correctos,
            SUM(CASE WHEN precio_factura_vendedor != precio_factura_real AND precio_factura_real > 0 THEN 1 ELSE 0 END) as precios_incorrectos,
            SUM(ABS(precio_factura_vendedor - precio_factura_real)) as total_diferencias,
            AVG(precio_factura_real) as promedio_precio,
            SUM(CASE WHEN precio_factura_real < 1000 AND precio_factura_real > 0 THEN 1 ELSE 0 END) as pedidos_bajo_1000
        FROM pedidos
        WHERE $where_clause AND precio_factura_real > 0
        GROUP BY VENDEDOR
        ORDER BY total_pedidos DESC";

        $result_vend_stats = $conn->query($sql_vendedores_stats);

        if ($result_vend_stats->num_rows > 0) {
            echo "<table class='tabla-reporte'>";
            echo "<tr>
                    <th>Vendedor</th>
                    <th>Total Pedidos</th>
                    <th>Precios Correctos</th>
                    <th>Precios Incorrectos</th>
                    <th>% Precisión</th>
                    <th>Total Diferencias</th>
                    <th>Promedio Precio</th>
                    <th>Pedidos < $1000</th>
                  </tr>";

            while ($row = $result_vend_stats->fetch_assoc()) {
                $precision = ($row['total_pedidos'] > 0) ?
                    round(($row['precios_correctos'] / $row['total_pedidos']) * 100, 2) : 0;

                $color_precision = ($precision >= 90) ? '#d4edda' : (($precision >= 70) ? '#fff3cd' : '#f8d7da');

                echo "<tr>";
                echo "<td><strong>{$row['VENDEDOR']}</strong></td>";
                echo "<td>{$row['total_pedidos']}</td>";
                echo "<td class='precio-correcto'>{$row['precios_correctos']}</td>";
                echo "<td class='precio-incorrecto'>{$row['precios_incorrectos']}</td>";
                echo "<td style='background-color: $color_precision; font-weight: bold;'>{$precision}%</td>";
                echo "<td style='color: #dc3545;'>$" . number_format($row['total_diferencias'], 2) . "</td>";
                echo "<td>$" . number_format($row['promedio_precio'], 2) . "</td>";
                echo "<td>{$row['pedidos_bajo_1000']}</td>";
                echo "</tr>";
            }

            echo "</table>";
        } else {
            echo "<p style='text-align: center; padding: 20px;'>No hay datos disponibles</p>";
        }
    }

    // SECCIÓN: PENDIENTES DE VALIDAR
    elseif ($seccion == 'pendientes') {
        ?>
        <h2>⏳ Pedidos Pendientes de Validación por JC</h2>

        <?php
        $sql_pendientes = "SELECT ID, SUCURSAL, VENDEDOR, FACTURA, NOMBRE_CLIENTE,
                                  precio_factura_real, FECHA_RECEPCION_FACTURA,
                                  DATEDIFF(NOW(), FECHA_RECEPCION_FACTURA) as dias_antiguedad
        FROM pedidos
        WHERE $where_clause
        AND precio_validado_jc = 0
        AND precio_factura_real > 0
        ORDER BY dias_antiguedad DESC";

        $result_pendientes = $conn->query($sql_pendientes);

        if ($result_pendientes->num_rows > 0) {
            echo "<table class='tabla-reporte'>";
            echo "<tr>
                    <th>ID</th>
                    <th>Antigüedad</th>
                    <th>Fecha</th>
                    <th>Sucursal</th>
                    <th>Vendedor</th>
                    <th>Factura</th>
                    <th>Cliente</th>
                    <th>Precio</th>
                    <th>Acción</th>
                  </tr>";

            while ($row = $result_pendientes->fetch_assoc()) {
                $color_antiguedad = ($row['dias_antiguedad'] > 7) ? '#dc3545' :
                                   (($row['dias_antiguedad'] > 3) ? '#ffc107' : '#28a745');

                $color_precio = ($row['precio_factura_real'] < 1000) ? '#fff3cd' : '#ffffff';

                echo "<tr>";
                echo "<td><a href='Inicio.php?id={$row['ID']}'>{$row['ID']}</a></td>";
                echo "<td style='color: $color_antiguedad; font-weight: bold;'>{$row['dias_antiguedad']} días</td>";
                echo "<td>{$row['FECHA_RECEPCION_FACTURA']}</td>";
                echo "<td>{$row['SUCURSAL']}</td>";
                echo "<td>{$row['VENDEDOR']}</td>";
                echo "<td>{$row['FACTURA']}</td>";
                echo "<td>{$row['NOMBRE_CLIENTE']}</td>";
                echo "<td style='background-color: $color_precio;'>$" . number_format($row['precio_factura_real'], 2) . "</td>";
                echo "<td><a href='ActualizarPedido.php?id={$row['ID']}' style='background: #667eea; color: white; padding: 5px 10px; border-radius: 3px; text-decoration: none;'>Validar</a></td>";
                echo "</tr>";
            }

            echo "</table>";
        } else {
            echo "<p style='text-align: center; color: #28a745; padding: 20px;'>✓ No hay pedidos pendientes de validación</p>";
        }
    }

    // SECCIÓN: ANÁLISIS DE GRUPOS
    elseif ($seccion == 'grupos') {
        ?>
        <h2>📊 Análisis de Costos por Grupo de Ruta</h2>
        <p>Promedio de costo de factura por grupo logístico.</p>

        <?php
        // Preparar condiciones con alias 'pedidos'
        $where_joined_arr = ["pedidos.FECHA_RECEPCION_FACTURA BETWEEN '$fecha_inicio' AND '$fecha_fin'"];
        if ($sucursal_filtro != 'TODAS') $where_joined_arr[] = "pedidos.SUCURSAL = '$sucursal_filtro'";
        if ($vendedor_filtro != 'TODOS') $where_joined_arr[] = "pedidos.VENDEDOR = '$vendedor_filtro'";
        
        if ($estado_filtro == 'ACTIVOS') {
            $where_joined_arr[] = "pedidos.ESTADO IN ('EN TIENDA', 'REPROGRAMADO', 'ACTIVO', 'EN RUTA')";
        } elseif ($estado_filtro == 'ENTREGADOS') {
            $where_joined_arr[] = "pedidos.ESTADO = 'ENTREGADO'";
        } elseif ($estado_filtro == 'CANCELADOS') {
            $where_joined_arr[] = "pedidos.ESTADO = 'CANCELADO'";
        }
        
        $where_clause_grupos = implode(" AND ", $where_joined_arr);

        $sql_grupos = "SELECT 
                        gr.nombre_grupo,
                        COUNT(pedidos.ID) as cantidad_pedidos,
                        SUM(pedidos.precio_factura_real) as total_grupo,
                        AVG(pedidos.precio_factura_real) as promedio_grupo,
                        MIN(pedidos.precio_factura_real) as min_precio,
                        MAX(pedidos.precio_factura_real) as max_precio
                    FROM pedidos
                    JOIN pedidos_grupos pg ON pedidos.ID = pg.pedido_id
                    JOIN grupos_rutas gr ON pg.grupo_id = gr.id
                    WHERE $where_clause_grupos 
                    AND gr.estado = 'ACTIVO' 
                    AND pedidos.precio_factura_real > 0
                    GROUP BY gr.id, gr.nombre_grupo
                    ORDER BY promedio_grupo ASC";

        $result_grupos = $conn->query($sql_grupos);

        if ($result_grupos && $result_grupos->num_rows > 0) {
            echo "<table class='tabla-reporte'>";
            echo "<tr>
                    <th>Grupo / Ruta</th>
                    <th>Cantidad Pedidos</th>
                    <th>Total Facturado</th>
                    <th>Promedio x Pedido</th>
                    <th>Rango Precios</th>
                  </tr>";

            while ($row = $result_grupos->fetch_assoc()) {
                $promedio = $row['promedio_grupo'];
                
                // Colores para el promedio
                $bg_color = '';
                if ($promedio < 500) $bg_color = '#d4edda'; // Verde claro (muy bueno/barato)
                elseif ($promedio < 1500) $bg_color = '#ffffff'; // Normal
                else $bg_color = '#fff3cd'; // Amarillo (caro)
                if ($promedio > 5000) $bg_color = '#f8d7da'; // Rojo (muy caro)

                echo "<tr>";
                echo "<td><strong style='color: #005aa3;'>🚚 " . htmlspecialchars($row['nombre_grupo']) . "</strong></td>";
                echo "<td style='text-align: center; font-weight: bold;'>{$row['cantidad_pedidos']}</td>";
                echo "<td style='text-align: right;'>$" . number_format($row['total_grupo'], 2) . "</td>";
                echo "<td style='background-color: $bg_color; text-align: right; font-weight: bold;'>$" . number_format($promedio, 2) . "</td>";
                echo "<td style='text-align: center; font-size: 0.9em; color: #666;'>$" . number_format($row['min_precio'], 0) . " - $" . number_format($row['max_precio'], 0) . "</td>";
                echo "</tr>";
            }

            echo "</table>";
        } else {
            if(!$result_grupos) {
                echo "<p style='color:red;'>Error en consulta: " . $conn->error . "</p>";
            } else {
                echo "<p style='text-align: center; color: #6c757d; padding: 20px;'>ℹ️ No se encontraron pedidos agrupados con los filtros seleccionados.</p>";
            }
        }
    }
    ?>

    <div style="position: absolute; top: 20px; left: 20px; z-index: 999;">
        <a href="Pedidos_GA.php" style="background: #6c757d; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none;">← Volver al Inicio</a>
    </div>
</div>

</body>
</html>

<?php
$conn->close();
?>
