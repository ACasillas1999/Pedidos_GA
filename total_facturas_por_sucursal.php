<?php
session_name("GA");
session_start();

// Verificar si el usuario no está logeado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    // Si no está logeado, redirigir al formulario de inicio de sesión
    header("location: /Pedidos_GA/Sesion/login.html");
    exit;
}

// Obtener la fecha de inicio y fin desde la solicitud GET
$start_date = $_GET['start_date'];
$end_date = $_GET['end_date'];

// Crear conexión
require_once __DIR__ . "/Conexiones/Conexion.php";

// Consulta SQL para obtener el total de facturas por sucursal en el rango de tiempo especificado,
// agrupado por estado y total de facturas, y la suma de kilometros
$sql = "SELECT 
          SUCURSAL, 
          SUM(CASE WHEN ESTADO = 'Entregado' THEN 1 ELSE 0 END) AS Entregadas, 
          SUM(CASE WHEN ESTADO = 'Cancelado' THEN 1 ELSE 0 END) AS Canceladas, 
          SUM(CASE WHEN ESTADO = 'En Ruta' THEN 1 ELSE 0 END) AS EnRuta, 
          SUM(CASE WHEN ESTADO = 'Activo' THEN 1 ELSE 0 END) AS Activas,
          SUM(CASE WHEN ESTADO = 'En Tienda' THEN 1 ELSE 0 END) AS EnTienda,
          SUM(CASE WHEN ESTADO = 'REPROGRAMADO' THEN 1 ELSE 0 END) AS REPROGRAMADO,
          COUNT(*) AS Total_Facturas,
          SUM(kilometros) AS Total_Kilometros
        FROM pedidos 
        WHERE FECHA_RECEPCION_FACTURA BETWEEN '$start_date' AND '$end_date' 
        GROUP BY SUCURSAL";
$result = $conn->query($sql);

// Array para almacenar los resultados
$data = array();

// Agregar los resultados al array
if ($result->num_rows > 0) {
  $data[] = ['Sucursal', 'Entregadas', 'Canceladas', 'En Ruta', 'Activas','En Tienda','Reprogramado', 'Total Facturas', 'Total Kilometros']; // Encabezado
    
  while($row = $result->fetch_assoc()) {
    $data[] = [
      $row['SUCURSAL'], 
      (int)$row['Entregadas'], 
      (int)$row['Canceladas'], 
      (int)$row['EnRuta'], 
      (int)$row['Activas'],
      (int)$row['EnTienda'],
      (int)$row['REPROGRAMADO'],
      (int)$row['Total_Facturas'],
      (float)$row['Total_Kilometros']
    ];
  }
} else {
  echo "No se encontraron resultados para el rango de tiempo seleccionado.";
}

// Devolver los datos en formato JSON
header('Content-Type: application/json');
echo json_encode($data);

// Cerrar la conexión
$conn->close();
?>

