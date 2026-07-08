<?php
// Reglas de visibilidad de pedidos por rol. Funciones puras: no tocan $conn, no arman SQL.

function sucursalesPermitidasPorRol(string $rol, string $sucursalSesion): array {
    $sucursalSesion = strtoupper($sucursalSesion);
    if ($rol === 'Admin' && $sucursalSesion === 'TODAS') {
        return [];
    }
    if ($rol === 'JC' && $sucursalSesion === 'TAPATIA') {
        return ['TAPATIA', 'ILUMINACION'];
    }
    if ($sucursalSesion !== '' && $sucursalSesion !== 'TODAS') {
        return [$sucursalSesion];
    }
    return ['___NULO___'];
}

function nombreVendedorFiltro(string $rol): ?string {
    if ($rol !== 'VR') {
        return null;
    }
    $nombre = trim($_SESSION['Nombre'] ?? '');
    return $nombre !== '' ? $nombre : '___NULO___';
}
