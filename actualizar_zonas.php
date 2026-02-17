<?php
/**
 * Script de migración para clasificar pedidos existentes como LOCAL o FORÁNEO
 * 
 * Este script debe ejecutarse UNA SOLA VEZ después de implementar el sistema de zonas.
 * Clasificará todos los pedidos que tienen coordenadas de destino pero no tienen tipo_zona asignado.
 * 
 * INSTRUCCIONES:
 * 1. Asegúrate de haber ejecutado el script SQL: add_tipo_zona_column.sql
 * 2. Accede a este archivo desde el navegador: http://[tu-servidor]/Pedidos_GA/actualizar_zonas.php
 * 3. El script mostrará el progreso y resultados
 * 
 * SEGURIDAD:
 * - Solo usuarios Admin pueden ejecutar este script
 * - Después de ejecutarlo, considera eliminarlo o moverlo fuera del directorio web
 */

// Iniciar sesión
session_name("GA");
session_start();

// Verificar que el usuario esté logeado y sea Admin
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    die("❌ Acceso denegado. Debes iniciar sesión.");
}

if ($_SESSION["Rol"] !== "Admin") {
    die("❌ Acceso denegado. Solo administradores pueden ejecutar este script.");
}

// Incluir archivos necesarios
require_once __DIR__ . "/Conexiones/Conexion.php";
require_once __DIR__ . "/funciones_zona.php";

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actualizar Zonas de Pedidos</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 900px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #005aa3;
            border-bottom: 3px solid #005aa3;
            padding-bottom: 10px;
        }
        .progress {
            background: #e0e0e0;
            border-radius: 10px;
            height: 30px;
            margin: 20px 0;
            overflow: hidden;
        }
        .progress-bar {
            background: linear-gradient(90deg, #28a745, #20c997);
            height: 100%;
            width: 0%;
            transition: width 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin: 20px 0;
        }
        .stat-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            border-left: 4px solid #005aa3;
        }
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #005aa3;
        }
        .stat-label {
            font-size: 14px;
            color: #666;
            margin-top: 5px;
        }
        .log {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            max-height: 400px;
            overflow-y: auto;
            font-family: monospace;
            font-size: 12px;
            margin-top: 20px;
        }
        .log-item {
            padding: 5px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        .success { color: #28a745; }
        .warning { color: #ffc107; }
        .error { color: #dc3545; }
        .info { color: #17a2b8; }
        .badge-local {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
        }
        .badge-foraneo {
            background: linear-gradient(135deg, #ff9800, #f57c00);
            color: white;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🗺️ Actualización de Zonas Geográficas</h1>
        <p>Este script clasificará los pedidos existentes como <span class="badge-local">🏠 LOCAL</span> o <span class="badge-foraneo">🌍 FORÁNEO</span> basándose en sus coordenadas de destino.</p>
        
        <?php
        // Obtener pedidos que necesitan clasificación
        $sql = "SELECT ID, Coord_Destino FROM pedidos WHERE Coord_Destino IS NOT NULL AND Coord_Destino != '' AND tipo_zona IS NULL";
        $result = $conn->query($sql);
        
        if ($result === false) {
            echo "<div class='error'>❌ Error al consultar la base de datos: " . $conn->error . "</div>";
            exit;
        }
        
        $totalPedidos = $result->num_rows;
        
        if ($totalPedidos === 0) {
            echo "<div class='info'>✅ No hay pedidos pendientes de clasificación. Todos los pedidos con coordenadas ya están clasificados.</div>";
            $conn->close();
            exit;
        }
        
        echo "<p class='info'>📊 Se encontraron <strong>$totalPedidos</strong> pedidos pendientes de clasificación.</p>";
        
        // Contadores
        $procesados = 0;
        $locales = 0;
        $foraneos = 0;
        $errores = 0;
        $logs = [];
        
        // Procesar cada pedido
        while ($row = $result->fetch_assoc()) {
            $pedidoId = $row['ID'];
            $coordenadas = $row['Coord_Destino'];
            
            // Determinar tipo de zona
            $tipoZona = determinarTipoZona($coordenadas);
            
            if ($tipoZona !== null) {
                // Actualizar en la base de datos
                $updateSql = "UPDATE pedidos SET tipo_zona = '$tipoZona' WHERE ID = $pedidoId";
                
                if ($conn->query($updateSql) === TRUE) {
                    $procesados++;
                    if ($tipoZona === 'LOCAL') {
                        $locales++;
                        $logs[] = "<span class='success'>✓</span> Pedido #$pedidoId → <span class='badge-local'>LOCAL</span>";
                    } else {
                        $foraneos++;
                        $logs[] = "<span class='success'>✓</span> Pedido #$pedidoId → <span class='badge-foraneo'>FORÁNEO</span>";
                    }
                } else {
                    $errores++;
                    $logs[] = "<span class='error'>✗</span> Error al actualizar pedido #$pedidoId: " . $conn->error;
                }
            } else {
                $errores++;
                $logs[] = "<span class='warning'>⚠</span> Pedido #$pedidoId tiene coordenadas inválidas: $coordenadas";
            }
        }
        
        $conn->close();
        
        // Mostrar estadísticas
        $porcentaje = ($procesados / $totalPedidos) * 100;
        ?>
        
        <div class="progress">
            <div class="progress-bar" style="width: <?php echo $porcentaje; ?>%">
                <?php echo round($porcentaje, 1); ?>%
            </div>
        </div>
        
        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?php echo $procesados; ?></div>
                <div class="stat-label">Procesados</div>
            </div>
            <div class="stat-card" style="border-left-color: #28a745;">
                <div class="stat-number success"><?php echo $locales; ?></div>
                <div class="stat-label">🏠 Locales</div>
            </div>
            <div class="stat-card" style="border-left-color: #ff9800;">
                <div class="stat-number warning"><?php echo $foraneos; ?></div>
                <div class="stat-label">🌍 Foráneos</div>
            </div>
        </div>
        
        <?php if ($errores > 0): ?>
        <div class="stat-card" style="border-left-color: #dc3545; margin-bottom: 20px;">
            <div class="stat-number error"><?php echo $errores; ?></div>
            <div class="stat-label">⚠️ Errores</div>
        </div>
        <?php endif; ?>
        
        <h2>📋 Registro de Actividad</h2>
        <div class="log">
            <?php
            foreach ($logs as $log) {
                echo "<div class='log-item'>$log</div>";
            }
            ?>
        </div>
        
        <div style="margin-top: 30px; padding: 15px; background: #e7f3ff; border-radius: 8px; border-left: 4px solid #005aa3;">
            <strong>✅ Proceso completado</strong><br>
            Se clasificaron <?php echo $procesados; ?> de <?php echo $totalPedidos; ?> pedidos.
            <?php if ($errores > 0): ?>
                <br><span class="warning">⚠️ Revisa los errores en el registro de actividad.</span>
            <?php endif; ?>
        </div>
        
        <div style="margin-top: 20px; text-align: center;">
            <a href="Pedidos_GA.php" style="background: #005aa3; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block;">
                Ver Pedidos Clasificados
            </a>
        </div>
    </div>
</body>
</html>
