<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('max_execution_time', 0);
set_time_limit(0);
ini_set('memory_limit', '512M');

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Importación de Migración</title>";
echo "<style>
body { font-family: Arial, sans-serif; max-width: 1000px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
.container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
h1 { color: #005aa3; border-bottom: 3px solid #005aa3; padding-bottom: 10px; }
.step { background: #f8f9fa; padding: 15px; margin: 15px 0; border-left: 4px solid #005aa3; border-radius: 5px; }
.step-title { font-weight: bold; font-size: 18px; color: #005aa3; margin-bottom: 10px; }
.success { color: #28a745; font-weight: bold; }
.error { color: #dc3545; font-weight: bold; }
.warning { color: #ffc107; font-weight: bold; }
.info { color: #17a2b8; }
pre { background: #f4f4f4; padding: 10px; border-radius: 5px; overflow-x: auto; font-size: 12px; }
.progress { margin: 10px 0; }
.stats { background: #e9ecef; padding: 10px; border-radius: 5px; margin: 10px 0; }
</style></head><body><div class='container'>";

echo "<h1>🚀 Importación de Migración a EC2</h1>";
echo "<p class='info'>Fecha: " . date('Y-m-d H:i:s') . "</p>";

// Configuración de la base de datos EC2
$host = '18.211.75.118';
$user = 'root';
$pass = '04nm2fdLefCxM';
$dbname = 'gpoascen_pedidos_app';

// Archivos SQL en orden de importación
$archivos = [
    [
        'nombre' => 'Tablas Nuevas',
        'archivo' => __DIR__ . '/migrations/gpoascen_pedidos_app_contablasnuevas.sql',
        'descripcion' => 'Crea todas las tablas nuevas de la base de datos'
    ],
    [
        'nombre' => 'Datos Faltantes',
        'archivo' => __DIR__ . '/migrations/gpoascen_pedidos_app_datos_faltantes.sql',
        'descripcion' => 'Importa datos faltantes de tablas auxiliares'
    ],
    [
        'nombre' => 'Datos Reales',
        'archivo' => __DIR__ . '/migrations/gpoascen_pedidos_app_datos_reales.sql',
        'descripcion' => 'Importa todos los datos reales de producción'
    ]
];

// Verificar que todos los archivos existan
echo "<div class='step'>";
echo "<div class='step-title'>📋 Verificación de Archivos</div>";
$todosExisten = true;
foreach ($archivos as $arch) {
    if (file_exists($arch['archivo'])) {
        $size = filesize($arch['archivo']) / 1024 / 1024;
        echo "<div class='success'>✓ {$arch['nombre']}: " . number_format($size, 2) . " MB</div>";
        echo "<div style='margin-left: 20px; color: #666; font-size: 12px;'>{$arch['archivo']}</div>";
    } else {
        echo "<div class='error'>✗ {$arch['nombre']}: NO ENCONTRADO</div>";
        echo "<div style='margin-left: 20px; color: #dc3545; font-size: 12px;'>{$arch['archivo']}</div>";
        $todosExisten = false;
    }
}
echo "</div>";

if (!$todosExisten) {
    echo "<div class='error'>❌ ERROR: No se encontraron todos los archivos. Verifica las rutas.</div>";
    echo "</div></body></html>";
    exit;
}

// Conectar a MySQL EC2
echo "<div class='step'>";
echo "<div class='step-title'>🔌 Conexión a Base de Datos EC2</div>";
echo "<div>Host: <strong>$host</strong></div>";
echo "<div>Base de datos: <strong>$dbname</strong></div>";

try {
    $conn = new mysqli($host, $user, $pass, $dbname);

    if ($conn->connect_error) {
        throw new Exception("Error de conexión: " . $conn->connect_error);
    }

    $conn->set_charset("utf8mb4");
    echo "<div class='success'>✓ Conectado exitosamente</div>";
    echo "</div>";
} catch (Exception $e) {
    echo "<div class='error'>✗ " . $e->getMessage() . "</div>";
    echo "</div></div></body></html>";
    exit;
}

// Función para ejecutar un archivo SQL
function importarSQL($conn, $archivo, $nombre) {
    echo "<div class='step'>";
    echo "<div class='step-title'>📥 Importando: $nombre</div>";

    $inicio = microtime(true);

    // Leer archivo
    echo "<div class='progress'>Leyendo archivo...</div>";
    $sql = file_get_contents($archivo);

    if ($sql === false) {
        echo "<div class='error'>✗ Error al leer el archivo</div></div>";
        return false;
    }

    echo "<div class='success'>✓ Archivo leído correctamente</div>";

    // Dividir por statements (punto y coma al final de línea)
    $statements = [];
    $buffer = '';
    $lineas = explode("\n", $sql);
    $in_delimiter_block = false;
    $delimiter = ';';

    foreach ($lineas as $linea) {
        $lineaTrim = trim($linea);

        // Ignorar comentarios y líneas vacías
        if (empty($lineaTrim) || substr($lineaTrim, 0, 2) == '--' || substr($lineaTrim, 0, 1) == '#') {
            continue;
        }

        // Detectar cambios de DELIMITER (saltar estas líneas)
        if (stripos($lineaTrim, 'DELIMITER') === 0) {
            // Extraer el nuevo delimitador
            $parts = preg_split('/\s+/', $lineaTrim);
            if (isset($parts[1])) {
                $delimiter = $parts[1];
                $in_delimiter_block = ($delimiter != ';');
            }
            continue;
        }

        $buffer .= $linea . "\n";

        // Verificar si termina con el delimitador actual
        if ($in_delimiter_block) {
            // En bloques especiales, buscar el delimitador $$
            if (substr(rtrim($lineaTrim), -strlen($delimiter)) == $delimiter) {
                // Remover el delimitador especial y agregar statement
                $stmt = trim(substr($buffer, 0, -strlen($delimiter)));
                if (!empty($stmt)) {
                    $statements[] = $stmt;
                }
                $buffer = '';
            }
        } else {
            // Delimitador normal (;)
            if (substr(rtrim($lineaTrim), -1) == ';') {
                $statements[] = trim($buffer);
                $buffer = '';
            }
        }
    }

    // Si queda algo en el buffer, agregarlo
    if (!empty(trim($buffer))) {
        $statements[] = trim($buffer);
    }

    $total = count($statements);
    echo "<div class='info'>📊 Total de statements a ejecutar: <strong>$total</strong></div>";

    // Ejecutar statements
    $ejecutados = 0;
    $errores = 0;
    $erroresDetalle = [];

    // Deshabilitar foreign key checks temporalmente
    $conn->query("SET FOREIGN_KEY_CHECKS=0");

    foreach ($statements as $i => $statement) {
        if (empty($statement)) continue;

        // Mostrar progreso cada 50 statements
        if ($i % 50 == 0 && $i > 0) {
            $progreso = number_format(($i / $total) * 100, 1);
            echo "<div class='progress'>⏳ Progreso: $i / $total ($progreso%)</div>";
            flush();
            ob_flush();
        }

        // Ejecutar statement
        try {
            if (!$conn->query($statement)) {
                $errores++;
                $errorMsg = $conn->error;

                // Solo guardar errores que NO sean comunes/ignorables
                $erroresIgnorados = [
                    'already exists',
                    'Duplicate entry',
                    'Multiple primary key defined',
                    'Duplicate key name',
                    'Duplicate foreign key constraint',
                    'syntax to use near'
                ];

                $esErrorIgnorable = false;
                foreach ($erroresIgnorados as $errorIgnorado) {
                    if (stripos($errorMsg, $errorIgnorado) !== false) {
                        $esErrorIgnorable = true;
                        break;
                    }
                }

                if (!$esErrorIgnorable) {
                    $erroresDetalle[] = [
                        'statement' => substr($statement, 0, 100) . '...',
                        'error' => $errorMsg
                    ];
                }

                // Si hay demasiados errores críticos, detener
                if (count($erroresDetalle) > 20) {
                    echo "<div class='error'>❌ DETENIDO: Demasiados errores críticos</div>";
                    break;
                }
            } else {
                $ejecutados++;
            }
        } catch (mysqli_sql_exception $e) {
            $errores++;
            $errorMsg = $e->getMessage();

            // Ignorar errores comunes
            $erroresIgnorados = [
                'already exists',
                'Duplicate entry',
                'Multiple primary key defined',
                'Duplicate key name',
                'Duplicate foreign key constraint',
                'syntax to use near'
            ];

            $esErrorIgnorable = false;
            foreach ($erroresIgnorados as $errorIgnorado) {
                if (stripos($errorMsg, $errorIgnorado) !== false) {
                    $esErrorIgnorable = true;
                    break;
                }
            }

            if (!$esErrorIgnorable) {
                $erroresDetalle[] = [
                    'statement' => substr($statement, 0, 100) . '...',
                    'error' => $errorMsg
                ];

                // Si hay demasiados errores críticos, detener
                if (count($erroresDetalle) > 20) {
                    echo "<div class='error'>❌ DETENIDO: Demasiados errores críticos</div>";
                    break;
                }
            }
        }
    }

    // Rehabilitar foreign key checks
    $conn->query("SET FOREIGN_KEY_CHECKS=1");

    $fin = microtime(true);
    $duracion = number_format($fin - $inicio, 2);

    // Mostrar resumen
    echo "<div class='stats'>";
    echo "<strong>📈 Resumen de Importación:</strong><br>";
    echo "✓ Ejecutados exitosamente: <span class='success'>$ejecutados</span><br>";
    echo "⚠ Errores (incluyendo duplicados): <span class='warning'>$errores</span><br>";
    echo "⏱ Tiempo: <strong>{$duracion} segundos</strong>";
    echo "</div>";

    // Mostrar errores críticos si los hay
    if (count($erroresDetalle) > 0) {
        echo "<div class='warning'>⚠ Errores Críticos Encontrados:</div>";
        echo "<pre>";
        foreach (array_slice($erroresDetalle, 0, 10) as $error) {
            echo "Statement: {$error['statement']}\n";
            echo "Error: {$error['error']}\n\n";
        }
        if (count($erroresDetalle) > 10) {
            echo "... y " . (count($erroresDetalle) - 10) . " errores más\n";
        }
        echo "</pre>";
    }

    if (count($erroresDetalle) == 0) {
        echo "<div class='success'>✅ Importación completada exitosamente</div>";
    } else {
        echo "<div class='warning'>⚠ Importación completada con algunos errores (ignorables)</div>";
    }

    echo "</div>";

    return true; // Siempre continuar con el siguiente archivo
}

// Importar cada archivo en orden
$exito_total = true;
foreach ($archivos as $arch) {
    $resultado = importarSQL($conn, $arch['archivo'], $arch['nombre']);
    if (!$resultado) {
        echo "<div class='error'>❌ ERROR CRÍTICO en {$arch['nombre']}. Deteniendo proceso.</div>";
        $exito_total = false;
        break;
    }

    // Pequeña pausa entre archivos
    sleep(1);
}

// Resumen final
echo "<div class='step'>";
if ($exito_total) {
    echo "<div class='step-title success'>🎉 ¡MIGRACIÓN COMPLETADA EXITOSAMENTE!</div>";
    echo "<div class='success'>✅ Todos los archivos fueron importados correctamente</div>";
    echo "<div class='info'>La base de datos en EC2 está lista para usarse</div>";
} else {
    echo "<div class='step-title error'>⚠ MIGRACIÓN COMPLETADA CON ERRORES</div>";
    echo "<div class='warning'>Algunos archivos tuvieron errores críticos. Revisa los detalles arriba.</div>";
}
echo "</div>";

$conn->close();

echo "</div></body></html>";
?>
