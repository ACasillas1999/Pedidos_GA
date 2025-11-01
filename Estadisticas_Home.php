<?php
/**
 * Estadísticas Home - Dashboard de Estadísticas por Sucursal
 * Sistema de Pedidos GA - Grupo Ascencio
 *
 * Muestra estadísticas de pedidos por sucursal con:
 * - Gráficos circulares por sucursal
 * - Gráfico de barras general
 * - Tablas detalladas por chofer
 * - Filtros de fecha con slider
 */

// Iniciar sesión
session_name("GA");
session_start();

// Verificar autenticación
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: /Pedidos_GA/Sesion/login.html");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estadísticas - Pedidos GA</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/Pedidos_GA/Img/Botones%20entregas/ICONOSPAG/ICONOPEDIDOS.png">

    <!-- CSS -->
    <link rel="stylesheet" href="styles4.css">

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.3.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.16/jspdf.plugin.autotable.min.js"></script>

    <script type="text/javascript">
        // ============================================================
        // CONFIGURACIÓN Y CONSTANTES
        // ============================================================

        const SUCURSALES = [
            { nombre: 'AIESA', chartId: 'piechart1', tableId: 'table_div_aiesa' },
            { nombre: 'DEASA', chartId: 'piechart2', tableId: 'table_div_deasa' },
            { nombre: 'GABSA', chartId: 'piechart4', tableId: 'table_div_gabsa' },
            { nombre: 'ILUMINACION', chartId: 'piechart5', tableId: 'table_div_ilu' },
            { nombre: 'DIMEGSA', chartId: 'piechart3', tableId: 'table_div_dimegsa' },
            { nombre: 'SEGSA', chartId: 'piechart6', tableId: 'table_div_segsa' },
            { nombre: 'FESA', chartId: 'piechart7', tableId: 'table_div_fesa' },
            { nombre: 'TAPATIA', chartId: 'piechart8', tableId: 'table_div_tapatia' },
            { nombre: 'VALLARTA', chartId: 'piechart9', tableId: 'table_div_vallarta' },
            { nombre: 'CODI', chartId: 'piechart10', tableId: 'table_div_codi' },
            { nombre: 'QUERETARO', chartId: 'piechart11', tableId: 'table_div_queretaro' }
        ];

        // ============================================================
        // INICIALIZACIÓN DE GOOGLE CHARTS
        // ============================================================

        google.charts.load('current', {'packages':['table', 'bar', 'corechart']});
        google.charts.setOnLoadCallback(inicializarGraficas);

        function inicializarGraficas() {
            // Las gráficas se dibujarán después de configurar las fechas
            console.log('Google Charts cargado exitosamente');
        }

        // ============================================================
        // FUNCIONES PARA TABLAS POR CHOFER
        // ============================================================

        function drawTables() {
            SUCURSALES.forEach(sucursal => {
                drawTable(sucursal.nombre, sucursal.tableId);
            });
        }

        function drawTable(sucursal, containerId) {
            const startDate = $('#start_date').val();
            const endDate = $('#end_date').val();

            $.ajax({
                url: 'facturas_por_chofer.php',
                type: 'GET',
                dataType: 'json',
                data: {
                    sucursal: sucursal,
                    start_date: startDate,
                    end_date: endDate
                },
                success: function(data) {
                    const dataTable = new google.visualization.DataTable();

                    // Definir columnas
                    dataTable.addColumn('string', 'Chofer');
                    dataTable.addColumn('number', 'Total Facturas');
                    dataTable.addColumn('number', 'Kilómetros');
                    dataTable.addColumn('number', 'Entregadas');
                    dataTable.addColumn('number', 'Canceladas');
                    dataTable.addColumn('number', 'En Ruta');
                    dataTable.addColumn('number', 'En Tienda');
                    dataTable.addColumn('number', 'Reprogramado');
                    dataTable.addColumn('number', 'Activas');

                    // Agregar filas
                    $.each(data, function(index, row) {
                        dataTable.addRow([
                            row.chofer,
                            parseInt(row.total_facturas) || 0,
                            parseInt(row.Total_Kilometros) || 0,
                            parseInt(row.entregadas) || 0,
                            parseInt(row.canceladas) || 0,
                            parseInt(row.en_ruta) || 0,
                            parseInt(row.En_Tienda) || 0,
                            parseInt(row.REPROGRAMADO) || 0,
                            parseInt(row.activas) || 0
                        ]);
                    });

                    // Dibujar tabla
                    const table = new google.visualization.Table(document.getElementById(containerId));
                    table.draw(dataTable, {
                        showRowNumber: true,
                        width: '100%',
                        height: '100%',
                        allowHtml: true
                    });
                },
                error: function(xhr, status, error) {
                    console.error(`Error al obtener datos de ${sucursal}:`, {
                        status: xhr.status,
                        statusText: xhr.statusText,
                        responseText: xhr.responseText,
                        error: error
                    });

                    let errorMsg = 'Error al cargar datos.';
                    if (xhr.status === 403) {
                        errorMsg = 'Acceso denegado (403). Verifica los permisos del servidor.';
                    } else if (xhr.status === 404) {
                        errorMsg = 'Archivo no encontrado (404).';
                    } else if (xhr.status === 500) {
                        errorMsg = 'Error en el servidor (500).';
                    }

                    $(`#${containerId}`).html(
                        `<div class="error-message">${errorMsg}<br><small>Sucursal: ${sucursal}</small></div>`
                    );
                }
            });
        }

        // ============================================================
        // GRÁFICO DE BARRAS TOTALES POR SUCURSAL
        // ============================================================

        function drawTotalColumnChart() {
            const startDate = $('#start_date').val();
            const endDate = $('#end_date').val();

            $.ajax({
                url: 'total_facturas_por_sucursal.php',
                type: 'GET',
                dataType: 'json',
                data: {
                    start_date: startDate,
                    end_date: endDate
                },
                success: function(data) {
                    console.log("Datos recibidos:", data);

                    if (!data || data.length <= 1) {
                        $('#total_column_chart').html(
                            '<div class="info-message">No hay datos disponibles para el rango seleccionado.</div>'
                        );
                        $('#summary_container').empty();
                        return;
                    }

                    // Preparar datos para el gráfico (sin columna de kilómetros)
                    const jsonData = data.map(row => row.slice(0, 7));
                    const dataChart = google.visualization.arrayToDataTable(jsonData);

                    const options = {
                        chart: {
                            title: 'Total de Facturas por Sucursal',
                            subtitle: 'Distribución por Estado en el Rango Seleccionado'
                        },
                        bars: 'horizontal',
                        legend: { position: 'top' },
                        width: '100%',
                        height: 400,
                        colors: ['#2ca02c', '#d62728', '#1f77b4', '#bcbd22', '#ff7f0e', '#795548', '#9c27b0']
                    };

                    const chart = new google.charts.Bar(document.getElementById('total_column_chart'));
                    chart.draw(dataChart, google.charts.Bar.convertOptions(options));

                    // Crear tabla resumen
                    crearTablaResumen(data);
                },
                error: function(xhr, status, error) {
                    console.error("Error al obtener datos del gráfico:", error);
                    $('#total_column_chart').html(
                        '<div class="error-message">Error al cargar el gráfico. Por favor, intente nuevamente.</div>'
                    );
                }
            });
        }

        function crearTablaResumen(data) {
            const summary = $('<table>').addClass('summary-table');

            // Encabezado - CORREGIDO: orden correcto según los datos
            const headerRow = $('<tr>').append(
                $('<th>').text('Sucursal'),
                $('<th>').text('Entregadas'),
                $('<th>').text('Canceladas'),
                $('<th>').text('En Ruta'),
                $('<th>').text('Activas'),
                $('<th>').text('En Tienda'),
                $('<th>').text('Reprogramado'),
                $('<th>').text('Total Facturas'),
                $('<th>').text('Kilómetros')
            );
            summary.append(headerRow);

            // Filas de datos - índices correctos [0-8]
            data.forEach(function(row, index) {
                if (index > 0) { // Saltar el encabezado
                    const rowElement = $('<tr>').append(
                        $('<td>').text(row[0]),  // Sucursal
                        $('<td>').text(row[1]),  // Entregadas
                        $('<td>').text(row[2]),  // Canceladas
                        $('<td>').text(row[3]),  // En Ruta
                        $('<td>').text(row[4]),  // Activas
                        $('<td>').text(row[5]),  // En Tienda
                        $('<td>').text(row[6]),  // Reprogramado
                        $('<td>').text(row[7]),  // Total Facturas
                        $('<td>').text(parseFloat(row[8]).toFixed(2))  // Kilómetros con 2 decimales
                    );
                    summary.append(rowElement);
                }
            });

            $('#summary_container').empty().append(summary);
        }

        // ============================================================
        // GRÁFICOS CIRCULARES POR SUCURSAL
        // ============================================================

        function drawCharts() {
            SUCURSALES.forEach(sucursal => {
                const title = `Facturas de ${sucursal.nombre} por Estado`;
                drawChart(sucursal.nombre, sucursal.chartId, title);
            });
        }

        function drawChart(sucursal, chartId, chartTitle) {
            const startDate = $('#start_date').val();
            const endDate = $('#end_date').val();

            // Actualizar texto de rango de fechas
            $('#time_range').text(`Rango: ${formatDate(startDate)} - ${formatDate(endDate)}`);

            $.ajax({
                url: 'Estadisticas.php',
                type: 'GET',
                dataType: 'json',
                data: {
                    start_date: startDate,
                    end_date: endDate,
                    sucursal: sucursal
                },
                success: function(data) {
                    if (!data || data.length <= 1) {
                        $(`#${chartId}`).html(
                            '<div class="info-message">Sin datos</div>'
                        );
                        return;
                    }

                    const dataChart = google.visualization.arrayToDataTable(data);
                    const options = {
                        title: chartTitle,
                        chartArea: { width: '85%', height: '75%' },
                        legend: { position: 'right' },
                        colors: ['#2ca02c', '#d62728', '#1f77b4', '#ff7f0e', '#bcbd22', '#795548'],
                        pieSliceText: 'value'
                    };

                    const chart = new google.visualization.PieChart(document.getElementById(chartId));
                    chart.draw(dataChart, options);
                },
                error: function(xhr, status, error) {
                    console.error(`Error en gráfico de ${sucursal}:`, {
                        status: xhr.status,
                        statusText: xhr.statusText,
                        responseText: xhr.responseText,
                        error: error,
                        url: this.url
                    });

                    let errorMsg = 'Error al cargar gráfico';
                    if (xhr.status === 403) {
                        errorMsg = 'Acceso denegado (403)';
                    }

                    $(`#${chartId}`).html(
                        `<div class="error-message" style="padding: 10px; font-size: 12px;">${errorMsg}</div>`
                    );
                }
            });
        }

        // ============================================================
        // FUNCIONES DE ACTUALIZACIÓN - UNA SOLA PETICIÓN
        // ============================================================

        // Variable global para almacenar todos los datos
        let allData = null;

        function actualizarTodo() {
            // Cargar todos los datos de una sola vez
            cargarTodosLosDatos();
        }

        function cargarTodosLosDatos() {
            const startDate = $('#start_date').val();
            const endDate = $('#end_date').val();

            console.log('🔄 Cargando datos de todas las sucursales...');

            $.ajax({
                url: 'estadisticas_todas_sucursales.php',
                type: 'GET',
                dataType: 'json',
                data: {
                    start_date: startDate,
                    end_date: endDate
                },
                success: function(data) {
                    console.log('✅ Datos cargados exitosamente');
                    allData = data;

                    // Actualizar rango de fechas
                    $('#time_range').text(`Rango: ${formatDate(startDate)} - ${formatDate(endDate)}`);

                    // Dibujar todo con los datos cargados
                    drawChartsFromCache();
                    drawTablesFromCache();
                    drawTotalColumnChartFromCache();
                },
                error: function(xhr, status, error) {
                    console.error('❌ Error al cargar datos:', {
                        status: xhr.status,
                        statusText: xhr.statusText,
                        error: error
                    });

                    alert('Error al cargar datos. Por favor, recarga la página.');
                }
            });
        }

        function drawChartsFromCache() {
            if (!allData || !allData.sucursales) return;

            SUCURSALES.forEach(sucursal => {
                const sucursalData = allData.sucursales[sucursal.nombre];
                if (!sucursalData) return;

                const data = sucursalData.estadisticas_por_estado;
                const title = `Facturas de ${sucursal.nombre} por Estado`;

                if (!data || data.length <= 1) {
                    $(`#${sucursal.chartId}`).html('<div class="info-message">Sin datos</div>');
                    return;
                }

                const dataChart = google.visualization.arrayToDataTable(data);
                const options = {
                    title: title,
                    chartArea: { width: '85%', height: '75%' },
                    legend: { position: 'right' },
                    colors: ['#2ca02c', '#d62728', '#1f77b4', '#ff7f0e', '#bcbd22', '#795548'],
                    pieSliceText: 'value'
                };

                const chart = new google.visualization.PieChart(document.getElementById(sucursal.chartId));
                chart.draw(dataChart, options);
            });
        }

        function drawTablesFromCache() {
            if (!allData || !allData.sucursales) return;

            SUCURSALES.forEach(sucursal => {
                const sucursalData = allData.sucursales[sucursal.nombre];
                if (!sucursalData) return;

                const data = sucursalData.facturas_por_chofer;
                const dataTable = new google.visualization.DataTable();

                // Definir columnas
                dataTable.addColumn('string', 'Chofer');
                dataTable.addColumn('number', 'Total Facturas');
                dataTable.addColumn('number', 'Kilómetros');
                dataTable.addColumn('number', 'Entregadas');
                dataTable.addColumn('number', 'Canceladas');
                dataTable.addColumn('number', 'En Ruta');
                dataTable.addColumn('number', 'En Tienda');
                dataTable.addColumn('number', 'Reprogramado');
                dataTable.addColumn('number', 'Activas');

                // Agregar filas
                data.forEach(row => {
                    dataTable.addRow([
                        row.chofer,
                        row.total_facturas,
                        row.Total_Kilometros,
                        row.entregadas,
                        row.canceladas,
                        row.en_ruta,
                        row.En_Tienda,
                        row.REPROGRAMADO,
                        row.activas
                    ]);
                });

                // Dibujar tabla
                const table = new google.visualization.Table(document.getElementById(sucursal.tableId));
                table.draw(dataTable, {
                    showRowNumber: true,
                    width: '100%',
                    height: '100%',
                    allowHtml: true
                });
            });
        }

        function drawTotalColumnChartFromCache() {
            if (!allData || !allData.resumen_general) return;

            const data = allData.resumen_general;

            if (!data || data.length <= 1) {
                $('#total_column_chart').html('<div class="info-message">No hay datos disponibles.</div>');
                $('#summary_container').empty();
                return;
            }

            // Preparar datos para el gráfico (sin columna de kilómetros)
            const jsonData = data.map(row => row.slice(0, 7));
            const dataChart = google.visualization.arrayToDataTable(jsonData);

            const options = {
                chart: {
                    title: 'Total de Facturas por Sucursal',
                    subtitle: 'Distribución por Estado en el Rango Seleccionado'
                },
                bars: 'horizontal',
                legend: { position: 'top' },
                width: '100%',
                height: 400,
                colors: ['#2ca02c', '#d62728', '#1f77b4', '#bcbd22', '#ff7f0e', '#795548', '#9c27b0']
            };

            const chart = new google.charts.Bar(document.getElementById('total_column_chart'));
            chart.draw(dataChart, google.charts.Bar.convertOptions(options));

            // Crear tabla resumen
            crearTablaResumen(data);
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('es-MX', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        }

        // ============================================================
        // INICIALIZACIÓN DE FECHAS
        // ============================================================

        $(function() {
            // Establecer fechas predeterminadas (último mes)
            const endDate = new Date();
            const startDate = new Date(endDate);
            startDate.setMonth(startDate.getMonth() - 1);

            $('#start_date').val(startDate.toISOString().slice(0, 10));
            $('#end_date').val(endDate.toISOString().slice(0, 10));

            // Dibujar todas las gráficas con valores iniciales
            setTimeout(actualizarTodo, 500);

            // Event listeners para los campos de fecha
            $('#start_date, #end_date').on('change', function() {
                const startDateVal = $('#start_date').val();
                const endDateVal = $('#end_date').val();

                // Validar que la fecha inicial no sea mayor que la final
                if (startDateVal && endDateVal && startDateVal > endDateVal) {
                    alert('La fecha inicial no puede ser mayor que la fecha final');
                    return;
                }

                // Actualizar todas las gráficas
                if (startDateVal && endDateVal) {
                    actualizarTodo();
                }
            });

            // Botón para aplicar filtros rápidos
            $('#btn_ultimos_7_dias').on('click', function() {
                const end = new Date();
                const start = new Date();
                start.setDate(start.getDate() - 7);
                $('#start_date').val(start.toISOString().slice(0, 10));
                $('#end_date').val(end.toISOString().slice(0, 10));
                actualizarTodo();
            });

            $('#btn_ultimos_30_dias').on('click', function() {
                const end = new Date();
                const start = new Date();
                start.setDate(start.getDate() - 30);
                $('#start_date').val(start.toISOString().slice(0, 10));
                $('#end_date').val(end.toISOString().slice(0, 10));
                actualizarTodo();
            });

            $('#btn_ultimos_90_dias').on('click', function() {
                const end = new Date();
                const start = new Date();
                start.setDate(start.getDate() - 90);
                $('#start_date').val(start.toISOString().slice(0, 10));
                $('#end_date').val(end.toISOString().slice(0, 10));
                actualizarTodo();
            });

            $('#btn_este_mes').on('click', function() {
                const now = new Date();
                const start = new Date(now.getFullYear(), now.getMonth(), 1);
                const end = new Date();
                $('#start_date').val(start.toISOString().slice(0, 10));
                $('#end_date').val(end.toISOString().slice(0, 10));
                actualizarTodo();
            });
        });

        // ============================================================
        // EFECTOS HOVER DE BOTONES
        // ============================================================

        document.addEventListener("DOMContentLoaded", function() {
            const iconoVolver = document.querySelector(".icono-Volver");
            const iconoImprimir = document.querySelector(".icono-Imprimir");

            // Hover para botón Volver
            if (iconoVolver) {
                const imgNormal = "/Pedidos_GA/Img/Botones%20entregas/RegistrarChofer/VOLVAZ.png";
                const imgHover = "/Pedidos_GA/Img/Botones%20entregas/RegistrarChofer/VOLVNA.png";

                iconoVolver.addEventListener("mouseover", () => iconoVolver.src = imgHover);
                iconoVolver.addEventListener("mouseout", () => iconoVolver.src = imgNormal);
            }

            // Hover para botón Imprimir
            if (iconoImprimir) {
                const imgNormal = "/Pedidos_GA/Img/Botones%20entregas/Estadisticas/IMPAZ.png";
                const imgHover = "/Pedidos_GA/Img/Botones%20entregas/Estadisticas/IMPNA.png";

                iconoImprimir.addEventListener("mouseover", () => iconoImprimir.src = imgHover);
                iconoImprimir.addEventListener("mouseout", () => iconoImprimir.src = imgNormal);
            }
        });
    </script>

    <style>
        .error-message {
            color: #d32f2f;
            padding: 20px;
            text-align: center;
            font-weight: bold;
        }

        .info-message {
            color: #1976d2;
            padding: 20px;
            text-align: center;
        }

        .loading {
            text-align: center;
            padding: 20px;
            color: #666;
        }

        /* Estilos adicionales para calendario nativo */
        .date-input::-webkit-calendar-picker-indicator {
            cursor: pointer;
            border-radius: 4px;
            margin-left: 5px;
            opacity: 0.6;
            filter: invert(28%) sepia(99%) saturate(1000%) hue-rotate(181deg);
        }

        .date-input::-webkit-calendar-picker-indicator:hover {
            opacity: 1;
        }

        /* Mejoras adicionales para gráficas */
        [id^="piechart"] {
            min-height: 350px;
        }

        /* Contenedor de tabla con mejor separación */
        [id^="table_div_"] {
            margin-top: 15px;
        }

        /* Animación de carga suave */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .container, .container2, .chart-container {
            animation: fadeInUp 0.6s ease-out;
        }
    </style>
</head>

<body>
    <!-- ============================================================ -->
    <!-- HEADER -->
    <!-- ============================================================ -->
    <header class="header">
        <div class="logo">
            <img src="/Pedidos_GA/Img/Botones%20entregas/Estadisticas/ESTADISTICAS.png"
                 alt="Estadísticas"
                 class="icono-registro"
                 style="max-width: 25%; height: auto;">
        </div>
        <nav class="navbar">
            <ul>
                <li class="nav-item">
                    <a href='Pedidos_GA.php' class="nav-link">
                        <img src="\Pedidos_GA\Img\Botones entregas\RegistrarChofer\VOLVAZ.png"
                             alt="Volver"
                             class="icono-Volver"
                             style="max-width: 5%; height: auto; position:absolute; top: 70px; left: 25px;">
                    </a>
                </li>
            </ul>
        </nav>
    </header>

    <!-- ============================================================ -->
    <!-- INDICADOR DE SESIÓN -->
    <!-- ============================================================ -->
    <div class="container" style="margin-top: 20px; padding: 15px; display: none;">
        <div id="session-indicator" style="display: flex; align-items: center; justify-content: space-between; background: #f0f7ff; padding: 12px 20px; border-radius: 6px; border-left: 4px solid #005aa3;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <span style="font-size: 20px;">👤</span>
                <div>
                    <div style="font-weight: bold; color: #005aa3; font-size: 14px;">
                        Usuario: <?php echo htmlspecialchars($_SESSION["username"] ?? 'Desconocido'); ?>
                    </div>
                    <div style="color: #666; font-size: 12px; margin-top: 2px;">
                        <?php echo htmlspecialchars($_SESSION["Nombre"] ?? ''); ?> |
                        Rol: <?php echo htmlspecialchars($_SESSION["Rol"] ?? ''); ?> |
                        Sucursal: <?php echo htmlspecialchars($_SESSION["Sucursal"] ?? ''); ?>
                    </div>
                </div>
            </div>
            <div style="color: #999; font-size: 11px;">
                Sesión activa ✓
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- SELECTOR DE RANGO DE FECHAS -->
    <!-- ============================================================ -->
    <div class="container" style="margin-top: 20px;">
        <div class="date-filter-container">
            <h3 style="color: #005aa3; margin-bottom: 20px;">Filtrar por Rango de Fechas</h3>

            <div class="date-inputs">
                <div class="date-input-group">
                    <label for="start_date">Fecha Inicial:</label>
                    <input type="date" id="start_date" class="date-input">
                </div>

                <div class="date-input-group">
                    <label for="end_date">Fecha Final:</label>
                    <input type="date" id="end_date" class="date-input">
                </div>
            </div>

            <div class="quick-filters">
                <h4 style="color: #666; margin: 20px 0 10px 0;">Filtros Rápidos:</h4>
                <button id="btn_ultimos_7_dias" class="filter-btn">Últimos 7 días</button>
                <button id="btn_ultimos_30_dias" class="filter-btn">Últimos 30 días</button>
                <button id="btn_ultimos_90_dias" class="filter-btn">Últimos 90 días</button>
                <button id="btn_este_mes" class="filter-btn">Este mes</button>
            </div>

            <div id="time_range" style="margin-top: 15px; font-size: 18px; color: #005aa3; font-weight: bold;"></div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- GRÁFICO GENERAL Y RESUMEN -->
    <!-- ============================================================ -->
    <div class="container">
        <div id="total_column_chart" style="height: 400px; margin: 20px 0;"></div>
        <div id="summary_container"></div>
    </div>

    <!-- ============================================================ -->
    <!-- GRÁFICAS POR SUCURSAL -->
    <!-- ============================================================ -->
    <div class="container2">
        <div class="linea-horizontal">
            <div class="texto-linea">GRÁFICAS POR SUCURSAL</div>
        </div>

        <!-- Fila 1: AIESA, DEASA, DIMEGSA -->
        <div class="row">
            <div class="col-md-4">
                <div class="chart-container">
                    <div class="chart-title">
                        <img src="/Pedidos_GA/Img/Botones%20entregas/Estadisticas/aiesa.png"
                             alt="AIESA"
                             style="max-width: 20%; height: auto;">
                    </div>
                    <div id="piechart1" style="height: 300px;"></div>
                </div>
                <div id="table_div_aiesa"></div>
            </div>

            <div class="col-md-4">
                <div class="chart-container">
                    <div class="chart-title">
                        <img src="/Pedidos_GA/Img/Botones%20entregas/Estadisticas/deasa.png"
                             alt="DEASA"
                             style="max-width: 20%; height: auto;">
                    </div>
                    <div id="piechart2" style="height: 300px;"></div>
                </div>
                <div id="table_div_deasa"></div>
            </div>

            <div class="col-md-4">
                <div class="chart-container">
                    <div class="chart-title">
                        <img src="/Pedidos_GA/Img/Botones%20entregas/Estadisticas/dimegsa.png"
                             alt="DIMEGSA"
                             style="max-width: 20%; height: auto;">
                    </div>
                    <div id="piechart3" style="height: 300px;"></div>
                </div>
                <div id="table_div_dimegsa"></div>
            </div>
        </div>

        <!-- Fila 2: GABSA, ILUMINACION, SEGSA -->
        <div class="row">
            <div class="col-md-4">
                <div class="chart-container">
                    <div class="chart-title">
                        <img src="/Pedidos_GA/Img/Botones%20entregas/Estadisticas/gabajio.png"
                             alt="GABSA"
                             style="max-width: 20%; height: auto;">
                    </div>
                    <div id="piechart4" style="height: 300px;"></div>
                </div>
                <div id="table_div_gabsa"></div>
            </div>

            <div class="col-md-4">
                <div class="chart-container">
                    <div class="chart-title">
                        <img src="/Pedidos_GA/Img/Botones%20entregas/Estadisticas/iluminacion_1.png"
                             alt="ILUMINACION"
                             style="max-width: 20%; height: auto;">
                    </div>
                    <div id="piechart5" style="height: 300px;"></div>
                </div>
                <div id="table_div_ilu"></div>
            </div>

            <div class="col-md-4">
                <div class="chart-container">
                    <div class="chart-title">
                        <img src="/Pedidos_GA/Img/Botones%20entregas/Estadisticas/segsa.png"
                             alt="SEGSA"
                             style="max-width: 20%; height: auto;">
                    </div>
                    <div id="piechart6" style="height: 300px;"></div>
                </div>
                <div id="table_div_segsa"></div>
            </div>
        </div>

        <!-- Fila 3: FESA, TAPATIA, VALLARTA -->
        <div class="row">
            <div class="col-md-4">
                <div class="chart-container">
                    <div class="chart-title">
                        <img src="/Pedidos_GA/Img/Botones%20entregas/Estadisticas/fesa.png"
                             alt="FESA"
                             style="max-width: 20%; height: auto;">
                    </div>
                    <div id="piechart7" style="height: 300px;"></div>
                </div>
                <div id="table_div_fesa"></div>
            </div>

            <div class="col-md-4">
                <div class="chart-container">
                    <div class="chart-title">
                        <img src="/Pedidos_GA/Img/Botones%20entregas/Estadisticas/eitsa.png"
                             alt="TAPATIA"
                             style="max-width: 20%; height: auto;">
                    </div>
                    <div id="piechart8" style="height: 300px;"></div>
                </div>
                <div id="table_div_tapatia"></div>
            </div>

            <div class="col-md-4">
                <div class="chart-container">
                    <div class="chart-title">
                        <img src="/Pedidos_GA/Img/Botones%20entregas/Estadisticas/gavallarta.png"
                             alt="VALLARTA"
                             style="max-width: 20%; height: auto;">
                    </div>
                    <div id="piechart9" style="height: 300px;"></div>
                </div>
                <div id="table_div_vallarta"></div>
            </div>
        </div>

        <!-- Fila 4: CODI, QUERETARO -->
        <div class="row">
            <div class="col-md-4">
                <div class="chart-container">
                    <div class="chart-title">
                        <img src="/Pedidos_GA/Img/Botones%20entregas/Estadisticas/codi.png"
                             alt="CODI"
                             style="max-width: 20%; height: auto;">
                    </div>
                    <div id="piechart10" style="height: 300px;"></div>
                </div>
                <div id="table_div_codi"></div>
            </div>

            <div class="col-md-4">
                <div class="chart-container">
                    <div class="chart-title">
                        <img src="/Pedidos_GA/Img/Botones%20entregas/Estadisticas/QRO.png"
                             alt="QUERETARO"
                             style="max-width: 20%; height: auto;">
                    </div>
                    <div id="piechart11" style="height: 300px;"></div>
                </div>
                <div id="table_div_queretaro"></div>
            </div>

            <div class="col-md-4">
                <!-- Espacio vacío -->
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- BOTÓN DE IMPRESIÓN -->
    <!-- ============================================================ -->
    <div class="container" style="margin: 30px auto;">
        <button style="background: none; border: none; padding: 0; cursor: pointer;"
                class="print-button"
                onclick="window.print()"
                title="Imprimir estadísticas">
            <img src="/Pedidos_GA/Img/Botones%20entregas/Estadisticas/IMPAZ.png"
                 alt="Imprimir"
                 class="icono-Imprimir"
                 style="max-width: 50%; height: auto;">
        </button>
    </div>
</body>
</html>
