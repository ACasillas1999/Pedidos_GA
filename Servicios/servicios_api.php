<?php
ini_set('session.cookie_httponly', true);
ini_set('session.cookie_secure', true);
session_name("GA");
session_start();

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . "/../Conexiones/Conexion.php";
if (!isset($conn) || !($conn instanceof mysqli)) {
  http_response_code(500);
  echo json_encode(["ok"=>false, "error"=>"Sin conexión a BD"]);
  exit;
}
$conn->set_charset("utf8mb4");

function jerr($msg,$code=400){ http_response_code($code); echo json_encode(["ok"=>false,"error"=>$msg]); exit; }
function ok($data=null){ echo json_encode(["ok"=>true,"data"=>$data]); exit; }
function body_json(){
  $raw=file_get_contents('php://input');
  if($raw==='') return [];
  $j=json_decode($raw,true);
  if(!is_array($j)) jerr("JSON inválido");
  return $j;
}
function ints_array($arr){ if(!is_array($arr)) return []; return array_values(array_unique(array_map('intval',$arr))); }
function norm_materiales($arr){
  // Espera: [{id_inventario, cantidad}, ...]
  $out=[];
  if(is_array($arr)){
    foreach($arr as $x){
      $id  = intval($x['id_inventario'] ?? 0);
      $qty = isset($x['cantidad']) ? (float)$x['cantidad'] : 0;
      if($id>0 && $qty>0) $out[]=['id_inventario'=>$id,'cantidad'=>$qty];
    }
  }
  return $out;
}

$action = $_GET['action'] ?? 'list';

switch ($action) {
  case 'config': {
    // Configuración para cálculo automático de mano de obra
    // Busca costo por minuto en tabla servicios_config (clave: costo_minuto_mo)
    // Fallback por defecto: $11,000 / (160 h * 60) = 1.145833
    $costoMinuto = 1.145833;
    try {
      $res = $conn->query("SELECT valor FROM servicios_config WHERE clave='costo_minuto_mo' LIMIT 1");
      if ($res && ($r = $res->fetch_assoc())) {
        $costoMinuto = (float)$r['valor'];
      }
    } catch (Throwable $e) {
      // tabla no existe o error: devolvemos 0.0
    }
    ok(['costo_minuto_mo' => $costoMinuto]);
  }
  case 'vehiculos': {
    $res = $conn->query("SELECT id_vehiculo AS id, placa, tipo, Sucursal AS suc
                         FROM vehiculos ORDER BY Sucursal, placa");
    $rows=[];
    while($r=$res->fetch_assoc()){
      $r['id']=(int)$r['id'];
      $r['label']=trim(($r['placa']?:'SN-PLACA').' · '.$r['tipo'].' · '.$r['suc']);
      $rows[]=$r;
    }
    ok($rows);
  }

  case 'inventario': { // catálogo de materiales (incluye costo/stock/presentación)
    $res = $conn->query("SELECT id, nombre, marca, modelo, costo, cantidad, stock_minimo, base_unidad, presentacion_unidad, presentacion_cantidad FROM inventario ORDER BY nombre");
    $rows=[];
    while($r=$res->fetch_assoc()){
      $label = trim($r['nombre'].
          ($r['marca']?' · '.$r['marca']:'').
          ($r['modelo']?' · '.$r['modelo']:'').
          ((isset($r['presentacion_cantidad']) && $r['presentacion_cantidad']>0 && !empty($r['presentacion_unidad']))
            ? (' · '.$r['presentacion_cantidad'].' '.$r['presentacion_unidad']) : ''));
      $rows[]=[
        'id'=>(int)$r['id'],
        'nombre'=>$r['nombre'],
        'marca'=>$r['marca'],
        'modelo'=>$r['modelo'],
        'costo'=> isset($r['costo']) ? (float)$r['costo'] : 0,
        'unidad'=> $r['presentacion_unidad'] ?? null,
        'base'=> $r['base_unidad'] ?? null,
        'contenido'=> isset($r['presentacion_cantidad']) ? (float)$r['presentacion_cantidad'] : null,
        'cantidad'=> isset($r['cantidad']) ? (int)$r['cantidad'] : 0,
        'stock_minimo'=> isset($r['stock_minimo']) ? (int)$r['stock_minimo'] : 0,
        'label'=>$label
      ];
    }
    ok($rows);
  }

  case 'list': {
    $sql = "SELECT s.id, s.nombre, s.duracion_minutos, s.costo_mano_obra, s.precio,
                   GROUP_CONCAT(DISTINCT sv.id_vehiculo) AS vehs,
                   GROUP_CONCAT(DISTINCT CONCAT(si.id_inventario, ':', si.cantidad) SEPARATOR '|') AS mats,
                   (
                     SELECT COALESCE(SUM(si2.cantidad * COALESCE(i.costo,0)),0)
                     FROM servicio_insumo si2
                     JOIN inventario i ON i.id = si2.id_inventario
                     WHERE si2.id_servicio = s.id
                   ) AS costo_materiales
            FROM servicios s
            LEFT JOIN servicio_vehiculo sv ON sv.id_servicio = s.id
            LEFT JOIN servicio_insumo  si ON si.id_servicio  = s.id
            GROUP BY s.id
            ORDER BY s.id DESC";
    $res=$conn->query($sql);
    $rows=[];
    while($r=$res->fetch_assoc()){
      $veh=[]; if(!empty($r['vehs'])) foreach(explode(',',$r['vehs']) as $v){ if($v!=='') $veh[]=(int)$v; }
      $mats=[];
      if(!empty($r['mats'])){
        foreach(explode('|',$r['mats']) as $p){
          if(strpos($p,':')!==false){
            list($iid,$qty)=explode(':',$p,2);
            $iid=intval($iid); $qty=(float)$qty;
            if($iid>0 && $qty>0) $mats[]=['id_inventario'=>$iid,'cantidad'=>$qty];
          }
        }
      }
      $rows[]=[
        'id'=>(int)$r['id'],
        'nombre'=>$r['nombre'],
        'duracion_minutos'=>(int)$r['duracion_minutos'],
        'costo_mano_obra'=> isset($r['costo_mano_obra']) ? (float)$r['costo_mano_obra'] : 0,
        'precio'=> isset($r['precio']) ? (float)$r['precio'] : 0,
        'costo_materiales'=> isset($r['costo_materiales']) ? (float)$r['costo_materiales'] : 0,
        'vehiculos'=>$veh,
        'materiales'=>$mats
      ];
    }
    ok($rows);
  }

  case 'get': {
    $id=intval($_GET['id']??0);
    if($id<=0) jerr("ID inválido");
    $st=$conn->prepare("SELECT id,nombre,duracion_minutos,costo_mano_obra,precio FROM servicios WHERE id=? LIMIT 1");
    $st->bind_param("i",$id); $st->execute();
    $s=$st->get_result()->fetch_assoc(); if(!$s) jerr("No encontrado",404);

    $veh=[]; $res=$conn->query("SELECT id_vehiculo FROM servicio_vehiculo WHERE id_servicio=".$id);
    while($v=$res->fetch_assoc()) $veh[]=(int)$v['id_vehiculo'];

    $mat=[]; $res2=$conn->query("SELECT id_inventario,cantidad FROM servicio_insumo WHERE id_servicio=".$id);
    while($m=$res2->fetch_assoc()) $mat[]=['id_inventario'=>(int)$m['id_inventario'],'cantidad'=>(float)$m['cantidad']];

    ok([
      'id'=>(int)$s['id'],
      'nombre'=>$s['nombre'],
      'duracion_minutos'=>(int)$s['duracion_minutos'],
      'costo_mano_obra'=> isset($s['costo_mano_obra']) ? (float)$s['costo_mano_obra'] : 0,
      'precio'=> isset($s['precio']) ? (float)$s['precio'] : 0,
      'vehiculos'=>$veh,
      'materiales'=>$mat
    ]);
  }

  case 'create': {
    $b=body_json();
    $nombre=trim($b['nombre']??'');
    $dur   =intval($b['duracion_minutos']??0);
    $cmo   =isset($b['costo_mano_obra']) ? (float)$b['costo_mano_obra'] : 0;
    $precio=isset($b['precio']) ? (float)$b['precio'] : 0;
    $veh   =ints_array($b['vehiculos']??[]);
    $mat   =norm_materiales($b['materiales']??[]);
    if($nombre==='') jerr("El nombre es obligatorio");
    if($dur<0) $dur=0;

    // Validación de insumos: existencia, activo y stock suficiente
    $faltantes = [];
    foreach ($mat as $m) {
      $iid = (int)$m['id_inventario'];
      $qty = (float)$m['cantidad'];
      if ($iid <= 0 || $qty <= 0) continue;
      $stC = $conn->prepare("SELECT nombre, cantidad, activo FROM inventario WHERE id=? LIMIT 1");
      $stC->bind_param("i", $iid); $stC->execute(); $ri = $stC->get_result()->fetch_assoc();
      if (!$ri) { $faltantes[] = "ID $iid no existe"; continue; }
      if (isset($ri['activo']) && (int)$ri['activo'] !== 1) { $faltantes[] = ($ri['nombre']?:"ID $iid")." inactivo"; continue; }
      $disp = isset($ri['cantidad']) ? (float)$ri['cantidad'] : 0.0;
      if ($disp < $qty) { $faltantes[] = ($ri['nombre']?:"ID $iid")." insuficiente (disp: ".$disp.", req: ".$qty.")"; }
    }
    if (!empty($faltantes)) {
      jerr("No se puede crear el servicio: materiales sin stock o inválidos: ".implode('; ', $faltantes), 409);
    }

    $conn->begin_transaction();
    try{
      $st=$conn->prepare("INSERT INTO servicios (nombre,duracion_minutos,costo_mano_obra,precio) VALUES (?,?,?,?)");
      $st->bind_param("sidd",$nombre,$dur,$cmo,$precio);
      if(!$st->execute()) throw new Exception($st->error);
      $newId=$conn->insert_id;

      if(!empty($veh)){
        $st2=$conn->prepare("INSERT INTO servicio_vehiculo (id_servicio,id_vehiculo) VALUES (?,?)");
        foreach($veh as $vid){ $st2->bind_param("ii",$newId,$vid); if(!$st2->execute()) throw new Exception($st2->error); }
      }
      if(!empty($mat)){
        $st3=$conn->prepare("INSERT INTO servicio_insumo (id_servicio,id_inventario,cantidad) VALUES (?,?,?)");
        foreach($mat as $m){ $iid=(int)$m['id_inventario']; $qty=(float)$m['cantidad']; $st3->bind_param("iid",$newId,$iid,$qty); if(!$st3->execute()) throw new Exception($st3->error); }
      }
      $conn->commit(); ok(['id'=>$newId]);
    }catch(Throwable $e){ $conn->rollback(); jerr("Error al crear: ".$e->getMessage(),500); }
  }

  case 'update': {
    $b=body_json();
    $id   =intval($b['id']??0);
    $nombre=trim($b['nombre']??'');
    $dur   =intval($b['duracion_minutos']??0);
    $cmo   =isset($b['costo_mano_obra']) ? (float)$b['costo_mano_obra'] : 0;
    $precio=isset($b['precio']) ? (float)$b['precio'] : 0;
    $veh   =ints_array($b['vehiculos']??[]);
    $mat   =norm_materiales($b['materiales']??[]);
    if($id<=0) jerr("ID inválido");
    if($nombre==='') jerr("El nombre es obligatorio");
    if($dur<0) $dur=0;

    // Validación de insumos en update
    $faltantes = [];
    foreach ($mat as $m) {
      $iid = (int)$m['id_inventario'];
      $qty = (float)$m['cantidad'];
      if ($iid <= 0 || $qty <= 0) continue;
      $stC = $conn->prepare("SELECT nombre, cantidad, activo FROM inventario WHERE id=? LIMIT 1");
      $stC->bind_param("i", $iid); $stC->execute(); $ri = $stC->get_result()->fetch_assoc();
      if (!$ri) { $faltantes[] = "ID $iid no existe"; continue; }
      if (isset($ri['activo']) && (int)$ri['activo'] !== 1) { $faltantes[] = ($ri['nombre']?:"ID $iid")." inactivo"; continue; }
      $disp = isset($ri['cantidad']) ? (float)$ri['cantidad'] : 0.0;
      if ($disp < $qty) { $faltantes[] = ($ri['nombre']?:"ID $iid")." insuficiente (disp: ".$disp.", req: ".$qty.")"; }
    }
    if (!empty($faltantes)) {
      jerr("No se puede actualizar el servicio: materiales sin stock o inválidos: ".implode('; ', $faltantes), 409);
    }

    $conn->begin_transaction();
    try{
      $st=$conn->prepare("UPDATE servicios SET nombre=?, duracion_minutos=?, costo_mano_obra=?, precio=? WHERE id=?");
      $st->bind_param("siddi",$nombre,$dur,$cmo,$precio,$id);
      if(!$st->execute()) throw new Exception($st->error);

      $conn->query("DELETE FROM servicio_vehiculo WHERE id_servicio=".$id);
      $conn->query("DELETE FROM servicio_insumo  WHERE id_servicio=".$id);

      if(!empty($veh)){
        $st2=$conn->prepare("INSERT INTO servicio_vehiculo (id_servicio,id_vehiculo) VALUES (?,?)");
        foreach($veh as $vid){ $st2->bind_param("ii",$id,$vid); if(!$st2->execute()) throw new Exception($st2->error); }
      }
      if(!empty($mat)){
        $st3=$conn->prepare("INSERT INTO servicio_insumo (id_servicio,id_inventario,cantidad) VALUES (?,?,?)");
        foreach($mat as $m){ $iid=(int)$m['id_inventario']; $qty=(float)$m['cantidad']; $st3->bind_param("iid",$id,$iid,$qty); if(!$st3->execute()) throw new Exception($st3->error); }
      }
      $conn->commit(); ok(['id'=>$id]);
    }catch(Throwable $e){ $conn->rollback(); jerr("Error al actualizar: ".$e->getMessage(),500); }
  }

  case 'delete': {
    $b=body_json(); $id=intval($b['id'] ?? ($_GET['id']??0));
    if($id<=0) jerr("ID inválido");
    $st=$conn->prepare("DELETE FROM servicios WHERE id=?");
    $st->bind_param("i",$id);
    if(!$st->execute()) jerr("No se pudo eliminar: ".$st->error,500);
    ok(['id'=>$id]);
  }

  default: jerr("Acción no soportada",404);
}
