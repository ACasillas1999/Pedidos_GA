<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name("GA");
    @session_start();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Pedidos GA</title>
    <link rel="icon" type="image/png" href="/Pedidos_GA/Img/Botones%20entregas/ICONOSPAG/ICONOPEDIDOS.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="styles1.css">
    <link href='https://api.mapbox.com/mapbox-gl-js/v2.6.1/mapbox-gl.css' rel='stylesheet' />
    <link rel="icon" href="Pedidos_GA/Img/Paquete.ico" type="image/x-icon">
    <script src='https://api.mapbox.com/mapbox-gl-js/v2.6.1/mapbox-gl.js'></script>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<div class="ga-dashboard-wrapper">
<?php
require_once __DIR__ . "/Conexiones/Conexion.php";

$pedidoId = isset($_GET['id']) ? intval($_GET['id']) : (isset($_POST['id']) ? intval($_POST['id']) : 0);

if ($pedidoId > 0) {
    // Validación de permisos para VR (Vendedores)
    if (($_SESSION["Rol"] ?? '') === "VR") {
        $username = $_SESSION["username"];
        $sqlUser = "SELECT Nombre FROM usuarios WHERE username = ?";
        $stmtUser = $conn->prepare($sqlUser);
        $stmtUser->bind_param("s", $username);
        $stmtUser->execute();
        $resultUser = $stmtUser->get_result();

        if ($resultUser->num_rows > 0) {
            $userData = $resultUser->fetch_assoc();
            $nombreVendedor = $userData['Nombre'];

            $sqlCheck = "SELECT VENDEDOR FROM pedidos WHERE ID = ? AND VENDEDOR = ?";
            $stmtCheck = $conn->prepare($sqlCheck);
            $stmtCheck->bind_param("is", $pedidoId, $nombreVendedor);
            $stmtCheck->execute();
            $resultCheck = $stmtCheck->get_result();

            if ($resultCheck->num_rows === 0) {
                echo "<script>
                    Swal.fire({
                        icon: 'error',
                        title: 'Acceso Denegado',
                        text: 'No tienes permisos para ver este pedido. Solo puedes ver tus propios pedidos.',
                        confirmButtonColor: '#d33'
                    }).then(() => {
                        window.location.href = 'Pedidos_GA.php';
                    });
                </script>";
                exit;
            }
        }
    }

    $sql = "SELECT * FROM pedidos WHERE ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $pedidoId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        $tipoEnvio = $row["tipo_envio"] ?? '';
        $tipoEnvioTexto = strtoupper($tipoEnvio ?: 'DOMICILIO');
        $badgeEnvioBg = "#f1f5f9";
        $badgeEnvioColor = "#334155";
        $badgeEnvioBorder = "#cbd5e1";

        switch (mb_strtolower($tipoEnvio, 'UTF-8')) {
            case "programado":
            case "domicilio":
                $badgeEnvioBg = "#dcfce7";
                $badgeEnvioColor = "#166534";
                $badgeEnvioBorder = "#86efac";
                break;
            case "paquetería":
            case "paqueteria":
                $badgeEnvioBg = "#f3e8ff";
                $badgeEnvioColor = "#6b21a8";
                $badgeEnvioBorder = "#d8b4fe";
                break;
        }

        $precio_vendedor = isset($row['precio_factura_vendedor']) ? floatval($row['precio_factura_vendedor']) : 0;
        $precio_real = isset($row['precio_factura_real']) ? floatval($row['precio_factura_real']) : 0;
        $precio_validado = isset($row['precio_validado_jc']) ? intval($row['precio_validado_jc']) : 0;
        $hubo_correccion = ($precio_vendedor != $precio_real && $precio_real > 0 && $precio_vendedor > 0);

        if (!function_exists('gaIcon')) {
            function gaIcon($name, $size = 15) {
                $s = intval($size);
                $attrs = 'width="' . $s . '" height="' . $s . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;vertical-align:middle;"';
                switch ($name) {
                    case 'box':
                        return "<svg $attrs><path d=\"M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z\"></path><polyline points=\"3.27 6.96 12 12.01 20.73 6.96\"></polyline><line x1=\"12\" y1=\"22.08\" x2=\"12\" y2=\"12\"></line></svg>";
                    case 'invoice':
                    case 'file':
                        return "<svg $attrs><path d=\"M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z\"></path><polyline points=\"14 2 14 8 20 8\"></polyline><line x1=\"16\" y1=\"13\" x2=\"8\" y2=\"13\"></line><line x1=\"16\" y1=\"17\" x2=\"8\" y2=\"17\"></line><line x1=\"10\" y1=\"9\" x2=\"8\" y2=\"9\"></line></svg>";
                    case 'building':
                        return "<svg $attrs><rect x=\"4\" y=\"2\" width=\"16\" height=\"20\" rx=\"2\" ry=\"2\"></rect><line x1=\"9\" y1=\"22\" x2=\"9\" y2=\"12\"></line><line x1=\"15\" y1=\"22\" x2=\"15\" y2=\"12\"></line><line x1=\"9\" y1=\"6\" x2=\"9.01\" y2=\"6\"></line><line x1=\"15\" y1=\"6\" x2=\"15.01\" y2=\"6\"></line></svg>";
                    case 'edit':
                        return "<svg $attrs><path d=\"M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7\"></path><path d=\"M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z\"></path></svg>";
                    case 'print':
                        return "<svg $attrs><polyline points=\"6 9 6 2 18 2 18 9\"></polyline><path d=\"M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2\"></path><rect x=\"6\" y=\"14\" width=\"12\" height=\"8\"></rect></svg>";
                    case 'user':
                        return "<svg $attrs><path d=\"M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2\"></path><circle cx=\"12\" cy=\"7\" r=\"4\"></circle></svg>";
                    case 'phone':
                        return "<svg $attrs><path d=\"M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z\"></path></svg>";
                    case 'pin':
                        return "<svg $attrs><path d=\"M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z\"></path><circle cx=\"12\" cy=\"10\" r=\"3\"></circle></svg>";
                    case 'comment':
                        return "<svg $attrs><path d=\"M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z\"></path></svg>";
                    case 'truck':
                        return "<svg $attrs><rect x=\"1\" y=\"3\" width=\"15\" height=\"13\"></rect><polygon points=\"16 8 20 8 23 11 23 16 16 16 16 8\"></polygon><circle cx=\"5.5\" cy=\"18.5\" r=\"2.5\"></circle><circle cx=\"18.5\" cy=\"18.5\" r=\"2.5\"></circle></svg>";
                    case 'briefcase':
                        return "<svg $attrs><rect x=\"2\" y=\"7\" width=\"20\" height=\"14\" rx=\"2\" ry=\"2\"></rect><path d=\"M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16\"></path></svg>";
                    case 'calendar':
                        return "<svg $attrs><rect x=\"3\" y=\"4\" width=\"18\" height=\"18\" rx=\"2\" ry=\"2\"></rect><line x1=\"16\" y1=\"2\" x2=\"16\" y2=\"6\"></line><line x1=\"8\" y1=\"2\" x2=\"8\" y2=\"6\"></line><line x1=\"3\" y1=\"10\" x2=\"21\" y2=\"10\"></line></svg>";
                    case 'flag':
                        return "<svg $attrs><path d=\"M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z\"></path><line x1=\"4\" y1=\"22\" x2=\"4\" y2=\"15\"></line></svg>";
                    case 'clock':
                        return "<svg $attrs><circle cx=\"12\" cy=\"12\" r=\"10\"></circle><polyline points=\"12 6 12 12 16 14\"></polyline></svg>";
                    case 'dollar':
                        return "<svg $attrs><line x1=\"12\" y1=\"1\" x2=\"12\" y2=\"23\"></line><path d=\"M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6\"></path></svg>";
                    case 'alert':
                        return "<svg $attrs><path d=\"M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z\"></path><line x1=\"12\" y1=\"9\" x2=\"12\" y2=\"13\"></line><line x1=\"12\" y1=\"17\" x2=\"12.01\" y2=\"17\"></line></svg>";
                    case 'plus':
                        return "<svg $attrs><line x1=\"12\" y1=\"5\" x2=\"12\" y2=\"19\"></line><line x1=\"5\" y1=\"12\" x2=\"19\" y2=\"12\"></line></svg>";
                    case 'check':
                        return "<svg $attrs><polyline points=\"20 6 9 17 4 12\"></polyline></svg>";
                    case 'refresh':
                        return "<svg $attrs><polyline points=\"23 4 23 10 17 10\"></polyline><polyline points=\"1 20 1 14 7 14\"></polyline><path d=\"M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15\"></path></svg>";
                    case 'ban':
                        return "<svg $attrs><circle cx=\"12\" cy=\"12\" r=\"10\"></circle><line x1=\"4.93\" y1=\"4.93\" x2=\"19.07\" y2=\"19.07\"></line></svg>";
                    case 'cloud-rain':
                        return "<svg $attrs><line x1=\"16\" y1=\"13\" x2=\"16\" y2=\"21\"></line><line x1=\"8\" y1=\"13\" x2=\"8\" y2=\"21\"></line><line x1=\"12\" y1=\"15\" x2=\"12\" y2=\"23\"></line><path d=\"M20 16.58A5 5 0 0 0 18 7h-1.26A8 8 0 1 0 4 15.25\"></path></svg>";
                    case 'upload':
                        return "<svg $attrs><path d=\"M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4\"></path><polyline points=\"17 8 12 3 7 8\"></polyline><line x1=\"12\" y1=\"3\" x2=\"12\" y2=\"15\"></line></svg>";
                    case 'search':
                        return "<svg $attrs><circle cx=\"11\" cy=\"11\" r=\"8\"></circle><line x1=\"21\" y1=\"21\" x2=\"16.65\" y2=\"16.65\"></line></svg>";
                    case 'eye':
                        return "<svg $attrs><path d=\"M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z\"></path><circle cx=\"12\" cy=\"12\" r=\"3\"></circle></svg>";
                    case 'camera':
                        return "<svg $attrs><path d=\"M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z\"></path><circle cx=\"12\" cy=\"13\" r=\"4\"></circle></svg>";
                    case 'image':
                        return "<svg $attrs><rect x=\"3\" y=\"3\" width=\"18\" height=\"18\" rx=\"2\" ry=\"2\"></rect><circle cx=\"8.5\" cy=\"8.5\" r=\"1.5\"></circle><polyline points=\"21 15 16 10 5 21\"></polyline></svg>";
                    case 'video':
                        return "<svg $attrs><polygon points=\"23 7 16 12 23 17 23 7\"></polygon><rect x=\"1\" y=\"5\" width=\"15\" height=\"14\" rx=\"2\" ry=\"2\"></rect></svg>";
                    case 'paperclip':
                        return "<svg $attrs><path d=\"M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48\"></path></svg>";
                    case 'download':
                        return "<svg $attrs><path d=\"M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4\"></path><polyline points=\"7 10 12 15 17 10\"></polyline><line x1=\"12\" y1=\"15\" x2=\"12\" y2=\"3\"></line></svg>";
                    case 'save':
                        return "<svg $attrs><path d=\"M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z\"></path><polyline points=\"17 21 17 13 7 13 7 21\"></polyline><polyline points=\"7 3 7 8 15 8\"></polyline></svg>";
                    default:
                        return "<svg $attrs><circle cx=\"12\" cy=\"12\" r=\"10\"></circle></svg>";
                }
            }
        }
?>

<style>
/* ==========================================================================
   SISTEMA DE DISEÑO MODERNO - DETALLE DE PEDIDO E INCIDENCIAS CRM
   ========================================================================== */
body {
    background-color: #f1f5f9 !important;
    font-family: 'Segoe UI', system-ui, -apple-system, sans-serif !important;
    color: #1e293b;
    margin: 0 !important;
    padding: 0 0 24px 0 !important;
}

.ga-dashboard-wrapper {
    width: 95%;
    max-width: 1520px;
    margin: 0 auto;
    text-align: left;
}

/* Hero Card Superior */
.ga-hero-card {
    background: #ffffff;
    border-radius: 18px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.06);
    padding: 18px 26px;
    margin-bottom: 18px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
}

.ga-hero-left {
    display: flex;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
}

.ga-hero-id-badge {
    background: linear-gradient(135deg, #0f4c81 0%, #005aa3 100%);
    color: #ffffff;
    padding: 10px 18px;
    border-radius: 14px;
    font-size: 20px;
    font-weight: 800;
    letter-spacing: -0.02em;
    box-shadow: 0 4px 12px rgba(15, 76, 129, 0.25);
}

.ga-hero-meta h1 {
    margin: 0 0 6px 0;
    font-size: 21px;
    font-weight: 800;
    color: #0f172a;
    text-align: left;
}

.ga-hero-chips {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.ga-chip {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 12px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 700;
}

.ga-chip-sucursal {
    background: #e0f2fe;
    color: #0369a1;
    border: 1px solid #bae6fd;
}

.ga-chip-factura {
    background: #fef3c7;
    color: #92400e;
    border: 1px solid #fde68a;
}

.ga-hero-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.ga-estado-control {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #f8fafc;
    border: 1px solid #cbd5e1;
    padding: 6px 12px;
    border-radius: 12px;
}

.ga-estado-control label {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    color: #64748b;
    margin: 0;
}

#estado {
    padding: 6px 12px;
    border-radius: 8px;
    border: 1px solid #cbd5e1;
    background-color: #ffffff;
    font-size: 13px;
    font-weight: 700;
    color: #0f4c81;
    cursor: pointer;
    transition: all 0.18s ease;
}

#estado:focus, #estado:hover {
    outline: none;
    border-color: #005aa3;
    box-shadow: 0 0 0 3px rgba(0, 90, 163, 0.12);
}

.ga-btn-action {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 10px 16px !important;
    margin: 0 !important;
    border-radius: 10px !important;
    font-size: 13px !important;
    font-weight: 700 !important;
    cursor: pointer;
    text-decoration: none !important;
    transition: all 0.18s ease;
    border: 1px solid transparent !important;
}

.ga-btn-edit {
    background: linear-gradient(135deg, #0f4c81 0%, #005aa3 100%) !important;
    color: #ffffff !important;
    box-shadow: 0 3px 10px rgba(15, 76, 129, 0.25);
}

.ga-btn-edit:hover {
    background: linear-gradient(135deg, #0b3c66 0%, #00447c 100%) !important;
    transform: translateY(-1px);
    box-shadow: 0 5px 14px rgba(15, 76, 129, 0.35);
}

.ga-btn-print {
    background: #ffffff !important;
    color: #334155 !important;
    border-color: #cbd5e1 !important;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.05);
}

.ga-btn-print:hover {
    background: #f8fafc !important;
    color: #0f172a !important;
    border-color: #94a3b8 !important;
    transform: translateY(-1px);
}

/* Grid de 3 Tarjetas de Información */
.ga-info-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 18px;
    margin-bottom: 18px;
}

@media (max-width: 1024px) {
    .ga-info-grid {
        grid-template-columns: 1fr;
    }
}

.ga-card {
    background: #ffffff;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 6px 18px -4px rgba(15, 23, 42, 0.05);
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.ga-card-header {
    padding: 14px 22px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.ga-card-header-icon {
    width: 34px;
    height: 34px;
    border-radius: 10px;
    background: #e0f2fe;
    color: #0f4c81;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
}

.ga-card-header h3 {
    margin: 0;
    font-size: 15px;
    font-weight: 800;
    color: #0f172a;
}

.ga-card-body {
    padding: 18px 22px;
    display: flex;
    flex-direction: column;
    gap: 14px;
    flex: 1;
}

.ga-field-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

.ga-field {
    display: flex;
    flex-direction: column;
    gap: 3px;
}

.ga-field-label {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #64748b;
}

.ga-field-value {
    font-size: 13.5px;
    font-weight: 600;
    color: #1e293b;
    word-break: break-word;
}

.ga-field-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 10px 12px;
    font-size: 13px;
    color: #334155;
    line-height: 1.45;
}

.ga-price-highlight {
    background: linear-gradient(135deg, #f8fafc 0%, #f0f9ff 100%);
    border: 1px solid #bae6fd;
    border-radius: 12px;
    padding: 14px 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 10px;
}

.ga-price-amount {
    font-size: 22px;
    font-weight: 800;
    color: #0f4c81;
}

.ga-badge-validado {
    background: #dcfce7;
    color: #166534;
    border: 1px solid #86efac;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 11.5px;
    font-weight: 700;
}

.ga-badge-sin-validar {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fca5a5;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 11.5px;
    font-weight: 700;
}

/* Módulo de Incidencias */
.incidencias-box {
    width: 100%;
    margin: 0 0 18px 0;
    background: #ffffff;
    border-radius: 18px;
    box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.06);
    border: 1px solid #e2e8f0;
    overflow: hidden;
    box-sizing: border-box;
}

.incidencias-header {
    background: linear-gradient(135deg, #0f4c81 0%, #005aa3 100%);
    color: #ffffff;
    padding: 16px 26px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 14px;
}

.incidencias-header-left {
    display: flex;
    align-items: center;
    gap: 14px;
    flex-wrap: wrap;
}

.incidencias-header-icon {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.16);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 19px;
}

.incidencias-header-title {
    display: flex;
    flex-direction: column;
    gap: 2px;
    margin: 0;
    color: #ffffff;
}

.incidencias-header-title-main {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 16.5px;
    font-weight: 700;
}

.incidencias-header-subtitle {
    font-size: 12px;
    color: rgba(255, 255, 255, 0.8);
}

.incidencias-badge-count {
    background: rgba(255, 255, 255, 0.22);
    color: #ffffff;
    padding: 3px 11px;
    border-radius: 999px;
    font-size: 11.5px;
    font-weight: 700;
    border: 1px solid rgba(255, 255, 255, 0.25);
}

.btn-reportar-incidencia {
    background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important;
    color: #ffffff !important;
    border: 1px solid rgba(255, 255, 255, 0.2) !important;
    border-radius: 10px !important;
    padding: 9px 18px !important;
    font-size: 13px !important;
    font-weight: 700 !important;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s ease;
    box-shadow: 0 4px 12px rgba(234, 88, 12, 0.35);
    text-decoration: none;
    margin: 0 !important;
}

.btn-reportar-incidencia:hover {
    background: linear-gradient(135deg, #c2410c 0%, #b45309 100%) !important;
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(234, 88, 12, 0.45);
    color: #ffffff !important;
}

.incidencias-body {
    padding: 18px 26px;
    background-color: #f8fafc;
}

.incidencias-empty-compact {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 14px;
    padding: 16px 22px;
    background: #ffffff;
    border: 1.5px dashed #cbd5e1;
    border-radius: 12px;
}

.incidencias-empty-left {
    display: flex;
    align-items: center;
    gap: 14px;
}

.incidencias-empty-icon {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: #ecfdf5;
    color: #10b981;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}

.incidencias-empty-title {
    font-size: 14.5px;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 2px 0;
}

.incidencias-empty-desc {
    font-size: 12.5px;
    color: #64748b;
    margin: 0;
}

.incidencias-table-wrap {
    width: 100%;
    overflow-x: auto;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
}

.incidencias-table {
    width: 100% !important;
    border-collapse: collapse !important;
    margin: 0 !important;
    background: #ffffff;
}

.incidencias-table th {
    background: #f8fafc !important;
    color: #475569 !important;
    font-size: 11.5px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 12px 18px !important;
    border-bottom: 1px solid #e2e8f0;
    text-align: left;
}

.incidencias-table td {
    padding: 12px 18px !important;
    font-size: 13.5px;
    color: #1e293b;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
    text-align: left;
}

.incidencias-table tbody tr:last-child td {
    border-bottom: none;
}

.incidencias-table tbody tr:hover {
    background-color: #f8fafc !important;
}

.incidencia-motivo-tag {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 13px;
    border-radius: 999px;
    font-size: 12.5px;
    font-weight: 700;
    white-space: nowrap;
}

.incidencia-autor-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: #f1f5f9;
    color: #1e293b;
    border: 1px solid #e2e8f0;
    padding: 4px 10px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 12px;
    white-space: nowrap;
}

.incidencia-fecha-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: #f8fafc;
    color: #475569;
    border: 1px solid #e2e8f0;
    padding: 4px 10px;
    border-radius: 8px;
    font-weight: 500;
    font-size: 12px;
    white-space: nowrap;
}

.incidencia-comentarios-cell {
    font-size: 13.5px;
    color: #334155;
    line-height: 1.45;
    white-space: pre-line;
}

.incidencia-adjunto {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.incidencia-thumb-wrap {
    position: relative;
    display: inline-block;
    border-radius: 8px;
    overflow: hidden;
    border: 1.5px solid #cbd5e1;
    background: #0f172a;
    cursor: pointer;
    box-shadow: 0 2px 6px rgba(15, 23, 42, 0.1);
    transition: all 0.2s ease;
    flex-shrink: 0;
}

.incidencia-thumb-wrap:hover {
    transform: scale(1.04);
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.18);
    border-color: #0284c7;
}

.incidencia-thumb-img {
    display: block;
    width: 46px;
    height: 46px;
    object-fit: cover;
}

.btn-adjunto-preview {
    display: inline-flex !important;
    align-items: center !important;
    gap: 6px !important;
    padding: 6px 14px !important;
    margin: 0 !important;
    background: #f0f9ff !important;
    color: #0369a1 !important;
    border: 1px solid #bae6fd !important;
    border-radius: 8px !important;
    font-size: 12.5px !important;
    font-weight: 700 !important;
    text-decoration: none !important;
    cursor: pointer !important;
    transition: all 0.18s ease !important;
}

.btn-adjunto-preview:hover {
    background: #0284c7 !important;
    color: #ffffff !important;
    border-color: #0284c7 !important;
    text-decoration: none !important;
}

/* Grid de 2 Columnas: Documentación + Evidencia Fotográfica */
.ga-media-grid {
    display: grid;
    grid-template-columns: 1.35fr 1fr;
    gap: 18px;
    margin-bottom: 18px;
}

@media (max-width: 900px) {
    .ga-media-grid {
        grid-template-columns: 1fr;
    }
}

.ga-doc-actions-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
}

@media (max-width: 640px) {
    .ga-doc-actions-grid {
        grid-template-columns: 1fr;
    }
}

.ga-doc-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 16px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    gap: 12px;
}

.ga-doc-box h4 {
    margin: 0 0 4px 0;
    font-size: 14px;
    font-weight: 700;
    color: #0f172a;
    display: flex;
    align-items: center;
    gap: 6px;
}

.ga-doc-box p {
    margin: 0;
    font-size: 12px;
    color: #64748b;
    line-height: 1.4;
}

.ga-doc-box input[type="file"] {
    width: 100%;
    padding: 8px;
    border: 1.5px dashed #cbd5e1;
    border-radius: 8px;
    background: #ffffff;
    font-size: 12px;
    color: #475569;
    box-sizing: border-box;
    margin-bottom: 10px;
}

.ga-btn-submit {
    width: 100%;
    background: linear-gradient(135deg, #0f4c81 0%, #005aa3 100%) !important;
    color: #ffffff !important;
    border: none !important;
    border-radius: 8px !important;
    padding: 9px 14px !important;
    font-size: 13px !important;
    font-weight: 700 !important;
    cursor: pointer;
    transition: all 0.18s ease;
    box-shadow: 0 2px 6px rgba(15, 76, 129, 0.22);
}

.ga-btn-submit:hover {
    background: linear-gradient(135deg, #0b3c66 0%, #00447c 100%) !important;
    transform: translateY(-1px);
}

.ga-btn-submit-secondary {
    background: #ffffff !important;
    color: #0f4c81 !important;
    border: 1.5px solid #0f4c81 !important;
    box-shadow: none;
}

.ga-btn-submit-secondary:hover {
    background: #f0f9ff !important;
}

#imagen-container {
    width: 100% !important;
    max-width: 100% !important;
    min-height: 160px;
    background: #f8fafc;
    border: 2px dashed #cbd5e1;
    border-radius: 12px;
    padding: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    color: #64748b;
    font-size: 13px;
    font-weight: 600;
    box-sizing: border-box;
}

#miniatura {
    max-width: 240px;
    max-height: 200px;
    width: auto;
    height: auto;
    border-radius: 10px;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.12);
    transition: transform 0.2s ease;
}

#miniatura:hover {
    transform: scale(1.03);
}

/* Historial de Estados */
.ga-history-card {
    background: #ffffff;
    border-radius: 18px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 6px 18px -4px rgba(15, 23, 42, 0.05);
    overflow: hidden;
    margin-bottom: 18px;
}

.table-custom {
    width: 100% !important;
    max-width: 100% !important;
    margin: 0 !important;
    border-collapse: collapse !important;
    background: #ffffff;
}

.table-custom th {
    background: #f8fafc !important;
    color: #475569 !important;
    font-size: 11.5px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 13px 22px !important;
    border-bottom: 1px solid #e2e8f0;
    text-align: left;
}

.table-custom td {
    padding: 13px 22px !important;
    font-size: 13.5px;
    color: #1e293b;
    border-bottom: 1px solid #f1f5f9;
    text-align: left;
}

.table-custom tr:last-child td {
    border-bottom: none;
}

.btn-mapa-historial {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #f0f9ff !important;
    color: #0369a1 !important;
    border: 1px solid #bae6fd !important;
    padding: 5px 12px !important;
    border-radius: 8px !important;
    font-size: 12px !important;
    font-weight: 700 !important;
    text-decoration: none !important;
    transition: all 0.18s ease;
}

.btn-mapa-historial:hover {
    background: #0284c7 !important;
    color: #ffffff !important;
    border-color: #0284c7 !important;
    transform: translateY(-1px);
}

/* Modal Desktop CRM */
.inc-modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(15, 23, 42, 0.65);
    z-index: 99999;
    justify-content: center;
    align-items: center;
    backdrop-filter: blur(4px);
}

.inc-modal-content {
    background: #ffffff;
    width: 92%;
    max-width: 500px;
    border-radius: 18px;
    box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.35);
    overflow: hidden;
    animation: incModalSlideIn 0.22s ease-out;
    text-align: left;
}

@keyframes incModalSlideIn {
    from { opacity: 0; transform: translateY(-16px) scale(0.97); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

.inc-modal-header {
    background: linear-gradient(135deg, #0f4c81 0%, #005aa3 100%);
    color: #ffffff;
    padding: 18px 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.inc-modal-header h3 {
    margin: 0;
    font-size: 16.5px;
    font-weight: 700;
    color: #ffffff;
    display: flex;
    align-items: center;
    gap: 8px;
}

.inc-modal-close {
    background: rgba(255, 255, 255, 0.15) !important;
    border: none !important;
    color: #ffffff !important;
    width: 32px;
    height: 32px;
    border-radius: 50% !important;
    padding: 0 !important;
    margin: 0 !important;
    font-size: 20px !important;
    cursor: pointer;
    display: inline-flex !important;
    align-items: center;
    justify-content: center;
    line-height: 1;
}

.inc-modal-close:hover {
    background: rgba(255, 255, 255, 0.28) !important;
}

.inc-modal-body {
    padding: 24px;
}

.inc-form-group {
    margin-bottom: 18px;
}

.inc-form-group:last-child {
    margin-bottom: 4px;
}

.inc-form-group label {
    display: block;
    font-size: 12.5px;
    font-weight: 700;
    color: #334155;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    margin-bottom: 8px;
}

.inc-form-control {
    width: 100%;
    padding: 11px 14px;
    border: 1.5px solid #cbd5e1;
    border-radius: 10px;
    font-size: 14px;
    color: #1e293b;
    background-color: #f8fafc;
    box-sizing: border-box;
    font-family: inherit;
    transition: all 0.15s ease;
}

.inc-form-control:focus {
    outline: none;
    background-color: #ffffff;
    border-color: #005aa3;
    box-shadow: 0 0 0 3px rgba(0, 90, 163, 0.15);
}

.inc-file-dropzone {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 14px 16px;
    border: 2px dashed #cbd5e1;
    border-radius: 12px;
    background: #f8fafc;
    color: #475569;
    cursor: pointer;
    transition: all 0.18s ease;
    text-align: center;
}

.inc-file-dropzone:hover {
    border-color: #0f4c81;
    background: #f0f9ff;
    color: #0f4c81;
}

.inc-file-selected {
    margin-top: 8px;
    display: none;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    padding: 8px 12px;
    background: #f0f9ff;
    border: 1px solid #bae6fd;
    border-radius: 8px;
    font-size: 12.5px;
    font-weight: 600;
    color: #0369a1;
}

.inc-modal-footer {
    padding: 16px 24px;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.inc-btn-cancelar {
    background: #ffffff !important;
    color: #475569 !important;
    border: 1px solid #cbd5e1 !important;
    border-radius: 10px !important;
    padding: 9px 18px !important;
    margin: 0 !important;
    font-size: 13.5px !important;
    font-weight: 600 !important;
    cursor: pointer;
}

.inc-btn-cancelar:hover {
    background: #f1f5f9 !important;
    color: #1e293b !important;
}

.inc-btn-guardar {
    background: linear-gradient(135deg, #0f4c81 0%, #005aa3 100%) !important;
    color: #ffffff !important;
    border: none !important;
    border-radius: 10px !important;
    padding: 9px 20px !important;
    margin: 0 !important;
    font-size: 13.5px !important;
    font-weight: 700 !important;
    cursor: pointer;
    display: inline-flex !important;
    align-items: center;
    gap: 8px;
    box-shadow: 0 3px 8px rgba(15, 76, 129, 0.28);
}

.inc-btn-guardar:hover {
    background: linear-gradient(135deg, #0b3c66 0%, #00447c 100%) !important;
    transform: translateY(-1px);
}
</style>

<!-- 1. HERO CARD SUPERIOR DEL PEDIDO -->
<div class="ga-hero-card">
    <div class="ga-hero-left">
        <div class="ga-hero-id-badge">#<?php echo htmlspecialchars($row["ID"]); ?></div>
        <div class="ga-hero-meta">
            <h1><?php echo htmlspecialchars($row["NOMBRE_CLIENTE"] ?: 'Registro de Pedido #' . $row["ID"]); ?></h1>
            <div class="ga-hero-chips">
                <span class="ga-chip ga-chip-factura"><?= gaIcon('invoice', 13) ?> <span>Factura: <?php echo htmlspecialchars($row["FACTURA"] ?: 'S/F'); ?></span></span>
                <span class="ga-chip ga-chip-sucursal"><?= gaIcon('building', 13) ?> <span><?php echo htmlspecialchars($row["SUCURSAL"] ?: 'SIN SUCURSAL'); ?></span></span>
                <span class="ga-chip" style="background: <?= $badgeEnvioBg ?>; color: <?= $badgeEnvioColor ?>; border: 1px solid <?= $badgeEnvioBorder ?>;">
                    <?= gaIcon('box', 13) ?> <span>Envío: <?= htmlspecialchars($tipoEnvioTexto) ?></span>
                </span>
            </div>
        </div>
    </div>

    <div class="ga-hero-actions">
        <?php if (in_array($_SESSION["Rol"] ?? '', ["JC", "Admin", "VR"])): ?>
        <div class="ga-estado-control">
            <label for="estado">Estado:</label>
            <select id="estado" onchange="cambiarEstado()">
                <option value="ACTIVO" <?php if ($row["ESTADO"] === "ACTIVO") echo "selected"; ?>>ACTIVO</option>
                <option value="EN RUTA" <?php if ($row["ESTADO"] === "EN RUTA") echo "selected"; ?>>EN RUTA</option>
                <option value="REPROGRAMADO" <?php if ($row["ESTADO"] === "REPROGRAMADO") echo "selected"; ?>>REPROGRAMADO</option>
                <option value="EN TIENDA" <?php if ($row["ESTADO"] === "EN TIENDA") echo "selected"; ?>>EN TIENDA</option>
                <option value="CANCELADO" <?php if ($row["ESTADO"] === "CANCELADO") echo "selected"; ?>>CANCELADO</option>
                <option value="ENTREGADO" <?php if ($row["ESTADO"] === "ENTREGADO") echo "selected"; ?>>ENTREGADO</option>
            </select>
            <span id="estado-actual" style="display: none;"><?php echo htmlspecialchars($row["ESTADO"]); ?></span>
        </div>
        <?php endif; ?>

        <a href="ActualizarPedido.php?id=<?php echo urlencode($row['ID']); ?>" class="ga-btn-action ga-btn-edit">
            <?= gaIcon('edit', 14) ?>
            <span>Editar Pedido</span>
        </a>

        <button type="button" class="ga-btn-action ga-btn-print" onclick="imprimirPagina()">
            <?= gaIcon('print', 14) ?>
            <span>Imprimir</span>
        </button>
    </div>
</div>

<!-- 2. GRID DE 3 TARJETAS DE INFORMACIÓN DEL PEDIDO -->
<div class="ga-info-grid">
    <!-- Tarjeta 1: Cliente y Destino -->
    <div class="ga-card">
        <div class="ga-card-header">
            <div class="ga-card-header-icon"><?= gaIcon('user', 17) ?></div>
            <h3>Datos del Cliente y Entrega</h3>
        </div>
        <div class="ga-card-body">
            <div class="ga-field">
                <span class="ga-field-label">Nombre del Cliente</span>
                <span class="ga-field-value"><?php echo htmlspecialchars($row["NOMBRE_CLIENTE"] ?: '—'); ?></span>
            </div>
            <div class="ga-field-row">
                <div class="ga-field">
                    <span class="ga-field-label">Contacto</span>
                    <span class="ga-field-value"><?php echo htmlspecialchars($row["CONTACTO"] ?: '—'); ?></span>
                </div>
                <div class="ga-field">
                    <span class="ga-field-label">Teléfono</span>
                    <span class="ga-field-value" style="display:inline-flex;align-items:center;gap:5px;"><?= gaIcon('phone', 13) ?> <span><?php echo htmlspecialchars($row["TELEFONO"] ?: '—'); ?></span></span>
                </div>
            </div>
            <div class="ga-field">
                <span class="ga-field-label">Dirección de Entrega</span>
                <div class="ga-field-box" style="display:flex;align-items:flex-start;gap:6px;"><?= gaIcon('pin', 14) ?> <span><?php echo htmlspecialchars($row["DIRECCION"] ?: 'Sin dirección registrada'); ?></span></div>
            </div>
            <?php if (!empty($row["COMENTARIOS"])): ?>
            <div class="ga-field">
                <span class="ga-field-label">Comentarios del Pedido</span>
                <div class="ga-field-box" style="background:#fffbeb; border-color:#fde68a; color:#92400e; display:flex; align-items:flex-start; gap:6px;">
                    <?= gaIcon('comment', 14) ?> <span><?php echo htmlspecialchars($row["COMENTARIOS"]); ?></span>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tarjeta 2: Logística y Programación -->
    <div class="ga-card">
        <div class="ga-card-header">
            <div class="ga-card-header-icon"><?= gaIcon('truck', 17) ?></div>
            <h3>Logística y Ventana de Entrega</h3>
        </div>
        <div class="ga-card-body">
            <div class="ga-field-row">
                <div class="ga-field">
                    <span class="ga-field-label">Chofer Asignado</span>
                    <span class="ga-field-value" style="display:inline-flex;align-items:center;gap:5px;"><?= gaIcon('truck', 14) ?> <span><?php echo htmlspecialchars($row["CHOFER_ASIGNADO"] ?: 'Sin asignar'); ?></span></span>
                </div>
                <div class="ga-field">
                    <span class="ga-field-label">Vendedor</span>
                    <span class="ga-field-value" style="display:inline-flex;align-items:center;gap:5px;"><?= gaIcon('briefcase', 14) ?> <span><?php echo htmlspecialchars($row["VENDEDOR"] ?: '—'); ?></span></span>
                </div>
            </div>
            <div class="ga-field-row">
                <div class="ga-field">
                    <span class="ga-field-label">Recepción Factura</span>
                    <span class="ga-field-value" style="display:inline-flex;align-items:center;gap:5px;"><?= gaIcon('calendar', 14) ?> <span><?php echo htmlspecialchars($row["FECHA_RECEPCION_FACTURA"] ?: '—'); ?></span></span>
                </div>
                <div class="ga-field">
                    <span class="ga-field-label">Entrega Cliente</span>
                    <span class="ga-field-value" style="display:inline-flex;align-items:center;gap:5px;"><?= gaIcon('flag', 14) ?> <span><?php echo htmlspecialchars($row["FECHA_ENTREGA_CLIENTE"] ?: 'Pendiente'); ?></span></span>
                </div>
            </div>
            <div class="ga-field-row">
                <div class="ga-field">
                    <span class="ga-field-label">Fecha Mín. / Máx.</span>
                    <span class="ga-field-value"><?php echo htmlspecialchars(($row["FECHA_MIN_ENTREGA"] ?: '—') . ' / ' . ($row["FECHA_MAX_ENTREGA"] ?: '—')); ?></span>
                </div>
                <div class="ga-field">
                    <span class="ga-field-label">Ventana Horaria</span>
                    <span class="ga-field-value" style="display:inline-flex;align-items:center;gap:5px;"><?= gaIcon('clock', 14) ?> <span><?php echo htmlspecialchars(($row["MIN_VENTANA_HORARIA_1"] ?: '—') . ' - ' . ($row["MAX_VENTANA_HORARIA_1"] ?: '—')); ?></span></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Tarjeta 3: Facturación y Validación -->
    <div class="ga-card">
        <div class="ga-card-header">
            <div class="ga-card-header-icon"><?= gaIcon('dollar', 17) ?></div>
            <h3>Facturación y Validación</h3>
        </div>
        <div class="ga-card-body">
            <div class="ga-price-highlight">
                <div>
                    <div class="ga-field-label">Importe Factura Real</div>
                    <div class="ga-price-amount">$<?php echo number_format($precio_real, 2); ?></div>
                    <?php if ($hubo_correccion): ?>
                        <div style="color: #dc2626; font-size: 12px; text-decoration: line-through; margin-top: 2px;">
                            Original: $<?php echo number_format($precio_vendedor, 2); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div>
                    <?php if ($precio_validado == 1): ?>
                        <span class="ga-badge-validado"><?= gaIcon('check', 13) ?> <span>Validado por JC</span></span>
                    <?php else: ?>
                        <span class="ga-badge-sin-validar"><?= gaIcon('alert', 13) ?> <span>Sin validar</span></span>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($precio_real > 0 && $precio_real < 1000): ?>
            <div class="ga-field-box" style="background: #fffbeb; border-color: #fcd34d; color: #92400e; font-weight: 600; display:flex; align-items:center; gap:6px;">
                <?= gaIcon('alert', 14) ?> <span>Precio menor a $1,000 — Flete no conveniente</span>
            </div>
            <?php endif; ?>

            <div class="ga-field-row">
                <div class="ga-field">
                    <span class="ga-field-label">Folio Factura</span>
                    <span class="ga-field-value"><?php echo htmlspecialchars($row["FACTURA"] ?: '—'); ?></span>
                </div>
                <div class="ga-field">
                    <span class="ga-field-label">Sucursal Origen</span>
                    <span class="ga-field-value"><?php echo htmlspecialchars($row["SUCURSAL"] ?: '—'); ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ================================================================= -->
<!-- 3. MÓDULO DE INCIDENCIAS OPERATIVAS (VENDEDOR / ESCRITORIO)       -->
<!-- ================================================================= -->
<?php
$usuarioActualNombre = $_SESSION["Nombre"] ?? $_SESSION["username"] ?? "Vendedor";
$usuarioActualRol = $_SESSION["Rol"] ?? "VR";
$rolEtiqueta = ($usuarioActualRol === "VR") ? "Vendedor" : $usuarioActualRol;
$autorCompleto = trim($usuarioActualNombre) . " (" . $rolEtiqueta . ")";

if (!function_exists('obtenerEstiloMotivoDesktop')) {
    function obtenerEstiloMotivoDesktop($motivo) {
        switch ($motivo) {
            case 'Faltante':
                return ['bg' => '#fee2e2', 'color' => '#991b1b', 'border' => '#fca5a5', 'icono' => gaIcon('box', 13)];
            case 'Sobrante':
                return ['bg' => '#ede9fe', 'color' => '#5b21b6', 'border' => '#c4b5fd', 'icono' => gaIcon('plus', 13)];
            case 'Error de entrega':
                return ['bg' => '#ffedd5', 'color' => '#9a3412', 'border' => '#fdba74', 'icono' => gaIcon('alert', 13)];
            case 'Producto erróneo':
                return ['bg' => '#ffedd5', 'color' => '#9a3412', 'border' => '#fdba74', 'icono' => gaIcon('refresh', 13)];
            case 'Cliente no recibe pedido':
                return ['bg' => '#ffe4e6', 'color' => '#9f1239', 'border' => '#fda4af', 'icono' => gaIcon('ban', 13)];
            case 'Cliente reprograma pedido':
                return ['bg' => '#e0f2fe', 'color' => '#075985', 'border' => '#7dd3fc', 'icono' => gaIcon('calendar', 13)];
            case 'Cliente no cuenta con maniobra para descarga':
                return ['bg' => '#fef3c7', 'color' => '#92400e', 'border' => '#fcd34d', 'icono' => gaIcon('truck', 13)];
            case 'Cuestiones climáticas':
                return ['bg' => '#cffafe', 'color' => '#155e75', 'border' => '#67e8f9', 'icono' => gaIcon('cloud-rain', 13)];
            default:
                return ['bg' => '#f1f5f9', 'color' => '#334155', 'border' => '#cbd5e1', 'icono' => gaIcon('alert', 13)];
        }
    }
}

$incidencias = [];
if (isset($conn) && $conn instanceof mysqli && !empty($pedidoId)) {
    $conn->query("CREATE TABLE IF NOT EXISTS `pedidos_incidencias` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `pedido_id` INT NOT NULL,
      `motivo` VARCHAR(150) NOT NULL,
      `comentarios` TEXT NULL,
      `archivo_ruta` VARCHAR(255) NULL,
      `archivo_nombre` VARCHAR(255) NULL,
      `archivo_tipo` VARCHAR(50) NULL,
      `chofer` VARCHAR(100) NULL,
      `fecha_registro` DATETIME DEFAULT CURRENT_TIMESTAMP,
      INDEX `idx_pedido_id` (`pedido_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    $stmtInc = $conn->prepare("SELECT id, pedido_id, motivo, comentarios, archivo_ruta, archivo_nombre, archivo_tipo, chofer, DATE_FORMAT(fecha_registro, '%d/%m/%Y %H:%i') as fecha_formato, fecha_registro FROM pedidos_incidencias WHERE pedido_id = ? ORDER BY id DESC");
    if ($stmtInc) {
        $stmtInc->bind_param("i", $pedidoId);
        $stmtInc->execute();
        $resInc = $stmtInc->get_result();
        while ($r = $resInc->fetch_assoc()) {
            $incidencias[] = $r;
        }
        $stmtInc->close();
    }
}
$totalIncidencias = count($incidencias);
?>

<div id="seccion-incidencias" class="incidencias-box">
    <div class="incidencias-header">
        <div class="incidencias-header-left">
            <div class="incidencias-header-icon"><?= gaIcon('alert', 19) ?></div>
            <div class="incidencias-header-title">
                <div class="incidencias-header-title-main">
                    <span>Incidencias del Pedido</span>
                    <span class="incidencias-badge-count">
                        <?= $totalIncidencias ?> <?= $totalIncidencias === 1 ? 'registrada' : 'registradas' ?>
                    </span>
                </div>
                <span class="incidencias-header-subtitle">Módulo CRM (Vendedores) y reportes en ruta</span>
            </div>
        </div>
        <div>
            <button type="button" class="btn-reportar-incidencia" onclick="abrirModalNuevaIncidencia()">
                <?= gaIcon('plus', 15) ?>
                <span>Registrar Incidencia</span>
            </button>
        </div>
    </div>

    <div class="incidencias-body">
        <?php if ($totalIncidencias === 0): ?>
            <div class="incidencias-empty-compact">
                <div class="incidencias-empty-left">
                    <div class="incidencias-empty-icon"><?= gaIcon('check', 20) ?></div>
                    <div>
                        <div class="incidencias-empty-title">Sin incidencias registradas</div>
                        <div class="incidencias-empty-desc">Este pedido no presenta reportes de Faltante, Sobrante, Error de entrega ni incidencias en ruta.</div>
                    </div>
                </div>
                <button type="button" class="btn-reportar-incidencia" onclick="abrirModalNuevaIncidencia()">
                    <?= gaIcon('plus', 15) ?>
                    <span>Registrar la primera incidencia</span>
                </button>
            </div>
        <?php else: ?>
            <div class="incidencias-table-wrap">
                <table class="incidencias-table">
                    <thead>
                        <tr>
                            <th style="width: 180px;">Motivo</th>
                            <th>Comentarios / Observaciones</th>
                            <th style="width: 190px;">Reportado por</th>
                            <th style="width: 160px;">Fecha y Hora</th>
                            <th style="width: 240px;">Evidencia / Adjunto</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($incidencias as $inc): 
                            $estilo = obtenerEstiloMotivoDesktop($inc['motivo']);
                            $esVendedor = (stripos($inc['chofer'], 'Vendedor') !== false || stripos($inc['chofer'], 'Venta') !== false || stripos($inc['chofer'], 'Admin') !== false || stripos($inc['chofer'], 'JC') !== false);
                            $iconoRol = $esVendedor ? gaIcon('user', 13) : gaIcon('truck', 13);
                            $nombreAutor = !empty($inc['chofer']) ? htmlspecialchars($inc['chofer']) : ($esVendedor ? 'Ventas' : 'Chofer en ruta');
                        ?>
                            <tr>
                                <td>
                                    <div class="incidencia-motivo-tag" style="background-color: <?= $estilo['bg'] ?>; color: <?= $estilo['color'] ?>; border: 1px solid <?= $estilo['border'] ?>;">
                                        <?= $estilo['icono'] ?>
                                        <span><?= htmlspecialchars($inc['motivo']) ?></span>
                                    </div>
                                </td>
                                <td>
                                    <?php if (!empty($inc['comentarios'])): ?>
                                        <div class="incidencia-comentarios-cell"><?= htmlspecialchars($inc['comentarios']) ?></div>
                                    <?php else: ?>
                                        <span style="color: #94a3b8; font-style: italic; font-size: 12.5px;">Sin comentarios</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="incidencia-autor-badge">
                                        <?= $iconoRol ?> <span><?= $nombreAutor ?></span>
                                    </span>
                                </td>
                                <td>
                                    <span class="incidencia-fecha-badge">
                                        <?= gaIcon('clock', 12) ?> <span><?= htmlspecialchars($inc['fecha_formato'] ?: $inc['fecha_registro']) ?></span>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($inc['archivo_ruta'])): 
                                        $rutaLimpiaInc = ltrim(str_replace('\\', '/', $inc['archivo_ruta']), './');
                                        $archivoLocalInc = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rutaLimpiaInc);
                                        $rutaUrl = file_exists($archivoLocalInc)
                                            ? ('/Pedidos_GA/' . $rutaLimpiaInc)
                                            : ('https://pedidos.grupoascencio.com.mx/Pedidos_GA/' . $rutaLimpiaInc);
                                        $rutaUrlFallbackRemota = 'https://pedidos.grupoascencio.com.mx/Pedidos_GA/' . $rutaLimpiaInc;
                                        $nombreAdj = htmlspecialchars($inc['archivo_nombre'] ?: basename($inc['archivo_ruta']));
                                        $tipoAdj = $inc['archivo_tipo'] ?? '';
                                        $esImgAdj = ($tipoAdj === 'imagen' || preg_match('/\.(jpg|jpeg|png|webp|gif|bmp|jfif|heic)$/i', $inc['archivo_ruta']));
                                        $esPdfAdj = ($tipoAdj === 'pdf' || preg_match('/\.pdf$/i', $inc['archivo_ruta']));
                                        $esVidAdj = ($tipoAdj === 'video' || preg_match('/\.(mp4|mov|avi|webm)$/i', $inc['archivo_ruta']));
                                        $motivoAdj = htmlspecialchars($inc['motivo'] ?? 'Incidencia', ENT_QUOTES);
                                        $rutaUrlJs = htmlspecialchars($rutaUrl, ENT_QUOTES);
                                        $rutaFallbackJs = htmlspecialchars($rutaUrlFallbackRemota, ENT_QUOTES);
                                        $nombreAdjJs = htmlspecialchars($inc['archivo_nombre'] ?: basename($inc['archivo_ruta']), ENT_QUOTES);
                                    ?>
                                        <div class="incidencia-adjunto">
                                            <?php if ($esImgAdj): ?>
                                                <div class="incidencia-thumb-wrap" onclick="abrirModalAdjuntoIncidencia(this.querySelector('img').src, '<?= $nombreAdjJs ?>', 'imagen', '<?= $motivoAdj ?>')" title="Haz clic para ampliar evidencia fotográfica">
                                                    <img src="<?= htmlspecialchars($rutaUrl) ?>" alt="<?= $nombreAdj ?>" class="incidencia-thumb-img" onerror="if(this.src !== '<?= $rutaFallbackJs ?>'){ this.src = '<?= $rutaFallbackJs ?>'; }">
                                                </div>
                                                <button type="button" class="btn-adjunto-preview" onclick="abrirModalAdjuntoIncidencia('<?= $rutaUrlJs ?>', '<?= $nombreAdjJs ?>', 'imagen', '<?= $motivoAdj ?>')">
                                                    <?= gaIcon('image', 14) ?> <span>Ver Fotografía</span>
                                                </button>
                                            <?php elseif ($esPdfAdj): ?>
                                                <button type="button" class="btn-adjunto-preview" onclick="abrirModalAdjuntoIncidencia('<?= $rutaUrlJs ?>', '<?= $nombreAdjJs ?>', 'pdf', '<?= $motivoAdj ?>')">
                                                    <?= gaIcon('file', 14) ?> <span>Ver PDF (<?= $nombreAdj ?>)</span>
                                                </button>
                                            <?php elseif ($esVidAdj): ?>
                                                <button type="button" class="btn-adjunto-preview" onclick="abrirModalAdjuntoIncidencia('<?= $rutaUrlJs ?>', '<?= $nombreAdjJs ?>', 'video', '<?= $motivoAdj ?>')">
                                                    <?= gaIcon('video', 14) ?> <span>Ver Video (<?= $nombreAdj ?>)</span>
                                                </button>
                                            <?php else: ?>
                                                <button type="button" class="btn-adjunto-preview" onclick="abrirModalAdjuntoIncidencia('<?= $rutaUrlJs ?>', '<?= $nombreAdjJs ?>', 'otro', '<?= $motivoAdj ?>')">
                                                    <?= gaIcon('paperclip', 14) ?> <span>Ver Documento (<?= $nombreAdj ?>)</span>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <span style="color: #94a3b8; font-size: 12.5px;">Sin adjunto</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- 4. GRID DE 2 COLUMNAS: DOCUMENTACIÓN Y EVIDENCIA FOTOGRÁFICA -->
<div class="ga-media-grid">
    <div class="ga-card">
        <div class="ga-card-header">
            <div class="ga-card-header-icon"><?= gaIcon('file', 17) ?></div>
            <h3>Documentación del Pedido</h3>
        </div>
        <div class="ga-card-body">
            <?php
            if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accion']) && isset($_POST['id'])) {
                $accionPost = $_POST['accion'];
                if ($accionPost === "cargar") {
                    cargarArchivo($pedidoId);
                } elseif ($accionPost === "descargar") {
                    descargarArchivo($pedidoId);
                } elseif ($accionPost === "consultar") {
                    consultarArchivo($pedidoId);
                }
            }

            $rutaArchivoCargado = trim((string)(obtenerRutaArchivoCargado($pedidoId) ?: ($row['Ruta'] ?? '')));
            $urlDocPedido = '';
            $nombreDocPedido = '';
            $tipoDocPedido = 'otro';
            $contenidoTxtDocPedido = '';

            if (!empty($rutaArchivoCargado)) {
                $rutaDocLimpia = ltrim(str_replace('\\', '/', $rutaArchivoCargado), './');
                $archivoDocLocal = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rutaDocLimpia);
                $urlDocPedido = file_exists($archivoDocLocal)
                    ? ('/Pedidos_GA/' . $rutaDocLimpia)
                    : ('https://pedidos.grupoascencio.com.mx/Pedidos_GA/' . $rutaDocLimpia);
                $nombreDocPedido = basename($rutaDocLimpia);
                $extDocPedido = strtolower(pathinfo($nombreDocPedido, PATHINFO_EXTENSION));
                if ($extDocPedido === 'pdf') {
                    $tipoDocPedido = 'pdf';
                } elseif (in_array($extDocPedido, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp', 'jfif'])) {
                    $tipoDocPedido = 'imagen';
                } elseif ($extDocPedido === 'txt') {
                    $tipoDocPedido = 'txt';
                    if (file_exists($archivoDocLocal)) {
                        $contenidoTxtDocPedido = (string)@file_get_contents($archivoDocLocal);
                    }
                }
            }

            if ($rutaArchivoCargado):
            ?>
                <div class="ga-field-box" style="background: #f0fdf4; border-color: #bbf7d0; color: #166534; font-weight: 600; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px;">
                    <span style="display:inline-flex;align-items:center;gap:6px;"><?= gaIcon('check', 14) ?> <span>Documento actual: <strong><?= htmlspecialchars($nombreDocPedido) ?></strong></span></span>
                    <button type="button" class="btn-adjunto-preview" onclick="verDocumentoPedidoModal()">
                        <?= gaIcon('eye', 14) ?> <span>Vista rápida</span>
                    </button>
                </div>
            <?php endif; ?>

            <div class="ga-doc-actions-grid">
                <div class="ga-doc-box">
                    <div>
                        <h4><?= gaIcon('upload', 15) ?> <span>Cargar Documento</span></h4>
                        <p>Formatos permitidos: PDF, DOC, DOCX o TXT.</p>
                    </div>
                    <form action="Inicio.php?id=<?php echo $pedidoId; ?>" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?php echo $pedidoId; ?>">
                        <input type="hidden" name="accion" value="cargar">
                        <input type="file" name="archivo" accept=".pdf,.doc,.docx,.txt">
                        <input type="submit" value="Subir Archivo" class="ga-btn-submit">
                    </form>
                </div>

                <div class="ga-doc-box">
                    <div>
                        <h4><?= gaIcon('search', 15) ?> <span>Consultar Documento</span></h4>
                        <p>Visualiza o descarga el archivo adjunto actual del pedido sin recargar la página.</p>
                    </div>
                    <div>
                        <button type="button" class="ga-btn-submit ga-btn-submit-secondary" onclick="verDocumentoPedidoModal()">
                            Ver Documento
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php
    $rutaFotoRaw = trim((string)($row['Ruta_fotos'] ?? $row['Ruta_Fotos'] ?? ''));
    $urlFotoEntregaInicial = '';
    $urlFotoEntregaFallback = '';
    $etiquetaOrigenFoto = 'Evidencia de Entrega';

    if (!empty($rutaFotoRaw)) {
        $rutaFotoLimpia = ltrim(str_replace('\\', '/', $rutaFotoRaw), './');
        $archivoFotoLocal = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rutaFotoLimpia);
        $urlFotoEntregaInicial = file_exists($archivoFotoLocal)
            ? ('/Pedidos_GA/' . $rutaFotoLimpia)
            : ('https://pedidos.grupoascencio.com.mx/Pedidos_GA/' . $rutaFotoLimpia);
        $urlFotoEntregaFallback = 'https://pedidos.grupoascencio.com.mx/Pedidos_GA/' . $rutaFotoLimpia;
    }

    $urlFotoIncidenciaChofer = '';
    $urlFotoIncidenciaChoferFallback = '';
    $motivoFotoIncidenciaChofer = '';
    foreach ($incidencias as $incItem) {
        if (!empty($incItem['archivo_ruta'])) {
            $tipoItem = $incItem['archivo_tipo'] ?? '';
            if ($tipoItem === 'imagen' || preg_match('/\.(jpg|jpeg|png|webp|gif|bmp|jfif|heic)$/i', $incItem['archivo_ruta'])) {
                $rutaIncLimpia = ltrim(str_replace('\\', '/', $incItem['archivo_ruta']), './');
                $archivoIncLocal = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rutaIncLimpia);
                $urlFotoIncidenciaChofer = file_exists($archivoIncLocal)
                    ? ('/Pedidos_GA/' . $rutaIncLimpia)
                    : ('https://pedidos.grupoascencio.com.mx/Pedidos_GA/' . $rutaIncLimpia);
                $urlFotoIncidenciaChoferFallback = 'https://pedidos.grupoascencio.com.mx/Pedidos_GA/' . $rutaIncLimpia;
                $motivoFotoIncidenciaChofer = $incItem['motivo'] ?? 'Incidencia en ruta';
                break;
            }
        }
    }
    ?>
    <div class="ga-card">
        <div class="ga-card-header">
            <div class="ga-card-header-icon"><?= gaIcon('camera', 17) ?></div>
            <h3>Evidencia Fotográfica de Entrega</h3>
        </div>
        <div class="ga-card-body" style="justify-content: center;">
            <div id="imagen-container" style="flex-direction: column; gap: 10px;">
                <?php if (!empty($urlFotoEntregaInicial)): ?>
                    <img id="miniatura"
                         src="<?= htmlspecialchars($urlFotoEntregaInicial) ?>"
                         alt="Evidencia fotográfica del pedido #<?= intval($pedidoId) ?>"
                         onclick="abrirModalAdjuntoIncidencia(this.src, 'Pedido #<?= intval($pedidoId) ?>', 'imagen', 'Evidencia Fotográfica de Entrega')"
                         onerror="if(this.src !== '<?= htmlspecialchars($urlFotoEntregaFallback, ENT_QUOTES) ?>'){ this.src = '<?= htmlspecialchars($urlFotoEntregaFallback, ENT_QUOTES) ?>'; }">
                    <button type="button" class="btn-adjunto-preview" onclick="abrirModalAdjuntoIncidencia(document.getElementById('miniatura').src, 'Pedido #<?= intval($pedidoId) ?>', 'imagen', 'Evidencia Fotográfica de Entrega')">
                        <?= gaIcon('search', 14) ?> <span>Ampliar Evidencia Fotográfica</span>
                    </button>
                <?php elseif (!empty($urlFotoIncidenciaChofer)): ?>
                    <img id="miniatura"
                         src="<?= htmlspecialchars($urlFotoIncidenciaChofer) ?>"
                         alt="Evidencia adjunta por chofer - Pedido #<?= intval($pedidoId) ?>"
                         onclick="abrirModalAdjuntoIncidencia(this.src, 'Pedido #<?= intval($pedidoId) ?>', 'imagen', 'Evidencia Chofer · <?= htmlspecialchars($motivoFotoIncidenciaChofer, ENT_QUOTES) ?>')"
                         onerror="if(this.src !== '<?= htmlspecialchars($urlFotoIncidenciaChoferFallback, ENT_QUOTES) ?>'){ this.src = '<?= htmlspecialchars($urlFotoIncidenciaChoferFallback, ENT_QUOTES) ?>'; }">
                    <button type="button" class="btn-adjunto-preview" onclick="abrirModalAdjuntoIncidencia(document.getElementById('miniatura').src, 'Pedido #<?= intval($pedidoId) ?>', 'imagen', 'Evidencia Chofer · <?= htmlspecialchars($motivoFotoIncidenciaChofer, ENT_QUOTES) ?>')">
                        <?= gaIcon('search', 14) ?> <span>Ampliar Evidencia (<?= htmlspecialchars($motivoFotoIncidenciaChofer) ?>)</span>
                    </button>
                <?php else: ?>
                    <img id="miniatura" src="" alt="Miniatura de la imagen de pedido" style="display:none;" onclick="abrirModalAdjuntoIncidencia(this.src, 'Pedido #<?= intval($pedidoId) ?>', 'imagen', 'Evidencia Fotográfica de Entrega')">
                    <span id="imagen-status-msg" style="display:inline-flex;align-items:center;gap:6px;"><?= gaIcon('clock', 15) ?> <span>Consultando evidencia fotográfica...</span></span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- 5. HISTORIAL DE ESTADOS Y COORDENADAS -->
<div class="ga-history-card">
    <div class="ga-card-header">
        <div class="ga-card-header-icon"><?= gaIcon('clock', 17) ?></div>
        <h3>Bitácora de Estados y Coordenadas GPS</h3>
    </div>
    <table class="table-custom">
        <thead>
            <tr>
                <th>Estado</th>
                <th>Fecha</th>
                <th>Hora</th>
                <th>Ubicación GPS</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $detalleSql = "SELECT Estado, Fecha, Hora, Coordenada FROM EstadoPedido WHERE ID_Pedido = ?";
            $detalleStmt = $conn->prepare($detalleSql);
            $detalleStmt->bind_param("i", $pedidoId);
            $detalleStmt->execute();
            $detalleResult = $detalleStmt->get_result();

            if ($detalleResult->num_rows > 0):
                while ($detalleRow = $detalleResult->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td><strong>" . htmlspecialchars($detalleRow["Estado"]) . "</strong></td>";
                    echo "<td>" . htmlspecialchars($detalleRow["Fecha"]) . "</td>";
                    echo "<td>" . htmlspecialchars($detalleRow["Hora"]) . "</td>";

                    $coordenadas = explode(',', $detalleRow["Coordenada"] ?? '');
                    $latitud = trim($coordenadas[0] ?? '');
                    $longitud = isset($coordenadas[1]) ? trim($coordenadas[1]) : null;

                    if ($latitud && $longitud) {
                        $googleMapsLink = "https://www.google.com/maps/search/?api=1&query=$latitud,$longitud";
                        echo "<td><a href='$googleMapsLink' target='_blank' class='btn-mapa-historial'>" . gaIcon('pin', 13) . " <span>Ver en Google Maps</span></a></td>";
                    } else {
                        echo "<td style='color:#94a3b8;'>Coordenadas no disponibles</td>";
                    }
                    echo "</tr>";
                }
            else:
                echo "<tr><td colspan='4' style='text-align:center; color:#64748b; padding:20px !important;'>Sin movimientos registrados en la bitácora de estados.</td></tr>";
            endif;

            $detalleStmt->close();
            $conn->close();
            ?>
        </tbody>
    </table>
</div>

<!-- Modal para Registrar Nueva Incidencia -->
<div id="modalIncidenciaDesktop" class="inc-modal-overlay" onclick="if(event.target === this) cerrarModalNuevaIncidencia()">
    <div class="inc-modal-content">
        <div class="inc-modal-header">
            <h3><?= gaIcon('alert', 17) ?> <span>Registrar Incidencia del Pedido</span></h3>
            <button type="button" class="inc-modal-close" onclick="cerrarModalNuevaIncidencia()">&times;</button>
        </div>
        <form id="formNuevaIncidenciaDesktop" onsubmit="guardarIncidenciaDesktop(event)">
            <div class="inc-modal-body">
                <div class="inc-form-group">
                    <label for="inc_motivo">Motivo de la Incidencia *</label>
                    <select id="inc_motivo" class="inc-form-control" required>
                        <option value="">-- Selecciona el motivo --</option>
                        <option value="Faltante">Faltante</option>
                        <option value="Sobrante">Sobrante</option>
                        <option value="Error de entrega">Error de entrega</option>
                    </select>
                </div>

                <div class="inc-form-group">
                    <label for="inc_comentarios">Comentarios adicionales</label>
                    <textarea id="inc_comentarios" class="inc-form-control" rows="3" placeholder="Escribe una breve explicación de la incidencia..."></textarea>
                </div>

                <div class="inc-form-group">
                    <label for="inc_archivo">Evidencia o Documento Adjunto (Opcional)</label>
                    <label for="inc_archivo" class="inc-file-dropzone">
                        <span style="color:#0f4c81;"><?= gaIcon('upload', 22) ?></span>
                        <span style="font-size:13px; font-weight:700; color:#1e293b;">Seleccionar imagen, PDF o documento</span>
                        <span style="font-size:11.5px; color:#64748b;">Formatos: JPG, PNG, WEBP, PDF, DOC, DOCX, XLS, TXT, MP4</span>
                    </label>
                    <input type="file" id="inc_archivo" accept=".jpg,.jpeg,.png,.webp,.gif,.bmp,.heic,.pdf,.doc,.docx,.xls,.xlsx,.txt,.csv,.ppt,.pptx,.mp4,.mov,.avi,.webm" style="display:none;" onchange="mostrarArchivoSeleccionadoInc(this)">
                    <div id="inc_archivo_info" class="inc-file-selected">
                        <span id="inc_archivo_nombre" style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"></span>
                        <button type="button" onclick="limpiarArchivoSeleccionadoInc()" style="background:transparent !important; border:none !important; color:#dc2626 !important; padding:2px 6px !important; margin:0 !important; font-size:12px !important; font-weight:700 !important; cursor:pointer;">Quitar</button>
                    </div>
                </div>
            </div>
            <div class="inc-modal-footer">
                <button type="button" class="inc-btn-cancelar" onclick="cerrarModalNuevaIncidencia()">Cancelar</button>
                <button type="submit" id="btnGuardarIncidencia" class="inc-btn-guardar">
                    <?= gaIcon('save', 15) ?> <span>Guardar Incidencia</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal para Vista Previa de Documento / Evidencia de Incidencia -->
<div id="modalAdjuntoIncidencia" class="inc-modal-overlay" onclick="if(event.target === this) cerrarModalAdjuntoIncidencia()">
    <div class="inc-modal-content" style="max-width: 820px; width: 94%;">
        <div class="inc-modal-header">
            <h3 id="modalAdjuntoTitulo"><?= gaIcon('paperclip', 17) ?> <span>Documento Adjunto de Incidencia</span></h3>
            <button type="button" class="inc-modal-close" onclick="cerrarModalAdjuntoIncidencia()">&times;</button>
        </div>
        <div id="modalAdjuntoBody" class="inc-modal-body" style="background: #f8fafc; text-align: center; max-height: 74vh; overflow: auto; padding: 20px;">
        </div>
        <div class="inc-modal-footer" style="justify-content: space-between; flex-wrap: wrap;">
            <div id="modalAdjuntoZoomControls" style="display: none; gap: 6px; align-items: center;">
                <button type="button" class="inc-btn-cancelar" style="padding: 6px 12px !important; font-size: 12.5px !important;" onclick="zoomAdjuntoIncidencia(-0.2)">− Zoom</button>
                <button type="button" class="inc-btn-cancelar" style="padding: 6px 12px !important; font-size: 12.5px !important;" onclick="zoomAdjuntoIncidencia(0)">100%</button>
                <button type="button" class="inc-btn-cancelar" style="padding: 6px 12px !important; font-size: 12.5px !important;" onclick="zoomAdjuntoIncidencia(0.2)">+ Zoom</button>
            </div>
            <div style="display: flex; gap: 10px; align-items: center; margin-left: auto;">
                <a id="btnDescargarAdjuntoInc" href="#" target="_blank" download class="inc-btn-guardar" style="text-decoration: none !important;">
                    <?= gaIcon('download', 15) ?> <span>Descargar / Abrir en pestaña</span>
                </a>
                <button type="button" class="inc-btn-cancelar" onclick="cerrarModalAdjuntoIncidencia()">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para mostrar la imagen en tamaño completo -->
<div class="modal fade" id="modal-imagen" tabindex="-1" role="dialog" aria-labelledby="modal-imagen-label" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-imagen-label">Vista previa de la evidencia</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" style="text-align:center; overflow:auto;">
                <img id="imagen" src="" alt="Imagen de pedido" style="max-width:100%; height:auto;">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" onclick="zoomIn()">Zoom +</button>
                <button type="button" class="btn btn-primary" onclick="zoomOut()">Zoom -</button>
            </div>
        </div>
    </div>
</div>

<script>
function cambiarEstado() {
    var nuevoEstado = document.getElementById("estado").value;
    var estadoActual = "<?php echo addslashes($row["ESTADO"]); ?>";

    if (confirm("¿Estás seguro de cambiar el estado a " + nuevoEstado + "?")) {
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "actualizar_estado.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4 && xhr.status === 200) {
                var correoXHR = new XMLHttpRequest();
                correoXHR.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                correoXHR.send("estado=" + nuevoEstado);

                document.getElementById("estado").value = nuevoEstado;
                alert("Estado actualizado correctamente.");
            }
        };
        xhr.send("id=<?php echo $pedidoId; ?>&estado=" + nuevoEstado);
    } else {
        document.getElementById("estado").value = estadoActual;
    }
}

function imprimirPagina() {
    window.print();
}

function abrirModalNuevaIncidencia() {
    document.getElementById('modalIncidenciaDesktop').style.display = 'flex';
    document.getElementById('inc_motivo').focus();
}

function cerrarModalNuevaIncidencia() {
    document.getElementById('modalIncidenciaDesktop').style.display = 'none';
    document.getElementById('formNuevaIncidenciaDesktop').reset();
    limpiarArchivoSeleccionadoInc();
}

function mostrarArchivoSeleccionadoInc(input) {
    var infoBox = document.getElementById('inc_archivo_info');
    var nombreSpan = document.getElementById('inc_archivo_nombre');
    if (input.files && input.files.length > 0) {
        var f = input.files[0];
        var sizeKB = (f.size / 1024).toFixed(1);
        nombreSpan.innerHTML = '<?= addslashes(gaIcon("paperclip", 13)) ?> ' + f.name + ' (' + sizeKB + ' KB)';
        infoBox.style.display = 'flex';
    } else {
        infoBox.style.display = 'none';
        nombreSpan.textContent = '';
    }
}

function limpiarArchivoSeleccionadoInc() {
    var input = document.getElementById('inc_archivo');
    var infoBox = document.getElementById('inc_archivo_info');
    var nombreSpan = document.getElementById('inc_archivo_nombre');
    if (input) input.value = '';
    if (infoBox) infoBox.style.display = 'none';
    if (nombreSpan) nombreSpan.textContent = '';
}

var scaleAdjuntoInc = 1;

function abrirModalAdjuntoIncidencia(url, nombre, tipo, motivo) {
    scaleAdjuntoInc = 1;
    var modal = document.getElementById('modalAdjuntoIncidencia');
    var titulo = document.getElementById('modalAdjuntoTitulo');
    var body = document.getElementById('modalAdjuntoBody');
    var btnDescargar = document.getElementById('btnDescargarAdjuntoInc');
    var zoomControls = document.getElementById('modalAdjuntoZoomControls');

    var icono = '<?= addslashes(gaIcon("paperclip", 17)) ?>';
    if (tipo === 'imagen') icono = '<?= addslashes(gaIcon("image", 17)) ?>';
    else if (tipo === 'pdf' || tipo === 'txt') icono = '<?= addslashes(gaIcon("file", 17)) ?>';
    else if (tipo === 'video') icono = '<?= addslashes(gaIcon("video", 17)) ?>';

    titulo.innerHTML = icono + ' <span>' + (motivo ? motivo + ' · ' : '') + nombre + '</span>';
    btnDescargar.href = url;
    btnDescargar.setAttribute('download', nombre);

    if (tipo === 'imagen') {
        zoomControls.style.display = 'flex';
        var fallbackRemoteUrl = (url.indexOf('/Pedidos_GA/') === 0)
            ? ('https://pedidos.grupoascencio.com.mx' + url)
            : url;
        body.innerHTML = '<div style="overflow: auto; max-height: 65vh; display: flex; justify-content: center; align-items: center; background: #0f172a; border-radius: 12px; padding: 12px;">' +
            '<img id="imgPreviewIncModal" src="' + url + '" alt="' + nombre + '" style="max-width: 100%; max-height: 60vh; border-radius: 8px; transition: transform 0.18s ease; transform-origin: center center;" onerror="if(this.src !== \'' + fallbackRemoteUrl + '\'){ this.src = \'' + fallbackRemoteUrl + '\'; }">' +
            '</div>';
    } else if (tipo === 'pdf') {
        zoomControls.style.display = 'none';
        body.innerHTML = '<iframe src="' + url + '" style="width: 100%; height: 65vh; border: 1px solid #cbd5e1; border-radius: 12px; background: #ffffff;"></iframe>';
    } else if (tipo === 'txt') {
        zoomControls.style.display = 'none';
        var txtContent = window.__contenidoTxtDocPedido || '';
        if (txtContent) {
            body.innerHTML = '<textarea readonly style="width: 100%; min-height: 55vh; padding: 14px; border: 1px solid #cbd5e1; border-radius: 12px; background: #ffffff; font-family: monospace; font-size: 13px; color: #1e293b; box-sizing: border-box;">' +
                txtContent.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;') +
                '</textarea>';
        } else {
            body.innerHTML = '<iframe src="' + url + '" style="width: 100%; height: 60vh; border: 1px solid #cbd5e1; border-radius: 12px; background: #ffffff;"></iframe>';
        }
    } else if (tipo === 'video') {
        zoomControls.style.display = 'none';
        body.innerHTML = '<div style="background: #0f172a; border-radius: 12px; padding: 12px;">' +
            '<video src="' + url + '" controls autoplay style="max-width: 100%; max-height: 62vh; border-radius: 8px;"></video>' +
            '</div>';
    } else {
        zoomControls.style.display = 'none';
        body.innerHTML = '<div style="padding: 36px 20px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px;">' +
            '<div style="color: #0f4c81; margin-bottom: 12px;"><?= addslashes(gaIcon("file", 42)) ?></div>' +
            '<div style="font-size: 16px; font-weight: 700; color: #0f172a; margin-bottom: 6px;">' + nombre + '</div>' +
            '<p style="font-size: 13px; color: #64748b; margin: 0;">Haz clic en el botón inferior para descargar o abrir este documento.</p>' +
            '</div>';
    }

    modal.style.display = 'flex';
}

function cerrarModalAdjuntoIncidencia() {
    var modal = document.getElementById('modalAdjuntoIncidencia');
    var body = document.getElementById('modalAdjuntoBody');
    modal.style.display = 'none';
    body.innerHTML = '';
}

function zoomAdjuntoIncidencia(delta) {
    var img = document.getElementById('imgPreviewIncModal');
    if (!img) return;
    if (delta === 0) {
        scaleAdjuntoInc = 1;
    } else {
        scaleAdjuntoInc = Math.max(0.4, Math.min(3.5, scaleAdjuntoInc + delta));
    }
    img.style.transform = 'scale(' + scaleAdjuntoInc + ')';
}

window.__contenidoTxtDocPedido = <?= json_encode($contenidoTxtDocPedido, JSON_UNESCAPED_UNICODE) ?>;

function verDocumentoPedidoModal() {
    var urlDoc = <?= json_encode($urlDocPedido, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    var nombreDoc = <?= json_encode($nombreDocPedido, JSON_UNESCAPED_UNICODE) ?>;
    var tipoDoc = <?= json_encode($tipoDocPedido, JSON_UNESCAPED_UNICODE) ?>;

    if (!urlDoc) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'info',
                title: 'Sin documento adjunto',
                text: 'No hay ningún documento cargado actualmente para este pedido.',
                confirmButtonColor: '#0f4c81'
            });
        } else {
            alert('No hay ningún documento cargado para este pedido.');
        }
        return;
    }

    abrirModalAdjuntoIncidencia(urlDoc, nombreDoc, tipoDoc, 'Documentación del Pedido');
}

async function guardarIncidenciaDesktop(event) {
    event.preventDefault();
    const motivo = document.getElementById('inc_motivo').value.trim();
    const comentarios = document.getElementById('inc_comentarios').value.trim();
    const btnGuardar = document.getElementById('btnGuardarIncidencia');

    if (!motivo) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'warning',
                title: 'Motivo requerido',
                text: 'Por favor selecciona un motivo para la incidencia.'
            });
        } else {
            alert('Por favor selecciona un motivo para la incidencia.');
        }
        return;
    }

    const formData = new FormData();
    formData.append('pedido_id', '<?= intval($pedidoId) ?>');
    formData.append('motivo', motivo);
    formData.append('comentarios', comentarios);
    formData.append('chofer', '<?= htmlspecialchars($autorCompleto, ENT_QUOTES) ?>');

    const archivoInput = document.getElementById('inc_archivo');
    if (archivoInput && archivoInput.files && archivoInput.files.length > 0) {
        formData.append('archivo', archivoInput.files[0]);
    }

    btnGuardar.disabled = true;
    btnGuardar.innerHTML = '<span>Guardando...</span>';

    try {
        const response = await fetch('/Pedidos_GA/App/guardar_incidencia.php', {
            method: 'POST',
            body: formData
        });

        const res = await response.json();
        if (res.ok) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: '¡Incidencia registrada!',
                    text: 'La incidencia se guardó correctamente y queda registrada en el pedido.',
                    confirmButtonColor: '#0f4c81'
                }).then(() => {
                    window.location.reload();
                });
            } else {
                alert('Incidencia registrada con éxito.');
                window.location.reload();
            }
        } else {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Error al registrar',
                    text: res.error || 'Ocurrió un error al guardar la incidencia.'
                });
            } else {
                alert('Error al registrar: ' + (res.error || 'Error desconocido'));
            }
            btnGuardar.disabled = false;
            btnGuardar.innerHTML = '<?= addslashes(gaIcon("save", 15)) ?> <span>Guardar Incidencia</span>';
        }
    } catch (err) {
        console.error('Error guardando incidencia:', err);
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Error de conexión',
                text: 'No se pudo comunicar con el servidor para registrar la incidencia.'
            });
        } else {
            alert('No se pudo comunicar con el servidor.');
        }
        btnGuardar.disabled = false;
        btnGuardar.innerHTML = '<?= addslashes(gaIcon("save", 15)) ?> <span>Guardar Incidencia</span>';
    }
}

var scaleFactor = 1;
function zoomIn() {
    scaleFactor += 0.1;
    aplicarZoom();
}
function zoomOut() {
    scaleFactor -= 0.1;
    aplicarZoom();
}
function aplicarZoom() {
    var imagen = document.getElementById("imagen");
    imagen.style.transform = "scale(" + scaleFactor + ")";
}
function obtenerImagen() {
    var id_pedido = <?php echo intval($row["ID"]); ?>;
    var miniatura = document.getElementById("miniatura");
    var imagenModal = document.getElementById("imagen");
    var container = document.getElementById("imagen-container");

    if (miniatura && miniatura.getAttribute("src") && miniatura.getAttribute("src").trim() !== "") {
        if (imagenModal) {
            imagenModal.src = miniatura.src;
        }
        return;
    }

    var localUrl = "/Pedidos_GA/App/Ver_Foto.php?id_pedido=" + id_pedido;
    var remoteUrl = "https://pedidos.grupoascencio.com.mx/Pedidos_GA/App/Ver_Foto.php?id_pedido=" + id_pedido;

    function mostrarFotoEncontrada(ruta_imagen) {
        if (miniatura) {
            miniatura.src = ruta_imagen;
            miniatura.style.display = "block";
        }
        if (imagenModal) {
            imagenModal.src = ruta_imagen;
        }
        var statusMsg = document.getElementById("imagen-status-msg");
        if (statusMsg) {
            statusMsg.outerHTML = '<button type="button" class="btn-adjunto-preview" onclick="abrirModalAdjuntoIncidencia(document.getElementById(\'miniatura\').src, \'Pedido #' + id_pedido + '\', \'imagen\', \'Evidencia Fotográfica de Entrega\')"><?= addslashes(gaIcon("search", 14)) ?> <span>Ampliar Evidencia Fotográfica</span></button>';
        }
    }

    fetch(localUrl)
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data && data[0] && data[0].found) {
                mostrarFotoEncontrada(data[0].ruta_imagen);
            } else {
                return fetch(remoteUrl)
                    .then(function(r) { return r.json(); })
                    .then(function(remoteData) {
                        if (remoteData && remoteData[0] && remoteData[0].found) {
                            mostrarFotoEncontrada(remoteData[0].ruta_imagen);
                        } else if (container) {
                            container.innerHTML = "<span style='display:inline-flex;align-items:center;gap:6px;'><?= addslashes(gaIcon('camera', 16)) ?> <span>Sin fotografía de entrega registrada</span></span>";
                        }
                    });
            }
        })
        .catch(function(error) {
            console.error('Error al obtener la imagen:', error);
            if (container) {
                container.innerHTML = "<span style='display:inline-flex;align-items:center;gap:6px;'><?= addslashes(gaIcon('camera', 16)) ?> <span>Sin fotografía disponible</span></span>";
            }
        });
}
window.addEventListener('load', obtenerImagen);
</script>

<?php
    } else {
        echo "<div class='ga-card'><div class='ga-card-body'>No se encontraron detalles para el pedido con el ID proporcionado.</div></div>";
    }
} else {
    echo '<div class="ga-card" style="padding:24px; text-align:center;">';
    echo '<img src="\Pedidos_GA\Img\Encabezado.png" alt="Encabezado" style="max-width:260px; margin-bottom:16px;"><br>';
    echo '<button class="ga-btn-action ga-btn-edit" onclick="javascript:history.go(-1);">← Volver</button>';
    echo '</div>';
}
?>
</div>

</body>
</html>

<?php
function cargarArchivo($pedidoId) {
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['archivo'])) {
        $file = $_FILES['archivo'];

        if ($file['error'] === UPLOAD_ERR_OK) {
            $fileName = $file['name'];
            $fileTmpName = $file['tmp_name'];
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            $allowedExtensions = array("pdf", "doc", "docx", "txt");
            if (in_array($fileExt, $allowedExtensions)) {
                $uploadDirFull = realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'Archivos' . DIRECTORY_SEPARATOR;
                if (!file_exists($uploadDirFull)) {
                    mkdir($uploadDirFull, 0777, true);
                }

                $uploadDirRelative = 'Archivos/';
                $uniqueName = uniqid("pedido_" . $pedidoId . "_") . "." . $fileExt;
                $filePathFull = $uploadDirFull . $uniqueName;

                if (move_uploaded_file($fileTmpName, $filePathFull)) {
                    $filePathRelative = $uploadDirRelative . $uniqueName;

                    $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
                    if ($conn->connect_error) {
                        die("Error de conexión: " . $conn->connect_error);
                    }

                    $sql = "UPDATE pedidos SET Ruta = ? WHERE ID = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("si", $filePathRelative, $pedidoId);
                    $stmt->execute();

                    $downloadUrl = '/Pedidos_GA/' . $filePathRelative;
                    echo '<div style="background:#f0fdf4;border:1px solid #bbf7d0;padding:16px;border-radius:12px;margin:12px 0;">';
                    echo '<h4 style="color:#166534;margin:0 0 6px 0;">' . gaIcon('check', 16) . ' Documento cargado con éxito</h4>';
                    echo '<p style="margin:0;color:#166534;font-size:13px;">Archivo: <strong>' . htmlspecialchars($fileName) . '</strong></p>';
                    echo '<p style="margin:8px 0 0 0;"><a href="' . $downloadUrl . '" target="_blank" style="color:#0f4c81;text-decoration:none;font-weight:700;font-size:13px;">' . gaIcon('file-text', 14) . ' Ver / Descargar archivo</a></p>';
                    echo '</div>';
                } else {
                    echo "<script>alert('Hubo un error al mover el archivo.');</script>";
                }
            } else {
                echo "<script>alert('Solo se permiten archivos PDF, DOC, DOCX y TXT.');</script>";
            }
        } else {
            echo "<script>alert('Seleccione un archivo válido para cargar.');</script>";
        }
    }
}

function descargarArchivo($pedidoId) {
    $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

    if ($conn->connect_error) {
        die("Error de conexión: " . $conn->connect_error);
    }

    $sql = "SELECT Ruta FROM pedidos WHERE ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $pedidoId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $rutaArchivo = $row['Ruta'];

        if (file_exists($rutaArchivo)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($rutaArchivo) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($rutaArchivo));
            readfile($rutaArchivo);
            exit;
        }
    }
    $conn->close();
}

function consultarArchivo($pedidoId) {
    $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    if ($conn->connect_error) {
        die("Error de conexión: " . $conn->connect_error);
    }

    $sql = "SELECT Ruta FROM pedidos WHERE ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $pedidoId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $rutaArchivoRelativa = $row['Ruta'];

        if (empty($rutaArchivoRelativa)) {
            echo '<div style="background:#fffbeb;border:1px solid #fde68a;padding:14px;border-radius:10px;margin:10px 0;">';
            echo '<p style="margin:0;color:#92400e;font-weight:600;font-size:13px;">' . gaIcon('alert-triangle', 15) . ' No hay ningún documento cargado para este pedido.</p>';
            echo '</div>';
            return;
        }

        $rutaArchivoCompleta = realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR . $rutaArchivoRelativa;
        $urlArchivo = '/Pedidos_GA/' . $rutaArchivoRelativa;

        if (!file_exists($rutaArchivoCompleta)) {
            echo '<div style="background:#ffe4e6;border:1px solid #fecdd3;padding:14px;border-radius:10px;margin:10px 0;">';
            echo '<p style="margin:0;color:#9f1239;font-weight:600;font-size:13px;">' . gaIcon('x-circle', 15) . ' El archivo registrado no se encontró en el servidor.</p>';
            echo '</div>';
            return;
        }

        $fileExt = strtolower(pathinfo($rutaArchivoCompleta, PATHINFO_EXTENSION));
        $fileName = basename($rutaArchivoCompleta);

        echo '<div style="background:#f0fdf4;border:1px solid #bbf7d0;padding:14px;border-radius:10px;margin:10px 0;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">';
        echo '<div><strong style="color:#166534;font-size:13px;">' . gaIcon('file-text', 15) . ' ' . htmlspecialchars($fileName) . '</strong> <span style="font-size:11px;color:#15803d;">(' . strtoupper($fileExt) . ')</span></div>';
        echo '<a href="' . htmlspecialchars($urlArchivo) . '" target="_blank" style="background:#0f4c81;color:#fff;padding:7px 14px;border-radius:8px;text-decoration:none;font-weight:700;font-size:12px;">' . gaIcon('download', 14) . ' Abrir / Descargar</a>';
        echo '</div>';

        if ($fileExt === "pdf") {
            echo '<div style="margin-top:10px;">';
            echo '<embed src="' . htmlspecialchars($urlArchivo, ENT_QUOTES, 'UTF-8') . '" type="application/pdf" width="100%" height="480px" style="border:1px solid #e2e8f0;border-radius:10px;" />';
            echo '</div>';
        } elseif ($fileExt === "txt") {
            $fileContent = file_get_contents($rutaArchivoCompleta);
            echo '<textarea style="width:100%;min-height:220px;padding:12px;border:1px solid #e2e8f0;border-radius:10px;font-family:monospace;margin-top:10px;" readonly>' . htmlspecialchars($fileContent, ENT_QUOTES, 'UTF-8') . '</textarea>';
        }
    }
    $conn->close();
}

function obtenerRutaArchivoCargado($pedidoId) {
    $rutaArchivo = null;
    $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    if ($conn->connect_error) {
        return null;
    }

    $sql = "SELECT Ruta FROM pedidos WHERE ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $pedidoId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $rutaArchivo = $row['Ruta'];
    }
    $conn->close();
    return $rutaArchivo;
}
?>
