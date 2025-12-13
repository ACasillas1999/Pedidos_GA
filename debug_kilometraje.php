<?php
session_name("GA");
session_start();

// Verificación de autenticación
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: /Pedidos_GA/Sesion/login.html");
    exit;
}

require_once __DIR__ . "/Conexiones/Conexion.php";

// Verificar conexión
if (!isset($conn) || $conn->connect_error) {
    die("Error de conexión a la base de datos");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug - Registro de Kilometraje</title>
    <link rel="icon" type="image/png" href="/Pedidos_GA/Img/logo empresa/LOGO_GPO_A.png">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        h1 {
            color: white;
            text-align: center;
            margin-bottom: 30px;
            font-size: 2.5em;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .cards-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .card h2 {
            color: #667eea;
            margin-bottom: 20px;
            font-size: 1.5em;
            border-bottom: 3px solid #667eea;
            padding-bottom: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        select, input[type="number"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s;
        }

        select:focus, input[type="number"]:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
            width: 100%;
            margin-top: 10px;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        button:active {
            transform: translateY(0);
        }

        .info-box {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
        }

        .info-box.success {
            background: #d4edda;
            border-left-color: #28a745;
        }

        .info-box.error {
            background: #f8d7da;
            border-left-color: #dc3545;
        }

        .info-box.warning {
            background: #fff3cd;
            border-left-color: #ffc107;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #dee2e6;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: #666;
        }

        .info-value {
            color: #333;
            font-weight: 500;
        }

        .debug-section {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-top: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .debug-section h2 {
            color: #764ba2;
            margin-bottom: 15px;
        }

        pre {
            background: #2d3748;
            color: #68d391;
            padding: 20px;
            border-radius: 8px;
            overflow-x: auto;
            font-size: 14px;
            line-height: 1.5;
        }

        .step-indicator {
            display: flex;
            align-items: center;
            margin: 10px 0;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
        }

        .step-number {
            background: #667eea;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 15px;
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
        }

        .badge.success {
            background: #28a745;
            color: white;
        }

        .badge.error {
            background: #dc3545;
            color: white;
        }

        .badge.warning {
            background: #ffc107;
            color: #333;
        }

        .badge.info {
            background: #17a2b8;
            color: white;
        }

        #loading {
            display: none;
            text-align: center;
            padding: 20px;
        }

        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Debug - Registro de Kilometraje</h1>

        <div class="cards-container">
            <!-- Card 1: Selección de Chofer -->
            <div class="card">
                <h2>1️⃣ Seleccionar Chofer</h2>
                <div class="form-group">
                    <label for="chofer-select">Chofer:</label>
                    <select id="chofer-select">
                        <option value="">-- Selecciona un chofer --</option>
                        <?php
                        $sql = "SELECT c.ID, c.username, c.Nombre, v.placa, v.id_vehiculo
                                FROM choferes c
                                LEFT JOIN vehiculos v ON v.id_chofer_asignado = c.ID
                                WHERE c.Estado = 'Activo'
                                ORDER BY c.Nombre";
                        $result = $conn->query($sql);

                        if (!$result) {
                            echo "<option value=''>Error en consulta: " . $conn->error . "</option>";
                        } elseif ($result->num_rows == 0) {
                            echo "<option value=''>No hay choferes activos</option>";
                        } else {
                            while ($row = $result->fetch_assoc()) {
                                $vehiculo = $row['placa'] ? " - Vehículo: {$row['placa']}" : " - Sin vehículo";
                                echo "<option value='{$row['username']}' data-id='{$row['ID']}' data-vehiculo='{$row['id_vehiculo']}'>{$row['Nombre']} ({$row['username']}){$vehiculo}</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                <button onclick="consultarEstado()">Consultar Estado</button>
            </div>

            <!-- Card 2: Estado Actual -->
            <div class="card">
                <h2>2️⃣ Estado Actual del Kilometraje</h2>
                <div id="estado-container">
                    <p style="text-align: center; color: #999; padding: 20px;">
                        Selecciona un chofer y consulta el estado
                    </p>
                </div>
            </div>

            <!-- Card 3: Registro de Kilometraje -->
            <div class="card">
                <h2>3️⃣ Registrar Nuevo Kilometraje</h2>
                <div id="registro-container">
                    <div class="info-box warning">
                        ⚠️ Primero consulta el estado del chofer
                    </div>
                </div>
            </div>
        </div>

        <!-- Debug Log -->
        <div class="debug-section">
            <h2>📋 Log de Debug</h2>
            <div id="loading">
                <div class="spinner"></div>
                <p>Procesando...</p>
            </div>
            <div id="debug-log">
                <pre>Esperando acción...</pre>
            </div>
        </div>
    </div>

    <script>
        let estadoActual = null;
        let usernameActual = null;

        function log(mensaje, tipo = 'info') {
            const debugLog = document.getElementById('debug-log');
            const timestamp = new Date().toLocaleTimeString();
            const colores = {
                info: '#68d391',
                warning: '#ffc107',
                error: '#fc8181',
                success: '#48bb78'
            };

            debugLog.innerHTML = `<pre style="color: ${colores[tipo]};">[${timestamp}] ${mensaje}</pre>` + debugLog.innerHTML;
        }

        async function consultarEstado() {
            const select = document.getElementById('chofer-select');
            const username = select.value;

            if (!username) {
                alert('Por favor selecciona un chofer');
                return;
            }

            usernameActual = username;
            const loading = document.getElementById('loading');
            loading.style.display = 'block';

            log(`🔍 Consultando estado para chofer: ${username}`, 'info');

            try {
                const response = await fetch(`App/estado_kilometraje.php?username=${username}`);
                const data = await response.json();

                log(`📥 Respuesta recibida:\n${JSON.stringify(data, null, 2)}`, 'success');

                estadoActual = data;
                mostrarEstado(data);

                if (data.assigned) {
                    mostrarFormularioRegistro(data);
                }

            } catch (error) {
                log(`❌ Error al consultar estado: ${error.message}`, 'error');
                alert('Error al consultar el estado');
            } finally {
                loading.style.display = 'none';
            }
        }

        function mostrarEstado(data) {
            const container = document.getElementById('estado-container');

            if (!data.ok) {
                container.innerHTML = `
                    <div class="info-box error">
                        ❌ Error: ${data.error}
                    </div>
                `;
                return;
            }

            if (!data.assigned) {
                container.innerHTML = `
                    <div class="info-box warning">
                        ⚠️ Este chofer no tiene vehículo asignado
                    </div>
                `;
                return;
            }

            const necesitaRegistrar = data.needs_km
                ? '<span class="badge warning">⚠️ Pendiente</span>'
                : '<span class="badge success">✅ Registrado</span>';

            container.innerHTML = `
                <div class="info-box ${data.needs_km ? 'warning' : 'success'}">
                    <div class="info-row">
                        <span class="info-label">Estado:</span>
                        <span class="info-value">${necesitaRegistrar}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">ID Vehículo:</span>
                        <span class="info-value">${data.id_vehiculo}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">ID Chofer:</span>
                        <span class="info-value">${data.id_chofer || 'N/A'}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Km Total Vehículo:</span>
                        <span class="info-value">${data.Km_Total ? data.Km_Total.toLocaleString() : 'N/A'} km</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Último Registro:</span>
                        <span class="info-value">${data.last_fecha || 'Nunca'}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Último Km Final:</span>
                        <span class="info-value">${data.last_km_final ? data.last_km_final.toLocaleString() : 'N/A'} km</span>
                    </div>
                </div>
            `;
        }

        function mostrarFormularioRegistro(estado) {
            const container = document.getElementById('registro-container');

            const kmSugerido = estado.last_km_final || estado.Km_Total || 0;

            container.innerHTML = `
                <div class="form-group">
                    <label for="km-actual">Kilometraje Actual del Odómetro:</label>
                    <input type="number" id="km-actual" min="${kmSugerido}" value="${kmSugerido}" step="1">
                    <small style="color: #666; font-size: 12px; margin-top: 5px; display: block;">
                        💡 El kilometraje debe ser mayor o igual a: ${kmSugerido.toLocaleString()} km
                    </small>
                </div>

                <div class="info-box">
                    <div class="step-indicator">
                        <div class="step-number">1</div>
                        <div>Se usará el último km registrado (${estado.last_km_final || 0}) como km_inicial</div>
                    </div>
                    <div class="step-indicator">
                        <div class="step-number">2</div>
                        <div>Se calculará: km_recorridos = km_actual - km_inicial</div>
                    </div>
                    <div class="step-indicator">
                        <div class="step-number">3</div>
                        <div>Se actualizará la tabla vehiculos</div>
                    </div>
                    <div class="step-indicator">
                        <div class="step-number">4</div>
                        <div>Se insertará en registro_kilometraje</div>
                    </div>
                    <div class="step-indicator">
                        <div class="step-number">5</div>
                        <div>Se verificará si necesita orden de servicio</div>
                    </div>
                </div>

                <button onclick="registrarKilometraje()">Registrar Kilometraje</button>
            `;
        }

        async function registrarKilometraje() {
            if (!usernameActual || !estadoActual) {
                alert('Primero consulta el estado del chofer');
                return;
            }

            const kmActual = document.getElementById('km-actual').value;

            if (!kmActual || kmActual <= 0) {
                alert('Ingresa un kilometraje válido');
                return;
            }

            const loading = document.getElementById('loading');
            loading.style.display = 'block';

            log(`📝 Iniciando registro de kilometraje...`, 'info');
            log(`   Username: ${usernameActual}`, 'info');
            log(`   Km Actual: ${kmActual}`, 'info');

            try {
                const formData = new FormData();
                formData.append('username', usernameActual);
                formData.append('km', kmActual);

                const response = await fetch('App/registrar_kilometraje.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                log(`📥 Respuesta del registro:\n${JSON.stringify(data, null, 2)}`, data.ok ? 'success' : 'error');

                if (data.ok) {
                    const kmRecorridos = data.km_final - data.km_inicial;

                    mostrarResultadoRegistro(data, kmRecorridos);

                    // Refrescar estado
                    setTimeout(() => consultarEstado(), 1000);
                } else {
                    alert(`Error al registrar: ${data.error}`);
                }

            } catch (error) {
                log(`❌ Error en el registro: ${error.message}`, 'error');
                alert('Error al registrar el kilometraje');
            } finally {
                loading.style.display = 'none';
            }
        }

        function mostrarResultadoRegistro(data, kmRecorridos) {
            const container = document.getElementById('registro-container');

            container.innerHTML = `
                <div class="info-box success">
                    <h3 style="margin-bottom: 15px; color: #28a745;">✅ Kilometraje Registrado Exitosamente</h3>

                    <div class="info-row">
                        <span class="info-label">ID Registro:</span>
                        <span class="info-value">${data.id_registro}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">ID Vehículo:</span>
                        <span class="info-value">${data.id_vehiculo}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">ID Chofer:</span>
                        <span class="info-value">${data.id_chofer || 'N/A'}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Km Inicial:</span>
                        <span class="info-value">${data.km_inicial.toLocaleString()} km</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Km Final:</span>
                        <span class="info-value">${data.km_final.toLocaleString()} km</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Km Recorridos:</span>
                        <span class="info-value" style="color: #28a745; font-weight: bold;">${kmRecorridos.toLocaleString()} km</span>
                    </div>
                </div>

                <button onclick="consultarEstado()" style="background: #28a745; margin-top: 15px;">
                    🔄 Actualizar Estado
                </button>
            `;
        }

        // Log inicial
        log('Sistema de debug de kilometraje iniciado', 'success');
        log('Selecciona un chofer para comenzar', 'info');
    </script>
</body>
</html>
