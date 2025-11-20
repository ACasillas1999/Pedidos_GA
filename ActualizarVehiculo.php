<?php
// Iniciar la sesión de forma segura
ini_set('session.cookie_httponly', true);
ini_set('session.cookie_secure', true);
session_name("GA");
session_start();

// Verificar si el usuario no está logeado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: /Pedidos_GA/Sesion/login.html");
    exit;
}

require_once __DIR__ . "/Conexiones/Conexion.php";

// Obtener el ID del vehículo desde la URL
if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    echo "<script>alert('ID de vehículo inválido.'); window.location.href = 'vehiculos.php';</script>";
    exit;
}

$id_vehiculo = (int) $_GET["id"];

// Consultar la información del vehículo
$query = "SELECT * FROM vehiculos WHERE id_vehiculo = $id_vehiculo";
$resultado = $conn->query($query);

if ($resultado->num_rows === 0) {
    echo "<script>alert('Vehículo no encontrado.'); window.location.href = 'vehiculos.php';</script>";
    exit;
}

$vehiculo = $resultado->fetch_assoc();

// Procesar el formulario de actualización
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["actualizar_vehiculo"])) {
    $numero_serie = $conn->real_escape_string($_POST["numero_serie"]);
    $placa = $conn->real_escape_string($_POST["placa"]);
    $tipo = $conn->real_escape_string($_POST["tipo"]);
    $sucursal = $conn->real_escape_string($_POST["sucursal"]);
    $km_de_servicio = (int) $_POST["km_de_servicio"];
    $km_total = (int) $_POST["km_total"];
    $km_actual = (int) $_POST["km_actual"];

    // Actualizar la información en la base de datos
    $query = "UPDATE vehiculos SET 
                numero_serie='$numero_serie', 
                placa='$placa', 
                tipo='$tipo', 
                Sucursal='$sucursal', 
                Km_de_Servicio=$km_de_servicio, 
                Km_Total=$km_total, 
                Km_Actual=$km_actual 
              WHERE id_vehiculo=$id_vehiculo";

    if ($conn->query($query)) {
        echo "<script>alert('Vehículo actualizado exitosamente.'); window.location.href = 'vehiculos.php';</script>";
    } else {
        echo "<script>alert('Error al actualizar vehículo: " . $conn->error . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actualizar Vehículo</title>
    <link rel="stylesheet" href="styles3.css">
   </head>
<body>

<header class="header">
        <div class="logo"><h3>Actualizar Vehículo</h3></div>
        <nav class="navbar">
            <ul>
                <li class="nav-item"><a href='vehiculos.php' class="nav-link">
                    
                    
                     <img src="/Img/Botones entregas\RegistrarChofer\VOLVAZ.png" alt="Choferes"class = "icono-Volver"style="max-width: 5%; height: auto; position:absolute; top: 70px; left: 35px;">
                    </a></li>
            </ul>
        </nav>
    </header>
    <div >
       

        <!-- Formulario para editar vehículo -->
        <form method="POST">
            <div class="mb-3">
                <label>Número de Serie</label>
                <input type="text" name="numero_serie" class="form-control" required value="<?= htmlspecialchars($vehiculo['numero_serie']) ?>">
            </div>
            <div class="mb-3">
                <label>Placa</label>
                <input type="text" name="placa" class="form-control" required value="<?= htmlspecialchars($vehiculo['placa']) ?>">
            </div>
            <div class="mb-3">
                <label>Tipo</label>
                <input type="text" name="tipo" class="form-control" required value="<?= htmlspecialchars($vehiculo['tipo']) ?>">
            </div>
            <div class="mb-3">
                <label>Sucursal</label>
                <select id="sucursal" name="sucursal" required>
 <option value="">Selecciona una sucursal</option>
 <?php
 $sucursales = ['DIMEGSA', 'DEASA', 'AIESA', 'SEGSA', 'FESA', 'TAPATIA', 'GABSA', 'ILUMINACION', 'VALLARTA', 'CODI', 'QUERETARO'];
 $selectedSucursal = isset($vehiculo['Sucursal']) ? $vehiculo['Sucursal'] : '';

 foreach ($sucursales as $sucursal) {
     $selected = ($sucursal === $selectedSucursal) ? 'selected' : '';
     echo "<option value=\"$sucursal\" $selected>$sucursal</option>";
 }
 ?>
</select><br><br> </div>
            <div class="mb-3">
                <label>Kilometraje de Servicio</label>
                <input type="number" name="km_de_servicio" class="form-control" required value="<?= htmlspecialchars($vehiculo['Km_de_Servicio']) ?>">
            </div>
            <div class="mb-3">
                <label>Kilometraje Total</label>
                <input type="number" name="km_total" class="form-control" required value="<?= htmlspecialchars($vehiculo['Km_Total']) ?>">
            </div>
            <div class="mb-3">
                <label>Km Actual sin servicio</label>
                <input type="number" name="km_actual" class="form-control" required value="<?= htmlspecialchars($vehiculo['Km_Actual']) ?>">
            </div>
            <button type="submit" name="actualizar_vehiculo" class="button">Actualizar Vehículo</button>
           
        </form>
    </div>

   </body>
</html>
