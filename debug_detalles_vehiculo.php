<?php
// Debug file para ver el error exacto
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_debug.log');

echo "<h1>Debug detalles_vehiculo.php</h1>";
echo "<pre>";

// Iniciar sesión segura
ini_set('session.cookie_httponly', true);
ini_set('session.cookie_secure', false); // Cambio a false para HTTP
session_name("GA");
session_start();

echo "1. Sesión iniciada OK\n";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    echo "ERROR: No hay sesión activa\n";
    echo "Redirigiendo...\n";
    // header("location: /Pedidos_GA/Sesion/login.html");
    // exit;
} else {
    echo "2. Usuario logueado OK\n";
}

require_once __DIR__ . "/Conexiones/Conexion.php";
echo "3. Conexión incluida OK\n";

if (!isset($conn)) {
    echo "ERROR: Variable \$conn no existe\n";
    exit;
}

if ($conn->connect_error) {
    echo "ERROR de conexión: " . $conn->connect_error . "\n";
    exit;
}

echo "4. Conexión a BD OK\n";

$id_vehiculo = isset($_GET['id']) ? (int)$_GET['id'] : 7;
echo "5. ID vehículo: $id_vehiculo\n";

// Probar query por query
echo "\n--- Probando query de vehículo ---\n";
$query = "SELECT * FROM vehiculos WHERE id_vehiculo = $id_vehiculo";
echo "Query: $query\n";
$result = $conn->query($query);
if (!$result) {
    echo "ERROR: " . $conn->error . "\n";
} else {
    $vehiculo = $result->fetch_assoc();
    echo "OK - Vehículo encontrado: " . ($vehiculo ? print_r($vehiculo, true) : "NULL") . "\n";
}

echo "\n--- Probando query de historial kilometraje ---\n";
$query = "SELECT r.*, c.username AS chofer
    FROM registro_kilometraje r
    JOIN choferes c ON r.id_chofer = c.ID
    WHERE r.id_vehiculo = $id_vehiculo
    ORDER BY r.id_registro DESC";
echo "Query: $query\n";
$result = $conn->query($query);
if (!$result) {
    echo "ERROR: " . $conn->error . "\n";
} else {
    echo "OK - " . $result->num_rows . " registros\n";
}

echo "\n--- Probando query de gasolina_semanal ---\n";
$query = "SELECT fecha_registro, anio, semana, importe, observaciones
  FROM gasolina_semanal
  WHERE id_vehiculo = {$id_vehiculo}
  ORDER BY fecha_registro DESC
  LIMIT 2";
echo "Query: $query\n";
$result = $conn->query($query);
if (!$result) {
    echo "ERROR: " . $conn->error . "\n";
} else {
    echo "OK - " . $result->num_rows . " registros\n";
}

echo "\n--- Probando query de servicios ---\n";
$query = "SELECT
    os.id,
    os.id_servicio,
    s.nombre AS nombre_servicio,
    os.duracion_minutos,
    os.notas,
    os.creado_en,
    os.estatus,
    os.fecha_programada,
    s.costo_mano_obra,
    s.precio
  FROM orden_servicio os
  LEFT JOIN servicios s ON os.id_servicio = s.id
  WHERE os.id_vehiculo = {$id_vehiculo}
  ORDER BY os.creado_en DESC";
echo "Query: $query\n";
$result = $conn->query($query);
if (!$result) {
    echo "ERROR: " . $conn->error . "\n";
} else {
    echo "OK - " . $result->num_rows . " registros\n";
}

echo "\n</pre>";
echo "<p><a href='detalles_vehiculo.php?id=$id_vehiculo'>Ir a detalles_vehiculo.php original</a></p>";
?>
