<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mostrar Imagen</title>
</head>
<body>
    <h1>Mostrar Imagen</h1>
    <div id="imagen-container">
        <img id="imagen" src="" alt="Imagen de pedido">
    </div>

    <script>
        // Función para hacer una solicitud HTTP GET
        function obtenerImagen() {
            var id_pedido = 91; // Establece el ID del pedido que deseas mostrar
            var url = "https://pedidos.grupoascencio.com.mx/App/Ver_Foto.php?id_pedido=" + id_pedido; // Reemplaza la URL con la ruta correcta a tu archivo PHP

            // Realizar la solicitud HTTP GET
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    // Verificar si se encontró la imagen
                    if (data[0].found) {
                        var ruta_imagen = data[0].ruta_imagen;
                        // Establecer la ruta de la imagen en el elemento <img>
                        document.getElementById("imagen").src = ruta_imagen;
                    } else {
                        // Mostrar mensaje de error si no se encontró la imagen
                        document.getElementById("imagen-container").innerText = "Imagen no encontrada";
                    }
                })
                .catch(error => {
                    console.error('Error al obtener la imagen:', error);
                    // Mostrar mensaje de error si hay un problema con la solicitud
                    document.getElementById("imagen-container").innerText = "Error al obtener la imagen";
                });
        }

        // Llamar a la función para obtener la imagen al cargar la página
        window.onload = obtenerImagen;
    </script>
</body>
</html>
