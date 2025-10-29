<?php
// ====== Seguridad de sesión ======
ini_set('session.cookie_httponly', true);
ini_set('session.cookie_secure', true);
session_name("GA");
session_start();

// Si quieres exigir login, descomenta este bloque:
/*
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(["ok"=>false, "error"=>"No autorizado"]);
    exit;
}
*/

header('Content-Type: application/json; charset=utf-8');

// ====== Conexión BD ======
require_once __DIR__ . "/../Conexiones/Conexion.php";
if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    echo json_encode(["ok"=>false, "error"=>"Sin conexión a BD"]);
    exit;
}
$conn->set_charset("utf8mb4");

// ====== Utils ======
function jerr($msg, $code=400){
    http_response_code($code);
    echo json_encode(["ok"=>false, "error"=>$msg]);
    exit;
}
function ok($data=null){ echo json_encode(["ok"=>true, "data"=>$data]); exit; }

$action = $_GET['action'] ?? 'list';

// ====== Normalizadores ======
function body_json(){
    $raw = file_get_contents('php://input');
    if ($raw==='') return [];
    $j = json_decode($raw, true);
    if (!is_array($j)) jerr("JSON inválido");
    return $j;
}
function ints_array($arr){
    if (!is_array($arr)) return [];
    return array_values(array_unique(array_map(fn($x)=>intval($x), $arr)));
}

// ====== Endpoints ======
switch ($action) {

    case 'vehiculos': {
        // Útil si prefieres llenar el selector desde JS en vez de PHP
        $res = $conn->query("SELECT id_vehiculo AS id, placa, tipo, Sucursal AS suc
                             FROM vehiculos ORDER BY Sucursal, placa");
        $rows = [];
        while ($r = $res->fetch_assoc()) {
            $r['id'] = (int)$r['id'];
            $r['label'] = trim(($r['placa'] ?: 'SN-PLACA') . ' · ' . $r['tipo'] . ' · ' . $r['suc']);
            $rows[] = $r;
        }
        ok($rows);
    }

    case 'list': {
        // Devuelve todos los productos con arreglo de IDs de vehículos
        $sql = "SELECT i.id, i.nombre, i.marca, i.modelo, i.cantidad,
                       i.stock_minimo, i.stock_maximo,
                       i.sku, i.costo, i.precio, i.activo,
                       i.base_unidad, i.presentacion_unidad, i.presentacion_cantidad,
                       GROUP_CONCAT(iv.id_vehiculo) AS vehs
                FROM inventario i
                LEFT JOIN inventario_vehiculo iv ON iv.id_inventario = i.id
                GROUP BY i.id
                ORDER BY i.id DESC";
        $res = $conn->query($sql);
        $rows = [];
        while ($r = $res->fetch_assoc()) {
            $veh = [];
            if (!empty($r['vehs'])) {
                foreach (explode(',', $r['vehs']) as $v) $veh[] = (int)$v;
            }
            $rows[] = [
                "id"      => (int)$r['id'],
                "nombre"  => $r['nombre'],
                "marca"   => $r['marca'],
                "modelo"  => $r['modelo'],
                "cantidad"=> (int)$r['cantidad'],
                "min"     => (int)$r['stock_minimo'],
                "max"     => (int)$r['stock_maximo'],
                "sku"     => $r['sku'] ?? null,
                "costo"   => isset($r['costo']) ? (float)$r['costo'] : null,
                "activo"  => isset($r['activo']) ? (int)$r['activo'] : 1,
                "base"    => $r['base_unidad'] ?? null,
                "unidad"  => $r['presentacion_unidad'] ?? null,
                "contenido"=> isset($r['presentacion_cantidad']) ? (float)$r['presentacion_cantidad'] : 1,
                "vehiculos" => $veh
            ];
        }
        ok($rows);
    }

    case 'get': {
        $id = intval($_GET['id'] ?? 0);
        if ($id <= 0) jerr("ID inválido");

        $st = $conn->prepare("SELECT id, nombre, marca, modelo, cantidad, stock_minimo, stock_maximo, sku, costo, precio, activo, base_unidad, presentacion_unidad, presentacion_cantidad
                              FROM inventario WHERE id=? LIMIT 1");
        $st->bind_param("i", $id);
        $st->execute(); $p = $st->get_result()->fetch_assoc();
        if (!$p) jerr("No encontrado", 404);

        $st2 = $conn->prepare("SELECT id_vehiculo FROM inventario_vehiculo WHERE id_inventario=?");
        $st2->bind_param("i", $id);
        $st2->execute(); $r2 = $st2->get_result();
        $veh = [];
        while ($x = $r2->fetch_assoc()) $veh[] = (int)$x['id_vehiculo'];

        ok([
            "id"       => (int)$p['id'],
            "nombre"   => $p['nombre'],
            "marca"    => $p['marca'],
            "modelo"   => $p['modelo'],
            "cantidad" => (int)$p['cantidad'],
            "min"      => (int)$p['stock_minimo'],
            "max"      => (int)$p['stock_maximo'],
            "sku"      => $p['sku'] ?? null,
            "costo"    => isset($p['costo']) ? (float)$p['costo'] : null,
            "activo"   => isset($p['activo']) ? (int)$p['activo'] : 1,
            "base"     => $p['base_unidad'] ?? null,
            "unidad"   => $p['presentacion_unidad'] ?? null,
            "contenido"=> isset($p['presentacion_cantidad']) ? (float)$p['presentacion_cantidad'] : 1,
            "vehiculos"=> $veh
        ]);
    }

    case 'create': {
        $b = body_json();
        $nombre   = trim($b['nombre'] ?? '');
        $marca    = trim($b['marca'] ?? '');
        $modelo   = trim($b['modelo'] ?? '');
        $cant     = intval($b['cantidad'] ?? 0);
        $min      = intval($b['min'] ?? 0);
        $max      = intval($b['max'] ?? 0);
        $veh      = ints_array($b['vehiculos'] ?? []);
        $sku      = trim($b['sku'] ?? '');
        $costo    = isset($b['costo']) ? floatval($b['costo']) : null;
        $precio   = isset($b['precio']) ? floatval($b['precio']) : null; // opcional (no usado en UI)
        $activo   = isset($b['activo']) ? (int)$b['activo'] : 1;
        $unidad   = trim($b['unidad'] ?? '');
        $base     = trim($b['base'] ?? '');
        $contenido= isset($b['contenido']) ? (string)$b['contenido'] : '1';

        if ($nombre === '') jerr("El nombre es obligatorio");
        if ($min > 0 && $max > 0 && $min > $max) jerr("El stock mínimo no puede ser mayor al máximo");

        $conn->begin_transaction();
        try {
            $st = $conn->prepare("INSERT INTO inventario (nombre, marca, modelo, cantidad, stock_minimo, stock_maximo, sku, costo, precio, activo, base_unidad, presentacion_unidad, presentacion_cantidad)
                                  VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $st->bind_param("sssiiisssisss", $nombre, $marca, $modelo, $cant, $min, $max, $sku, $costo, $precio, $activo, $base, $unidad, $contenido);
            if (!$st->execute()) throw new Exception($st->error);
            $newId = $conn->insert_id;

            if (!empty($veh)) {
                $st2 = $conn->prepare("INSERT INTO inventario_vehiculo (id_inventario, id_vehiculo) VALUES (?,?)");
                foreach ($veh as $vid) {
                    $st2->bind_param("ii", $newId, $vid);
                    if (!$st2->execute()) throw new Exception($st2->error);
                }
            }

            $conn->commit();
            ok(["id"=>$newId]);
        } catch (Throwable $e) {
            $conn->rollback();
            jerr("Error al crear: ".$e->getMessage(), 500);
        }
    }

    case 'update': {
        $b = body_json();
        $id       = intval($b['id'] ?? 0);
        $nombre   = trim($b['nombre'] ?? '');
        $marca    = trim($b['marca'] ?? '');
        $modelo   = trim($b['modelo'] ?? '');
        $cant     = intval($b['cantidad'] ?? 0);
        $min      = intval($b['min'] ?? 0);
        $max      = intval($b['max'] ?? 0);
        $veh      = ints_array($b['vehiculos'] ?? []);
        $sku      = trim($b['sku'] ?? '');
        $costo    = isset($b['costo']) ? floatval($b['costo']) : null;
        $precio   = isset($b['precio']) ? floatval($b['precio']) : null; // opcional
        $activo   = isset($b['activo']) ? (int)$b['activo'] : 1;
        $unidad   = trim($b['unidad'] ?? '');
        $base     = trim($b['base'] ?? '');
        $contenido= isset($b['contenido']) ? (string)$b['contenido'] : '1';

        if ($id <= 0) jerr("ID inválido");
        if ($nombre === '') jerr("El nombre es obligatorio");
        if ($min > 0 && $max > 0 && $min > $max) jerr("El stock mínimo no puede ser mayor al máximo");

        $conn->begin_transaction();
        try {
            $st = $conn->prepare("UPDATE inventario
                                  SET nombre=?, marca=?, modelo=?, cantidad=?, stock_minimo=?, stock_maximo=?, sku=?, costo=?, precio=?, activo=?, base_unidad=?, presentacion_unidad=?, presentacion_cantidad=?
                                  WHERE id=?");
            $st->bind_param("sssiiisssisssi", $nombre, $marca, $modelo, $cant, $min, $max, $sku, $costo, $precio, $activo, $base, $unidad, $contenido, $id);
            if (!$st->execute()) throw new Exception($st->error);

            // Resincronizar vehículos
            $stDel = $conn->prepare("DELETE FROM inventario_vehiculo WHERE id_inventario=?");
            $stDel->bind_param("i", $id);
            if (!$stDel->execute()) throw new Exception($stDel->error);

            if (!empty($veh)) {
                $st2 = $conn->prepare("INSERT INTO inventario_vehiculo (id_inventario, id_vehiculo) VALUES (?,?)");
                foreach ($veh as $vid) {
                    $st2->bind_param("ii", $id, $vid);
                    if (!$st2->execute()) throw new Exception($st2->error);
                }
            }

            $conn->commit();
            ok(["id"=>$id]);
        } catch (Throwable $e) {
            $conn->rollback();
            jerr("Error al actualizar: ".$e->getMessage(), 500);
        }
    }

    case 'delete': {
        $b = body_json();
        $id = intval($b['id'] ?? ($_GET['id'] ?? 0));
        if ($id <= 0) jerr("ID inválido");

        // Pre-chequeo: no permitir si está referenciado en servicios o relación con vehículos
        try {
            $stRef1 = $conn->prepare("SELECT COUNT(*) AS c FROM servicio_insumo WHERE id_inventario=?");
            $stRef1->bind_param("i", $id);
            $stRef1->execute();
            $c1 = (int)($stRef1->get_result()->fetch_assoc()['c'] ?? 0);
            $stRef2 = $conn->prepare("SELECT COUNT(*) AS c FROM inventario_vehiculo WHERE id_inventario=?");
            $stRef2->bind_param("i", $id);
            $stRef2->execute();
            $c2 = (int)($stRef2->get_result()->fetch_assoc()['c'] ?? 0);
            if ($c1 > 0 || $c2 > 0) {
                jerr("No se puede eliminar: el producto está en uso por servicios/vehículos", 409);
            }
        } catch (Throwable $e) {
            // Si falla, continuar y dejar que la FK lo bloquee abajo
        }

        $st = $conn->prepare("DELETE FROM inventario WHERE id=?");
        $st->bind_param("i", $id);
        if (!$st->execute()) {
            $err = $st->error;
            if (stripos($err,'foreign key')!==false || stripos($err,'constraint fails')!==false) {
                jerr("No se puede eliminar: el producto está en uso por servicios", 409);
            }
            jerr("No se pudo eliminar: ".$err, 500);
        }

        // Relaciones caen por ON DELETE CASCADE
        ok(["id"=>$id]);
    }

    default:
        jerr("Acción no soportada", 404);
}
