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

    <link rel="icon" type="image/png" href="/Pedidos_GA/Img/Botones%20entregas/ICONOSPAG/ICONOPEDIDOS.png">

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
            { nombre: 'AIESA',        logo: 'aiesa.png', chartId: 'piechart1', tableId: 'table_div_aiesa' },
            { nombre: 'DEASA',        logo: 'deasa.png', chartId: 'piechart2', tableId: 'table_div_deasa' },
            { nombre: 'GABSA',        logo: 'gabajio.png', chartId: 'piechart4', tableId: 'table_div_gabsa' },
            { nombre: 'ILUMINACION',  logo: 'iluminacion_1.png', chartId: 'piechart5', tableId: 'table_div_ilu' },
            { nombre: 'DIMEGSA',      logo: 'dimegsa.png', chartId: 'piechart3', tableId: 'table_div_dimegsa' },
            { nombre: 'SEGSA',        logo: 'segsa.png', chartId: 'piechart6', tableId: 'table_div_segsa' },
            { nombre: 'FESA',         logo: 'fesa.png', chartId: 'piechart7', tableId: 'table_div_fesa' },
            { nombre: 'TAPATIA',      logo: 'eitsa.png', chartId: 'piechart8', tableId: 'table_div_tapatia' },
            { nombre: 'VALLARTA',     logo: 'gavallarta.png', chartId: 'piechart9', tableId: 'table_div_vallarta' },
            { nombre: 'CODI',         logo: 'codi.png', chartId: 'piechart10', tableId: 'table_div_codi' },
            { nombre: 'QUERETARO',    logo: 'QRO.png', chartId: 'piechart11', tableId: 'table_div_queretaro' }
        ];

        google.charts.load('current', {'packages':['table', 'bar', 'corechart']});
        
        let chartsReady = false;
        let domReady = false;

        google.charts.setOnLoadCallback(() => {
            console.log('Google Charts cargado');
            chartsReady = true;
            if (chartsReady && domReady) {
                actualizarTodo();
            }
        });

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

                    const globalWasHidden = $('#content-global').hasClass('hidden');
                    const sucursalesWasHidden = $('#content-sucursales').hasClass('hidden');
                    const tiemposWasHidden = $('#content-tiempos').hasClass('hidden');

                    $('#content-global, #content-sucursales, #content-tiempos').removeClass('hidden');

                    drawTotalColumnChartFromCache();
                    drawChartsAndTables();
                    drawTiemposEntregaTable();

                    if (globalWasHidden) $('#content-global').addClass('hidden');
                    if (sucursalesWasHidden) $('#content-sucursales').addClass('hidden');
                    if (tiemposWasHidden) $('#content-tiempos').addClass('hidden');
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

        // ================= DETALLE SUCURSAL =================
        let currentActiveSucursal = 'AIESA';

        function drawChartsAndTables() {
            if (!allData || !allData.sucursales) return;

            // Mostrar todas temporalmente para que Google Charts dibuje con ancho/alto correcto
            $('.sucursal-card-container').removeClass('hidden');

            SUCURSALES.forEach(s => {
                // 1. DIBUJAR PIE CHART
                const contChart = document.getElementById(s.chartId);
                const sucursalData = allData.sucursales[s.nombre];
                const chartData = sucursalData && sucursalData.estadisticas_por_estado;

                if (!chartData || chartData.length <= 1) {
                    if(contChart) contChart.innerHTML = '<div class="text-center text-sm text-sky-500 pt-20">Sin datos de estados</div>';
                } else {
                    const dataChart = google.visualization.arrayToDataTable(chartData);
                    const options = {
                        chartArea: { width: '90%', height: '85%' },
                        legend: { position: 'right', textStyle: { fontSize: 11 } },
                        pieSliceText: 'value',
                        colors: ['#22c55e', '#ef4444', '#3b82f6', '#f97316', '#eab308', '#8b5cf6']
                    };
                    if(contChart) new google.visualization.PieChart(contChart).draw(dataChart, options);
                }

                // 2. DIBUJAR TABLA
                const contTable = document.getElementById(s.tableId);
                const rows = (sucursalData && sucursalData.facturas_por_chofer) || [];

                if (rows.length === 0) {
                    if(contTable) contTable.innerHTML = '<div class="text-center text-sm text-sky-500 pt-20">Sin datos de choferes</div>';
                } else {
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
                        let choferNombre = r.chofer ? String(r.chofer) : '';
                        if (choferNombre === '[object Object]' || choferNombre === 'null' || choferNombre.trim() === '') {
                            choferNombre = 'Sin Asignar';
                        }

                        dt.addRow([
                            choferNombre,
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

                    if(contTable) {
                        const table = new google.visualization.Table(contTable);
                        table.draw(dt, {
                            showRowNumber: false,
                            width: '100%',
                            height: '100%',
                            allowHtml: true
                        });
                    }
                }
            });

            // Ocultar de nuevo las que no están activas (excepto en impresión)
            $('.sucursal-card-container').each(function() {
                if ($(this).attr('id') !== `card-${currentActiveSucursal}`) {
                    $(this).addClass('hidden');
                }
            });
        }

        // ================= TABLA DE TIEMPOS DE ENTREGA =================

        function drawTiemposEntregaTable() {
            if (!allData || !allData.detalle_entregas || allData.detalle_entregas.length === 0) {
                $('#table_div_tiempos').html('<div class="text-center text-sm text-sky-600 py-4">No hay datos de entregas disponibles.</div>');
                $('#table_div_efectividad_sucursales').empty();
                $('#chart_efectividad_pie').empty();
                $('#chart_medibles_pie').empty();
                $('#efectividad_global').text('0%').css('color', '#94a3b8');
                $('#efectividad_global_detalle').text('0 de 0 entregas');
                $('#medicion_global').text('0%').css('color', '#94a3b8');
                $('#medicion_global_detalle').text('0 de 0 con hora');
                return;
            }

            const data = allData.detalle_entregas;
            const filterSuc = $('#filtro_sucursal_tiempos').val() || 'Global';
            
            const dt = new google.visualization.DataTable();
            dt.addColumn('number', 'ID Pedido');
            dt.addColumn('string', 'Sucursal');
            dt.addColumn('string', 'Cliente');
            dt.addColumn('string', 'Chofer');
            dt.addColumn('string', 'Ventana Prometida');
            dt.addColumn('string', 'Fecha/Hora Entrega');
            dt.addColumn('string', 'Evaluación');

            // STATS CALCULATION
            let globalTotal = 0;
            let globalExito = 0;
            let globalConHora = 0;
            let globalSinHora = 0;
            const sucursalStats = {};

            data.forEach(r => {
                const suc = r.SUCURSAL || 'Desconocida';
                if (!sucursalStats[suc]) {
                    sucursalStats[suc] = { total: 0, exito: 0, atrasado: 0, conHora: 0, sinHora: 0 };
                }
                
                const isExito = (r.Evaluacion_Entrega === 'A Tiempo' || r.Evaluacion_Entrega === 'Antes de Tiempo');
                const hasTime = r.Hora_Real_Entrega ? true : false;
                
                sucursalStats[suc].total++;
                if (isExito) sucursalStats[suc].exito++;
                else sucursalStats[suc].atrasado++;

                if (hasTime) sucursalStats[suc].conHora++;
                else sucursalStats[suc].sinHora++;

                // APLICAR FILTRO PARA LAS MÉTRICAS Y LA TABLA
                if (filterSuc !== 'Global' && suc !== filterSuc) return;

                globalTotal++;
                if (isExito) globalExito++;
                if (hasTime) globalConHora++;
                else globalSinHora++;

                const ventana = `${r.FECHA_MIN_ENTREGA} - ${r.FECHA_MAX_ENTREGA} (${r.MIN_VENTANA_HORARIA_1} a ${r.MAX_VENTANA_HORARIA_1})`;
                const horaStr = r.Hora_Real_Entrega ? r.Hora_Real_Entrega : '(Sin Hora)';
                const entrega = `${r.Fecha_Real_Entrega} ${horaStr}`;
                
                dt.addRow([
                    parseInt(r.ID_Pedido),
                    suc,
                    r.NOMBRE_CLIENTE || '',
                    r.CHOFER_ASIGNADO || '',
                    ventana,
                    entrega,
                    r.Evaluacion_Entrega || ''
                ]);
            });

            // UPDATE GLOBAL DOM
            const globalPorc = globalTotal > 0 ? ((globalExito / globalTotal) * 100).toFixed(1) : 0;
            $('#efectividad_global').text(globalPorc + '%');
            $('#efectividad_global_detalle').text(`${globalExito} a tiempo de ${globalTotal} entregas`);
            
            let color = '#22c55e'; // verde
            if(globalPorc < 80) color = '#ef4444'; // rojo
            else if(globalPorc < 90) color = '#f59e0b'; // naranja
            $('#efectividad_global').css('color', color);

            const medicionPorc = globalTotal > 0 ? ((globalConHora / globalTotal) * 100).toFixed(1) : 0;
            $('#medicion_global').text(medicionPorc + '%');
            $('#medicion_global_detalle').text(`${globalConHora} de ${globalTotal} con hora exacta`);

            let colorMed = '#3b82f6'; // azul
            if(medicionPorc < 50) colorMed = '#ef4444'; // rojo
            else if(medicionPorc < 80) colorMed = '#f59e0b'; // naranja
            $('#medicion_global').css('color', colorMed);

            // DRAW PIE CHARTS
            if (globalTotal === 0) {
                $('#chart_efectividad_pie').html('<div class="text-center text-xs text-sky-500 pt-10">Sin datos</div>');
                $('#chart_medibles_pie').html('<div class="text-center text-xs text-sky-500 pt-10">Sin datos</div>');
            } else {
                const atrasadasTotal = globalTotal - globalExito;
                const dataPieEf = google.visualization.arrayToDataTable([
                    ['Estado', 'Cantidad'],
                    ['A Tiempo', globalExito],
                    ['Atrasadas', atrasadasTotal]
                ]);

                const optionsPieEf = {
                    pieHole: 0.55,
                    chartArea: { width: '90%', height: '80%' },
                    legend: { position: 'bottom', textStyle: { fontSize: 10 } },
                    colors: ['#22c55e', '#ef4444'],
                    pieSliceText: 'none'
                };

                new google.visualization.PieChart(document.getElementById('chart_efectividad_pie')).draw(dataPieEf, optionsPieEf);

                const sinHoraTotal = globalTotal - globalConHora;
                const dataPieMed = google.visualization.arrayToDataTable([
                    ['Estado', 'Cantidad'],
                    ['Con Hora', globalConHora],
                    ['Sin Hora', sinHoraTotal]
                ]);

                const optionsPieMed = {
                    pieHole: 0.55,
                    chartArea: { width: '90%', height: '80%' },
                    legend: { position: 'bottom', textStyle: { fontSize: 10 } },
                    colors: ['#3b82f6', '#f59e0b'],
                    pieSliceText: 'none'
                };

                new google.visualization.PieChart(document.getElementById('chart_medibles_pie')).draw(dataPieMed, optionsPieMed);
            }

            // DRAW SUCURSAL STATS TABLE
            const dtStats = new google.visualization.DataTable();
            dtStats.addColumn('string', 'Sucursal');
            dtStats.addColumn('number', 'Total Entregas');
            dtStats.addColumn('number', 'Con Hora Exacta');
            dtStats.addColumn('number', 'Sin Hora');
            dtStats.addColumn('number', 'A Tiempo / Antes');
            dtStats.addColumn('number', 'Atrasadas');
            dtStats.addColumn('number', '% Efectividad');

            Object.keys(sucursalStats).forEach(suc => {
                if (filterSuc !== 'Global' && suc !== filterSuc) return; // Filtrar aquí también

                const stat = sucursalStats[suc];
                const porc = stat.total > 0 ? (stat.exito / stat.total) * 100 : 0;
                dtStats.addRow([
                    suc,
                    stat.total,
                    stat.conHora,
                    stat.sinHora,
                    stat.exito,
                    stat.atrasado,
                    {v: porc, f: porc.toFixed(1) + '%'}
                ]);
            });

            const tableStats = new google.visualization.Table(document.getElementById('table_div_efectividad_sucursales'));
            tableStats.draw(dtStats, {
                showRowNumber: false,
                width: '100%',
                height: '100%',
                sortColumn: 6,
                sortAscending: false
            });

            const table = new google.visualization.Table(document.getElementById('table_div_tiempos'));
            table.draw(dt, {
                showRowNumber: false,
                width: '100%',
                height: '100%',
                page: 'enable',
                pageSize: 15
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

            domReady = true;
            if (chartsReady && domReady) {
                actualizarTodo();
            }

            // Generar Cards HTML dinámicamente para soportar impresión de todas
            const container = $('#sucursales_container');
            SUCURSALES.forEach((s, index) => {
                const hiddenClass = index === 0 ? '' : 'hidden';
                const cardHTML = `
                    <div id="card-${s.nombre}" class="sucursal-card-container bg-white rounded-3xl shadow-gaCard p-6 border border-slate-100 mb-8 print:block ${hiddenClass}">
                        <div class="flex items-center justify-center border-b border-slate-100 pb-4 mb-6">
                            <img src="/Pedidos_GA/Img/Botones%20entregas/Estadisticas/${s.logo}" alt="Logo ${s.nombre}" class="h-12 object-contain">
                        </div>
                        <div class="grid grid-cols-1 xl:grid-cols-4 gap-8">
                            <div class="xl:col-span-1 flex flex-col items-center xl:border-r xl:border-slate-100 xl:pr-4">
                                <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4 text-center">Estado de Facturas</h4>
                                <div id="${s.chartId}" class="w-full h-[300px]"></div>
                            </div>
                            <div class="xl:col-span-3 flex flex-col">
                                <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4 text-center xl:text-left">Desempeño de Choferes</h4>
                                <div class="overflow-x-auto h-[300px] chofer-table">
                                    <div id="${s.tableId}" class="w-full h-full"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                container.append(cardHTML);
            });

            $('.sucursal-pill').on('click', function() {
                $('.sucursal-pill').removeClass('active bg-gaBlue text-white').addClass('bg-white text-slate-600 hover:bg-slate-50 border border-slate-200');
                $(this).removeClass('bg-white text-slate-600 hover:bg-slate-50 border border-slate-200').addClass('active bg-gaBlue text-white');
                
                currentActiveSucursal = $(this).data('nombre');
                
                // Mostrar solo la seleccionada
                $('.sucursal-card-container').addClass('hidden');
                $(`#card-${currentActiveSucursal}`).removeClass('hidden');
            });

            $('#filtro_sucursal_tiempos').on('change', function() {
                if (allData) drawTiemposEntregaTable();
            });

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

            // Botón para exportar estadísticas a Excel con los filtros de fechas activos
            $('#btn_export_excel').on('click', function() {
                const s = $('#start_date').val();
                const e = $('#end_date').val();
                let url = 'export_estadisticas.php';
                if (s && e) {
                    url += `?start_date=${encodeURIComponent(s)}&end_date=${encodeURIComponent(e)}`;
                }
                window.location.href = url;
            });
        });

        document.addEventListener("DOMContentLoaded", function () {
            // Lógica de Pestañas
            const tabGlobal = document.getElementById('tab-global');
            const tabSucursales = document.getElementById('tab-sucursales');
            const tabTiempos = document.getElementById('tab-tiempos');
            
            const contentGlobal = document.getElementById('content-global');
            const contentSucursales = document.getElementById('content-sucursales');
            const contentTiempos = document.getElementById('content-tiempos');

            function resetTabs() {
                const tabs = [tabGlobal, tabSucursales, tabTiempos];
                tabs.forEach(t => {
                    if(t) {
                        t.classList.remove('border-gaBlue', 'text-gaBlue');
                        t.classList.add('border-transparent', 'text-slate-500', 'hover:text-slate-700', 'hover:border-slate-300');
                    }
                });
                if(contentGlobal) contentGlobal.classList.add('hidden');
                if(contentSucursales) contentSucursales.classList.add('hidden');
                if(contentTiempos) contentTiempos.classList.add('hidden');
            }

            if (tabGlobal && tabSucursales && tabTiempos) {
                tabGlobal.addEventListener('click', () => {
                    resetTabs();
                    tabGlobal.classList.remove('border-transparent', 'text-slate-500', 'hover:text-slate-700', 'hover:border-slate-300');
                    tabGlobal.classList.add('border-gaBlue', 'text-gaBlue');
                    if(contentGlobal) contentGlobal.classList.remove('hidden');
                    if (allData) drawTotalColumnChartFromCache();
                });

                tabSucursales.addEventListener('click', () => {
                    resetTabs();
                    tabSucursales.classList.remove('border-transparent', 'text-slate-500', 'hover:text-slate-700', 'hover:border-slate-300');
                    tabSucursales.classList.add('border-gaBlue', 'text-gaBlue');
                    if(contentSucursales) contentSucursales.classList.remove('hidden');
                    if (allData) drawChartsAndTables();
                });

                tabTiempos.addEventListener('click', () => {
                    resetTabs();
                    tabTiempos.classList.remove('border-transparent', 'text-slate-500', 'hover:text-slate-700', 'hover:border-slate-300');
                    tabTiempos.classList.add('border-gaBlue', 'text-gaBlue');
                    if(contentTiempos) contentTiempos.classList.remove('hidden');
                    if (allData) drawTiemposEntregaTable();
                });
            }

            const iconoVolver = document.querySelector(".icono-Volver");
            const iconoImprimir = document.querySelector(".icono-Imprimir");

            if (iconoVolver) {
                const normal = "/Pedidos_GA/Img/Botones%20entregas/RegistrarChofer/VOLVAZ.png";
                const hover  = "/Pedidos_GA/Img/Botones%20entregas/RegistrarChofer/VOLVNA.png";
                iconoVolver.addEventListener('mouseover', () => iconoVolver.src = hover);
                iconoVolver.addEventListener('mouseout',  () => iconoVolver.src = normal);
            }

            if (iconoImprimir) {
                const normal = "/Pedidos_GA/Img/Botones%20entregas/Estadisticas/IMPAZ.png";
                const hover  = "/Pedidos_GA/Img/Botones%20entregas/Estadisticas/IMPNA.png";
                iconoImprimir.addEventListener('mouseover', () => iconoImprimir.src = hover);
                iconoImprimir.addEventListener('mouseout',  () => iconoImprimir.src = normal);
            }
        });
    </script>

    <style>
        /* ================= ESTILOS MODERNOS TABLAS GOOGLE CHARTS ================= */
        .google-visualization-table-table {
            width: 100% !important;
            border-collapse: collapse !important;
            border: none !important;
            font-family: 'Inter', system-ui, -apple-system, sans-serif !important;
        }
        
        .google-visualization-table-th {
            background-color: #f8fafc !important; /* bg-slate-50 */
            background-image: none !important;
            color: #64748b !important; /* text-slate-500 */
            font-weight: 700 !important;
            font-size: 11px !important;
            text-transform: uppercase !important;
            letter-spacing: 0.05em !important;
            padding: 12px 16px !important;
            border: none !important;
            border-bottom: 2px solid #e2e8f0 !important; /* border-slate-200 */
            text-align: center !important;
            position: sticky !important;
            top: 0 !important;
            z-index: 10 !important;
        }
        
        .google-visualization-table-th:first-child {
            text-align: left !important;
        }

        .google-visualization-table-td {
            font-size: 13px !important;
            color: #334155 !important; /* text-slate-700 */
            padding: 14px 16px !important;
            border: none !important;
            border-bottom: 1px solid #f1f5f9 !important; /* border-slate-100 */
            background-color: #ffffff !important;
            transition: background-color 0.15s ease !important;
            text-align: center !important;
        }

        .google-visualization-table-td:first-child {
            text-align: left !important;
            font-weight: 500 !important;
        }

        /* Hover y Selección */
        .google-visualization-table-tr-head { box-shadow: none !important; }
        .google-visualization-table-tr-sel { background-color: #f0f9ff !important; }
        .google-visualization-table-tr-over .google-visualization-table-td {
            background-color: #f8fafc !important; /* hover:bg-slate-50 */
        }
        
        /* Limpiar contenedores extras */
        .google-visualization-table-div-page {
            background: transparent !important;
            border: none !important;
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
            <img src="/Pedidos_GA/Img/Botones%20entregas/Estadisticas/ESTADISTICAS.png"
                 alt="Estadísticas"
                 class="h-14 object-contain drop-shadow">
        </div>
        <nav class="ml-auto">
            <a href="Pedidos_GA.php" title="Volver">
                <img src="/Pedidos_GA/Img/Botones%20entregas/RegistrarChofer/VOLVAZ.png"
                     alt="Volver"
                     class="icono-Volver h-9 w-auto hover:scale-105 transition-transform">
            </a>
        </nav>
    </header>

    <!-- FILTRO DE FECHAS -->
    <section class="max-w-[1500px] w-[96%] mx-auto px-4 mt-4">
        <div class="bg-white rounded-3xl shadow-gaSoft px-6 py-5">
            <h2 class="text-2xl font-extrabold text-gaBlue text-center mb-4">
                Filtrar por Rango de Fechas
            </h2>

            <div class="flex flex-wrap justify-center items-end gap-6">
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
                <button id="btn_export_excel" 
                        class="px-4 py-2 rounded-xl bg-emerald-600 text-white text-sm font-bold shadow hover:bg-emerald-700 transition flex items-center gap-2 h-[38px] mb-[2px]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                    </svg>
                    Exportar a Excel
                </button>
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

    <!-- TABS NAVEGACIÓN -->
    <section class="max-w-[1500px] w-[96%] mx-auto px-4 mt-6 print:hidden">
        <div class="border-b border-slate-200">
            <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                <button id="tab-global" class="border-gaBlue text-gaBlue whitespace-nowrap py-4 px-1 border-b-2 font-bold text-base transition-colors">
                    Comparativa Global
                </button>
                <button id="tab-sucursales" class="border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 whitespace-nowrap py-4 px-1 border-b-2 font-bold text-base transition-colors">
                    Gráficas por Sucursal
                </button>
                <button id="tab-tiempos" class="border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 whitespace-nowrap py-4 px-1 border-b-2 font-bold text-base transition-colors">
                    Tiempos de Entrega e Indicadores
                </button>
            </nav>
        </div>
    </section>

    <!-- COMPARATIVA GLOBAL (AHORA ES UNA PESTAÑA) -->
    <section id="content-global" class="max-w-[1500px] w-[96%] mx-auto px-4 mt-8 pb-10 print:block">
        <div class="flex items-center gap-3 mb-6">
            <div class="h-[2px] w-10 bg-gaBlue rounded-full"></div>
            <h3 class="text-lg font-bold text-gaBlue tracking-wide uppercase">
                Comparativa General de Rendimiento
            </h3>
        </div>
        <div class="bg-white rounded-3xl shadow-gaSoft px-6 py-6">
            <div id="total_column_chart" class="w-full h-[320px]"></div>
            <div id="summary_container" class="mt-4"></div>
        </div>
    </section>

    <!-- VISTA DETALLE SUCURSAL -->
    <section id="content-sucursales" class="max-w-[1500px] w-[96%] mx-auto px-4 mt-8 pb-10 hidden print:block">
        <div class="flex items-center gap-3 mb-6">
            <div class="h-[2px] w-10 bg-gaBlue rounded-full"></div>
            <h3 class="text-lg font-bold text-gaBlue tracking-wide uppercase">
                Rendimiento por Sucursal
            </h3>
        </div>

        <!-- Píldoras de Sucursales -->
        <div class="flex overflow-x-auto gap-2 mb-6 pb-2 print:hidden scrollbar-hide" id="sucursal_pills_container" style="scrollbar-width: none; -ms-overflow-style: none;">
            <button class="sucursal-pill active bg-gaBlue text-white px-4 py-2 rounded-lg text-xs font-semibold transition flex-shrink-0" data-nombre="AIESA">AIESA</button>
            <button class="sucursal-pill bg-white text-slate-600 hover:bg-slate-50 px-4 py-2 rounded-lg text-xs font-semibold border border-slate-200 transition flex-shrink-0" data-nombre="DEASA">DEASA</button>
            <button class="sucursal-pill bg-white text-slate-600 hover:bg-slate-50 px-4 py-2 rounded-lg text-xs font-semibold border border-slate-200 transition flex-shrink-0" data-nombre="GABSA">GABSA</button>
            <button class="sucursal-pill bg-white text-slate-600 hover:bg-slate-50 px-4 py-2 rounded-lg text-xs font-semibold border border-slate-200 transition flex-shrink-0" data-nombre="ILUMINACION">ILUMINACION</button>
            <button class="sucursal-pill bg-white text-slate-600 hover:bg-slate-50 px-4 py-2 rounded-lg text-xs font-semibold border border-slate-200 transition flex-shrink-0" data-nombre="DIMEGSA">DIMEGSA</button>
            <button class="sucursal-pill bg-white text-slate-600 hover:bg-slate-50 px-4 py-2 rounded-lg text-xs font-semibold border border-slate-200 transition flex-shrink-0" data-nombre="SEGSA">SEGSA</button>
            <button class="sucursal-pill bg-white text-slate-600 hover:bg-slate-50 px-4 py-2 rounded-lg text-xs font-semibold border border-slate-200 transition flex-shrink-0" data-nombre="FESA">FESA</button>
            <button class="sucursal-pill bg-white text-slate-600 hover:bg-slate-50 px-4 py-2 rounded-lg text-xs font-semibold border border-slate-200 transition flex-shrink-0" data-nombre="TAPATIA">TAPATIA</button>
            <button class="sucursal-pill bg-white text-slate-600 hover:bg-slate-50 px-4 py-2 rounded-lg text-xs font-semibold border border-slate-200 transition flex-shrink-0" data-nombre="VALLARTA">VALLARTA</button>
            <button class="sucursal-pill bg-white text-slate-600 hover:bg-slate-50 px-4 py-2 rounded-lg text-xs font-semibold border border-slate-200 transition flex-shrink-0" data-nombre="CODI">CODI</button>
            <button class="sucursal-pill bg-white text-slate-600 hover:bg-slate-50 px-4 py-2 rounded-lg text-xs font-semibold border border-slate-200 transition flex-shrink-0" data-nombre="QUERETARO">QUERÉTARO</button>
        </div>
        <style>
            .scrollbar-hide::-webkit-scrollbar { display: none; }
        </style>

        <!-- Contenedor de las Sucursales generadas por JS -->
        <div id="sucursales_container"></div>
    </section>

    <!-- REPORTE DE TIEMPOS DE ENTREGA -->
    <section id="content-tiempos" class="max-w-[1500px] w-[96%] mx-auto px-4 mt-8 pb-4 hidden print:block">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
            <div class="flex items-center gap-3">
                <div class="h-[2px] w-10 bg-gaBlue rounded-full"></div>
                <h3 class="text-lg font-bold text-gaBlue tracking-wide uppercase">
                    Reporte de Tiempos de Entrega e Indicadores de Efectividad
                </h3>
            </div>
            
            <div class="flex items-center gap-2 bg-white px-3 py-1.5 rounded-2xl shadow-sm border border-slate-100">
                <label for="filtro_sucursal_tiempos" class="text-xs font-bold text-slate-500 uppercase tracking-wider">Ver por Sucursal:</label>
                <select id="filtro_sucursal_tiempos" class="border border-slate-200 rounded-lg px-2 py-1 text-sm bg-slate-50 text-slate-700 focus:outline-none focus:ring-2 focus:ring-gaBlue/40 min-w-[140px] font-semibold">
                    <option value="Global">GLOBAL (Todas)</option>
                    <option value="AIESA">AIESA</option>
                    <option value="DEASA">DEASA</option>
                    <option value="DIMEGSA">DIMEGSA</option>
                    <option value="GABSA">GABSA</option>
                    <option value="ILUMINACION">ILUMINACION</option>
                    <option value="SEGSA">SEGSA</option>
                    <option value="FESA">FESA</option>
                    <option value="TAPATIA">TAPATIA</option>
                    <option value="VALLARTA">VALLARTA</option>
                    <option value="CODI">CODI</option>
                    <option value="QUERETARO">QUERÉTARO</option>
                </select>
            </div>
        </div>

        <!-- Fila Superior: KPIs y Gráficas -->
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-6">
            <!-- KPIs -->
            <div class="bg-white rounded-3xl shadow-gaCard p-6 flex flex-row items-center justify-evenly">
                <div class="flex flex-col items-center text-center">
                    <h4 class="text-xs font-bold text-slate-500 mb-2 uppercase tracking-wide">Efectividad</h4>
                    <div id="efectividad_global" class="text-5xl font-extrabold text-gaBlue">0%</div>
                    <div id="efectividad_global_detalle" class="text-[11px] font-semibold text-slate-400 mt-2">0 de 0 entregas</div>
                </div>
                <div class="w-px h-24 bg-slate-100"></div>
                <div class="flex flex-col items-center text-center">
                    <h4 class="text-xs font-bold text-slate-500 mb-2 uppercase tracking-wide">Medibles</h4>
                    <div id="medicion_global" class="text-5xl font-extrabold text-gaBlue">0%</div>
                    <div id="medicion_global_detalle" class="text-[11px] font-semibold text-slate-400 mt-2">0 de 0 con hora</div>
                </div>
            </div>

            <!-- Gráfico 1 (A tiempo vs Atrasadas) -->
            <div class="bg-white rounded-3xl shadow-gaCard p-4 flex flex-col justify-center items-center">
                <h4 class="text-xs font-bold text-slate-500 mb-2 uppercase tracking-wide">Desempeño</h4>
                <div id="chart_efectividad_pie" class="w-full h-[220px]"></div>
            </div>

            <!-- Gráfico 2 (Con hora vs Sin hora) -->
            <div class="bg-white rounded-3xl shadow-gaCard p-4 flex flex-col justify-center items-center">
                <h4 class="text-xs font-bold text-slate-500 mb-2 uppercase tracking-wide">Registro</h4>
                <div id="chart_medibles_pie" class="w-full h-[220px]"></div>
            </div>
        </div>

        <!-- Fila Inferior: Tabla de Sucursales -->
        <div class="bg-white rounded-3xl shadow-gaCard p-6 overflow-x-auto mb-6">
            <h4 class="text-xs font-bold text-slate-500 mb-4 uppercase tracking-wide">Desempeño por Sucursal</h4>
            <div id="table_div_efectividad_sucursales" class="w-full h-[220px]"></div>
        </div>

        <div class="bg-white rounded-3xl shadow-gaCard p-4 overflow-x-auto">
            <h4 class="text-sm font-semibold text-slate-500 mb-2">Detalle de Entregas</h4>
            <div id="table_div_tiempos" class="w-full h-[350px]"></div>
        </div>
    </section>

    <!-- BOTÓN IMPRIMIR -->
    <div class="max-w-[1500px] w-[96%] mx-auto px-4 pb-10 text-center">
        <button onclick="window.print()" title="Imprimir estadísticas"
                class="inline-flex items-center justify-center mt-2">
            <img src="/Pedidos_GA/Img/Botones%20entregas/Estadisticas/IMPAZ.png"
                 alt="Imprimir"
                 class="icono-Imprimir h-12 w-auto hover:scale-105 transition-transform">
        </button>
    </div>

</body>
</html>
