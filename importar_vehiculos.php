<?php
session_name("GA");
session_start();

// Verificar permisos de administrador
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["Rol"] !== "Admin") {
    die("Acceso denegado. Solo administradores pueden ejecutar migraciones.");
}

// Configuración de la base de datos EC2
$host = '18.211.75.118';
$user = 'root';
$pass = '04nm2fdLefCxM';
$dbname = 'gpoascen_pedidos_app';

// Archivo SQL a importar
$sqlFile = __DIR__ . '/migrations/gpoascen_pedidos_appvehiculos.sql';

// Verificar que el archivo existe
if (!file_exists($sqlFile)) {
    die("ERROR: El archivo $sqlFile no existe.");
}

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Importar Vehículos</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #4CAF50; padding-bottom: 10px; }
        .info { background: #e3f2fd; padding: 15px; border-left: 4px solid #2196F3; margin: 15px 0; border-radius: 4px; }
        .success { background: #e8f5e9; padding: 10px; border-left: 4px solid #4CAF50; margin: 5px 0; font-size: 14px; }
        .error { background: #ffebee; padding: 10px; border-left: 4px solid #f44336; margin: 5px 0; font-size: 14px; }
        .warning { background: #fff3e0; padding: 10px; border-left: 4px solid #ff9800; margin: 5px 0; font-size: 14px; }
        .progress { background: #f0f0f0; border-radius: 4px; padding: 3px; margin: 10px 0; }
        .progress-bar { background: #4CAF50; height: 24px; border-radius: 3px; text-align: center; color: white; line-height: 24px; transition: width 0.3s; }
        .btn { display: inline-block; margin: 20px 10px 0 0; padding: 10px 20px; background: #4CAF50; color: white; text-decoration: none; border-radius: 4px; }
        .btn:hover { background: #45a049; }
    </style>
</head>
<body>
<div class='container'>
    <h1>🚗 Importación de Datos de Vehículos</h1>";

try {
    // Conectar a la base de datos
    echo "<div class='info'>📡 Conectando a la base de datos en EC2...</div>";
    $conn = new mysqli($host, $user, $pass, $dbname);

    if ($conn->connect_error) {
        throw new Exception("Error de conexión: " . $conn->connect_error);
    }

    $conn->set_charset("utf8mb4");
    echo "<div class='success'>✅ Conexión establecida exitosamente</div>";

    // Leer el archivo SQL
    echo "<div class='info'>📄 Leyendo archivo: " . basename($sqlFile) . " (" . number_format(filesize($sqlFile) / 1024, 2) . " KB)</div>";
    $sqlContent = file_get_contents($sqlFile);

    if ($sqlContent === false) {
        throw new Exception("No se pudo leer el archivo SQL");
    }

    // Configurar para manejar errores de duplicados
    $conn->query("SET FOREIGN_KEY_CHECKS=0");

    // Dividir el contenido en statements
    $delimiter = ';';
    $in_delimiter_block = false;
    $statements = [];
    $current = '';
    $lines = explode("\n", $sqlContent);

    foreach ($lines as $line) {
        $lineaTrim = trim($line);

        // Ignorar comentarios y líneas vacías
        if ($lineaTrim === '' ||
            strpos($lineaTrim, '--') === 0 ||
            strpos($lineaTrim, '#') === 0) {
            continue;
        }

        // Detectar cambios de DELIMITER
        if (stripos($lineaTrim, 'DELIMITER') === 0) {
            $parts = preg_split('/\s+/', $lineaTrim);
            if (isset($parts[1])) {
                $delimiter = $parts[1];
                $in_delimiter_block = ($delimiter != ';');
            }
            continue;
        }

        $current .= $line . "\n";

        // Detectar fin de statement
        if ($in_delimiter_block) {
            if (strpos($lineaTrim, $delimiter) !== false) {
                $current = str_replace($delimiter, '', $current);
                $statements[] = trim($current);
                $current = '';
                $in_delimiter_block = false;
                $delimiter = ';';
            }
        } else {
            if (substr($lineaTrim, -1) === $delimiter) {
                $statements[] = trim($current);
                $current = '';
            }
        }
    }

    if (trim($current) !== '') {
        $statements[] = trim($current);
    }

    echo "<div class='info'>📊 Total de statements a ejecutar: " . count($statements) . "</div>";

    // Ejecutar statements
    $ejecutados = 0;
    $errores = 0;
    $ignorados = 0;

    $erroresIgnorados = [
        'already exists',
        'Duplicate entry',
        'Multiple primary key defined',
        'Duplicate key name',
        'Duplicate foreign key constraint',
    ];

    echo "<div class='progress'><div class='progress-bar' id='progressBar'>0%</div></div>";
    echo "<div id='status'></div>";

    foreach ($statements as $index => $statement) {
        $statement = trim($statement);
        if (empty($statement)) continue;

        // Convertir INSERT a INSERT IGNORE para preservar datos existentes
        if (preg_match('/^INSERT\s+INTO/i', $statement)) {
            $statement = preg_replace('/^INSERT\s+INTO/i', 'INSERT IGNORE INTO', $statement);
        }

        try {
            $conn->query($statement);
            $ejecutados++;

            // Mostrar progreso cada 10 statements
            if ($ejecutados % 10 === 0 || $ejecutados === count($statements)) {
                $porcentaje = round(($ejecutados / count($statements)) * 100);
                echo "<script>
                    document.getElementById('progressBar').style.width = '{$porcentaje}%';
                    document.getElementById('progressBar').textContent = '{$porcentaje}%';
                </script>";
                flush();
                ob_flush();
            }

        } catch (Exception $e) {
            $errorMsg = $e->getMessage();
            $debeIgnorar = false;

            foreach ($erroresIgnorados as $textoIgnorar) {
                if (stripos($errorMsg, $textoIgnorar) !== false) {
                    $debeIgnorar = true;
                    break;
                }
            }

            if ($debeIgnorar) {
                $ignorados++;
            } else {
                $errores++;
                $preview = substr($statement, 0, 100);
                echo "<div class='error'>❌ Error en statement " . ($index + 1) . ": " . htmlspecialchars($errorMsg) . "<br><small>" . htmlspecialchars($preview) . "...</small></div>";
            }
        }
    }

    $conn->query("SET FOREIGN_KEY_CHECKS=1");

    // Resumen final
    echo "<div class='info' style='margin-top: 20px; font-size: 16px;'>";
    echo "<strong>📊 Resumen de la Importación:</strong><br>";
    echo "✅ Statements ejecutados exitosamente: <strong>$ejecutados</strong><br>";
    echo "⚠️  Errores ignorados (duplicados): <strong>$ignorados</strong><br>";
    echo "❌ Errores reales: <strong>$errores</strong><br>";
    echo "</div>";

    if ($errores === 0) {
        echo "<div class='success' style='font-size: 18px; padding: 20px;'>
            🎉 <strong>¡IMPORTACIÓN COMPLETADA EXITOSAMENTE!</strong><br>
            Los datos de vehículos han sido importados correctamente.
        </div>";
    } else {
        echo "<div class='warning' style='font-size: 16px; padding: 15px;'>
            ⚠️ La importación se completó con algunos errores. Revisa los mensajes anteriores.
        </div>";
    }

    $conn->close();

} catch (Exception $e) {
    echo "<div class='error' style='font-size: 16px; padding: 20px;'>
        ❌ <strong>ERROR CRÍTICO:</strong><br>" . htmlspecialchars($e->getMessage()) . "
    </div>";
}

echo "
    <div style='margin-top: 30px; padding-top: 20px; border-top: 2px solid #ddd;'>
        <a href='Pedidos_GA.php' class='btn'>← Volver al Inicio</a>
        <a href='vehiculos.php' class='btn' style='background: #2196F3;'>Ver Vehículos</a>
    </div>
</div>
</body>
</html>";
?>
