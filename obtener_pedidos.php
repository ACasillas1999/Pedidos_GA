<?php
session_name('GA');
session_start();

// Verificar sesión
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('location: /Pedidos_GA/Sesion/login.html');
    exit;
}

// Conexión centralizada
require_once __DIR__ . '/Conexiones/Conexion.php';
require_once __DIR__ . '/helpers/filtro_pedidos_rol.php';

$rolSesion = $_SESSION['Rol'] ?? '';
$sucSesion = strtoupper($_SESSION['Sucursal'] ?? '');

$sucursales_permitidas = sucursalesPermitidasPorRol($rolSesion, $sucSesion);
$vendedorFiltro        = nombreVendedorFiltro($rolSesion);

$where = [];
$types = '';
$params = [];

if (!empty($sucursales_permitidas)) {
    $place = implode(',', array_fill(0, count($sucursales_permitidas), '?'));
    $where[] = "SUCURSAL IN ($place)";
    $types  .= str_repeat('s', count($sucursales_permitidas));
    foreach ($sucursales_permitidas as $s) { $params[] = $s; }
}
if ($vendedorFiltro !== null) {
    $where[] = "TRIM(VENDEDOR) = ?";
    $types  .= 's';
    $params[] = $vendedorFiltro;
}

$whereSql = !empty($where) ? ('WHERE ' . implode(' AND ', $where)) : '';

// Traer los campos necesarios para las vistas nuevas
$sql = "SELECT ID, SUCURSAL, ESTADO, FECHA_RECEPCION_FACTURA, FECHA_ENTREGA_CLIENTE,
                CHOFER_ASIGNADO, VENDEDOR, FACTURA, DIRECCION, NOMBRE_CLIENTE,
                TELEFONO, CONTACTO, COMENTARIOS, Ruta, Coord_Origen, Coord_Destino,
                precio_factura_real
        FROM pedidos
        $whereSql";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}

$pedidos = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $pedidos[] = [
            'ID' => $row['ID'],
            'SUCURSAL' => $row['SUCURSAL'],
            'ESTADO' => $row['ESTADO'],
            'FECHA_RECEPCION_FACTURA' => $row['FECHA_RECEPCION_FACTURA'],
            'FECHA_ENTREGA_CLIENTE' => $row['FECHA_ENTREGA_CLIENTE'],
            'CHOFER_ASIGNADO' => $row['CHOFER_ASIGNADO'],
            'VENDEDOR' => $row['VENDEDOR'],
            'FACTURA' => $row['FACTURA'],
            'DIRECCION' => $row['DIRECCION'],
            'NOMBRE_CLIENTE' => $row['NOMBRE_CLIENTE'],
            'TELEFONO' => $row['TELEFONO'],
            'CONTACTO' => $row['CONTACTO'],
            'COMENTARIOS' => $row['COMENTARIOS'],
            'Ruta' => $row['Ruta'],
            'Coord_Origen' => $row['Coord_Origen'],
            'Coord_Destino' => $row['Coord_Destino'],
            'precio_factura_real' => $row['precio_factura_real']
        ];
    }
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($pedidos, JSON_UNESCAPED_UNICODE);

$conn->close();
?>

