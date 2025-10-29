<?php
session_name("GA");
session_start();

// Errores (desarrollo)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Requiere login
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: /Pedidos_GA/Sesion/login.html");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    require_once __DIR__ . "/../Conexiones/Conexion.php";

    // Datos del form
    $username         = trim($_POST["username"] ?? "");
    $password         = $_POST["password"] ?? "";
    $sucursal         = trim($_POST["sucursal"] ?? "");
    $Numero           = trim($_POST["Numero"] ?? "");
    $Estado           = trim($_POST["Estado"] ?? "ACTIVO");
    $confirm_password = $_POST["confirm_password"] ?? "";

    // Validaciones básicas
    if ($password !== $confirm_password) {
        echo "<script>alert('Las contraseñas no coinciden.'); history.back();</script>";
        exit;
    }
    if ($username === "" || $sucursal === "" || $Numero === "" || $Estado === "") {
        echo "<script>alert('Faltan campos obligatorios.'); history.back();</script>";
        exit;
    }

    // (Actualmente sin hash, como lo manejas)
    $hashed_password = $password;

    // --- Validación preliminar del archivo (si viene) ---
    $tieneArchivo = (isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE);
    $tmpFile      = null;
    $fileExt      = null;

    if ($tieneArchivo) {
        if ($_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
            echo "<script>alert('Error al subir la imagen (código: " . (int)$_FILES['foto']['error'] . ").'); history.back();</script>";
            exit;
        }
        $tmp  = $_FILES['foto']['tmp_name'];
        $size = (int)$_FILES['foto']['size'];

        // MIME real
        $type = function_exists('mime_content_type') ? mime_content_type($tmp) : null;
        if (!$type) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $type = finfo_file($finfo, $tmp);
                finfo_close($finfo);
            }
        }

        $permitidos = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (!isset($permitidos[$type])) {
            echo "<script>alert('Formato no permitido. Usa JPG, PNG o WebP.'); history.back();</script>";
            exit;
        }
        if ($size > 2 * 1024 * 1024) { // 2 MB
            echo "<script>alert('La imagen supera el límite de 2 MB.'); history.back();</script>";
            exit;
        }
        if (@getimagesize($tmp) === false) {
            echo "<script>alert('El archivo no es una imagen válida.'); history.back();</script>";
            exit;
        }

        $tmpFile = $tmp;
        $fileExt = $permitidos[$type]; // 'jpg' | 'png' | 'webp'
    }

    // ¿Existe el usuario?
    $stmt = $conn->prepare("SELECT ID, foto_perfil FROM choferes WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $rs  = $stmt->get_result();
    $row = $rs->fetch_assoc();
    $stmt->close();

    // Rutas
    $dirAbs  = dirname(__DIR__) . "/uploads/choferes";   // carpeta física (uploads en minúsculas)
    $webBase = "/Pedidos_GA/uploads/choferes";           // ruta pública base
    if (!is_dir($dirAbs)) { @mkdir($dirAbs, 0775, true); }

    if ($row) {
        // ===== UPDATE =====
        $idChofer = (int)$row['ID'];
        $foto_rel = null;

        if ($tieneArchivo && $tmpFile && $fileExt) {
            $ts       = date('YmdHis');
            $filename = "chofer_{$idChofer}_{$ts}.{$fileExt}";
            $dest     = $dirAbs . "/" . $filename;

            if (!move_uploaded_file($tmpFile, $dest)) {
                echo "<script>alert('No se pudo guardar la imagen en el servidor.'); history.back();</script>";
                exit;
            }
            $foto_rel = $webBase . "/" . $filename;

            // Borrar foto anterior si era nuestra
            if (!empty($row['foto_perfil']) && strpos($row['foto_perfil'], '/Pedidos_GA/uploads/choferes/') === 0) {
                $oldAbs = dirname(__DIR__) . str_replace('/Pedidos_GA', '', $row['foto_perfil']);
                if (is_file($oldAbs)) { @unlink($oldAbs); }
            }
        }

        if ($foto_rel) {
            $sql  = "UPDATE choferes
                        SET password = ?, Sucursal = ?, Numero = ?, Estado = ?, foto_perfil = ?
                      WHERE username = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssss", $hashed_password, $sucursal, $Numero, $Estado, $foto_rel, $username);
        } else {
            $sql  = "UPDATE choferes
                        SET password = ?, Sucursal = ?, Numero = ?, Estado = ?
                      WHERE username = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssss", $hashed_password, $sucursal, $Numero, $Estado, $username);
        }

        if ($stmt->execute()) {
            $stmt->close();
            $conn->close();
            header("location: Registro_exitoso.html");
            exit;
        } else {
            $stmt->close();
            $conn->close();
            echo "<script>alert('Error al actualizar el usuario.'); history.back();</script>";
            exit;
        }

    } else {
        // ===== INSERT =====
        // 1) Inserta sin foto para obtener ID
        $sql  = "INSERT INTO choferes (username, password, Sucursal, Numero, Estado)
                 VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssss", $username, $hashed_password, $sucursal, $Numero, $Estado);

        if (!$stmt->execute()) {
            $stmt->close();
            $conn->close();
            echo "<script>alert('Error al registrar el usuario.'); history.back();</script>";
            exit;
        }
        $idChofer = $conn->insert_id;
        $stmt->close();

        // 2) Si subieron foto, se guarda con el nombre deseado y se actualiza la ruta
        if ($tieneArchivo && $tmpFile && $fileExt) {
            $ts       = date('YmdHis');
            $filename = "chofer_{$idChofer}_{$ts}.{$fileExt}";
            $dest     = $dirAbs . "/" . $filename;

            if (!move_uploaded_file($tmpFile, $dest)) {
                $conn->close();
                echo "<script>alert('Usuario creado, pero no se pudo guardar la imagen.'); window.location.href='Registro_exitoso.html';</script>";
                exit;
            }

            $foto_rel = $webBase . "/" . $filename;

            $stmt = $conn->prepare("UPDATE choferes SET foto_perfil = ? WHERE ID = ?");
            $stmt->bind_param("si", $foto_rel, $idChofer);
            $stmt->execute();
            $stmt->close();
        }

        $conn->close();
        header("location: Registro_exitoso.html");
        exit;
    }
}
?>
