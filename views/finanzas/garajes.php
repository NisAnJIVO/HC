<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/Garaje.php';
require_once __DIR__ . '/../../models/RegistroOcupacion.php';

// Verificar que el usuario sea administrador
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'administrador') {
    header('Location: ' . BASE_PATH . '/views/finanzas/resumen.php?error=acceso_denegado');
    exit;
}

$page_title = 'Control de Garajes';
$mensaje = '';
$tipo_mensaje = '';

// Procesar registro de garaje
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar_garaje'])) {
    try {
        $garajeModel = new Garaje();
        
        $datos = [
            'ocupacion_id' => $_POST['ocupacion_id'],
            'huesped_nombre' => $_POST['huesped_nombre'], // Se enviará desde el form
            'fecha' => $_POST['fecha'],
            'costo' => floatval($_POST['costo']),
            'observaciones' => !empty($_POST['observaciones']) ? clean_input($_POST['observaciones']) : null
        ];
        
        if ($garajeModel->registrar($datos)) {
            $mensaje = 'Uso de garaje registrado correctamente.';
            $tipo_mensaje = 'success';
            // Limpiar POST
            $_POST = [];
        } else {
            throw new Exception('Error al registrar el uso de garaje');
        }
    } catch (Exception $e) {
        $mensaje = 'Error: ' . $e->getMessage();
        $tipo_mensaje = 'danger';
        error_log("Error en registro de garaje: " . $e->getMessage());
    }
}

// Obtener registros
$garajeModel = new Garaje();
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-d', strtotime('-30 days'));
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');
$registros = $garajeModel->obtenerPorFechas($fecha_inicio, $fecha_fin);
$resumen = $garajeModel->obtenerResumen($fecha_inicio, $fecha_fin);

// Obtener ocupaciones activas para el select
$registroModel = new RegistroOcupacion();
$ocupaciones_activas = $registroModel->obtenerActivos();

include __DIR__ . '/../../includes/header.php';
?>

<style>
.glass-card {
    background: rgba(255, 255, 255, 0.7);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.8);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.04);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.dark .glass-card {
    background: rgba(23, 23, 23, 0.7);
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
}
</style>

<!-- Page Header -->
<div class="mb-8">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-4xl font-bold text-noir dark:text-white mb-2">Control de Garajes</h1>
            <p class="text-sm md:text-base text-gray-500 dark:text-gray-400">Gestión de espacios de estacionamiento para huéspedes</p>
        </div>
        <a href="<?php echo BASE_PATH; ?>/index.php" class="px-3 md:px-6 py-2 md:py-3 border border-gray-300 dark:border-gray-700 rounded-lg md:rounded-xl text-gray-700 dark:text-gray-300 text-sm md:text-base font-medium hover:bg-mist dark:hover:bg-gray-800 transition-all duration-200 text-center">
            ← Volver
        </a>
    </div>
</div>

<!-- Alert Messages -->
<?php if ($mensaje): ?>
    <div class="mb-8 animate-fade-in">
        <div class="bg-<?php echo $tipo_mensaje === 'success' ? 'green' : 'red'; ?>-50 dark:bg-<?php echo $tipo_mensaje === 'success' ? 'green' : 'red'; ?>-900/20 border border-<?php echo $tipo_mensaje === 'success' ? 'green' : 'red'; ?>-200 dark:border-<?php echo $tipo_mensaje === 'success' ? 'green' : 'red'; ?>-800 rounded-xl p-4">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <?php if ($tipo_mensaje === 'success'): ?>
                        <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                    <?php else: ?>
                        <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                        </svg>
                    <?php endif; ?>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-<?php echo $tipo_mensaje === 'success' ? 'green' : 'red'; ?>-800 dark:text-<?php echo $tipo_mensaje === 'success' ? 'green' : 'red'; ?>-200">
                        <?php echo $mensaje; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 lg:gap-8">
    <!-- Formulario de Registro -->
    <div class="lg:col-span-1">
        <div class="glass-card p-4 sm:p-6 rounded-xl sm:rounded-2xl lg:sticky lg:top-4">
            <div class="flex items-center gap-2 sm:gap-3 mb-4 sm:mb-6">
                <div class="w-10 h-10 sm:w-12 sm:h-12 bg-gradient-to-br from-orange-500 to-orange-600 rounded-lg sm:rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"></path>
                    </svg>
                </div>
                <div>
                    <h2 class="text-base sm:text-xl font-semibold text-noir dark:text-white">Registrar Uso</h2>
                    <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">Asignar garaje a huésped</p>
                </div>
            </div>
            
            <form method="POST" action="" class="space-y-3 sm:space-y-4" id="formGaraje">
                <div class="space-y-1.5 sm:space-y-2">
                    <label class="block text-xs sm:text-sm font-semibold text-noir dark:text-white">
                        Huésped <span class="text-red-500">*</span>
                    </label>
                    <select name="ocupacion_id" id="ocupacion_id" required
                            class="w-full px-3 py-2.5 sm:px-4 sm:py-3 text-sm sm:text-base border border-gray-300 dark:border-gray-700 rounded-lg sm:rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all duration-200 text-noir dark:text-white bg-white dark:bg-gray-800">
                        <option value="">Seleccione un huésped...</option>
                        <?php foreach ($ocupaciones_activas as $ocu): ?>
                            <option value="<?php echo $ocu['id']; ?>" data-nombre="<?php echo htmlspecialchars($ocu['nombres_apellidos']); ?>">
                                Hab. <?php echo $ocu['nro_pieza']; ?> - <?php echo htmlspecialchars($ocu['nombres_apellidos']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" name="huesped_nombre" id="huesped_nombre">
                </div>
                
                <div>
                    <label class="block text-xs sm:text-sm font-semibold text-noir dark:text-white mb-1.5 sm:mb-2">
                        Fecha <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="fecha" 
                           value="<?php echo date('Y-m-d'); ?>"
                           required
                           class="w-full px-3 py-2.5 sm:px-4 sm:py-3 text-sm sm:text-base border border-gray-300 dark:border-gray-700 rounded-lg sm:rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all duration-200 text-noir dark:text-white bg-white dark:bg-gray-800">
                </div>
                
                <div>
                    <label class="block text-xs sm:text-sm font-semibold text-noir dark:text-white mb-1.5 sm:mb-2">
                        Costo (Bs.) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" step="0.01" name="costo" 
                           value="10.00"
                           min="0" required
                           class="w-full px-3 py-2.5 sm:px-4 sm:py-3 text-sm sm:text-base border border-gray-300 dark:border-gray-700 rounded-lg sm:rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all duration-200 text-noir dark:text-white bg-white dark:bg-gray-800">
                </div>
                
                <div>
                    <label class="block text-xs sm:text-sm font-semibold text-noir dark:text-white mb-1.5 sm:mb-2">
                        Observaciones
                    </label>
                    <textarea name="observaciones" rows="2"
                              class="w-full px-3 py-2.5 sm:px-4 sm:py-3 text-sm sm:text-base border border-gray-300 dark:border-gray-700 rounded-lg sm:rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all duration-200 text-noir dark:text-white placeholder-gray-400 dark:placeholder-gray-500 bg-white dark:bg-gray-800"
                              placeholder="Detalles del vehículo (Placa, Color, etc.)"></textarea>
                </div>
                
                <button type="submit" name="registrar_garaje" class="w-full px-5 py-2.5 sm:px-6 sm:py-3.5 bg-gradient-to-r from-orange-600 to-orange-700 text-white text-sm sm:text-base rounded-lg sm:rounded-xl font-medium hover:from-orange-700 hover:to-orange-800 transition-all duration-200 shadow-md sm:shadow-lg hover:shadow-lg sm:hover:shadow-xl">
                    Registrar Uso
                </button>
            </form>
        </div>
    </div>
    
    <!-- Lista de Registros -->
    <div class="lg:col-span-2">
        <div class="glass-card p-4 sm:p-6 rounded-xl sm:rounded-2xl">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                <div>
                    <h2 class="text-base sm:text-xl font-semibold text-noir dark:text-white">Historial de Uso</h2>
                    <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                        Total: <span class="font-bold text-orange-600"><?php echo $resumen['cantidad']; ?> registros</span>
                        • Ingresos: <span class="font-bold text-green-600">Bs. <?php echo formatMoney($resumen['total']); ?></span>
                    </p>
                </div>
            </div>
            
            <!-- Filtros -->
            <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 p-4 bg-mist dark:bg-gray-800 rounded-xl">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Fecha Inicio</label>
                    <input type="date" name="fecha_inicio" 
                           value="<?php echo $fecha_inicio; ?>"
                           class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-noir focus:border-transparent bg-white dark:bg-gray-900 text-noir dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Fecha Fin</label>
                    <input type="date" name="fecha_fin" 
                           value="<?php echo $fecha_fin; ?>"
                           class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-noir focus:border-transparent bg-white dark:bg-gray-900 text-noir dark:text-white">
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full px-4 py-2.5 bg-noir dark:bg-gray-800 text-white font-medium rounded-lg hover:bg-gray-800 dark:hover:bg-gray-700 transition-all duration-200">
                        Filtrar
                    </button>
                </div>
            </form>
            
            <!-- Tabla -->
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-black border-b-2 border-gray-200 dark:border-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Fecha</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Huésped</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Observaciones</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Costo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        <?php foreach ($registros as $reg): ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-300"><?php echo formatDate($reg['fecha']); ?></td>
                                <td class="px-4 py-3 text-sm font-medium text-noir dark:text-white">
                                    <?php echo htmlspecialchars($reg['huesped_nombre']); ?>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                    <?php echo $reg['observaciones'] ? htmlspecialchars($reg['observaciones']) : '-'; ?>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <span class="text-sm font-bold text-green-600">Bs. <?php echo formatMoney($reg['costo']); ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($registros)): ?>
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                    <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"></path>
                                    </svg>
                                    No hay registros de garaje en este período
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('ocupacion_id').addEventListener('change', function() {
    var selectedOption = this.options[this.selectedIndex];
    var nombre = selectedOption.getAttribute('data-nombre');
    document.getElementById('huesped_nombre').value = nombre;
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
