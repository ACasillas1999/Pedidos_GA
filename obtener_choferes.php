<?php
// obtener_choferes.php
header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . "/Conexiones/Conexion.php";

$sucursal = isset($_GET['sucursal']) ? trim($_GET['sucursal']) : '';
if ($sucursal === '') { echo json_encode([]); exit; }

/*
 Tablas:
 - choferes: ID, username, Estado, Sucursal
 - vehiculos: id_vehiculo, id_chofer_asignado, placa, numero_serie, Sucursal
 Regla: devolver TODOS los choferes ACTIVO de la sucursal y marcar si tienen vehículo.
*/
$sql = "
  SELECT
    c.ID,
    c.username,
    c.Sucursal,
    COUNT(v.id_vehiculo) AS n_vehiculos,
    MAX(v.id_vehiculo)   AS id_vehiculo,
    MAX(v.placa)         AS placa,
    MAX(v.numero_serie)  AS numero_serie
  FROM choferes c
  LEFT JOIN vehiculos v
    ON v.id_chofer_asignado = c.ID
  WHERE c.Estado = 'ACTIVO'
    AND c.Sucursal = ?
  GROUP BY c.ID, c.username, c.Sucursal
  ORDER BY c.username ASC
";

$lista = [];
if ($stmt = $conn->prepare($sql)) {
  $stmt->bind_param("s", $sucursal);
  $stmt->execute();
  $res = $stmt->get_result();
  while ($row = $res->fetch_assoc()) {
    $tiene = ((int)$row['n_vehiculos'] > 0);
    $lista[] = [
      "id"             => (int)$row['ID'],
      "username"       => $row['username'],
      "tiene_vehiculo" => $tiene,
      "id_vehiculo"    => $row['id_vehiculo'] ? (int)$row['id_vehiculo'] : null,
      "placa"          => $row['placa'] ?? null,
      "numero_serie"   => $row['numero_serie'] ?? null,
    ];
  }
  $stmt->close();
}

echo json_encode($lista, JSON_UNESCAPED_UNICODE);
