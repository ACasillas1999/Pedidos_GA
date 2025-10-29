<?php
session_name("GA");
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: /Pedidos_GA/Sesion/login.html");
    exit;
}

require_once __DIR__ . "/Conexiones/Conexion.php";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['busqueda'])) {
    $busqueda = $_POST['busqueda'];
    $sql = "SELECT ID, username, Nombre, Sucursal, Numero, Rol
            FROM usuarios
            WHERE ID LIKE '%$busqueda%' 
               OR Sucursal LIKE '%$busqueda%' 
               OR username LIKE '%$busqueda%' 
               OR Rol LIKE '%$busqueda%'";

    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        echo "<table class='mi-tabla' border='1'>";
        echo "<tr>
                <th>ID</th><th>Username</th><th>Nombre</th>
                <th>Sucursal</th><th>Numero</th><th>Rol</th><th>Acciones</th>
              </tr>";
        while ($row = $result->fetch_assoc()) {
            $ID = (int)$row['ID'];
            $username = htmlspecialchars($row['username']);
            $Nombre   = htmlspecialchars($row['Nombre']);
            $Sucursal = htmlspecialchars($row['Sucursal']);
            $Numero   = htmlspecialchars($row['Numero']);
            $Rol      = htmlspecialchars($row['Rol']);

            echo "<tr>
                    <td>{$ID}</td>
                    <td>{$username}</td>
                    <td>{$Nombre}</td>
                    <td>{$Sucursal}</td>
                    <td>{$Numero}</td>
                    <td>{$Rol}</td>
                    <td>
                      <a href='Actualizar_usuarios.php?id={$ID}'>
                        <img src='/Pedidos_GA/Img/Botones%20entregas/Usuario/ACTUZAZ.png' style='max-width:35px'>
                      </a>
                      <button class='btn-regenerar' data-id='{$ID}' data-user='{$username}'
                              style='background:#005aa3;color:#fff;border:0;border-radius:6px;padding:6px 10px;cursor:pointer'>
                        🔄 Regenerar
                      </button>
                    </td>
                  </tr>";
        }
        echo "</table>";
    } else {
        echo "No se encontraron resultados para la búsqueda: '" . htmlspecialchars($busqueda) . "'.";
    }
} else {
    echo "Por favor, ingrese un término de búsqueda.";
}
$conn->close();