<?php

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
<<<<<<< HEAD
   <link rel="icon" type="image/png" href="/Img/Botones%20entregas/ICONOSPAG/ICONOPEDIDOS.png">
=======
   <link rel="icon" type="image/png" href="/Pedidos_GA/Img/Botones%20entregas/ICONOSPAG/ICONOPEDIDOS.png">
>>>>>>> parent of 5e8b02c (parra amazon Update image paths and SQL table names)
    <title>Estadísticas del Chofer</title>
    <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <link rel="stylesheet" href="styles5.css">
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dom-to-image/2.6.0/dom-to-image.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.3.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.16/jspdf.plugin.autotable.min.js"></script>

    <script type="text/javascript">
        google.charts.load('current', {'packages':['corechart', 'bar']});
        google.charts.setOnLoadCallback(drawChart);

        function getParameterByName(name) {
            var url = window.location.href;
            name = name.replace(/[\[\]]/g, "\\$&");
            var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
                results = regex.exec(url);
            if (!results) return null;
            if (!results[2]) return '';
            return decodeURIComponent(results[2].replace(/\+/g, " "));
        }

        function drawChart() {
            var chofer_username = getParameterByName('id');
            if (!chofer_username) {
                console.error("No se ha proporcionado el username del chofer.");
                return;
            }

            var startDate = $('#start_date').val();
            var endDate = $('#end_date').val();

            $('#time_range').text('Rango de tiempo seleccionado: ' + startDate + ' - ' + endDate);
            
            $.ajax({
                url: 'FuncionEstadisticasChofer.php',
                type: 'GET',
                dataType: 'json',
                data: { 
                    id: chofer_username,
                    start_date: startDate,
                    end_date: endDate
                },
                success: function(response) {
                    if (response.error) {
                        console.error(response.error);
                        return;
                    }

                    var data = response.data;
                    data.unshift(['Estado', 'Cantidad', { role: 'style' }]);

                    var colors = {
                        'ACTIVO': '#678cff',
                        'CANCELADO': '#ff4642',
                        'EN RUTA': '#ffa33e',
                        'ENTREGADO': '#8cff8a',
                        'EN TIENDA': '#faff06',
                        'REPROGRAMADOS': '#8b81ff',
                    };

                    for (var i = 1; i < data.length; i++) {
                        var estado = data[i][0];
                        var color = colors[estado] || 'grey';
                        data[i].push(color);
                    }

                    var dataChart = google.visualization.arrayToDataTable(data);
                    var options = {
                        title: 'Cantidad de Pedidos por Estado',
                        chartArea: {width: '50%'},
                        hAxis: {
                            title: 'Cantidad',
                            minValue: 0
                        },
                        vAxis: {
                            title: 'Estado'
                        }
                    };
                    var chart = new google.visualization.BarChart(document.getElementById('barchart'));
                    chart.draw(dataChart, options);

                    document.getElementById('chofer-username').innerText = "Chofer: " + chofer_username;

                    var resumen = "<table class='resumen-table'><thead><tr><th>Estado</th><th>Cantidad</th></tr></thead><tbody>";
                    for (var i = 1; i < data.length; i++) {
                        resumen += "<tr><td>" + data[i][0] + "</td><td>" + data[i][1] + "</td></tr>";
                    }
                    resumen += "</tbody></table>";
                    document.getElementById('resumen').innerHTML = resumen;

                    $.ajax({
                        url: 'ConsultarChofer.php',
                        type: 'POST',
                        data: { 
                            nombre: chofer_username,
                            start_date: startDate,
                            end_date: endDate 
                        },
                        success: function(response) {
                            document.getElementById('pedidos-chofer').innerHTML = response;
                        },
                        error: function(xhr, status, error) {
                            console.error("Error al obtener los pedidos del chofer:", error);
                        }
                    });
                },
                error: function(xhr, status, error) {
                    console.error("Error al obtener los datos para la gráfica:", error);
                }
            });
        }

        $(function() {
            $("#date-range").slider({
                range: true,
                min: new Date('2024-01-01').getTime() / 1000,
                max: new Date().getTime() / 1000,
                step: 86400,
                values: [new Date('2023-01-01').getTime() / 1000, new Date().getTime() / 1000],
                slide: function(event, ui) {
                    var startDate = new Date(ui.values[0] * 1000).toISOString().split('T')[0];
                    var endDate = new Date(ui.values[1] * 1000).toISOString().split('T')[0];
                    $("#start_date").val(startDate);
                    $("#end_date").val(endDate);
                    drawChart();
                }
            });

            var startDate = new Date($("#date-range").slider("values", 0) * 1000).toISOString().split('T')[0];
            var endDate = new Date($("#date-range").slider("values", 1) * 1000).toISOString().split('T')[0];
            $("#start_date").val(startDate);
            $("#end_date").val(endDate);

            drawChart();
        });

        function exportPDF() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();

            doc.text("Estadísticas del Chofer", 10, 10);

            // Add chart as image
            domtoimage.toPng(document.getElementById('barchart'))
                .then(function (imgData) {
                    doc.addImage(imgData, 'PNG', 10, 20, 180, 80);

                    // Add resumen table
                    doc.autoTable({ html: '.resumen-table', startY: 110 });

                    // Add pedidos-chofer
                    doc.autoTable({ html: '#pedidos-chofer', startY: doc.autoTable.previous.finalY + 10 });

                    doc.save('Estadisticas_Chofer.pdf');
                })
                .catch(function (error) {
                    console.error("Error al convertir la gráfica a imagen:", error);
                });
        }
    </script>
    <style>
        #barchart {
            width: 100%;
            height: 500px;
        }
        .ui-slider-horizontal .ui-slider-handle {
            top: -1px;
        }
        #resumen {
            margin-top: 20px;
        }
        .resumen-table {
            width: 100%;
            border-collapse: collapse;
        }
        .resumen-table th, .resumen-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .resumen-table th {
            background-color: #f2f2f2;
        }
    </style>
</head>

<script>
   document.addEventListener("DOMContentLoaded", function() {
    var iconoImprimir = document.querySelector(".icono-Imprimir-img");
    var iconoVolver = document.querySelector(".icono-Volver");
    

<<<<<<< HEAD
    var imgNormalImprimir = "/Img/Botones%20entregas/Estadisticas/IMPAZ.png";
    var imgHoverImprimir = "/Img/Botones%20entregas/Estadisticas/IMPNA.png";
=======
    var imgNormalImprimir = "/Pedidos_GA/Img/Botones%20entregas/Estadisticas/IMPAZ.png";
    var imgHoverImprimir = "/Pedidos_GA/Img/Botones%20entregas/Estadisticas/IMPNA.png";
>>>>>>> parent of 5e8b02c (parra amazon Update image paths and SQL table names)

    // Cambiar la imagen al pasar el mouse
    if (iconoImprimir) {
        iconoImprimir.addEventListener("mouseover", function() {
            iconoImprimir.src = imgHoverImprimir;
        });

        iconoImprimir.addEventListener("mouseout", function() {
            iconoImprimir.src = imgNormalImprimir;
        });
    }

<<<<<<< HEAD
    var imgNormalVolver = "/Img/Botones%20entregas/RegistrarChofer/VOLVAZ.png";
    var imgHoverVolver = "/Img/Botones%20entregas/RegistrarChofer/VOLVNA.png";
=======
    var imgNormalVolver = "/Pedidos_GA/Img/Botones%20entregas/RegistrarChofer/VOLVAZ.png";
    var imgHoverVolver = "/Pedidos_GA/Img/Botones%20entregas/RegistrarChofer/VOLVNA.png";
>>>>>>> parent of 5e8b02c (parra amazon Update image paths and SQL table names)

    // Cambiar la imagen al pasar el mouse para Volver
    if (iconoVolver) {
        iconoVolver.addEventListener("mouseover", function() {
            iconoVolver.src = imgHoverVolver;
        });

        iconoVolver.addEventListener("mouseout", function() {
            iconoVolver.src = imgNormalVolver;
        });
    }
});


    </script>

<body>
    <header class="header">
        <div class="logo">
<<<<<<< HEAD
            <img src="/Img/Botones%20entregas/Choferes/ESTADISTICAS.png" alt="Estaditicas " class="icono-estadisticas" style="max-width: 20%; height: auto;">
=======
            <img src="/Pedidos_GA/Img/Botones%20entregas/Choferes/ESTADISTICAS.png" alt="Estaditicas " class="icono-estadisticas" style="max-width: 20%; height: auto;">
>>>>>>> parent of 5e8b02c (parra amazon Update image paths and SQL table names)
        </div>
        <nav class="navbar">
            <ul>
                <li class="nav-item"><a href='Choferes.php' class="nav-link">
                    
                <img src="\Pedidos_GA\Img\Botones entregas\RegistrarChofer\VOLVAZ.png" alt="Choferes"class = "icono-Volver"style="max-width: 5%; height: auto; position:absolute; top: 70px; left: 25px;">
                

                </a></li>
            </ul>
        </nav>
    </header>
    
    <div class="container">
        <div id="chofer-username"></div>
        <div id="date-range"></div>
        <div> 
        <input type="hidden" id="start_date" name="start_date" value="">
        <input type="hidden" id="end_date" name="end_date" value="">
        </div>
        <div id="time_range" style="margin-bottom: 10px; font-size: 24px; color: navy;"></div>
        
        <div id="barchart" class="chart-container"></div>
        <div id="resumen"></div>
   
    <p></p>
    <div id="pedidos-chofer"></div>
         </div>
    
    <div>

    <p></p>
    <button type="submit" class="icono-Imprimir" style="background: none; border: none; padding: 0;">
<<<<<<< HEAD
    <img src="/Img/Botones%20entregas/Estadisticas/IMPAZ.png" alt="iconoVPedidos" class="icono-Imprimir-img" style="max-width: 50%; height: auto;">
=======
    <img src="/Pedidos_GA/Img/Botones%20entregas/Estadisticas/IMPAZ.png" alt="iconoVPedidos" class="icono-Imprimir-img" style="max-width: 50%; height: auto;">
>>>>>>> parent of 5e8b02c (parra amazon Update image paths and SQL table names)
</button>

     <!--   <button class="pdf-button" onclick="exportPDF()">Exportar como PDF</button>-->
    </div>
</body>
</html>
