<?php
header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . "/Conexiones/Conexion.php";

if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'No hay conexión activa con la base de datos']);
    exit;
}

$conn->set_charset('utf8mb4');

// Auto-crear la tabla pedidos_incidencias si no existe
$sqlTable = "CREATE TABLE IF NOT EXISTS `pedidos_incidencias` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `pedido_id` INT NOT NULL,
  `motivo` VARCHAR(150) NOT NULL,
  `comentarios` TEXT NULL,
  `archivo_ruta` VARCHAR(255) NULL,
  `archivo_nombre` VARCHAR(255) NULL,
  `archivo_tipo` VARCHAR(50) NULL,
  `chofer` VARCHAR(100) NULL,
  `fecha_registro` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_pedido_id` (`pedido_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
$conn->query($sqlTable);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido. Utilice POST.']);
    exit;
}

$pedido_id = isset($_POST['pedido_id']) ? intval($_POST['pedido_id']) : 0;
$motivo = isset($_POST['motivo']) ? trim($_POST['motivo']) : '';
$comentarios = isset($_POST['comentarios']) ? trim($_POST['comentarios']) : '';
$chofer = isset($_POST['chofer']) ? trim($_POST['chofer']) : '';
if (empty($chofer)) {
    if (session_status() === PHP_SESSION_NONE) {
        session_name("GA");
        @session_start();
    }
    $nombreSesion = $_SESSION["Nombre"] ?? $_SESSION["username"] ?? "";
    $rolSesion = $_SESSION["Rol"] ?? "";
    if (!empty($nombreSesion)) {
        $etiqueta = ($rolSesion === "VR") ? "Vendedor" : ($rolSesion ?: "Oficina");
        $chofer = $nombreSesion . " (" . $etiqueta . ")";
    } else {
        $chofer = "Vendedor";
    }
}

if ($pedido_id <= 0) {
    echo json_encode(['ok' => false, 'error' => 'ID de pedido inválido']);
    exit;
}

// Motivos permitidos (App Móvil Choferes + CRM Escritorio Vendedores)
$motivosValidos = [
    'Faltante',
    'Sobrante',
    'Error de entrega',
    'Producto erróneo',
    'Cliente no recibe pedido',
    'Cliente reprograma pedido',
    'Cliente no cuenta con maniobra para descarga',
    'Cuestiones climáticas'
];

if (empty($motivo)) {
    echo json_encode(['ok' => false, 'error' => 'Debe seleccionar un motivo para la incidencia']);
    exit;
}

// Procesar archivo opcional (foto, video, doc, pdf, etc.)
$archivo_ruta = null;
$archivo_nombre = null;
$archivo_tipo = null;

if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
    $fileTmp = $_FILES['archivo']['tmp_name'];
    $origName = basename($_FILES['archivo']['name']);
    
    // Directorio de almacenamiento: uploads/incidencias/
    $uploadDir = __DIR__ . '/../uploads/incidencias/';
    if (!file_exists($uploadDir)) {
        @mkdir($uploadDir, 0777, true);
    }
    
    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    
    // Extensiones permitidas (imágenes, videos, documentos)
    $allowedExts = [
        // Imágenes
        'jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp', 'heic',
        // Videos
        'mp4', 'mov', 'avi', 'mkv', '3gp', 'webm',
        // Documentos
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'csv', 'ppt', 'pptx'
    ];
    
    if (!in_array($ext, $allowedExts)) {
        echo json_encode([
            'ok' => false, 
            'error' => 'Formato de archivo no admitido (.' . $ext . '). Admite imágenes, videos, PDF, DOC, XLS o TXT.'
        ]);
        exit;
    }
    
    // Determinar categoría para facilitar la renderización en el frontend
    if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp', 'heic'])) {
        $archivo_tipo = 'imagen';
    } elseif (in_array($ext, ['mp4', 'mov', 'avi', 'mkv', '3gp', 'webm'])) {
        $archivo_tipo = 'video';
    } elseif ($ext === 'pdf') {
        $archivo_tipo = 'pdf';
    } else {
        $archivo_tipo = 'documento';
    }
    
    $cleanOrigName = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $origName);
    $uniqueName = 'inc_' . $pedido_id . '_' . date('Ymd_His') . '_' . substr(md5(uniqid()), 0, 6) . '.' . $ext;
    $targetPath = $uploadDir . $uniqueName;
    
    if (move_uploaded_file($fileTmp, $targetPath)) {
        $archivo_ruta = 'uploads/incidencias/' . $uniqueName;
        $archivo_nombre = $cleanOrigName;
    } else {
        echo json_encode(['ok' => false, 'error' => 'No se pudo guardar el archivo adjunto en el servidor']);
        exit;
    }
}

// Inserción en la base de datos
$stmt = $conn->prepare("INSERT INTO `pedidos_incidencias` (`pedido_id`, `motivo`, `comentarios`, `archivo_ruta`, `archivo_nombre`, `archivo_tipo`, `chofer`, `fecha_registro`) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
if (!$stmt) {
    echo json_encode(['ok' => false, 'error' => 'Error al preparar inserción: ' . $conn->error]);
    exit;
}

$stmt->bind_param('issssss', $pedido_id, $motivo, $comentarios, $archivo_ruta, $archivo_nombre, $archivo_tipo, $chofer);

if ($stmt->execute()) {
    $insertedId = $stmt->insert_id;
    $stmt->close();

    // Registrar en historial_cambios
    $cambioHist = "Incidencia registrada ($motivo)" . (!empty($comentarios) ? ": $comentarios" : "");
    if ($stmtHist = $conn->prepare("INSERT INTO historial_cambios (Usuario_ID, Pedido_ID, Cambio, Fecha_Hora) VALUES (?, ?, ?, NOW())")) {
        $stmtHist->bind_param("sis", $chofer, $pedido_id, $cambioHist);
        @$stmtHist->execute();
        $stmtHist->close();
    }

    // =========================================================================
    // NOTIFICACIÓN WHATSAPP AL JEFE DE CHOFERES (JC) DE LA SUCURSAL DEL PEDIDO
    // Se ejecuta tanto para incidencias registradas desde Escritorio como desde App Móvil
    // =========================================================================
    $wpNotificados = 0;
    try {
        $sqlJC = "SELECT p.SUCURSAL, p.FACTURA, p.NOMBRE_CLIENTE, u.Numero, u.Nombre AS NombreJC
                  FROM pedidos p
                  INNER JOIN usuarios u ON UPPER(TRIM(u.Sucursal)) = UPPER(TRIM(p.SUCURSAL))
                  WHERE p.ID = ? AND u.Rol = 'JC' AND u.Numero IS NOT NULL AND TRIM(u.Numero) != ''";

        if ($stmtJC = $conn->prepare($sqlJC)) {
            $stmtJC->bind_param("i", $pedido_id);
            $stmtJC->execute();
            $resJC = $stmtJC->get_result();

            $tokenWp = 'EAAGacaATjwEBOZBgqhohcVk1ZBGEAbiTl7i86qESvSPjdllaomwzIG7LmOOvyTFpzyIlXX6dtTYTVTLLuw6SjaLoh2rec07I8qu1nGNYSVZAmQTGNa3QCQjujTqfd7QuLLwFNQllnX2z1V7JvToDhEi5KVqUWXHSqgSETvGyU7S2SN2fpXW0NpQaRI48pwZAgGS7A1BQMjLl5ZBjy';
            $urlWp   = 'https://graph.facebook.com/v19.0/335894526282507/messages';

            while ($rowJC = $resJC->fetch_assoc()) {
                $numJC = preg_replace('/\D+/', '', (string)($rowJC['Numero'] ?? ''));
                if (empty($numJC)) {
                    continue;
                }
                $telJC = (strlen($numJC) === 10) ? '52' . $numJC : $numJC;
                $sucursalPed = trim((string)($rowJC['SUCURSAL'] ?? 'N/A'));
                $facturaPed  = trim((string)($rowJC['FACTURA'] ?? ('#' . $pedido_id)));
                if ($facturaPed === '') $facturaPed = '#' . $pedido_id;

                $parametrosBody = [
                    ["type" => "text", "text" => $facturaPed],  // {{1}} Factura
                    ["type" => "text", "text" => $sucursalPed], // {{2}} Sucursal
                    ["type" => "text", "text" => $motivo],      // {{3}} Motivo
                    ["type" => "text", "text" => $chofer]       // {{4}} Reportado por
                ];

                // Enviar plantilla ga_incidencia en Meta (prueba es_MX y respalda en es / en_US)
                $idiomasIntento = ['es_MX', 'es', 'en_US'];
                $respTpl = '';
                foreach ($idiomasIntento as $langCode) {
                    $payloadTemplate = json_encode([
                        "messaging_product" => "whatsapp",
                        "to"                => $telJC,
                        "type"              => "template",
                        "template"          => [
                            "name"       => "ga_incidencia",
                            "language"   => ["code" => $langCode],
                            "components" => [
                                [
                                    "type"       => "body",
                                    "parameters" => $parametrosBody
                                ]
                            ]
                        ]
                    ]);

                    $ch = curl_init();
                    curl_setopt_array($ch, [
                        CURLOPT_URL            => $urlWp,
                        CURLOPT_POST           => true,
                        CURLOPT_POSTFIELDS     => $payloadTemplate,
                        CURLOPT_HTTPHEADER     => [
                            "Authorization: Bearer " . $tokenWp,
                            "Content-Type: application/json; charset=utf-8"
                        ],
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_TIMEOUT        => 4
                    ]);
                    $respTpl = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);

                    $decodedTpl = json_decode((string)$respTpl, true);
                    if ($httpCode >= 200 && $httpCode < 300 && isset($decodedTpl['messages'])) {
                        break; // Enviado con éxito
                    }
                }

                $wpNotificados++;
                @file_put_contents(
                    __DIR__ . "/../whatsapp_log.txt",
                    date("Y-m-d H:i:s") . " [Incidencia Pedido #$pedido_id ($sucursalPed) -> JC $telJC ga_incidencia] " . $respTpl . PHP_EOL,
                    FILE_APPEND
                );
            }
            $stmtJC->close();
        }
    } catch (Throwable $wpEx) {
        error_log("Error enviando WhatsApp de incidencia a JC: " . $wpEx->getMessage());
    }

    // Devolver la incidencia completa recién creada
    $res = $conn->query("SELECT id, pedido_id, motivo, comentarios, archivo_ruta, archivo_nombre, archivo_tipo, chofer, DATE_FORMAT(fecha_registro, '%d/%m/%Y %H:%i') as fecha_formato, fecha_registro FROM `pedidos_incidencias` WHERE id = " . intval($insertedId));
    $incidencia = $res ? $res->fetch_assoc() : null;
    
    echo json_encode([
        'ok' => true,
        'mensaje' => 'Incidencia registrada exitosamente',
        'wp_notificados_jc' => $wpNotificados,
        'incidencia' => $incidencia
    ]);
} else {
    echo json_encode(['ok' => false, 'error' => 'Error al registrar la incidencia: ' . $stmt->error]);
}
?>
