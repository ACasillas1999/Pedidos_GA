<?php
header('Content-Type: application/json');
date_default_timezone_set('America/Mexico_City');

// Verifica que la solicitud sea POST
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode(["error" => "Metodo no permitido"]);
    exit;
}

// Verifica que los datos esten presentes en la solicitud POST
if (!isset($_POST['id'], $_POST['estado'], $_POST['coordenada'])) {
    echo json_encode(["error" => "ID, estado y coordenada son requeridos"]);
    exit;
}

// Obten los datos de la solicitud POST y verifica que no esten vacios
$id = trim($_POST['id']);
$estado = trim($_POST['estado']);
$coordenada = trim($_POST['coordenada']);

if (empty($id) || empty($estado) || empty($coordenada)) {
    echo json_encode(["error" => "ID, estado y coordenada no pueden estar vacios"]);
    exit;
}

// Conexion a la base de datos
require_once __DIR__ . "/Conexiones/Conexion.php";

// Inicia una transaccion para asegurar que ambas consultas se ejecuten exitosamente
$conn->begin_transaction();

try {
    // Actualiza el estado del pedido en la base de datos
    $sql_update = "UPDATE pedidos SET estado = ? WHERE id = ?";
    $stmt_update = $conn->prepare($sql_update);
    if (!$stmt_update) {
        throw new Exception("Error en la preparacion de la consulta de actualizacion: " . $conn->error);
    }

    $stmt_update->bind_param("si", $estado, $id);
    if (!$stmt_update->execute()) {
        throw new Exception("Error al actualizar el estado");
    }

    // Obtiene la fecha y hora actual
    $fecha = date("Y-m-d");
    $hora = date("H:i:s");

    // Inserta un nuevo registro en la tabla estadopedido
    $sql_insert = "INSERT INTO EstadoPedido (ID_Pedido, Estado, Fecha, Hora, Coordenada) VALUES (?, ?, ?, ?, ?)";
    $stmt_insert = $conn->prepare($sql_insert);
    if (!$stmt_insert) {
        throw new Exception("Error en la preparacion de la consulta de insercion: " . $conn->error);
    }

    $stmt_insert->bind_param("issss", $id, $estado, $fecha, $hora, $coordenada);
    if (!$stmt_insert->execute()) {
        throw new Exception("Error al insertar el registro en estadopedido");
    }

    // Sincronizar estado_factura_caja segun el nuevo estado
    $facturaCajaMap = ['EN RUTA' => 5, 'ENTREGADO' => 6];
    $estadoUpper = strtoupper(trim($estado));
    if (isset($facturaCajaMap[$estadoUpper])) {
        $nuevoFactura = $facturaCajaMap[$estadoUpper];
        // Solo avanza, nunca retrocede
        $stmtF = $conn->prepare("UPDATE pedidos SET estado_factura_caja = ? WHERE id = ? AND estado_factura_caja < ?");
        $stmtF->bind_param("iii", $nuevoFactura, $id, $nuevoFactura);
        $stmtF->execute();
        $affected = $stmtF->affected_rows;
        $stmtF->close();

        if ($affected > 0) {
            $usrLog = isset($_POST['username']) ? trim($_POST['username']) : 'Chofer';
            if (empty($usrLog)) $usrLog = 'Chofer';
            
            $msgLog = ($nuevoFactura === 5) 
                ? 'Factura: Cargado en Camioneta -> En Ruta [Chofer]' 
                : 'Factura: En Ruta -> Entregado [Chofer]';

            $stmtH = $conn->prepare("INSERT INTO historial_cambios (Usuario_ID, Pedido_ID, Cambio, Fecha_Hora) VALUES (?, ?, ?, NOW())");
            $stmtH->bind_param("sis", $usrLog, $id, $msgLog);
            $stmtH->execute();
            $stmtH->close();
        }
    }

    // =========================================================================
    // CONFIGURACION DE PLANTILLAS WHATSAPP POR ESTATUS
    // =========================================================================
    // Aqui se especifican que estatus disparan WhatsApp y que plantilla de Meta usan.
    // Para agregar mas estatus en el futuro, solo agregalos a esta lista:
    $plantillasPorEstatus = [
        'EN RUTA' => [
            'nombre_plantilla' => 'ga_pedido_en_ruta', // Nombre registrado en Meta
            'idioma'           => 'es_MX'              // Idioma en Meta (ej: es, es_MX o en_US)
        ],
        'ENTREGADO' => [
            'nombre_plantilla' => 'ga_pedido_entregado', // Nombre registrado en Meta
            'idioma'           => 'es_MX'                // Idioma en Meta
        ]
        // Ejemplo para futuro:
        // 'REPROGRAMADO' => ['nombre_plantilla' => 'ga_pedido_reprogramado', 'idioma' => 'es_MX'],
        // 'CANCELADO'    => ['nombre_plantilla' => 'ga_pedido_cancelado',    'idioma' => 'es_MX']
    ];

    if (isset($plantillasPorEstatus[$estadoUpper])) {
        try {
            $cfgPlantilla = $plantillasPorEstatus[$estadoUpper];

            $stmtCliente = $conn->prepare("SELECT SUCURSAL, FACTURA, NOMBRE_CLIENTE, TELEFONO FROM pedidos WHERE id = ?");
            if ($stmtCliente) {
                $stmtCliente->bind_param("i", $id);
                $stmtCliente->execute();
                $resCliente = $stmtCliente->get_result();

                if ($rowC = $resCliente->fetch_assoc()) {
                    $telCliente = preg_replace('/\D+/', '', (string)($rowC['TELEFONO'] ?? ''));

                    if (!empty($telCliente)) {
                        $telDestino = (strlen($telCliente) === 10) ? '52' . $telCliente : $telCliente;
                        $sucursalCliente = trim((string)($rowC['SUCURSAL'] ?? 'N/A'));
                        $facturaCliente  = trim((string)($rowC['FACTURA'] ?? 'S/N'));
                        $nombreCliente   = trim((string)($rowC['NOMBRE_CLIENTE'] ?? 'Cliente'));
                        if (empty($nombreCliente)) {
                            $nombreCliente = 'Cliente';
                        }

                        // Directorio de conmutadores por sucursal
                        $conmutadores = [
                            'TAPATIA'     => '33 36 19 14 53',
                            'DEASA'       => '33 36 14 19 89',
                            'DIMEGSA'     => '33 36 19 30 60',
                            'AIESA'       => '33 38 10 91 01',
                            'SEGSA'       => '33 38 25 25 72',
                            'FESA'        => '33 36 58 03 13',
                            'CODI'        => '33 41 64 78 73',
                            'QUERETARO'   => '442 9269 017',
                            'GABSA'       => '447 7136 491',
                            'ILUMINACION' => '33 3650 1060',
                            'VALLARTA'    => '329 296 2100'
                        ];
                        $telConmutador = $conmutadores[strtoupper($sucursalCliente)] ?? '33 3811 0000';

                        // Credenciales Meta WhatsApp API
                        $tokenWp = 'EAAGacaATjwEBOZBgqhohcVk1ZBGEAbiTl7i86qESvSPjdllaomwzIG7LmOOvyTFpzyIlXX6dtTYTVTLLuw6SjaLoh2rec07I8qu1nGNYSVZAmQTGNa3QCQjujTqfd7QuLLwFNQllnX2z1V7JvToDhEi5KVqUWXHSqgSETvGyU7S2SN2fpXW0NpQaRI48pwZAgGS7A1BQMjLl5ZBjy';
                        $urlWp   = 'https://graph.facebook.com/v19.0/335894526282507/messages';

                        // Estructura oficial tipo TEMPLATE de Meta con variables {{1}}, {{2}}, {{3}}, {{4}}
                        $payloadWp = json_encode([
                            "messaging_product" => "whatsapp",
                            "to"                => $telDestino,
                            "type"              => "template",
                            "template"          => [
                                "name"       => $cfgPlantilla['nombre_plantilla'],
                                "language"   => [
                                    "code" => $cfgPlantilla['idioma']
                                ],
                                "components" => [
                                    [
                                        "type"       => "body",
                                        "parameters" => [
                                            ["type" => "text", "text" => $nombreCliente],   // {{1}} Nombre del cliente
                                            ["type" => "text", "text" => $facturaCliente],  // {{2}} Numero de factura
                                            ["type" => "text", "text" => $sucursalCliente], // {{3}} Nombre de sucursal
                                            ["type" => "text", "text" => $telConmutador]    // {{4}} Telefono conmutador
                                        ]
                                    ]
                                ]
                            ]
                            
                        ]);

                        $ch = curl_init();
                        curl_setopt_array($ch, [
                            CURLOPT_URL            => $urlWp,
                            CURLOPT_POST           => true,
                            CURLOPT_POSTFIELDS     => $payloadWp,
                            CURLOPT_HTTPHEADER     => [
                                "Authorization: Bearer " . $tokenWp,
                                "Content-Type: application/json; charset=utf-8"
                            ],
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_TIMEOUT        => 4
                        ]);
                        $respWp = curl_exec($ch);
                        curl_close($ch);

                        @file_put_contents(__DIR__ . "/../whatsapp_log.txt", date("Y-m-d H:i:s") . " [Pedido #$id {$cfgPlantilla['nombre_plantilla']}] -> $telDestino: " . $respWp . PHP_EOL, FILE_APPEND);
                    }
                }
                $stmtCliente->close();
            }
        } catch (Throwable $wpEx) {
            error_log("Error enviando WhatsApp Template: " . $wpEx->getMessage());
        }
    }

    // Si ambas consultas se ejecutan correctamente, se confirma la transaccion
    $conn->commit();
    echo json_encode("Estado actualizado y registro anadido correctamente");
} catch (Exception $e) {
    // Si alguna consulta falla, se revierte la transaccion
    $conn->rollback();
    echo json_encode(["error" => $e->getMessage()]);
} finally {
    // Cierre de declaraciones y conexion
    if (isset($stmt_update)) {
        $stmt_update->close();
    }
    if (isset($stmt_insert)) {
        $stmt_insert->close();
    }
    $conn->close();
}
?>
