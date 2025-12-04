<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('max_execution_time', 0);
set_time_limit(0);
ini_set('memory_limit', '512M');

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Limpieza e Importación</title>";
echo "<style>
body { font-family: Arial, sans-serif; max-width: 1000px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
.container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
h1 { color: #dc3545; border-bottom: 3px solid #dc3545; padding-bottom: 10px; }
.step { background: #f8f9fa; padding: 15px; margin: 15px 0; border-left: 4px solid #dc3545; border-radius: 5px; }
.step-title { font-weight: bold; font-size: 18px; color: #dc3545; margin-bottom: 10px; }
.success { color: #28a745; font-weight: bold; }
.error { color: #dc3545; font-weight: bold; }
.warning { color: #ffc107; font-weight: bold; }
.info { color: #17a2b8; }
pre { background: #f4f4f4; padding: 10px; border-radius: 5px; overflow-x: auto; font-size: 12px; }
.progress { margin: 10px 0; }
.stats { background: #e9ecef; padding: 10px; border-radius: 5px; margin: 10px 0; }
</style></head><body><div class='container'>";

echo "<h1>⚠️ LIMPIEZA E IMPORTACIÓN COMPLETA</h1>";
echo "<p class='warning'>⚠️ ADVERTENCIA: Esto eliminará TODOS los datos existentes</p>";
echo "<p class='info'>Fecha: " . date('Y-m-d H:i:s') . "</p>";

// Configuración de la base de datos EC2
$host = '18.211.75.118';
$user = 'root';
$pass = '04nm2fdLefCxM';
$dbname = 'gpoascen_pedidos_app';

// Conectar a MySQL
echo "<div class='step'>";
echo "<div class='step-title'>🔌 Conexión a Base de Datos</div>";
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

// PASO 1: Obtener todas las tablas
echo "<div class='step'>";
echo "<div class='step-title'>📋 PASO 1: Obteniendo lista de tablas</div>";

$result = $conn->query("SHOW TABLES");
$tables = [];
while ($row = $result->fetch_array()) {
    $tables[] = $row[0];
}

echo "<div class='info'>Total de tablas encontradas: <strong>" . count($tables) . "</strong></div>";
echo "<div style='max-height: 200px; overflow-y: auto; background: #f4f4f4; padding: 10px; border-radius: 5px;'>";
foreach ($tables as $table) {
    echo "• $table<br>";
}
echo "</div>";
echo "</div>";

// PASO 2: Deshabilitar foreign key checks y eliminar todas las tablas
echo "<div class='step'>";
echo "<div class='step-title'>🗑️ PASO 2: Eliminando todas las tablas</div>";

$conn->query("SET FOREIGN_KEY_CHECKS=0");

$eliminadas = 0;
$errores = 0;

foreach ($tables as $table) {
    if ($conn->query("DROP TABLE IF EXISTS `$table`")) {
        $eliminadas++;
        echo "<div style='color: #666; font-size: 12px;'>✓ Eliminada: $table</div>";
    } else {
        $errores++;
        echo "<div class='error'>✗ Error al eliminar: $table - " . $conn->error . "</div>";
    }
}

$conn->query("SET FOREIGN_KEY_CHECKS=1");

echo "<div class='stats'>";
echo "<strong>📈 Resumen de Eliminación:</strong><br>";
echo "✓ Tablas eliminadas: <span class='success'>$eliminadas</span><br>";
echo "✗ Errores: <span class='error'>$errores</span>";
echo "</div>";
echo "</div>";

// PASO 3: Verificar que la base de datos esté vacía
echo "<div class='step'>";
echo "<div class='step-title'>✅ PASO 3: Verificando limpieza</div>";

$result = $conn->query("SHOW TABLES");
$tablas_restantes = $result->num_rows;

if ($tablas_restantes == 0) {
    echo "<div class='success'>✓✓✓ Base de datos completamente limpia ✓✓✓</div>";
} else {
    echo "<div class='warning'>⚠ Aún quedan $tablas_restantes tablas</div>";
}
echo "</div>";

// PASO 4: Listo para importar
echo "<div class='step'>";
echo "<div class='step-title'>📥 PASO 4: Listo para importar datos limpios</div>";
echo "<div class='success'>✓ La base de datos está completamente vacía y lista para importar</div>";
echo "<div style='margin-top: 20px; padding: 20px; background: #e7f3ff; border-left: 4px solid #005aa3; border-radius: 5px;'>";
echo "<strong style='color: #005aa3;'>Siguiente paso:</strong><br>";
echo "Haz clic en el botón de abajo para iniciar la importación de datos reales:<br><br>";
echo "<a href='importar_migracion.php' style='display: inline-block; background: #005aa3; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 16px;'>🚀 Importar Datos Reales</a>";
echo "</div>";
echo "</div>";

$conn->close();

echo "</div></body></html>";
?>
