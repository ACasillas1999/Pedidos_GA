<?php
// Conexión a la base de datos
require_once __DIR__ . "/Conexiones/Conexion.php";

// Verificar si se recibió el ID del servicio
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die('ID de servicio no especificado.');
}

$id = intval($_GET['id']); // Sanitización del ID recibido

// Consultar la información del servicio
$query = "SELECT rk.id_registro, rk.id_vehiculo, v.numero_serie, v.placa, v.tipo AS tipo_vehiculo, v.Sucursal,
                 rk.id_chofer, rk.fecha_registro, rk.kilometraje_inicial, rk.kilometraje_final,
                 sd.tipo_servicio, sd.detalles, sd.reiniciar_km, sd.observaciones 
          FROM registro_kilometraje rk
          LEFT JOIN servicios_detalle sd ON rk.id_registro = sd.id_servicio
          LEFT JOIN vehiculos v ON rk.id_vehiculo = v.id_vehiculo
          WHERE rk.id_registro = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die('Servicio no encontrado.');
}

$servicio = $result->fetch_assoc();

// Recomendaciones y beneficios según el tipo de servicio
$recomendaciones = [
    "Afinacion Mayor" => "Realiza una afinación mayor cada 30,000 km para mantener el motor en óptimas condiciones.",
    "Afinacion Menor" => "Haz una afinación menor cada 10,000 km para evitar fallas prematuras.",
    "Revision General" => "Es recomendable realizar una revisión general cada 10,000 km para detectar problemas a tiempo.",
    "Frenos" => "Evita frenadas bruscas y revisa el sistema de frenos cada 5,000 km.",
    "Sistema Electrico" => "Verifica el estado de las conexiones eléctricas y luces cada 6 meses.",
    "Mecanica General" => "Inspecciona el motor y componentes principales cada 20,000 km.",
    "Carroceria y Estetico" => "Mantén en buen estado la carrocería con revisiones periódicas.",
    "Traslados Con Proveedores" => "Asegura la correcta gestión de insumos y partes con proveedores confiables.",
    "Observaciones" => "Cada vehículo debe ser inspeccionado antes de salir del taller."
];

$beneficios = [
    "Afinacion Mayor" => "Mejora el rendimiento del motor y evita fallos prematuros.",
    "Afinacion Menor" => "Asegura un consumo eficiente de combustible y prolonga la vida del motor.",
    "Revision General" => "Previene reparaciones costosas y aumenta la seguridad del vehículo.",
    "Frenos" => "Mejora la seguridad en el frenado y reduce el desgaste de los discos y tambores.",
    "Sistema Electrico" => "Optimiza el rendimiento del sistema eléctrico y evita cortocircuitos.",
    "Mecanica General" => "Mantiene el vehículo en condiciones óptimas y prolonga su vida útil.",
    "Carroceria y Estetico" => "Conserva el buen estado del vehículo y mejora su apariencia.",
    "Traslados Con Proveedores" => "Permite abastecerse de piezas y repuestos necesarios para las reparaciones.",
    "Observaciones" => "Garantiza que el vehículo se entregue en las mejores condiciones posibles."
];


$procedimineto= [
"Afinacion Mayor" => "
•Cambio de bujías, 
•Filtro de aire filtro de aceite 
•Filtro de gasolina y aceite nuevos 
•Relleno de anticongelante
•Líquido de frenos
•Aceite de dirección transmisión y diferencial.",
"Afinacion Menor" => "
•Cambio de aceite
•Nuevo filtro de aceite 
•Limpieza de filtro de aire y bujías
•Revisión de niveles de líquido de frenos
•Aceite de transmisión y diferencial.",
"Revision General" => "En ambos servicios de afinación se revisa también suspensión frenos se lavan y aspiran todas las unidades se engrasan puntos de suspensión.",
"Frenos" => "
•Cambio de balatas delanteras y traseras
•Cambio de zapatas de freno de tambor 
•Cambio de zapatas de freno de mano 
•Cambio de chicotes de freno de mano lubricación y limpieza 
•Purgado de líneas de freno 
•Cambio de bomba de frenos 
•Cambio de mangueras de freno cambio y rectificación de discos y tambores de freno 
•Cambio de repuestos de mordaza de frenos cambio de líquido de frenos.",
"Sistema Electrico" => "
•Cambio de focos principales delanteros traseros de interior 
•Reparacion de cortos reparacion de fallas electricas en sensores y actuadores 
•Cambio de chapas electircas y seguros electricos 
•Revision de alimentaciones a componentes de motor revision y cambio de fusibles y reelevadores
•Reparacion de motor de  limpiaparabrisas cambio e instalacion de bocinas de claxon reloj de claxon palancas de direccionales.",
"Mecanica General" => "
•Cambio de radiadores, 
•Cambio de acumuladores, 
•Cambio de mangueras de agua, 
•Mangueras de gasolina, 
•Mangueras de bomba de direccion hidraulica, 
•Cambio de bombas de agua, 
•Bombas de aceite
•Bombas de gasolina, 
•Cambios de bandas serpentinas, 
•Cambio de bandas y quit de distribucion
•Cambios de clutch, 
•Cambios de cilindros esclavos de clutch, 
•Cambio de bombas de frenos cambio de termostatos, 
•Tomas de agua correccion de fugas de aceite de motor transmision diferenciales, 
•Fugas de anticongelante,
•Fugas de liquido de frenos cambios de retenes de flechas de transmision, 
•Retenes de diferencial,
•Retenes de cigüeñal,
•Retenes de árboles de levas,
•Cambios de crucetas de flecha cardan y soportes de flecha cardan 
•Cambio de soportes de motor y de transmision 
•Cambio de flechas de transmision mantenimiento preventivo a diferencial 
•Cambio de mazas baleros de ruedas delanteros y traseras 
•Reparación de fallas mecanicas por desgaste de componentes revisión y reemplazo de sensores y actuadores tales como , el sensor maf sensor de oxigeno sensor de cigueñal sensor de detonacion sensores abs sensores srs sensor de precion de aciete sensores te temperatura, sensores de mot ventilador de radiador mantenimiento y reemplazo de inyectores, 
•Reparación de pedales de freno, embrague y acelerador 
•Cambios de chicotes de acelerador limpieza y calibracion de cuerpos de aceleracion 
•Reparacion de fugas de vacio en multiole de admision y escape 
•Revisión de catalizadores diagnóstico de valvula egr, valvulas vvt, valvula iac, valvula pcv, deposito del canister , valvula de canisterreparacion de carburadores, 
•Cambios de empaques de tapas de punterias carter y difrencial empacado de motores 
•Cambio de enfriadores de aceite, depósitos recuperadores purgado de líneas de anticongelante y cambios de bombas de dirección  
•Recoger unidades descompuestas.",
"Carroceria y Estetico" => "
•Cambio de facias y defensas,
•Cambio de faros y micas de calaveras,
•Cambio de manijas de puertas chapas y elevadores cambio de limpiaparabrisas 
•Cambios de parrillas y cuartos 
•Cambios de molduras grapas cañuelas de ventanas empaques de puertas,cofre y cajuela 
•Cambio de amortiguadores de cofre y cajuela 
•Cambio de asientos dañados cambio de manijas de ventanas, 
•Cambio e instalación de espejos latreles y retrovisores reparacion de burreras 
•Reparacion de golpes minimos en carroceria.",
"Traslados Con Proveedores" => "Muelles ponce auto eléctrico miguel carrocerías rojo carrocerías duran mofles  y radiadores toshi artic frost aires acondicionados.",
"Observaciones" => "Toda unidad se inspecciona al momento de entrar al taller, no se realiza nada más la afinación  se inspecciona siempre la unidad de suspensión, frenos y luces si al momento de estar trabajando se encuentran mas piezas dañadas se reemplazan en el mismo servicio y todas las unidades se entregan aspiradas y lavadas tanto motor carrocería y chasis y se realiza lubricado de puntos que necesiten lubricación."
];

$Tiempo_Requerido_Aproximado = [
"Afinacion Mayor" => "1 Dia Mas Suspension Frenos y Lavado",
"Afinacion Menor" => "1/2 Dia Solo Afinacion Menor y Lavado",
"Revision General" => "1/2 Dia mas si requiere mas reparaciones",
"Frenos" => "1/2 Dia si solo es el servicio de frenos con discos o tambores rectificados mas limpieza y lubricado",
"Sistema Electrico" => "1-2 Dias dependiendo de la gravedad de la falla con diagnostico de 3 horas",
"Mecanica General" => "1-3 Dias dependiendo de la dificultad de la reparacion y disponibilidad de piezas con un diagnostico de 2 a 3 horas realizando inspeccion general de componentes hermanados a la falla y dependiendo de los pagos de las piezas ",
"Carroceria y Estetico" => "1/2 - 1 Dia dependiendo de la disponiblidad de piezas",
"Traslados Con Proveedores" => "1-2 Horas Tiempo de traslado",
"Observaciones" => "1-3 Horas Tiempo de Diagnostico"
];

    $tipo_servicio = $servicio['tipo_servicio'];
    $recomendacion = $recomendaciones[$tipo_servicio] ?? "Mantén un mantenimiento regular para evitar problemas inesperados.";
    $beneficio = $beneficios[$tipo_servicio] ?? "Este mantenimiento ayuda a optimizar el rendimiento del vehículo.";
    $tiempo_requerido = $Tiempo_Requerido_Aproximado[$tipo_servicio]?? "No hay información disponible.";
    $procedimiento = $procedimineto[$tipo_servicio]?? "No hay información disponible.";
?>

         
            
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle del Servicio</title>
    <link rel="stylesheet" href="styles7.css">
<<<<<<< HEAD
    <link rel="icon" type="image/png" href="/Img/Botones%20entregas/ICONOSPAG/ICONOPEDIDOS.png"> 
=======
    <link rel="icon" type="image/png" href="/Pedidos_GA/Img/Botones%20entregas/ICONOSPAG/ICONOPEDIDOS.png"> 
>>>>>>> parent of 5e8b02c (parra amazon Update image paths and SQL table names)
   
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 800px;
            margin: 50px auto;
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            text-align: center;
            color: #333;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .info-box {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
        }
        .highlight {
            padding: 15px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            text-align: left;
            color: white;
            grid-column: span 3;
        }

        .highlight.recommendation {
            background: #3498db;
         } 
        .highlight.benefit { 
            background: #2ecc71; } 
        .highlight.time { 
            background: #f39c12; } 
        .highlight.procedure {
            background: #7f8c8d; }
    </style>
</head>
<body>

<header class="header">
        <div class="logo">
    
<a>Servicio</a>
      <!--  <img src="\Pedidos_GA\Img\Botones entregas\RegistrarChofer\REGCHOFTIT.png" alt="Estadísticas " style="max-width: 15%; height: auto;">
    -->
    </div>
        <nav class="navbar">
            <ul>
                <li class="nav-item"><a href='detalles_vehiculo.php?id=<?= htmlspecialchars($servicio['id_vehiculo']) ?>' class="nav-link">

                <img src="\Pedidos_GA\Img\Botones entregas\RegistrarChofer\VOLVAZ.png" alt="Choferes"class = "icono-Volver"style="max-width: 5%; height: auto; position:absolute; top: 70px; left: 25px;">
               </a></li>
               <p></p>
            </ul>
        </nav>
    </header>
    <div class="container">
        <h1>Información del Servicio</h1>
        <h2>-<?= htmlspecialchars($servicio['tipo_servicio']) ?>-</h2>
        <p></p>
        <div class="info-grid">
            <div class="info-box"><strong>ID Registro:</strong> <?= htmlspecialchars($servicio['id_registro']) ?></div>
            <div class="info-box"><strong>Vehículo:</strong> <?= htmlspecialchars($servicio['numero_serie']) ?> - <?= htmlspecialchars($servicio['placa']) ?> (<?= htmlspecialchars($servicio['tipo_vehiculo']) ?>)</div>
            <div class="info-box"><strong>Sucursal:</strong> <?= htmlspecialchars($servicio['Sucursal']) ?></div>
            <div class="info-box"><strong>ID Chofer:</strong> <?= htmlspecialchars($servicio['id_chofer']) ?></div>
            <div class="info-box"><strong>Fecha de Registro:</strong> <?= htmlspecialchars($servicio['fecha_registro']) ?></div>
            <div class="info-box"><strong>Kilometraje Inicial:</strong> <?= htmlspecialchars($servicio['kilometraje_inicial']) ?></div>
            <div class="info-box"><strong>Kilometraje Final:</strong> <?= htmlspecialchars($servicio['kilometraje_final']) ?></div>
            <div class="info-box"><strong>Tipo de Servicio:</strong> <?= htmlspecialchars($servicio['tipo_servicio']) ?></div>
            <div class="info-box"><strong>Detalles:</strong> <?= htmlspecialchars($servicio['detalles']) ?></div>
            <div class="info-box"><strong>Observaciones:</strong> <?= htmlspecialchars($servicio['observaciones']) ?></div>
            <div class="info-box"><strong>Reinició Kilometraje:</strong> <?= $servicio['reiniciar_km'] ? 'Sí' : 'No' ?></div>
        </div>

        <p></p>
        <hr></hr>
        <div class="info-grid">
        <div class="highlight recommendation"><strong>Recomendación:</strong> <?= $recomendacion ?></div>
        <div class="highlight benefit"><strong>Beneficio:</strong> <?= $beneficio ?></div>
        <div class="highlight time"><strong>Tiempo Requerido Aproximado:</strong> <?= $tiempo_requerido ?></div>
        <div class="highlight procedure"><strong>Procedimiento:</strong> <?= nl2br(htmlspecialchars($procedimiento)) ?></div>
        </div>
    </div>
</body>
</html>
