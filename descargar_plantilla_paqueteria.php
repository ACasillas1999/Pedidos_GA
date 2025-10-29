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

// Armar datos
$folio           = trim((string)($row['FACTURA'] ?? ''));
$referencia      = $folio !== '' ? $folio : "PED-{$row['ID']}";
$cliente         = (string)($row['NOMBRE_CLIENTE'] ?? '');
$direccion       = (string)($row['DIRECCION'] ?? '');
$telefono        = (string)($row['CONTACTO'] ?? '');
$sucursalOrigen  = (string)($row['SUCURSAL'] ?? '');
$estado          = (string)($row['ESTADO'] ?? '');
$vendedor        = (string)($row['VENDEDOR'] ?? '');
$chofer          = (string)($row['CHOFER_ASIGNADO'] ?? '');
$fechaRecepcion  = (string)($row['FECHA_RECEPCION_FACTURA'] ?? '');
$tipoEnvioUpper  = mb_strtoupper('paquetería', 'UTF-8'); // fijo para el doc

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

// Setear marcadores
$template->setValue('Referencia',       $referencia);
$template->setValue('Cliente',          $cliente);
$template->setValue('Direccion',        $direccion);
$template->setValue('Telefono',         $telefono);
$template->setValue('Sucursal_Origen',  $sucursalOrigen);
$template->setValue('Tipo_Envio',       $tipoEnvioUpper);
$template->setValue('Estado',           $estado);
$template->setValue('Vendedor',         $vendedor);
$template->setValue('Chofer',           $chofer);
$template->setValue('Fecha_Recepcion',  $fechaRecepcion);
$template->setValue('FechaHoy',         $fechaHoy);
// Campos libres para que editen después
$template->setValue('Observaciones',    '');

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
