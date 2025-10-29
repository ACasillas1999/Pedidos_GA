<?php


session_name("GA");
session_start();

// Verificar si el usuario no está logeado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    // Si no está logeado, redirigir al formulario de inicio de sesión
    header("location: /Pedidos_GA/Sesion/login.html");
    exit;
}
// Datos de conexión a la base de datos
require_once __DIR__ . "/Conexiones/Conexion.php";

// Obtener los parámetros de la solicitud
$start_date = $_GET['start_date'];
$end_date = $_GET['end_date'];
$sucursal = $_GET['sucursal'];

// Consulta SQL para obtener la cantidad de facturas por estado para la sucursal seleccionada en el rango de tiempo seleccionado
$sql = "SELECT ESTADO, COUNT(*) AS Cantidad_Facturas FROM pedidos WHERE SUCURSAL = '$sucursal' AND FECHA_RECEPCION_FACTURA BETWEEN '$start_date' AND '$end_date' GROUP BY ESTADO";

$result = $conn->query($sql);

// Array para almacenar los datos del gráfico circular
$data_circular = array();

// Encabezado del array
$data_circular[] = ['Estado', 'Cantidad de Facturas'];

// Verificar si se encontraron resultados
if ($result->num_rows > 0) {
  // Almacenar los datos de la consulta en el array para el gráfico circular
  while($row = $result->fetch_assoc()) {
    $data_circular[] = [$row['ESTADO'], (int)$row['Cantidad_Facturas']];
  }
} else {
  echo "No se encontraron resultados para la sucursal $sucursal en el rango de tiempo seleccionado.";
}

// Cerrar la conexión
$conn->close();

// Devolver los datos en formato JSON
header('Content-Type: application/json');
echo json_encode($data_circular);
?>
