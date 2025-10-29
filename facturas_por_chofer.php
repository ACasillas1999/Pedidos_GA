<?php
session_name("GA");
session_start();

// Verificar si el usuario no está logeado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    // Si no está logeado, redirigir al formulario de inicio de sesión
    header("location: /Pedidos_GA/Sesion/login.html");
    exit;
}

// Obtener la sucursal deseada y el rango de fechas desde la solicitud GET
$sucursal = $_GET['sucursal'];
$start_date = $_GET['start_date'];
$end_date = $_GET['end_date'];

// Crear conexión
require_once __DIR__ . "/Conexiones/Conexion.php";

// Consulta SQL para obtener la cantidad total de facturas y la cantidad de facturas en cada estado para cada chofer en la sucursal y el rango de fechas especificados



$sql = "

SELECT 
    CHOFER_ASIGNADO,
    COUNT(*) AS TotalFacturas,
    SUM(CASE WHEN ESTADO = 'Entregado' THEN 1 ELSE 0 END) AS Entregadas,
    SUM(CASE WHEN ESTADO = 'Cancelado' THEN 1 ELSE 0 END) AS Canceladas,
    SUM(CASE WHEN ESTADO = 'En Ruta' THEN 1 ELSE 0 END) AS EnRuta,
    SUM(CASE WHEN ESTADO = 'Activo' THEN 1 ELSE 0 END) AS Activas,
    SUM(CASE WHEN ESTADO = 'En Tienda' THEN 1 ELSE  0 END) AS EnTienda,
    SUM(CASE WHEN ESTADO = 'REPROGRAMADO' THEN 1 ELSE  0 END) AS REPROGRAMADO,
    SUM(kilometros) AS TotalKilometros
FROM 
    pedidos
WHERE 
    SUCURSAL = '$sucursal' 
    AND FECHA_RECEPCION_FACTURA BETWEEN '$start_date' AND '$end_date' 
GROUP BY 
    CHOFER_ASIGNADO;


";

/*
$sql = "SELECT 
            CHOFER_ASIGNADO,
            COUNT(*) AS TotalFacturas,
            SUM(CASE WHEN ESTADO = 'Entregado' THEN 1 ELSE 0 END) AS Entregadas,
            SUM(CASE WHEN ESTADO = 'Cancelado' THEN 1 ELSE 0 END) AS Canceladas,
            SUM(CASE WHEN ESTADO = 'En Ruta' THEN 1 ELSE 0 END) AS EnRuta,
            SUM(CASE WHEN ESTADO = 'Activo' THEN 1 ELSE 0 END) AS Activas
        FROM 
            Pedidos
        WHERE 
            SUCURSAL = '$sucursal' 
            AND FECHA_RECEPCION_FACTURA BETWEEN '$start_date' AND '$end_date' 
        GROUP BY 
            CHOFER_ASIGNADO";*/



$result = $conn->query($sql);

// Array para almacenar los resultados
$data = array();

// Obtener los resultados de la consulta y agregarlos al array
if ($result->num_rows > 0) {
  while($row = $result->fetch_assoc()) {
    $data[] = array(
      'chofer' => $row['CHOFER_ASIGNADO'],
      'total_facturas' => (int)$row['TotalFacturas'],
      'entregadas' => (int)$row['Entregadas'],
      'canceladas' => (int)$row['Canceladas'],
      'en_ruta' => (int)$row['EnRuta'],
      'activas' => (int)$row['Activas'],
      'En_Tienda' => (int)$row['EnTienda'],
      'REPROGRAMADO' => (int)$row['REPROGRAMADO'],
      'Total_Kilometros' => (int)$row['TotalKilometros']
    );
  }
}

// Devolver los datos en formato JSON
echo json_encode($data);

// Cerrar la conexión
$conn->close();
?>