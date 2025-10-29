<?php
session_name("GA");
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: /Pedidos_GA/Sesion/login.html");
    exit;
}

require_once __DIR__ . "/Conexiones/Conexion.php";

if (!isset($_GET['id']) || !isset($_GET['start_date']) || !isset($_GET['end_date'])) {
    echo json_encode(["error" => "No se han proporcionado los parámetros necesarios."]);
    exit;
}

$chofer_username = $_GET['id'];
$start_date = $_GET['start_date'];
$end_date = $_GET['end_date'];

$sql = "SELECT estado, COUNT(*) as cantidad 
        FROM pedidos 
        WHERE chofer_asignado = ? AND fecha_recepcion_factura BETWEEN ? AND ?
        GROUP BY estado";
        
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("sss", $chofer_username, $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    $total = 0;
    while ($row = $result->fetch_assoc()) {
        $data[] = [$row['estado'], (int)$row['cantidad']];
        $total += $row['cantidad'];
    }
    
    echo json_encode(["data" => $data, "total" => $total]);
} else {
    echo json_encode(["error" => "ERROR: Could not prepare query: $sql. " . $conn->error]);
}

$stmt->close();
$conn->close();
?>
