<?php
// Iniciar la sesión de forma segura
ini_set('session.cookie_httponly', true);
ini_set('session.cookie_secure', true);
session_name("GA");
session_start();

// Verificar si el usuario no está logeado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: /Pedidos_GA/Sesion/login.html");
    exit;
}

require_once __DIR__ . "/Conexiones/Conexion.php";
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["agregar_vehiculo"])) {
    // Sanitización básica
    $numero_serie   = trim($_POST["numero_serie"] ?? "");
    $placa          = trim($_POST["placa"] ?? "");
    $tipo           = trim($_POST["tipo"] ?? "");
    $sucursal       = trim($_POST["sucursal"] ?? "");
    $razon_social   = trim($_POST["razon_social"] ?? "");
    $km_de_servicio = (int)($_POST["km_de_servicio"] ?? 5000);
    $km_total       = (int)($_POST["km_total"] ?? 0);
    $km_actual      = (int)($_POST["km_actual"] ?? 0);
    $es_particular  = isset($_POST["es_particular"]) ? 1 : 0;

    // Convertir string vacío a NULL para razón social (campo opcional)
    $razon_social = $razon_social !== "" ? $razon_social : null;

    // 1) Verificar número de serie duplicado
    $stmt = $conn->prepare("SELECT id_vehiculo FROM vehiculos WHERE numero_serie = ?");
    $stmt->bind_param("s", $numero_serie);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo "<script>alert('Error: El número de serie ya existe.');</script>";
        $stmt->close();
    } else {
        $stmt->close();

        // 2) Manejar subida de foto (opcional)
        $foto_path = null; // valor por defecto si no suben imagen

        if (isset($_FILES["foto"]) && $_FILES["foto"]["error"] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES["foto"]["error"] !== UPLOAD_ERR_OK) {
                echo "<script>alert('Error al subir la imagen (código: {$_FILES["foto"]["error"]}).');</script>";
            } else {
                $maxBytes = 2 * 1024 * 1024; // 2 MB
                if ($_FILES["foto"]["size"] > $maxBytes) {
                    echo "<script>alert('La imagen excede 2 MB.');</script>";
                } else {
                    // Validar MIME real
                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    $mime  = $finfo->file($_FILES["foto"]["tmp_name"]);
                    $ext   = null;
                    $map   = [
                        'image/jpeg' => 'jpg',
                        'image/png'  => 'png',
                        'image/webp' => 'webp',
                    ];
                    if (isset($map[$mime])) {
                        $ext = $map[$mime];
                    }

                    if (!$ext) {
                        echo "<script>alert('Formato no permitido. Solo JPG/PNG/WEBP.');</script>";
                    } else {
                        // Carpeta destino
                        $baseDir = __DIR__ . "/Uploads/vehiculos";
                        if (!is_dir($baseDir)) {
                            @mkdir($baseDir, 0775, true);
                        }

                        // Nombre seguro y único
                        $slug  = preg_replace('/[^A-Za-z0-9_-]/', '', $placa !== '' ? $placa : 'vehiculo');
                        $rand  = bin2hex(random_bytes(6));
                        $fname = "{$slug}_{$rand}.{$ext}";
                        $dest  = $baseDir . "/" . $fname;

                        if (move_uploaded_file($_FILES["foto"]["tmp_name"], $dest)) {
                            // Guardamos ruta relativa (para usar en <img src>)
                            $foto_path = "uploads/vehiculos/" . $fname;
                        } else {
                            echo "<script>alert('No se pudo guardar la imagen en el servidor.');</script>";
                        }
                    }
                }
            }
        }

        // 3) Insertar vehículo
        $sql = "INSERT INTO vehiculos
                    (numero_serie, placa, tipo, Sucursal, razon_social, Km_de_Servicio, Km_Total, Km_Actual, foto_path, es_particular)
                VALUES (?,?,?,?,?,?,?,?,?,?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "sssssiissi",
            $numero_serie,
            $placa,
            $tipo,
            $sucursal,
            $razon_social,
            $km_de_servicio,
            $km_total,
            $km_actual,
            $foto_path,
            $es_particular
        );

        if ($stmt->execute()) {
            echo "<script>alert('Vehículo agregado exitosamente'); window.location.href='vehiculos.php';</script>";
        } else {
            $err = $conn->error;
            echo "<script>alert('Error al agregar vehículo: $err');</script>";
        }
        $stmt->close();
    }
}


// Obtener la lista de vehículos para mostrar en la tabla
$vehiculos = $conn->query("SELECT * FROM vehiculos");

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Vehículo</title>
<<<<<<< HEAD
    <link rel="icon" type="image/png" href="/Img/Botones%20entregas/ICONOSPAG/ICONOPEDIDOS.png">
=======
    <link rel="icon" type="image/png" href="/Pedidos_GA/Img/Botones%20entregas/ICONOSPAG/ICONOPEDIDOS.png">
>>>>>>> parent of 5e8b02c (parra amazon Update image paths and SQL table names)
    <link rel="stylesheet" href="styles3.css">

    <style>

        .upload-container {
    display: flex;
    align-items: center;
    gap: 12px;
}

.btn-foto {
    background: #ff6600;
    color: white;
    padding: 10px 16px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
    transition: background 0.3s;
}

.btn-foto:hover {
    background: #e65c00;
}

#file-chosen {
    font-size: 0.9rem;
    color: #555;
    font-style: italic;
}

    </style>
</head>

<body>

    <header class="header">
        <div class="logo">
            <h3> Agregar Vehiculo<h3>
        </div>
        <nav class="navbar">
            <ul>
                <li class="nav-item"><a href='vehiculos.php' class="nav-link">


                        <img src="\Pedidos_GA\Img\Botones entregas\RegistrarChofer\VOLVAZ.png" alt="Choferes" class="icono-Volver" style="max-width: 5%; height: auto; position:absolute; top: 70px; left: 35px;">
                    </a></li>
            </ul>
        </nav>
    </header>
    <p></p>
    <div>


        <!-- Formulario para agregar vehículo -->
        <form method="POST" enctype="multipart/form-data"> <!-- ← AQUI -->
            <div class="mb-3">
                <label>Número de Serie</label>
                <input type="text" name="numero_serie" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Placa</label>
                <input type="text" name="placa" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Tipo</label>
                <input type="text" name="tipo" class="form-control" required>
            </div>
            <p></p>
            <div class="mb-3">
                <label>Sucursal</label>
                <select type="text" name="sucursal" class="form-control" required>
                    <option value="">Selecciona una sucursal</option>
                    <option value="AIESA">AIESA</option>
                    <option value="BODEGA ALEMANIA">BODEGA ALEMANIA</option>
                    <option value="BODEGA ARTESANOS">BODEGA ARTESANOS</option>
                    <option value="BODEGA CALZADA DEL AGUILA">BODEGA CALZADA DEL AGUILA</option>
                    <option value="BODEGA CLEMENTE OROZCO">BODEGA CLEMENTE OROZCO</option>
                    <option value="BODEGA ESPAÑA">BODEGA ESPAÑA</option>
                    <option value="BODEGA FEDERALISMO">BODEGA FEDERALISMO</option>
                    <option value="CODI">CODI</option>
                    <option value="CONEXION">CONEXION</option>
                    <option value="CONSTITUYENTES">CONSTITUYENTES</option>
                    <option value="CORPORATIVO">CORPORATIVO</option>
                    <option value="DEASA">DEASA</option>
                    <option value="DIMEGSA">DIMEGSA</option>
                    <option value="FESA">FESA</option>
                    <option value="GABSA">GABSA</option>
                    <option value="ILUMINACION">ILUMINACION</option>
                    <option value="OVALO">OVALO</option>
                    <option value="QUERETARO">QUERETARO</option>
                    <option value="SEGSA">SEGSA</option>
                    <option value="TALLER">TALLER</option>
                    <option value="TAPATIA">TAPATIA</option>
                    <option value="VALLARTA">VALLARTA</option>
                </select>
            </div>
            <p></p>
            <div class="mb-3">
                <label>Razón Social (Empresa que compra el vehículo)</label>
                <select name="razon_social" class="form-control">
                    <option value="">Selecciona razón social</option>
                    <option value="AIESA">AIESA</option>
                    <option value="DEASA">DEASA</option>
                    <option value="DIMEGSA">DIMEGSA</option>
                    <option value="FESA">FESA</option>
                    <option value="GABSA">GABSA</option>
                    <option value="ILUMINACION">ILUMINACION</option>
                    <option value="QUERETARO">QUERETARO</option>
                    <option value="SEGSA">SEGSA</option>
                    <option value="TAPATIA">TAPATIA</option>
                    <option value="VALLARTA">VALLARTA</option>
                </select>
                <small style="display: block; margin-top: 4px; color: #666;">
                    Nota: El campo "Sucursal" (arriba) indica donde opera el vehículo
                </small>
            </div>
            <p></p>
            <div class="mb-3">
                <label>Kilometraje de Servicio</label>
                <input type="number" name="km_de_servicio" class="form-control" required value="5000">
            </div>
            <div class="mb-3">
                <label>Kilometraje Total</label>
                <input type="number" name="km_total" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Km Actual sin servicio</label>
                <input type="number" name="km_actual" class="form-control" required>
            </div>
            <div class="mb-3" style="display: flex; align-items: center; gap: 10px;">
                <input type="checkbox" name="es_particular" id="es_particular" style="width: auto; cursor: pointer;">
                <label for="es_particular" style="margin: 0; cursor: pointer; font-weight: 600;">
                    🏠 Marcar como vehículo particular
                    <span style="font-weight: normal; font-size: 0.9em; color: #666; display: block; margin-top: 4px;">
                        (Los vehículos particulares no pueden tener chofer asignado)
                    </span>
                </label>
            </div>
         <div class="mb-3">
    <label>Foto del vehículo (JPG/PNG/WEBP, máx. 2 MB)</label>
    <div class="upload-container">
        <input type="file" name="foto" id="foto" accept="image/jpeg,image/png,image/webp" hidden>
        <label for="foto" class="btn-foto">📷 Subir Foto</label>
        <span id="file-chosen">Ningún archivo seleccionado</span>
    </div>
</div>
            <button type="submit" name="agregar_vehiculo" class="button">Agregar Vehículo</button>
        </form>




    </div>

</body>

</html>

<script>
document.addEventListener("DOMContentLoaded", function () {


  // Preview y validación básica de la foto
  const inputFoto = document.getElementById('foto_perfil');
  const imgPrev   = document.getElementById('preview_foto');

  if (inputFoto && imgPrev) {
    inputFoto.addEventListener('change', function () {
      const file = this.files && this.files[0] ? this.files[0] : null;
      if (!file) {
        imgPrev.style.display = 'none';
        imgPrev.src = '';
        return;
      }

      // Validaciones rápidas en cliente
      const maxBytes = 3 * 1024 * 1024; // 3 MB
      if (file.size > maxBytes) {
        alert("La imagen supera el límite de 3 MB.");
        this.value = "";
        imgPrev.style.display = 'none';
        imgPrev.src = '';
        return;
      }

      const validTypes = ['image/jpeg','image/png','image/webp'];
      if (!validTypes.includes(file.type)) {
        alert("Formato no permitido. Usa JPG, PNG o WebP.");
        this.value = "";
        imgPrev.style.display = 'none';
        imgPrev.src = '';
        return;
      }

      // Mostrar preview
      const reader = new FileReader();
      reader.onload = (e) => {
        imgPrev.src = e.target.result;
        imgPrev.style.display = 'block';
      };
      reader.readAsDataURL(file);
    });
  }
});
</script>


<script>
document.getElementById("foto").addEventListener("change", function(){
    const fileName = this.files.length > 0 ? this.files[0].name : "Ningún archivo seleccionado";
    document.getElementById("file-chosen").textContent = fileName;
});
</script>