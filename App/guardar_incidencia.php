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
    
    // Devolver la incidencia completa recién creada
    $res = $conn->query("SELECT id, pedido_id, motivo, comentarios, archivo_ruta, archivo_nombre, archivo_tipo, chofer, DATE_FORMAT(fecha_registro, '%d/%m/%Y %H:%i') as fecha_formato, fecha_registro FROM `pedidos_incidencias` WHERE id = " . intval($insertedId));
    $incidencia = $res ? $res->fetch_assoc() : null;
    
    echo json_encode([
        'ok' => true,
        'mensaje' => 'Incidencia registrada exitosamente',
        'incidencia' => $incidencia
    ]);
} else {
    echo json_encode(['ok' => false, 'error' => 'Error al registrar la incidencia: ' . $stmt->error]);
}
?>
