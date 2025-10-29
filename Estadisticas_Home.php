<?php
// Iniciar la sesión
session_name("GA");
session_start();


// Verificar si el usuario no está logeado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    // Si no está logeado, redirigir al formulario de inicio de sesión
    header("location: /Pedidos_GA/Sesion/login.html");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Pedidos GA</title>
   <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
   <link rel="icon" type="image/png" href="/Pedidos_GA/Img/Botones%20entregas/ICONOSPAG/ICONOPEDIDOS.png">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
  <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    
     <!-- jsPDF -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.3.1/jspdf.umd.min.js"></script>

<!-- jsPDF AutoTable -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.16/jspdf.plugin.autotable.min.js"></script>


  

  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/0.4.1/html2canvas.min.js"></script>
  

  
  <link rel="stylesheet" href="styles4.css">
    
  
   <script type="text/javascript">
    google.charts.load('current', {'packages':['table']});
    google.charts.setOnLoadCallback(drawTables);

    function drawTables() {
      // Dibujar tablas al cargar la página con los valores predeterminados
      drawTable('AIESA', 'table_div_aiesa');
      drawTable('DEASA', 'table_div_deasa');
        drawTable('GABSA', 'table_div_gabsa');
        drawTable('ILUMINACION', 'table_div_ilu');
        drawTable('DIMEGSA', 'table_div_dimegsa');
        drawTable('SEGSA', 'table_div_segsa');
        drawTable('FESA', 'table_div_fesa');
        drawTable('TAPATIA', 'table_div_tapatia');
        drawTable('VALLARTA', 'table_div_vallarta');
        drawTable('CODI', 'table_div_codi');
        drawTable('QUERETARO', 'table_div_queretaro');
      // Agrega más llamadas a drawTable para otras sucursales si es necesario
    }

function drawTable(sucursal, containerId) {
  var startDate = $('#start_date').val();
  var endDate = $('#end_date').val();
  $.ajax({
    url: 'facturas_por_chofer.php', // Ruta al archivo PHP que obtiene los datos para la sucursal especificada
    type: 'GET',
    dataType: 'json',
    data: { sucursal: sucursal, start_date: startDate, end_date: endDate }, // Pasar también el intervalo de tiempo
    success: function(data) {
      var dataTable = new google.visualization.DataTable();
      dataTable.addColumn('string', 'Chofer');
      
      dataTable.addColumn('number', 'Total Facturas');
      dataTable.addColumn('number', 'Total Kilometros');
      dataTable.addColumn('number', 'Entregadas');
      dataTable.addColumn('number', 'Canceladas');
      dataTable.addColumn('number', 'En Ruta');
      dataTable.addColumn('number', 'En Tienda');
      dataTable.addColumn('number', 'Reprogramado');
      dataTable.addColumn('number', 'Activas');
      

      // Agregar filas desde los datos obtenidos
      $.each(data, function(index, row) {
        dataTable.addRow([
          row.chofer,
          parseInt(row.total_facturas),
          parseInt(row.Total_Kilometros),
          parseInt(row.entregadas),
          parseInt(row.canceladas),
          parseInt(row.en_ruta),
          parseInt(row.En_Tienda),
          parseInt(row.REPROGRAMADO),   
          parseInt(row.activas)
         
        ]);
      });

      var table = new google.visualization.Table(document.getElementById(containerId));
      table.draw(dataTable, {showRowNumber: true, width: '100%', height: '100%'});
    },
    error: function(xhr, status, error) {
      console.error("Error al obtener los datos para la tabla:", error);
    }
  });
}
       
       
  </script>
    
    
    
   <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<script type="text/javascript">
  google.charts.load('current', {'packages':['bar']});
  google.charts.setOnLoadCallback(drawTotalColumnChart);

  function drawTotalColumnChart() {
    var startDate = $('#start_date').val();
    var endDate = $('#end_date').val();
    $.ajax({
      url: 'total_facturas_por_sucursal.php', // Ruta al archivo PHP que obtiene los datos del total de facturas
      type: 'GET',
      dataType: 'json',
      data: { start_date: startDate, end_date: endDate },
      success: function(data) {
        console.log("Datos recibidos del servidor:", data); // Verificar los datos recibidos
        if (data.length > 1) { // Verificar si se recibieron datos válidos
          var jsonData = data.map(row => row.slice(0, 7)); // Eliminar la columna de kilómetros para la gráfica
          var dataChart = google.visualization.arrayToDataTable(jsonData);

          var options = {
            chart: {
              title: 'Total de Facturas por Sucursal en el Rango de Tiempo Seleccionado',
              subtitle: 'Facturas por Sucursal y Estado', // Subtitulo personalizado
            },
            bars: 'horizontal', // Barra horizontal
            legend: { position: 'top' }, // Leyenda arriba
            width: '100%', // Ancho completo
            height: '400', // Altura fija
           // colors: ['#3366CC'], // Color de las barras
            colors: ['#2ca02c','#d62728','#1f77b4','#bcbd22','#ff7f0e','#123','#321']
          };

          var chart = new google.charts.Bar(document.getElementById('total_column_chart'));
          chart.draw(dataChart, google.charts.Bar.convertOptions(options));

          // Crear el resumen
          var summary = $('<table>').addClass('summary-table');
          var headerRow = $('<tr>').append(
            $('<th>').text('Sucursal'),
                $('<th>').text('Total de Facturas'),
            $('<th>').text('Kilometros'),
          
            $('<th>').text('Entregadas'),
            $('<th>').text('Canceladas'),
            $('<th>').text('En Ruta'),
            
            $('<th>').text('Activas'),
            $('<th>').text('En Tienda'),
            $('<th>').text('Reprogramado')

          );
          summary.append(headerRow);

          data.forEach(function(row, index) {
            if (index > 0) { // Saltar el encabezado
              var rowElement = $('<tr>').append(
                $('<td>').text(row[0]),
                $('<td>').text(row[7]), // Kilometros
                $('<td>').text(row[8]), // Total de Facturas en la última columna
                  
                $('<td>').text(row[1]),
                $('<td>').text(row[2]),
                $('<td>').text(row[3]),
                $('<td>').text(row[4]),
                $('<td>').text(row[5]),
                $('<td>').text(row[6]),
              
              );
              summary.append(rowElement);
            }
          });

          $('#summary_container').empty().append(summary);
        } else {
          console.error("Error: No se recibieron datos válidos del servidor.");
        }
      },
      error: function(xhr, status, error) {
        console.error("Error al obtener los datos para el gráfico de barras:", error);
      }
    });
  }
</script>



    
    
  <script type="text/javascript">
    google.charts.load('current', {'packages':['corechart']});
    google.charts.setOnLoadCallback(drawCharts);
   
    function drawCharts() {
      drawChart('AIESA', 'piechart1', 'Cantidad de Facturas de la Sucursal AIESA por Estado en el Rango de Tiempo Seleccionado');
      drawChart('DEASA', 'piechart2', 'Cantidad de Facturas de la Sucursal DEASA por Estado en el Rango de Tiempo Seleccionado');
      drawChart('DIMEGSA', 'piechart3', 'Cantidad de Facturas de la Sucursal DIMEGSA por Estado en el Rango de Tiempo Seleccionado');
        drawChart('GABSA', 'piechart4', 'Cantidad de Facturas de la Sucursal GABSA por Estado en el Rango de Tiempo Seleccionado');
         drawChart('ILUMINACION', 'piechart5', 'Cantidad de Facturas de la Sucursal ILUMNACION por Estado en el Rango de Tiempo Seleccionado');
        drawChart('SEGSA', 'piechart6', 'Cantidad de Facturas de la Sucursal SEGSA por Estado en el Rango de Tiempo Seleccionado');
         drawChart('FESA', 'piechart7', 'Cantidad de Facturas de la Sucursal FESA por Estado en el Rango de Tiempo Seleccionado');
         drawChart('TAPATIA', 'piechart8', 'Cantidad de Facturas de la Sucursal TAPATIA por Estado en el Rango de Tiempo Seleccionado');
         drawChart('VALLARTA', 'piechart9', 'Cantidad de Facturas de la Sucursal VALLARTA por Estado en el Rango de Tiempo Seleccionado');
         drawChart('CODI', 'piechart10', 'Cantidad de Facturas de la Sucursal CODI por Estado en el Rango de Tiempo Seleccionado');
         drawChart('QUERETARO', 'piechart11', 'Cantidad de Facturas de la Sucursal QUERETARO por Estado en el Rango de Tiempo Seleccionado');
    }

 
      
    function drawChart(sucursal, chartId, chartTitle) {
      var startDate = $('#start_date').val();
      var endDate = $('#end_date').val();
      $('#time_range').text('Rango de tiempo seleccionado: ' + startDate + ' - ' + endDate);
      $.ajax({
        url: 'Estadisticas.php',
        type: 'GET',
        dataType: 'json',
        data: { start_date: startDate, end_date: endDate, sucursal: sucursal },
        success: function(data) {
          var jsonData = data;
          var dataChart = google.visualization.arrayToDataTable(jsonData);
          var options = {
            title: chartTitle,
            chartArea: {width: '80%', height: '70%'},
            legend: {position: 'right'}
          };
          var chart = new google.visualization.PieChart(document.getElementById(chartId));
          chart.draw(dataChart, options);
        }
      });
    }
      
      
      
      
      
function actualizarTablas() {
    drawTables(); // Llama a la función para dibujar las tablas
}

      

   $(function() {
    // Establecer fechas predeterminadas (último mes)
    var endDate = new Date();
    var startDate = new Date(endDate);
    startDate.setMonth(startDate.getMonth() - 1);

    // Establecer valores iniciales en los campos ocultos
    $('#start_date').val(startDate.toISOString().slice(0, 10));
    $('#end_date').val(endDate.toISOString().slice(0, 10));

    // Dibujar gráficas y tablas con los valores iniciales
    drawCharts();
    drawTables();

    $("#date_range_slider").slider({
        range: true,
        min: 0,
        max: 365,
        step: 1,
        values: [0, 30],
        slide: function(event, ui) {
            var startDate = new Date();
            startDate.setDate(startDate.getDate() - ui.values[1]);
            var endDate = new Date();
            endDate.setDate(endDate.getDate() - ui.values[0]);
            $('#start_date').val(startDate.toISOString().slice(0, 10));
            $('#end_date').val(endDate.toISOString().slice(0, 10));
            drawCharts();
            drawTables(); // Actualizar las tablas también al cambiar el rango de fechas
            drawTotalColumnChart();
        }
    });
});
  </script>
  <style>
    .chart-container {
      width: 100%;
      margin-bottom: 20px;
    }
    .chart-title {
      font-size: 18px;
      font-weight: bold;
      margin-bottom: 10px;
    }
  </style>
</head>


<script>
   document.addEventListener("DOMContentLoaded", function() {  
    var iconoVolver = document.querySelector(".icono-Volver");
    var iconoImprimir = document.querySelector(".icono-Imprimir");

    var imgNormalVolver = "/Pedidos_GA/Img/Botones%20entregas/RegistrarChofer/VOLVAZ.png";
    var imgHoverVolver = "/Pedidos_GA/Img/Botones%20entregas/RegistrarChofer/VOLVNA.png";

    // Cambiar la imagen al pasar el mouse para Volver
    if (iconoVolver) {
        iconoVolver.addEventListener("mouseover", function() {
            iconoVolver.src = imgHoverVolver;
        });

        iconoVolver.addEventListener("mouseout", function() {
            iconoVolver.src = imgNormalVolver;
        });
    }

    var imgNormalImprimir = "/Pedidos_GA/Img/Botones%20entregas/Estadisticas/IMPAZ.png";
    var imgHoverImprimir = "/Pedidos_GA/Img/Botones%20entregas/Estadisticas/IMPNA.png";

    // Cambiar la imagen al pasar el mouse para FAP
    if (iconoImprimir) {
      iconoImprimir.addEventListener("mouseover", function() {
        iconoImprimir.src = imgHoverImprimir;
        });

        iconoImprimir.addEventListener("mouseout", function() {
          iconoImprimir.src = imgNormalImprimir;
        });
    }
});

    </script>
    
<body>
    <header class="header">
        <div class="logo">
            <img src="/Pedidos_GA/Img/Botones%20entregas/Estadisticas/ESTADISTICAS.png" alt="iconoVPedidos "class= "icono-registro" style="max-width: 25%; height: auto; ">
        </div>
        <nav class="navbar">
            <ul>
                <li class="nav-item"><a href='Pedidos_GA.php' class="nav-link">
                  
                <img src="\Pedidos_GA\Img\Botones entregas\RegistrarChofer\VOLVAZ.png" alt="Choferes"class = "icono-Volver"style="max-width: 5%; height: auto; position:absolute; top: 70px; left: 25px;">
                    

                </a></li>
            </ul>
        </nav>
    </header>
    <p></p>
  <div id="date_range_slider"></div>
    <p></p>
    
   
  <input type="hidden" id="start_date" value="">
  <input type="hidden" id="end_date" value="">
 <div class="container">
  <div id="time_range" style="margin-bottom: 10px; font-size: 24px; color: navy;"></div>
    </div>

    <div id="total_column_chart" style="height: 400px;"></div>
   <div id="summary_container"></div>
    
    <p></p>
    
    <div class="container2"> 
   <div class="linea-horizontal">
    <div class="texto-linea">GRAFICAS POR SUCURSAL</div>
  </div>
    
     
  <div class="row">
      
    
    <div class="col-md-4">
        
      <div class="chart-container">
        <div id="piechart1" style="height: 300px;"></div>
        <div class="chart-title">
            
            <img src="/Pedidos_GA/Img/Botones%20entregas/Estadisticas/aiesa.png" alt="iconoVPedidos "class= "icono-registro" style="max-width: 20%; height: auto; ">
            
            </div>
      </div>
      <div id="table_div_aiesa"></div>
    </div>
      
      
    <div class="col-md-4">
      <div class="chart-container">
        <div id="piechart2" style="height: 300px;"></div>
        <div class="chart-title">
            
            <img src="/Pedidos_GA/Img/Botones%20entregas/Estadisticas/deasa.png" alt="iconoVPedidos "class= "icono-registro" style="max-width: 20%; height: auto; ">            
            </div>
      </div>
      <div id="table_div_deasa"></div>
    </div>
    <div class="col-md-4">
      <div class="chart-container">
        <div id="piechart3" style="height: 300px;"></div>
        <div class="chart-title">
            
            <img src="/Pedidos_GA/Img/Botones%20entregas/Estadisticas/dimegsa.png" alt="iconoVPedidos "class= "icono-registro" style="max-width: 20%; height: auto; ">
            </div>
      </div>
      <div id="table_div_dimegsa"></div>
    </div>
  </div>
  <div class="row">
    <div class="col-md-4">
      <div class="chart-container">
        <div id="piechart4" style="height: 300px;"></div>
        <div class="chart-title">
          <img src="/Pedidos_GA/Img/Botones%20entregas/Estadisticas/gabajio.png" alt="iconoVPedidos "class= "icono-registro" style="max-width: 20%; height: auto; ">
          
          </div>
      </div>
      <div id="table_div_gabsa"></div>
    </div>
    <div class="col-md-4">
      <div class="chart-container">
        <div id="piechart5" style="height: 300px;"></div>
        <div class="chart-title">
          <img src="/Pedidos_GA/Img/Botones%20entregas/Estadisticas/iluminacion_1.png" alt="iconoVPedidos "class= "icono-registro" style="max-width: 20%; height: auto; ">
          </div>
      </div>
      <div id="table_div_ilu"></div>
    </div>
    <div class="col-md-4">
      <div class="chart-container">
        <div id="piechart6" style="height: 300px;"></div>
        <div class="chart-title">
          <img src="/Pedidos_GA/Img/Botones%20entregas/Estadisticas/segsa.png" alt="iconoVPedidos "class= "icono-registro" style="max-width: 20%; height: auto; ">
          </div>
      </div>
      <div id="table_div_segsa"></div>
    </div>
  </div>
  <div class="row">
    <div class="col-md-4">
      <div class="chart-container">
        <div id="piechart7" style="height: 300px;"></div>
        <div class="chart-title">
          <img src="/Pedidos_GA/Img/Botones%20entregas/Estadisticas/fesa.png" alt="iconoVPedidos "class= "icono-registro" style="max-width: 20%; height: auto; ">
          </div>
      </div>
      <div id="table_div_fesa"></div>
    </div>
    <div class="col-md-4">
      <div class="chart-container">
        <div id="piechart8" style="height: 300px;"></div>
        <div class="chart-title"><img src="/Pedidos_GA/Img/Botones%20entregas/Estadisticas/eitsa.png" alt="iconoVPedidos "class= "icono-registro" style="max-width: 20%; height: auto; "></div>
      </div>
      <div id="table_div_tapatia"></div>
    </div>
    <div class="col-md-4">
      <div class="chart-container">
        <div id="piechart9" style="height: 300px;"></div>
        <div class="chart-title"><img src="/Pedidos_GA/Img/Botones%20entregas/Estadisticas/gavallarta.png" alt="iconoVPedidos "class= "icono-registro" style="max-width: 20%; height: auto; "></div>
      </div>
      <div id="table_div_vallarta"></div>
    </div>
  </div>
  <!------>

  <div class="row">
    <div class="col-md-4">
      <div class="chart-container">
        <div id="piechart10" style="height: 300px;"></div>
        <div class="chart-title">
          <img src="/Pedidos_GA/Img/Botones%20entregas/Estadisticas/codi.png" alt="iconoVPedidos "class= "icono-registro" style="max-width: 20%; height: auto; ">
          </div>
      </div>
      <div id="table_div_codi"></div>
    </div>
    
    <div class="col-md-4">
      <div class="chart-container">
        <div id="piechart11" style="height: 300px;"></div>
        <div class="chart-title"><img src="/Pedidos_GA/Img/Botones%20entregas/Estadisticas/QRO.png" alt="iconoVPedidos "class= "icono-registro" style="max-width: 20%; height: auto; "></div>
      </div>
      <div id="table_div_queretaro"></div>
    </div>
  </div>


</div>
    
    <div class ="Container">
 
    </div>
 
        <div class="container">
    <button  style="background: none; border: none; padding: 0; "class="print-button" onclick="window.print()" >
      
    <img src="/Pedidos_GA/Img/Botones%20entregas/Estadisticas/IMPAZ.png" alt="iconoVPedidos "class= "icono-Imprimir" style="max-width: 50%; height: auto; ">

    </button>
    
   <!-- <button id="btnGuardarPDF">Guardar como PDF</button>-->


</div>

    
   <script>
    
 
// Función para guardar la página como PDF
document.getElementById('btnGuardarPDF').addEventListener('click', function() {
    // Crear un objeto jsPDF
    var doc = new jsPDF();

    // Agregar el contenido de la página al PDF
    doc.html(document.body, {
        callback: function(doc) {
            // Guardar el PDF con un nombre específico
            doc.save('mi_pagina.pdf');
        },
        x: 10,
        y: 10
    });
});
</script>

   


</body>
</html>
