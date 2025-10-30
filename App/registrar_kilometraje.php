<?php
header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/Conexiones/Conexion.php';
if (isset($conn) && $conn instanceof mysqli) {
    $conn->set_charset('utf8mb4');
}

// Entrada: username (chofer asignado) y km (odómetro final absoluto)
$username = isset($_POST['username']) ? trim($_POST['username']) : (isset($_GET['username']) ? trim($_GET['username']) : '');
$kmFinal  = isset($_POST['km']) ? trim($_POST['km']) : (isset($_GET['km']) ? trim($_GET['km']) : '');

if ($username === '' || $kmFinal === '' || !is_numeric($kmFinal)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'PARAMS']);
    exit;
}

$kmFinal = (int)$kmFinal;
if ($kmFinal < 0) { $kmFinal = 0; }

try {
    // Resolver vehículo e id_chofer por username
    $sqlVeh = 'SELECT v.id_vehiculo, c.ID AS id_chofer
               FROM vehiculos AS v
               INNER JOIN choferes AS c ON c.ID = v.id_chofer_asignado
               WHERE c.username = ?
               LIMIT 1';
    $st = $conn->prepare($sqlVeh);
    if (!$st) { throw new Exception('Prepare veh: ' . $conn->error); }
    $st->bind_param('s', $username);
    $st->execute();
    $st->store_result();
    $idVehiculo = null; $idChofer = null;
    $st->bind_result($idVehiculo, $idChofer);
    $found = $st->fetch();
    $st->free_result();
    $st->close();

    if (!$found) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'NO_ASIGNADO']);
        exit;
    }

    // Km actuales del vehículo (para fallback y actualización por delta)
    $kmActualVeh = 0; $kmTotalVeh = 0; $kmServicio = 0;
    $stVeh = $conn->prepare('SELECT Km_Actual, Km_Total, Km_de_Servicio FROM vehiculos WHERE id_vehiculo = ? LIMIT 1');
    if (!$stVeh) { throw new Exception('Prepare veh2: ' . $conn->error); }
    $stVeh->bind_param('i', $idVehiculo);
    $stVeh->execute();
    $stVeh->bind_result($kmActualVeh, $kmTotalVeh, $kmServicio);
    $stVeh->fetch();
    $stVeh->close();

    // Último kilometraje_final como inicial; si no hay, usar Km_Actual del vehículo
    $sqlLast = 'SELECT kilometraje_final
                FROM registro_kilometraje
                WHERE id_vehiculo = ?
                ORDER BY id_registro DESC
                LIMIT 1';
    $stLast = $conn->prepare($sqlLast);
    if (!$stLast) { throw new Exception('Prepare last: ' . $conn->error); }
    $stLast->bind_param('i', $idVehiculo);
    $stLast->execute();
    $stLast->store_result();
    $kmInicial = null; $tmp = null;
    $stLast->bind_result($tmp);
    if ($stLast->fetch() && $tmp !== null) { $kmInicial = (int)$tmp; }
    $stLast->free_result();
    $stLast->close();
    if ($kmInicial === null) { $kmInicial = (int)$kmActualVeh; }

    // Delta y actualización de vehículo (misma lógica que detalles_vehiculo.php)
    $kmRecorridos = $kmFinal - $kmInicial;
    if ($kmRecorridos < 0) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'KM_NEGATIVO']);
        exit;
    }
    $kmActualNuevo = (int)$kmActualVeh + $kmRecorridos;
    $kmTotalNuevo  = (int)$kmTotalVeh  + $kmRecorridos;
    $stUp = $conn->prepare('UPDATE vehiculos SET Km_Actual = ?, Km_Total = ? WHERE id_vehiculo = ?');
    if ($stUp) {
        $stUp->bind_param('iii', $kmActualNuevo, $kmTotalNuevo, $idVehiculo);
        $stUp->execute();
        $stUp->close();
    }

    // Insertar registro
    $sqlIns = 'INSERT INTO registro_kilometraje (id_vehiculo, id_chofer, Tipo_Registro, fecha_registro, kilometraje_inicial, kilometraje_final)
               VALUES (?, ?, "Registro", CURDATE(), ?, ?)';
    $stIns = $conn->prepare($sqlIns);
    if (!$stIns) { throw new Exception('Prepare ins: ' . $conn->error); }
    $stIns->bind_param('iiii', $idVehiculo, $idChofer, $kmInicial, $kmFinal);
    $stIns->execute();
    $idReg = $conn->insert_id;
    $stIns->close();

    // Auto-crear Orden de Servicio si alcanzó/superó Km_de_Servicio (evitar duplicados)
    $km_objetivo = (int)($kmServicio ?? 0);
    if ($km_objetivo > 0 && $kmActualNuevo >= $km_objetivo) {
        $hasEstatus = false;
        if ($rc = $conn->query("SHOW COLUMNS FROM orden_servicio LIKE 'estatus'")) {
            $hasEstatus = (bool)$rc->num_rows;
        }

        $sqlOpen = $hasEstatus
            ? "SELECT id FROM orden_servicio WHERE id_vehiculo={$idVehiculo} AND (estatus IS NULL OR estatus IN ('Pendiente','Programado','EnTaller')) LIMIT 1"
            : "SELECT id FROM orden_servicio WHERE id_vehiculo={$idVehiculo} LIMIT 1";
        $existeAbierta = false;
        if ($r = $conn->query($sqlOpen)) { $existeAbierta = (bool)$r->num_rows; }

        if (!$existeAbierta) {
            // Asegurar que id_servicio permita NULL
            $permiteNull = true;
            if ($rc = $conn->query("SHOW COLUMNS FROM orden_servicio LIKE 'id_servicio'")) {
                if ($rc->num_rows) {
                    $col = $rc->fetch_assoc();
                    $permiteNull = (strtoupper((string)($col['Null'] ?? '')) === 'YES');
                }
            }
            if (!$permiteNull) {
                try { @$conn->query("ALTER TABLE orden_servicio MODIFY id_servicio INT NULL"); } catch (Throwable $e) {}
                if ($rc = $conn->query("SHOW COLUMNS FROM orden_servicio LIKE 'id_servicio'")) {
                    if ($rc->num_rows) {
                        $col = $rc->fetch_assoc();
                        $permiteNull = (strtoupper((string)($col['Null'] ?? '')) === 'YES');
                    }
                }
            }

            if ($permiteNull) {
                $nota = $conn->real_escape_string('[AUTO_KM] Autogenerado por kilometraje alcanzado');
                if ($hasEstatus) {
                    $conn->query("INSERT INTO orden_servicio (id_vehiculo,id_servicio,duracion_minutos,notas,estatus) VALUES ({$idVehiculo}, NULL, 0, '{$nota}', 'Pendiente')");
                    $osId = $conn->insert_id;
                    // Historial de estatus si existe la tabla
                    @$conn->query("CREATE TABLE IF NOT EXISTS orden_servicio_hist (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        id_orden INT NOT NULL,
                        de VARCHAR(20) NULL,
                        a VARCHAR(20) NOT NULL,
                        hecho_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        usuario VARCHAR(64) NULL,
                        comentario VARCHAR(255) NULL,
                        INDEX idx_hist_orden (id_orden)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                    $usuarioLog = $conn->real_escape_string($username ?: 'api');
                    @$conn->query("INSERT INTO orden_servicio_hist (id_orden,de,a,usuario,comentario) VALUES ({$osId},'', 'Pendiente','{$usuarioLog}','Autogenerado por KM')");
                } else {
                    $conn->query("INSERT INTO orden_servicio (id_vehiculo,id_servicio,duracion_minutos,notas) VALUES ({$idVehiculo}, NULL, 0, '{$nota}')");
                }
            } else {
                // No se pudo permitir NULL en id_servicio: evitar fatal y continuar
                error_log('ORDEN_SERVICIO auto: id_servicio es NOT NULL. No se creó OS automática.');
            }
        }
    }

    echo json_encode([
        'ok' => true,
        'id_registro' => (int)$idReg,
        'id_vehiculo' => (int)$idVehiculo,
        'id_chofer' => is_null($idChofer) ? null : (int)$idChofer,
        'km_inicial' => (int)$kmInicial,
        'km_final' => (int)$kmFinal,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'ERROR']);
}
