<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/Finanzas.php';
require_once __DIR__ . '/../../models/RegistroOcupacion.php';

// Verificar que el usuario sea administrador
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'administrador') {
    header('Location: ' . BASE_PATH . '/views/finanzas/resumen.php?error=acceso_denegado');
    exit;
}

$page_title = 'Pagos QR';
$mensaje = '';
$tipo_mensaje = '';

// Procesar registro de pago QR
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar_pago_qr'])) {
    $finanzasModel = new Finanzas();
    
    $tipo = $_POST['tipo_pago'] ?? 'huesped';
    
    $datos = [
        'ocupacion_id' => ($tipo === 'huesped' && !empty($_POST['ocupacion_id'])) ? $_POST['ocupacion_id'] : null,
        'monto' => floatval($_POST['monto']),
        'fecha' => $_POST['fecha'],
        'numero_transaccion' => clean_input($_POST['numero_transaccion']),
        'observaciones' => clean_input($_POST['observaciones']),
        'concepto' => ($tipo === 'externo') ? clean_input($_POST['concepto']) : null,
        'tipo' => $tipo
    ];
    
    // Registrar en tabla pagos_qr
    if ($finanzasModel->registrarPagoQR($datos)) {
        // También registrar como ingreso para que aparezca en reportes
        $concepto_ingreso = '';
        if ($tipo === 'externo') {
            $concepto_ingreso = 'Cobro QR externo: ' . $datos['concepto'];
        } else {
            // Obtener nombre del huésped si existe
            if ($datos['ocupacion_id']) {
                $registroModel = new RegistroOcupacion();
                $sql = "SELECT h.nombres_apellidos, hab.numero 
                        FROM registro_ocupacion ro 
                        INNER JOIN huespedes h ON ro.huesped_id = h.id
                        INNER JOIN habitaciones hab ON ro.habitacion_id = hab.id
                        WHERE ro.id = :id";
                $stmt = $registroModel->conn->prepare($sql);
                $stmt->execute([':id' => $datos['ocupacion_id']]);
                $ocupacion = $stmt->fetch();
                if ($ocupacion) {
                    $concepto_ingreso = 'Pago QR - Hab. ' . $ocupacion['numero'] . ' - ' . $ocupacion['nombres_apellidos'];
                } else {
                    $concepto_ingreso = 'Pago QR - Huésped';
                }
            } else {
                $concepto_ingreso = 'Pago QR sin asociar';
            }
        }
        
        // Registrar ingreso
        $datos_ingreso = [
            'concepto' => $concepto_ingreso,
            'monto' => $datos['monto'],
            'fecha' => $datos['fecha'],
            'metodo_pago' => 'qr',
            'categoria' => $tipo === 'externo' ? 'otros' : 'alojamiento',
            'ocupacion_id' => $datos['ocupacion_id']
        ];
        $finanzasModel->registrarIngreso($datos_ingreso);
        
        $mensaje = 'Pago QR registrado correctamente. Total: Bs. ' . number_format($datos['monto'], 2);
        $tipo_mensaje = 'success';
        $_POST = []; // Limpiar formulario
    } else {
        $mensaje = 'Error al registrar pago QR.';
        $tipo_mensaje = 'danger';
    }
}

// Obtener pagos QR
$finanzasModel = new Finanzas();
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-d', strtotime('-30 days'));
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');
$pagos_qr = $finanzasModel->obtenerPagosQR($fecha_inicio, $fecha_fin);

// Obtener ocupaciones activas
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

.stat-badge {
    background: linear-gradient(135deg, rgba(147, 51, 234, 0.1), rgba(126, 34, 206, 0.1));
    backdrop-filter: blur(10px);
    border: 1px solid rgba(147, 51, 234, 0.2);
}

.dark .stat-badge {
    background: linear-gradient(135deg, rgba(147, 51, 234, 0.2), rgba(126, 34, 206, 0.2));
    border: 1px solid rgba(147, 51, 234, 0.3);
}
</style>

<!-- Hero Section -->
<div class="mb-8">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-4xl font-bold text-noir dark:text-white mb-2">Pagos QR</h1>
            <p class="text-sm md:text-base text-gray-500 dark:text-gray-400">Gestión de transferencias bancarias directas</p>
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

<!-- Grid Principal -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 lg:gap-6">
    
    <!-- Formulario Registro (1/3) -->
    <div class="lg:col-span-1">
        <div class="glass-card p-4 sm:p-6 rounded-xl sm:rounded-2xl">
            <div class="flex items-center gap-2 sm:gap-3 mb-4 sm:mb-6">
                <div class="w-10 h-10 sm:w-12 sm:h-12 bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg sm:rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path>
                    </svg>
                </div>
                <div>
                    <h2 class="text-base sm:text-xl font-semibold text-noir dark:text-white">Registrar Pago</h2>
                    <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">Transferencia QR</p>
                </div>
            </div>
            
            <form method="POST" action="" class="space-y-3 sm:space-y-4">
                <!-- Tipo de Pago -->
                <div class="p-4 bg-gradient-to-r from-purple-50 to-blue-50 dark:from-purple-900/20 dark:to-blue-900/20 rounded-lg border border-purple-200 dark:border-purple-700">
                    <label class="block text-xs sm:text-sm font-semibold text-noir dark:text-white mb-3">
                        Tipo de Cobro
                    </label>
                    <div class="flex gap-3">
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" name="tipo_pago" value="huesped" checked 
                                   class="peer sr-only" 
                                   onchange="toggleTipoPago()">
                            <div class="p-3 text-center border-2 border-gray-300 dark:border-gray-600 rounded-lg peer-checked:border-purple-600 peer-checked:bg-purple-50 dark:peer-checked:bg-purple-900/30 transition-all">
                                <i class="fas fa-user text-lg mb-1 text-purple-600"></i>
                                <p class="text-xs font-medium text-gray-700 dark:text-gray-300">Huésped</p>
                            </div>
                        </label>
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" name="tipo_pago" value="externo" 
                                   class="peer sr-only"
                                   onchange="toggleTipoPago()">
                            <div class="p-3 text-center border-2 border-gray-300 dark:border-gray-600 rounded-lg peer-checked:border-blue-600 peer-checked:bg-blue-50 dark:peer-checked:bg-blue-900/30 transition-all">
                                <i class="fas fa-shopping-bag text-lg mb-1 text-blue-600"></i>
                                <p class="text-xs font-medium text-gray-700 dark:text-gray-300">Externo</p>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Ocupación (solo visible si es tipo huesped) -->
                <div id="ocupacion_div">
                    <label class="block text-xs sm:text-sm font-semibold text-noir dark:text-white mb-1.5 sm:mb-2">
                        Huésped (Opcional)
                    </label>
                    <select name="ocupacion_id" class="w-full px-3 py-2.5 sm:px-4 sm:py-3 text-sm sm:text-base border border-gray-300 dark:border-gray-700 rounded-lg sm:rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200 text-noir dark:text-white bg-white dark:bg-gray-800 appearance-none">
                        <option value="">Sin asociar a huésped</option>
                        <?php foreach ($ocupaciones_activas as $ocu): ?>
                            <option value="<?php echo $ocu['id']; ?>">
                                Hab <?php echo $ocu['nro_pieza']; ?> - <?php echo htmlspecialchars($ocu['nombres_apellidos']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Opcional: vincular pago</p>
                </div>

                <!-- Concepto (solo visible si es tipo externo) -->
                <div id="concepto_div" style="display: none;">
                    <label class="block text-xs sm:text-sm font-semibold text-noir dark:text-white mb-1.5 sm:mb-2">
                        Concepto del Cobro <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        name="concepto"
                        id="concepto_input"
                        placeholder="Ej: Venta de productos, Servicio de lavandería, etc."
                        class="w-full px-3 py-2.5 sm:px-4 sm:py-3 text-sm sm:text-base border border-gray-300 dark:border-gray-700 rounded-lg sm:rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 text-noir dark:text-white bg-white dark:bg-gray-800 placeholder-gray-400"
                    >
                    <p class="text-xs text-blue-600 dark:text-blue-400 mt-1">
                        <i class="fas fa-info-circle mr-1"></i>
                        Describe el motivo del cobro externo
                    </p>
                </div>
                
                <!-- Monto -->
                <div>
                    <label class="block text-xs sm:text-sm font-semibold text-noir dark:text-white mb-1.5 sm:mb-2">
                        Monto <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-500 dark:text-gray-400 font-medium">Bs.</span>
                        <input 
                            type="number" 
                            step="0.01" 
                            name="monto" 
                            required
                            class="w-full pl-10 sm:pl-12 pr-3 py-2.5 sm:pr-4 sm:py-3 text-sm sm:text-base border border-gray-300 dark:border-gray-700 rounded-lg sm:rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200 text-noir dark:text-white bg-white dark:bg-gray-800 placeholder-gray-400"
                            placeholder="0.00"
                        >
                    </div>
                </div>
                
                <!-- Número de Transacción -->
                <div>
                    <label class="block text-xs sm:text-sm font-semibold text-noir dark:text-white mb-1.5 sm:mb-2">
                        Nro de Transacción
                    </label>
                    <input 
                        type="text" 
                        name="numero_transaccion"
                        class="w-full px-3 py-2.5 sm:px-4 sm:py-3 text-sm sm:text-base border border-gray-300 dark:border-gray-700 rounded-lg sm:rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200 text-noir dark:text-white bg-white dark:bg-gray-800 placeholder-gray-400"
                        placeholder="TRX123456789"
                    >
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Código de transferencia</p>
                </div>
                
                <!-- Fecha -->
                <div>
                    <label class="block text-xs sm:text-sm font-semibold text-noir dark:text-white mb-1.5 sm:mb-2">
                        Fecha <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="date" 
                        name="fecha" 
                        value="<?php echo date('Y-m-d'); ?>"
                        required
                        class="w-full px-3 py-2.5 sm:px-4 sm:py-3 text-sm sm:text-base border border-gray-300 dark:border-gray-700 rounded-lg sm:rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200 text-noir dark:text-white bg-white dark:bg-gray-800"
                    >
                </div>
                
                <!-- Observaciones -->
                <div>
                    <label class="block text-xs sm:text-sm font-semibold text-noir dark:text-white mb-1.5 sm:mb-2">
                        Observaciones
                    </label>
                    <textarea 
                        name="observaciones" 
                        rows="2"
                        class="w-full px-3 py-2.5 sm:px-4 sm:py-3 text-sm sm:text-base border border-gray-300 dark:border-gray-700 rounded-lg sm:rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200 text-noir dark:text-white bg-white dark:bg-gray-800 placeholder-gray-400 resize-none"
                        placeholder="Detalles adicionales..."
                    ></textarea>
                </div>
                
                <!-- Botón -->
                <button 
                    type="submit" 
                    name="registrar_pago_qr"
                    class="w-full px-5 py-2.5 sm:px-6 sm:py-3.5 bg-gradient-to-r from-purple-600 to-purple-700 text-white text-sm sm:text-base rounded-lg sm:rounded-xl font-medium hover:from-purple-700 hover:to-purple-800 transition-all duration-200 shadow-md sm:shadow-lg hover:shadow-lg sm:hover:shadow-xl"
                >
                    Registrar Pago QR
                </button>
            </form>
        </div>
    </div>
    
    <!-- Lista de Pagos (2/3) -->
    <div class="lg:col-span-2">
        <div class="glass-card p-6 rounded-2xl">
            <!-- Header con Filtros -->
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-xl font-semibold text-noir dark:text-white">Historial de Pagos</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Transferencias registradas</p>
                </div>
            </div>
            
            <!-- Filtro de Fechas -->
            <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Desde:</label>
                    <input 
                        type="date" 
                        name="fecha_inicio" 
                        value="<?php echo $fecha_inicio; ?>"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-purple-500 text-noir dark:text-white bg-white dark:bg-gray-800"
                    >
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Hasta:</label>
                    <input 
                        type="date" 
                        name="fecha_fin" 
                        value="<?php echo $fecha_fin; ?>"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-purple-500 text-noir dark:text-white bg-white dark:bg-gray-800"
                    >
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">&nbsp;</label>
                    <button type="submit" class="w-full px-4 py-2 bg-purple-600 text-white rounded-lg font-medium hover:bg-purple-700 transition-colors">
                        Filtrar
                    </button>
                </div>
            </form>
            
            <!-- Tabla -->
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700 dark:text-gray-300">Fecha</th>
                            <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700 dark:text-gray-300">Tipo</th>
                            <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700 dark:text-gray-300">Concepto/Huésped</th>
                            <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700 dark:text-gray-300">Nro Transacción</th>
                            <th class="text-right py-3 px-4 text-sm font-semibold text-gray-700 dark:text-gray-300">Monto</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        <?php 
                        $total = 0;
                        foreach ($pagos_qr as $pqr): 
                            $total += $pqr['monto'];
                            $es_externo = ($pqr['tipo'] ?? 'huesped') === 'externo';
                        ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                <td class="py-3 px-4 text-sm text-gray-700 dark:text-gray-300">
                                    <?php echo date('d/m/Y', strtotime($pqr['fecha'])); ?>
                                </td>
                                <td class="py-3 px-4 text-sm">
                                    <?php if ($es_externo): ?>
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
                                            <i class="fas fa-shopping-bag mr-1"></i> Externo
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300">
                                            <i class="fas fa-user mr-1"></i> Huésped
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4 text-sm text-gray-700 dark:text-gray-300">
                                    <?php 
                                    if ($es_externo && !empty($pqr['concepto'])) {
                                        echo '<span class="font-medium">' . htmlspecialchars($pqr['concepto']) . '</span>';
                                        if (!empty($pqr['observaciones'])) {
                                            echo '<br><span class="text-xs text-gray-500 dark:text-gray-400">' . htmlspecialchars($pqr['observaciones']) . '</span>';
                                        }
                                    } elseif ($pqr['nombres_apellidos']) {
                                        echo '<span class="font-medium">' . htmlspecialchars($pqr['nombres_apellidos']) . '</span>';
                                        echo '<span class="text-xs text-gray-500 dark:text-gray-400 ml-2">Hab ' . $pqr['nro_pieza'] . '</span>';
                                    } else {
                                        echo '<span class="text-gray-400 dark:text-gray-500">Sin detalles</span>';
                                    }
                                    ?>
                                </td>
                                <td class="py-3 px-4 text-sm text-gray-600 dark:text-gray-400 font-mono">
                                    <?php echo htmlspecialchars($pqr['numero_transaccion'] ?? '-'); ?>
                                </td>
                                <td class="py-3 px-4 text-sm text-right">
                                    <span class="font-semibold text-purple-600 dark:text-purple-400">
                                        Bs. <?php echo number_format($pqr['monto'], 2); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($pagos_qr)): ?>
                            <tr>
                                <td colspan="5" class="py-12 text-center">
                                    <div class="flex flex-col items-center">
                                        <div class="w-16 h-16 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mb-3">
                                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        </div>
                                        <p class="text-gray-500 dark:text-gray-400">No hay pagos QR registrados en este período</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2 border-gray-300 dark:border-gray-600">
                            <th colspan="4" class="text-right py-4 px-4 text-sm font-semibold text-gray-700 dark:text-gray-300">
                                TOTAL:
                            </th>
                            <th class="text-right py-4 px-4">
                                <span class="stat-badge px-4 py-2 rounded-lg text-lg font-bold text-purple-700 dark:text-purple-300">
                                    Bs. <?php echo number_format($total, 2); ?>
                                </span>
                            </th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function toggleTipoPago() {
    const tipoPago = document.querySelector('input[name="tipo_pago"]:checked').value;
    const ocupacionDiv = document.getElementById('ocupacion_div');
    const conceptoDiv = document.getElementById('concepto_div');
    const conceptoInput = document.getElementById('concepto_input');
    
    if (tipoPago === 'externo') {
        // Mostrar campo de concepto y ocultar huésped
        ocupacionDiv.style.display = 'none';
        conceptoDiv.style.display = 'block';
        conceptoInput.required = true;
    } else {
        // Mostrar selector de huésped y ocultar concepto
        ocupacionDiv.style.display = 'block';
        conceptoDiv.style.display = 'none';
        conceptoInput.required = false;
    }
}

// Inicializar al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    toggleTipoPago();
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
