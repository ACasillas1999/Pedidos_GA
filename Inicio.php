<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


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
<html lang="es">
<head>
    <title>Pedidos GA</title>
    <link rel="icon" type="image/png" href="/Pedidos_GA/Img/Botones%20entregas/ICONOSPAG/ICONOPEDIDOS.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="styles1.css">
    <link href='https://api.mapbox.com/mapbox-gl-js/v2.6.1/mapbox-gl.css' rel='stylesheet' />
    <script src='https://api.mapbox.com/mapbox-gl-js/v2.6.1/mapbox-gl.js'></script>
</head>
    
    
  <script>
    document.addEventListener("DOMContentLoaded", function() {  
        var iconoPVovler = document.querySelector(".icono-PVovler");
        var iconoVChoferes = document.querySelector(".icono-VChoferes");
        

        var imgNormalPVolver = "/Pedidos_GA/Img/Botones%20entregas/Inicio/VOLVER/VOLPEDNA.png";
        var imgHoverPVolvers = "/Pedidos_GA/Img/Botones%20entregas/Inicio/VOLVER/VOLPEDBL.png";

        // Cambiar la imagen al pasar el mouse para icono-PVovler
        if (iconoPVovler) {
            iconoPVovler.addEventListener("mouseover", function() {
                iconoPVovler.src = imgHoverPVolvers;
            });
            iconoPVovler.addEventListener("mouseout", function() {
                iconoPVovler.src = imgNormalPVolver;
            });
        }

        var imgNormalVChoferes = "/Pedidos_GA/Img/Botones%20entregas/Inicio/VOLVER/VOLCHOFNA.png";
        var imgHoverVChoferes = "/Pedidos_GA/Img/Botones%20entregas/Inicio/VOLVER/VOLCHOFBL.png";

        // Cambiar la imagen al pasar el mouse para icono-VChoferes
        if (iconoVChoferes) {
            iconoVChoferes.addEventListener("mouseover", function() {
                iconoVChoferes.src = imgHoverVChoferes;
            });
            iconoVChoferes.addEventListener("mouseout", function() {
                iconoVChoferes.src = imgNormalVChoferes;
            });
        } 
        
    });
    </script>
    
    
<body>
    <header class="header">
      <nav class="navbar">
            <ul>
                <li class="nav-item "><a href="Pedidos_GA.php" class="nav-link ">
                      <img src="\Pedidos_GA\Img\Botones entregas\Inicio\VOLVER\VOLPEDNA.png" alt="iconoVPedidos "class= "icono-PVovler" style="max-width: 30%; height: auto; "></a></li>
                
        <div >  <img src="\Pedidos_GA\Img\Botones entregas\Inicio\DETPED2.png" alt="iconoVPedidos "class= "icono-registro" style="max-width: 40%; height: auto; "></div>
                
                <li class="nav-item"><a href="Choferes.php" class="nav-link back-button">
                    
                    
                    
                     <img src="\Pedidos_GA\Img\Botones entregas\Inicio\VOLVER\VOLCHOFNA.png" alt="AddRegistro "class= "icono-VChoferes" style="max-width: 30%; height: auto; ">
                   </a></li>
            </ul>
        </nav>
    </header>
    <script>
        const backButton = document.querySelector('.back-button');

        backButton.addEventListener('click', () => {
            if (window.history.length > 1) {
                window.history.back();
            } else {
                window.location.href = "Choferes.php";
            }
        });
    </script>
    
  <?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('detalle_pedido.php');
?>
    
     <div class="linea-horizontal">
    <div class="texto-linea">Mapa</div>
  </div>
    
    
    
    <script>
document.addEventListener("DOMContentLoaded", function() {  
   
    
   
    
});
</script>
    
    <p></p>

    <input type="hidden" id="pedido-id" value="<?php echo $row['ID']; ?>">

    <h2 id="distance-label">Distancia entre puntos: ...</h2>
    <h3 id="duration-label">Tiempo de traslado aproximado: ...</h3>

    <div class="input-container">Coordenadas:</div>
    <input type="text" id="start-coordinates" placeholder="Coordenadas inicio" value="<?php echo $row["Coord_Origen"]; ?>" disabled>
    <input type="text" id="end-coordinates" placeholder="Coordenadas fin" value="<?php echo $row["Coord_Destino"]; ?>" disabled>
    <div style="margin: auto; width: 1080px; height: 720px;">
        <div id="map" style="width: 100%; height: 100%;"></div>
    </div>
  
    <p></p>

    <button class="icono-Maps" style="background: none; border: none; padding: 0;" onclick="openGoogleMaps()">
        <img src="/Pedidos_GA/Img/Botones%20entregas/Inicio/DETPED/MAPSNA.png" alt="icono-Maps" class="icono-Maps-img" style="max-width: 50%; height: auto;">
    </button>
    
   
   

    <footer class="footer">
        <ul class="menu">
        <li class="icono-Home">
                <a href="#">
                <img src="/Pedidos_GA/Img/Botones%20entregas/Inicio/DETPED/HOMEBL.png" alt="icono-Home" class="icono-Home-img" style="max-width: 10%;">
               </a><!--HOMEBL-->
            </li>
        </ul>
        <p>&copy;2024 Alejandro Casillas | All Rights Reserved</p>
    </footer>


    <script>


document.addEventListener("DOMContentLoaded", function() {
            var iconoMaps = document.querySelector(".icono-Maps-img");
           
            var iconoHome = document.querySelector(".icono-Home-img");

            var imgNormalMaps = "/Pedidos_GA/Img/Botones%20entregas/Inicio/DETPED/MAPSNA.png";
            var imgHoverMaps = "/Pedidos_GA/Img/Botones%20entregas/Inicio/DETPED/ABRGMAZ.png";

            var imgNormalHome = "/Pedidos_GA/Img/Botones%20entregas/Inicio/DETPED/HOMEBL.png";
            var imgHoverHome = "/Pedidos_GA/Img/Botones%20entregas/Inicio/DETPED/HOMENA.png";

            // Hover para el botón "Abrir en Google Maps"
            if (iconoMaps) {
                iconoMaps.addEventListener("mouseover", function() {
                    iconoMaps.src = imgHoverMaps;
                });
                iconoMaps.addEventListener("mouseout", function() {
                    iconoMaps.src = imgNormalMaps;
                });
            }

           

            // Hover para el botón "Home"
            if (iconoHome) {
                iconoHome.addEventListener("mouseover", function() {
                    iconoHome.src = imgHoverHome;
                });
                iconoHome.addEventListener("mouseout", function() {
                    iconoHome.src = imgNormalHome;
                });
            }
        });



    </script>

    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>

    <script>
        mapboxgl.accessToken = 'pk.eyJ1IjoiYWNhc2lsbGFzNzY2IiwiYSI6ImNsdW12cTZyMjB4NnMya213MDdseXp6ZGgifQ.t7-l1lQfd8mgHILM5YrdNw';

        var map = new mapboxgl.Map({
            container: 'map',
            style: 'mapbox://styles/mapbox/streets-v11',
            zoom: 9
        });

        var startMarker = new mapboxgl.Marker({
            color: "#FF5733"
        });
        var endMarker = new mapboxgl.Marker({
            color: "#337DFF"
        });

        function calcularDistancia(start, end) {
            var R = 6371;
            var dLat = (end[1] - start[1]) * Math.PI / 180;
            var dLon = (end[0] - start[0]) * Math.PI / 180;
            var a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                    Math.cos(start[1] * Math.PI / 180) * Math.cos(end[1] * Math.PI / 180) *
                    Math.sin(dLon / 2) * Math.sin(dLon / 2);
            var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
            var distance = R * c;
            return distance.toFixed(2);
        }

        function updateRoute() {
            var startInput = document.getElementById("start-coordinates").value.trim();
            var endInput = document.getElementById("end-coordinates").value.trim();
            var start = startInput.split(',').map(parseFloat).reverse();
            var end = endInput.split(',').map(parseFloat).reverse();

            var url = 'https://api.mapbox.com/directions/v5/mapbox/driving/' + start[0] + ',' + start[1] + ';' + end[0] + ',' + end[1] + '?steps=true&geometries=geojson&access_token=' + mapboxgl.accessToken;

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    var route = data.routes[0].geometry.coordinates;
                    map.getSource('route').setData({
                        'type': 'Feature',
                        'properties': {},
                        'geometry': {
                            'type': 'LineString',
                            'coordinates': route
                        }
                    });

                    startMarker.setLngLat(start).addTo(map);
                    endMarker.setLngLat(end).addTo(map);

                    var distancia = calcularDistancia(start, end);
                    document.getElementById("distance-label").textContent = "Distancia entre puntos: " + distancia + " kilómetros.";

                    var duration = data.routes[0].duration;
                    var tiempoTraslado = Math.round(duration / 60);
                    document.getElementById("duration-label").textContent = "Tiempo de traslado aproximado: " + tiempoTraslado + " minutos.";

                    var bounds = [start, end];
                    map.fitBounds(bounds, { padding: 100 });

                    enviarDistancia(distancia);
                });
        }

        function enviarDistancia(distancia) {
            var pedido_id = document.getElementById("pedido-id").value;

            var xhr = new XMLHttpRequest();
            xhr.open("POST", "update_distance.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    console.log(xhr.responseText);
                }
            };
            xhr.send("distancia=" + distancia + "&pedido_id=" + pedido_id);
        }

        function openGoogleMaps() {
            var startInput = document.getElementById("start-coordinates").value.trim();
            var endInput = document.getElementById("end-coordinates").value.trim();
            var start = startInput.split(',').map(parseFloat).reverse();
            var end = endInput.split(',').map(parseFloat).reverse();
            var latitudStart = start[1];
            var longitudStart = start[0];
            var latitudEnd = end[1];
            var longitudEnd = end[0];
            var url = "https://www.google.com/maps/dir/?api=1&origin=" + latitudStart + "," + longitudStart + "&destination=" + latitudEnd + "," + longitudEnd;
            window.open(url, "_blank");
        }

        map.on('load', function() {
            map.addSource('route', {
                'type': 'geojson',
                'data': {
                    'type': 'Feature',
                    'properties': {},
                    'geometry': {
                        'type': 'LineString',
                        'coordinates': []
                    }
                }
            });

            map.addLayer({
                'id': 'route',
                'type': 'line',
                'source': 'route',
                'layout': {
                    'line-join': 'round',
                    'line-cap': 'round'
                },
                'paint': {
                    'line-color': '#888',
                    'line-width': 6
                }
            });

            updateRoute();
        });
    </script>
</body>
</html>