<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/Huesped.php';
require_once __DIR__ . '/../../models/Habitacion.php';
require_once __DIR__ . '/../../models/RegistroOcupacion.php';

$page_title = 'Editar Huésped';
$mensaje = '';
$tipo_mensaje = '';

// Verificar que se recibió el ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: ' . BASE_PATH . '/views/huespedes/activos.php');
    exit;
}

$ocupacion_id = (int)$_GET['id'];

// Obtener datos de la ocupación y huésped
$registroModel = new RegistroOcupacion();
$huespedModel = new Huesped();
$habitacionModel = new Habitacion();

$ocupacion = $registroModel->obtenerPorId($ocupacion_id);

if (!$ocupacion) {
    header('Location: ' . BASE_PATH . '/views/huespedes/activos.php?error=no_encontrado');
    exit;
}

$huesped = $huespedModel->obtenerPorId($ocupacion['huesped_id']);

// Procesar formulario de actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Actualizar datos del huésped
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
        
        if ($huespedModel->actualizar($ocupacion['huesped_id'], $datos_huesped)) {
            // Actualizar datos de la ocupación
            $datos_ocupacion = [
                'nro_pieza' => clean_input($_POST['nro_pieza']),
                'prox_destino' => !empty($_POST['prox_destino']) ? clean_input($_POST['prox_destino']) : null,
                'via_ingreso' => !empty($_POST['via_ingreso']) ? clean_input($_POST['via_ingreso']) : null,
                'nro_dias' => (int)$_POST['nro_dias']
            ];
            
            if ($registroModel->actualizar($ocupacion_id, $datos_ocupacion)) {
                $mensaje = 'Datos actualizados correctamente';
                $tipo_mensaje = 'success';
                
                // Recargar datos actualizados
                $ocupacion = $registroModel->obtenerPorId($ocupacion_id);
                $huesped = $huespedModel->obtenerPorId($ocupacion['huesped_id']);
            } else {
                throw new Exception('Error al actualizar la ocupación');
            }
        } else {
            throw new Exception('Error al actualizar los datos del huésped');
        }
    } catch (Exception $e) {
        $mensaje = 'Error: ' . $e->getMessage();
        $tipo_mensaje = 'danger';
    }
}

// Obtener habitaciones disponibles (incluyendo la actual)
$habitaciones_disponibles = $habitacionModel->obtenerDisponibles();
$habitacion_actual = $habitacionModel->obtenerPorNumero($ocupacion['nro_pieza']);

include __DIR__ . '/../../includes/header.php';
?>

<!-- Page Header -->
<div class="mb-6 sm:mb-8">
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
        <div class="flex-1">
            <h1 class="text-2xl sm:text-4xl font-bold text-noir mb-1 sm:mb-2">Editar Registro de Huésped</h1>
            <p class="text-sm sm:text-base text-gray-500">Modificar información de la estadía actual</p>
        </div>
        <a href="<?php echo BASE_PATH; ?>/views/huespedes/activos.php" class="px-4 py-2 sm:px-6 sm:py-3 text-sm sm:text-base border-2 border-gray-300 rounded-xl text-gray-700 font-medium hover:bg-mist transition-all duration-200 text-center">
            ← Volver
        </a>
    </div>
</div>

<!-- Alert Messages -->
<?php if ($mensaje): ?>
    <div class="mb-8 animate-fade-in">
        <div class="bg-<?php echo $tipo_mensaje === 'success' ? 'green' : 'red'; ?>-50 border-2 border-<?php echo $tipo_mensaje === 'success' ? 'green' : 'red'; ?>-300 rounded-xl p-4">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <?php if ($tipo_mensaje === 'success'): ?>
                        <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                    <?php else: ?>
                        <svg class="w-6 h-6 text-red-600" fill="currentColor" viewBox="0 0 20 20">
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
    <div class="bg-white rounded-2xl border-2 border-blue-300 shadow-lg overflow-hidden">
        <div class="px-8 py-6 border-b-2 border-blue-300 bg-gradient-to-r from-blue-50 to-indigo-50">
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
        
        <div class="p-8 space-y-6 bg-blue-50/20">
            <!-- Fila 1: CI y Nombres -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                    <label class="block text-sm font-bold text-gray-800 mb-2">
                        CI o Pasaporte <span class="text-red-600 text-base">*</span>
                    </label>
                    <input 
                        type="text" 
                        name="ci_pasaporte" 
                        value="<?php echo htmlspecialchars($huesped['ci_pasaporte']); ?>"
                        required
                        class="w-full px-4 py-4 border-2 border-gray-400 rounded-xl focus:ring-4 focus:ring-blue-300 focus:border-blue-500 transition-all duration-200 text-gray-900 font-medium placeholder-gray-400 hover:border-gray-500 shadow-sm"
                        placeholder="Ingrese CI o Pasaporte"
                    >
                </div>
                
                <div class="space-y-2">
                    <label class="block text-sm font-bold text-gray-800 mb-2">
                        Nombres y Apellidos <span class="text-red-600 text-base">*</span>
                    </label>
                    <input 
                        type="text" 
                        name="nombres_apellidos" 
                        value="<?php echo htmlspecialchars($huesped['nombres_apellidos']); ?>"
                        required
                        class="w-full px-4 py-4 border-2 border-gray-400 rounded-xl focus:ring-4 focus:ring-blue-300 focus:border-blue-500 transition-all duration-200 text-gray-900 font-medium placeholder-gray-400 hover:border-gray-500 shadow-sm"
                        placeholder="Nombre completo del huésped"
                    >
                </div>
            </div>
            
            <!-- Fila 2: Género, Edad, Estado Civil, Nacionalidad -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="space-y-2">
                    <label class="block text-sm font-bold text-gray-800 mb-2">
                        Género <span class="text-red-600 text-base">*</span>
                    </label>
                    <select 
                        name="genero" 
                        required
                        class="w-full px-4 py-4 border-2 border-gray-400 rounded-xl focus:ring-4 focus:ring-blue-300 focus:border-blue-500 transition-all duration-200 text-gray-900 font-medium appearance-none bg-white hover:border-gray-500 shadow-sm"
                    >
                        <option value="">Seleccione</option>
                        <option value="M" <?php echo ($huesped['genero'] == 'M') ? 'selected' : ''; ?>>Masculino</option>
                        <option value="F" <?php echo ($huesped['genero'] == 'F') ? 'selected' : ''; ?>>Femenino</option>
                    </select>
                </div>
                
                <div class="space-y-2">
                    <label class="block text-sm font-bold text-gray-800 mb-2">
                        Edad <span class="text-red-600 text-base">*</span>
                    </label>
                    <input 
                        type="number" 
                        name="edad" 
                        value="<?php echo htmlspecialchars($huesped['edad']); ?>"
                        required
                        min="1"
                        max="120"
                        class="w-full px-4 py-4 border-2 border-gray-400 rounded-xl focus:ring-4 focus:ring-blue-300 focus:border-blue-500 transition-all duration-200 text-gray-900 font-medium hover:border-gray-500 shadow-sm"
                        placeholder="Edad"
                    >
                </div>
                
                <div class="space-y-2">
                    <label class="block text-sm font-bold text-gray-800 mb-2">Estado Civil</label>
                    <select 
                        name="estado_civil"
                        class="w-full px-4 py-4 border-2 border-gray-400 rounded-xl focus:ring-4 focus:ring-blue-300 focus:border-blue-500 transition-all duration-200 text-gray-900 font-medium appearance-none bg-white hover:border-gray-500 shadow-sm"
                    >
                        <option value="">Seleccione</option>
                        <option value="S" <?php echo ($huesped['estado_civil'] == 'S') ? 'selected' : ''; ?>>S</option>
                        <option value="C" <?php echo ($huesped['estado_civil'] == 'C') ? 'selected' : ''; ?>>C</option>
                        <option value="D" <?php echo ($huesped['estado_civil'] == 'D') ? 'selected' : ''; ?>>D</option>
                        <option value="V" <?php echo ($huesped['estado_civil'] == 'V') ? 'selected' : ''; ?>>V</option>
                    </select>
                </div>
                
                <div class="space-y-2">
                    <label class="block text-sm font-bold text-gray-800 mb-2">
                        Nacionalidad <span class="text-red-600 text-base">*</span>
                    </label>
                    <input 
                        type="text" 
                        name="nacionalidad" 
                        required
                        value="<?php echo htmlspecialchars($huesped['nacionalidad']); ?>"
                        class="w-full px-4 py-4 border-2 border-gray-400 rounded-xl focus:ring-4 focus:ring-blue-300 focus:border-blue-500 transition-all duration-200 text-gray-900 font-medium placeholder-gray-400 hover:border-gray-500 shadow-sm"
                        placeholder="País"
                    >
                </div>
            </div>
            
            <!-- Fila 3: Profesión, Objeto, Procedencia -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="space-y-2">
                    <label class="block text-sm font-bold text-gray-800 mb-2">Profesión</label>
                    <input 
                        type="text" 
                        name="profesion"
                        value="<?php echo htmlspecialchars($huesped['profesion'] ?? ''); ?>"
                        class="w-full px-4 py-4 border-2 border-gray-400 rounded-xl focus:ring-4 focus:ring-blue-300 focus:border-blue-500 transition-all duration-200 text-gray-900 font-medium placeholder-gray-400 hover:border-gray-500 shadow-sm"
                        placeholder="Ocupación laboral"
                    >
                </div>
                
                <div class="space-y-2">
                    <label class="block text-sm font-bold text-gray-800 mb-2">Objeto del Viaje</label>
                    <select 
                        name="objeto"
                        class="w-full px-4 py-4 border-2 border-gray-400 rounded-xl focus:ring-4 focus:ring-blue-300 focus:border-blue-500 transition-all duration-200 text-gray-900 font-medium appearance-none bg-white hover:border-gray-500 shadow-sm"
                    >
                        <option value="">Seleccione</option>
                        <option value="Turismo" <?php echo ($huesped['objeto'] == 'Turismo') ? 'selected' : ''; ?>>Turismo</option>
                        <option value="Negocios" <?php echo ($huesped['objeto'] == 'Negocios') ? 'selected' : ''; ?>>Negocios</option>
                        <option value="Salud" <?php echo ($huesped['objeto'] == 'Salud') ? 'selected' : ''; ?>>Salud</option>
                        <option value="Educación" <?php echo ($huesped['objeto'] == 'Educación') ? 'selected' : ''; ?>>Educación</option>
                        <option value="Familiar" <?php echo ($huesped['objeto'] == 'Familiar') ? 'selected' : ''; ?>>Familiar</option>
                        <option value="Tránsito" <?php echo ($huesped['objeto'] == 'Tránsito') ? 'selected' : ''; ?>>Tránsito</option>
                        <option value="Paso" <?php echo ($huesped['objeto'] == 'Paso') ? 'selected' : ''; ?>>Paso</option>
                        <option value="Otro" <?php echo ($huesped['objeto'] == 'Otro') ? 'selected' : ''; ?>>Otro</option>
                    </select>
                </div>
                
                <div class="space-y-2">
                    <label class="block text-sm font-bold text-gray-800 mb-2">Procedencia</label>
                    <input 
                        type="text" 
                        name="procedencia"
                        value="<?php echo htmlspecialchars($huesped['procedencia'] ?? ''); ?>"
                        class="w-full px-4 py-4 border-2 border-gray-400 rounded-xl focus:ring-4 focus:ring-blue-300 focus:border-blue-500 transition-all duration-200 text-gray-900 font-medium placeholder-gray-400 hover:border-gray-500 shadow-sm"
                        placeholder="Ciudad de origen"
                    >
                </div>
            </div>
        </div>
    </div>
    
    <!-- Sección: Detalles de Estadía -->
    <div class="bg-white rounded-2xl border-2 border-green-300 shadow-lg overflow-hidden">
        <div class="px-8 py-6 border-b-2 border-green-300 bg-gradient-to-r from-green-50 to-emerald-50">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-green-600 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-gray-900">Detalles de Estadía</h2>
                    <p class="text-sm text-gray-600 mt-0.5">Información de la habitación y fechas</p>
                </div>
            </div>
        </div>
        
        <div class="p-8 space-y-6 bg-green-50/20">
            <!-- Fila 1: Habitación y Próximo Destino -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                    <label class="block text-sm font-bold text-gray-800 mb-2">
                        Habitación <span class="text-red-600 text-base">*</span>
                    </label>
                    <select 
                        name="nro_pieza" 
                        required
                        class="w-full px-4 py-4 border-2 border-gray-400 rounded-xl focus:ring-4 focus:ring-green-300 focus:border-green-500 transition-all duration-200 text-gray-900 font-medium appearance-none bg-white hover:border-gray-500 shadow-sm"
                    >
                        <option value="<?php echo $ocupacion['nro_pieza']; ?>" selected>
                            Habitación <?php echo $ocupacion['nro_pieza']; ?> (Actual)
                        </option>
                        <?php foreach ($habitaciones_disponibles as $hab): ?>
                            <?php if ($hab['numero'] != $ocupacion['nro_pieza']): ?>
                                <option value="<?php echo $hab['numero']; ?>">
                                    Habitación <?php echo $hab['numero']; ?> - <?php echo $hab['tipo']; ?> - Bs. <?php echo number_format($hab['precio_dia'], 2); ?>/día
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="space-y-2">
                    <label class="block text-sm font-bold text-gray-800 mb-2">Próximo Destino</label>
                    <input 
                        type="text" 
                        name="prox_destino"
                        value="<?php echo htmlspecialchars($ocupacion['prox_destino'] ?? ''); ?>"
                        class="w-full px-4 py-4 border-2 border-gray-400 rounded-xl focus:ring-4 focus:ring-green-300 focus:border-green-500 transition-all duration-200 text-gray-900 font-medium placeholder-gray-400 hover:border-gray-500 shadow-sm"
                        placeholder="Ciudad o país de destino"
                    >
                </div>
            </div>
            
            <!-- Fila 2: Vía de Ingreso y Días -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                    <label class="block text-sm font-bold text-gray-800 mb-2">Vía de Ingreso</label>
                    <select 
                        name="via_ingreso"
                        class="w-full px-4 py-4 border-2 border-gray-400 rounded-xl focus:ring-4 focus:ring-green-300 focus:border-green-500 transition-all duration-200 text-gray-900 font-medium appearance-none bg-white hover:border-gray-500 shadow-sm"
                    >
                        <option value="">Seleccione</option>
                        <option value="T" <?php echo ($ocupacion['via_ingreso'] == 'T') ? 'selected' : ''; ?>>Terrestre</option>
                        <option value="A" <?php echo ($ocupacion['via_ingreso'] == 'A') ? 'selected' : ''; ?>>Aéreo</option>
                    </select>
                </div>
                
                <div class="space-y-2">
                    <label class="block text-sm font-bold text-gray-800 mb-2">
                        Número de Días <span class="text-red-600 text-base">*</span>
                    </label>
                    <input 
                        type="number" 
                        name="nro_dias" 
                        value="<?php echo htmlspecialchars($ocupacion['nro_dias']); ?>"
                        min="1"
                        required
                        class="w-full px-4 py-4 border-2 border-gray-400 rounded-xl focus:ring-4 focus:ring-green-300 focus:border-green-500 transition-all duration-200 text-gray-900 font-medium placeholder-gray-400 hover:border-gray-500 shadow-sm"
                        placeholder="Días"
                    >
                </div>
            </div>
            
            <!-- Info de Fecha de Ingreso (solo lectura) -->
            <div class="bg-blue-50 border-2 border-blue-300 rounded-xl p-4">
                <div class="flex items-center gap-2 text-blue-800">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="text-sm font-medium">
                        Fecha de ingreso: <strong><?php echo date('d/m/Y', strtotime($ocupacion['fecha_ingreso'])); ?></strong> (no se puede modificar)
                    </span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Action Buttons -->
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 pt-6">
        <a href="<?php echo BASE_PATH; ?>/views/huespedes/activos.php" class="px-5 py-2.5 border-2 border-gray-300 rounded-lg text-gray-700 text-sm font-medium hover:bg-mist transition-all duration-200 text-center">
            Cancelar
        </a>
        <button 
            type="submit" 
            class="px-8 py-3 bg-blue-600 text-white text-base font-bold rounded-xl hover:bg-blue-700 transition-all duration-200 shadow-lg hover:shadow-xl flex items-center justify-center gap-2"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            Guardar Cambios
        </button>
    </div>
</form>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
