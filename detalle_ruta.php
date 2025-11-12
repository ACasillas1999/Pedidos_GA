<?php
session_name("GA");
session_start();

// Verificar si el usuario está logeado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: /Pedidos_GA/Sesion/login.html");
    exit;
}

// Establecer la conexión a la base de datos
require_once __DIR__ . "/Conexiones/Conexion.php";

$grupoId = intval($_GET['grupo_id'] ?? 0);

if ($grupoId <= 0) {
    die("ID de grupo inválido");
}

// Obtener información del grupo
$sqlGrupo = "SELECT gr.*, c.username as chofer_nombre, c.Numero as chofer_telefono
            FROM grupos_rutas gr
            LEFT JOIN choferes c ON gr.chofer_asignado = c.username
            WHERE gr.id = ?";

$stmtGrupo = $conn->prepare($sqlGrupo);
$stmtGrupo->bind_param("i", $grupoId);
$stmtGrupo->execute();
$resultGrupo = $stmtGrupo->get_result();
$grupo = $resultGrupo->fetch_assoc();

if (!$grupo) {
    die("Grupo no encontrado");
}

// Obtener pedidos del grupo (incluyendo SUCURSAL)
$sqlPedidos = "SELECT p.ID, p.FACTURA, p.NOMBRE_CLIENTE, p.DIRECCION,
                     p.TELEFONO, p.ESTADO, p.tipo_envio, p.Coord_Destino,
                     p.precio_factura_real, p.COMENTARIOS, p.SUCURSAL, pg.orden_entrega
              FROM pedidos p
              INNER JOIN pedidos_grupos pg ON p.ID = pg.pedido_id
              WHERE pg.grupo_id = ?
              ORDER BY pg.orden_entrega ASC";

$stmtPedidos = $conn->prepare($sqlPedidos);
$stmtPedidos->bind_param("i", $grupoId);
$stmtPedidos->execute();
$resultPedidos = $stmtPedidos->get_result();
$pedidos = [];
while ($pedido = $resultPedidos->fetch_assoc()) {
    $pedidos[] = $pedido;
}

// Obtener coordenadas de la sucursal (bodega/origen)
// Usar la sucursal del primer pedido (todos deben ser de la misma sucursal)
$sucursalOrigen = '';
if (!empty($pedidos)) {
    $sucursalOrigen = $pedidos[0]['SUCURSAL'];
}

// Si la sucursal es ILUMINACION o TAPATIA, usar TAPATIA como origen
if ($sucursalOrigen === 'ILUMINACION' || $sucursalOrigen === 'TAPATIA') {
    $sucursalOrigen = 'TAPATIA';
}

$sqlUbicacion = "SELECT coordenadas, NombreCompleto, Direccion FROM ubicaciones WHERE Ubicacion = ?";
$stmtUbicacion = $conn->prepare($sqlUbicacion);
$stmtUbicacion->bind_param("s", $sucursalOrigen);
$stmtUbicacion->execute();
$resultUbicacion = $stmtUbicacion->get_result();
$ubicacion = $resultUbicacion->fetch_assoc();
$stmtUbicacion->close();

// Generar color del grupo
$colores = [
    ['bg' => '#28a745', 'border' => '#20c997'],
    ['bg' => '#007bff', 'border' => '#0056b3'],
    ['bg' => '#ffc107', 'border' => '#ff9800'],
    ['bg' => '#dc3545', 'border' => '#c82333'],
    ['bg' => '#6f42c1', 'border' => '#5a32a3'],
    ['bg' => '#fd7e14', 'border' => '#e8590c'],
    ['bg' => '#20c997', 'border' => '#17a2b8'],
    ['bg' => '#e83e8c', 'border' => '#d63384']
];
$colorIndex = $grupoId % count($colores);
$color = $colores[$colorIndex];
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Detalle de Ruta - <?php echo htmlspecialchars($grupo['nombre_grupo']); ?></title>
  <link rel="icon" href="img/Logo 2.png" type="image/png">
  <link rel="stylesheet" type="text/css" href="styles.css">

  <!-- Mapbox -->
  <script src='https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.js'></script>
  <link href='https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css' rel='stylesheet' />

  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <!-- SortableJS para drag & drop -->
  <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>

  <style>
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background-color: #f5f5f5;
      margin: 0;
      padding: 20px;
    }

    .container {
      max-width: 1400px;
      margin: 0 auto;
      background: white;
      border-radius: 12px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      overflow: hidden;
    }

    .header {
      background: linear-gradient(135deg, <?php echo $color['bg']; ?> 0%, <?php echo $color['border']; ?> 100%);
      color: white;
      padding: 30px;
    }

    .header h1 {
      margin: 0 0 10px 0;
      font-size: 28px;
      display: flex;
      align-items: center;
      gap: 15px;
    }

    .header-info {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 15px;
      margin-top: 20px;
    }

    .info-card {
      background: rgba(255, 255, 255, 0.2);
      padding: 15px;
      border-radius: 8px;
    }

    .info-label {
      font-size: 12px;
      opacity: 0.9;
      margin-bottom: 5px;
    }

    .info-value {
      font-size: 18px;
      font-weight: bold;
    }

    .content {
      display: grid;
      grid-template-columns: 400px 1fr;
      gap: 20px;
      padding: 20px;
    }

    .pedidos-list {
      background: #f8f9fa;
      border-radius: 8px;
      padding: 15px;
      max-height: calc(100vh - 300px);
      overflow-y: auto;
    }

    .pedidos-list h2 {
      margin: 0 0 15px 0;
      font-size: 18px;
      color: #333;
    }

    .pedido-item {
      background: white;
      border-radius: 8px;
      padding: 15px;
      margin-bottom: 10px;
      cursor: move;
      border-left: 4px solid <?php echo $color['bg']; ?>;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
      transition: all 0.2s ease;
    }

    .pedido-item:hover {
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
      transform: translateY(-2px);
    }

    .pedido-item.sortable-drag {
      opacity: 0.5;
    }

    .sortable-ghost {
      opacity: 0.4;
      background: #f0f0f0;
    }

    .pedido-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 10px;
    }

    .pedido-orden {
      background: <?php echo $color['bg']; ?>;
      color: white;
      width: 30px;
      height: 30px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
      font-size: 14px;
    }

    .pedido-factura {
      font-weight: bold;
      color: #333;
      font-size: 16px;
    }

    .pedido-details {
      font-size: 13px;
      color: #666;
      line-height: 1.6;
    }

    .pedido-details strong {
      color: #333;
    }

    .map-container {
      background: white;
      border-radius: 8px;
      padding: 15px;
      height: calc(100vh - 300px);
    }

    #map {
      width: 100%;
      height: 100%;
      border-radius: 8px;
    }

    .btn-back {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: rgba(255, 255, 255, 0.2);
      color: white;
      padding: 10px 20px;
      border-radius: 8px;
      text-decoration: none;
      font-weight: bold;
      transition: all 0.2s ease;
      margin-bottom: 15px;
    }

    .btn-back:hover {
      background: rgba(255, 255, 255, 0.3);
    }

    .drag-handle {
      color: #999;
      font-size: 18px;
      margin-right: 10px;
    }

    .map-legend {
      position: absolute;
      bottom: 30px;
      left: 10px;
      background: white;
      padding: 10px;
      border-radius: 8px;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
      font-size: 12px;
      z-index: 1;
    }

    .legend-item {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 5px;
    }

    .legend-marker {
      width: 20px;
      height: 20px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 10px;
      font-weight: bold;
      color: white;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <div style="display: flex; gap: 10px; align-items: center;">
        <a href="Pedidos_GA.php" class="btn-back">
          ← Volver a Pedidos
        </a>
        <button onclick="desactivarGrupo()" class="btn-danger" style="padding: 10px 20px; background: #dc3545; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: 500;">
          🗑️ Desactivar Grupo
        </button>
      </div>

      <h1>
        <span>🚚</span>
        <?php echo htmlspecialchars($grupo['nombre_grupo']); ?>
      </h1>

      <div class="header-info">
        <div class="info-card">
          <div class="info-label">Chofer Asignado</div>
          <div class="info-value"><?php echo htmlspecialchars($grupo['chofer_asignado']); ?></div>
        </div>
        <div class="info-card">
          <div class="info-label">Sucursal</div>
          <div class="info-value"><?php echo htmlspecialchars($grupo['sucursal']); ?></div>
        </div>
        <div class="info-card">
          <div class="info-label">Total de Pedidos</div>
          <div class="info-value"><?php echo count($pedidos); ?></div>
        </div>
        <div class="info-card">
          <div class="info-label">Creado por</div>
          <div class="info-value"><?php echo htmlspecialchars($grupo['usuario_creo']); ?></div>
        </div>
      </div>

      <?php if ($grupo['notas']): ?>
      <div class="info-card" style="margin-top: 15px;">
        <div class="info-label">Notas</div>
        <div class="info-value" style="font-size: 14px; font-weight: normal;">
          <?php echo nl2br(htmlspecialchars($grupo['notas'])); ?>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <div class="content">
      <div class="pedidos-list">
        <h2>📋 Orden de Entrega</h2>
        <p style="font-size: 13px; color: #666; margin-bottom: 15px;">
          Arrastra los pedidos para cambiar el orden de entrega. La ruta se actualizará automáticamente.
        </p>

        <div id="pedidos-sortable">
          <?php foreach ($pedidos as $pedido): ?>
          <div class="pedido-item" data-pedido-id="<?php echo $pedido['ID']; ?>" data-orden="<?php echo $pedido['orden_entrega']; ?>">
            <div class="pedido-header">
              <span class="drag-handle">⋮⋮</span>
              <span class="pedido-orden"><?php echo $pedido['orden_entrega']; ?></span>
              <span class="pedido-factura"><?php echo htmlspecialchars($pedido['FACTURA']); ?></span>
            </div>
            <div class="pedido-details">
              <div><strong>Cliente:</strong> <?php echo htmlspecialchars($pedido['NOMBRE_CLIENTE']); ?></div>
              <div><strong>Dirección:</strong> <?php echo htmlspecialchars($pedido['DIRECCION']); ?></div>
              <div><strong>Teléfono:</strong> <?php echo htmlspecialchars($pedido['TELEFONO']); ?></div>
              <?php if ($pedido['precio_factura_real']): ?>
              <div><strong>Monto:</strong> $<?php echo number_format($pedido['precio_factura_real'], 2); ?></div>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="map-container">
        <div id="map"></div>
        <div class="map-legend">
          <div class="legend-item">
            <div class="legend-marker" style="background: #28a745;">1</div>
            <span>Orden de entrega</span>
          </div>
          <div class="legend-item">
            <div style="width: 3px; height: 20px; background: <?php echo $color['bg']; ?>;"></div>
            <span>Ruta optimizada</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Datos de los pedidos para JavaScript
    const pedidos = <?php echo json_encode($pedidos); ?>;
    const grupoId = <?php echo $grupoId; ?>;
    const grupoColor = '<?php echo $color['bg']; ?>';
    const ubicacionOrigen = <?php echo json_encode($ubicacion); ?>;

    // Inicializar mapa
    mapboxgl.accessToken = 'pk.eyJ1IjoiYWNhc2lsbGFzNzY2IiwiYSI6ImNsdW12cTZyMjB4NnMya213MDdseXp6ZGgifQ.t7-l1lQfd8mgHILM5YrdNw';

    const map = new mapboxgl.Map({
      container: 'map',
      style: 'mapbox://styles/mapbox/streets-v12',
      center: [-103.3494, 20.6737], // Guadalajara por defecto
      zoom: 12
    });

    let markers = [];
    let routeLayer = null;

    // Función para actualizar el mapa
    function actualizarMapa() {
      // Limpiar marcadores anteriores
      markers.forEach(marker => marker.remove());
      markers = [];

      // Limpiar ruta anterior
      if (routeLayer && map.getLayer('route')) {
        map.removeLayer('route');
        map.removeSource('route');
      }

      const bounds = new mapboxgl.LngLatBounds();
      const coordenadas = [];

      // Agregar coordenadas de la bodega/origen como primer punto
      if (ubicacionOrigen && ubicacionOrigen.coordenadas) {
        try {
          let coordString = ubicacionOrigen.coordenadas.trim();
          let lat, lng;

          // Parsear coordenadas (formato "lat, lng")
          if (coordString.includes(',')) {
            const parts = coordString.split(',').map(p => p.trim());
            if (parts.length === 2) {
              lat = parseFloat(parts[0]);
              lng = parseFloat(parts[1]);
            }
          }

          if (!isNaN(lng) && !isNaN(lat) && lng !== 0 && lat !== 0) {
            // Crear marcador de bodega/origen
            const elOrigen = document.createElement('div');
            elOrigen.innerHTML = `
              <div style="
                background: #ff6b6b;
                color: white;
                width: 40px;
                height: 40px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 24px;
                border: 3px solid white;
                box-shadow: 0 2px 8px rgba(0,0,0,0.4);
                cursor: pointer;
              ">
                🏢
              </div>
            `;

            const markerOrigen = new mapboxgl.Marker(elOrigen)
              .setLngLat([lng, lat])
              .setPopup(new mapboxgl.Popup().setHTML(`
                <strong>🏢 Bodega/Origen</strong><br>
                ${ubicacionOrigen.NombreCompleto || ''}<br>
                ${ubicacionOrigen.Direccion || ''}
              `))
              .addTo(map);

            markers.push(markerOrigen);
            bounds.extend([lng, lat]);
            coordenadas.push([lng, lat]);

            console.log('Marcador de bodega agregado:', ubicacionOrigen.NombreCompleto);
          }
        } catch (e) {
          console.error('Error procesando coordenadas de bodega:', e);
        }
      }

      // Obtener el orden actual de los pedidos
      const pedidosOrdenados = Array.from(document.querySelectorAll('.pedido-item')).map((item, index) => {
        const pedidoId = parseInt(item.dataset.pedidoId);
        return pedidos.find(p => p.ID === pedidoId);
      });

      // Agregar marcadores
      pedidosOrdenados.forEach((pedido, index) => {
        console.log(`Procesando pedido ${pedido.ID}:`, pedido.Coord_Destino);

        if (pedido.Coord_Destino && pedido.Coord_Destino.trim() !== '') {
          try {
            // Limpiar la cadena antes de parsear
            let coordString = pedido.Coord_Destino.trim();
            let lat, lng;

            // Intentar parsear como JSON primero
            try {
              const coords = JSON.parse(coordString);
              lng = parseFloat(coords.lng);
              lat = parseFloat(coords.lat);
              console.log('Formato JSON detectado');
            } catch (jsonError) {
              console.log('No es JSON, intentando formato "lat, lng"...');

              // Intentar formato simple: "20.71685200, -103.36460500"
              if (coordString.includes(',')) {
                const parts = coordString.split(',').map(p => p.trim());
                if (parts.length === 2) {
                  lat = parseFloat(parts[0]);
                  lng = parseFloat(parts[1]);
                  console.log('Formato "lat, lng" detectado');
                } else {
                  throw new Error('Formato de coordenadas no válido');
                }
              } else {
                // Intentar extraer lng y lat con regex como último recurso
                const lngMatch = coordString.match(/lng["\s:]+(-?\d+\.?\d*)/i);
                const latMatch = coordString.match(/lat["\s:]+(-?\d+\.?\d*)/i);

                if (lngMatch && latMatch) {
                  lng = parseFloat(lngMatch[1]);
                  lat = parseFloat(latMatch[1]);
                  console.log('Formato regex detectado');
                } else {
                  throw new Error('No se pudieron extraer coordenadas');
                }
              }
            }

            console.log(`Coordenadas parseadas - Lng: ${lng}, Lat: ${lat}`);

            if (!isNaN(lng) && !isNaN(lat) && lng !== 0 && lat !== 0) {
              // Crear marcador personalizado
              const el = document.createElement('div');
              el.className = 'custom-marker';
              el.innerHTML = `
                <div style="
                  background: ${grupoColor};
                  color: white;
                  width: 30px;
                  height: 30px;
                  border-radius: 50%;
                  display: flex;
                  align-items: center;
                  justify-content: center;
                  font-weight: bold;
                  font-size: 14px;
                  border: 3px solid white;
                  box-shadow: 0 2px 4px rgba(0,0,0,0.3);
                  cursor: pointer;
                ">
                  ${index + 1}
                </div>
              `;

              const marker = new mapboxgl.Marker(el)
                .setLngLat([lng, lat])
                .setPopup(new mapboxgl.Popup().setHTML(`
                  <strong>${pedido.FACTURA}</strong><br>
                  ${pedido.NOMBRE_CLIENTE}<br>
                  ${pedido.DIRECCION}
                `))
                .addTo(map);

              markers.push(marker);
              bounds.extend([lng, lat]);
              coordenadas.push([lng, lat]);

              console.log(`Marcador agregado para pedido ${pedido.ID}`);
            } else {
              console.warn(`Coordenadas inválidas para pedido ${pedido.ID}:`, lng, lat);
            }
          } catch (e) {
            console.error(`Error procesando coordenadas del pedido ${pedido.ID}:`, e, pedido.Coord_Destino);
          }
        } else {
          console.warn(`Pedido ${pedido.ID} no tiene coordenadas`);
        }
      });

      // Ajustar vista al contenido
      if (coordenadas.length > 0) {
        console.log(`Total de coordenadas válidas: ${coordenadas.length}`, coordenadas);
        map.fitBounds(bounds, { padding: 50 });

        // Dibujar ruta entre los puntos (necesitamos al menos 2 puntos: origen + 1 destino)
        if (coordenadas.length > 1) {
          console.log('Obteniendo ruta entre puntos...');
          obtenerRuta(coordenadas);
        } else {
          console.log('Solo hay 1 punto, no se puede trazar ruta');
        }
      } else {
        console.warn('No hay coordenadas válidas para mostrar');
      }
    }

    // Obtener ruta de Mapbox Directions API
    async function obtenerRuta(coordenadas) {
      // Mapbox tiene límite de 25 waypoints
      if (coordenadas.length > 25) {
        console.warn('Demasiados puntos, limitando a 25');
        coordenadas = coordenadas.slice(0, 25);
      }

      const coordsString = coordenadas.map(c => c.join(',')).join(';');
      const url = `https://api.mapbox.com/directions/v5/mapbox/driving/${coordsString}?geometries=geojson&access_token=${mapboxgl.accessToken}`;

      console.log('URL de ruta:', url);

      try {
        const response = await fetch(url);
        const data = await response.json();

        console.log('Respuesta de Directions API:', data);

        if (data.routes && data.routes.length > 0) {
          const route = data.routes[0].geometry;
          console.log('Ruta obtenida exitosamente');

          // Agregar capa de ruta
          if (map.getSource('route')) {
            console.log('Actualizando ruta existente');
            map.getSource('route').setData({
              type: 'Feature',
              geometry: route
            });
          } else {
            console.log('Creando nueva capa de ruta');
            map.addSource('route', {
              type: 'geojson',
              data: {
                type: 'Feature',
                geometry: route
              }
            });

            map.addLayer({
              id: 'route',
              type: 'line',
              source: 'route',
              layout: {
                'line-join': 'round',
                'line-cap': 'round'
              },
              paint: {
                'line-color': grupoColor,
                'line-width': 4,
                'line-opacity': 0.8
              }
            });
          }
        } else {
          console.error('No se encontraron rutas en la respuesta:', data);
        }
      } catch (error) {
        console.error('Error obteniendo ruta:', error);
      }
    }

    // Inicializar SortableJS cuando el DOM esté listo
    document.addEventListener('DOMContentLoaded', function() {
      console.log('Inicializando Sortable...');
      const elemento = document.getElementById('pedidos-sortable');

      if (!elemento) {
        console.error('Elemento pedidos-sortable no encontrado');
        return;
      }

      const sortable = new Sortable(elemento, {
        animation: 150,
        handle: '.drag-handle',
        ghostClass: 'sortable-ghost',
        onStart: function(evt) {
          console.log('Comenzó el arrastre');
        },
        onEnd: function(evt) {
          console.log('Terminó el arrastre');
        // Actualizar números de orden
        const items = document.querySelectorAll('.pedido-item');
        items.forEach((item, index) => {
          item.querySelector('.pedido-orden').textContent = index + 1;
          item.dataset.orden = index + 1;
        });

        // Actualizar mapa
        actualizarMapa();

          // Guardar nuevo orden en base de datos
          guardarNuevoOrden();
        }
      });

      console.log('Sortable inicializado correctamente');
    });

    // Guardar nuevo orden en la base de datos
    async function guardarNuevoOrden() {
      const items = document.querySelectorAll('.pedido-item');
      const ordenes = Array.from(items).map((item, index) => ({
        pedido_id: parseInt(item.dataset.pedidoId),
        orden: index + 1
      }));

      try {
        const response = await fetch('actualizar_grupo.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            accion: 'actualizar_orden',
            grupo_id: grupoId,
            ordenes: ordenes
          })
        });

        const result = await response.json();

        if (!result.success) {
          console.error('Error al guardar orden:', result.message);
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se pudo guardar el nuevo orden',
            confirmButtonColor: grupoColor
          });
        }
      } catch (error) {
        console.error('Error:', error);
      }
    }

    // Desactivar grupo
    async function desactivarGrupo() {
      const result = await Swal.fire({
        icon: 'warning',
        title: '¿Desactivar este grupo?',
        html: `
          <p style="margin-bottom: 15px;">Esta acción desactivará el grupo <strong>"<?php echo htmlspecialchars($grupo['nombre_grupo']); ?>"</strong></p>
          <p style="color: #666; font-size: 14px; margin-bottom: 20px;">
            Los pedidos seguirán asignados al chofer pero ya no aparecerán como parte de este grupo.
          </p>
          <div style="background: #fff3cd; padding: 15px; border-radius: 5px; border-left: 4px solid #ffc107; margin-bottom: 15px; text-align: left;">
            <strong>⚠️ Opciones:</strong><br>
            <label style="display: block; margin: 10px 0; cursor: pointer;">
              <input type="radio" name="desactivar-opcion" value="mantener" checked style="margin-right: 8px;">
              Mantener la asignación del chofer en los pedidos
            </label>
            <label style="display: block; margin: 10px 0; cursor: pointer;">
              <input type="radio" name="desactivar-opcion" value="quitar" style="margin-right: 8px;">
              Quitar la asignación del chofer de los pedidos
            </label>
          </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Sí, desactivar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        preConfirm: () => {
          const opcion = document.querySelector('input[name="desactivar-opcion"]:checked');
          return opcion ? opcion.value : 'mantener';
        }
      });

      if (result.isConfirmed) {
        const opcion = result.value;

        // Mostrar loading
        Swal.fire({
          title: 'Desactivando grupo...',
          html: 'Por favor espere',
          allowOutsideClick: false,
          didOpen: () => {
            Swal.showLoading();
          }
        });

        try {
          const response = await fetch('actualizar_grupo.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json'
            },
            body: JSON.stringify({
              accion: 'desactivar',
              grupo_id: grupoId,
              quitar_chofer: opcion === 'quitar'
            })
          });

          const data = await response.json();

          if (data.success) {
            await Swal.fire({
              icon: 'success',
              title: 'Grupo desactivado',
              text: 'El grupo ha sido desactivado exitosamente',
              confirmButtonColor: '#28a745'
            });

            // Redirigir a la página principal
            window.location.href = 'Pedidos_GA.php';
          } else {
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: data.message || 'No se pudo desactivar el grupo',
              confirmButtonColor: '#dc3545'
            });
          }
        } catch (error) {
          console.error('Error:', error);
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Ocurrió un error al desactivar el grupo',
            confirmButtonColor: '#dc3545'
          });
        }
      }
    }

    // Inicializar mapa cuando esté listo
    map.on('load', function() {
      actualizarMapa();
    });
  </script>
</body>
</html>
