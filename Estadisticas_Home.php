<?php
session_name("GA");
session_start();

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

    <link rel="icon" type="image/png" href="/Img/Botones%20entregas/ICONOSPAG/ICONOPEDIDOS.png">

    <!-- Tailwind CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        gaBlue: '#005aa3',
                        gaBg: '#f4f7fb',
                        gaAccent: '#e3f2fd',
                        gaOrange: '#ff7f0e'
                    },
                    boxShadow: {
                        gaSoft: '0 10px 25px rgba(15,23,42,0.10)',
                        gaCard: '0 8px 18px rgba(15,23,42,0.08)'
                    },
                    borderRadius: {
                        '3xl': '1.5rem'
                    }
                }
            }
        }
    </script>

    <!-- jQuery & Google Charts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.3.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.16/jspdf.plugin.autotable.min.js"></script>

    <script type="text/javascript">
        const SUCURSALES = [
            { nombre: 'AIESA',        chartId: 'piechart1',  tableId: 'table_div_aiesa' },
            { nombre: 'DEASA',        chartId: 'piechart2',  tableId: 'table_div_deasa' },
            { nombre: 'GABSA',        chartId: 'piechart4',  tableId: 'table_div_gabsa' },
            { nombre: 'ILUMINACION',  chartId: 'piechart5',  tableId: 'table_div_ilu' },
            { nombre: 'DIMEGSA',      chartId: 'piechart3',  tableId: 'table_div_dimegsa' },
            { nombre: 'SEGSA',        chartId: 'piechart6',  tableId: 'table_div_segsa' },
            { nombre: 'FESA',         chartId: 'piechart7',  tableId: 'table_div_fesa' },
            { nombre: 'TAPATIA',      chartId: 'piechart8',  tableId: 'table_div_tapatia' },
            { nombre: 'VALLARTA',     chartId: 'piechart9',  tableId: 'table_div_vallarta' },
            { nombre: 'CODI',         chartId: 'piechart10', tableId: 'table_div_codi' },
            { nombre: 'QUERETARO',    chartId: 'piechart11', tableId: 'table_div_queretaro' }
        ];

        google.charts.load('current', {'packages':['table', 'bar', 'corechart']});
        google.charts.setOnLoadCallback(() => console.log('Google Charts cargado'));

        let allData = null;

        function actualizarTodo() {
            const startDate = $('#start_date').val();
            const endDate   = $('#end_date').val();

            // Construir datos del request (sin fechas si están vacías)
            const requestData = {};
            if (startDate && endDate) {
                requestData.start_date = startDate;
                requestData.end_date = endDate;
            }

            $.ajax({
                url: 'estadisticas_todas_sucursales.php',
                type: 'GET',
                dataType: 'json',
                data: requestData,
                success: function(data) {
                    allData = data || {};

                    // Actualizar texto del rango
                    if (startDate && endDate) {
                        $('#time_range').text(`Rango: ${formatDate(startDate)} - ${formatDate(endDate)}`);
                    } else {
                        $('#time_range').text('Mostrando todos los registros');
                    }

                    drawTotalColumnChartFromCache();
                    drawChartsFromCache();
                    drawTablesFromCache();
                },
                error: function() {
                    alert('Error al cargar datos. Intenta recargar la página.');
                }
            });
        }

        // ================= GRÁFICO GENERAL =================

        function drawTotalColumnChartFromCache() {
            if (!allData || !allData.resumen_general) return;

            const data = allData.resumen_general;
            if (!data || data.length <= 1) {
                $('#total_column_chart').html('<div class="text-center text-sm text-sky-600 py-4">No hay datos disponibles.</div>');
                $('#summary_container').empty();
                return;
            }

            const jsonData  = data.map(row => row.slice(0, 7));
            const dataChart = google.visualization.arrayToDataTable(jsonData);

            const options = {
                chart: {
                    title: 'Total de Facturas por Sucursal',
                    subtitle: 'Distribución por Estado en el rango seleccionado'
                },
                bars: 'horizontal',
                legend: { position: 'top' },
                width: '100%',
                height: 320,
                colors: ['#22c55e', '#ef4444', '#3b82f6', '#facc15', '#0ea5e9', '#8b5cf6', '#f97316']
            };

            const chart = new google.charts.Bar(document.getElementById('total_column_chart'));
            chart.draw(dataChart, google.charts.Bar.convertOptions(options));

            crearTablaResumen(data);
        }

        function crearTablaResumen(data) {
            const $table = $('<table class="min-w-full text-[10px] border-collapse">');
            const $thead = $('<thead class="bg-slate-100 text-gaBlue font-semibold"></thead>');
            const $tbody = $('<tbody class="bg-white"></tbody>');

            $thead.append(
                $('<tr>').append(
                    '<th class="px-2 py-1 border border-slate-200">Sucursal</th>',
                    '<th class="px-2 py-1 border border-slate-200">Entregadas</th>',
                    '<th class="px-2 py-1 border border-slate-200">Canceladas</th>',
                    '<th class="px-2 py-1 border border-slate-200">En Ruta</th>',
                    '<th class="px-2 py-1 border border-slate-200">Activas</th>',
                    '<th class="px-2 py-1 border border-slate-200">En Tienda</th>',
                    '<th class="px-2 py-1 border border-slate-200">Reprog.</th>',
                    '<th class="px-2 py-1 border border-slate-200">Total</th>',
                    '<th class="px-2 py-1 border border-slate-200">Km</th>'
                )
            );

            data.forEach((row, i) => {
                if (i === 0) return;
                $tbody.append(
                    $('<tr class="hover:bg-slate-50">').append(
                        `<td class="px-2 py-1 border border-slate-100">${row[0]}</td>`,
                        `<td class="px-2 py-1 border border-slate-100 text-center">${row[1]}</td>`,
                        `<td class="px-2 py-1 border border-slate-100 text-center">${row[2]}</td>`,
                        `<td class="px-2 py-1 border border-slate-100 text-center">${row[3]}</td>`,
                        `<td class="px-2 py-1 border border-slate-100 text-center">${row[4]}</td>`,
                        `<td class="px-2 py-1 border border-slate-100 text-center">${row[5]}</td>`,
                        `<td class="px-2 py-1 border border-slate-100 text-center">${row[6]}</td>`,
                        `<td class="px-2 py-1 border border-slate-100 text-center">${row[7]}</td>`,
                        `<td class="px-2 py-1 border border-slate-100 text-center">${parseFloat(row[8]).toFixed(2)}</td>`
                    )
                );
            });

            $table.append($thead).append($tbody);
            $('#summary_container').empty().append(
                $('<div class="mt-3 overflow-x-auto rounded-2xl border border-slate-100 bg-white">').append($table)
            );
        }

        // ================= PIE CHARTS =================

        function drawChartsFromCache() {
            if (!allData || !allData.sucursales) return;

            SUCURSALES.forEach(s => {
                const cont = document.getElementById(s.chartId);
                if (!cont) return;

                const sucursalData = allData.sucursales[s.nombre];
                const chartData    = sucursalData && sucursalData.estadisticas_por_estado;

                if (!chartData || chartData.length <= 1) {
                    cont.innerHTML = '<div class="text-center text-xs text-sky-500">Sin datos</div>';
                    return;
                }

                const dataChart = google.visualization.arrayToDataTable(chartData);
                const options = {
                    chartArea: { width: '88%', height: '80%' },
                    legend: { position: 'right', textStyle: { fontSize: 9 } },
                    pieSliceText: 'value',
                    colors: ['#22c55e', '#ef4444', '#3b82f6', '#f97316', '#eab308', '#8b5cf6']
                };

                new google.visualization.PieChart(cont).draw(dataChart, options);
            });
        }

        // ================= TABLAS CHOFERES =================

        function drawTablesFromCache() {
            if (!allData || !allData.sucursales) return;

            SUCURSALES.forEach(s => {
                const cont = document.getElementById(s.tableId);
                if (!cont) return;

                const sucursalData = allData.sucursales[s.nombre];
                const rows = (sucursalData && sucursalData.facturas_por_chofer) || [];

                const dt = new google.visualization.DataTable();
                dt.addColumn('string', 'Chofer');
                dt.addColumn('number', 'Total');
                dt.addColumn('number', 'Km');
                dt.addColumn('number', 'Entregadas');
                dt.addColumn('number', 'Canceladas');
                dt.addColumn('number', 'En Ruta');
                dt.addColumn('number', 'En Tienda');
                dt.addColumn('number', 'Reprog.');
                dt.addColumn('number', 'Activas');

                rows.forEach(r => {
                    dt.addRow([
                        r.chofer,
                        r.total_facturas,
                        r.Total_Kilometros,
                        r.entregadas,
                        r.canceladas,
                        r.en_ruta,
                        r.En_Tienda,
                        r.REPROGRAMADO,
                        r.activas
                    ]);
                });

                const table = new google.visualization.Table(cont);
                table.draw(dt, {
                    showRowNumber: false,
                    width: '100%',
                    height: '100%',
                    allowHtml: true
                });
            });
        }

        // ================= UTIL & INIT =================

        function formatDate(dateString) {
            const d = new Date(dateString);
            if (isNaN(d)) return dateString || '';
            return d.toLocaleDateString('es-MX', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        }

        function setRangoRapido(dias) {
            const end = new Date();
            const start = new Date();
            start.setDate(start.getDate() - dias);
            $('#start_date').val(start.toISOString().slice(0,10));
            $('#end_date').val(end.toISOString().slice(0,10));
            actualizarTodo();
        }

        $(function () {
            const end = new Date();
            const start = new Date(end);
            start.setMonth(start.getMonth() - 1);
            $('#start_date').val(start.toISOString().slice(0,10));
            $('#end_date').val(end.toISOString().slice(0,10));

            actualizarTodo();

            $('#start_date, #end_date').on('change', function () {
                const s = $('#start_date').val();
                const e = $('#end_date').val();
                if (s && e && s > e) {
                    alert('La fecha inicial no puede ser mayor que la final');
                    return;
                }
                actualizarTodo();
            });

            $('#btn_ultimos_7_dias').on('click', () => setRangoRapido(7));
            $('#btn_ultimos_30_dias').on('click', () => setRangoRapido(30));
            $('#btn_ultimos_90_dias').on('click', () => setRangoRapido(90));
            $('#btn_este_mes').on('click', () => {
                const now = new Date();
                const s = new Date(now.getFullYear(), now.getMonth(), 1);
                const e = new Date();
                $('#start_date').val(s.toISOString().slice(0,10));
                $('#end_date').val(e.toISOString().slice(0,10));
                actualizarTodo();
            });

            // Botón para mostrar todos los registros
            $('#btn_todos').on('click', () => {
                $('#start_date').val('');
                $('#end_date').val('');
                actualizarTodo();
            });
        });

        document.addEventListener("DOMContentLoaded", function () {
            const iconoVolver = document.querySelector(".icono-Volver");
            const iconoImprimir = document.querySelector(".icono-Imprimir");

            if (iconoVolver) {
                const normal = "//Img/Botones%20entregas/RegistrarChofer/VOLVAZ.png";
                const hover  = "//Img/Botones%20entregas/RegistrarChofer/VOLVNA.png";
                iconoVolver.addEventListener('mouseover', () => iconoVolver.src = hover);
                iconoVolver.addEventListener('mouseout',  () => iconoVolver.src = normal);
            }

            if (iconoImprimir) {
                const normal = "//Img/Botones%20entregas/Estadisticas/IMPAZ.png";
                const hover  = "//Img/Botones%20entregas/Estadisticas/IMPNA.png";
                iconoImprimir.addEventListener('mouseover', () => iconoImprimir.src = hover);
                iconoImprimir.addEventListener('mouseout',  () => iconoImprimir.src = normal);
            }
        });
    </script>

    <style>
        /* Sticky header dentro de la tabla generada por Google (cuando hay scroll) */
        .chofer-table table thead tr th {
            position: sticky;
            top: 0;
            z-index: 10;
            background-color: #f97316; /* naranja GA para header tabla */
            color: #ffffff;
            font-size: 9px;
        }
        .chofer-table table tbody tr td {
            font-size: 9px;
        }

        /* Evitar solapamiento de componentes */
        .card-sucursal {
            display: flex;
            flex-direction: column;
            height: 100%;
            min-height: 420px;
        }

        .card-sucursal .chart-container {
            flex: 1;
            min-height: 0; /* Importante para flexbox */
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .card-sucursal .table-container {
            flex-shrink: 0;
            max-height: 130px;
            overflow-y: auto;
            overflow-x: hidden;
        }

        /* Asegurar que los gráficos no se salgan de su contenedor */
        #total_column_chart {
            min-height: 320px;
            max-height: 320px;
            overflow: hidden;
        }
    </style>
</head>

<body class="bg-gaBg text-slate-900">

    <!-- HEADER -->
    <header class="sticky top-0 z-30 bg-gaBg/95 backdrop-blur flex items-center px-6 py-4 shadow-sm" style="background-color: rgb(0 90 163 / 91%);">
        <div class="flex items-center gap-4">
            <img src="/Img/Botones%20entregas/Estadisticas/ESTADISTICAS.png"
                 alt="Estadísticas"
                 class="h-14 object-contain drop-shadow">
        </div>
        <nav class="ml-auto">
            <a href="Pedidos_GA.php" title="Volver">
                <img src="/Img/Botones%20entregas/RegistrarChofer/VOLVAZ.png"
                     alt="Volver"
                     class="icono-Volver h-9 w-auto hover:scale-105 transition-transform">
            </a>
        </nav>
    </header>

    <!-- FILTRO DE FECHAS -->
    <section class="max-w-6xl mx-auto px-4 mt-4">
        <div class="bg-white rounded-3xl shadow-gaSoft px-6 py-5">
            <h2 class="text-2xl font-extrabold text-gaBlue text-center mb-4">
                Filtrar por Rango de Fechas
            </h2>

            <div class="flex flex-wrap justify-center gap-6">
                <div class="flex flex-col text-sm text-slate-700">
                    <label for="start_date" class="mb-1 font-medium">Fecha inicial</label>
                    <input type="date" id="start_date"
                           class="border border-slate-200 rounded-xl px-3 py-2 text-sm bg-gaBg focus:outline-none focus:ring-2 focus:ring-gaBlue/40 min-w-[170px]">
                </div>
                <div class="flex flex-col text-sm text-slate-700">
                    <label for="end_date" class="mb-1 font-medium">Fecha final</label>
                    <input type="date" id="end_date"
                           class="border border-slate-200 rounded-xl px-3 py-2 text-sm bg-gaBg focus:outline-none focus:ring-2 focus:ring-gaBlue/40 min-w-[170px]">
                </div>
            </div>

            <div class="flex flex-wrap justify-center items-center gap-2 mt-4">
                <span class="text-xs text-slate-500 mr-1">Filtros rápidos:</span>
                <button id="btn_todos"
                        class="px-3 py-1 rounded-full bg-orange-100 text-orange-600 text-xs font-semibold hover:bg-orange-500 hover:text-white transition">
                    Todos
                </button>
                <button id="btn_ultimos_7_dias"
                        class="px-3 py-1 rounded-full bg-gaAccent text-gaBlue text-xs font-semibold hover:bg-gaBlue hover:text-white transition">
                    Últimos 7 días
                </button>
                <button id="btn_ultimos_30_dias"
                        class="px-3 py-1 rounded-full bg-gaAccent text-gaBlue text-xs font-semibold hover:bg-gaBlue hover:text-white transition">
                    Últimos 30 días
                </button>
                <button id="btn_ultimos_90_dias"
                        class="px-3 py-1 rounded-full bg-gaAccent text-gaBlue text-xs font-semibold hover:bg-gaBlue hover:text-white transition">
                    Últimos 90 días
                </button>
                <button id="btn_este_mes"
                        class="px-3 py-1 rounded-full bg-gaAccent text-gaBlue text-xs font-semibold hover:bg-gaBlue hover:text-white transition">
                    Este mes
                </button>
            </div>

            <div id="time_range"
                 class="mt-3 text-center text-sm font-semibold text-gaBlue">
            </div>
        </div>
    </section>

    <!-- GRÁFICO GENERAL + RESUMEN -->
    <section class="max-w-6xl mx-auto px-4 mt-4">
        <div class="bg-white rounded-3xl shadow-gaSoft px-4 py-4">
            <div id="total_column_chart" class="w-full h-[320px]"></div>
            <div id="summary_container" class="mt-1"></div>
        </div>
    </section>

    <!-- GRID SUCURSALES -->
    <section class="max-w-6xl mx-auto px-4 mt-8 pb-10">
        <div class="flex items-center gap-3 mb-3">
            <div class="h-[2px] w-10 bg-gaBlue rounded-full"></div>
            <h3 class="text-lg font-bold text-gaBlue tracking-wide">
                GRÁFICAS POR SUCURSAL
            </h3>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- AIESA -->
            <div class="bg-white rounded-3xl shadow-gaCard p-3 card-sucursal hover:shadow-2xl hover:-translate-y-1 transition">
                <div class="flex justify-center items-center pb-2 border-b border-slate-100 flex-shrink-0">
                    <img src="/Img/Botones%20entregas/Estadisticas/aiesa.png" alt="AIESA"
                         class="h-9 object-contain">
                </div>
                <div class="chart-container pt-2">
                    <div id="piechart1" class="w-full h-full"></div>
                </div>
                <div id="table_div_aiesa" class="chofer-table table-container mt-2"></div>
            </div>

            <!-- DEASA -->
            <div class="bg-white rounded-3xl shadow-gaCard p-3 card-sucursal hover:shadow-2xl hover:-translate-y-1 transition">
                <div class="flex justify-center items-center pb-2 border-b border-slate-100 flex-shrink-0">
                    <img src="/Img/Botones%20entregas/Estadisticas/deasa.png" alt="DEASA" class="h-9 object-contain">
                </div>
                <div class="chart-container pt-2">
                    <div id="piechart2" class="w-full h-full"></div>
                </div>
                <div id="table_div_deasa" class="chofer-table table-container mt-2"></div>
            </div>

            <!-- DIMEGSA -->
            <div class="bg-white rounded-3xl shadow-gaCard p-3 card-sucursal hover:shadow-2xl hover:-translate-y-1 transition">
                <div class="flex justify-center items-center pb-2 border-b border-slate-100 flex-shrink-0">
                    <img src="/Img/Botones%20entregas/Estadisticas/dimegsa.png" alt="DIMEGSA" class="h-9 object-contain">
                </div>
                <div class="chart-container pt-2">
                    <div id="piechart3" class="w-full h-full"></div>
                </div>
                <div id="table_div_dimegsa" class="chofer-table table-container mt-2"></div>
            </div>

            <!-- GABSA -->
            <div class="bg-white rounded-3xl shadow-gaCard p-3 card-sucursal hover:shadow-2xl hover:-translate-y-1 transition">
                <div class="flex justify-center items-center pb-2 border-b border-slate-100 flex-shrink-0">
                    <img src="/Img/Botones%20entregas/Estadisticas/gabajio.png" alt="GABSA" class="h-9 object-contain">
                </div>
                <div class="chart-container pt-2">
                    <div id="piechart4" class="w-full h-full"></div>
                </div>
                <div id="table_div_gabsa" class="chofer-table table-container mt-2"></div>
            </div>

            <!-- ILUMINACION -->
            <div class="bg-white rounded-3xl shadow-gaCard p-3 card-sucursal hover:shadow-2xl hover:-translate-y-1 transition">
                <div class="flex justify-center items-center pb-2 border-b border-slate-100 flex-shrink-0">
                    <img src="/Img/Botones%20entregas/Estadisticas/iluminacion_1.png" alt="ILUMINACION" class="h-9 object-contain">
                </div>
                <div class="chart-container pt-2">
                    <div id="piechart5" class="w-full h-full"></div>
                </div>
                <div id="table_div_ilu" class="chofer-table table-container mt-2"></div>
            </div>

            <!-- SEGSA -->
            <div class="bg-white rounded-3xl shadow-gaCard p-3 card-sucursal hover:shadow-2xl hover:-translate-y-1 transition">
                <div class="flex justify-center items-center pb-2 border-b border-slate-100 flex-shrink-0">
                    <img src="/Img/Botones%20entregas/Estadisticas/segsa.png" alt="SEGSA" class="h-9 object-contain">
                </div>
                <div class="chart-container pt-2">
                    <div id="piechart6" class="w-full h-full"></div>
                </div>
                <div id="table_div_segsa" class="chofer-table table-container mt-2"></div>
            </div>

            <!-- FESA -->
            <div class="bg-white rounded-3xl shadow-gaCard p-3 card-sucursal hover:shadow-2xl hover:-translate-y-1 transition">
                <div class="flex justify-center items-center pb-2 border-b border-slate-100 flex-shrink-0">
                    <img src="/Img/Botones%20entregas/Estadisticas/fesa.png" alt="FESA" class="h-9 object-contain">
                </div>
                <div class="chart-container pt-2">
                    <div id="piechart7" class="w-full h-full"></div>
                </div>
                <div id="table_div_fesa" class="chofer-table table-container mt-2"></div>
            </div>

            <!-- TAPATIA -->
            <div class="bg-white rounded-3xl shadow-gaCard p-3 card-sucursal hover:shadow-2xl hover:-translate-y-1 transition">
                <div class="flex justify-center items-center pb-2 border-b border-slate-100 flex-shrink-0">
                    <img src="/Img/Botones%20entregas/Estadisticas/eitsa.png" alt="TAPATIA" class="h-9 object-contain">
                </div>
                <div class="chart-container pt-2">
                    <div id="piechart8" class="w-full h-full"></div>
                </div>
                <div id="table_div_tapatia" class="chofer-table table-container mt-2"></div>
            </div>

            <!-- VALLARTA -->
            <div class="bg-white rounded-3xl shadow-gaCard p-3 card-sucursal hover:shadow-2xl hover:-translate-y-1 transition">
                <div class="flex justify-center items-center pb-2 border-b border-slate-100 flex-shrink-0">
                    <img src="/Img/Botones%20entregas/Estadisticas/gavallarta.png" alt="VALLARTA" class="h-9 object-contain">
                </div>
                <div class="chart-container pt-2">
                    <div id="piechart9" class="w-full h-full"></div>
                </div>
                <div id="table_div_vallarta" class="chofer-table table-container mt-2"></div>
            </div>

            <!-- CODI -->
            <div class="bg-white rounded-3xl shadow-gaCard p-3 card-sucursal hover:shadow-2xl hover:-translate-y-1 transition">
                <div class="flex justify-center items-center pb-2 border-b border-slate-100 flex-shrink-0">
                    <img src="/Img/Botones%20entregas/Estadisticas/codi.png" alt="CODI" class="h-9 object-contain">
                </div>
                <div class="chart-container pt-2">
                    <div id="piechart10" class="w-full h-full"></div>
                </div>
                <div id="table_div_codi" class="chofer-table table-container mt-2"></div>
            </div>

            <!-- QUERETARO -->
            <div class="bg-white rounded-3xl shadow-gaCard p-3 card-sucursal hover:shadow-2xl hover:-translate-y-1 transition">
                <div class="flex justify-center items-center pb-2 border-b border-slate-100 flex-shrink-0">
                    <img src="/Img/Botones%20entregas/Estadisticas/QRO.png" alt="QUERÉTARO" class="h-9 object-contain">
                </div>
                <div class="chart-container pt-2">
                    <div id="piechart11" class="w-full h-full"></div>
                </div>
                <div id="table_div_queretaro" class="chofer-table table-container mt-2"></div>
            </div>
        </div>
    </section>

    <!-- BOTÓN IMPRIMIR -->
    <div class="max-w-6xl mx-auto px-4 pb-10 text-center">
        <button onclick="window.print()" title="Imprimir estadísticas"
                class="inline-flex items-center justify-center mt-2">
            <img src="/Img/Botones%20entregas/Estadisticas/IMPAZ.png"
                 alt="Imprimir"
                 class="icono-Imprimir h-12 w-auto hover:scale-105 transition-transform">
        </button>
    </div>

</body>
</html>
