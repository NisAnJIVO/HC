<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/Huesped.php';
require_once __DIR__ . '/../../models/Habitacion.php';
require_once __DIR__ . '/../../models/RegistroOcupacion.php';

$page_title = 'Nuevo Registro de Huésped';
$mensaje = '';
$tipo_mensaje = '';

// Validar administrador
if (!esAdmin()) {
    header('Location: ' . BASE_PATH . '/index.php');
    exit;
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $huespedModel = new Huesped();
    $habitacionModel = new Habitacion();
    $registroModel = new RegistroOcupacion();
    
    try {
        // Buscar o crear huésped
        $huesped_existente = $huespedModel->buscarPorCI($_POST['ci_pasaporte']);
        
        if ($huesped_existente) {
            $huesped_id = $huesped_existente['id'];
            $mensaje = 'Huésped encontrado en el sistema. ';
        } else {
            $datos_huesped = [
                'nombres_apellidos' => clean_input($_POST['nombres_apellidos']),
                'genero' => $_POST['genero'],
                'edad' => (int)$_POST['edad'],
                'estado_civil' => clean_input($_POST['estado_civil']),
                'nacionalidad' => clean_input($_POST['nacionalidad']),
                'ci_pasaporte' => clean_input($_POST['ci_pasaporte']),
                'profesion' => clean_input($_POST['profesion']),
                'objeto' => clean_input($_POST['objeto']),
                'procedencia' => clean_input($_POST['procedencia'])
            ];
            
            $huesped_id = $huespedModel->crear($datos_huesped);
            if (!$huesped_id) {
                throw new Exception('Error al registrar huésped');
            }
            $mensaje = 'Huésped registrado. ';
        }
        
        // Obtener habitación
        $habitacion = $habitacionModel->obtenerPorNumero($_POST['nro_pieza']);
        if (!$habitacion) {
            throw new Exception('Habitación no encontrada');
        }
        
        // Verificar si existe un registro reciente del huésped en esta habitación
        $registro_reciente = $registroModel->buscarRegistroReciente($huesped_id, $habitacion['id'], 2);
        if ($registro_reciente) {
            $mensaje_aviso = "Este huésped tiene una estadía reciente en esta habitación. Redirigiendo a extensión de estadía...";
            header("refresh:2;url=extender_estadia.php?id={$registro_reciente['id']}");
            $mensaje = $mensaje_aviso;
            $tipo_mensaje = 'info';
        } else {
            // Validar capacidad de habitación vs número de personas
            $tipo_hab = $habitacion['tipo'];
            $capacidad_maxima = 2; // Por defecto
            if ($tipo_hab == 'Individual') $capacidad_maxima = 1;
            elseif ($tipo_hab == 'Doble' || $tipo_hab == 'Matrimonial') $capacidad_maxima = 2;
            elseif ($tipo_hab == 'Triple') $capacidad_maxima = 3;
            elseif ($tipo_hab == 'Familiar' || $tipo_hab == 'Suite') $capacidad_maxima = 4;
            
            $num_acompanantes = 0;
            if (isset($_POST['acomp_ci']) && is_array($_POST['acomp_ci'])) {
                foreach ($_POST['acomp_ci'] as $ci) {
                    if (!empty($ci)) $num_acompanantes++;
                }
            }
            
            $total_personas = 1 + $num_acompanantes;
            if ($total_personas > $capacidad_maxima) {
                throw new Exception("La habitación {$tipo_hab} solo permite {$capacidad_maxima} persona(s). Intentó registrar {$total_personas}.");
            }
            
            // Calcular fecha de salida estimada
            $fecha_ingreso = $_POST['fecha_ingreso'];
            $nro_dias = (int)$_POST['nro_dias'];
            $fecha_salida = date('Y-m-d', strtotime($fecha_ingreso . ' +' . $nro_dias . ' days'));
            
            if (empty($_POST['prox_destino'])) {
                throw new Exception('El Próximo Destino es obligatorio.');
            }
            if (empty($_POST['via_ingreso'])) {
                throw new Exception('La Vía de Ingreso es obligatoria.');
            }

            // Registrar ocupación
            $datos_ocupacion = [
                'huesped_id' => $huesped_id,
                'habitacion_id' => $habitacion['id'],
                'nro_pieza' => clean_input($_POST['nro_pieza']),
                'prox_destino' => clean_input($_POST['prox_destino']),
                'via_ingreso' => clean_input($_POST['via_ingreso']),
                'fecha_ingreso' => $fecha_ingreso,
                'nro_dias' => $nro_dias,
                'fecha_salida_estimada' => $fecha_salida
            ];
            
            $ocupacion_id = $registroModel->crear($datos_ocupacion);
            if (!$ocupacion_id) {
                throw new Exception('Error al registrar ocupación');
            }
            
            // Actualizar estado de la habitación a 'ocupada'
            $conn = getConnection();
            $sql_update = "UPDATE habitaciones SET estado = 'ocupada' WHERE id = :habitacion_id";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->execute([':habitacion_id' => $habitacion['id']]);
            
            // Registrar ingreso automáticamente
            require_once __DIR__ . '/../../models/Finanzas.php';
            $finanzasModel = new Finanzas();
            
            $monto_total = $habitacion['precio_dia'] * $nro_dias;
            
            // Aplicar descuento si existe
            $descuento = 0;
            $motivo_descuento = '';
            if (isset($_POST['descuento']) && !empty($_POST['descuento'])) {
                $descuento = floatval($_POST['descuento']);
                $monto_total = $monto_total - $descuento;
                $motivo_descuento = isset($_POST['motivo_descuento']) && !empty($_POST['motivo_descuento']) 
                    ? clean_input($_POST['motivo_descuento']) 
                    : 'Descuento aplicado';
            }
            
            $metodo_pago = isset($_POST['metodo_pago']) ? $_POST['metodo_pago'] : 'efectivo';
            
            $concepto = 'Pago habitación ' . $_POST['nro_pieza'] . ' - ' . $nro_dias . ' día(s)';
            if ($descuento > 0) {
                $concepto .= ' (Descuento: Bs. ' . number_format($descuento, 2) . ' - ' . $motivo_descuento . ')';
            }
            
            $datos_ingreso = [
                'ocupacion_id' => $ocupacion_id,
                'concepto' => $concepto,
                'monto' => $monto_total,
                'metodo_pago' => $metodo_pago,
                'fecha' => $fecha_ingreso,
                'observaciones' => 'Ingreso automático por registro de huésped'
            ];
            
            $finanzasModel->registrarIngreso($datos_ingreso);
            
            // Si es pago QR
            if ($metodo_pago === 'qr') {
                $datos_qr = [
                    'ocupacion_id' => $ocupacion_id,
                    'monto' => $monto_total,
                    'fecha' => $fecha_ingreso,
                    'numero_transaccion' => isset($_POST['numero_transaccion']) ? clean_input($_POST['numero_transaccion']) : null,
                    'observaciones' => 'Pago QR por habitación ' . $_POST['nro_pieza']
                ];
                $finanzasModel->registrarPagoQR($datos_qr);
            }
            
            // Registrar uso de garaje si fue seleccionado
            if (isset($_POST['usa_garaje']) && $_POST['usa_garaje'] == '1') {
                require_once __DIR__ . '/../../models/Garaje.php';
                $garajeModel = new Garaje();
                
                $datos_garaje = [
                    'ocupacion_id' => $ocupacion_id,
                    'huesped_nombre' => $_POST['nombres_apellidos'],
                    'placa' => isset($_POST['garaje_placa']) ? strtoupper(clean_input($_POST['garaje_placa'])) : null,
                    'tipo_vehiculo' => isset($_POST['garaje_tipo_vehiculo']) ? clean_input($_POST['garaje_tipo_vehiculo']) : null,
                    'fecha' => $fecha_ingreso,
                    'costo' => 10.00,
                    'observaciones' => 'Habitación ' . $_POST['nro_pieza']
                ];
                
                $garajeModel->registrar($datos_garaje);
            }
            
            // Registrar acompañantes si existen
            if (isset($_POST['acomp_ci']) && is_array($_POST['acomp_ci']) && count($_POST['acomp_ci']) > 0) {
                $acomp_count = 0;
                for ($i = 0; $i < count($_POST['acomp_ci']); $i++) {
                    if (!empty($_POST['acomp_ci'][$i]) && !empty($_POST['acomp_nombres'][$i])) {
                        $acomp_existente = $huespedModel->buscarPorCI($_POST['acomp_ci'][$i]);
                        
                        if ($acomp_existente) {
                            $acomp_huesped_id = $acomp_existente['id'];
                        } else {
                            $datos_acomp = [
                                'nombres_apellidos' => clean_input($_POST['acomp_nombres'][$i]),
                                'genero' => $_POST['acomp_genero'][$i],
                                'edad' => (int)$_POST['acomp_edad'][$i],
                                'estado_civil' => isset($_POST['acomp_estado_civil'][$i]) ? clean_input($_POST['acomp_estado_civil'][$i]) : null,
                                'nacionalidad' => clean_input($_POST['acomp_nacionalidad'][$i]),
                                'ci_pasaporte' => clean_input($_POST['acomp_ci'][$i]),
                                'profesion' => isset($_POST['acomp_profesion'][$i]) ? clean_input($_POST['acomp_profesion'][$i]) : null,
                                'objeto' => isset($_POST['acomp_objeto'][$i]) ? clean_input($_POST['acomp_objeto'][$i]) : null,
                                'procedencia' => isset($_POST['acomp_procedencia'][$i]) ? clean_input($_POST['acomp_procedencia'][$i]) : null
                            ];
                            
                            $acomp_huesped_id = $huespedModel->crear($datos_acomp);
                        }
                        
                        if ($acomp_huesped_id) {
                            $datos_ocupacion_acomp = [
                                'huesped_id' => $acomp_huesped_id,
                                'habitacion_id' => $habitacion['id'],
                                'nro_pieza' => clean_input($_POST['nro_pieza']),
                                'prox_destino' => !empty($_POST['prox_destino']) ? clean_input($_POST['prox_destino']) : null,
                                'via_ingreso' => !empty($_POST['via_ingreso']) ? clean_input($_POST['via_ingreso']) : null,
                                'fecha_ingreso' => $fecha_ingreso,
                                'nro_dias' => $nro_dias,
                                'fecha_salida_estimada' => $fecha_salida
                            ];
                            
                            $registroModel->crear($datos_ocupacion_acomp);
                            $acomp_count++;
                        }
                    }
                }
                
                if ($acomp_count > 0) {
                    $mensaje .= " Se registraron {$acomp_count} acompañante(s). ";
                }
            }
            
            $metodo_pago_texto = $metodo_pago === 'qr' ? 'QR' : ($metodo_pago === 'pendiente' ? 'Pendiente' : 'Efectivo');
            $mensaje .= 'Huésped y ocupación registrados correctamente. Total: Bs. ' . number_format($monto_total, 2) . ' (' . $metodo_pago_texto . ')';
            $tipo_mensaje = 'success';
            
            $_POST = [];
            
            // Redirigir usando PRG para evitar doble envío al refrescar
            header("Location: " . BASE_PATH . "/views/huespedes/activos.php?msg=" . urlencode($mensaje) . "&type=success");
            exit;
        }
        
    } catch (PDOException $e) {
        $mensaje = 'Error de base de datos: ' . $e->getMessage();
        $tipo_mensaje = 'danger';
    } catch (Exception $e) {
        $mensaje = 'Error: ' . $e->getMessage();
        $tipo_mensaje = 'danger';
    }
}

// Obtener habitaciones disponibles
$habitacionModel = new Habitacion();
$habitaciones = $habitacionModel->obtenerDisponibles();

include __DIR__ . '/../../includes/header.php';
?>

<style>
/* Estilos premium Apple-style */
:root {
    --apple-font: -apple-system, BlinkMacSystemFont, "SF Pro Text", "SF Pro Icons", "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
}

body {
    background-color: #f5f5f7;
    font-family: var(--apple-font);
    color: #1d1d1f;
}

.apple-card {
    background: #ffffff;
    border: 1px solid rgba(0, 0, 0, 0.05);
    border-radius: 24px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.015);
    padding: 30px;
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.apple-card:hover {
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.03);
}

.dark .apple-card {
    background: #1c1c1e;
    border-color: rgba(255, 255, 255, 0.06);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
}

.apple-card-title {
    font-size: 17px;
    font-weight: 700;
    letter-spacing: -0.02em;
    color: #1d1d1f;
}

.dark .apple-card-title {
    color: #ffffff;
}

/* inputs & select */
.apple-input {
    width: 100%;
    background: rgba(0, 0, 0, 0.02);
    border: 1px solid rgba(0, 0, 0, 0.08);
    border-radius: 12px;
    padding: 12px 16px;
    font-size: 14.5px;
    transition: all 0.2s ease;
    color: #1d1d1f;
}

.apple-input:focus {
    outline: none;
    border-color: #0071e3;
    background: #ffffff;
    box-shadow: 0 0 0 4px rgba(0, 113, 227, 0.12);
}

.apple-input:disabled, .apple-input[readonly] {
    background: rgba(0, 0, 0, 0.04);
    color: #86868b;
    cursor: not-allowed;
    border-color: transparent;
}

.dark .apple-input {
    background: rgba(255, 255, 255, 0.03);
    border-color: rgba(255, 255, 255, 0.1);
    color: #f5f5f7;
}

.dark .apple-input:focus {
    background: #1c1c1e;
    border-color: #0071e3;
    box-shadow: 0 0 0 4px rgba(0, 113, 227, 0.25);
}

.dark .apple-input:disabled, .dark .apple-input[readonly] {
    background: rgba(255, 255, 255, 0.02);
    color: #aeaeb2;
}

/* Elegant Segment Controls for Method of Payment */
.segment-container {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    background: rgba(0, 0, 0, 0.04);
    border-radius: 14px;
    padding: 4px;
    border: 1px solid rgba(0, 0, 0, 0.01);
}

.dark .segment-container {
    background: rgba(255, 255, 255, 0.04);
}

.segment-item {
    cursor: pointer;
    text-align: center;
    padding: 10px;
    border-radius: 10px;
    font-size: 13.5px;
    font-weight: 600;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    color: #86868b;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.segment-item.active {
    background: #ffffff;
    color: #1d1d1f;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

.dark .segment-item.active {
    background: #2c2c2e;
    color: #ffffff;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
}

/* Switch Styles */
.switch-label {
    display: inline-flex;
    align-items: center;
    cursor: pointer;
    user-select: none;
}

.switch-track {
    position: relative;
    width: 46px;
    height: 26px;
    background-color: #e5e5ea;
    border-radius: 9999px;
    transition: background-color 0.2s ease;
    margin-right: 12px;
}

.dark .switch-track {
    background-color: #3a3a3c;
}

.switch-thumb {
    position: absolute;
    top: 2px;
    left: 2px;
    width: 22px;
    height: 22px;
    background-color: #ffffff;
    border-radius: 50%;
    box-shadow: 0 1px 3px rgba(0,0,0,0.15);
    transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

input[type="checkbox"]:checked + .switch-track {
    background-color: #34c759;
}

input[type="checkbox"]:checked + .switch-track .switch-thumb {
    transform: translateX(20px);
}
</style>

<div class="container mx-auto px-4 py-8 max-w-4xl">
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight text-gray-900 dark:text-white mb-2">Nuevo Registro</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">Complete los datos de la estadía y asigne la habitación en el sistema</p>
            </div>
            <a href="<?php echo BASE_PATH; ?>/views/huespedes/activos.php" 
               class="px-4 py-2.5 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl border border-gray-200 dark:border-gray-700 transition font-medium text-sm text-center shadow-sm">
                Cancelar
            </a>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if ($mensaje): ?>
        <div class="mb-6 p-4 rounded-xl border <?php echo $tipo_mensaje === 'success' ? 'bg-green-50/80 dark:bg-green-950/20 text-green-800 dark:text-green-300 border-green-100 dark:border-green-900/30' : 'bg-red-50/80 dark:bg-red-950/20 text-red-800 dark:text-red-300 border-red-100 dark:border-red-900/30'; ?>">
            <span class="text-sm font-semibold"><?php echo $mensaje; ?></span>
        </div>
    <?php endif; ?>

    <!-- Main Form -->
    <form method="POST" action="" class="space-y-6">
        
        <!-- SECCIÓN 1: HABITACIÓN Y ESTADÍA (CHOOSE ROOM FIRST!) -->
        <div class="apple-card">
            <div class="apple-card-header pb-4 mb-5 border-b border-gray-100 dark:border-gray-800 flex items-center gap-3">
                <h2 class="apple-card-title flex items-center gap-2">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                    1. Habitación y Estadía
                </h2>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <!-- Habitación select -->
                <div class="space-y-2">
                    <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">
                        Habitación disponible <span class="text-red-500">*</span>
                    </label>
                    <select 
                        name="nro_pieza" 
                        id="nro_pieza"
                        onchange="actualizarCapacidadHabitacion(); calcularFechaSalida();"
                        required
                        class="apple-input bg-white appearance-none cursor-pointer"
                    >
                        <option value="">Seleccione una habitación</option>
                        <?php foreach ($habitaciones as $hab): ?>
                            <option value="<?php echo $hab['numero']; ?>" 
                                    data-tipo="<?php echo $hab['tipo']; ?>" 
                                    data-capacidad="<?php 
                                        $tipo = $hab['tipo'];
                                        if ($tipo == 'Individual') echo '1';
                                        elseif ($tipo == 'Doble' || $tipo == 'Matrimonial') echo '2';
                                        elseif ($tipo == 'Triple') echo '3';
                                        elseif ($tipo == 'Familiar' || $tipo == 'Suite') echo '4';
                                        else echo '2';
                                    ?>"
                                    data-precio="<?php echo $hab['precio_dia']; ?>"
                                    <?php echo (isset($_POST['nro_pieza']) && $_POST['nro_pieza'] == $hab['numero']) ? 'selected' : ''; ?>>
                                Habitación <?php echo $hab['numero']; ?> (<?php echo $hab['tipo']; ?>) — Bs. <?php echo number_format($hab['precio_dia'], 2); ?>/día
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div id="mensaje_capacidad" class="mt-2 text-xs font-semibold text-blue-600 dark:text-blue-400 hidden"></div>
                </div>

                <!-- Próximo Destino -->
                <div class="space-y-2">
                    <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Próximo Destino <span class="text-red-500">*</span></label>
                    <div class="grid grid-cols-1 gap-2">
                        <select 
                            id="prox_destino_select"
                            onchange="toggleProxDestinoCustom()"
                            required
                            class="apple-input bg-white appearance-none cursor-pointer"
                        >
                            <option value="">Seleccione destino</option>
                            <option value="La Paz">La Paz</option>
                            <option value="Santa Cruz">Santa Cruz</option>
                            <option value="Cochabamba">Cochabamba</option>
                            <option value="Oruro">Oruro</option>
                            <option value="Potosí">Potosí</option>
                            <option value="Chuquisaca">Chuquisaca</option>
                            <option value="Tarija">Tarija</option>
                            <option value="Beni">Beni</option>
                            <option value="Pando">Pando</option>
                            <option value="otro">Otro lugar...</option>
                        </select>
                        <input 
                            type="text" 
                            id="prox_destino"
                            name="prox_destino"
                            value="<?php echo isset($_POST['prox_destino']) ? htmlspecialchars($_POST['prox_destino']) : ''; ?>"
                            class="hidden apple-input"
                            placeholder="Escriba la ciudad o país de destino"
                        >
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mt-5">
                <!-- Vía de Ingreso -->
                <div class="space-y-2">
                    <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Vía de Ingreso <span class="text-red-500">*</span></label>
                    <select 
                        name="via_ingreso"
                        required
                        class="apple-input bg-white appearance-none cursor-pointer"
                    >
                        <option value="">Seleccione</option>
                        <option value="T" <?php echo (isset($_POST['via_ingreso']) && $_POST['via_ingreso'] == 'T') ? 'selected' : ''; ?>>Terrestre</option>
                        <option value="A" <?php echo (isset($_POST['via_ingreso']) && $_POST['via_ingreso'] == 'A') ? 'selected' : ''; ?>>Aéreo</option>
                    </select>
                </div>
                
                <!-- Fecha Ingreso -->
                <div class="space-y-2">
                    <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">
                        Fecha Ingreso <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="date" 
                        id="fecha_ingreso"
                        name="fecha_ingreso" 
                        value="<?php echo isset($_POST['fecha_ingreso']) ? htmlspecialchars($_POST['fecha_ingreso']) : ''; ?>"
                        onchange="calcularFechaSalida()"
                        required
                        class="apple-input"
                    >
                </div>
                
                <!-- Número de Días -->
                <div class="space-y-2">
                    <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">
                        Número de Días <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="number" 
                        id="nro_dias"
                        name="nro_dias" 
                        value="<?php echo isset($_POST['nro_dias']) ? htmlspecialchars($_POST['nro_dias']) : ''; ?>"
                        onchange="calcularFechaSalida()"
                        min="1"
                        required
                        class="apple-input"
                        placeholder="Días"
                    >
                </div>
            </div>
        </div>

        <!-- SECCIÓN 2: INFORMACIÓN DEL HUÉSPED TITULAR -->
        <div class="apple-card">
            <div class="apple-card-header pb-4 mb-5 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                <h2 class="apple-card-title flex items-center gap-2">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    2. Información del Huésped Titular
                </h2>
                <!-- Indicador de autocompletado en header -->
                <div id="busqueda_indicador" class="hidden flex items-center gap-2 text-xs font-semibold text-blue-600">
                    <div class="w-4 h-4 border-2 border-blue-200 border-t-blue-600 rounded-full animate-spin"></div>
                    Buscando...
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <!-- CI o Pasaporte -->
                <div class="space-y-2">
                    <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">
                        CI o Pasaporte <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="ci_pasaporte"
                        name="ci_pasaporte" 
                        onblur="buscarHuespedPorCI()"
                        value="<?php echo isset($_POST['ci_pasaporte']) ? htmlspecialchars($_POST['ci_pasaporte']) : ''; ?>"
                        required
                        class="apple-input font-semibold tracking-wide"
                        placeholder="Número de documento"
                    >
                    <div id="busqueda_mensaje" class="text-xs font-semibold mt-1"></div>
                </div>
                
                <!-- Nombres y Apellidos -->
                <div class="space-y-2 md:col-span-2">
                    <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">
                        Nombres y Apellidos <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="nombres_apellidos"
                        name="nombres_apellidos" 
                        value="<?php echo isset($_POST['nombres_apellidos']) ? htmlspecialchars($_POST['nombres_apellidos']) : ''; ?>"
                        required
                        class="apple-input"
                        placeholder="Nombre completo"
                    >
                </div>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-5 mt-5">
                <!-- Género -->
                <div class="space-y-2">
                    <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">
                        Género <span class="text-red-500">*</span>
                    </label>
                    <select 
                        id="genero"
                        name="genero" 
                        required
                        class="apple-input bg-white appearance-none cursor-pointer"
                    >
                        <option value="">Seleccione</option>
                        <option value="M" <?php echo (isset($_POST['genero']) && $_POST['genero'] == 'M') ? 'selected' : ''; ?>>Masculino</option>
                        <option value="F" <?php echo (isset($_POST['genero']) && $_POST['genero'] == 'F') ? 'selected' : ''; ?>>Femenino</option>
                    </select>
                </div>
                
                <!-- Fecha Nacimiento -->
                <div class="space-y-2">
                    <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">
                        F. Nacimiento <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="date" 
                        id="fecha_nacimiento"
                        onchange="calcularEdad()"
                        required
                        class="apple-input"
                    >
                </div>
                
                <!-- Edad (Calculada) -->
                <div class="space-y-2">
                    <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">
                        Edad <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="number" 
                        id="edad"
                        name="edad" 
                        value="<?php echo isset($_POST['edad']) ? htmlspecialchars($_POST['edad']) : ''; ?>"
                        required
                        readonly
                        min="1"
                        class="apple-input font-bold"
                        placeholder="0"
                    >
                </div>
                
                <!-- Estado Civil -->
                <div class="space-y-2">
                    <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">E. Civil</label>
                    <select 
                        id="estado_civil"
                        name="estado_civil"
                        class="apple-input bg-white appearance-none cursor-pointer"
                    >
                        <option value="">Seleccione</option>
                        <option value="S" <?php echo (isset($_POST['estado_civil']) && $_POST['estado_civil'] == 'S') ? 'selected' : ''; ?>>Soltero(a)</option>
                        <option value="C" <?php echo (isset($_POST['estado_civil']) && $_POST['estado_civil'] == 'C') ? 'selected' : ''; ?>>Casado(a)</option>
                        <option value="D" <?php echo (isset($_POST['estado_civil']) && $_POST['estado_civil'] == 'D') ? 'selected' : ''; ?>>Divorciado(a)</option>
                        <option value="V" <?php echo (isset($_POST['estado_civil']) && $_POST['estado_civil'] == 'V') ? 'selected' : ''; ?>>Viudo(a)</option>
                    </select>
                </div>
                
                <!-- Nacionalidad -->
                <div class="space-y-2">
                    <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">
                        Nacionalidad <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="nacionalidad"
                        name="nacionalidad" 
                        required
                        value="<?php echo isset($_POST['nacionalidad']) ? htmlspecialchars($_POST['nacionalidad']) : 'Boliviano'; ?>"
                        class="apple-input"
                        placeholder="Nacionalidad"
                    >
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mt-5">
                <!-- Profesión -->
                <div class="space-y-2">
                    <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Profesión</label>
                    <input 
                        type="text" 
                        id="profesion"
                        name="profesion"
                        value="<?php echo isset($_POST['profesion']) ? htmlspecialchars($_POST['profesion']) : ''; ?>"
                        class="apple-input"
                        placeholder="Ocupación"
                    >
                </div>
                
                <!-- Objeto del Viaje -->
                <div class="space-y-2">
                    <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Objeto del Viaje</label>
                    <select 
                        id="objeto"
                        name="objeto"
                        class="apple-input bg-white appearance-none cursor-pointer"
                    >
                        <option value="">Seleccione</option>
                        <option value="Turismo" <?php echo (isset($_POST['objeto']) && $_POST['objeto'] == 'Turismo') ? 'selected' : ''; ?>>Turismo</option>
                        <option value="Negocios" <?php echo (isset($_POST['objeto']) && $_POST['objeto'] == 'Negocios') ? 'selected' : ''; ?>>Negocios</option>
                        <option value="Salud" <?php echo (isset($_POST['objeto']) && $_POST['objeto'] == 'Salud') ? 'selected' : ''; ?>>Salud</option>
                        <option value="Educación" <?php echo (isset($_POST['objeto']) && $_POST['objeto'] == 'Educación') ? 'selected' : ''; ?>>Educación</option>
                        <option value="Familiar" <?php echo (isset($_POST['objeto']) && $_POST['objeto'] == 'Familiar') ? 'selected' : ''; ?>>Familiar</option>
                        <option value="Tránsito" <?php echo (isset($_POST['objeto']) && $_POST['objeto'] == 'Tránsito') ? 'selected' : ''; ?>>Tránsito</option>
                        <option value="Paso" <?php echo (isset($_POST['objeto']) && $_POST['objeto'] == 'Paso') ? 'selected' : ''; ?>>Paso</option>
                        <option value="Otro" <?php echo (isset($_POST['objeto']) && $_POST['objeto'] == 'Otro') ? 'selected' : ''; ?>>Otro</option>
                    </select>
                </div>
                
                <!-- Procedencia -->
                <div class="space-y-2">
                    <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Procedencia</label>
                    <div class="grid grid-cols-1 gap-2">
                        <select 
                            id="procedencia_select"
                            onchange="toggleProcedenciaCustom()"
                            class="apple-input bg-white appearance-none cursor-pointer"
                        >
                            <option value="">Seleccione procedencia</option>
                            <option value="La Paz">La Paz</option>
                            <option value="Santa Cruz">Santa Cruz</option>
                            <option value="Cochabamba">Cochabamba</option>
                            <option value="Oruro">Oruro</option>
                            <option value="Potosí">Potosí</option>
                            <option value="Chuquisaca">Chuquisaca</option>
                            <option value="Tarija">Tarija</option>
                            <option value="Beni">Beni</option>
                            <option value="Pando">Pando</option>
                            <option value="otro">Otro país...</option>
                        </select>
                        <input 
                            type="text" 
                            id="procedencia"
                            name="procedencia"
                            value="<?php echo isset($_POST['procedencia']) ? htmlspecialchars($_POST['procedencia']) : ''; ?>"
                            class="hidden apple-input"
                            placeholder="Escriba el lugar de procedencia"
                        >
                    </div>
                </div>
            </div>
        </div>

        <!-- SECCIÓN 3: ACOMPAÑANTES -->
        <div class="apple-card">
            <div class="apple-card-header pb-4 mb-5 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                <div>
                    <h2 class="apple-card-title flex items-center gap-2">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        3. Acompañantes en la Habitación
                    </h2>
                </div>
                <button 
                    type="button" 
                    id="btn_agregar_acompanante"
                    onclick="agregarAcompanante()" 
                    disabled
                    class="px-4 py-2.5 bg-gray-300 text-gray-600 dark:bg-gray-800 dark:text-gray-400 font-semibold rounded-xl text-xs transition duration-200 cursor-not-allowed"
                >
                    + Agregar Acompañante
                </button>
            </div>
            
            <div class="bg-gray-50/50 dark:bg-gray-900/10 rounded-2xl p-4 min-h-[80px]" id="lista_acompanantes">
                <div class="text-center text-gray-400 py-6" id="mensaje_sin_acompanantes">
                    <p class="text-sm font-medium">No hay acompañantes agregados</p>
                    <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1">El botón se habilitará automáticamente al elegir una habitación múltiple</p>
                </div>
            </div>
        </div>

        <!-- SECCIÓN 4: LIQUIDACIÓN Y PAGO -->
        <div class="apple-card">
            <div class="apple-card-header pb-4 mb-5 border-b border-gray-100 dark:border-gray-800 flex items-center gap-3">
                <h2 class="apple-card-title flex items-center gap-2">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                    4. Liquidación y Pago
                </h2>
            </div>

            <div class="space-y-6">
                <!-- Método de Pago Segmentado -->
                <div class="space-y-2">
                    <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">
                        Método de Pago <span class="text-red-500">*</span>
                    </label>
                    <div class="segment-container">
                        <label class="segment-item active" id="label_efectivo">
                            <input type="radio" name="metodo_pago" value="efectivo" checked class="hidden" onchange="cambiarMetodoPago('efectivo')">
                            <span>Efectivo</span>
                        </label>
                        <label class="segment-item" id="label_qr">
                            <input type="radio" name="metodo_pago" value="qr" class="hidden" onchange="cambiarMetodoPago('qr')">
                            <span>Pago QR</span>
                        </label>
                        <label class="segment-item" id="label_pendiente">
                            <input type="radio" name="metodo_pago" value="pendiente" class="hidden" onchange="cambiarMetodoPago('pendiente')">
                            <span>Pendiente</span>
                        </label>
                    </div>
                </div>

                <!-- Contenedor QR -->
                <div id="qr_imagen_container" style="display: none;" class="p-6 bg-purple-50/30 dark:bg-purple-950/10 border border-purple-100 dark:border-purple-900/30 rounded-2xl text-center space-y-4">
                    <p class="text-sm font-semibold text-purple-700 dark:text-purple-400">Escanee el código QR para realizar la transferencia:</p>
                    <img src="<?php echo BASE_PATH; ?>/assets/img/QR.jpeg" alt="Código QR" class="mx-auto max-w-[200px] rounded-2xl shadow-md border border-white">
                    <div class="text-left space-y-2 max-w-sm mx-auto">
                        <label class="block text-xs font-bold text-purple-950 dark:text-purple-300 uppercase tracking-wider">Número de transacción</label>
                        <input type="text" id="numero_transaccion" name="numero_transaccion" class="apple-input bg-white" placeholder="Ej. 12345678">
                    </div>
                </div>

                <!-- Contenedor Pendiente -->
                <div id="pendiente_aviso_container" style="display: none;" class="p-5 bg-orange-50/50 dark:bg-orange-950/10 border border-orange-100 dark:border-orange-900/30 rounded-2xl">
                    <p class="text-sm font-bold text-orange-800 dark:text-orange-400 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        Pago Pendiente de Registro
                    </p>
                    <p class="text-xs text-orange-600 dark:text-orange-500/80 mt-1 leading-relaxed">
                        El huésped se registrará en la habitación pero la transacción quedará pendiente en finanzas. Podrá conciliarla más tarde desde el apartado de Pagos Pendientes.
                    </p>
                </div>

                <!-- Garaje checkbox premium (Apple Switch) -->
                <div class="p-5 bg-gray-50 dark:bg-gray-900/40 rounded-2xl border border-gray-100 dark:border-gray-800 flex flex-col gap-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <label class="text-sm font-bold text-gray-800 dark:text-gray-200">Uso de Garaje</label>
                            <p class="text-xs text-gray-400 dark:text-gray-500">¿El huésped ingresará con vehículo? (Bs. 10.00 / día)</p>
                        </div>
                        <label class="switch-label">
                            <input type="checkbox" id="usa_garaje" name="usa_garaje" value="1" onchange="toggleGarajeDetalles()" class="hidden">
                            <span class="switch-track">
                                <span class="switch-thumb"></span>
                            </span>
                        </label>
                    </div>

                    <!-- Datos Garaje expandibles -->
                    <div id="garaje_detalles" style="display: none;" class="pt-4 border-t border-gray-200/50 dark:border-gray-800/80">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Número de Placa <span class="text-red-500">*</span></label>
                                <input type="text" id="garaje_placa" name="garaje_placa" class="apple-input bg-white uppercase font-bold" placeholder="Ej. 1234ABC">
                            </div>
                            <div class="space-y-2">
                                <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Tipo de Vehículo <span class="text-red-500">*</span></label>
                                <select id="garaje_tipo_vehiculo" name="garaje_tipo_vehiculo" class="apple-input bg-white appearance-none cursor-pointer">
                                    <option value="">Seleccione tipo</option>
                                    <option value="Automóvil">Automóvil</option>
                                    <option value="Camioneta">Camioneta</option>
                                    <option value="Vagoneta">Vagoneta</option>
                                    <option value="Minibús">Minibús</option>
                                    <option value="Motocicleta">Motocicleta</option>
                                    <option value="Otro">Otro</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detalle y Salida Estimada -->
                <div class="bg-gray-50/50 dark:bg-gray-900/10 border border-gray-150 dark:border-gray-800 rounded-2xl p-5 space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                        <div>
                            <span class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Salida Estimada</span>
                            <span class="text-xs text-gray-400 dark:text-gray-500 leading-snug">Se calcula automáticamente según la duración</span>
                        </div>
                        <input type="date" id="fecha_salida_estimada" readonly class="apple-input sm:w-auto font-bold text-right bg-white dark:bg-gray-800">
                    </div>
                    <div id="mensaje_salida" class="text-xs font-semibold text-blue-700 bg-blue-50/50 dark:text-blue-400 dark:bg-blue-950/10 p-3 rounded-xl border border-blue-100/50 dark:border-blue-900/20">
                        Complete la fecha de ingreso y días en el paso 1.
                    </div>

                    <!-- Precio Total Dashboard -->
                    <div id="precio_total_container" class="hidden pt-2">
                        <div class="bg-gray-900 dark:bg-gray-100 text-white dark:text-gray-900 rounded-2xl p-5 flex items-center justify-between">
                            <div>
                                <span class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Total a cobrar</span>
                                <span class="text-[10px] opacity-80" id="detalle_precio">Bs. 0.00 × Días</span>
                            </div>
                            <div class="text-right">
                                <span class="text-2xl font-extrabold tracking-tight" id="precio_total_monto">Bs. 0.00</span>
                            </div>
                        </div>
                    </div>

                    <!-- Descuentos premium -->
                    <div id="descuento_container" class="hidden p-4 bg-orange-50/50 dark:bg-orange-950/10 border border-orange-100 dark:border-orange-900/20 rounded-xl space-y-3">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-orange-850 dark:text-orange-400 uppercase tracking-wider">Aplicar Descuento (Opcional)</span>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <input type="number" id="descuento" name="descuento" min="0" step="0.01" oninput="calcularFechaSalida()" class="apple-input bg-white" placeholder="Monto Bs. (ej. 10.00)">
                            <input type="text" id="motivo_descuento" name="motivo_descuento" class="apple-input bg-white" placeholder="Motivo (ej. Sin desayuno)">
                        </div>
                        <div id="precio_con_descuento" class="hidden pt-2 border-t border-orange-200/50 dark:border-orange-900/20 flex items-center justify-between">
                            <span class="text-xs font-bold text-orange-900 dark:text-orange-400">Total con descuento:</span>
                            <span class="text-lg font-extrabold text-orange-900 dark:text-orange-400" id="precio_final">Bs. 0.00</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center justify-end gap-3 pt-4">
            <a href="<?php echo BASE_PATH; ?>/views/huespedes/activos.php" 
               class="px-5 py-3 border border-gray-250 dark:border-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800 font-medium transition text-sm">
                Cancelar
            </a>
            <button 
                type="submit" 
                class="px-6 py-3 bg-gray-900 hover:bg-gray-800 dark:bg-gray-100 dark:hover:bg-white text-white dark:text-gray-900 font-bold rounded-xl transition text-sm shadow-sm"
            >
                Confirmar y Registrar Huésped
            </button>
        </div>
    </form>
</div>

<script>
let contadorAcompanantes = 0;
let capacidadHabitacion = 0;
let tipoHabitacion = '';

// Eliminar acompañante
function eliminarAcompanante(id) {
    const elemento = document.getElementById(`acompanante_${id}`);
    if (elemento) {
        elemento.remove();
        
        const restantes = document.querySelectorAll('[id^="acompanante_"]').length;
        if (restantes === 0) {
            document.getElementById('mensaje_sin_acompanantes').style.display = 'block';
        }
        
        actualizarBotonAcompanante();
    }
}

// Actualizar capacidad de habitación seleccionada
function actualizarCapacidadHabitacion() {
    const select = document.getElementById('nro_pieza');
    const op = select.options[select.selectedIndex];
    
    if (!op.value) {
        capacidadHabitacion = 0;
        tipoHabitacion = '';
        document.getElementById('mensaje_capacidad').classList.add('hidden');
        
        const btn = document.getElementById('btn_agregar_acompanante');
        btn.disabled = true;
        btn.className = 'px-4 py-2.5 bg-gray-300 text-gray-500 dark:bg-gray-800 dark:text-gray-500 font-semibold rounded-xl text-xs transition duration-200 cursor-not-allowed';
        btn.innerHTML = '+ Agregar Acompañante';
        return;
    }
    
    capacidadHabitacion = parseInt(op.dataset.capacidad);
    tipoHabitacion = op.dataset.tipo;
    
    const msgCap = document.getElementById('mensaje_capacidad');
    const pl = capacidadHabitacion === 1 ? 'persona' : 'personas';
    msgCap.textContent = `Capacidad máxima de esta habitación: ${capacidadHabitacion} ${pl} (${tipoHabitacion})`;
    msgCap.classList.remove('hidden');
    
    actualizarBotonAcompanante();
    verificarCapacidadExcedida();
}

// Actualizar estado del botón de agregar acompañante
function actualizarBotonAcompanante() {
    const btn = document.getElementById('btn_agregar_acompanante');
    const numAcomp = document.querySelectorAll('[id^="acompanante_"]').length;
    const total = 1 + numAcomp;
    
    if (capacidadHabitacion === 0) {
        btn.disabled = true;
        btn.className = 'px-4 py-2.5 bg-gray-300 text-gray-500 dark:bg-gray-800 dark:text-gray-500 font-semibold rounded-xl text-xs transition duration-200 cursor-not-allowed';
        btn.innerHTML = '+ Agregar Acompañante';
    } else if (total >= capacidadHabitacion) {
        btn.disabled = true;
        btn.className = 'px-4 py-2.5 bg-red-100 dark:bg-red-950/20 text-red-600 dark:text-red-400 font-semibold rounded-xl text-xs transition duration-200 cursor-not-allowed border border-red-200/50';
        btn.innerHTML = `Límite alcanzado (${total}/${capacidadHabitacion})`;
    } else {
        btn.disabled = false;
        btn.className = 'px-4 py-2.5 bg-gray-900 hover:bg-gray-800 dark:bg-gray-100 dark:hover:bg-white text-white dark:text-gray-900 font-semibold rounded-xl text-xs transition duration-200 cursor-pointer shadow-sm';
        btn.innerHTML = `+ Agregar Acompañante (${total}/${capacidadHabitacion})`;
    }
}

// Alertas de exceso de capacidad
function verificarCapacidadExcedida() {
    const numAcomp = document.querySelectorAll('[id^="acompanante_"]').length;
    const total = 1 + numAcomp;
    
    if (total > capacidadHabitacion && capacidadHabitacion > 0) {
        const exceso = total - capacidadHabitacion;
        alert(`La habitación seleccionada (${tipoHabitacion}) tiene una capacidad de ${capacidadHabitacion} personas.\n\nActualmente tienes registradas ${total} personas. Por favor, remueve ${exceso} acompañante(s) o cambia a otra habitación.`);
    }
}

// Agregar acompañante
function agregarAcompanante() {
    const numAcomp = document.querySelectorAll('[id^="acompanante_"]').length;
    const total = 1 + numAcomp;
    
    if (total >= capacidadHabitacion) {
        alert(`La habitación ${tipoHabitacion} solo permite un máximo de ${capacidadHabitacion} personas.`);
        return;
    }
    
    contadorAcompanantes++;
    const listCont = document.getElementById('lista_acompanantes');
    const msgSin = document.getElementById('mensaje_sin_acompanantes');
    
    if (numAcomp === 0) {
        msgSin.style.display = 'none';
    }
    
    const HTML = `
        <div class="apple-card mb-4 bg-white shadow-sm border border-gray-100 dark:border-gray-800 p-5 space-y-4" id="acompanante_${contadorAcompanantes}">
            <div class="flex items-center justify-between pb-3 border-b border-gray-150/50 dark:border-gray-800/80">
                <h3 class="text-sm font-bold text-gray-800 dark:text-gray-200 flex items-center gap-2">
                    <span class="w-6 h-6 bg-gray-900 dark:bg-gray-100 text-white dark:text-gray-900 rounded-full flex items-center justify-center text-xs font-extrabold">${contadorAcompanantes}</span>
                    Acompañante #${contadorAcompanantes}
                </h3>
                <button type="button" onclick="eliminarAcompanante(${contadorAcompanantes})" class="px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-600 dark:bg-red-950/20 dark:hover:bg-red-900/30 rounded-xl text-xs font-semibold border border-red-200/30">
                    ✕ Quitar
                </button>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="space-y-1">
                    <label class="block text-[10px] font-bold text-gray-400 uppercase">Documento / CI <span class="text-red-500">*</span></label>
                    <input type="text" name="acomp_ci[]" id="acomp_ci_${contadorAcompanantes}" onblur="buscarAcompanantePorCI(${contadorAcompanantes})" required class="apple-input bg-white" placeholder="Documento">
                </div>
                
                <div class="space-y-1 md:col-span-2">
                    <label class="block text-[10px] font-bold text-gray-400 uppercase">Nombres y Apellidos <span class="text-red-500">*</span></label>
                    <input type="text" name="acomp_nombres[]" id="acomp_nombres_${contadorAcompanantes}" required class="apple-input bg-white" placeholder="Nombre completo">
                </div>
            </div>
            
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="space-y-1">
                    <label class="block text-[10px] font-bold text-gray-400 uppercase">Género <span class="text-red-500">*</span></label>
                    <select name="acomp_genero[]" id="acomp_genero_${contadorAcompanantes}" required class="apple-input bg-white appearance-none cursor-pointer">
                        <option value="">Seleccione</option>
                        <option value="M">Masculino</option>
                        <option value="F">Femenino</option>
                    </select>
                </div>
                
                <div class="space-y-1">
                    <label class="block text-[10px] font-bold text-gray-400 uppercase">F. Nacimiento <span class="text-red-500">*</span></label>
                    <input type="date" id="acomp_fecha_nacimiento_${contadorAcompanantes}" onchange="calcularEdadAcompanante(${contadorAcompanantes})" required class="apple-input bg-white">
                </div>
                
                <div class="space-y-1">
                    <label class="block text-[10px] font-bold text-gray-400 uppercase">Edad <span class="text-red-500">*</span></label>
                    <input type="number" name="acomp_edad[]" id="acomp_edad_${contadorAcompanantes}" required readonly class="apple-input" placeholder="0">
                </div>

                <div class="space-y-1">
                    <label class="block text-[10px] font-bold text-gray-400 uppercase">E. Civil <span class="text-red-500">*</span></label>
                    <select name="acomp_estado_civil[]" id="acomp_estado_civil_${contadorAcompanantes}" required class="apple-input bg-white appearance-none cursor-pointer">
                        <option value="">Seleccione</option>
                        <option value="S">Soltero(a)</option>
                        <option value="C">Casado(a)</option>
                        <option value="D">Divorciado(a)</option>
                        <option value="V">Viudo(a)</option>
                    </select>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="space-y-1">
                    <label class="block text-[10px] font-bold text-gray-400 uppercase">Nacionalidad <span class="text-red-500">*</span></label>
                    <input type="text" name="acomp_nacionalidad[]" id="acomp_nacionalidad_${contadorAcompanantes}" required value="Boliviano" class="apple-input bg-white">
                </div>
                
                <div class="space-y-1">
                    <label class="block text-[10px] font-bold text-gray-400 uppercase">Profesión <span class="text-red-500">*</span></label>
                    <input type="text" name="acomp_profesion[]" id="acomp_profesion_${contadorAcompanantes}" required class="apple-input bg-white" placeholder="Ocupación">
                </div>
                
                <div class="space-y-1">
                    <label class="block text-[10px] font-bold text-gray-400 uppercase">Objeto del Viaje <span class="text-red-500">*</span></label>
                    <select name="acomp_objeto[]" id="acomp_objeto_${contadorAcompanantes}" required class="apple-input bg-white appearance-none cursor-pointer">
                        <option value="">Seleccione</option>
                        <option value="Turismo">Turismo</option>
                        <option value="Negocios">Negocios</option>
                        <option value="Salud">Salud</option>
                        <option value="Educación">Educación</option>
                        <option value="Familiar">Familiar</option>
                        <option value="Tránsito">Tránsito</option>
                        <option value="Paso">Paso</option>
                        <option value="Otro">Otro</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-2">
                <label class="block text-[10px] font-bold text-gray-400 uppercase">Procedencia <span class="text-red-500">*</span></label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <select 
                        id="acomp_procedencia_select_${contadorAcompanantes}"
                        onchange="toggleProcedenciaCustomAcomp(${contadorAcompanantes})"
                        class="apple-input bg-white appearance-none cursor-pointer"
                    >
                        <option value="">Seleccione procedencia</option>
                        <option value="La Paz">La Paz</option>
                        <option value="Santa Cruz">Santa Cruz</option>
                        <option value="Cochabamba">Cochabamba</option>
                        <option value="Oruro">Oruro</option>
                        <option value="Potosí">Potosí</option>
                        <option value="Chuquisaca">Chuquisaca</option>
                        <option value="Tarija">Tarija</option>
                        <option value="Beni">Beni</option>
                        <option value="Pando">Pando</option>
                        <option value="otro">Otro país...</option>
                    </select>
                    <input 
                        type="text" 
                        name="acomp_procedencia[]" 
                        id="acomp_procedencia_${contadorAcompanantes}"
                        required
                        class="hidden apple-input bg-white"
                        placeholder="Escriba la procedencia"
                    >
                </div>
            </div>
        </div>
    `;
    
    listCont.insertAdjacentHTML('beforeend', HTML);
    actualizarBotonAcompanante();
}

// Calcular edad del Titular
function calcularEdad() {
    const fn = document.getElementById('fecha_nacimiento').value;
    if (!fn) {
        document.getElementById('edad').value = '';
        return;
    }
    
    const hoy = new Date();
    const nac = new Date(fn);
    let ed = hoy.getFullYear() - nac.getFullYear();
    const m = hoy.getMonth() - nac.getMonth();
    
    if (m < 0 || (m === 0 && hoy.getDate() < nac.getDate())) {
        ed--;
    }
    document.getElementById('edad').value = ed;
}

// Calcular edad de Acompañante
function calcularEdadAcompanante(id) {
    const fn = document.getElementById('acomp_fecha_nacimiento_' + id).value;
    if (!fn) {
        document.getElementById('acomp_edad_' + id).value = '';
        return;
    }
    
    const hoy = new Date();
    const nac = new Date(fn);
    let ed = hoy.getFullYear() - nac.getFullYear();
    const m = hoy.getMonth() - nac.getMonth();
    
    if (m < 0 || (m === 0 && hoy.getDate() < nac.getDate())) {
        ed--;
    }
    document.getElementById('acomp_edad_' + id).value = ed;
}

// Toggles de procedencia personalizada
function toggleProcedenciaCustom() {
    const select = document.getElementById('procedencia_select');
    const input = document.getElementById('procedencia');
    
    if (select.value === 'otro') {
        input.classList.remove('hidden');
        input.required = true;
        input.value = '';
        input.focus();
    } else if (select.value) {
        input.classList.add('hidden');
        input.required = false;
        input.value = select.value;
    } else {
        input.classList.add('hidden');
        input.required = false;
        input.value = '';
    }
}

function toggleProxDestinoCustom() {
    const select = document.getElementById('prox_destino_select');
    const input = document.getElementById('prox_destino');
    
    if (select.value === 'otro') {
        input.classList.remove('hidden');
        input.required = true;
        input.value = '';
        input.focus();
    } else if (select.value) {
        input.classList.add('hidden');
        input.required = false;
        input.value = select.value;
    } else {
        input.classList.add('hidden');
        input.required = true;
        input.value = '';
    }
}

function toggleProcedenciaCustomAcomp(id) {
    const select = document.getElementById('acomp_procedencia_select_' + id);
    const input = document.getElementById('acomp_procedencia_' + id);
    
    if (select.value === 'otro') {
        input.classList.remove('hidden');
        input.required = true;
        input.value = '';
        input.focus();
    } else if (select.value) {
        input.classList.add('hidden');
        input.required = true;
        input.value = select.value;
    } else {
        input.classList.add('hidden');
        input.required = true;
        input.value = '';
    }
}

// Búsqueda inteligente de Huésped Titular (CI)
function buscarHuespedPorCI() {
    const ci = document.getElementById('ci_pasaporte').value.trim();
    if (!ci) return;
    
    const ind = document.getElementById('busqueda_indicador');
    const msg = document.getElementById('busqueda_mensaje');
    
    ind.classList.remove('hidden');
    msg.innerHTML = '';
    
    fetch('<?php echo BASE_PATH; ?>/controllers/buscar_huesped_ci.php?ci=' + encodeURIComponent(ci))
        .then(response => response.json())
        .then(data => {
            ind.classList.add('hidden');
            
            if (data.error) {
                msg.innerHTML = '<span class="text-red-650">⚠️ Error al buscar</span>';
                return;
            }
            
            if (data.encontrado && data.datos) {
                const d = data.datos;
                
                document.getElementById('nombres_apellidos').value = d.nombres_apellidos || '';
                document.getElementById('genero').value = d.genero || '';
                document.getElementById('estado_civil').value = d.estado_civil || '';
                document.getElementById('nacionalidad').value = d.nacionalidad || 'Boliviano';
                document.getElementById('profesion').value = d.profesion || '';
                document.getElementById('objeto').value = d.objeto || '';
                
                if (d.procedencia) {
                    const deptos = ['La Paz', 'Santa Cruz', 'Cochabamba', 'Oruro', 'Potosí', 'Chuquisaca', 'Tarija', 'Beni', 'Pando'];
                    const selectProc = document.getElementById('procedencia_select');
                    const inputProc = document.getElementById('procedencia');
                    
                    if (deptos.includes(d.procedencia)) {
                        selectProc.value = d.procedencia;
                        inputProc.value = d.procedencia;
                        inputProc.classList.add('hidden');
                    } else {
                        selectProc.value = 'otro';
                        inputProc.value = d.procedencia;
                        inputProc.classList.remove('hidden');
                    }
                }
                
                if (d.fecha_nacimiento) {
                    document.getElementById('fecha_nacimiento').value = d.fecha_nacimiento;
                    calcularEdad();
                } else if (d.edad) {
                    document.getElementById('edad').value = d.edad;
                }
                
                msg.innerHTML = '<span class="text-green-600 font-semibold animate-pulse">✓ Huésped encontrado — Datos completados</span>';
                setTimeout(() => { msg.innerHTML = ''; }, 3000);
            } else {
                msg.innerHTML = '<span class="text-gray-400">ℹ️ Huésped nuevo — Complete los campos</span>';
            }
        })
        .catch(err => {
            ind.classList.add('hidden');
            msg.innerHTML = '<span class="text-red-650">⚠️ Error de conexión</span>';
        });
}

// Búsqueda inteligente de Acompañante (CI)
function buscarAcompanantePorCI(id) {
    const ci = document.getElementById('acomp_ci_' + id).value.trim();
    if (!ci || ci.length < 3) return;
    
    fetch('<?php echo BASE_PATH; ?>/controllers/buscar_huesped_ci.php?ci=' + encodeURIComponent(ci))
        .then(response => response.json())
        .then(data => {
            if (data.encontrado && data.datos) {
                const h = data.datos;
                
                document.getElementById('acomp_nombres_' + id).value = h.nombres_apellidos || '';
                document.getElementById('acomp_genero_' + id).value = h.genero || '';
                document.getElementById('acomp_estado_civil_' + id).value = h.estado_civil || '';
                document.getElementById('acomp_nacionalidad_' + id).value = h.nacionalidad || 'Boliviano';
                document.getElementById('acomp_profesion_' + id).value = h.profesion || '';
                document.getElementById('acomp_objeto_' + id).value = h.objeto || '';
                
                if (h.procedencia) {
                    const deptos = ['La Paz', 'Santa Cruz', 'Cochabamba', 'Oruro', 'Potosí', 'Chuquisaca', 'Tarija', 'Beni', 'Pando'];
                    const selectProc = document.getElementById('acomp_procedencia_select_' + id);
                    const inputProc = document.getElementById('acomp_procedencia_' + id);
                    
                    if (deptos.includes(h.procedencia)) {
                        selectProc.value = h.procedencia;
                        inputProc.value = h.procedencia;
                        inputProc.classList.add('hidden');
                    } else {
                        selectProc.value = 'otro';
                        inputProc.value = h.procedencia;
                        inputProc.classList.remove('hidden');
                    }
                }
                
                if (h.fecha_nacimiento) {
                    document.getElementById('acomp_fecha_nacimiento_' + id).value = h.fecha_nacimiento;
                    calcularEdadAcompanante(id);
                } else if (h.edad) {
                    document.getElementById('acomp_edad_' + id).value = h.edad;
                }
                
                const aDiv = document.getElementById('acompanante_' + id);
                const prev = aDiv.querySelector('.mensaje-encontrado');
                if (prev) prev.remove();
                
                const msg = document.createElement('div');
                msg.className = 'mensaje-encontrado mt-2 p-2 bg-green-50 border border-green-200 rounded-lg text-xs text-green-700 flex items-center gap-1.5';
                msg.innerHTML = '✓ Datos de acompañante autocompletados';
                aDiv.appendChild(msg);
                
                setTimeout(() => msg.remove(), 3000);
            }
        })
        .catch(err => console.error('Error al buscar acompañante:', err));
}

// Calcular salida y precio total
function calcularFechaSalida() {
    const fi = document.getElementById('fecha_ingreso').value;
    const nd = document.getElementById('nro_dias').value;
    const habSelect = document.getElementById('nro_pieza');
    const prContainer = document.getElementById('precio_total_container');
    const descContainer = document.getElementById('descuento_container');
    
    if (fi && nd) {
        const parts = fi.split('-');
        const date = new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, parseInt(parts[2]));
        date.setDate(date.getDate() + parseInt(nd));
        
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const d = String(date.getDate()).padStart(2, '0');
        
        document.getElementById('fecha_salida_estimada').value = `${y}-${m}-${d}`;
        
        const msgSalida = document.getElementById('mensaje_salida');
        if (msgSalida) {
            const meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
            msgSalida.innerHTML = `<i class="fas fa-clock mr-1"></i> Salida: ${d} de ${meses[date.getMonth()]} de ${y} hasta las 12:00 del mediodía`;
        }
        
        if (habSelect && habSelect.value) {
            const op = habSelect.options[habSelect.selectedIndex];
            const pDia = parseFloat(op.getAttribute('data-precio'));
            
            if (pDia && !isNaN(pDia)) {
                const tot = pDia * parseInt(nd);
                document.getElementById('precio_total_monto').textContent = `Bs. ${tot.toFixed(2)}`;
                document.getElementById('detalle_precio').textContent = `Bs. ${pDia.toFixed(2)} × ${nd} día${nd > 1 ? 's' : ''}`;
                
                prContainer.classList.remove('hidden');
                descContainer.classList.remove('hidden');
                
                const desc = parseFloat(document.getElementById('descuento').value) || 0;
                if (desc > 0) {
                    const final = tot - desc;
                    document.getElementById('precio_final').textContent = `Bs. ${final.toFixed(2)}`;
                    document.getElementById('precio_con_descuento').classList.remove('hidden');
                } else {
                    document.getElementById('precio_con_descuento').classList.add('hidden');
                }
            }
        }
    } else {
        if (prContainer) prContainer.classList.add('hidden');
        if (descContainer) descContainer.classList.add('hidden');
    }
}

// Iniciar fecha de ingreso por defecto a hoy
document.addEventListener('DOMContentLoaded', function() {
    const hoy = new Date();
    const y = hoy.getFullYear();
    const m = String(hoy.getMonth() + 1).padStart(2, '0');
    const d = String(hoy.getDate()).padStart(2, '0');
    
    document.getElementById('fecha_ingreso').value = `${y}-${m}-${d}`;
    
    // Si ya hay habitación precargada, actualizar capacidad
    actualizarCapacidadHabitacion();
    calcularFechaSalida();
});

// Cambiar método de pago
function cambiarMetodoPago(metodo) {
    const lbEfectivo = document.getElementById('label_efectivo');
    const lbQr = document.getElementById('label_qr');
    const lbPendiente = document.getElementById('label_pendiente');
    
    const qrContainer = document.getElementById('qr_imagen_container');
    const pendienteContainer = document.getElementById('pendiente_aviso_container');
    
    // Quitar active de todos
    lbEfectivo.classList.remove('active');
    lbQr.classList.remove('active');
    lbPendiente.classList.remove('active');
    
    qrContainer.style.display = 'none';
    pendienteContainer.style.display = 'none';
    
    if (metodo === 'efectivo') {
        lbEfectivo.classList.add('active');
    } else if (metodo === 'qr') {
        lbQr.classList.add('active');
        qrContainer.style.display = 'block';
    } else if (metodo === 'pendiente') {
        lbPendiente.classList.add('active');
        pendienteContainer.style.display = 'block';
    }
}

// Toggle garaje detalles
function toggleGarajeDetalles() {
    const ch = document.getElementById('usa_garaje');
    const det = document.getElementById('garaje_detalles');
    const placa = document.getElementById('garaje_placa');
    const tipo = document.getElementById('garaje_tipo_vehiculo');
    
    if (ch.checked) {
        det.style.display = 'block';
        placa.required = true;
        tipo.required = true;
    } else {
        det.style.display = 'none';
        placa.required = false;
        tipo.required = false;
        placa.value = '';
        tipo.value = '';
    }
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
