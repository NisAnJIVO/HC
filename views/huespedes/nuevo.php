<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/Huesped.php';
require_once __DIR__ . '/../../models/Habitacion.php';
require_once __DIR__ . '/../../models/RegistroOcupacion.php';

$page_title = 'Nuevo Registro de Huésped';
$mensaje = '';
$tipo_mensaje = '';

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
        
        // NUEVO: Verificar si existe un registro reciente del huésped en esta habitación
        // Si es así, sugerir extender en lugar de crear nuevo registro
        $registro_reciente = $registroModel->buscarRegistroReciente($huesped_id, $habitacion['id'], 2);
        if ($registro_reciente) {
            // Redirigir a la página de extender estadía
            $mensaje_aviso = "Este huésped tiene una estadía reciente en esta habitación. Redirigiendo a extensión de estadía...";
            header("refresh:2;url=extender_estadia.php?id={$registro_reciente['id']}");
            $mensaje = $mensaje_aviso;
            $tipo_mensaje = 'info';
        } else {
            // Continuar con el registro normal
            
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
        
        $total_personas = 1 + $num_acompanantes; // 1 titular + acompañantes
        if ($total_personas > $capacidad_maxima) {
            throw new Exception("La habitación {$tipo_hab} solo permite {$capacidad_maxima} persona(s). Usted intentó registrar {$total_personas} persona(s).");
        }
        
        // Calcular fecha de salida estimada
        // Si entra el 20 y se queda 1 día, sale el 21
        $fecha_ingreso = $_POST['fecha_ingreso'];
        $nro_dias = (int)$_POST['nro_dias'];
        $fecha_salida = date('Y-m-d', strtotime($fecha_ingreso . ' +' . $nro_dias . ' days'));
        
        // Registrar ocupación
        $datos_ocupacion = [
            'huesped_id' => $huesped_id,
            'habitacion_id' => $habitacion['id'],
            'nro_pieza' => clean_input($_POST['nro_pieza']),
            'prox_destino' => !empty($_POST['prox_destino']) ? clean_input($_POST['prox_destino']) : null,
            'via_ingreso' => !empty($_POST['via_ingreso']) ? clean_input($_POST['via_ingreso']) : null,
            'fecha_ingreso' => $fecha_ingreso,
            'nro_dias' => $nro_dias,
            'fecha_salida_estimada' => $fecha_salida
        ];
        
        $ocupacion_id = $registroModel->crear($datos_ocupacion);
        if (!$ocupacion_id) {
            throw new Exception('Error al registrar ocupación en la base de datos');
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
        
        // Si es pago QR, también registrar en tabla pagos_qr
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
                    // Verificar si el acompañante ya existe
                    $acomp_existente = $huespedModel->buscarPorCI($_POST['acomp_ci'][$i]);
                    
                    if ($acomp_existente) {
                        $acomp_huesped_id = $acomp_existente['id'];
                    } else {
                        // Crear nuevo acompañante con todos los campos
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
                    
                    // Registrar ocupación del acompañante (misma habitación, mismo período)
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
        
            $metodo_pago_texto = $metodo_pago === 'qr' ? 'QR' : 'Efectivo';
            $mensaje .= 'Ocupación e ingreso registrados correctamente. Total: Bs. ' . number_format($monto_total, 2) . ' (' . $metodo_pago_texto . ')';
            $tipo_mensaje = 'success';
            
            // Limpiar POST para evitar reenvíos
            $_POST = [];
        } // FIN del else de verificación de registro reciente
        
    } catch (PDOException $e) {
        $mensaje = 'Error de base de datos: ' . $e->getMessage();
        $tipo_mensaje = 'danger';
        error_log("Error en registro: " . $e->getMessage());
    } catch (Exception $e) {
        $mensaje = 'Error: ' . $e->getMessage();
        $tipo_mensaje = 'danger';
        
        // Log para debugging
        error_log("Error en registro: " . $e->getMessage());
        error_log("Datos POST: " . print_r($_POST, true));
    }
}

// Obtener habitaciones disponibles
$habitacionModel = new Habitacion();
$habitaciones = $habitacionModel->obtenerDisponibles();

include __DIR__ . '/../../includes/header.php';
?>

<!-- Page Header -->
<div class="mb-6 sm:mb-8">
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
        <div class="flex-1">
            <h1 class="text-2xl sm:text-4xl font-bold text-noir mb-1 sm:mb-2">Nuevo Registro</h1>
            <p class="text-sm sm:text-base text-gray-500">Complete la información del huésped y asigne una habitación</p>
        </div>
        <a href="<?php echo BASE_PATH; ?>/views/huespedes/activos.php" class="px-4 py-2 sm:px-6 sm:py-3 text-sm sm:text-base border border-gray-300 rounded-xl text-gray-700 font-medium hover:bg-mist transition-all duration-200 text-center">
            Cancelar
        </a>
    </div>
</div>

<!-- Alert Messages -->
<?php if ($mensaje): ?>
    <div class="mb-8 animate-fade-in">
        <div class="bg-<?php echo $tipo_mensaje === 'success' ? 'green' : 'red'; ?>-50 border border-<?php echo $tipo_mensaje === 'success' ? 'green' : 'red'; ?>-200 rounded-xl p-4">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <?php if ($tipo_mensaje === 'success'): ?>
                        <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                    <?php else: ?>
                        <svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                        </svg>
                    <?php endif; ?>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-<?php echo $tipo_mensaje === 'success' ? 'green' : 'red'; ?>-800">
                        <?php echo $mensaje; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Main Form -->
<form method="POST" action="" class="space-y-8">
    
    <!-- Sección: Información Personal -->
    <div class="bg-white rounded-2xl border-2 border-gray-300 shadow-lg overflow-hidden hover:shadow-xl transition-shadow duration-300">
        <div class="px-8 py-6 border-b-2 border-gray-300 bg-gradient-to-r from-blue-50 to-indigo-50">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-gray-900">Información Personal</h2>
                    <p class="text-sm text-gray-600 mt-0.5">Datos de identificación del huésped</p>
                </div>
            </div>
        </div>
        
        <div class="p-8 space-y-6 bg-gray-50">
            <!-- Fila 1: CI y Nombres -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                    <label class="block text-sm font-bold text-gray-800 mb-2">
                        CI o Pasaporte <span class="text-red-600 text-base">*</span>
                    </label>
                    <div class="relative">
                        <input 
                            type="text" 
                            id="ci_pasaporte"
                            name="ci_pasaporte" 
                            onblur="buscarHuespedPorCI()"
                            value="<?php echo isset($_POST['ci_pasaporte']) ? htmlspecialchars($_POST['ci_pasaporte']) : ''; ?>"
                            required
                            class="w-full px-4 py-4 border-2 border-gray-400 rounded-xl focus:ring-4 focus:ring-blue-300 focus:border-blue-500 transition-all duration-200 text-gray-900 font-medium placeholder-gray-400 hover:border-gray-500 shadow-sm"
                            placeholder="Ingrese CI o Pasaporte"
                        >
                        <!-- Indicador de búsqueda -->
                        <div id="busqueda_indicador" class="hidden absolute right-3 top-1/2 transform -translate-y-1/2">
                            <div class="w-5 h-5 border-2 border-gray-300 border-t-noir rounded-full animate-spin"></div>
                        </div>
                    </div>
                    <!-- Mensaje de estado -->
                    <div id="busqueda_mensaje" class="text-xs mt-1.5"></div>
                    <p class="text-xs text-gray-500 mt-1.5">Si existe en el sistema, los datos se autocompletarán</p>
                </div>
                
                <div class="space-y-2">
                    <label class="block text-sm font-bold text-gray-800 mb-2">
                        Nombres y Apellidos <span class="text-red-600 text-base">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="nombres_apellidos"
                        name="nombres_apellidos" 
                        value="<?php echo isset($_POST['nombres_apellidos']) ? htmlspecialchars($_POST['nombres_apellidos']) : ''; ?>"
                        required
                        class="w-full px-4 py-4 border-2 border-gray-400 rounded-xl focus:ring-4 focus:ring-blue-300 focus:border-blue-500 transition-all duration-200 text-gray-900 font-medium placeholder-gray-400 hover:border-gray-500 shadow-sm"
                        placeholder="Nombre completo del huésped"
                    >
                </div>
            </div>
            
            <!-- Fila 2: Género, Fecha Nacimiento, Edad, E. Civil, Nacionalidad -->
            <div class="grid grid-cols-1 md:grid-cols-5 gap-6">
                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-noir">
                        Género <span class="text-red-500">*</span>
                    </label>
                    <select 
                        id="genero"
                        name="genero" 
                        required
                        class="w-full px-4 py-3.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-noir focus:border-transparent transition-all duration-200 text-noir appearance-none bg-white"
                    >
                        <option value="">Seleccione</option>
                        <option value="M" <?php echo (isset($_POST['genero']) && $_POST['genero'] == 'M') ? 'selected' : ''; ?>>Masculino</option>
                        <option value="F" <?php echo (isset($_POST['genero']) && $_POST['genero'] == 'F') ? 'selected' : ''; ?>>Femenino</option>
                    </select>
                </div>
                
                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-noir">
                        Fecha de Nacimiento <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="date" 
                        id="fecha_nacimiento"
                        onchange="calcularEdad()"
                        required
                        class="w-full px-4 py-3.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-noir focus:border-transparent transition-all duration-200 text-noir"
                    >
                    <p class="text-xs text-gray-500 mt-1.5">La edad se calcula automáticamente</p>
                </div>
                
                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-noir">
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
                        max="120"
                        class="w-full px-4 py-3.5 border border-gray-300 rounded-xl bg-gray-50 text-noir font-semibold cursor-not-allowed"
                        placeholder="0"
                    >
                    <p class="text-xs text-gray-500 mt-1.5">Se calcula automáticamente</p>
                </div>
                
                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-noir">Estado Civil</label>
                    <select 
                        id="estado_civil"
                        name="estado_civil"
                        class="w-full px-4 py-3.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-noir focus:border-transparent transition-all duration-200 text-noir appearance-none bg-white"
                    >
                        <option value="">Seleccione</option>
                        <option value="S" <?php echo (isset($_POST['estado_civil']) && $_POST['estado_civil'] == 'S') ? 'selected' : ''; ?>>S</option>
                        <option value="C" <?php echo (isset($_POST['estado_civil']) && $_POST['estado_civil'] == 'C') ? 'selected' : ''; ?>>C</option>
                        <option value="D" <?php echo (isset($_POST['estado_civil']) && $_POST['estado_civil'] == 'D') ? 'selected' : ''; ?>>D</option>
                        <option value="V" <?php echo (isset($_POST['estado_civil']) && $_POST['estado_civil'] == 'V') ? 'selected' : ''; ?>>V</option>
                    </select>
                </div>
                
                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-noir">
                        Nacionalidad <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="nacionalidad"
                        name="nacionalidad" 
                        required
                        value="<?php echo isset($_POST['nacionalidad']) ? htmlspecialchars($_POST['nacionalidad']) : 'Boliviano'; ?>"
                        class="w-full px-4 py-3.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-noir focus:border-transparent transition-all duration-200 text-noir placeholder-gray-400"
                        placeholder="País"
                    >
                </div>
            </div>
            
            <!-- Fila 3: Profesión, Objeto, Procedencia -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-noir">Profesión</label>
                    <input 
                        type="text" 
                        id="profesion"
                        name="profesion"
                        value="<?php echo isset($_POST['profesion']) ? htmlspecialchars($_POST['profesion']) : ''; ?>"
                        class="w-full px-4 py-3.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-noir focus:border-transparent transition-all duration-200 text-noir placeholder-gray-400"
                        placeholder="Ocupación laboral"
                    >
                </div>
                
                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-noir">Objeto del Viaje</label>
                    <select 
                        id="objeto"
                        name="objeto"
                        class="w-full px-4 py-3.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-noir focus:border-transparent transition-all duration-200 text-noir appearance-none bg-white"
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
                
                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-noir">Procedencia</label>
                    <select 
                        id="procedencia_select"
                        onchange="toggleProcedenciaCustom()"
                        class="w-full px-4 py-3.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-noir focus:border-transparent transition-all duration-200 text-noir appearance-none bg-white"
                    >
                        <option value="">Seleccione departamento</option>
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
                        class="hidden w-full px-4 py-3.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-noir focus:border-transparent transition-all duration-200 text-noir placeholder-gray-400"
                        placeholder="Escriba el país de origen"
                    >
                </div>
            </div>
        </div>
    </div>
    
    <!-- Sección: Acompañantes (Huéspedes Adicionales) -->
    <div class="bg-white rounded-2xl border-2 border-purple-300 shadow-lg overflow-hidden hover:shadow-xl transition-shadow duration-300" id="seccion_acompanantes">
        <div class="px-8 py-6 border-b-2 border-purple-300 bg-gradient-to-r from-purple-50 to-pink-50">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-purple-600 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">Acompañantes</h2>
                        <p class="text-sm text-gray-600 mt-0.5">Para habitaciones compartidas (dobles, triples, matrimoniales)</p>
                    </div>
                </div>
                <button 
                    type="button" 
                    id="btn_agregar_acompanante"
                    onclick="agregarAcompanante()" 
                    disabled
                    class="px-5 py-3 bg-gray-400 text-white rounded-xl text-sm font-bold cursor-not-allowed transition-all duration-200 shadow-md"
                >
                    + Agregar Acompañante
                </button>
            </div>
        </div>
        
        <div class="p-8 bg-purple-50/30" id="lista_acompanantes">
            <div class="text-center text-gray-400 py-6" id="mensaje_sin_acompanantes">
                <svg class="w-16 h-16 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                <p class="text-sm">No hay acompañantes agregados</p>
                <p class="text-xs mt-1">Haz clic en "Agregar Acompañante" para registrar más personas en la misma habitación</p>
            </div>
        </div>
    </div>
    
    <!-- Sección: Detalles de Reserva -->
    <div class="bg-white rounded-2xl border-2 border-green-300 shadow-lg overflow-hidden hover:shadow-xl transition-shadow duration-300">
        <div class="px-8 py-6 border-b-2 border-green-300 bg-gradient-to-r from-green-50 to-emerald-50">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-green-600 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-gray-900">Detalles de Reserva</h2>
                    <p class="text-sm text-gray-600 mt-0.5">Asignación de habitación y fechas de estadía</p>
                </div>
            </div>
        </div>
        
        <div class="p-8 space-y-6 bg-green-50/30">
            <!-- Fila 1: Habitación y Destino -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                    <label class="block text-sm font-bold text-gray-800 mb-2">
                        Habitación <span class="text-red-600 text-base">*</span>
                    </label>
                    <select 
                        name="nro_pieza" 
                        id="nro_pieza"
                        onchange="actualizarCapacidadHabitacion(); calcularFechaSalida();"
                        required
                        class="w-full px-4 py-4 border-2 border-gray-400 rounded-xl focus:ring-4 focus:ring-green-300 focus:border-green-500 transition-all duration-200 text-gray-900 font-medium appearance-none bg-white hover:border-gray-500 shadow-sm"
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
                                Habitación <?php echo $hab['numero']; ?> - <?php echo $hab['tipo']; ?> - Bs. <?php echo number_format($hab['precio_dia'], 2); ?>/día
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div id="mensaje_capacidad" class="mt-2 text-sm text-gray-600 hidden">
                        <i class="fas fa-users mr-1"></i>
                        <span id="texto_capacidad"></span>
                    </div>
                </div>
                
                <div class="space-y-2">
                    <label class="block text-sm font-bold text-gray-800 mb-2">Próximo Destino</label>
                    <select 
                        id="prox_destino_select"
                        onchange="toggleProxDestinoCustom()"
                        class="w-full px-4 py-4 border-2 border-gray-400 rounded-xl focus:ring-4 focus:ring-green-300 focus:border-green-500 transition-all duration-200 text-gray-900 font-medium appearance-none bg-white hover:border-gray-500 shadow-sm"
                    >
                        <option value="">Seleccione departamento</option>
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
                        class="hidden w-full px-4 py-4 border-2 border-gray-400 rounded-xl focus:ring-4 focus:ring-green-300 focus:border-green-500 transition-all duration-200 text-gray-900 font-medium placeholder-gray-400 hover:border-gray-500 shadow-sm mt-2"
                        placeholder="Escriba la ciudad o país de destino"
                    >
                </div>
            </div>
            
            <!-- Fila 2: Vía, Fecha Ingreso, Días -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-noir">Vía de Ingreso</label>
                    <select 
                        name="via_ingreso"
                        class="w-full px-4 py-3.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-noir focus:border-transparent transition-all duration-200 text-noir appearance-none bg-white"
                    >
                        <option value="">Seleccione</option>
                        <option value="T" <?php echo (isset($_POST['via_ingreso']) && $_POST['via_ingreso'] == 'T') ? 'selected' : ''; ?>>Terrestre</option>
                        <option value="A" <?php echo (isset($_POST['via_ingreso']) && $_POST['via_ingreso'] == 'A') ? 'selected' : ''; ?>>Aéreo</option>
                    </select>
                </div>
                
                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-noir">
                        Fecha de Ingreso <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="date" 
                        id="fecha_ingreso"
                        name="fecha_ingreso" 
                        value="<?php echo isset($_POST['fecha_ingreso']) ? htmlspecialchars($_POST['fecha_ingreso']) : ''; ?>"
                        onchange="calcularFechaSalida()"
                        required
                        class="w-full px-4 py-3.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-noir focus:border-transparent transition-all duration-200 text-noir"
                    >
                </div>
                
                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-noir">
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
                        class="w-full px-4 py-3.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-noir focus:border-transparent transition-all duration-200 text-noir placeholder-gray-400"
                        placeholder="Días"
                    >
                </div>
            </div>
            
            <!-- Método de Pago -->
            <div class="space-y-3">
                <label class="block text-sm font-semibold text-noir">
                    Método de Pago <span class="text-red-500">*</span>
                </label>
                
                <div class="grid grid-cols-3 gap-3">
                    <!-- Efectivo -->
                    <label class="cursor-pointer">
                        <input 
                            type="radio" 
                            name="metodo_pago" 
                            value="efectivo" 
                            id="metodo_efectivo"
                            checked
                            onchange="cambiarMetodoPago('efectivo')"
                            class="hidden"
                        >
                        <div id="btn_efectivo" class="flex items-center justify-center gap-2 p-3 border-2 border-green-500 bg-green-50 rounded-xl transition-all duration-200 hover:shadow-md">
                            <i class="fas fa-money-bill-wave text-xl text-green-600"></i>
                            <span class="font-semibold text-sm text-green-700">Efectivo</span>
                        </div>
                    </label>
                    
                    <!-- QR -->
                    <label class="cursor-pointer">
                        <input 
                            type="radio" 
                            name="metodo_pago" 
                            value="qr" 
                            id="metodo_qr"
                            onchange="cambiarMetodoPago('qr')"
                            class="hidden"
                        >
                        <div id="btn_qr" class="flex items-center justify-center gap-2 p-3 border-2 border-gray-300 bg-white rounded-xl transition-all duration-200 hover:shadow-md hover:border-purple-300">
                            <i class="fas fa-qrcode text-xl text-gray-600"></i>
                            <span class="font-semibold text-sm text-gray-700">QR</span>
                        </div>
                    </label>
                    
                    <!-- Pendiente -->
                    <label class="cursor-pointer">
                        <input 
                            type="radio" 
                            name="metodo_pago" 
                            value="pendiente" 
                            id="metodo_pendiente"
                            onchange="cambiarMetodoPago('pendiente')"
                            class="hidden"
                        >
                        <div id="btn_pendiente" class="flex items-center justify-center gap-2 p-3 border-2 border-gray-300 bg-white rounded-xl transition-all duration-200 hover:shadow-md hover:border-orange-300">
                            <i class="fas fa-clock text-xl text-gray-600"></i>
                            <span class="font-semibold text-sm text-gray-700">Pendiente</span>
                        </div>
                    </label>
                </div>
                
                <!-- Imagen QR (se muestra al seleccionar QR) -->
                <div id="qr_imagen_container" style="display: none;" class="mt-4 p-4 bg-purple-50 border-2 border-purple-300 rounded-xl text-center">
                    <p class="text-sm font-semibold text-purple-700 mb-3">Escanea el código QR para realizar el pago:</p>
                    <img src="<?php echo BASE_PATH; ?>/assets/img/QR.jpeg" alt="QR de pago" class="mx-auto max-w-xs w-full rounded-lg shadow-lg">
                    
                    <!-- Número de Transacción -->
                    <div class="mt-4">
                        <label class="block text-sm font-semibold text-noir mb-2">
                            Número de Transacción (Opcional)
                        </label>
                        <input 
                            type="text" 
                            id="numero_transaccion"
                            name="numero_transaccion"
                            value="<?php echo isset($_POST['numero_transaccion']) ? htmlspecialchars($_POST['numero_transaccion']) : ''; ?>"
                            class="w-full px-4 py-3 border border-purple-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200 text-noir placeholder-gray-400"
                            placeholder="Ingrese el número de transacción"
                        >
                    </div>
                </div>
                
                <!-- Aviso Pago Pendiente (se muestra al seleccionar Pendiente) -->
                <div id="pendiente_aviso_container" style="display: none;" class="mt-4 p-4 bg-orange-50 border-2 border-orange-300 rounded-xl">
                    <div class="flex items-start gap-3">
                        <i class="fas fa-exclamation-triangle text-2xl text-orange-600 mt-1"></i>
                        <div>
                            <p class="text-sm font-semibold text-orange-700 mb-2">
                                <i class="fas fa-info-circle"></i> Pago Pendiente
                            </p>
                            <p class="text-sm text-orange-600">
                                El huésped será registrado pero el pago quedará marcado como <strong>PENDIENTE</strong>. 
                                Podrás completar el pago más tarde desde la sección de <strong>Pagos Pendientes</strong> en Finanzas.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Garaje (Opcional - Mejorado) -->
            <div class="bg-gradient-to-r from-gray-50 to-blue-50 rounded-xl p-5 border-2 border-gray-300">
                <div class="flex items-start gap-3">
                    <input 
                        type="checkbox" 
                        id="usa_garaje"
                        name="usa_garaje" 
                        value="1"
                        onchange="toggleGarajeDetalles()"
                        class="w-5 h-5 mt-0.5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2"
                    >
                    <div class="flex-1">
                        <label for="usa_garaje" class="text-base font-bold text-gray-800 cursor-pointer flex items-center gap-2">
                            <i class="fas fa-car text-blue-600"></i>
                            Usa garaje
                        </label>
                        <p class="text-xs text-gray-600 mt-1">Registrar vehículo del huésped (Bs. 10.00/día)</p>
                    </div>
                </div>
                
                <!-- Detalles del vehículo (se muestran al marcar el checkbox) -->
                <div id="garaje_detalles" style="display: none;" class="mt-4 pt-4 border-t-2 border-gray-300">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-gray-800 mb-2">
                                Número de Placa <span class="text-red-600">*</span>
                            </label>
                            <input 
                                type="text" 
                                id="garaje_placa"
                                name="garaje_placa"
                                placeholder="Ej: 1234 ABC"
                                class="w-full px-4 py-3 border-2 border-gray-400 rounded-lg focus:ring-3 focus:ring-blue-300 focus:border-blue-500 transition-all duration-200 text-gray-900 font-medium uppercase hover:border-gray-500 shadow-sm"
                            >
                        </div>
                        
                        <div>
                            <label class="block text-sm font-bold text-gray-800 mb-2">
                                Tipo de Vehículo <span class="text-red-600">*</span>
                            </label>
                            <select 
                                id="garaje_tipo_vehiculo"
                                name="garaje_tipo_vehiculo"
                                class="w-full px-4 py-3 border-2 border-gray-400 rounded-lg focus:ring-3 focus:ring-blue-300 focus:border-blue-500 transition-all duration-200 text-gray-900 font-medium appearance-none bg-white hover:border-gray-500 shadow-sm"
                            >
                                <option value="">Seleccione</option>
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
            
            <!-- Fecha Salida Estimada y Precio Total -->
            <div class="bg-mist rounded-xl p-4 sm:p-6 border border-gray-200 space-y-4">
                <!-- Fecha de Salida -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="flex-1">
                        <label class="block text-sm font-semibold text-noir mb-1">Fecha de Salida Estimada</label>
                        <p class="text-xs text-gray-500">Se calcula automáticamente según los días de estadía</p>
                    </div>
                    <input 
                        type="date" 
                        id="fecha_salida_estimada"
                        readonly
                        class="w-full sm:w-auto px-3 py-2 sm:px-4 sm:py-3 text-sm sm:text-base border border-gray-300 rounded-xl bg-white text-noir font-semibold"
                    >
                </div>
                <div id="mensaje_salida" class="text-xs sm:text-sm font-medium text-blue-700 bg-blue-50 px-3 py-2 sm:px-4 sm:py-2 rounded-lg border border-blue-200">
                    <i class="fas fa-info-circle mr-1"></i> Complete fecha de ingreso y número de días para calcular
                </div>
                
                <!-- Precio Total -->
                <div id="precio_total_container" class="hidden">
                    <div class="bg-gradient-to-r from-green-50 to-emerald-50 border-2 border-green-300 rounded-xl p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <label class="block text-sm font-semibold text-green-900 mb-1">
                                    <i class="fas fa-money-bill-wave mr-2"></i>Precio Total a Pagar
                                </label>
                                <p class="text-xs text-green-700" id="detalle_precio">Habitación × días</p>
                            </div>
                            <div class="text-right">
                                <div class="text-2xl sm:text-3xl font-bold text-green-900" id="precio_total_monto">
                                    Bs. 0.00
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Descuento (Opcional) -->
                <div id="descuento_container" class="hidden">
                    <div class="bg-orange-50 border-2 border-orange-300 rounded-xl p-4">
                        <div class="flex items-start gap-2 mb-3">
                            <i class="fas fa-tag text-orange-600 mt-1"></i>
                            <div class="flex-1">
                                <label class="block text-sm font-semibold text-orange-900 mb-1">Aplicar Descuento (Opcional)</label>
                                <p class="text-xs text-orange-700">Ej: Sin desayuno, promoción, estadía larga</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-orange-900 mb-1">Monto a descontar (Bs.)</label>
                                <input 
                                    type="number" 
                                    id="descuento"
                                    name="descuento"
                                    min="0"
                                    step="0.01"
                                    oninput="calcularFechaSalida()"
                                    placeholder="0.00"
                                    class="w-full px-3 py-2 text-sm border border-orange-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                                >
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-orange-900 mb-1">Motivo del descuento</label>
                                <input 
                                    type="text" 
                                    id="motivo_descuento"
                                    name="motivo_descuento"
                                    placeholder="Ej: Sin desayuno"
                                    class="w-full px-3 py-2 text-sm border border-orange-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                                >
                            </div>
                        </div>
                        <div id="precio_con_descuento" class="hidden mt-3 pt-3 border-t border-orange-300">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-orange-900">Total con descuento:</span>
                                <span class="text-xl font-bold text-orange-900" id="precio_final">Bs. 0.00</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Action Buttons -->
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 pt-6">
        <a href="<?php echo BASE_PATH; ?>/views/huespedes/activos.php" class="px-5 py-2.5 border border-gray-300 rounded-lg text-gray-700 text-sm font-medium hover:bg-mist transition-all duration-200 text-center">
            Cancelar
        </a>
        <button 
            type="submit" 
            class="px-6 py-2.5 bg-noir text-white text-sm font-semibold rounded-lg hover:bg-gray-800 transition-all duration-200 shadow-md hover:shadow-lg"
        >
            Registrar Huésped
        </button>
    </div>
</form>

<script>
let contadorAcompanantes = 0;

// Función para eliminar acompañante
function eliminarAcompanante(id) {
    const elemento = document.getElementById(`acompanante_${id}`);
    if (elemento) {
        elemento.remove();
        
        // Contar acompañantes restantes
        const acompanantesRestantes = document.querySelectorAll('[id^="acompanante_"]').length;
        if (acompanantesRestantes === 0) {
            document.getElementById('mensaje_sin_acompanantes').style.display = 'block';
        }
        
        // Actualizar estado del botón de agregar acompañante
        actualizarBotonAcompanante();
    }
}

// Variables globales para capacidad
let capacidadHabitacion = 0;
let tipoHabitacion = '';

// Función para actualizar capacidad cuando cambia la habitación
function actualizarCapacidadHabitacion() {
    const select = document.getElementById('nro_pieza');
    const opcionSeleccionada = select.options[select.selectedIndex];
    
    if (!opcionSeleccionada.value) {
        // No hay habitación seleccionada
        capacidadHabitacion = 0;
        tipoHabitacion = '';
        document.getElementById('mensaje_capacidad').classList.add('hidden');
        document.getElementById('btn_agregar_acompanante').disabled = true;
        document.getElementById('btn_agregar_acompanante').className = 'px-4 py-2 bg-gray-400 text-white rounded-xl text-sm font-medium cursor-not-allowed transition-all duration-200';
        return;
    }
    
    capacidadHabitacion = parseInt(opcionSeleccionada.dataset.capacidad);
    tipoHabitacion = opcionSeleccionada.dataset.tipo;
    
    // Mostrar mensaje de capacidad
    const mensajeCapacidad = document.getElementById('mensaje_capacidad');
    const textoCapacidad = document.getElementById('texto_capacidad');
    
    let textoPersonas = capacidadHabitacion === 1 ? 'persona' : 'personas';
    textoCapacidad.textContent = `Habitación ${tipoHabitacion}: Capacidad máxima ${capacidadHabitacion} ${textoPersonas}`;
    mensajeCapacidad.classList.remove('hidden');
    
    // Actualizar estado del botón
    actualizarBotonAcompanante();
    
    // Verificar si hay que eliminar acompañantes excedentes
    verificarCapacidadExcedida();
}

// Función para actualizar el botón de agregar acompañante
function actualizarBotonAcompanante() {
    const btn = document.getElementById('btn_agregar_acompanante');
    const numAcompanantesActuales = document.querySelectorAll('[id^="acompanante_"]').length;
    const totalPersonas = 1 + numAcompanantesActuales; // 1 titular + acompañantes
    
    if (capacidadHabitacion === 0) {
        // No hay habitación seleccionada
        btn.disabled = true;
        btn.className = 'px-5 py-3 bg-gray-400 text-white rounded-xl text-sm font-bold cursor-not-allowed transition-all duration-200 shadow-md';
        btn.innerHTML = '+ Agregar Acompañante';
    } else if (totalPersonas >= capacidadHabitacion) {
        // Capacidad alcanzada
        btn.disabled = true;
        btn.className = 'px-5 py-3 bg-red-400 text-white rounded-xl text-sm font-bold cursor-not-allowed transition-all duration-200 shadow-md';
        btn.innerHTML = `Capacidad completa (${totalPersonas}/${capacidadHabitacion})`;
    } else {
        // Puede agregar más acompañantes
        btn.disabled = false;
        btn.className = 'px-5 py-3 bg-purple-600 text-white rounded-xl text-sm font-bold hover:bg-purple-700 hover:shadow-lg active:transform active:scale-95 transition-all duration-200 shadow-md';
        btn.innerHTML = `+ Agregar Acompañante (${totalPersonas}/${capacidadHabitacion})`;
    }
}

// Función para verificar si hay acompañantes excedentes y notificar
function verificarCapacidadExcedida() {
    const numAcompanantesActuales = document.querySelectorAll('[id^="acompanante_"]').length;
    const totalPersonas = 1 + numAcompanantesActuales;
    
    if (totalPersonas > capacidadHabitacion && capacidadHabitacion > 0) {
        const exceso = totalPersonas - capacidadHabitacion;
        alert(`⚠️ ATENCIÓN: La habitación ${tipoHabitacion} solo permite ${capacidadHabitacion} persona(s).\n\nActualmente tiene ${totalPersonas} persona(s) registrada(s).\n\nPor favor elimine ${exceso} acompañante(s) o cambie a una habitación de mayor capacidad.`);
    }
}

// Modificar función agregarAcompanante original para actualizar botón
const agregarAcompananteOriginal = agregarAcompanante;
function agregarAcompanante() {
    // Verificar capacidad antes de agregar
    const numAcompanantesActuales = document.querySelectorAll('[id^="acompanante_"]').length;
    const totalPersonas = 1 + numAcompanantesActuales;
    
    if (totalPersonas >= capacidadHabitacion) {
        alert(`La habitación ${tipoHabitacion} solo permite ${capacidadHabitacion} persona(s).\n\nNo puede agregar más acompañantes.`);
        return;
    }
    
    // Llamar función original (código existente)
    contadorAcompanantes++;
    const container = document.getElementById('lista_acompanantes');
    const mensajeSin = document.getElementById('mensaje_sin_acompanantes');
    
    // Ocultar mensaje si es el primer acompañante
    if (document.querySelectorAll('[id^="acompanante_"]').length === 0) {
        mensajeSin.style.display = 'none';
    }
    
    const acompananteHTML = `
        <div class="border-2 border-purple-300 rounded-xl p-6 mb-4 bg-white shadow-md hover:shadow-lg transition-shadow duration-200" id="acompanante_${contadorAcompanantes}">
            <div class="flex items-center justify-between mb-5 pb-4 border-b-2 border-purple-200">
                <h3 class="text-lg font-bold text-purple-900 flex items-center gap-2">
                    <span class="w-8 h-8 bg-purple-600 text-white rounded-full flex items-center justify-center text-sm">${contadorAcompanantes}</span>
                    Acompañante #${contadorAcompanantes}
                </h3>
                <button 
                    type="button" 
                    onclick="eliminarAcompanante(${contadorAcompanantes})"
                    class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white text-sm font-bold rounded-lg transition-all duration-200 shadow-sm hover:shadow-md"
                >
                    ✕ Eliminar
                </button>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-800 mb-2">CI/Pasaporte <span class="text-red-600">*</span></label>
                    <input 
                        type="text" 
                        name="acomp_ci[]" 
                        id="acomp_ci_${contadorAcompanantes}"
                        onblur="buscarAcompanantePorCI(${contadorAcompanantes})"
                        required
                        class="w-full px-3 py-3 border-2 border-gray-400 rounded-lg text-sm focus:ring-3 focus:ring-purple-300 focus:border-purple-500 hover:border-gray-500 shadow-sm font-medium"
                        placeholder="Número de documento"
                    >
                </div>
                
                <div>
                    <label class="block text-xs font-bold text-gray-800 mb-2">Nombres y Apellidos <span class="text-red-600">*</span></label>
                    <input 
                        type="text" 
                        name="acomp_nombres[]" 
                        id="acomp_nombres_${contadorAcompanantes}"
                        required
                        class="w-full px-3 py-3 border-2 border-gray-400 rounded-lg text-sm focus:ring-3 focus:ring-purple-300 focus:border-purple-500 hover:border-gray-500 shadow-sm font-medium"
                        placeholder="Nombre completo"
                    >
                </div>
                
                <div>
                    <label class="block text-xs font-bold text-gray-800 mb-2">Género <span class="text-red-600">*</span></label>
                    <select 
                        name="acomp_genero[]" 
                        id="acomp_genero_${contadorAcompanantes}"
                        required
                        class="w-full px-3 py-3 border-2 border-gray-400 rounded-lg text-sm focus:ring-3 focus:ring-purple-300 focus:border-purple-500 bg-white hover:border-gray-500 shadow-sm font-medium"
                    >
                        <option value="">Seleccione</option>
                        <option value="M">Masculino</option>
                        <option value="F">Femenino</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-xs font-semibold text-noir mb-1">Fecha de Nacimiento <span class="text-red-500">*</span></label>
                    <input 
                        type="date" 
                        id="acomp_fecha_nacimiento_${contadorAcompanantes}"
                        onchange="calcularEdadAcompanante(${contadorAcompanantes})"
                        required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-purple-500"
                    >
                </div>
                
                <div>
                    <label class="block text-xs font-semibold text-noir mb-1">Edad <span class="text-red-500">*</span></label>
                    <input 
                        type="number" 
                        name="acomp_edad[]" 
                        id="acomp_edad_${contadorAcompanantes}"
                        required
                        readonly
                        min="1"
                        max="120"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-gray-50 cursor-not-allowed font-semibold"
                        placeholder="Se calcula automáticamente"
                    >
                </div>
                
                <div>
                    <label class="block text-xs font-semibold text-noir mb-1">Estado Civil <span class="text-red-500">*</span></label>
                    <select 
                        name="acomp_estado_civil[]" 
                        id="acomp_estado_civil_${contadorAcompanantes}"
                        required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-purple-500 bg-white"
                    >
                        <option value="">Seleccione</option>
                        <option value="S">S</option>
                        <option value="C">C</option>
                        <option value="D">D</option>
                        <option value="V">V</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-xs font-semibold text-noir mb-1">Nacionalidad <span class="text-red-500">*</span></label>
                    <input 
                        type="text" 
                        name="acomp_nacionalidad[]" 
                        id="acomp_nacionalidad_${contadorAcompanantes}"
                        required
                        value="Boliviano"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-purple-500"
                        placeholder="País"
                    >
                </div>
                
                <div>
                    <label class="block text-xs font-semibold text-noir mb-1">Profesión <span class="text-red-500">*</span></label>
                    <input 
                        type="text" 
                        name="acomp_profesion[]" 
                        id="acomp_profesion_${contadorAcompanantes}"
                        required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-purple-500"
                        placeholder="Ocupación"
                    >
                </div>
                
                <div>
                    <label class="block text-xs font-bold text-gray-800 mb-2">Objeto del Viaje <span class="text-red-600">*</span></label>
                    <select 
                        name="acomp_objeto[]" 
                        id="acomp_objeto_${contadorAcompanantes}"
                        required
                        class="w-full px-3 py-3 border-2 border-gray-400 rounded-lg text-sm focus:ring-3 focus:ring-purple-300 focus:border-purple-500 bg-white hover:border-gray-500 shadow-sm font-medium"
                    >
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
                
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-gray-800 mb-2">Procedencia <span class="text-red-600">*</span></label>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                        <select 
                            id="acomp_procedencia_select_${contadorAcompanantes}"
                            onchange="toggleProcedenciaCustomAcomp(${contadorAcompanantes})"
                            class="w-full px-3 py-3 border-2 border-gray-400 rounded-lg text-sm focus:ring-3 focus:ring-purple-300 focus:border-purple-500 bg-white hover:border-gray-500 shadow-sm font-medium"
                        >
                            <option value="">Seleccione departamento</option>
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
                            name="acomp_procedencia[]" 
                            id="acomp_procedencia_${contadorAcompanantes}"
                            required
                            class="hidden w-full px-3 py-3 border-2 border-gray-400 rounded-lg text-sm focus:ring-3 focus:ring-purple-300 focus:border-purple-500 hover:border-gray-500 shadow-sm font-medium"
                            placeholder="Escriba el lugar de origen"
                        >
                    </div>
                </div>
            </div>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', acompananteHTML);
    
    // Actualizar estado del botón después de agregar
    actualizarBotonAcompanante();
}

// Función para calcular edad automáticamente del titular
function calcularEdad() {
    const fechaNacimiento = document.getElementById('fecha_nacimiento').value;
    
    if (!fechaNacimiento) {
        document.getElementById('edad').value = '';
        return;
    }
    
    const hoy = new Date();
    const nacimiento = new Date(fechaNacimiento);
    
    let edad = hoy.getFullYear() - nacimiento.getFullYear();
    const mes = hoy.getMonth() - nacimiento.getMonth();
    
    // Ajustar edad si aún no ha cumplido años este año
    if (mes < 0 || (mes === 0 && hoy.getDate() < nacimiento.getDate())) {
        edad--;
    }
    
    document.getElementById('edad').value = edad;
}

// Función para calcular edad de acompañante
function calcularEdadAcompanante(id) {
    const fechaNacimiento = document.getElementById('acomp_fecha_nacimiento_' + id).value;
    
    if (!fechaNacimiento) {
        document.getElementById('acomp_edad_' + id).value = '';
        return;
    }
    
    const hoy = new Date();
    const nacimiento = new Date(fechaNacimiento);
    
    let edad = hoy.getFullYear() - nacimiento.getFullYear();
    const mes = hoy.getMonth() - nacimiento.getMonth();
    
    if (mes < 0 || (mes === 0 && hoy.getDate() < nacimiento.getDate())) {
        edad--;
    }
    
    document.getElementById('acomp_edad_' + id).value = edad;
}

// Función para toggle del campo de procedencia personalizada (titular)
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

// Función para toggle del campo de próximo destino personalizado
function toggleProxDestinoCustom() {
    const select = document.getElementById('prox_destino_select');
    const input = document.getElementById('prox_destino');
    
    if (select.value === 'otro') {
        input.classList.remove('hidden');
        input.required = false;
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

// Función para toggle del campo de procedencia personalizada (acompañante)
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

// Función existente para buscar huésped
function buscarHuespedPorCI() {
    const ci = document.getElementById('ci_buscar').value;
    if (!ci) return;
    
    fetch('<?php echo BASE_PATH; ?>/controllers/buscar_huesped.php?ci=' + encodeURIComponent(ci))
        .then(response => response.json())
        .then(data => {
            if (data.success && data.huesped) {
                document.getElementById('nombres_apellidos').value = data.huesped.nombres_apellidos;
                document.getElementById('genero').value = data.huesped.genero;
                document.getElementById('edad').value = data.huesped.edad;
                document.getElementById('estado_civil').value = data.huesped.estado_civil || '';
                document.getElementById('nacionalidad').value = data.huesped.nacionalidad;
                document.getElementById('profesion').value = data.huesped.profesion || '';
                document.getElementById('objeto').value = data.huesped.objeto || '';
                document.getElementById('procedencia').value = data.huesped.procedencia || '';
            }
        })
        .catch(error => console.error('Error:', error));
}

// Función para buscar acompañante por CI y autocompletar datos
function buscarAcompanantePorCI(id) {
    const ci = document.getElementById('acomp_ci_' + id).value.trim();
    
    if (!ci || ci.length < 3) {
        return;
    }
    
    fetch('<?php echo BASE_PATH; ?>/controllers/buscar_huesped.php?ci=' + encodeURIComponent(ci))
        .then(response => response.json())
        .then(data => {
            if (data.success && data.huesped) {
                const h = data.huesped;
                
                // Autocompletar datos del acompañante
                document.getElementById('acomp_nombres_' + id).value = h.nombres_apellidos || '';
                document.getElementById('acomp_genero_' + id).value = h.genero || '';
                document.getElementById('acomp_estado_civil_' + id).value = h.estado_civil || '';
                document.getElementById('acomp_nacionalidad_' + id).value = h.nacionalidad || '';
                document.getElementById('acomp_profesion_' + id).value = h.profesion || '';
                document.getElementById('acomp_objeto_' + id).value = h.objeto || '';
                
                // Fecha de nacimiento y edad
                if (h.fecha_nacimiento) {
                    document.getElementById('acomp_fecha_nacimiento_' + id).value = h.fecha_nacimiento;
                    calcularEdadAcompanante(id);
                } else if (h.edad) {
                    document.getElementById('acomp_edad_' + id).value = h.edad;
                }
                
                // Procedencia inteligente
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
                
                // Mostrar mensaje de éxito
                const acompananteDiv = document.getElementById('acompanante_' + id);
                
                // Remover mensaje anterior si existe
                const mensajeAnterior = acompananteDiv.querySelector('.mensaje-encontrado');
                if (mensajeAnterior) {
                    mensajeAnterior.remove();
                }
                
                // Agregar nuevo mensaje
                const mensaje = document.createElement('div');
                mensaje.className = 'mensaje-encontrado mt-3 p-2 bg-green-100 border border-green-300 rounded-lg text-sm text-green-700 flex items-center gap-2';
                mensaje.innerHTML = '<i class="fas fa-check-circle"></i> Datos encontrados y completados automáticamente';
                acompananteDiv.appendChild(mensaje);
                
                // Remover mensaje después de 3 segundos
                setTimeout(() => {
                    mensaje.remove();
                }, 3000);
            }
        })
        .catch(error => {
            console.log('No se encontró el acompañante en el sistema');
        });
}

// Función existente para calcular fecha de salida y precio total
function calcularFechaSalida() {
    const fechaIngreso = document.getElementById('fecha_ingreso').value;
    const nroDias = document.getElementById('nro_dias').value;
    const habitacionSelect = document.getElementById('nro_pieza');
    const precioContainer = document.getElementById('precio_total_container');
    const descuentoContainer = document.getElementById('descuento_container');
    
    if (fechaIngreso && nroDias) {
        // Parsear la fecha correctamente evitando problemas de zona horaria
        const partes = fechaIngreso.split('-');
        const fecha = new Date(parseInt(partes[0]), parseInt(partes[1]) - 1, parseInt(partes[2]));
        
        // Sumar los días
        fecha.setDate(fecha.getDate() + parseInt(nroDias));
        
        const year = fecha.getFullYear();
        const month = String(fecha.getMonth() + 1).padStart(2, '0');
        const day = String(fecha.getDate()).padStart(2, '0');
        
        document.getElementById('fecha_salida_estimada').value = `${year}-${month}-${day}`;
        
        // Actualizar el mensaje de salida
        const mensajeSalida = document.getElementById('mensaje_salida');
        if (mensajeSalida) {
            const meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
            mensajeSalida.innerHTML = `<i class="fas fa-clock mr-1"></i> Salida: ${day} de ${meses[fecha.getMonth()]} de ${year} hasta las 12:00 del mediodía`;
        }
        
        // Calcular precio total si hay habitación seleccionada
        if (habitacionSelect && habitacionSelect.value) {
            const selectedOption = habitacionSelect.options[habitacionSelect.selectedIndex];
            const precioDia = parseFloat(selectedOption.getAttribute('data-precio'));
            
            if (precioDia && !isNaN(precioDia)) {
                const precioTotal = precioDia * parseInt(nroDias);
                document.getElementById('precio_total_monto').textContent = `Bs. ${precioTotal.toFixed(2)}`;
                document.getElementById('detalle_precio').textContent = `Bs. ${precioDia.toFixed(2)} × ${nroDias} día${nroDias > 1 ? 's' : ''}`;
                precioContainer.classList.remove('hidden');
                descuentoContainer.classList.remove('hidden');
                
                // Calcular precio con descuento si existe
                const descuento = parseFloat(document.getElementById('descuento').value) || 0;
                if (descuento > 0) {
                    const precioFinal = precioTotal - descuento;
                    document.getElementById('precio_final').textContent = `Bs. ${precioFinal.toFixed(2)}`;
                    document.getElementById('precio_con_descuento').classList.remove('hidden');
                } else {
                    document.getElementById('precio_con_descuento').classList.add('hidden');
                }
            }
        }
    } else {
        // Ocultar precio si no hay datos completos
        if (precioContainer) {
            precioContainer.classList.add('hidden');
        }
        if (descuentoContainer) {
            descuentoContainer.classList.add('hidden');
        }
    }
}

// Establecer fecha de ingreso por defecto a hoy
document.addEventListener('DOMContentLoaded', function() {
    const hoy = new Date();
    const year = hoy.getFullYear();
    const month = String(hoy.getMonth() + 1).padStart(2, '0');
    const day = String(hoy.getDate()).padStart(2, '0');
    
    document.getElementById('fecha_ingreso').value = `${year}-${month}-${day}`;
});

// Función para cambiar método de pago y actualizar estilos
function cambiarMetodoPago(metodo) {
    const btnEfectivo = document.getElementById('btn_efectivo');
    const btnQr = document.getElementById('btn_qr');
    const btnPendiente = document.getElementById('btn_pendiente');
    const qrContainer = document.getElementById('qr_imagen_container');
    const pendienteContainer = document.getElementById('pendiente_aviso_container');
    
    // Estilos inactivos para todos
    const estiloInactivo = 'flex items-center justify-center gap-2 p-3 border-2 border-gray-300 bg-white rounded-xl transition-all duration-200 hover:shadow-md';
    const iconoInactivo = 'text-xl text-gray-600';
    const textoInactivo = 'font-semibold text-sm text-gray-700';
    
    // Resetear todos los botones a inactivo
    btnEfectivo.className = estiloInactivo + ' hover:border-green-300';
    btnEfectivo.querySelector('i').className = 'fas fa-money-bill-wave ' + iconoInactivo;
    btnEfectivo.querySelector('span').className = textoInactivo;
    
    btnQr.className = estiloInactivo + ' hover:border-purple-300';
    btnQr.querySelector('i').className = 'fas fa-qrcode ' + iconoInactivo;
    btnQr.querySelector('span').className = textoInactivo;
    
    btnPendiente.className = estiloInactivo + ' hover:border-orange-300';
    btnPendiente.querySelector('i').className = 'fas fa-clock ' + iconoInactivo;
    btnPendiente.querySelector('span').className = textoInactivo;
    
    // Ocultar todos los contenedores por defecto
    qrContainer.style.display = 'none';
    pendienteContainer.style.display = 'none';
    
    // Activar el botón seleccionado
    if (metodo === 'efectivo') {
        btnEfectivo.className = 'flex items-center justify-center gap-2 p-3 border-2 border-green-500 bg-green-50 rounded-xl transition-all duration-200 hover:shadow-md';
        btnEfectivo.querySelector('i').className = 'fas fa-money-bill-wave text-xl text-green-600';
        btnEfectivo.querySelector('span').className = 'font-semibold text-sm text-green-700';
    } else if (metodo === 'qr') {
        btnQr.className = 'flex items-center justify-center gap-2 p-3 border-2 border-purple-500 bg-purple-50 rounded-xl transition-all duration-200 hover:shadow-md';
        btnQr.querySelector('i').className = 'fas fa-qrcode text-xl text-purple-600';
        btnQr.querySelector('span').className = 'font-semibold text-sm text-purple-700';
        qrContainer.style.display = 'block';
    } else if (metodo === 'pendiente') {
        btnPendiente.className = 'flex items-center justify-center gap-2 p-3 border-2 border-orange-500 bg-orange-50 rounded-xl transition-all duration-200 hover:shadow-md';
        btnPendiente.querySelector('i').className = 'fas fa-clock text-xl text-orange-600';
        btnPendiente.querySelector('span').className = 'font-semibold text-sm text-orange-700';
        pendienteContainer.style.display = 'block';
    }
}

// Función para mostrar/ocultar detalles del garaje
function toggleGarajeDetalles() {
    const checkbox = document.getElementById('usa_garaje');
    const detalles = document.getElementById('garaje_detalles');
    const placaInput = document.getElementById('garaje_placa');
    const tipoInput = document.getElementById('garaje_tipo_vehiculo');
    
    if (checkbox.checked) {
        detalles.style.display = 'block';
        placaInput.required = true;
        tipoInput.required = true;
    } else {
        detalles.style.display = 'none';
        placaInput.required = false;
        tipoInput.required = false;
        placaInput.value = '';
        tipoInput.value = '';
    }
}

// Función para buscar huésped por CI
function buscarHuespedPorCI() {
    const ci = document.getElementById('ci_pasaporte').value.trim();
    
    if (!ci) {
        return;
    }
    
    const indicador = document.getElementById('busqueda_indicador');
    const mensaje = document.getElementById('busqueda_mensaje');
    
    // Mostrar indicador de carga
    indicador.classList.remove('hidden');
    mensaje.innerHTML = '';
    
    fetch('<?php echo BASE_PATH; ?>/controllers/buscar_huesped_ci.php?ci=' + encodeURIComponent(ci))
        .then(response => response.json())
        .then(data => {
            indicador.classList.add('hidden');
            
            if (data.error) {
                mensaje.innerHTML = '<span class="text-red-600">⚠️ Error al buscar</span>';
                return;
            }
            
            if (data.encontrado) {
                // Autocompletar campos
                const d = data.datos;
                
                document.getElementById('nombres_apellidos').value = d.nombres_apellidos || '';
                document.getElementById('genero').value = d.genero || '';
                document.getElementById('estado_civil').value = d.estado_civil || '';
                document.getElementById('nacionalidad').value = d.nacionalidad || '';
                document.getElementById('profesion').value = d.profesion || '';
                document.getElementById('objeto').value = d.objeto || '';
                
                // Procedencia inteligente
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
                
                // Fecha de nacimiento y edad
                if (d.fecha_nacimiento) {
                    document.getElementById('fecha_nacimiento').value = d.fecha_nacimiento;
                    calcularEdad();
                } else if (d.edad) {
                    document.getElementById('edad').value = d.edad;
                }
                
                // Mensaje de éxito con animación
                mensaje.innerHTML = '<span class="text-green-600 font-medium animate-pulse">✓ Huésped encontrado - Datos autocompletados</span>';
                
                // Quitar mensaje después de 3 segundos
                setTimeout(() => {
                    mensaje.innerHTML = '';
                }, 3000);
                
            } else {
                mensaje.innerHTML = '<span class="text-gray-500">ℹ️ Huésped nuevo - Complete los datos</span>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            indicador.classList.add('hidden');
            mensaje.innerHTML = '<span class="text-red-600">⚠️ Error de conexión</span>';
        });
}

</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
