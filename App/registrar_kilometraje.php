<?php
header('Content-Type: application/json; charset=UTF-8');

$DB_HOST = 'localhost';
$DB_NAME = 'gopascen_pedidos_app';
$DB_USER = 'root';
$DB_PASS = '';

$username = isset($_POST['username']) ? trim($_POST['username']) : (isset($_GET['username']) ? trim($_GET['username']) : '');
$kmActual = isset($_POST['km']) ? trim($_POST['km']) : (isset($_GET['km']) ? trim($_GET['km']) : '');

if ($username === '' || $kmActual === '' || !is_numeric($kmActual)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'PARAMS']);
    exit;
}

$kmActual = (int)$kmActual;
if ($kmActual < 0) { $kmActual = 0; }

try {
    $pdo = new PDO("mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8", $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // Resolver vehículo e id_chofer por username (dos posibles columnas)
    $sql = "SELECT v.id_vehiculo, c.id_chofer
            FROM vehiculos v
            INNER JOIN choferes c ON c.id_chofer = v.id_chofer_asignado
            WHERE c.username = :u
            LIMIT 1";
    $st = $pdo->prepare($sql);
    $st->execute([':u' => $username]);
    $veh = $st->fetch();

    if (!$veh) {
        try {
            $sql2 = "SELECT v.id_vehiculo, c.id_chofer
                     FROM vehiculos v
                     INNER JOIN choferes c ON c.id_chofer = v.id_chofer_asignado
                     WHERE c.usuario = :u
                     LIMIT 1";
            $st2 = $pdo->prepare($sql2);
            $st2->execute([':u' => $username]);
            $veh = $st2->fetch();
        } catch (Throwable $e) { /* ignorar */ }
    }

    if (!$veh) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'NO_ASIGNADO']);
        exit;
    }

    $idVehiculo = (int)$veh['id_vehiculo'];
    $idChofer = isset($veh['id_chofer']) ? (int)$veh['id_chofer'] : null;

    // Obtener último kilometraje_final para usarlo como inicial
    $sqlLast = "SELECT kilometraje_final FROM registro_kilometraje WHERE id_vehiculo = :idv ORDER BY fecha_registro DESC, id_registro DESC LIMIT 1";
    $stLast = $pdo->prepare($sqlLast);
    $stLast->execute([':idv' => $idVehiculo]);
    $rowLast = $stLast->fetch();
    $kmInicial = $rowLast && isset($rowLast['kilometraje_final']) ? (int)$rowLast['kilometraje_final'] : 0;

    // Insertar registro
    $sqlIns = "INSERT INTO registro_kilometraje (id_vehiculo, id_chofer, Tipo_Registro, fecha_registro, kilometraje_inicial, kilometraje_final)
               VALUES (:idv, :idc, 'Registro', CURDATE(), :kmi, :kmf)";
    $stIns = $pdo->prepare($sqlIns);
    $stIns->execute([
        ':idv' => $idVehiculo,
        ':idc' => $idChofer,
        ':kmi' => $kmInicial,
        ':kmf' => $kmActual,
    ]);

    echo json_encode([
        'ok' => true,
        'id_registro' => (int)$pdo->lastInsertId(),
        'id_vehiculo' => $idVehiculo,
        'id_chofer' => $idChofer,
        'km_inicial' => $kmInicial,
        'km_final' => $kmActual,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'ERROR']);
}

