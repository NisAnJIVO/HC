<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/Finanzas.php';

// Verificar que el usuario sea administrador
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'administrador') {
    header('Location: ' . BASE_PATH . '/views/finanzas/resumen.php?error=acceso_denegado');
    exit;
}

$page_title = 'Registro de Egresos';
$mensaje = '';
$tipo_mensaje = '';

// Procesar registro de egreso
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar_egreso'])) {
    try {
        $finanzasModel = new Finanzas();
        
        $datos = [
            'concepto' => clean_input($_POST['concepto']),
            'monto' => floatval($_POST['monto']),
            'categoria' => !empty($_POST['categoria']) ? clean_input($_POST['categoria']) : null,
            'fecha' => $_POST['fecha'],
            'observaciones' => !empty($_POST['observaciones']) ? clean_input($_POST['observaciones']) : null
        ];
        
        if ($finanzasModel->registrarEgreso($datos)) {
            $mensaje = 'Egreso registrado correctamente.';
            $tipo_mensaje = 'success';
            // Limpiar POST para que el formulario se resetee
            $_POST = [];
        } else {
            throw new Exception('Error al registrar el egreso en la base de datos');
        }
    } catch (Exception $e) {
        $mensaje = 'Error: ' . $e->getMessage();
        $tipo_mensaje = 'danger';
        error_log("Error en registro de egreso: " . $e->getMessage());
    }
}

// Obtener egresos
$finanzasModel = new Finanzas();
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-d', strtotime('-30 days'));
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');
$egresos = $finanzasModel->obtenerEgresos($fecha_inicio, $fecha_fin);

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
            <h1 class="text-2xl md:text-4xl font-bold text-noir dark:text-white mb-2">Egresos - Salidas de Caja</h1>
            <p class="text-sm md:text-base text-gray-500 dark:text-gray-400">Registra gastos externos y de cafetería</p>
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
                <div class="w-10 h-10 sm:w-12 sm:h-12 bg-gradient-to-br from-red-500 to-red-600 rounded-lg sm:rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div>
                    <h2 class="text-base sm:text-xl font-semibold text-noir dark:text-white">Nueva Salida</h2>
                    <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">Registro de egresos</p>
                </div>
            </div>
            
            <form method="POST" action="" class="space-y-3 sm:space-y-4">
                <div>
                    <label class="block text-xs sm:text-sm font-semibold text-noir dark:text-white mb-1.5 sm:mb-2">
                        Tipo de Egreso <span class="text-red-500">*</span>
                    </label>
                    <select name="categoria" required class="w-full px-3 py-2.5 sm:px-4 sm:py-3 text-sm sm:text-base border border-gray-300 dark:border-gray-700 rounded-lg sm:rounded-xl focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all duration-200 text-noir dark:text-white appearance-none bg-white dark:bg-gray-800">
                        <option value="">Selecciona el tipo</option>
                        <option value="Externo" <?php echo (isset($_POST['categoria']) && $_POST['categoria'] == 'Externo') ? 'selected' : ''; ?>>Gastos Externos</option>
                        <option value="Cafetería" <?php echo (isset($_POST['categoria']) && $_POST['categoria'] == 'Cafetería') ? 'selected' : ''; ?>>Gastos de Cafetería</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-xs sm:text-sm font-semibold text-noir dark:text-white mb-1.5 sm:mb-2">
                        Descripción <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="concepto" 
                           value="<?php echo isset($_POST['concepto']) ? htmlspecialchars($_POST['concepto']) : ''; ?>"
                           required
                           class="w-full px-3 py-2.5 sm:px-4 sm:py-3 text-sm sm:text-base border border-gray-300 dark:border-gray-700 rounded-lg sm:rounded-xl focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all duration-200 text-noir dark:text-white placeholder-gray-400 dark:placeholder-gray-500 bg-white dark:bg-gray-800"
                           placeholder="Descripción breve...">
                </div>
                
                <div>
                    <label class="block text-xs sm:text-sm font-semibold text-noir dark:text-white mb-1.5 sm:mb-2">
                        Monto (Bs.) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" step="0.01" name="monto" 
                           value="<?php echo isset($_POST['monto']) ? htmlspecialchars($_POST['monto']) : ''; ?>"
                           min="0.01" required
                           class="w-full px-3 py-2.5 sm:px-4 sm:py-3 text-sm sm:text-base border border-gray-300 dark:border-gray-700 rounded-lg sm:rounded-xl focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all duration-200 text-noir dark:text-white placeholder-gray-400 dark:placeholder-gray-500 bg-white dark:bg-gray-800"
                           placeholder="0.00">
                </div>
                
                <input type="hidden" name="fecha" value="<?php echo date('Y-m-d'); ?>">
                <input type="hidden" name="observaciones" value="">
                
                <button type="submit" name="registrar_egreso" class="w-full px-5 py-2.5 sm:px-6 sm:py-3.5 bg-gradient-to-r from-red-600 to-red-700 text-white text-sm sm:text-base rounded-lg sm:rounded-xl font-medium hover:from-red-700 hover:to-red-800 transition-all duration-200 shadow-md sm:shadow-lg hover:shadow-lg sm:hover:shadow-xl">
                    Registrar Egreso
                </button>
            </form>
        </div>
    </div>
    
    <!-- Lista de Egresos -->
    <div class="lg:col-span-2">
        <div class="glass-card p-4 sm:p-6 rounded-xl sm:rounded-2xl">
            <div class="flex items-center justify-between mb-4 sm:mb-6">
                <div>
                    <h2 class="text-base sm:text-xl font-semibold text-noir dark:text-white">Lista de Egresos</h2>
                    <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">Historial registrado</p>
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
                        <button type="submit" class="w-full px-4 py-2.5 bg-noir dark:bg-gray-800 text-white text-sm sm:text-base font-medium rounded-lg hover:bg-gray-800 dark:hover:bg-gray-700 transition-all duration-200">
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
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Concepto</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Categoría</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Monto</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                            <?php 
                            $total = 0;
                            foreach ($egresos as $egr): 
                                $total += $egr['monto'];
                            ?>
                                    <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-300"><?php echo formatDate($egr['fecha']); ?></td>
                                    <td class="px-4 py-3 text-sm font-medium text-noir dark:text-white"><?php echo htmlspecialchars($egr['concepto']); ?></td>
                                    <td class="px-4 py-3">
                                        <?php if ($egr['categoria']): ?>
                                            <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold
                                                <?php 
                                                if ($egr['categoria'] == 'Suministros') echo 'bg-blue-100 text-blue-800';
                                                elseif ($egr['categoria'] == 'Servicios') echo 'bg-yellow-100 text-yellow-800';
                                                elseif ($egr['categoria'] == 'Mantenimiento') echo 'bg-orange-100 text-orange-800';
                                                elseif ($egr['categoria'] == 'Personal') echo 'bg-purple-100 text-purple-800';
                                                else echo 'bg-gray-100 text-gray-800';
                                                ?>">
                                                <?php echo htmlspecialchars($egr['categoria']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-gray-400">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <span class="text-sm font-bold text-red-600">Bs. <?php echo formatMoney($egr['monto']); ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($egresos)): ?>
                                <tr>
                                    <td colspan="4" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                        <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        No hay egresos registrados en este período
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot class="bg-noir dark:bg-black text-white">
                            <tr>
                                <th colspan="3" class="px-4 py-4 text-right text-sm font-semibold uppercase tracking-wider">TOTAL:</th>
                                <th class="px-4 py-4 text-right text-lg font-bold">Bs. <?php echo formatMoney($total); ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
