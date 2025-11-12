<?php
session_name("GA");
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: /Pedidos_GA/Sesion/login.html");
    exit;
}

require_once __DIR__ . "/Conexiones/Conexion.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $id_pedido = $_POST['id_pedido'];

    $sql_pedido_actual = "SELECT * FROM pedidos WHERE ID = $id_pedido";
    $resultado_actual = $conn->query($sql_pedido_actual);

    if ($resultado_actual->num_rows == 0) {
        echo "Pedido no encontrado.";
        exit;
    }

    $pedido_actual = $resultado_actual->fetch_assoc();

    // Lista de campos exactos como están en tu BD
    $campos = [
    'SUCURSAL', 'ESTADO', 'FECHA_RECEPCION_FACTURA', 'FECHA_ENTREGA_CLIENTE',
    'CHOFER_ASIGNADO', 'VENDEDOR', 'FACTURA', 'DIRECCION', 'FECHA_MIN_ENTREGA',
    'FECHA_MAX_ENTREGA', 'MIN_VENTANA_HORARIA_1', 'MAX_VENTANA_HORARIA_1',
    'NOMBRE_CLIENTE', 'TELEFONO', 'CONTACTO', 'COMENTARIOS', 'Coord_Origen', 'Coord_Destino',
    'TIPO_ENVIO'
];

    // Campos de precio (requieren procesamiento especial)
    $campos_precio = ['precio_factura_vendedor', 'precio_factura_real', 'precio_validado_jc',
                      'fecha_validacion_precio', 'usuario_validacion_precio'];


    $cambios_realizados = [];
    $valores_update = [];

    foreach($campos as $campo){
        $campo_post = strtolower($campo);  // Para capturarlo desde $_POST
        $nuevo_valor = isset($_POST[$campo_post]) ? trim($_POST[$campo_post]) : '';
        $valor_actual = isset($pedido_actual[$campo]) ? trim($pedido_actual[$campo]) : '';

        // VALIDACIÓN CRÍTICA: Verificar que el precio esté validado antes de permitir cambiar el chofer
        if ($campo === 'CHOFER_ASIGNADO' && $nuevo_valor !== $valor_actual && !empty($nuevo_valor)) {
            // Verificar si el precio está validado
            $precio_validado = isset($pedido_actual['precio_validado_jc']) ? intval($pedido_actual['precio_validado_jc']) : 0;

            if ($precio_validado != 1) {
                // Si el precio no está validado, no permitir el cambio de chofer
                echo '<script>alert("ERROR: No puedes asignar un chofer sin antes validar el precio de la factura."); window.history.back();</script>';
                exit;
            }
        }

        if($valor_actual !== $nuevo_valor){
            $cambios_realizados[] = "$campo: '$valor_actual' → '$nuevo_valor'";
        }

        $valores_update[] = "$campo = '$nuevo_valor'";
    }

    // Procesamiento especial para campos de precio
    $rol = $_SESSION["Rol"];

    // Para Vendedores (VR)
    if ($rol === "VR") {
        $precio_vendedor = isset($_POST['precio_factura_vendedor']) ? floatval($_POST['precio_factura_vendedor']) : 0;
        $precio_actual_vendedor = isset($pedido_actual['precio_factura_vendedor']) ? floatval($pedido_actual['precio_factura_vendedor']) : 0;

        if ($precio_vendedor != $precio_actual_vendedor) {
            $cambios_realizados[] = "Precio Factura Vendedor: '$precio_actual_vendedor' → '$precio_vendedor'";
        }

        $valores_update[] = "precio_factura_vendedor = $precio_vendedor";
        $valores_update[] = "precio_factura_real = $precio_vendedor"; // Sincronizar con el real
    }

    // Para Jefe de Choferes y Admin
    if ($rol === "JC" || $rol === "Admin") {
        $precio_real = isset($_POST['precio_factura_real']) ? floatval($_POST['precio_factura_real']) : 0;
        $precio_actual_real = isset($pedido_actual['precio_factura_real']) ? floatval($pedido_actual['precio_factura_real']) : 0;
        $precio_vendedor_original = isset($pedido_actual['precio_factura_vendedor']) ? floatval($pedido_actual['precio_factura_vendedor']) : 0;

        $precio_validado = isset($_POST['precio_validado_jc']) && $_POST['precio_validado_jc'] == '1' ? 1 : 0;
        $validado_anterior = isset($pedido_actual['precio_validado_jc']) ? intval($pedido_actual['precio_validado_jc']) : 0;

        // Detectar cambios en precio real
        if ($precio_real != $precio_actual_real) {
            $diferencia = abs($precio_vendedor_original - $precio_real);
            if ($precio_vendedor_original != $precio_real && $precio_vendedor_original > 0) {
                $tipo_diff = ($precio_vendedor_original > $precio_real) ? 'cobró de más' : 'cobró de menos';
                $cambios_realizados[] = "Precio corregido por JC: de $$precio_actual_real a $$precio_real (Diferencia: $$diferencia - vendedor $tipo_diff)";
            } else {
                $cambios_realizados[] = "Precio real actualizado: '$precio_actual_real' → '$precio_real'";
            }
        }

        $valores_update[] = "precio_factura_real = $precio_real";
        $valores_update[] = "precio_validado_jc = $precio_validado";

        // Si se está validando por primera vez o cambiando validación
        if ($precio_validado == 1 && $validado_anterior == 0) {
            $fecha_validacion = date("Y-m-d H:i:s");
            $usuario_validacion = $_SESSION['username'];
            $valores_update[] = "fecha_validacion_precio = '$fecha_validacion'";
            $valores_update[] = "usuario_validacion_precio = '$usuario_validacion'";
            $cambios_realizados[] = "Precio validado por JC: $usuario_validacion";

            // Alertar si el precio es menor a $1000
            if ($precio_real < 1000 && $precio_real > 0) {
                $cambios_realizados[] = "⚠️ ALERTA: Precio menor a $1000 ($$precio_real) - Flete no conveniente";
            }
        } elseif ($precio_validado == 0 && $validado_anterior == 1) {
            $valores_update[] = "fecha_validacion_precio = NULL";
            $valores_update[] = "usuario_validacion_precio = NULL";
            $cambios_realizados[] = "Validación de precio removida por JC";
        }
    }

    $sql_update = "UPDATE pedidos SET " . implode(", ", $valores_update) . " WHERE ID = $id_pedido";

    if ($conn->query($sql_update) === TRUE) {

        if(!empty($cambios_realizados)){
            $Usuario_ID = $_SESSION['username'];
            $Fecha_Hora = date("Y-m-d H:i:s");
            $Cambio = implode(" | ", $cambios_realizados);

            $sql_historial = "
                INSERT INTO historial_cambios (Usuario_ID, Pedido_ID, Cambio, Fecha_Hora)
                VALUES (?, ?, ?, ?)";

            if ($stmt = $conn->prepare($sql_historial)) {
                $stmt->bind_param("siss", $Usuario_ID, $id_pedido, $Cambio, $Fecha_Hora);
                $stmt->execute();
                $stmt->close();
            }

            // Verificar si se asignó o cambió el chofer y enviar notificación automática
            $chofer_cambiado = false;
            foreach($cambios_realizados as $cambio) {
                if (strpos($cambio, 'CHOFER_ASIGNADO:') !== false) {
                    $chofer_cambiado = true;
                    break;
                }
            }

            if ($chofer_cambiado) {
                $nuevo_chofer = isset($_POST['chofer_asignado']) ? trim($_POST['chofer_asignado']) : '';

                // Solo enviar si hay un chofer asignado (no vacío)
                if (!empty($nuevo_chofer)) {
                    // Obtener el número del chofer
                    $sql_chofer = "SELECT Numero FROM choferes WHERE username = ?";
                    if ($stmt_chofer = $conn->prepare($sql_chofer)) {
                        $stmt_chofer->bind_param("s", $nuevo_chofer);
                        $stmt_chofer->execute();
                        $result_chofer = $stmt_chofer->get_result();

                        if ($row_chofer = $result_chofer->fetch_assoc()) {
                            $numero_chofer = $row_chofer['Numero'];
                            $telefono = '52' . $numero_chofer;

                            // TOKEN Y URL de WhatsApp (mismo que en Mensaje_WP_NotificarChoferes.php)
                            $token = 'EAAGacaATjwEBOZBgqhohcVk1ZBGEAbiTl7i86qESvSPjdllaomwzIG7LmOOvyTFpzyIlXX6dtTYTVTLLuw6SjaLoh2rec07I8qu1nGNYSVZAmQTGNa3QCQjujTqfd7QuLLwFNQllnX2z1V7JvToDhEi5KVqUWXHSqgSETvGyU7S2SN2fpXW0NpQaRI48pwZAgGS7A1BQMjLl5ZBjy';
                            $url = 'https://graph.facebook.com/v19.0/335894526282507/messages';

                            // Configuración del mensaje (mismo template)
                            $mensaje = json_encode([
                                "messaging_product" => "whatsapp",
                                "to" => $telefono,
                                "type" => "template",
                                "template" => [
                                    "name" => "ga_notificarchofer",
                                    "language" => ["code" => "en_US"],
                                ]
                            ]);

                            // Enviar mensaje
                            $header = ["Authorization: Bearer " . $token, "Content-Type: application/json"];
                            $curl = curl_init();
                            curl_setopt($curl, CURLOPT_URL, $url);
                            curl_setopt($curl, CURLOPT_POSTFIELDS, $mensaje);
                            curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
                            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                            $response = json_decode(curl_exec($curl), true);
                            curl_close($curl);

                            // Opcional: registrar en historial que se envió notificación
                            // (puedes comentar esto si no quieres que aparezca en el historial)
                            $cambio_notif = "Notificación WhatsApp enviada al chofer $nuevo_chofer";
                            $sql_historial_notif = "INSERT INTO historial_cambios (Usuario_ID, Pedido_ID, Cambio, Fecha_Hora) VALUES (?, ?, ?, ?)";
                            if ($stmt_notif = $conn->prepare($sql_historial_notif)) {
                                $stmt_notif->bind_param("siss", $Usuario_ID, $id_pedido, $cambio_notif, $Fecha_Hora);
                                $stmt_notif->execute();
                                $stmt_notif->close();
                            }
                        }
                        $stmt_chofer->close();
                    }
                }

                // Mantener coherencia con grupos/rutas si el pedido pertenece a un grupo activo
                // Regla: si el nuevo chofer difiere del chofer del grupo activo, por defecto se remueve el pedido del grupo.
                // Si se envía accion_grupo_chofer=actualizar_grupo y el usuario es Admin/JC, se actualiza el chofer de todo el grupo.
                try {
                    // Buscar grupo activo del pedido
                    $sqlGrupo = "SELECT pg.grupo_id, gr.nombre_grupo, gr.chofer_asignado
                                  FROM pedidos_grupos pg
                                  INNER JOIN grupos_rutas gr ON pg.grupo_id = gr.id AND gr.estado = 'ACTIVO'
                                  WHERE pg.pedido_id = ?
                                  LIMIT 1";
                    if ($stmtG = $conn->prepare($sqlGrupo)) {
                        $stmtG->bind_param("i", $id_pedido);
                        $stmtG->execute();
                        $resG = $stmtG->get_result();
                        if ($grp = $resG->fetch_assoc()) {
                            $grupoId = (int)$grp['grupo_id'];
                            $grupoChofer = $grp['chofer_asignado'];
                            $grupoNombre = $grp['nombre_grupo'];

                            if (!empty($nuevo_chofer) && $nuevo_chofer !== $grupoChofer) {
                                $accionGrupo = isset($_POST['accion_grupo_chofer']) ? $_POST['accion_grupo_chofer'] : '';
                                $rolUsuario = $_SESSION['Rol'] ?? '';

                                if ($accionGrupo === 'actualizar_grupo' && ($rolUsuario === 'Admin' || $rolUsuario === 'JC')) {
                                    // Actualizar chofer del grupo y de todos los pedidos del grupo
                                    $conn->begin_transaction();
                                    try {
                                        $sqlUG = "UPDATE grupos_rutas SET chofer_asignado = ? WHERE id = ?";
                                        $stmtUG = $conn->prepare($sqlUG);
                                        $stmtUG->bind_param("si", $nuevo_chofer, $grupoId);
                                        $stmtUG->execute();
                                        $stmtUG->close();

                                        $sqlUP = "UPDATE pedidos p
                                                  INNER JOIN pedidos_grupos pg ON p.ID = pg.pedido_id
                                                  SET p.CHOFER_ASIGNADO = ?
                                                  WHERE pg.grupo_id = ?";
                                        $stmtUP = $conn->prepare($sqlUP);
                                        $stmtUP->bind_param("si", $nuevo_chofer, $grupoId);
                                        $stmtUP->execute();
                                        $stmtUP->close();

                                        // Registrar historial para el pedido actual
                                        $desc = "Chofer del grupo '$grupoNombre' actualizado a '$nuevo_chofer' por cambio individual";
                                        $sqlH = "INSERT INTO historial_cambios (Usuario_ID, Pedido_ID, Cambio, Fecha_Hora) VALUES (?, ?, ?, ?)";
                                        if ($stmtH = $conn->prepare($sqlH)) {
                                            $stmtH->bind_param("siss", $Usuario_ID, $id_pedido, $desc, $Fecha_Hora);
                                            $stmtH->execute();
                                            $stmtH->close();
                                        }

                                        $conn->commit();
                                    } catch (Exception $e) {
                                        $conn->rollback();
                                        // En caso de error, como fallback, remover del grupo
                                        $sqlDel = "DELETE FROM pedidos_grupos WHERE pedido_id = ? AND grupo_id = ?";
                                        $stmtDel = $conn->prepare($sqlDel);
                                        $stmtDel->bind_param("ii", $id_pedido, $grupoId);
                                        $stmtDel->execute();
                                        $stmtDel->close();
                                    }
                                } else {
                                    // Remover el pedido del grupo para evitar inconsistencia
                                    $sqlDel = "DELETE FROM pedidos_grupos WHERE pedido_id = ? AND grupo_id = ?";
                                    if ($stmtDel = $conn->prepare($sqlDel)) {
                                        $stmtDel->bind_param("ii", $id_pedido, $grupoId);
                                        $stmtDel->execute();
                                        $stmtDel->close();
                                    }

                                    // Registrar en historial
                                    $desc = "Removido del grupo '$grupoNombre' por cambio de chofer a '$nuevo_chofer'";
                                    $sqlH = "INSERT INTO historial_cambios (Usuario_ID, Pedido_ID, Cambio, Fecha_Hora) VALUES (?, ?, ?, ?)";
                                    if ($stmtH = $conn->prepare($sqlH)) {
                                        $stmtH->bind_param("siss", $Usuario_ID, $id_pedido, $desc, $Fecha_Hora);
                                        $stmtH->execute();
                                        $stmtH->close();
                                    }
                                }
                            }
                        }
                        $stmtG->close();
                    }
                } catch (Exception $e) {
                    // silencioso: no romper el flujo de actualización si algo falla en este suplemento
                }
            }
        }

        echo '<script>window.location.href = "Inicio.php?id=' . $id_pedido . '";</script>';

    } else {
        echo "Error al actualizar el pedido: " . $conn->error;
    }

} else {
    echo "No se ha recibido ningún dato para actualizar.";
}

$conn->close();
?>
