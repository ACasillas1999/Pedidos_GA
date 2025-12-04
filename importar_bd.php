<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(0); // Sin límite de tiempo para importaciones grandes

echo "<h1>Importar Base de Datos a EC2</h1>";
echo "<pre>";

// Configuración de la base de datos EC2
$host = '18.211.75.118';
$user = 'root';
$pass = 'Ascension2024**';
$dbname = 'gpoascen_pedidos_app';

// Ruta del archivo SQL
$sqlFile = 'C:\Users\compras-ovalo6\Downloads\gpoascen_pedidos_app.sql';

if (!file_exists($sqlFile)) {
    die("ERROR: El archivo SQL no existe en: $sqlFile\n");
}

echo "Archivo encontrado: $sqlFile\n";
echo "Tamaño: " . number_format(filesize($sqlFile) / 1024 / 1024, 2) . " MB\n\n";

// Conectar a MySQL EC2
echo "Conectando a la base de datos EC2...\n";
$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("ERROR de conexión: " . $conn->connect_error . "\n");
}

echo "✓ Conectado exitosamente\n\n";

// Leer el archivo SQL
echo "Leyendo archivo SQL...\n";
$sql = file_get_contents($sqlFile);

if ($sql === false) {
    die("ERROR: No se pudo leer el archivo SQL\n");
}

echo "✓ Archivo leído correctamente\n\n";

// Ejecutar el SQL
echo "Importando base de datos...\n";
echo "ADVERTENCIA: Esto puede tomar varios minutos...\n\n";

// Dividir por punto y coma y ejecutar cada statement
$statements = array_filter(array_map('trim', explode(';', $sql)));
$total = count($statements);
$ejecutados = 0;
$errores = 0;

echo "Total de statements a ejecutar: $total\n\n";

foreach ($statements as $i => $statement) {
    if (empty($statement)) continue;

    // Mostrar progreso cada 100 statements
    if ($i % 100 == 0) {
        echo "Progreso: $i / $total (" . number_format(($i / $total) * 100, 1) . "%)\n";
    }

    if (!$conn->query($statement)) {
        $errores++;
        echo "ERROR en statement $i: " . $conn->error . "\n";
        echo "Statement: " . substr($statement, 0, 100) . "...\n\n";

        // Si hay demasiados errores, detener
        if ($errores > 10) {
            echo "DETENIDO: Demasiados errores\n";
            break;
        }
    } else {
        $ejecutados++;
    }
}

echo "\n--- RESUMEN ---\n";
echo "Total statements: $total\n";
echo "Ejecutados exitosamente: $ejecutados\n";
echo "Errores: $errores\n";

if ($errores == 0) {
    echo "\n✓✓✓ IMPORTACIÓN COMPLETADA EXITOSAMENTE ✓✓✓\n";
} else {
    echo "\n⚠ IMPORTACIÓN COMPLETADA CON ERRORES ⚠\n";
}

$conn->close();
echo "</pre>";
?>
