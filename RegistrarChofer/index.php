<!DOCTYPE html>
<html lang="es">
<head>
  <link rel="icon" type="image/png" href="/Img/Botones%20entregas/ICONOSPAG/ICONOPEDIDOS.png">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registro de Chofer</title>
  <link rel="stylesheet" type="text/css" href="styles.css">
</head>

<script>
document.addEventListener("DOMContentLoaded", function() {
  // Hovers
  const iconoVolver = document.querySelector(".icono-Volver");
  const iconoFAP    = document.querySelector(".icono-FAP-img");

  const imgNormalVolver = "/Img/Botones%20entregas/RegistrarChofer/VOLVAZ.png";
  const imgHoverVolver  = "/Img/Botones%20entregas/RegistrarChofer/VOLVNA.png";

  if (iconoVolver) {
    iconoVolver.addEventListener("mouseover", () => iconoVolver.src = imgHoverVolver);
    iconoVolver.addEventListener("mouseout",  () => iconoVolver.src = imgNormalVolver);
  }

  const imgNormalFAP = "/Img/Botones%20entregas/RegistrarChofer/REGNA.png";
  const imgHoverFAP  = "/Img/Botones%20entregas/RegistrarChofer/REGNAF.png";

  if (iconoFAP) {
    iconoFAP.addEventListener("mouseover", () => iconoFAP.src = imgHoverFAP);
    iconoFAP.addEventListener("mouseout",  () => iconoFAP.src = imgNormalFAP);
  }
});
</script>

<body>
<header class="header">
  <div class="logo">
    <img src="/Img/Botones%20entregas/RegistrarChofer/REGCHOFTIT.png" alt="Registro de Chofer" style="max-width: 15%; height: auto;">
  </div>
  <nav class="navbar">
    <ul>
      <li class="nav-item">
        <a href="../Choferes.php" class="nav-link">
          <!-- Usa / y codifica espacios -->
          <img src="/Img/Botones%20entregas/RegistrarChofer/VOLVAZ.png"
               alt="Volver" class="icono-Volver"
               style="max-width: 5%; height: auto; position:absolute; top: 50px; left: 25px;">
        </a>
      </li>
    </ul>
  </nav>
</header>

<div class="container">
  <h2>Registro de Chofer</h2>

  <!-- enctype obligatorio para archivos -->
  <form action="Registrar_Chofer.php" method="post" enctype="multipart/form-data">
    <label for="username">Nombre de Usuario:</label>
    <input type="text" id="username" name="username" required>

    <label for="Numero">Número Celular:</label>
    <input type="text" id="Numero" name="Numero" required>

    <label for="sucursal">Sucursal:</label>
    <select id="sucursal" name="sucursal" required>
      <option value="">Selecciona una sucursal</option>
      <option value="DIMEGSA">DIMEGSA</option>
      <option value="DEASA">DEASA</option>
      <option value="AIESA">AIESA</option>
      <option value="SEGSA">SEGSA</option>
      <option value="FESA">FESA</option>
      <option value="TAPATIA">TAPATIA</option>
      <option value="GABSA">GABSA</option>
      <option value="ILUMINACION">ILUMINACION</option>
      <option value="VALLARTA">VALLARTA</option>
      <option value="CODI">CODI</option>
      <option value="QUERETARO">QUERETARO</option>
    </select>

    <p></p>
    <label for="Estado">Estado:</label>
    <input type="text" id="Estado" name="Estado" value="ACTIVO" readonly>

    <div class="mb-3">
      <label>Foto de perfil (JPG/PNG/WEBP, máx. 2 MB)</label>
      <input id="foto_perfil" type="file" name="foto"
             accept="image/jpeg,image/png,image/webp" class="form-control">
      <div style="margin-top:8px;">
        <img id="preview_foto" alt="Vista previa"
             style="display:none; max-width:180px; height:auto; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,.15);">
      </div>
    </div>

    <label for="password">Contraseña:</label>
    <input type="password" id="password" name="password" required>

    <label for="confirm_password">Confirmar Contraseña:</label>
    <input type="password" id="confirm_password" name="confirm_password" required>

    <button type="submit" class="icono-FAP" style="background:none; border:none; padding:0;">
      <img src="/Img/Botones%20entregas/RegistrarChofer/REGNA.png"
           class="icono-FAP-img" alt="Registrar" style="max-width:50%; height:auto; display:flex;">
    </button>

    <div class="error-message">
      <?php if (isset($error_message)) echo $error_message; ?>
    </div>
  </form>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
  // Preview y validación básica de la foto
  const inputFoto = document.getElementById('foto_perfil');
  const imgPrev   = document.getElementById('preview_foto');
  if (!inputFoto || !imgPrev) return;

  inputFoto.addEventListener('change', function () {
    const file = this.files && this.files[0] ? this.files[0] : null;
    if (!file) {
      imgPrev.style.display = 'none';
      imgPrev.src = '';
      return;
    }

    const maxBytes = 2 * 1024 * 1024; // 2 MB
    if (file.size > maxBytes) {
      alert("La imagen supera el límite de 2 MB.");
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

    const url = URL.createObjectURL(file);
    imgPrev.onload = () => URL.revokeObjectURL(url);
    imgPrev.src = url;
    imgPrev.style.display = 'block';
  });
});
</script>
</body>
</html>
