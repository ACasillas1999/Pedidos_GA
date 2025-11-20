<?php
// Iniciar la sesión de forma segura
ini_set('session.cookie_httponly', true);
ini_set('session.cookie_secure', true);
session_name("GA");
session_start();


?>
<!DOCTYPE html>
<html>
<script>
    window.PEDIDOS_CHOFER = <?= json_encode($pedidosAll, JSON_UNESCAPED_UNICODE) ?>;
    window.EVENTOS_PEDIDOS = <?= json_encode($eventosAll, JSON_UNESCAPED_UNICODE) ?>;
</script>


<head>
    <title>Detalles Chofer</title>
    <meta charset="utf-8">
    <link rel="stylesheet" href="styles.css">
<<<<<<< HEAD
    <link rel="icon" type="image/png" href="/Img/Botones%20entregas/ICONOSPAG/ICONOPEDIDOS.png">
=======
    <link rel="icon" type="image/png" href="/Pedidos_GA/Img/Botones%20entregas/ICONOSPAG/ICONOPEDIDOS.png">
>>>>>>> parent of 5e8b02c (parra amazon Update image paths and SQL table names)
    <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dom-to-image/2.6.0/dom-to-image.min.js"></script>

    <style>
        /* ===== Estilos del perfil ===== */
        .driver-profile {
            position: relative;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, .08);
            overflow: hidden;
            font-family: system-ui, Segoe UI, Roboto, Arial, sans-serif
        }

        .driver-cover {
            height: 180px;
            background: linear-gradient(135deg, #0a66c290, #0f172a);
            position: relative
        }

        .driver-avatar {
            position: absolute;
            left: 24px;
            bottom: -48px;
            width: 96px;
            height: 96px;
            border-radius: 999px;
            background: #e2e8f0;
            display: grid;
            place-items: center;
            font-size: 40px;
            font-weight: 700;
            color: #0f172a;
            border: 5px solid #fff;
            user-select: none
        }

        .driver-head {
            padding: 64px 24px 16px 24px;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: center;
            justify-content: space-between
        }

        .driver-title {
            display: flex;
            gap: 14px;
            align-items: center
        }

        .driver-name {
            font-size: 22px;
            font-weight: 800;
            color: #0f172a;
            margin: 0
        }

        .driver-meta {
            font-size: 13px;
            color: #475569
        }

        .driver-actions a {
            display: inline-block;
            padding: 10px 14px;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            text-decoration: none;
            color: #0f172a;
            font-weight: 600
        }

        .driver-actions a+a {
            margin-left: 8px
        }

        .driver-actions a.primary {
            background: #0a66c2;
            color: #fff;
            border-color: #0a66c2
        }

        .driver-stats {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
            padding: 0 24px 14px
        }

        .stat {
            background: #f8fafc;
            border: 1px solid #eef2f7;
            border-radius: 12px;
            padding: 12px
        }

        .stat strong {
            display: block;
            font-size: 20px
        }

        .badges {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 8px
        }

        .badge {
            font-size: 12px;
            background: #eef2ff;
            color: #1e3a8a;
            border-radius: 999px;
            padding: 6px 10px;
            border: 1px solid #e5e7eb
        }

        .driver-tabs {
            display: flex;
            gap: 8px;
            padding: 8px 8px 0 8px;
            border-top: 1px solid #eef2f7;
            background: #fff;
            position: sticky;
            top: 0;
            z-index: 1
        }

        .driver-tabs button {
            flex: 1;
            padding: 12px;
            border: 0;
            background: #f1f5f9;
            border-radius: 10px;
            font-weight: 700;
            color: #334155;
            cursor: pointer
        }

        .driver-tabs button.active {
            background: #0a66c2;
            color: #fff
        }

        .tab {
            display: none;
            padding: 16px 20px 24px
        }

        .tab.active {
            display: block
        }

        .table {
            width: 100%;
            border-collapse: collapse
        }

        .table th,
        .table td {
            padding: 10px;
            border-bottom: 1px solid #eef2f7;
            text-align: left;
            font-size: 14px
        }

        .table th {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: #64748b
        }

        .kpi {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px
        }

        .kpi .card {
            background: #f8fafc;
            border: 1px solid #eef2f7;
            border-radius: 12px;
            padding: 12px
        }

        .vehis {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px
        }

        .vehi {
            border: 1px solid #eef2f7;
            background: #f8fafc;
            border-radius: 12px;
            padding: 12px
        }

        @media (max-width:900px) {
            .driver-stats {
                grid-template-columns: repeat(2, 1fr)
            }

            .vehis {
                grid-template-columns: repeat(2, 1fr)
            }
        }

        @media (max-width:600px) {
            .driver-stats {
                grid-template-columns: 1fr
            }

            .vehis {
                grid-template-columns: 1fr
            }

            .driver-actions {
                width: 100%
            }
        }

        /* Estados (si tus ESTADO vienen en mayúsculas, agrega variantes) */
        .badge.estado-Entregado,
        .badge.estado-ENTREGADO {
            background: #e7f9e7;
            color: #217a21;
            border-color: #cfeacf
        }

        .badge.estado-Pendiente,
        .badge.estado-PENDIENTE {
            background: #fff6d6;
            color: #8a6d00;
            border-color: #fde9a8
        }

        .badge.estado-Cancelado,
        .badge.estado-CANCELADO {
            background: #ffe4e6;
            color: #9f1239;
            border-color: #fecdd3
        }

        /* Gráfica */
        #barchart {
            min-height: 380px;
        }

        .driver-avatar {
            position: absolute;
            left: 24px;
            bottom: -48px;
            width: 96px;
            height: 96px;
            border-radius: 999px;
            background: #e2e8f0;
            display: grid;
            place-items: center;
            font-size: 40px;
            font-weight: 700;
            color: #0f172a;
            border: 5px solid #fff;
            user-select: none;
            cursor: pointer;
            /* <- clic para subir/cambiar */
        }

        .driver-avatar img {
            width: 100%;
            height: 100%;
            display: block;
            border-radius: 999px;
            object-fit: cover;
        }

        .driver-avatar:hover {
            box-shadow: 0 0 0 3px rgba(10, 102, 194, .25) inset;
        }
    </style>
</head>

<body>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Listeners seguros (solo si existen)
            var iconoAddChofer = document.querySelector(".icono-AddChofer");
            if (iconoAddChofer) {
<<<<<<< HEAD
                var imgNormalAddChoferes = "/Img/Botones%20entregas/Choferes/ADDSERVMECNA.png";
                var imgHoverCAddhoferes = "/Img/Botones%20entregas/Choferes/ADDSERVMECBLANC.png";
=======
                var imgNormalAddChoferes = "/Pedidos_GA/Img/Botones%20entregas/Choferes/ADDSERVMECNA.png";
                var imgHoverCAddhoferes = "/Pedidos_GA/Img/Botones%20entregas/Choferes/ADDSERVMECBLANC.png";
>>>>>>> parent of 5e8b02c (parra amazon Update image paths and SQL table names)
                iconoAddChofer.addEventListener("mouseover", function() {
                    this.src = imgHoverCAddhoferes;
                });
                iconoAddChofer.addEventListener("mouseout", function() {
                    this.src = imgNormalAddChoferes;
                });
            }

            var iconoVolver = document.querySelector(".icono-Volver");
            if (iconoVolver) {
<<<<<<< HEAD
                var imgNormalVolver = "/Img/Botones%20entregas/Usuario/VOLVAZ.png";
                var imgHoverVolver = "/Img/Botones%20entregas/Usuario/VOLVNA.png";
=======
                var imgNormalVolver = "/Pedidos_GA/Img/Botones%20entregas/Usuario/VOLVAZ.png";
                var imgHoverVolver = "/Pedidos_GA/Img/Botones%20entregas/Usuario/VOLVNA.png";
>>>>>>> parent of 5e8b02c (parra amazon Update image paths and SQL table names)
                iconoVolver.addEventListener("mouseover", function() {
                    this.src = imgHoverVolver;
                });
                iconoVolver.addEventListener("mouseout", function() {
                    this.src = imgNormalVolver;
                });
            }

            var iconoEstadisticas = document.querySelector(".icono-estadisticas");
            if (iconoEstadisticas) {
<<<<<<< HEAD
                var imgNormalEstadisticas = "/Img/Botones%20entregas/Pedidos_GA/ESTNA2.png";
                var imgHoverEstadisticas = "/Img/Botones%20entregas/Pedidos_GA/ESTBL2.png";
=======
                var imgNormalEstadisticas = "/Pedidos_GA/Img/Botones%20entregas/Pedidos_GA/ESTNA2.png";
                var imgHoverEstadisticas = "/Pedidos_GA/Img/Botones%20entregas/Pedidos_GA/ESTBL2.png";
>>>>>>> parent of 5e8b02c (parra amazon Update image paths and SQL table names)
                iconoEstadisticas.addEventListener("mouseover", function() {
                    this.src = imgHoverEstadisticas;
                });
                iconoEstadisticas.addEventListener("mouseout", function() {
                    this.src = imgNormalEstadisticas;
                });
            }
        });
    </script>

    <div class="sidebar">
        <ul>



            <li class="corner-left-bottom">
                <a href="">
<<<<<<< HEAD
                    <img src="/Img/Botones%20entregas/Usuario/VOLVAZ.png" alt="Volver" class="icono-Volver" style="max-width: 35%; height: auto;">
=======
                    <img src="/Pedidos_GA/Img/Botones%20entregas/Usuario/VOLVAZ.png" alt="Volver" class="icono-Volver" style="max-width: 35%; height: auto;">
>>>>>>> parent of 5e8b02c (parra amazon Update image paths and SQL table names)
                </a>
            </li>
        </ul>
    </div>


</body>

</html>