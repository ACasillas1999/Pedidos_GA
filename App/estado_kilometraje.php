<?php
header('Content-Type: application/json; charset=UTF-8');

// Ajusta estos datos a tu entorno si es necesario
$DB_HOST = 'localhost';
$DB_NAME = 'gopascen_pedidos_app';
$DB_USER = 'root';
$DB_PASS = '';

$username = isset($_POST['username']) ? trim($_POST['username']) : (isset($_GET['username']) ? trim($_GET['username']) : '');
if ($username === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'FALTA_USERNAME']);
    exit;
}

try {
    $pdo = new PDO("mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8", $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // Buscar vehículo asignado por username; probar dos posibles columnas
    $sql = "SELECT v.id_vehiculo, c.id_chofer
            FROM vehiculos v
            INNER JOIN choferes c ON c.id_chofer = v.id_chofer_asignado
            WHERE c.username = :u
            LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':u' => $username]);
    $veh = $stmt->fetch();

    if (!$veh) {
        // Intento alterno si la columna fuera 'usuario'
        try {
            $sql2 = "SELECT v.id_vehiculo, c.id_chofer
                     FROM vehiculos v
                     INNER JOIN choferes c ON c.id_chofer = v.id_chofer_asignado
                     WHERE c.usuario = :u
                     LIMIT 1";
            $stmt2 = $pdo->prepare($sql2);
            $stmt2->execute([':u' => $username]);
            $veh = $stmt2->fetch();
        } catch (Throwable $e) {
            // Ignorar si la columna no existe
        }
    }

    if (!$veh) {
        echo json_encode(['ok' => true, 'assigned' => false]);
        exit;
    }

    $idVehiculo = (int)$veh['id_vehiculo'];
    $idChofer   = isset($veh['id_chofer']) ? (int)$veh['id_chofer'] : null;

    // Último registro de kilometraje del vehículo
    $sqlLast = "SELECT id_registro, fecha_registro, kilometraje_final
                FROM registro_kilometraje
                WHERE id_vehiculo = :idv
                ORDER BY fecha_registro DESC, id_registro DESC
                LIMIT 1";
    $stmtLast = $pdo->prepare($sqlLast);
    $stmtLast->execute([':idv' => $idVehiculo]);
    $last = $stmtLast->fetch();

    $needs = true; // si nunca ha registrado, pedimos
    $lastFecha = null;
    $lastKm = null;

    if ($last) {
        $lastFecha = $last['fecha_registro'];
        $lastKm = is_null($last['kilometraje_final']) ? null : (int)$last['kilometraje_final'];

        // Comparar con hoy - 3 días
        $hoy = new DateTime('today');
        $limite = (clone $hoy)->modify('-3 days');
        $f = DateTime::createFromFormat('Y-m-d', substr($lastFecha, 0, 10));
        if ($f && $f >= $limite) {
            $needs = false;
        }
    }

    echo json_encode([
        'ok' => true,
        'assigned' => true,
        'id_vehiculo' => $idVehiculo,
        'id_chofer' => $idChofer,
        'last_fecha' => $lastFecha,
        'last_km_final' => $lastKm,
        'needs_km' => $needs,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'ERROR']);
}

