<?php


// descargar_plantilla_paqueteria.php
declare(strict_types=1);

session_name("GA");
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: /Pedidos_GA/Sesion/login.html");
    exit;
}

// Composer autoload (PHPWord)
require_once __DIR__ . "/vendor/autoload.php";
use PhpOffice\PhpWord\TemplateProcessor;

require_once __DIR__ . "/Conexiones/Conexion.php";

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    http_response_code(400);
    echo "ID inválido.";
    exit;
}

// Traer datos del pedido
$sql = "SELECT 
            ID, SUCURSAL, ESTADO, tipo_envio, FECHA_RECEPCION_FACTURA,
            CHOFER_ASIGNADO, VENDEDOR, FACTURA, DIRECCION,
            NOMBRE_CLIENTE, CONTACTO
        FROM pedidos
        WHERE ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();

if (!$res || $res->num_rows === 0) {
    http_response_code(404);
    echo "Pedido no encontrado.";
    exit;
}
$row = $res->fetch_assoc();
$stmt->close();

// Validar que sea paquetería (acepta con o sin tilde)
$tipoEnvio = mb_strtolower($row['tipo_envio'] ?? '', 'UTF-8');
if (!in_array($tipoEnvio, ['paquetería', 'paqueteria'], true)) {
    http_response_code(409);
    echo "Este pedido no es de tipo paquetería.";
    exit;
}

// Ruta del machote
$machote = __DIR__ . "/Machotes/Paqueteria/Plantilla_Paqueteria.docx";
if (!file_exists($machote)) {
    http_response_code(500);
    echo "No se encontró el machote: " . htmlspecialchars($machote);
    exit;
}

// Obtener datos del destinatario si existen
$sqlDest = "SELECT * FROM pedidos_destinatario WHERE pedido_id = ?";
$stmtDest = $conn->prepare($sqlDest);
$stmtDest->bind_param("i", $id);
$stmtDest->execute();
$resDest = $stmtDest->get_result();
$destinatario = $resDest->num_rows > 0 ? $resDest->fetch_assoc() : null;
$stmtDest->close();

// Obtener datos del remitente desde tabla ubicaciones
$sucursalOrigen = (string)($row['SUCURSAL'] ?? '');
$sqlRemitente = "SELECT Ubicacion, NombreCompleto, Direccion, Telefono FROM ubicaciones WHERE Ubicacion = ?";
$stmtRemitente = $conn->prepare($sqlRemitente);
$stmtRemitente->bind_param("s", $sucursalOrigen);
$stmtRemitente->execute();
$resRemitente = $stmtRemitente->get_result();
$remitente = $resRemitente->num_rows > 0 ? $resRemitente->fetch_assoc() : null;
$stmtRemitente->close();

// Armar datos del remitente
$nombreRemitente = $remitente ? (string)($remitente['NombreCompleto'] ?? '') : 'DISTRIBUIDORA ELÉCTRICA ASCENCIO SA DE CV';
$nombreSucursal  = $remitente ? (string)($remitente['Ubicacion'] ?? $sucursalOrigen) : $sucursalOrigen;
$direccionRemitente = $remitente ? (string)($remitente['Direccion'] ?? '') : 'AV. ALEMANIA 1255 -1257, COL. MODERNA C.P. 44190 GUADALAJARA JALISCO, MEXICO';
$telefonoRemitente = $remitente ? (string)($remitente['Telefono'] ?? '') : '36141989';

// Datos generales
$folio           = trim((string)($row['FACTURA'] ?? ''));
$referencia      = $folio !== '' ? $folio : "PED-{$row['ID']}";
$tipoEnvioUpper  = mb_strtoupper('paquetería', 'UTF-8');

// Datos del destinatario (usar datos capturados si existen, sino usar datos del pedido original)
if ($destinatario) {
    // Usar datos del destinatario capturado
    $nombreDestinatario = (string)($destinatario['nombre_destinatario'] ?? '');
    $calle              = (string)($destinatario['calle'] ?? '');
    $noExterior         = (string)($destinatario['no_exterior'] ?? '');
    $noInterior         = (string)($destinatario['no_interior'] ?? '');
    $entreCalles        = (string)($destinatario['entre_calles'] ?? '');
    $colonia            = (string)($destinatario['colonia'] ?? '');
    $codigoPostal       = (string)($destinatario['codigo_postal'] ?? '');
    $ciudad             = (string)($destinatario['ciudad'] ?? '');
    $estadoDestino      = (string)($destinatario['estado_destino'] ?? '');
    $telefonoDestino    = (string)($destinatario['telefono_destino'] ?? '');
    $contactoDestino    = (string)($destinatario['contacto_destino'] ?? '');

    // Construir dirección completa
    $direccionCompleta = $calle;
    if ($noExterior) $direccionCompleta .= " #{$noExterior}";
    if ($noInterior) $direccionCompleta .= " Int. {$noInterior}";
    if ($entreCalles) $direccionCompleta .= ", {$entreCalles}";
    $direccionCompleta .= ", Col. {$colonia}";
    if ($codigoPostal) $direccionCompleta .= ", C.P. {$codigoPostal}";
    $direccionCompleta .= ", {$ciudad}, {$estadoDestino}";

    // Datos de paquetería
    $nombrePaqueteria = (string)($destinatario['nombre_paqueteria'] ?? '');
    $tipoCobro        = (string)($destinatario['tipo_cobro'] ?? '');
    $atn              = (string)($destinatario['atn'] ?? '');
    $numCliente       = (string)($destinatario['num_cliente'] ?? '');
    $claveSat         = (string)($destinatario['clave_sat'] ?? '');
} else {
    // Fallback: usar datos del pedido original
    $nombreDestinatario = (string)($row['NOMBRE_CLIENTE'] ?? '');
    $direccionCompleta  = (string)($row['DIRECCION'] ?? '');
    $telefonoDestino    = (string)($row['CONTACTO'] ?? '');
    $contactoDestino    = '';
    $calle              = '';
    $noExterior         = '';
    $noInterior         = '';
    $entreCalles        = '';
    $colonia            = '';
    $codigoPostal       = '';
    $ciudad             = '';
    $estadoDestino      = '';
    $nombrePaqueteria   = '';
    $tipoCobro          = '';
    $atn                = '';
    $numCliente         = '';
    $claveSat           = '';
}

// Fecha “hoy” en español
$fechaHoyDT = new DateTime('now');
$meses = [
    1=>'enero',2=>'febrero',3=>'marzo',4=>'abril',5=>'mayo',6=>'junio',
    7=>'julio',8=>'agosto',9=>'septiembre',10=>'octubre',11=>'noviembre',12=>'diciembre'
];
$dia  = $fechaHoyDT->format('d');
$mes  = $meses[(int)$fechaHoyDT->format('m')];
$anio = $fechaHoyDT->format('Y');
$fechaHoy = "{$dia} de {$mes} del {$anio}";

// Crear TemplateProcessor
$template = new TemplateProcessor($machote);

// ========== DATOS DEL REMITENTE ==========
$template->setValue('Nombre_Remitente',    $nombreRemitente);
$template->setValue('Nombre_Sucursal',     $nombreSucursal);
$template->setValue('Direccion_Remitente', $direccionRemitente);
$template->setValue('Telefono_Remitente',  $telefonoRemitente);

// ========== DATOS DEL DESTINATARIO ==========
$template->setValue('Nombre_Destinatario', $nombreDestinatario);
$template->setValue('Direccion_Completa',  $direccionCompleta);
$template->setValue('Telefono_Destinatario', $telefonoDestino);
$template->setValue('Contacto_Destinatario', $contactoDestino);

// Campos individuales de dirección (por si se quieren usar separados)
$template->setValue('Calle',               $calle);
$template->setValue('No_Exterior',         $noExterior);
$template->setValue('No_Interior',         $noInterior);
$template->setValue('Entre_Calles',        $entreCalles);
$template->setValue('Colonia',             $colonia);
$template->setValue('Codigo_Postal',       $codigoPostal);
$template->setValue('Ciudad',              $ciudad);
$template->setValue('Estado_Destino',      $estadoDestino);

// ========== DATOS DE PAQUETERÍA ==========
$template->setValue('Nombre_Paqueteria',  $nombrePaqueteria);
$template->setValue('Tipo_Cobro',         $tipoCobro);
$template->setValue('ATN',                 $atn);
$template->setValue('Num_Cliente',        $numCliente);
$template->setValue('Clave_SAT',          $claveSat);

// ========== OTROS DATOS ==========
$template->setValue('Referencia',         $referencia);
$template->setValue('FechaHoy',           $fechaHoy);

// Compatibilidad con plantilla anterior
$template->setValue('Cliente',            $nombreDestinatario);
$template->setValue('Direccion',          $direccionCompleta);
$template->setValue('Telefono',           $telefonoDestino);

// Guardar en /Docs con nombre amigable
$destDir = __DIR__ . "/Docs";
if (!is_dir($destDir)) {
    @mkdir($destDir, 0777, true);
}

$base = $folio !== '' ? preg_replace('/[^A-Za-z0-9_\-]/', '_', $folio) : "ID{$row['ID']}";
$filename = "Plantilla_Paqueteria_{$base}.docx";
$fullpath = $destDir . "/" . $filename;

$template->saveAs($fullpath);

// Entregar el archivo para descarga
if (!is_file($fullpath)) {
    http_response_code(500);
    echo "No se pudo generar el archivo.";
    exit;
}

$conn->close();

// ===== después de saveAs($fullpath) =====
clearstatcache(true, $fullpath);
if (!is_file($fullpath)) {
    http_response_code(500);
    exit("No se pudo generar el archivo.");
}

// Limpia cualquier salida previa (evita BOM/espacios)
while (ob_get_level() > 0) { ob_end_clean(); }
ini_set('zlib.output_compression', '0');

$filesize = filesize($fullpath);
$fp = fopen($fullpath, 'rb');

header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="'.$filename.'"');
header('Content-Length: '.$filesize);
header('Cache-Control: private, must-revalidate');
header('Pragma: public');

fpassthru($fp);
fclose($fp);
exit;
