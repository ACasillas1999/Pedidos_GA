<?php
// Iniciar la sesión de forma segura
ini_set('session.cookie_httponly', true);
ini_set('session.cookie_secure', true);
session_name("GA");
session_start();

// Verificar si el usuario no está logeado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: /Pedidos_GA/Sesion/login.html");
    exit;
}

// Conexión a la base de datos
require_once __DIR__ . "/Conexiones/Conexion.php";

$sucursalSesion = strtoupper($_SESSION["Sucursal"] ?? "");
$rolSesion = $_SESSION["Rol"] ?? "";

// Determinar sucursales a consultar
$sucursalCondition = "";
if ($sucursalSesion === "TODAS") {
    $sucursalCondition = "";
} elseif (strtoupper($rolSesion) === 'JC' && $sucursalSesion === 'TAPATIA') {
    $sucursalCondition = "AND SUCURSAL IN ('TAPATIA','ILUMINACION')";
} else {
    if ($sucursalSesion != 'TODAS' && $sucursalSesion != '') {
        $sucursalCondition = "AND SUCURSAL='$sucursalSesion'";
    }
}

// Obtener filtros de fecha si existen
$fechaInicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : '';
$fechaFin    = isset($_GET['fecha_fin'])    ? $_GET['fecha_fin']    : '';

$fechaCondition = "";
if (!empty($fechaInicio) && !empty($fechaFin)) {
    $fechaCondition = " AND DATE(FECHA_RECEPCION_FACTURA) BETWEEN '$fechaInicio' AND '$fechaFin' ";
} elseif (!empty($fechaInicio)) {
    $fechaCondition = " AND DATE(FECHA_RECEPCION_FACTURA) >= '$fechaInicio' ";
} elseif (!empty($fechaFin)) {
    $fechaCondition = " AND DATE(FECHA_RECEPCION_FACTURA) <= '$fechaFin' ";
}

// ─── Estadísticas generales ───────────────────────────────────────────────
$sqlStats = "SELECT 
    COUNT(*) as total_pedidos,
    SUM(CASE WHEN tipo_zona = 'LOCAL'   THEN 1 ELSE 0 END) as total_local,
    SUM(CASE WHEN tipo_zona = 'FORANEO' THEN 1 ELSE 0 END) as total_foraneo,
    SUM(CASE WHEN tipo_zona IS NULL     THEN 1 ELSE 0 END) as sin_clasificar,
    SUM(CASE WHEN tipo_zona = 'LOCAL'   AND precio_factura_real > 0 THEN precio_factura_real ELSE 0 END) as monto_local,
    SUM(CASE WHEN tipo_zona = 'FORANEO' AND precio_factura_real > 0 THEN precio_factura_real ELSE 0 END) as monto_foraneo
FROM pedidos 
WHERE 1=1 $sucursalCondition $fechaCondition";

$resultStats = $conn->query($sqlStats);
$stats = $resultStats->fetch_assoc();

$porcentajeLocal   = $stats['total_pedidos'] > 0 ? ($stats['total_local']   / $stats['total_pedidos']) * 100 : 0;
$porcentajeForaneo = $stats['total_pedidos'] > 0 ? ($stats['total_foraneo'] / $stats['total_pedidos']) * 100 : 0;

// ─── Por sucursal ─────────────────────────────────────────────────────────
$sqlPorSucursal = "SELECT 
    SUCURSAL,
    COUNT(*) as total,
    SUM(CASE WHEN tipo_zona = 'LOCAL'   THEN 1 ELSE 0 END) as locales,
    SUM(CASE WHEN tipo_zona = 'FORANEO' THEN 1 ELSE 0 END) as foraneos
FROM pedidos 
WHERE tipo_zona IS NOT NULL $sucursalCondition $fechaCondition
GROUP BY SUCURSAL
ORDER BY total DESC";

$resultPorSucursal = $conn->query($sqlPorSucursal);

// ─── Por estado ───────────────────────────────────────────────────────────
$sqlPorEstado = "SELECT 
    ESTADO,
    COUNT(*) as total,
    SUM(CASE WHEN tipo_zona = 'LOCAL'   THEN 1 ELSE 0 END) as locales,
    SUM(CASE WHEN tipo_zona = 'FORANEO' THEN 1 ELSE 0 END) as foraneos
FROM pedidos 
WHERE tipo_zona IS NOT NULL $sucursalCondition $fechaCondition
GROUP BY ESTADO
ORDER BY total DESC";

$resultPorEstado = $conn->query($sqlPorEstado);

// ─── Cabeceras para descarga de Excel ─────────────────────────────────────
$periodoLabel = '';
if (!empty($fechaInicio) && !empty($fechaFin)) {
    $periodoLabel = "_" . $fechaInicio . "_al_" . $fechaFin;
} elseif (!empty($fechaInicio)) {
    $periodoLabel = "_desde_" . $fechaInicio;
} elseif (!empty($fechaFin)) {
    $periodoLabel = "_hasta_" . $fechaFin;
}

$filename = "Reporte_Zonas" . $periodoLabel . ".xls";

header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Cache-Control: max-age=0");

// BOM para UTF-8
echo "\xEF\xBB\xBF";

// ─── Inicio del HTML que Excel interpretará ───────────────────────────────
?>
<html xmlns:o="urn:schemas-microsoft-com:office:office"
      xmlns:x="urn:schemas-microsoft-com:office:excel"
      xmlns="http://www.w3.org/TR/REC-html40">
<head>
<meta charset="UTF-8">
<style>
  body { font-family: Arial, sans-serif; font-size: 11pt; }

  /* Encabezados de sección */
  .titulo-seccion {
    background-color: #005aa3;
    color: #FFFFFF;
    font-size: 14pt;
    font-weight: bold;
    text-align: center;
    padding: 6px;
  }
  .subtitulo-seccion {
    background-color: #0077cc;
    color: #FFFFFF;
    font-weight: bold;
    text-align: center;
    padding: 4px;
  }

  /* Cabeceras de tabla */
  .th-azul {
    background-color: #005aa3;
    color: #FFFFFF;
    font-weight: bold;
    border: 1px solid #003d73;
    padding: 5px;
    text-align: center;
  }
  .th-verde {
    background-color: #28a745;
    color: #FFFFFF;
    font-weight: bold;
    border: 1px solid #1d7a35;
    padding: 5px;
    text-align: center;
  }
  .th-naranja {
    background-color: #ff9800;
    color: #FFFFFF;
    font-weight: bold;
    border: 1px solid #c97a00;
    padding: 5px;
    text-align: center;
  }

  /* Celdas de datos */
  .td-normal {
    border: 1px solid #c0c0c0;
    padding: 4px 8px;
    text-align: center;
  }
  .td-bold {
    border: 1px solid #c0c0c0;
    padding: 4px 8px;
    font-weight: bold;
  }
  .td-numero {
    border: 1px solid #c0c0c0;
    padding: 4px 8px;
    text-align: right;
    mso-number-format:"\#\,\#\#0";
  }
  .td-moneda {
    border: 1px solid #c0c0c0;
    padding: 4px 8px;
    text-align: right;
    mso-number-format:"\$\#\,\#\#0\.00";
  }
  .td-pct {
    border: 1px solid #c0c0c0;
    padding: 4px 8px;
    text-align: center;
  }

  /* Totales */
  .tr-total td {
    background-color: #f0f4fa;
    font-weight: bold;
    border: 1px solid #c0c0c0;
    padding: 4px 8px;
  }

  /* Filas alternas */
  .tr-par  td { background-color: #FFFFFF; }
  .tr-impar td { background-color: #f5f9ff; }

  /* Separadores */
  .fila-vacia td { border: none; }
</style>
</head>
<body>

<table width="100%" cellspacing="0" cellpadding="0">

  <!-- ══════════════ ENCABEZADO PRINCIPAL ══════════════ -->
  <tr>
    <td colspan="5" class="titulo-seccion">
      📊 Reporte de Zonas Geográficas — Pedidos GA
    </td>
  </tr>
  <tr>
    <td colspan="5" class="subtitulo-seccion">
      Análisis de pedidos Locales vs Foráneos
      <?php if (!empty($fechaInicio) || !empty($fechaFin)): ?>
        — Periodo: 
        <?php if (!empty($fechaInicio)) echo "Desde " . htmlspecialchars($fechaInicio); ?>
        <?php if (!empty($fechaInicio) && !empty($fechaFin)) echo " "; ?>
        <?php if (!empty($fechaFin)) echo "Hasta " . htmlspecialchars($fechaFin); ?>
      <?php else: ?>
        — Todos los registros
      <?php endif; ?>
    </td>
  </tr>
  <tr>
    <td colspan="5" class="subtitulo-seccion">
      Generado el: <?php echo date('d/m/Y H:i:s'); ?>
      &nbsp;|&nbsp; Usuario: <?php echo htmlspecialchars($_SESSION['Usuario'] ?? $_SESSION['user'] ?? ''); ?>
      <?php if ($sucursalSesion !== 'TODAS'): ?>
        &nbsp;|&nbsp; Sucursal: <?php echo htmlspecialchars($sucursalSesion); ?>
      <?php endif; ?>
    </td>
  </tr>

  <!-- Fila vacía -->
  <tr class="fila-vacia"><td colspan="5">&nbsp;</td></tr>

  <!-- ══════════════ RESUMEN ESTADÍSTICO ══════════════ -->
  <tr>
    <td colspan="5" style="background-color:#e8f0fe; font-weight:bold; font-size:12pt; padding:5px; border-left:4px solid #005aa3;">
      📦 Resumen General
    </td>
  </tr>

  <tr>
    <td class="th-azul">Indicador</td>
    <td class="th-azul">Cantidad</td>
    <td class="th-azul">Porcentaje</td>
    <td class="th-azul">Monto ($)</td>
    <td class="th-azul">% del Monto Total</td>
  </tr>

  <?php
  $montoTotal = $stats['monto_local'] + $stats['monto_foraneo'];
  $pctMontoLocal   = $montoTotal > 0 ? ($stats['monto_local']   / $montoTotal) * 100 : 0;
  $pctMontoForaneo = $montoTotal > 0 ? ($stats['monto_foraneo'] / $montoTotal) * 100 : 0;
  ?>

  <tr class="tr-impar">
    <td class="td-bold">📦 Total de Pedidos</td>
    <td class="td-numero"><?php echo $stats['total_pedidos']; ?></td>
    <td class="td-pct">100.0%</td>
    <td class="td-moneda"><?php echo number_format($montoTotal, 2, '.', ''); ?></td>
    <td class="td-pct">100.0%</td>
  </tr>
  <tr class="tr-par">
    <td class="td-bold" style="color:#28a745;">🏠 Pedidos Locales</td>
    <td class="td-numero"><?php echo $stats['total_local']; ?></td>
    <td class="td-pct"><?php echo number_format($porcentajeLocal, 1); ?>%</td>
    <td class="td-moneda"><?php echo number_format($stats['monto_local'], 2, '.', ''); ?></td>
    <td class="td-pct"><?php echo number_format($pctMontoLocal, 1); ?>%</td>
  </tr>
  <tr class="tr-impar">
    <td class="td-bold" style="color:#ff9800;">🌍 Pedidos Foráneos</td>
    <td class="td-numero"><?php echo $stats['total_foraneo']; ?></td>
    <td class="td-pct"><?php echo number_format($porcentajeForaneo, 1); ?>%</td>
    <td class="td-moneda"><?php echo number_format($stats['monto_foraneo'], 2, '.', ''); ?></td>
    <td class="td-pct"><?php echo number_format($pctMontoForaneo, 1); ?>%</td>
  </tr>
  <?php if ($stats['sin_clasificar'] > 0): ?>
  <tr class="tr-par">
    <td class="td-bold" style="color:#e65100;">⚠️ Sin Clasificar</td>
    <td class="td-numero"><?php echo $stats['sin_clasificar']; ?></td>
    <td class="td-pct">—</td>
    <td class="td-pct">—</td>
    <td class="td-pct">—</td>
  </tr>
  <?php endif; ?>

  <!-- Fila vacía -->
  <tr class="fila-vacia"><td colspan="5">&nbsp;</td></tr>

  <!-- ══════════════ DESGLOSE POR SUCURSAL ══════════════ -->
  <tr>
    <td colspan="5" style="background-color:#e8f0fe; font-weight:bold; font-size:12pt; padding:5px; border-left:4px solid #28a745;">
      🏢 Desglose por Sucursal
    </td>
  </tr>

  <tr>
    <td class="th-azul">Sucursal</td>
    <td class="th-azul">Total</td>
    <td class="th-verde">Locales</td>
    <td class="th-naranja">Foráneos</td>
    <td class="th-azul">% Local</td>
  </tr>

  <?php
  $filaIdx = 0;
  $totalSucursal = ['total' => 0, 'locales' => 0, 'foraneos' => 0];
  while ($row = $resultPorSucursal->fetch_assoc()):
      $pctLocal = $row['total'] > 0 ? ($row['locales'] / $row['total']) * 100 : 0;
      $trClass  = ($filaIdx % 2 === 0) ? 'tr-par' : 'tr-impar';
      $filaIdx++;
      $totalSucursal['total']    += $row['total'];
      $totalSucursal['locales']  += $row['locales'];
      $totalSucursal['foraneos'] += $row['foraneos'];
  ?>
  <tr class="<?php echo $trClass; ?>">
    <td class="td-bold"><?php echo htmlspecialchars($row['SUCURSAL']); ?></td>
    <td class="td-numero"><?php echo $row['total']; ?></td>
    <td class="td-numero"><?php echo $row['locales']; ?></td>
    <td class="td-numero"><?php echo $row['foraneos']; ?></td>
    <td class="td-pct"><?php echo number_format($pctLocal, 1); ?>%</td>
  </tr>
  <?php endwhile; ?>

  <!-- Fila total sucursal -->
  <?php
  $pctTotalLocal = $totalSucursal['total'] > 0 ? ($totalSucursal['locales'] / $totalSucursal['total']) * 100 : 0;
  ?>
  <tr class="tr-total">
    <td>TOTAL</td>
    <td class="td-numero"><?php echo $totalSucursal['total']; ?></td>
    <td class="td-numero"><?php echo $totalSucursal['locales']; ?></td>
    <td class="td-numero"><?php echo $totalSucursal['foraneos']; ?></td>
    <td class="td-pct"><?php echo number_format($pctTotalLocal, 1); ?>%</td>
  </tr>

  <!-- Fila vacía -->
  <tr class="fila-vacia"><td colspan="5">&nbsp;</td></tr>

  <!-- ══════════════ DESGLOSE POR ESTADO ══════════════ -->
  <tr>
    <td colspan="5" style="background-color:#e8f0fe; font-weight:bold; font-size:12pt; padding:5px; border-left:4px solid #ff9800;">
      📋 Desglose por Estado del Pedido
    </td>
  </tr>

  <tr>
    <td class="th-azul">Estado</td>
    <td class="th-azul">Total</td>
    <td class="th-verde">Locales</td>
    <td class="th-naranja">Foráneos</td>
    <td class="th-azul">% Local</td>
  </tr>

  <?php
  $filaIdx = 0;
  $totalEstado = ['total' => 0, 'locales' => 0, 'foraneos' => 0];
  while ($row = $resultPorEstado->fetch_assoc()):
      $pctLocal = $row['total'] > 0 ? ($row['locales'] / $row['total']) * 100 : 0;
      $trClass  = ($filaIdx % 2 === 0) ? 'tr-par' : 'tr-impar';
      $filaIdx++;
      $totalEstado['total']    += $row['total'];
      $totalEstado['locales']  += $row['locales'];
      $totalEstado['foraneos'] += $row['foraneos'];
  ?>
  <tr class="<?php echo $trClass; ?>">
    <td class="td-bold"><?php echo htmlspecialchars($row['ESTADO']); ?></td>
    <td class="td-numero"><?php echo $row['total']; ?></td>
    <td class="td-numero"><?php echo $row['locales']; ?></td>
    <td class="td-numero"><?php echo $row['foraneos']; ?></td>
    <td class="td-pct"><?php echo number_format($pctLocal, 1); ?>%</td>
  </tr>
  <?php endwhile; ?>

  <!-- Fila total estado -->
  <?php
  $pctTotalLocalEst = $totalEstado['total'] > 0 ? ($totalEstado['locales'] / $totalEstado['total']) * 100 : 0;
  ?>
  <tr class="tr-total">
    <td>TOTAL</td>
    <td class="td-numero"><?php echo $totalEstado['total']; ?></td>
    <td class="td-numero"><?php echo $totalEstado['locales']; ?></td>
    <td class="td-numero"><?php echo $totalEstado['foraneos']; ?></td>
    <td class="td-pct"><?php echo number_format($pctTotalLocalEst, 1); ?>%</td>
  </tr>

</table>

</body>
</html>
<?php $conn->close(); ?>
