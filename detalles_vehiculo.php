<?php
// Iniciar sesión segura
ini_set('session.cookie_httponly', true);
ini_set('session.cookie_secure', true);
session_name("GA");
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: /Pedidos_GA/Sesion/login.html");
    exit;
}
require_once __DIR__ . "/Conexiones/Conexion.php";

$sucursal = $_SESSION["Sucursal"];
$rol      = $_SESSION["Rol"];
// --- Sucursales canónicas (tal cual tu grid) ---
$SUCURSALES_CANON = [
    'DEASA',
    'DIMEGSA',
    'AIESA',
    'TALLER ESI',
    'TAPATIA',
    'SEGSA',
    'FESA',
    'CODI',
    'COMPRAS',
    'CONTITUYENTES',
    'VALLARTA',
    'GABSA',
    'QUERETARO',
    'PRESTAMOS',
    'FORANEOS',
    'OVALO'
];

// Helpers
function suc_norm($s)
{
    return strtoupper(trim((string)$s));
}
function suc_valida($s)
{
    global $SUCURSALES_CANON;
    return in_array(suc_norm($s), $SUCURSALES_CANON, true);
}

// ------- Datos base -------
$id_vehiculo = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$vehiculo = $conn->query("SELECT * FROM vehiculos WHERE id_vehiculo = $id_vehiculo")->fetch_assoc();

$historial = $conn->query("
    SELECT r.*, c.username AS chofer
    FROM registro_kilometraje r
    JOIN choferes c ON r.id_chofer = c.ID
    WHERE r.id_vehiculo = $id_vehiculo
    ORDER BY r.id_registro DESC
");
$ultimo_registro = $conn->query("
    SELECT kilometraje_final
    FROM registro_kilometraje
    WHERE id_vehiculo = $id_vehiculo
    ORDER BY id_registro DESC
    LIMIT 1
")->fetch_assoc();
$kilometraje_inicial_sugerido = $ultimo_registro['kilometraje_final'] ?? ($vehiculo['Km_Actual'] ?? 0);


// Historial de conductores
$hist_conductores = $conn->query("
  SELECT
    hc.*,
    ch.username AS chofer_nombre,
    ch.Sucursal AS sucursal_chofer,
    TIMESTAMPDIFF(MINUTE, hc.fecha_inicio, IFNULL(hc.fecha_fin, NOW())) AS minutos_total
  FROM historial_conductores hc
  JOIN choferes ch ON ch.ID = hc.id_chofer
  WHERE hc.id_vehiculo = {$id_vehiculo}
  ORDER BY hc.id DESC
");

// Historial de responsables (para vehículos particulares)
$hist_responsables = $conn->query("
  SELECT
    hr.*,
    TIMESTAMPDIFF(MINUTE, hr.fecha_inicio, IFNULL(hr.fecha_fin, NOW())) AS minutos_total
  FROM historial_responsables hr
  WHERE hr.id_vehiculo = {$id_vehiculo}
  ORDER BY hr.id DESC
");

// Historial de sucursal del vehículo
$hist_sucursal = $conn->query("
  SELECT sucursal_anterior, sucursal_nueva, fecha, usuario
  FROM historial_sucursal
  WHERE id_vehiculo = {$id_vehiculo}
  ORDER BY id DESC
");

// Checklist vehicular (observaciones por vehículo)
$checklist_obs = $conn->query("
  SELECT id, fecha_inspeccion, kilometraje, seccion, item, calificacion, observaciones_rotulado
  FROM checklist_vehicular
  WHERE id_vehiculo = {$id_vehiculo}
  ORDER BY fecha_inspeccion DESC, seccion ASC, id ASC
");

// Agrupar por fecha y sección para acordeón
$obs_group = [];
if ($checklist_obs && $checklist_obs instanceof mysqli_result) {
    while ($r = $checklist_obs->fetch_assoc()) {
        $fecha = (string)($r['fecha_inspeccion'] ?? '');
        $sec   = strtoupper((string)($r['seccion'] ?? ''));
        if (!isset($obs_group[$fecha])) {
            $obs_group[$fecha] = [
                'km' => $r['kilometraje'] ?? null,
                'secciones' => []
            ];
        }
        if (!isset($obs_group[$fecha]['secciones'][$sec])) {
            $obs_group[$fecha]['secciones'][$sec] = [];
        }
        $obs_group[$fecha]['secciones'][$sec][] = $r;
    }
}

// Lista de sucursales disponibles (para cambiar sucursal)
$lista_sucursales = $conn->query("SELECT DISTINCT Sucursal AS suc FROM choferes WHERE Sucursal IS NOT NULL AND Sucursal<>'' ORDER BY Sucursal");

// Historial de servicios realizados al vehículo
$hist_servicios = $conn->query("
  SELECT
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
  ORDER BY os.creado_en DESC
");


// Resúmenes para pestaña Información (últimos 2 registros)
$resumen_km = $conn->query("
  SELECT fecha_registro, kilometraje_inicial, kilometraje_final
  FROM registro_kilometraje
  WHERE id_vehiculo = {$id_vehiculo}
  ORDER BY id_registro DESC
  LIMIT 2
");

$resumen_conductores = $conn->query("
  SELECT hc.fecha_inicio, hc.fecha_fin, ch.username AS chofer_nombre
  FROM historial_conductores hc
  JOIN choferes ch ON ch.ID = hc.id_chofer
  WHERE hc.id_vehiculo = {$id_vehiculo}
  ORDER BY hc.id DESC
  LIMIT 2
");

$resumen_obs = $conn->query("
  SELECT fecha_inspeccion, seccion, item, calificacion
  FROM checklist_vehicular
  WHERE id_vehiculo = {$id_vehiculo}
  ORDER BY fecha_inspeccion DESC, id DESC
  LIMIT 2
");

$resumen_gas = $conn->query("
  SELECT fecha_registro, anio, semana, importe, observaciones
  FROM gasolina_semanal
  WHERE id_vehiculo = {$id_vehiculo}
  ORDER BY fecha_registro DESC
  LIMIT 2
");

$resumen_servicios = $conn->query("
  SELECT os.creado_en, os.estatus, s.nombre AS nombre_servicio
  FROM orden_servicio os
  LEFT JOIN servicios s ON os.id_servicio = s.id
  WHERE os.id_vehiculo = {$id_vehiculo}
  ORDER BY os.creado_en DESC
  LIMIT 2
");


// ------- WhatsApp (igual que tenías) -------
$whatsapp_token  = "EAAGacaATjwEBOZBgqhohcVk1ZBGEAbiTl7i86qESvSPjdllaomwzIG7LmOOvyTFpzyIlXX6dtTYTVTLLuw6SjaLoh2rec07I8qu1nGNYSVZAmQTGNa3QCQjujTqfd7QuLLwFNQllnX2z1V7JvToDhEi5KVqUWXHSqgSETvGyU7S2SN2fpXW0NpQaRI48pwZAgGS7A1BQMjLl5ZBjy";
$phone_number_id = "335894526282507";
$recipient_phone = "523339565268";
$template_name   = "notificacion_servicio";

// ------- POST registrar kilometraje -------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kilometraje_final'])) {
    $id_chofer           = (int)$_POST['id_chofer'];
    $kilometraje_inicial = (int)$_POST['kilometraje_inicial'];
    $kilometraje_final   = (int)$_POST['kilometraje_final'];
    $km_recorridos       = $kilometraje_final - $kilometraje_inicial;
    $fecha_actual        = date("Y-m-d");

    if ($km_recorridos < 0) {
        echo "<script>alert('El kilometraje final no puede ser menor al inicial.'); window.history.back();</script>";
        exit;
    }

// Comportamiento como en vehiculos_old: Km_Actual avanza con el uso y Km_Total conserva histórico
$kmActualNuevo = ((int)$vehiculo['Km_Actual']) + $km_recorridos;
$kmTotalNuevo  = ((int)$vehiculo['Km_Total'])  + $km_recorridos;
$conn->query("UPDATE vehiculos
                  SET Km_Actual = $kmActualNuevo,
                      Km_Total  = $kmTotalNuevo
                  WHERE id_vehiculo = $id_vehiculo");
// Para la lógica siguiente, usar el nuevo Km_Actual
$nuevo_kilometraje = $kmActualNuevo;

    $conn->query("INSERT INTO registro_kilometraje
                    (id_vehiculo, id_chofer, Tipo_Registro, fecha_registro, kilometraje_inicial, kilometraje_final)
                  VALUES
                    ($id_vehiculo, $id_chofer, 'Registro', '$fecha_actual', $kilometraje_inicial, $kilometraje_final)");

    // Si ya alcanzó o superó el kilometraje de servicio, auto-crear una Orden de Servicio Pendiente (si no existe abierta)
    $km_objetivo = (int)($vehiculo['Km_de_Servicio'] ?? 0);
    if ($km_objetivo > 0 && $nuevo_kilometraje >= $km_objetivo) {
        // Verificar si existe columna estatus/fecha_programada en orden_servicio (evitar errores en instalaciones antiguas)
        $hasEstatus = false;
        if ($rc = $conn->query("SHOW COLUMNS FROM orden_servicio LIKE 'estatus'")) {
            $hasEstatus = ($rc->num_rows > 0);
        }
        if (!$hasEstatus) {
            // Intentar agregar columnas; si falla, continuamos sin romper el flujo
            @$conn->query("ALTER TABLE orden_servicio ADD COLUMN IF NOT EXISTS estatus VARCHAR(20) NULL");
            @$conn->query("ALTER TABLE orden_servicio ADD COLUMN IF NOT EXISTS fecha_programada DATE NULL");
            if ($rc = $conn->query("SHOW COLUMNS FROM orden_servicio LIKE 'estatus'")) {
                $hasEstatus = ($rc->num_rows > 0);
            }
        }

        // Evitar duplicados: buscar OS abierta (estatus NULL o en Pendiente/Programado/EnTaller)
        $sqlOpen = $hasEstatus
            ? "SELECT id FROM orden_servicio WHERE id_vehiculo=$id_vehiculo AND (estatus IS NULL OR estatus IN ('Pendiente','Programado','EnTaller')) LIMIT 1"
            : "SELECT id FROM orden_servicio WHERE id_vehiculo=$id_vehiculo LIMIT 1"; // mejor que nada
        $existeAbierta = false;
        if ($r = $conn->query($sqlOpen)) { $existeAbierta = (bool)$r->num_rows; }

        if (!$existeAbierta) {
            // Ya no seleccionamos un servicio automático: intentamos crear la OS sin servicio
            // Asegurar que orden_servicio.id_servicio permita NULL
            $permiteNull = true;
            if ($rc = $conn->query("SHOW COLUMNS FROM orden_servicio LIKE 'id_servicio'")) {
                if ($rc->num_rows) {
                    $col = $rc->fetch_assoc();
                    $permiteNull = (strtoupper((string)($col['Null'] ?? '')) === 'YES');
                }
            }
            if (!$permiteNull) {
                // Intentar modificar el esquema para permitir NULL
                try { @$conn->query("ALTER TABLE orden_servicio MODIFY id_servicio INT NULL"); } catch (Throwable $e) {}
                // Revalidar
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
                    // Insertar con id_servicio NULL y estatus Pendiente
                    $conn->query("INSERT INTO orden_servicio (id_vehiculo,id_servicio,duracion_minutos,notas,estatus)
                                  VALUES ($id_vehiculo, NULL, 0, '$nota','Pendiente')");
                    $osId = $conn->insert_id;
                    // Historial de estatus (si existe la tabla de historial)
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
                    $usuarioLog = $conn->real_escape_string($_SESSION['Usuario'] ?? ($_SESSION['Rol'] ?? 'sistema'));
                    @$conn->query("INSERT INTO orden_servicio_hist (id_orden,de,a,usuario,comentario)
                                   VALUES ($osId,'','Pendiente','$usuarioLog','Autogenerado por KM')");
                } else {
                    // Si no existe la columna estatus, inserta sin estatus
                    $conn->query("INSERT INTO orden_servicio (id_vehiculo,id_servicio,duracion_minutos,notas)
                                  VALUES ($id_vehiculo, NULL, 0, '$nota')");
                }
            } else {
                // No se pudo permitir NULL en id_servicio: evitar fatal y continuar sin crear OS
                error_log('ORDEN_SERVICIO auto: id_servicio es NOT NULL. No se creó OS automática.');
            }
        }
    }

    $km_faltante = ((int)$vehiculo['Km_de_Servicio']) - $nuevo_kilometraje;

    if ($km_faltante <= 500) {
        $url = "https://graph.facebook.com/v19.0/$phone_number_id/messages";
        $data = [
            "messaging_product" => "whatsapp",
            "to" => $recipient_phone,
            "type" => "template",
            "template" => [
                "name" => $template_name,
                "language" => ["code" => "en_US"],
                "components" => [[
                    "type" => "body",
                    "parameters" => [
                        ["type" => "text", "text" => $vehiculo['placa']],
                        ["type" => "text", "text" => $km_faltante]
                    ]
                ]]
            ]
        ];
        $headers = [
            "Authorization: Bearer $whatsapp_token",
            "Content-Type: application/json"
        ];
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        file_put_contents("whatsapp_log.txt", date("Y-m-d H:i:s") . " - " . $response . PHP_EOL, FILE_APPEND);
    }

    echo "<script>alert('Registro guardado exitosamente'); window.location.href='detalles_vehiculo.php?id=$id_vehiculo';</script>";
    exit;
}

// ------- Valores visuales -------
$rawFoto = (string)($vehiculo['foto_path'] ?? '');
$vehImg  = $rawFoto !== '' ? '/Pedidos_GA/' . ltrim($rawFoto, '/')
    : '/Pedidos_GA/Img/vehiculos/placeholder_car.png';

$placa = (string)($vehiculo['placa'] ?? '');
$tipoV = (string)($vehiculo['tipo'] ?? '');
$sucV  = (string)($vehiculo['Sucursal'] ?? '');
$serie = (string)($vehiculo['numero_serie'] ?? '');

// Determinar si tiene foto o si debe mostrar inicial
$tieneFoto = $rawFoto !== '';
$inicialPlaca = $placa !== '' ? strtoupper(substr($placa, 0, 1)) : 'V';

$kmAct = (int)($vehiculo['Km_Actual'] ?? 0);
$kmTot = (int)($vehiculo['Km_Total'] ?? 0);
$kmSrv = (int)($vehiculo['Km_de_Servicio'] ?? 5000);

$proxSrv = max(0, $kmSrv - $kmAct);
$ultServ = $vehiculo['Fecha_Ultimo_Servicio'] ?? 'No registrado';

// Chofer asignado
$choferNombre = null;
$choferTel    = null;
if (!empty($vehiculo['id_chofer_asignado'])) {
    $idc = (int)$vehiculo['id_chofer_asignado'];
    $rc  = $conn->query("SELECT username, Numero FROM choferes WHERE ID=$idc LIMIT 1");
    if ($rc && $rc->num_rows) {
        $c = $rc->fetch_assoc();
        $choferNombre = $c['username'] ?? null;
        $choferTel    = $c['Numero']   ?? null;
    }
}

// Estado servicio
if ($proxSrv <= 0) {
    $estadoSrv = 'SERVICIO VENCIDO';
    $estadoSrvClass = 'pill-danger';
} elseif ($proxSrv <= 500) {
    $estadoSrv = 'SERVICIO PRONTO';
    $estadoSrvClass = 'pill-warn';
} else {
    $estadoSrv = 'OK';
    $estadoSrvClass = 'pill-ok';
}

// Gasolina (usar misma tabla que Gas.php: gasolina_semanal)
// Registrar o actualizar semana de gasolina para ESTE vehiculo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_POST['accion'] ?? '') === 'registrar_semana_gasolina')) {
    $idVehiculo = $id_vehiculo; // ya viene del contexto
    $importe = (float)($_POST['importe'] ?? 0);
    $obs = trim($_POST['observaciones'] ?? '');
    $fechaSemana = $_POST['fecha_semana'] ?? date('Y-m-d');

    try {
        $dt = new DateTime($fechaSemana);
    } catch (Exception $e) {
        $dt = new DateTime();
    }

    $anio = (int)$dt->format('o');
    $semana = (int)$dt->format('W');

    if ($idVehiculo > 0 && $importe > 0) {
        $stmt = $conn->prepare("
        INSERT INTO gasolina_semanal (id_vehiculo, anio, semana, importe, fecha_registro, observaciones)
        VALUES (?, ?, ?, ?, NOW(), ?)
        ON DUPLICATE KEY UPDATE
          importe = VALUES(importe),
          observaciones = VALUES(observaciones),
          fecha_registro = VALUES(fecha_registro)
      ");
        $stmt->bind_param('iiids', $idVehiculo, $anio, $semana, $importe, $obs);
        if ($stmt->execute()) {
            echo "<script>alert('Semana de gasolina guardada para {$semana}/{$anio}'); window.location.href='detalles_vehiculo.php?id={$idVehiculo}';</script>";
            exit;
        } else {
            echo "<script>alert('No se pudo guardar la semana de gasolina: " . htmlspecialchars($stmt->error, ENT_QUOTES, 'UTF-8') . "'); window.history.back();</script>";
            exit;
        }
    } else {
        echo "<script>alert('Faltan datos obligatorios (importe > 0).'); window.history.back();</script>";
        exit;
    }
}

$historial_gasolina = $conn->query("
  SELECT gs.*
  FROM gasolina_semanal gs
  WHERE gs.id_vehiculo = {$id_vehiculo}
  ORDER BY gs.fecha_registro DESC
");

/* ====== POST: Asignar chofer con historial ====== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'asignar_chofer') {
    $idChoferNuevo = (int)($_POST['id_chofer_nuevo'] ?? 0);
    if ($idChoferNuevo > 0 && $id_vehiculo > 0) {

        // Verificar que el vehículo no sea particular
        $esParticular = (int)($vehiculo['es_particular'] ?? 0);
        if ($esParticular === 1) {
            echo "<script>alert('No se puede asignar chofer a un vehículo particular.'); history.back();</script>";
            exit;
        }

        // Validar que el chofer no tenga otro vehículo asignado actualmente
        $stmt = $conn->prepare("SELECT v.id_vehiculo, v.placa, v.Tipo
                                FROM historial_conductores hc
                                JOIN vehiculos v ON hc.id_vehiculo = v.id_vehiculo
                                WHERE hc.id_chofer = ?
                                AND hc.fecha_fin IS NULL
                                AND hc.id_vehiculo != ?");
        $stmt->bind_param("ii", $idChoferNuevo, $id_vehiculo);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $vehiculoConflicto = $result->fetch_assoc();
            $placas = $vehiculoConflicto['placa'];
            $tipo = $vehiculoConflicto['Tipo'];
            $idConflicto = $vehiculoConflicto['id_vehiculo'];
            echo "<script>alert('Este chofer ya tiene asignado otro vehículo: {$tipo} ({$placas}).\\nDesasigna primero el vehículo anterior antes de asignar uno nuevo.'); history.back();</script>";
            exit;
        }

        // 1) Cerrar asignación anterior abierta (si existe)
        // Cerrar asignación abierta (si hay)
        $stmt = $conn->prepare("UPDATE historial_conductores
                        SET fecha_fin = NOW()
                        WHERE id_vehiculo = ? AND fecha_fin IS NULL");
        $stmt->bind_param("i", $id_vehiculo);
        $stmt->execute();

        // Insertar nueva asignación con marca de tiempo completa
        $usuarioCreador = $_SESSION['Usuario'] ?? ($_SESSION['Rol'] ?? 'sistema');
        $stmt = $conn->prepare("INSERT INTO historial_conductores
                        (id_vehiculo, id_chofer, fecha_inicio, creado_por)
                        VALUES (?, ?, NOW(), ?)");
        $stmt->bind_param("iis", $id_vehiculo, $idChoferNuevo, $usuarioCreador);
        $stmt->execute();

        // Actualizar chofer actual del vehículo
        $stmt = $conn->prepare("UPDATE vehiculos SET id_chofer_asignado = ? WHERE id_vehiculo = ?");
        $stmt->bind_param("ii", $idChoferNuevo, $id_vehiculo);
        $stmt->execute();

        header("Location: detalles_vehiculo.php?id={$id_vehiculo}&msg=chofer-actualizado");
        exit;
    }
}

/* ====== POST: Cambiar Km de Servicio ====== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_POST['accion'] ?? '') === 'cambiar_km_servicio')) {
    $nuevoKmServicio = (int)($_POST['km_de_servicio'] ?? 0);
    if ($nuevoKmServicio > 0 && $id_vehiculo > 0) {
        $stmt = $conn->prepare("UPDATE vehiculos SET Km_de_Servicio = ? WHERE id_vehiculo = ?");
        $stmt->bind_param("ii", $nuevoKmServicio, $id_vehiculo);
        $stmt->execute();
        header("Location: detalles_vehiculo.php?id={$id_vehiculo}&msg=km-servicio-actualizado");
        exit;
    } else {
        echo "<script>alert('Valor inválido para Km de Servicio');history.back();</script>";
        exit;
    }
}

/* ====== POST: Cambiar sucursal con historial ====== */
/* ====== POST: Cambiar sucursal con historial ====== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'cambiar_sucursal') {
    $sucursalNueva = suc_norm($_POST['sucursal_nueva'] ?? '');
    if ($sucursalNueva !== '' && suc_valida($sucursalNueva) && $id_vehiculo > 0) {
        $sucursalAnterior = suc_norm($vehiculo['Sucursal'] ?? '');
        if ($sucursalAnterior !== $sucursalNueva) {
            $usuario = $_SESSION['Usuario'] ?? ($_SESSION['Rol'] ?? 'sistema');

            $stmt = $conn->prepare("INSERT INTO historial_sucursal
                                    (id_vehiculo, sucursal_anterior, sucursal_nueva, usuario)
                                    VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $id_vehiculo, $sucursalAnterior, $sucursalNueva, $usuario);
            $stmt->execute();

            $stmt = $conn->prepare("UPDATE vehiculos SET Sucursal = ? WHERE id_vehiculo = ?");
            $stmt->bind_param("si", $sucursalNueva, $id_vehiculo);
            $stmt->execute();

            header("Location: detalles_vehiculo.php?id={$id_vehiculo}&msg=sucursal-actualizada");
            exit;
        }
    } else {
        echo "<script>alert('Sucursal no válida');history.back();</script>";
        exit;
    }
}

/* ====== POST: Desasignar chofer actual ====== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'desasignar_chofer') {
    // Cierra tramo abierto con NOW()
    $stmt = $conn->prepare("UPDATE historial_conductores
                            SET fecha_fin = NOW()
                            WHERE id_vehiculo = ? AND fecha_fin IS NULL");
    $stmt->bind_param("i", $id_vehiculo);
    $stmt->execute();

    // Limpia chofer actual en vehiculos
    $stmt = $conn->prepare("UPDATE vehiculos SET id_chofer_asignado = NULL WHERE id_vehiculo = ?");
    $stmt->bind_param("i", $id_vehiculo);
    $stmt->execute();

    header("Location: detalles_vehiculo.php?id={$id_vehiculo}&msg=chofer-desasignado");
    exit;
}

/* ====== POST: Asignar responsable (para vehículos particulares) ====== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'asignar_responsable') {
    $responsable = trim($_POST['responsable'] ?? '');
    if ($responsable !== '' && $id_vehiculo > 0) {
        // Verificar que el vehículo sea particular
        $esParticular = (int)($vehiculo['es_particular'] ?? 0);
        if ($esParticular !== 1) {
            echo "<script>alert('Solo se puede asignar responsable a vehículos particulares.'); history.back();</script>";
            exit;
        }

        // Validar que el responsable no tenga otro vehículo asignado actualmente
        $stmt = $conn->prepare("SELECT v.id_vehiculo, v.placa, v.Tipo
                                FROM historial_responsables hr
                                JOIN vehiculos v ON hr.id_vehiculo = v.id_vehiculo
                                WHERE hr.nombre_responsable = ?
                                AND hr.fecha_fin IS NULL
                                AND hr.id_vehiculo != ?");
        $stmt->bind_param("si", $responsable, $id_vehiculo);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $vehiculoConflicto = $result->fetch_assoc();
            $placas = $vehiculoConflicto['placa'];
            $tipo = $vehiculoConflicto['Tipo'];
            $idConflicto = $vehiculoConflicto['id_vehiculo'];
            echo "<script>alert('Esta persona ya tiene asignado otro vehículo: {$tipo} ({$placas}).\\nDesasigna primero el vehículo anterior antes de asignar uno nuevo.'); history.back();</script>";
            exit;
        }

        // Obtener responsable anterior
        $responsableAnterior = $vehiculo['responsable'] ?? '';

        // Solo proceder si el responsable cambió
        if ($responsableAnterior !== $responsable) {
            // 1) Cerrar asignación anterior abierta en historial_responsables (si existe)
            $stmt = $conn->prepare("UPDATE historial_responsables
                            SET fecha_fin = NOW()
                            WHERE id_vehiculo = ? AND fecha_fin IS NULL");
            $stmt->bind_param("i", $id_vehiculo);
            $stmt->execute();

            // 2) Insertar nueva asignación en historial_responsables
            $usuarioCreador = $_SESSION['Usuario'] ?? ($_SESSION['Rol'] ?? 'sistema');
            $stmt = $conn->prepare("INSERT INTO historial_responsables
                            (id_vehiculo, nombre_responsable, fecha_inicio, creado_por)
                            VALUES (?, ?, NOW(), ?)");
            $stmt->bind_param("iss", $id_vehiculo, $responsable, $usuarioCreador);
            $stmt->execute();

            // 3) Actualizar responsable del vehículo
            $stmt = $conn->prepare("UPDATE vehiculos SET responsable = ? WHERE id_vehiculo = ?");
            $stmt->bind_param("si", $responsable, $id_vehiculo);
            $stmt->execute();
        }

        header("Location: detalles_vehiculo.php?id={$id_vehiculo}&msg=responsable-actualizado");
        exit;
    }
}

/* ====== POST: Desasignar responsable ====== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'desasignar_responsable') {
    // Cerrar asignación abierta en historial_responsables
    $stmt = $conn->prepare("UPDATE historial_responsables
                    SET fecha_fin = NOW()
                    WHERE id_vehiculo = ? AND fecha_fin IS NULL");
    $stmt->bind_param("i", $id_vehiculo);
    $stmt->execute();

    // Actualizar vehículo
    $stmt = $conn->prepare("UPDATE vehiculos SET responsable = NULL WHERE id_vehiculo = ?");
    $stmt->bind_param("i", $id_vehiculo);
    $stmt->execute();

    header("Location: detalles_vehiculo.php?id={$id_vehiculo}&msg=responsable-desasignado");
    exit;
}

/* ====== POST: Editar información del vehículo ====== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'editar_vehiculo') {
    $nuevaPlaca = trim($_POST['placa'] ?? '');
    $nuevoTipo = trim($_POST['tipo'] ?? '');
    $nuevaSerie = trim($_POST['numero_serie'] ?? '');
    $nuevaSucursal = suc_norm($_POST['sucursal'] ?? '');
    $nuevaRazon = trim($_POST['razon_social'] ?? '');
    $nuevoKmActual   = max(0, (int)($_POST['km_actual'] ?? ($vehiculo['Km_Actual'] ?? 0)));
    $nuevoKmTotal    = max($nuevoKmActual, (int)($_POST['km_total'] ?? ($vehiculo['Km_Total'] ?? 0)));
    $nuevoKmServicio = max(0, (int)($_POST['km_de_servicio'] ?? ($vehiculo['Km_de_Servicio'] ?? 0)));

    if ($nuevaPlaca !== '' && $nuevoTipo !== '' && $nuevaSerie !== '' && $id_vehiculo > 0) {
        // Verificar que la placa no esté en uso por otro vehículo
        $stmt = $conn->prepare("SELECT id_vehiculo FROM vehiculos WHERE placa = ? AND id_vehiculo != ?");
        $stmt->bind_param("si", $nuevaPlaca, $id_vehiculo);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            echo "<script>alert('La placa ya está en uso por otro vehículo.'); history.back();</script>";
            exit;
        }

        // Verificar que la serie no esté en uso por otro vehículo
        $stmt = $conn->prepare("SELECT id_vehiculo FROM vehiculos WHERE numero_serie = ? AND id_vehiculo != ?");
        $stmt->bind_param("si", $nuevaSerie, $id_vehiculo);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            echo "<script>alert('El número de serie ya está en uso por otro vehículo.'); history.back();</script>";
            exit;
        }

        // Actualizar el vehículo
        $stmt = $conn->prepare("UPDATE vehiculos SET placa = ?, tipo = ?, numero_serie = ?, Sucursal = ?, razon_social = ?, Km_Actual = ?, Km_Total = ?, Km_de_Servicio = ? WHERE id_vehiculo = ?");
        $stmt->bind_param("sssssiiii", $nuevaPlaca, $nuevoTipo, $nuevaSerie, $nuevaSucursal, $nuevaRazon, $nuevoKmActual, $nuevoKmTotal, $nuevoKmServicio, $id_vehiculo);
        $stmt->execute();

        // Registrar el cambio si la sucursal cambió
        if ($nuevaSucursal !== suc_norm($vehiculo['Sucursal'] ?? '')) {
            $sucursalAnterior = suc_norm($vehiculo['Sucursal'] ?? '');
            $usuario = $_SESSION['username'] ?? 'Sistema';
            $stmt = $conn->prepare("INSERT INTO historial_sucursal (id_vehiculo, sucursal_anterior, sucursal_nueva, fecha, usuario) VALUES (?, ?, ?, NOW(), ?)");
            $stmt->bind_param("isss", $id_vehiculo, $sucursalAnterior, $nuevaSucursal, $usuario);
            $stmt->execute();
        }

        header("Location: detalles_vehiculo.php?id={$id_vehiculo}&msg=vehiculo-actualizado");
        exit;
    } else {
        echo "<script>alert('Todos los campos son obligatorios.'); history.back();</script>";
        exit;
    }
}


?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Detalles del Vehículo</title>
    <link rel="icon" type="image/png" href="/Pedidos_GA/Img/Botones%20entregas/ICONOSPAG/ICONOPEDIDOS.png">
    <style>
        :root {
            --brand: #005aa3;
            --brand-2: #ed6b1f;
            --stroke: #e6e8ee;
            --surface: #fff;
            --bg: #f5f7fb;
            --text: #0f172a;
            --muted: #64748b;
            --radius: 16px;
            --shadow: 0 10px 30px rgba(0, 0, 0, .08);
        }

        * {
            box-sizing: border-box
        }

        body {
            background: var(--bg);
            margin: 0;
            font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
            color: var(--text)
        }

        header.header {
            background: #075a9e;
            color: #fff;
            padding: 14px 18px
        }

        header .logo {
            font-weight: 900;
            letter-spacing: .5px
        }

        /* Card principal */
        .veh-card {
            max-width: 98%;
            margin: 16px auto;
            background: var(--surface);
            border: 1px solid var(--stroke);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden
        }

        .veh-hero {
            height: 180px;
            background: linear-gradient(135deg, #0a66c290, #0f172a)
        }

        @media(max-width:600px) {
            .veh-hero {
                height: 140px
            }
        }

        .veh-header {
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 18px;
            padding: 0 18px 18px;
            align-items: end
        }

        @media(max-width:960px) {
            .veh-header {
                grid-template-columns: auto 1fr
            }

            .veh-actions-row {
                grid-column: 1/-1
            }
        }

        .veh-avatar {
            width: 110px;
            height: 110px;
            border-radius: 999px;
            overflow: hidden;
            border: 5px solid #fff;
            margin-top: -55px;
            background: #e2e8f0;
            box-shadow: 0 8px 24px rgba(15, 23, 42, .22);
            cursor: pointer;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center
        }

        .veh-avatar-letra {
            font-size: 48px;
            font-weight: 900;
            color: #005aa3;
            user-select: none;
            pointer-events: none
        }

        .veh-avatar::after {
            content: "Cambiar";
            position: absolute;
            inset: auto 0 0 0;
            background: rgba(0, 0, 0, .45);
            color: #fff;
            font-size: 12px;
            text-align: center;
            padding: 4px 0;
            opacity: 0;
            transition: .15s
        }

        .veh-avatar:hover::after {
            opacity: 1
        }

        .veh-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block
        }

        .veh-head-main {
            display: flex;
            flex-direction: column;
            gap: 6px
        }

        .veh-title {
            margin: 0;
            font-size: 22px;
            font-weight: 800
        }

        .veh-sub {
            font-size: 13px;
            color: #475569
        }

        .veh-pills {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 4px
        }

        .pill {
            font-size: 12px;
            padding: 6px 10px;
            border-radius: 999px;
            background: #f8fafc;
            color: #0f172a;
            border: 1px solid #e5e7eb;
            font-weight: 700
        }

        .pill-ok {
            background: #e7f9ef;
            border-color: #c6f1d7;
            color: #166534
        }

        .pill-warn {
            background: #fff6d6;
            border-color: #fde9a8;
            color: #8a6d00
        }

        .pill-danger {
            background: #ffe4e6;
            border-color: #fecdd3;
            color: #9f1239
        }

        .pill-info {
            background: #e0f2fe;
            border-color: #bae6fd;
            color: #075985
        }

        .pill-muted {
            background: #f1f5f9;
            color: #475569
        }

        .veh-actions-row {
            display: flex;
            gap: 8px;
            align-items: center;
            justify-content: flex-end
        }

        .btn {
            background: var(--brand);
            color: #fff;
            border: none;
            border-radius: 12px;
            padding: 10px 14px;
            font-weight: 700;
            cursor: pointer
        }

        .btn:hover {
            filter: brightness(1.05)
        }

        .btn.alt {
            background: var(--brand-2)
        }

        .btn.ghost {
            background: #eef2f7;
            color: #0f172a;
            border: 1px solid #d8dee9
        }

        .veh-metrics {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 10px;
            padding: 0 18px 14px
        }

        @media(max-width:1200px) {
            .veh-metrics {
                grid-template-columns: repeat(3, minmax(0, 1fr))
            }
        }

        @media(max-width:960px) {
            .veh-metrics {
                grid-template-columns: repeat(2, 1fr)
            }
        }

        @media(max-width:600px) {
            .veh-metrics {
                grid-template-columns: 1fr
            }
        }

        .metric {
            background: #f8fafc;
            border: 1px solid #eef2f7;
            border-radius: 12px;
            padding: 12px;
            text-align: center
        }

        .m-title {
            font-size: .82rem;
            color: var(--muted)
        }

        .m-value {
            font-size: 20px;
            font-weight: 800
        }

        /* Tabs + panes */
        .veh-tabs {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            padding: 0 18px 16px
        }

        .veh-tabs .tab {
            display: block;
            text-align: center;
            background: #f1f5f9;
            color: #0f172a;
            padding: 10px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 800;
            border: 1px solid #e2e8f0
        }

        .veh-tabs .tab.active {
            background: #0a66c2;
            color: #fff;
            border-color: #0a66c2
        }

        .tab-pane {
            max-width: 92%;
            margin: 0 auto 22px;
            background: #fff;
            border: 1px solid #e8ecf2;
            border-radius: 14px;
            box-shadow: 0 6px 16px rgba(0, 0, 0, .04);
            padding: 16px
        }

        /* Acordeón simple con <details> */
        .acc,
        .acc-sub { margin: 8px 0; }
        .acc > summary,
        .acc-sub > summary {
            list-style: none;
            cursor: pointer;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 10px 12px;
            font-weight: 800;
            color: #0f172a;
        }
        .acc > summary::marker,
        .acc-sub > summary::marker { display: none; }
        .acc[open] > summary { background: #eaf3ff; border-color: #cfe4ff; }
        .acc-sub[open] > summary { background: #f7fbff; }

        /* Tabla */
        .mi-tabla {
            width: 100%;
            border-collapse: collapse
        }

        .mi-tabla th,
        .mi-tabla td {
            border: 1px solid #e5e7eb;
            padding: 8px;
            font-size: 14px
        }

        .mi-tabla th {
            background: #005aa3;
            color: #fff
        }

        /* ===== MODALES ===== */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, .45);
            backdrop-filter: blur(2px);
            opacity: 0;
            pointer-events: none;
            transition: opacity .2s;
            z-index: 999
        }

        .modal-overlay.open {
            opacity: 1;
            pointer-events: auto
        }

        .modal {
            position: fixed;
            inset: 0;
            display: grid;
            place-items: center;
            opacity: 0;
            pointer-events: none;
            transition: opacity .2s;
            z-index: 1000
        }

        .modal.open {
            opacity: 1;
            pointer-events: auto
        }

        .modal__card {
            width: min(560px, 92vw);
            background: #fff;
            border: 1px solid #e6e8ee;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(15, 23, 42, .18);
            overflow: hidden;
            transform: translateY(6px) scale(.98);
            animation: modal-pop .18s ease forwards
        }

        @keyframes modal-pop {
            to {
                transform: translateY(0) scale(1)
            }
        }

        .modal__head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 16px;
            background: linear-gradient(135deg, #0a66c2, #0f345a);
            color: #fff
        }

        .modal__title {
            margin: 0;
            font-size: 18px;
            font-weight: 800
        }

        .modal__close {
            appearance: none;
            border: 0;
            background: transparent;
            color: #fff;
            font-size: 22px;
            line-height: 1;
            cursor: pointer;
            padding: 0 6px;
            opacity: .95
        }

        .modal__close:hover {
            opacity: 1;
            transform: scale(1.08)
        }

        .modal__body {
            padding: 16px;
            display: grid;
            gap: 10px
        }

        .modal__row {
            display: grid;
            gap: 8px
        }

        .modal__row.inline {
            grid-template-columns: 1fr 1fr;
            gap: 12px
        }

        @media(max-width:520px) {
            .modal__row.inline {
                grid-template-columns: 1fr
            }
        }

        .modal__label {
            font-size: 13px;
            color: #475569;
            font-weight: 700
        }

        .modal__field {
            width: 100%;
            border: 1px solid #dbe2ea;
            border-radius: 10px;
            padding: 10px 12px;
            font-size: 14px;
            outline: none;
            background: #fff;
            transition: border-color .15s, box-shadow .15s
        }

        .modal__field:focus {
            border-color: #0a66c2;
            box-shadow: 0 0 0 3px rgba(10, 102, 194, .16)
        }

        .modal__actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            padding: 12px 16px;
            background: #f7f9fc;
            border-top: 1px solid #eef2f7
        }

        .btn--ghost {
            background: #eef2f7;
            color: #0f172a;
            border: 1px solid #d8dee9;
            border-radius: 10px;
            padding: 10px 14px;
            font-weight: 700;
            cursor: pointer
        }

        .btn--ghost:hover {
            filter: brightness(1.03)
        }

        .btn--primary {
            background: #005aa3;
            color: #fff;
            border: 0;
            border-radius: 10px;
            padding: 10px 14px;
            font-weight: 800;
            cursor: pointer
        }

        .btn--primary:hover {
            filter: brightness(1.05)
        }

        .btn--warn {
            background: #ed6b1f
        }

        .checkbox-cuadrado {
            appearance: none;
            -webkit-appearance: none;
            width: 18px;
            height: 18px;
            border: 2px solid #ddd;
            border-radius: 4px;
            background: #fff;
            cursor: pointer;
            position: relative
        }

        .checkbox-cuadrado:checked {
            background: #005996;
            border-color: #005996
        }

        .checkbox-cuadrado:checked::after {
            content: '';
            position: absolute;
            top: 2px;
            left: 5px;
            width: 4px;
            height: 8px;
            border: solid #fff;
            border-width: 0 2px 2px 0;
            transform: rotate(45deg)
        }

        .btn-back-global {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: 1px solid var(--stroke);
            background: #e2e8f0;
            color: #0f172a;
            padding: 8px 14px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 700;
            font-size: .9rem;
            transition: background .2s ease, transform .1s ease;
            text-decoration: none;
        }

        .btn-back-global:hover {
            background: #dbe4f0;
        }

        .btn-back-global:active {
            transform: translateY(1px);
        }
    </style>
</head>

<body>

    <header class="header">
        <div class="logo">DETALLES DEL VEHÍCULO</div>
        <div style="margin:10px 0;">
            <button class="btn-back-global" onclick="location.href='vehiculos.php'">⬅ Volver</button>





        </div>

    </header>

    <!-- Card perfil -->
    <section class="veh-card">
        <div class="veh-hero"></div>

        <div class="veh-header">
            <!-- Avatar clickeable -->
            <div class="veh-avatar" id="veh-avatar" title="Clic para cambiar foto">
                <?php if ($tieneFoto): ?>
                    <img id="veh-img" src="<?= htmlspecialchars($vehImg) ?>" alt="Vehículo">
                <?php else: ?>
                    <div class="veh-avatar-letra" id="veh-letra"><?= htmlspecialchars($inicialPlaca) ?></div>
                    <img id="veh-img" src="<?= htmlspecialchars($vehImg) ?>" alt="Vehículo" style="display:none">
                <?php endif; ?>
            </div>

            <!-- Form oculto para subir -->
            <form id="form-foto-veh" action="subir_foto_vehiculo.php" method="post" enctype="multipart/form-data" style="display:none">
                <?php $_SESSION['csrf_veh'] = bin2hex(random_bytes(16)); ?>
                <input type="hidden" name="csrf" value="<?= $_SESSION['csrf_veh'] ?>">
                <input type="hidden" name="id_vehiculo" value="<?= (int)$id_vehiculo ?>">
                <input type="file" name="foto" id="veh-file" accept="image/*">
            </form>

            <div class="veh-head-main">
                <h1 class="veh-title"><?= htmlspecialchars($placa ?: 'Vehículo') ?></h1>
                <div class="veh-sub">
                    Sucursal: <strong><?= htmlspecialchars($sucV ?: '—') ?></strong> ·
                    Tipo: <strong><?= htmlspecialchars($tipoV ?: '—') ?></strong> ·
                    ID: <strong><?= (int)$id_vehiculo ?></strong>
                </div>
                <div class="veh-pills">
                    <span class="pill <?= $estadoSrvClass ?>"><?= $estadoSrv ?></span>
                    <?php if ($serie): ?><span class="pill">Serie: <?= htmlspecialchars($serie) ?></span><?php endif; ?>
                    <?php if ($choferNombre): ?><span class="pill pill-info">Chofer: <?= htmlspecialchars($choferNombre) ?></span>
                    <?php else: ?><span class="pill pill-muted">Sin chofer asignado</span><?php endif; ?>
                </div>
            </div>

            <div class="veh-actions-row">
                <?php if ($choferTel): ?>
                    <a class="btn ghost" href="tel:<?= htmlspecialchars(preg_replace('/\D+/', '', $choferTel)) ?>">Llamar</a>
                    <a class="btn ghost" target="_blank" rel="noopener" href="https://wa.me/<?= htmlspecialchars(preg_replace('/\D+/', '', $choferTel)) ?>">WhatsApp</a>
                <?php endif; ?>
                <button class="btn" onclick="abrirModal()">Registrar Km</button>
                <?php if ($rol === 'Admin'): ?><button class="btn" onclick="abrirModalServicio()">Registrar Servicio</button><?php endif; ?>
                <button class="btn alt" onclick="abrirModalGasolina()">Registrar Gasolina</button>
                <?php if ($rol === 'Admin'): ?><button class="btn ghost" onclick="abrirModalEditarVehiculo()">✏️ Editar Vehículo</button><?php endif; ?>
            </div>
        </div>

        <div class="veh-metrics">
            <div class="metric">
                <div class="m-title">Placa</div>
                <div class="m-value"><?= htmlspecialchars($placa ?: '—') ?></div>
            </div>
            <div class="metric">
                <div class="m-title">Sucursal</div>
                <div class="m-value"><?= htmlspecialchars($sucV ?: '—') ?></div>
            </div>
            <div class="metric">
                <div class="m-title">Km actual sin servicio</div>
                <div class="m-value"><?= number_format($kmAct) ?></div>
            </div>
            <div class="metric">
                <div class="m-title">Km total del vehiculo</div>
                <div class="m-value"><?= number_format($kmTot) ?></div>
            </div>
            <div class="metric">
                <div class="m-title">Km restante para Próximo servicio</div>
                <div class="m-value"><?= number_format($proxSrv) ?> km</div>
            </div>
        </div>

        <div class="veh-tabs">
            <a href="#info" class="tab active" data-tab="info">Información</a>
            <a href="#hist" class="tab" data-tab="hist">Historial de kilometraje</a>
            <a href="#cond" class="tab" data-tab="cond">Historial de Conductores</a> <!-- NUEVO -->
            <a href="#obs" class="tab" data-tab="obs">Observaciones</a>

            <a href="#gas" class="tab" data-tab="gas">Gasolina</a>
            <!--<a href="#ext" class="tab" data-tab="ext">MAPA GPS</a>
                -->   <a href="#serv" class="tab" data-tab="serv">Servicios</a>

        </div>

        <!-- Cambiar Km de Servicio -->
        <div style="margin-top:12px; padding:12px; border:1px dashed #e1e7ef; border-radius:10px; background:#f9fbff">
            <form method="post" style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
                <input type="hidden" name="accion" value="cambiar_km_servicio">
                <strong>Km para próximo servicio:</strong>
                <input type="number" name="km_de_servicio" class="modal__field" style="min-width:180px" min="1" required value="<?= (int)$kmSrv ?>">
                <button type="submit" class="btn">Guardar</button>
                <span style="color:#64748b; font-size:.9rem">Define el kilometraje objetivo al que le toca servicio.</span>
            </form>
        </div>

    </section>

    <!-- Panes -->
    <section id="pane-info" class="tab-pane">
                  <!-- Resumen rápido de últimas actividades -->
          <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:12px;margin-bottom:16px;">
              <!-- Historial de kilometraje -->
              <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:10px 12px;">
                  <div style="font-weight:600;color:#0f172a;font-size:.95rem;margin-bottom:6px;">Últimos kilometrajes</div>
                  <?php if ($resumen_km && $resumen_km->num_rows > 0): ?>
                      <?php while ($rk = $resumen_km->fetch_assoc()): ?>
                          <div style="font-size:.85rem;color:#475569;margin-bottom:4px;">
                              <strong><?= htmlspecialchars(date('d/m/Y', strtotime($rk['fecha_registro']))) ?>:</strong>
                              <?= number_format((int)$rk['kilometraje_inicial']) ?> →
                              <?= number_format((int)$rk['kilometraje_final']) ?> km
                          </div>
                      <?php endwhile; ?>
                  <?php else: ?>
                      <div style="font-size:.8rem;color:#94a3b8;">Sin registros de kilometraje.</div>
                  <?php endif; ?>
              </div>

              <!-- Historial de conductores -->
              <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:10px 12px;">
                  <div style="font-weight:600;color:#0f172a;font-size:.95rem;margin-bottom:6px;">Últimos conductores</div>
                  <?php if ($resumen_conductores && $resumen_conductores->num_rows > 0): ?>
                      <?php while ($rc = $resumen_conductores->fetch_assoc()): ?>
                          <div style="font-size:.85rem;color:#475569;margin-bottom:4px;">
                              <strong><?= htmlspecialchars($rc['chofer_nombre']) ?></strong>
                              <span>
                                  (<?= htmlspecialchars(date('d/m/Y', strtotime($rc['fecha_inicio']))) ?>
                                  <?php if (!empty($rc['fecha_fin'])): ?>
                                      – <?= htmlspecialchars(date('d/m/Y', strtotime($rc['fecha_fin']))) ?>
                                  <?php else: ?>
                                      – actual
                                  <?php endif; ?>)
                              </span>
                          </div>
                      <?php endwhile; ?>
                  <?php else: ?>
                      <div style="font-size:.8rem;color:#94a3b8;">Sin historial de conductores.</div>
                  <?php endif; ?>
              </div>

              <!-- Observaciones -->
              <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:10px 12px;">
                  <div style="font-weight:600;color:#0f172a;font-size:.95rem;margin-bottom:6px;">Últimas observaciones</div>
                  <?php if ($resumen_obs && $resumen_obs->num_rows > 0): ?>
                      <?php while ($ro = $resumen_obs->fetch_assoc()): ?>
                          <div style="font-size:.85rem;color:#475569;margin-bottom:4px;">
                              <strong><?= htmlspecialchars(date('d/m/Y', strtotime($ro['fecha_inspeccion']))) ?></strong>
                              – <?= htmlspecialchars($ro['seccion']) ?> / <?= htmlspecialchars($ro['item']) ?>
                              (<?= htmlspecialchars($ro['calificacion']) ?>)
                          </div>
                      <?php endwhile; ?>
                  <?php else: ?>
                      <div style="font-size:.8rem;color:#94a3b8;">Sin observaciones recientes.</div>
                  <?php endif; ?>
              </div>

              <!-- Gasolina -->
              <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:10px 12px;">
                  <div style="font-weight:600;color:#0f172a;font-size:.95rem;margin-bottom:6px;">Última gasolina semanal</div>
                  <?php if ($resumen_gas && $resumen_gas->num_rows > 0): ?>
                      <?php while ($rg = $resumen_gas->fetch_assoc()): ?>
                          <div style="font-size:.85rem;color:#475569;margin-bottom:4px;">
                              <strong><?= htmlspecialchars(date('d/m/Y', strtotime($rg['fecha_registro']))) ?></strong>
                              – Semana <?= (int)$rg['semana'] ?>/<?= (int)$rg['anio'] ?>:
                              $<?= number_format((float)$rg['importe'], 2) ?>
                              <?php if (!empty($rg['observaciones'])): ?>
                                  <div style="font-size:.8rem;color:#64748b;">
                                      <?= htmlspecialchars($rg['observaciones']) ?>
                                  </div>
                              <?php endif; ?>
                          </div>
                      <?php endwhile; ?>
                  <?php else: ?>
                      <div style="font-size:.8rem;color:#94a3b8;">Sin registros de gasolina.</div>
                  <?php endif; ?>
              </div>

              <!-- Servicios -->
              <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:10px 12px;">
                  <div style="font-weight:600;color:#0f172a;font-size:.95rem;margin-bottom:6px;">Últimos servicios</div>
                  <?php if ($resumen_servicios && $resumen_servicios->num_rows > 0): ?>
                      <?php while ($rs = $resumen_servicios->fetch_assoc()): ?>
                          <div style="font-size:.85rem;color:#475569;margin-bottom:4px;">
                              <strong><?= htmlspecialchars($rs['nombre_servicio'] ?? 'Sin servicio') ?></strong>
                              – <?= htmlspecialchars(date('d/m/Y', strtotime($rs['creado_en']))) ?>
                              <?php if (!empty($rs['estatus'])): ?>
                                  <span style="font-size:.8rem;color:#0f766e;">(<?= htmlspecialchars($rs['estatus']) ?>)</span>
                              <?php endif; ?>
                          </div>
                      <?php endwhile; ?>
                  <?php else: ?>
                      <div style="font-size:.8rem;color:#94a3b8;">Sin servicios registrados.</div>
                  <?php endif; ?>
              </div>
          </div>

        <table class="mi-tabla">
            <tr>
                <th>Placa</th>
                <th>Tipo</th>
                <th>Sucursal</th>
                <th>Kilometraje actual</th>
                <th>Kilometraje total</th>
                <th>Último servicio</th>
            </tr>
            <tr>
                <td><?= htmlspecialchars($placa ?: '—') ?></td>
                
                <td><?= htmlspecialchars($tipoV ?: '—') ?></td>
                <td><?= htmlspecialchars($sucV  ?: '—') ?></td>
                <td><?= number_format($kmAct) ?></td>
                <td><?= number_format($kmTot) ?></td>
                <td><?= htmlspecialchars($ultServ) ?></td>
            </tr>
        </table>

        <div style="margin-top:12px; padding:12px; border:1px dashed #e1e7ef; border-radius:10px; background:#f9fbff">
            <form method="post" style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
                <input type="hidden" name="accion" value="cambiar_sucursal">
                <strong>Cambiar sucursal:</strong>
                <select name="sucursal_nueva" class="modal__field" style="min-width:220px">
                    <?php foreach ($SUCURSALES_CANON as $S):
                        $sel = (suc_norm($sucV) === $S) ? 'selected' : ''; ?>
                        <option value="<?= htmlspecialchars($S) ?>" <?= $sel ?>><?= htmlspecialchars($S) ?></option>
                    <?php endforeach; ?>
                </select>

                <button type="submit" class="btn">Guardar</button>
            </form>

            <?php if ($hist_sucursal && $hist_sucursal->num_rows): ?>
                <div style="margin-top:10px">
                    <details>
                        <summary style="cursor:pointer;font-weight:700;color:#0a66c2">Ver historial de cambios de sucursal</summary>
                        <table class="mi-tabla" style="margin-top:8px">
                            <tr>
                                <th>Fecha</th>
                                <th>Anterior</th>
                                <th>Nueva</th>
                                <th>Usuario</th>
                            </tr>
                            <?php while ($hs = $hist_sucursal->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($hs['fecha']) ?></td>
                                    <td><?= htmlspecialchars($hs['sucursal_anterior']) ?></td>
                                    <td><?= htmlspecialchars($hs['sucursal_nueva']) ?></td>
                                    <td><?= htmlspecialchars($hs['usuario'] ?? '—') ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </table>
                    </details>
                </div>
            <?php endif; ?>
        </div>

    </section>
   <section id="pane-cond" class="tab-pane" style="display:none">
  <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:8px">
    <h3 style="margin:0">Historial de conductores</h3>

    <?php
    $esParticular = (int)($vehiculo['es_particular'] ?? 0);
    if ($esParticular === 1):
      // Para vehículos particulares, mostrar formulario de responsable
    ?>
      <div style="margin-bottom:12px;padding:10px 14px;background:#f0f9ff;border:1px solid #bae6fd;border-radius:8px;color:#0c4a6e;font-weight:600;">
        🏠 Vehículo particular - Asignar responsable
      </div>

      <!-- Form ASIGNAR RESPONSABLE -->
      <form method="post" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
        <input type="hidden" name="accion" value="asignar_responsable">
        <label class="modal__label" for="inputResponsable" style="margin:0">Responsable</label>
        <input
          type="text"
          name="responsable"
          id="inputResponsable"
          class="modal__field"
          style="min-width:300px"
          placeholder="Selecciona o escribe el nombre..."
          list="listaUsuarios"
          value="<?= htmlspecialchars($vehiculo['responsable'] ?? '') ?>"
          required
          autocomplete="off">
        <datalist id="listaUsuarios">
          <?php
          $qUsuarios = "SELECT username, Rol, Sucursal FROM usuarios ORDER BY username";
          $usuarios = $conn->query($qUsuarios);
          if ($usuarios) {
            while ($usr = $usuarios->fetch_assoc()):
              $display = htmlspecialchars($usr['username']);
              $rol = htmlspecialchars($usr['Rol'] ?? '');
              $suc = htmlspecialchars($usr['Sucursal'] ?? '');
              $label = $display;
              if ($rol || $suc) {
                $label .= " — {$rol}" . ($suc ? " ({$suc})" : "");
              }
          ?>
            <option value="<?= $display ?>" label="<?= $label ?>">
          <?php
            endwhile;
          }
          ?>
        </datalist>
        <button class="btn alt" type="submit">Guardar</button>

        <?php if (!empty($vehiculo['responsable'])): ?>
          </form>
          <form method="post" style="display:inline;" onsubmit="return confirm('¿Desasignar responsable actual?');">
            <input type="hidden" name="accion" value="desasignar_responsable">
            <button class="btn ghost" type="submit">Quitar responsable</button>
          </form>
        <?php else: ?>
          </form>
        <?php endif; ?>
    <?php else: ?>
      <!-- Form ASIGNAR -->
      <form method="post" style="display:flex;gap:8px;align-items:center">
        <input type="hidden" name="accion" value="asignar_chofer">
        <label class="modal__label" for="selChoferNuevo" style="margin:0">Asignar chofer</label>
        <select name="id_chofer_nuevo" id="selChoferNuevo" class="modal__field" required>
          <?php
          $sucVeh = $conn->real_escape_string($sucV);
          $q = "SELECT ID, username, Sucursal FROM choferes WHERE Estado='ACTIVO'
                ORDER BY (Sucursal='{$sucVeh}') DESC, Sucursal, username";
          $chs2 = $conn->query($q);
          while ($ch = $chs2->fetch_assoc()):
          ?>
            <option value="<?= (int)$ch['ID'] ?>">
              <?= htmlspecialchars($ch['username']) ?> — <?= htmlspecialchars($ch['Sucursal']) ?>
            </option>
          <?php endwhile; ?>
        </select>
        <button class="btn alt" type="submit">Guardar</button>
      </form>

      <!-- Form DESASIGNAR (separado) -->
      <?php if (!empty($vehiculo['id_chofer_asignado'])): ?>
        <form method="post" onsubmit="return confirm('¿Desasignar chofer actual?');">
          <input type="hidden" name="accion" value="desasignar_chofer">
          <button class="btn ghost" type="submit">Desasignar chofer</button>
        </form>
      <?php endif; ?>
    <?php endif; ?>
  </div>
<?php
// helper para minutos → "xh ym"
function fmtDuracion($min){
  $h = intdiv((int)$min, 60);
  $m = (int)$min % 60;
  if ($h <= 0) return "{$m} min";
  return $m > 0 ? "{$h} h {$m} min" : "{$h} h";
}
?>
<table class="mi-tabla">
  <?php if ($esParticular === 1): ?>
    <!-- Historial de responsables para vehículos particulares -->
    <tr>
      <th>Responsable</th>
      <th>Desde</th>
      <th>Hasta</th>
      <th>Duración</th>
      <th>Estado</th>
    </tr>
    <?php if ($hist_responsables && $hist_responsables->num_rows): ?>
      <?php while ($hr = $hist_responsables->fetch_assoc()):
        $abierto = is_null($hr['fecha_fin']);
        $desde   = date('d/m/Y H:i', strtotime($hr['fecha_inicio']));
        $hasta   = $abierto ? '—' : date('d/m/Y H:i', strtotime($hr['fecha_fin']));
        $dur     = fmtDuracion($hr['minutos_total']);
      ?>
        <tr>
          <td><?= htmlspecialchars($hr['nombre_responsable']) ?></td>
          <td><?= $desde ?></td>
          <td><?= $hasta ?></td>
          <td><?= $dur ?></td>
          <td><?= $abierto ? '<span class="pill pill-info">Actual</span>' : 'Cerrado' ?></td>
        </tr>
      <?php endwhile; ?>
    <?php else: ?>
      <tr><td colspan="5" style="text-align:center;color:#64748b">Sin registros aún</td></tr>
    <?php endif; ?>
  <?php else: ?>
    <!-- Historial de conductores para vehículos normales -->
    <tr>
      <th>Chofer</th>
      <th>Sucursal</th>
      <th>Desde</th>
      <th>Hasta</th>
      <th>Duración</th>
      <th>Estado</th>
    </tr>
    <?php if ($hist_conductores && $hist_conductores->num_rows): ?>
      <?php while ($hc = $hist_conductores->fetch_assoc()):
        $abierto = is_null($hc['fecha_fin']);
        $desde   = date('d/m/Y H:i', strtotime($hc['fecha_inicio']));
        $hasta   = $abierto ? '—' : date('d/m/Y H:i', strtotime($hc['fecha_fin']));
        $dur     = fmtDuracion($hc['minutos_total']);
      ?>
        <tr>
          <td><?= htmlspecialchars($hc['chofer_nombre']) ?></td>
          <td><?= htmlspecialchars($hc['sucursal_chofer'] ?? '—') ?></td>
          <td><?= $desde ?></td>
          <td><?= $hasta ?></td>
          <td><?= $dur ?></td>
          <td><?= $abierto ? '<span class="pill pill-info">Actual</span>' : 'Cerrado' ?></td>
        </tr>
      <?php endwhile; ?>
    <?php else: ?>
      <tr><td colspan="6" style="text-align:center;color:#64748b">Sin registros aún</td></tr>
    <?php endif; ?>
  <?php endif; ?>
</table>

</section>


    <section id="pane-hist" class="tab-pane" style="display:none">
        <table class="mi-tabla">
            <tr>
                <th>Fecha</th>
                <th>Chofer</th>
                <th>Tipo</th>
                <th>Kilometraje inicial</th>
                <th>Kilometraje final</th>
            </tr>
            <?php while ($r = $historial->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($r['fecha_registro']) ?></td>
                    <td><?= htmlspecialchars($r['chofer']) ?></td>
                    <td>
                        <?php if (($r['Tipo_Registro'] ?? '') === 'Servicio'): ?>
                            <a href="Detalle_Servicio.php?id=<?= (int)$r['id_registro'] ?>">Servicio</a>
                            <?php else: ?>Registro<?php endif; ?>
                    </td>
                    <td><?= (int)$r['kilometraje_inicial'] ?></td>
                    <td><?= (int)$r['kilometraje_final'] ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
    </section>

    <section id="pane-obs" class="tab-pane" style="display:none">
        <?php if (empty($obs_group)): ?>
            <div style="padding:8px 4px;color:#64748b">Sin registros de checklist.</div>
        <?php else: $i=0; foreach ($obs_group as $fecha => $data): $i++; $kmG = isset($data['km']) ? (int)$data['km'] : 0; ?>
            <details class="acc">
                <summary>Checklist <?= htmlspecialchars($fecha) ?> — Kilometraje: <?= number_format($kmG) ?> km</summary>
                <?php foreach ($data['secciones'] as $sec => $items): $cnt = count($items); ?>
                    <details class="acc-sub">
                        <summary><?= htmlspecialchars($sec) ?> (<?= (int)$cnt ?>)</summary>
                        <table class="mi-tabla">
                            <tr>
                                <th>Ítem</th>
                                <th>Calificación</th>
                                <th>Observaciones</th>
                            </tr>
                            <?php foreach ($items as $cl): ?>
                                <tr>
                                    <td><?= htmlspecialchars($cl['item']) ?></td>
                                    <td><?= htmlspecialchars($cl['calificacion']) ?></td>
                                    <td><?= htmlspecialchars((string)($cl['observaciones_rotulado'] ?? '')) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    </details>
                <?php endforeach; ?>
            </details>
        <?php endforeach; endif; ?>
    </section>

      <section id="pane-gas" class="tab-pane" style="display:none">
          <div style="display:flex;justify-content:flex-end;margin-bottom:8px">
              <button class="btn alt" onclick="abrirModalGasolina()">Agregar carga</button>
          </div>
          <table class="mi-tabla">
              <tr>
                  <th>Fecha registro</th>
                  <th>Año/Semana</th>
                  <th>Importe</th>
                  <th>Observaciones</th>
              </tr>
              <?php while ($g = $historial_gasolina->fetch_assoc()): ?>
                  <tr>
                      <td><?= htmlspecialchars($g['fecha_registro']) ?></td>
                      <td><?= htmlspecialchars($g['anio'] . ' / ' . $g['semana']) ?></td>
                      <td>$<?= number_format((float)$g['importe'], 2) ?></td>
                      <td><?= htmlspecialchars($g['observaciones'] ?? '') ?></td>
                  </tr>
              <?php endwhile; ?>
          </table>
      </section>


    <section id="pane-ext" class="tab-pane" style="display:none">
        <div class="ext-controls" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:10px">
            <input type="text" id="ext-url" class="modal__field"
                style="flex:1;max-width:520px"
                value="https://spot.resser.com/admin/lastPosition"
                placeholder="URL externa">
            <input type="text" id="ext-term" class="modal__field"
                style="width:220px"
                value="<?= htmlspecialchars($placa ?: '') ?>"
                placeholder="Término de búsqueda (ej. placa)">
            <button class="btn" id="ext-copy" type="button">
                Copiar placa para pegar en el buscador del mapa
            </button>
        </div>

        <div class="ext-frame-wrap" style="height:88vh;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;background:#f8fafc">
            <iframe id="ext-iframe" src="" style="width:100%;height:100%;border:0;background:#f8fafc"></iframe>
        </div>

        <p style="margin-top:10px;color:#64748b;font-size:.9rem">
            Tip:Si no carga, ábrelo en ventana nueva.
            <a class="btn ghost" id="ext-open-new" target="_blank" rel="noopener" style="margin-left:8px">Abrir en nueva pestaña</a>
        </p>
    </section>

    <!-- Historial de Servicios -->
    <section id="pane-serv" class="tab-pane" style="display:none">
        <h3 style="margin:0 0 15px 0">Historial de Servicios</h3>

        <?php
        $total_servicios = 0;
        $costo_total = 0;
        $servicios_array = [];

        if ($hist_servicios && $hist_servicios->num_rows > 0) {
            while ($srv = $hist_servicios->fetch_assoc()) {
                $servicios_array[] = $srv;
                $total_servicios++;
                $costo_total += floatval($srv['precio'] ?? 0);
            }
        }
        ?>

        <!-- Resumen -->
        <div style="display:flex;gap:15px;margin-bottom:20px;flex-wrap:wrap">
            <div style="background:#f0f9ff;padding:15px 20px;border-radius:10px;border-left:4px solid #0ea5e9">
                <div style="font-size:0.85rem;color:#64748b">Total de servicios</div>
                <div style="font-size:1.5rem;font-weight:600;color:#0369a1"><?= $total_servicios ?></div>
            </div>
            <div style="background:#f0fdf4;padding:15px 20px;border-radius:10px;border-left:4px solid #22c55e">
                <div style="font-size:0.85rem;color:#64748b">Costo total</div>
                <div style="font-size:1.5rem;font-weight:600;color:#15803d">$<?= number_format($costo_total, 2) ?></div>
            </div>
        </div>

        <?php if (count($servicios_array) > 0): ?>
        <div style="overflow-x:auto">
            <table class="tbl-km" style="width:100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Servicio</th>
                        <th>Duración</th>
                        <th>Costo</th>
                        <th>Estatus</th>
                        <th>Fecha Programada</th>
                        <th>Creado</th>
                        <th>Notas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($servicios_array as $srv): ?>
                    <tr>
                        <td><?= htmlspecialchars($srv['id']) ?></td>
                        <td>
                            <?php if ($srv['nombre_servicio']): ?>
                                <strong><?= htmlspecialchars($srv['nombre_servicio']) ?></strong>
                            <?php else: ?>
                                <span style="color:#94a3b8;font-style:italic">Sin especificar</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($srv['duracion_minutos']): ?>
                                <?= $srv['duracion_minutos'] ?> min
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($srv['precio']): ?>
                                $<?= number_format($srv['precio'], 2) ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $estatus = $srv['estatus'] ?? 'Pendiente';
                            $color = match($estatus) {
                                'Completado' => '#22c55e',
                                'EnTaller' => '#f59e0b',
                                'Programado' => '#3b82f6',
                                'Cancelado' => '#ef4444',
                                default => '#64748b'
                            };
                            ?>
                            <span style="background:<?= $color ?>22;color:<?= $color ?>;padding:4px 8px;border-radius:6px;font-size:0.85rem;font-weight:500">
                                <?= htmlspecialchars($estatus) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($srv['fecha_programada']): ?>
                                <?= date('d/m/Y', strtotime($srv['fecha_programada'])) ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td><?= date('d/m/Y H:i', strtotime($srv['creado_en'])) ?></td>
                        <td style="max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis" title="<?= htmlspecialchars($srv['notas'] ?? '') ?>">
                            <?= htmlspecialchars($srv['notas'] ?? '-') ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div style="text-align:center;padding:40px;color:#64748b;background:#f8fafc;border-radius:10px">
            <svg style="width:48px;height:48px;margin-bottom:10px;opacity:0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
            </svg>
            <p style="margin:0;font-size:1.1rem">No hay servicios registrados para este vehículo</p>
        </div>
        <?php endif; ?>
    </section>




    <!-- ================= MODALES ================= -->
    <!-- Gasolina -->
    <div id="modalGasolina" class="modal" role="dialog" aria-modal="true" aria-labelledby="mg-title">
        <div class="modal__card">
            <div class="modal__head">
                <h3 id="mg-title" class="modal__title">Registrar Carga de Gasolina</h3>
                <button class="modal__close" onclick="cerrarModalGasolina()" aria-label="Cerrar">×</button>
            </div>
            <form method="POST" autocomplete="off">
                <input type="hidden" name="accion" value="registrar_semana_gasolina">
                <div class="modal__body">
                    <div class="modal__row">
                        <label class="modal__label">Vehículo</label>
                        <div class="modal__field" style="background:#e5e7eb;">
                            <?= htmlspecialchars($vehiculo['placa'] ?? '') ?>
                            <?php if (!empty($vehiculo['Sucursal'])): ?>
                                - <?= htmlspecialchars($vehiculo['Sucursal']) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="modal__row inline">
                        <div>
                            <label class="modal__label">Fecha dentro de la semana</label>
                            <input class="modal__field" type="date" name="fecha_semana"
                                   value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div>
                            <label class="modal__label">Importe semanal (MXN)</label>
                            <input class="modal__field" type="number" name="importe"
                                   step="0.01" min="0" placeholder="0.00" required>
                        </div>
                    </div>
                    <div class="modal__row">
                        <label class="modal__label">Observaciones</label>
                        <textarea class="modal__field" name="observaciones"
                                  placeholder="Opcional"></textarea>
                    </div>
                </div>
                <div class="modal__actions">
                    <button type="button" class="btn--ghost" onclick="cerrarModalGasolina()">Cancelar</button>
                    <button type="submit" class="btn--primary btn--warn">Guardar / Actualizar semana</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Kilometraje -->
    <div id="modalRegistro" class="modal" role="dialog" aria-modal="true" aria-labelledby="mr-title">
        <div class="modal__card">
            <div class="modal__head">
                <h3 id="mr-title" class="modal__title">Registrar Kilometraje</h3>
                <button class="modal__close" onclick="cerrarModal()" aria-label="Cerrar">×</button>
            </div>
            <form method="POST">
                <div class="modal__body">
                    <?php $sucursalUsuario = $_SESSION["Sucursal"];
                    $rol = $_SESSION["Rol"]; ?>
                    <div class="modal__row">
                        <label class="modal__label">Sucursal</label>
                        <select id="filtroSucursalReg" onchange="filtrarChoferes('Reg')" class="modal__field" <?= ($rol === 'JC') ? 'disabled' : '' ?>>
                            <?php
                            if ($rol === 'Admin') {
                                echo "<option value=''>Todas</option>";
                                $sucs = $conn->query("SELECT DISTINCT Sucursal FROM choferes");
                                while ($s = $sucs->fetch_assoc()) echo "<option value='{$s['Sucursal']}'>{$s['Sucursal']}</option>";
                            } else {
                                echo "<option value='{$sucursalUsuario}' selected>{$sucursalUsuario}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="modal__row">
                        <label class="modal__label">Chofer</label>
                        <select name="id_chofer" id="listaChoferesReg" class="modal__field" required>
                            <?php
                            $chs = ($rol === 'Admin')
                                ? $conn->query("SELECT * FROM choferes WHERE Estado='ACTIVO'")
                                : $conn->query("SELECT * FROM choferes WHERE Estado='ACTIVO' AND Sucursal='" . $conn->real_escape_string($sucursalUsuario) . "'");
                            while ($ch = $chs->fetch_assoc()) {
                                echo "<option value='{$ch['ID']}' data-sucursal='{$ch['Sucursal']}'>{$ch['username']} - {$ch['Sucursal']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="modal__row inline">
                        <div>
                            <label class="modal__label">Kilometraje inicial</label>
                            <input class="modal__field" type="number" name="kilometraje_inicial" value="<?= (int)$kilometraje_inicial_sugerido ?>" readonly>
                        </div>
                        <div>
                            <label class="modal__label">Kilometraje final</label>
                            <input class="modal__field" type="number" name="kilometraje_final" required>
                        </div>
                    </div>
                </div>
                <div class="modal__actions">
                    <button type="button" class="btn--ghost" onclick="cerrarModal()">Cancelar</button>
                    <button type="submit" class="btn--primary">Registrar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Servicio -->
    <div id="modalServicio" class="modal" role="dialog" aria-modal="true" aria-labelledby="ms-title">
        <div class="modal__card">
            <div class="modal__head">
                <h3 id="ms-title" class="modal__title">Registrar Servicio del Vehículo</h3>
                <button class="modal__close" onclick="cerrarModalServicio()" aria-label="Cerrar">×</button>
            </div>
            <form method="POST" action="RegistrarServicio.php">
                <input type="hidden" name="id_vehiculo" value="<?= $id_vehiculo ?>">
                <div class="modal__body">
                    <div class="modal__row inline">
                        <div>
                            <label class="modal__label">Usuario (ID)</label>
                            <input class="modal__field" type="text" name="id_chofer" value="2" readonly>
                        </div>
                        <div>
                            <label class="modal__label">Fecha de servicio</label>
                            <input class="modal__field" type="date" name="fecha_servicio" required>
                        </div>
                    </div>
                    <div class="modal__row inline">
                        <div>
                            <label class="modal__label">Km inicial</label>
                            <input class="modal__field" type="number" name="kilometraje_inicial" value="<?= (int)$kilometraje_inicial_sugerido ?>" readonly>
                        </div>
                        <div>
                            <label class="modal__label">Km final</label>
                            <input class="modal__field" type="number" name="kilometraje_final" value="<?= (int)$kilometraje_inicial_sugerido ?>" readonly>
                        </div>
                    </div>
                    <div class="modal__row">
                        <label class="modal__label">Tipo de servicio</label>
                        <select class="modal__field" name="tipo_servicio" required>
                            <option value="Afinacion Mayor">Afinación Mayor</option>
                            <option value="Afinacion Menor">Afinación Menor</option>
                            <option value="Suspension">Suspensión</option>
                            <option value="Frenos">Frenos</option>
                            <option value="Sistema Electrico">Sistema Eléctrico</option>
                            <option value="Mecanica General">Mecánica General</option>
                            <option value="Carroceria y Estetico">Carrocería y Estético</option>
                            <option value="Traslados con Proveedores">Traslados con Proveedores</option>
                            <option value="Observaciones">Observaciones</option>
                        </select>
                    </div>
                    <div class="modal__row"><label class="modal__label">Detalles (opcional)</label><input class="modal__field" type="text" name="detalles" placeholder="Detalles adicionales"></div>
                    <div class="modal__row"><label class="modal__label">Observaciones</label><textarea class="modal__field" name="observaciones" rows="4" placeholder="Comentarios adicionales"></textarea></div>
                    <div class="modal__row" style="display:flex;align-items:center;gap:10px;">
                        <input class="checkbox-cuadrado" type="checkbox" name="reiniciar_km" value="1" id="reiniciarKm">
                        <label class="modal__label" for="reiniciarKm" style="margin:0;">Reiniciar contador de Km a 0</label>
                    </div>
                </div>
                <div class="modal__actions">
                    <button type="button" class="btn--ghost" onclick="cerrarModalServicio()">Cancelar</button>
                    <button type="submit" class="btn--primary">Registrar servicio</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Editar Vehículo -->
    <div id="modalEditarVehiculo" class="modal" role="dialog" aria-modal="true" aria-labelledby="mev-title">
        <div class="modal__card">
            <div class="modal__head">
                <h3 id="mev-title" class="modal__title">Editar Información del Vehículo</h3>
                <button class="modal__close" onclick="cerrarModalEditarVehiculo()" aria-label="Cerrar">×</button>
            </div>
            <form method="POST" autocomplete="off">
                <input type="hidden" name="accion" value="editar_vehiculo">
                <div class="modal__body">
                    <div class="modal__row">
                        <label class="modal__label">Placa *</label>
                        <input class="modal__field" type="text" name="placa"
                               value="<?= htmlspecialchars($vehiculo['placa'] ?? '') ?>"
                               required placeholder="Ej: ABC123">
                    </div>
                    <div class="modal__row">
                        <label class="modal__label">Tipo de Vehículo *</label>
                        <input class="modal__field" type="text" name="tipo"
                               value="<?= htmlspecialchars($vehiculo['tipo'] ?? '') ?>"
                               required placeholder="Ej: Camioneta, Auto, Camión, etc.">
                    </div>
                    <div class="modal__row">
                        <label class="modal__label">Número de Serie *</label>
                        <input class="modal__field" type="text" name="numero_serie"
                               value="<?= htmlspecialchars($vehiculo['numero_serie'] ?? '') ?>"
                               required placeholder="Número de serie del vehículo">
                    </div>
                    <div class="modal__row">
                        <label class="modal__label">Sucursal *</label>
                        <select class="modal__field" name="sucursal" required>
                            <?php foreach ($SUCURSALES_CANON as $S):
                                $sel = (suc_norm($sucV) === $S) ? 'selected' : ''; ?>
                                <option value="<?= $S ?>" <?= $sel ?>><?= $S ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="modal__row">
                        <label class="modal__label">Km actual sin servicio *</label>
                        <input class="modal__field" type="number" name="km_actual"
                               min="0" step="1"
                               value="<?= (int)($vehiculo['Km_Actual'] ?? 0) ?>"
                               required placeholder="Kilometraje actual">
                    </div>
                    <div class="modal__row">
                        <label class="modal__label">Km total del vehiculo (histórico) *</label>
                        <input class="modal__field" type="number" name="km_total"
                               min="0" step="1"
                               value="<?= (int)($vehiculo['Km_Total'] ?? 0) ?>" readonly
                               required placeholder="Suma total recorrida">
                    </div>
                    <div class="modal__row">
                        <label class="modal__label">Próximo servicio (km) *</label>
                        <input class="modal__field" type="number" name="km_de_servicio"
                               min="0" step="1"
                               value="<?= (int)($vehiculo['Km_de_Servicio'] ?? 0) ?>"
                               required placeholder="Km al que toca servicio">
                    </div>
                    <div class="modal__row">
                        <label class="modal__label">Razón Social (opcional)</label>
                        <input class="modal__field" type="text" name="razon_social"
                               value="<?= htmlspecialchars($vehiculo['razon_social'] ?? '') ?>"
                               placeholder="Para vehículos particulares">
                    </div>
                    <p style="font-size: 0.9rem; color: #64748b; margin: 8px 0 0 0;">
                        * Campos obligatorios
                    </p>
                </div>
                <div class="modal__actions">
                    <button type="button" class="btn--ghost" onclick="cerrarModalEditarVehiculo()">Cancelar</button>
                    <button type="submit" class="btn--primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Overlay global -->
    <script>
        // Overlay único
        const overlay = document.createElement('div');
        overlay.className = 'modal-overlay';
        overlay.id = 'modalOverlay';
        document.body.appendChild(overlay);
        overlay.addEventListener('click', closeAllModals);

        // Helpers modales (tus funciones originales)
        function openModal(id) {
            document.getElementById(id)?.classList.add('open');
            overlay.classList.add('open');
        }

        function closeModal(id) {
            document.getElementById(id)?.classList.remove('open');
            if (!document.querySelector('.modal.open')) overlay.classList.remove('open');
        }

        function closeAllModals() {
            document.querySelectorAll('.modal.open').forEach(m => m.classList.remove('open'));
            overlay.classList.remove('open');
        }

        function abrirModal() {
            openModal('modalRegistro');
        }

        function cerrarModal() {
            closeModal('modalRegistro');
        }

        function abrirModalServicio() {
            openModal('modalServicio');
        }

        function cerrarModalServicio() {
            closeModal('modalServicio');
        }

        function abrirModalGasolina() {
            openModal('modalGasolina');
        }

        function cerrarModalGasolina() {
            closeModal('modalGasolina');
        }

        function abrirModalEditarVehiculo() {
            openModal('modalEditarVehiculo');
        }

        function cerrarModalEditarVehiculo() {
            closeModal('modalEditarVehiculo');
        }

        // Tabs (tus tabs)
                // Tabs (tus tabs)
        const tabs = document.querySelectorAll('.veh-tabs .tab');
        const panes = {
            info: document.getElementById('pane-info'),
            hist: document.getElementById('pane-hist'),
            cond: document.getElementById('pane-cond'),
            obs: document.getElementById('pane-obs'),
            gas: document.getElementById('pane-gas'),
            ext: document.getElementById('pane-ext'),
            serv: document.getElementById('pane-serv'),
        };
        tabs.forEach(btn => {
            btn.addEventListener('click', e => {
                e.preventDefault();
                tabs.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                const k = btn.dataset.tab;
                Object.keys(panes).forEach(id => panes[id].style.display = (id === k) ? 'block' : 'none');
                panes[k].scrollIntoView({ behavior: 'smooth', block: 'start' });
                
            });
        });

        // Filtrado choferes (igual)
        function filtrarChoferes(ctx) {
            const selSuc = document.getElementById('filtroSucursal' + ctx);
            const selCh = document.getElementById('listaChoferes' + ctx);
            if (!selSuc || !selCh) return;
            const suc = (selSuc.value || '').toLowerCase();
            Array.from(selCh.options).forEach(op => {
                const sco = (op.getAttribute('data-sucursal') || '').toLowerCase();
                op.style.display = (!suc || sco === suc) ? '' : 'none';
            });
            const firstVisible = Array.from(selCh.options).find(op => op.style.display !== 'none');
            if (firstVisible) firstVisible.selected = true;
        }

        // Subida de foto (igual)
        const avatar = document.getElementById('veh-avatar');
        const img = document.getElementById('veh-img');
        const letra = document.getElementById('veh-letra');
        const fileInp = document.getElementById('veh-file');
        const form = document.getElementById('form-foto-veh');
        if (avatar && fileInp && form) {
            avatar.addEventListener('click', () => fileInp.click());
            fileInp.addEventListener('change', async () => {
                if (!fileInp.files.length) return;
                const fd = new FormData(form);
                fd.set('foto', fileInp.files[0]);
                try {
                    const r = await fetch(form.action, {
                        method: 'POST',
                        body: fd
                    });
                    const j = await r.json();
                    if (j.ok) {
                        img.src = j.url + '?t=' + Date.now();
                        img.style.display = 'block';
                        if (letra) letra.style.display = 'none';
                    } else {
                        alert(j.error || 'No se pudo subir la foto.');
                    }
                } catch (e) {
                    alert('Error subiendo la foto.');
                } finally {
                    fileInp.value = '';
                }
            });
        }

        // ------------ Ventana flotante + búsqueda via postMessage ------------
        function abrirMiniFrame(url, titulo = 'Vista externa', termino = '') {
            const box = document.getElementById('mini-frame');
            const iframe = document.getElementById('mini-iframe');
            const title = document.getElementById('mini-title');

            title.textContent = titulo;
            box.style.display = 'flex';
            traibleRedimensionable.iniciar();

            iframe.onload = () => {
                if (termino) {
                    // Enviamos la placa al iframe (otro origen)
                    iframe.contentWindow.postMessage({
                        type: 'SPOT_SEARCH',
                        term: termino
                    }, '*');
                }
            };
            iframe.src = url;
        }

        function cerrarMiniFrame() {
            const box = document.getElementById('mini-frame');
            const iframe = document.getElementById('mini-iframe');
            iframe.src = '';
            box.style.display = 'none';
        }

        // Max/restore + arrastrar/redimensionar (tus funciones)
        document.getElementById('mini-head')?.addEventListener('dblclick', toggleMaxMiniFrame);
        document.getElementById('mini-close')?.addEventListener('click', cerrarMiniFrame);
        document.getElementById('mini-max')?.addEventListener('click', toggleMaxMiniFrame);
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                cerrarMiniFrame();
            }
        });

        let _prevRect = null;

        function toggleMaxMiniFrame() {
            const box = document.getElementById('mini-frame');
            const btn = document.getElementById('mini-max');
            if (!box.classList.contains('max')) {
                _prevRect = {
                    left: box.style.left || (box.getBoundingClientRect().left + 'px'),
                    top: box.style.top || (box.getBoundingClientRect().top + 'px'),
                    width: box.offsetWidth + 'px',
                    height: box.offsetHeight + 'px'
                };
                box.classList.add('max');
                btn.textContent = '▣';
            } else {
                box.classList.remove('max');
                if (_prevRect) {
                    box.style.left = _prevRect.left;
                    box.style.top = _prevRect.top;
                    box.style.width = _prevRect.width;
                    box.style.height = _prevRect.height;
                }
                btn.textContent = '▢';
            }
        }

        const traibleRedimensionable = (function() {
            let dragging = false,
                resizing = false,
                startX = 0,
                startY = 0,
                startL = 0,
                startT = 0,
                startW = 0,
                startH = 0;

            function onDragStart(e) {
                const box = document.getElementById('mini-frame');
                if (box.classList.contains('max')) return;
                dragging = true;
                const r = box.getBoundingClientRect();
                startX = (e.touches ? e.touches[0].clientX : e.clientX);
                startY = (e.touches ? e.touches[0].clientY : e.clientY);
                startL = r.left;
                startT = r.top;
                box.classList.add('dragging');
                document.body.classList.add('noselect');
                window.addEventListener('mousemove', onDragMove);
                window.addEventListener('mouseup', onDragEnd);
            }

            function onDragMove(e) {
                if (!dragging) return;
                const box = document.getElementById('mini-frame');
                const dx = (e.clientX - startX),
                    dy = (e.clientY - startY);
                let L = startL + dx,
                    T = startT + dy;
                const r = box.getBoundingClientRect(),
                    vw = window.innerWidth,
                    vh = window.innerHeight,
                    margin = 10;
                L = Math.max(margin - (r.width - 60), Math.min(vw - margin - 60, L));
                T = Math.max(margin, Math.min(vh - margin - 60, T));
                box.style.left = L + 'px';
                box.style.top = T + 'px';
            }

            function onDragEnd() {
                dragging = false;
                const box = document.getElementById('mini-frame');
                box.classList.remove('dragging');
                document.body.classList.remove('noselect');
                window.removeEventListener('mousemove', onDragMove);
                window.removeEventListener('mouseup', onDragEnd);
            }

            function onResizeStart(e) {
                const box = document.getElementById('mini-frame');
                if (box.classList.contains('max')) return;
                resizing = true;
                const r = box.getBoundingClientRect();
                startX = (e.touches ? e.touches[0].clientX : e.clientX);
                startY = (e.touches ? e.touches[0].clientY : e.clientY);
                startW = r.width;
                startH = r.height;
                box.classList.add('resizing');
                document.body.classList.add('noselect');
                window.addEventListener('mousemove', onResizeMove);
                window.addEventListener('mouseup', onResizeEnd);
                e.preventDefault();
            }

            function onResizeMove(e) {
                if (!resizing) return;
                const box = document.getElementById('mini-frame');
                const dx = (e.clientX - startX),
                    dy = (e.clientY - startY);
                const minW = 320,
                    minH = 200,
                    maxW = Math.min(window.innerWidth - 20, 1200),
                    maxH = Math.min(window.innerHeight - 20, 900);
                let W = Math.max(minW, Math.min(maxW, startW + dx));
                let H = Math.max(minH, Math.min(maxH, startH + dy));
                box.style.width = W + 'px';
                box.style.height = H + 'px';
            }

            function onResizeEnd() {
                resizing = false;
                const box = document.getElementById('mini-frame');
                box.classList.remove('resizing');
                document.body.classList.remove('noselect');
                window.removeEventListener('mousemove', onResizeMove);
                window.removeEventListener('mouseup', onResizeEnd);
            }

            function iniciar() {
                const head = document.getElementById('mini-head');
                const handle = document.getElementById('mini-resize');
                if (head && !head._binded) {
                    head.addEventListener('mousedown', onDragStart);
                    head._binded = true;
                }
                if (handle && !handle._binded) {
                    handle.addEventListener('mousedown', onResizeStart);
                    handle._binded = true;
                }
            }
            window.addEventListener('resize', () => {
                const box = document.getElementById('mini-frame');
                if (!box || box.style.display === 'none' || box.classList.contains('max')) return;
                const r = box.getBoundingClientRect(),
                    vw = window.innerWidth,
                    vh = window.innerHeight,
                    margin = 10;
                let L = Math.min(r.left, vw - margin - 60);
                let T = Math.min(r.top, vh - margin - 60);
                L = Math.max(margin - (r.width - 60), L);
                T = Math.max(margin, T);
                box.style.left = L + 'px';
                box.style.top = T + 'px';
            });
            return {
                iniciar
            };
        })();

        // ===== Vista Externa en pestaña =====
        const extUrlInp = document.getElementById('ext-url');
        const extTermInp = document.getElementById('ext-term');
        const extOpenNew = document.getElementById('ext-open-new');
        const extIframe = document.getElementById('ext-iframe');

        function cargarExterno(url, term) {
            // Actualiza link "abrir en nueva pestaña"
            extOpenNew.href = url;

            // Carga iframe y, al finalizar, envía la búsqueda si aplica
            extIframe.onload = () => {
                if (term) {
                    try {
                        extIframe.contentWindow.postMessage({
                            type: 'SPOT_SEARCH',
                            term
                        }, '*');
                    } catch (e) {
                        // Silencioso si el origen bloquea postMessage
                    }
                }
            };
            extIframe.src = url;
        }

        // Botón cargar
        // Botón copiar placa
        // --- Copiar placa (con fallback para HTTP / navegadores viejos) ---
        function feedback(btn, okText = "✅ Copiado") {
            if (!btn) return;
            const original = btn.textContent;
            btn.textContent = okText;
            setTimeout(() => (btn.textContent = original), 2000);
        }

        function fallbackCopy(text, btn) {
            const ta = document.createElement("textarea");
            ta.value = text;
            ta.setAttribute("readonly", "");
            ta.style.position = "fixed";
            ta.style.opacity = "0";
            ta.style.left = "-9999px";
            document.body.appendChild(ta);
            ta.focus();
            ta.select();
            let ok = false;
            try {
                ok = document.execCommand("copy");
            } catch (e) {}
            document.body.removeChild(ta);
            if (ok) {
                feedback(btn);
            } else {
                alert("No se pudo copiar al portapapeles");
            }
        }

        const extCopyBtn = document.getElementById("ext-copy");
        extCopyBtn?.addEventListener("click", () => {
            const term = (extTermInp?.value || "").trim();
            if (!term) return alert("No hay placa para copiar");

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(term)
                    .then(() => feedback(extCopyBtn))
                    .catch(() => fallbackCopy(term, extCopyBtn));
            } else {
                // HTTP u otros casos: usar fallback
                fallbackCopy(term, extCopyBtn);
            }
        });


        // Auto-cargar cuando el usuario entra a la pestaña "Externo"
        // (si tiene URL por defecto)
        tabs.forEach(btn => {
            btn.addEventListener('click', () => {
                if (btn.dataset.tab === 'ext') {
                    const url = (extUrlInp?.value || '').trim();
                    const term = (extTermInp?.value || '').trim();
                    if (!url) return;
                    cargarExterno(url, term); // ← siempre recarga
                }
            });
        });
    </script>

    <!-- Ventana flotante con iframe -->
    <div id="mini-frame" class="mini-frame" style="display:none; left:20px; bottom:20px;">
        <div class="mini-head" id="mini-head">
            <span id="mini-title">Vista externa</span>
            <div class="mini-actions">
                <button class="mini-btn" id="mini-max">▢</button>
                <button class="mini-btn" id="mini-close">×</button>
            </div>
        </div>

        <iframe id="mini-iframe" src=""></iframe>

        <!-- Tirador de redimensionado -->
        <div class="resize-handle" id="mini-resize" title="Redimensionar"></div>
    </div>

    <style>
        .mini-frame {
            position: fixed;
            width: 520px;
            height: 360px;
            min-width: 320px;
            min-height: 200px;
            max-width: 95vw;
            max-height: 90vh;
            background: #fff;
            border: 1px solid #cfd6e4;
            border-radius: 12px;
            box-shadow: 0 12px 32px rgba(0, 0, 0, .28);
            overflow: hidden;
            z-index: 10000;
            display: flex;
            flex-direction: column;
        }

        .mini-head {
            cursor: move;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            padding: 8px 10px;
            background: #0a66c2;
            color: #fff;
            font-weight: 700;
            user-select: none;
        }

        .mini-actions {
            display: flex;
            gap: 6px;
        }

        .mini-btn {
            border: 0;
            background: transparent;
            color: #fff;
            font-size: 18px;
            line-height: 1;
            cursor: pointer;
            padding: 2px 6px;
            border-radius: 6px;
        }

        .mini-btn:hover {
            background: rgba(255, 255, 255, .18);
        }

        .mini-frame iframe {
            flex: 1;
            width: 100%;
            border: 0;
            background: #f8fafc;
        }

        /* Esquina para redimensionar */
        .resize-handle {
            position: absolute;
            right: 6px;
            bottom: 6px;
            width: 16px;
            height: 16px;
            border-right: 3px solid #c7cedc;
            border-bottom: 3px solid #c7cedc;
            transform: rotate(0deg);
            cursor: se-resize;
            opacity: .9;
        }

        /* Estados */
        .mini-frame.dragging,
        .mini-frame.resizing {
            transition: none;
        }

        body.noselect {
            user-select: none;
        }

        /* Maximizda ocupa casi toda la pantalla dejando margen */
        .mini-frame.max {
            left: 10px !important;
            top: 10px !important;
            right: auto;
            bottom: auto;
            width: calc(100vw - 20px) !important;
            height: calc(100vh - 20px) !important;
            max-width: none;
            max-height: none;
            border-radius: 10px;
        }
    </style>








</body>
</html>
