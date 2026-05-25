<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/Finanzas.php';
require_once __DIR__ . '/../../models/RegistroOcupacion.php';

// Verificar que el usuario sea administrador
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'administrador') {
    header('Location: ' . BASE_PATH . '/views/finanzas/resumen.php?error=acceso_denegado');
    exit;
}

$page_title = 'Registro de Ingresos';
$mensaje = '';
$tipo_mensaje = '';

// Procesar registro de ingreso
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar_ingreso'])) {
    try {
        $finanzasModel = new Finanzas();
        
        $datos = [
            'ocupacion_id' => !empty($_POST['ocupacion_id']) ? $_POST['ocupacion_id'] : null,
            'concepto' => clean_input($_POST['concepto']),
            'monto' => floatval($_POST['monto']),
            'metodo_pago' => $_POST['metodo_pago'],
            'fecha' => $_POST['fecha'],
            'observaciones' => !empty($_POST['observaciones']) ? clean_input($_POST['observaciones']) : null
        ];
        
        if ($finanzasModel->registrarIngreso($datos)) {
            $mensaje = 'Ingreso registrado correctamente.';
            $tipo_mensaje = 'success';
            $_POST = []; // Limpiar formulario
        } else {
            throw new Exception('Error al registrar el ingreso en la base de datos');
        }
    } catch (Exception $e) {
        $mensaje = 'Error: ' . $e->getMessage();
        $tipo_mensaje = 'danger';
        error_log("Error en registro de ingreso: " . $e->getMessage());
    }
}

// Procesar edición de método de pago
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_metodo_pago'])) {
    try {
        $finanzasModel = new Finanzas();
        
        $ingreso_id = intval($_POST['ingreso_id']);
        $metodo_pago_nuevo = clean_input($_POST['metodo_pago_nuevo']);
        $numero_transaccion = !empty($_POST['numero_transaccion']) ? clean_input($_POST['numero_transaccion']) : null;
        
        if ($finanzasModel->editarMetodoPagoIngreso($ingreso_id, $metodo_pago_nuevo, $numero_transaccion)) {
            $mensaje = 'Método de pago actualizado correctamente a: ' . strtoupper($metodo_pago_nuevo);
            $tipo_mensaje = 'success';
        } else {
            throw new Exception('Error al actualizar el método de pago');
        }
    } catch (Exception $e) {
        $mensaje = 'Error: ' . $e->getMessage();
        $tipo_mensaje = 'danger';
        error_log("Error al editar método de pago: " . $e->getMessage());
    }
}

// Obtener ingresos
$finanzasModel = new Finanzas();
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-d', strtotime('-30 days'));
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');
$ingresos = $finanzasModel->obtenerIngresos($fecha_inicio, $fecha_fin);

// Obtener ocupaciones activas para el select
$registroModel = new RegistroOcupacion();
$ocupaciones_activas = $registroModel->obtenerActivos();

// Calcular estadísticas rápidas para la cabecera (widgets)
$total_ingresos = 0;
$transacciones_ingresos = count($ingresos);
foreach ($ingresos as $ing) {
    $total_ingresos += $ing['monto'];
}
$promedio_ingresos = $transacciones_ingresos > 0 ? $total_ingresos / $transacciones_ingresos : 0;

include __DIR__ . '/../../includes/header.php';
?>

<style>
    body {
        background-color: #f5f5f7;
    }
    .dark body {
        background-color: #080808;
    }

    /* Scrollbar Apple Style */
    ::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }
    ::-webkit-scrollbar-track {
        background: transparent;
    }
    ::-webkit-scrollbar-thumb {
        background: rgba(0, 0, 0, 0.12);
        border-radius: 10px;
    }
    .dark ::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.12);
    }
    ::-webkit-scrollbar-thumb:hover {
        background: rgba(0, 0, 0, 0.24);
    }
    .dark ::-webkit-scrollbar-thumb:hover {
        background: rgba(255, 255, 255, 0.24);
    }

    /* Apple Clean Card */
    .apple-card-clean {
        background: #ffffff;
        border: 1px solid rgba(0, 0, 0, 0.05);
        border-radius: 20px;
        box-shadow: 0 4px 30px rgba(0, 0, 0, 0.015);
        padding: 24px;
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .dark .apple-card-clean {
        background: #121212;
        border-color: rgba(255, 255, 255, 0.05);
        box-shadow: 0 4px 30px rgba(0, 0, 0, 0.2);
    }

    /* Widgets de Resumen */
    .apple-widget {
        background: #ffffff;
        border: 1px solid rgba(0, 0, 0, 0.06);
        border-radius: 22px;
        padding: 24px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        position: relative;
        overflow: hidden;
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.02);
    }
    .dark .apple-widget {
        background: #161618;
        border-color: rgba(255, 255, 255, 0.06);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
    }
    .apple-widget:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.04);
    }
    .dark .apple-widget:hover {
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.3);
    }

    /* Inputs Apple Style */
    .input-apple {
        width: 100%;
        background: #f5f5f7;
        border: 1px solid rgba(0, 0, 0, 0.06);
        border-radius: 12px;
        padding: 12px 16px;
        font-size: 14px;
        font-weight: 500;
        color: #1c1c1e;
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        outline: none;
    }
    .dark .input-apple {
        background: rgba(255, 255, 255, 0.04);
        border-color: rgba(255, 255, 255, 0.06);
        color: #ffffff;
    }
    .input-apple:focus {
        background: #ffffff;
        border-color: #34c759;
        box-shadow: 0 0 0 4px rgba(52, 199, 89, 0.15);
    }
    .dark .input-apple:focus {
        background: #1c1c1e;
        border-color: #30d158;
        box-shadow: 0 0 0 4px rgba(48, 209, 88, 0.2);
    }
    .input-apple-amount {
        padding-left: 48px !important;
    }

    /* Apple Pill Buttons */
    .btn-apple {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 10px 18px;
        border-radius: 14px;
        font-size: 14px;
        font-weight: 600;
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        cursor: pointer;
        border: 1px solid transparent;
    }
    .btn-apple:active {
        transform: scale(0.96);
    }

    .btn-apple-primary {
        background: #1c1c1e;
        color: #ffffff;
    }
    .btn-apple-primary:hover {
        background: #2c2c2e;
    }
    .dark .btn-apple-primary {
        background: #ffffff;
        color: #1c1c1e;
    }
    .dark .btn-apple-primary:hover {
        background: #f5f5f7;
    }

    .btn-apple-secondary {
        background: rgba(0, 0, 0, 0.04);
        color: #1c1c1e;
        border-color: rgba(0, 0, 0, 0.02);
    }
    .btn-apple-secondary:hover {
        background: rgba(0, 0, 0, 0.08);
    }
    .dark .btn-apple-secondary {
        background: rgba(255, 255, 255, 0.05);
        color: #ffffff;
        border-color: rgba(255, 255, 255, 0.02);
    }
    .dark .btn-apple-secondary:hover {
        background: rgba(255, 255, 255, 0.1);
    }

    .btn-apple-green {
        background: #34c759;
        color: #ffffff;
        box-shadow: 0 4px 12px rgba(52, 199, 89, 0.2);
    }
    .btn-apple-green:hover {
        background: #28a745;
        box-shadow: 0 6px 16px rgba(52, 199, 89, 0.3);
    }
    .dark .btn-apple-green {
        background: #30d158;
        color: #000000;
        box-shadow: 0 4px 12px rgba(48, 209, 88, 0.15);
    }
    .dark .btn-apple-green:hover {
        background: #2cb74c;
        box-shadow: 0 6px 16px rgba(48, 209, 88, 0.25);
    }

    .btn-apple-blue {
        background: rgba(0, 122, 255, 0.08);
        color: #007aff;
        border-radius: 10px;
        padding: 6px 14px;
        font-size: 12px;
        font-weight: 600;
        border: 1px solid rgba(0, 122, 255, 0.15);
    }
    .btn-apple-blue:hover {
        background: rgba(0, 122, 255, 0.15);
        border-color: rgba(0, 122, 255, 0.25);
    }
    .dark .btn-apple-blue {
        background: rgba(10, 132, 255, 0.12);
        color: #0a84ff;
        border-color: rgba(10, 132, 255, 0.2);
    }
    .dark .btn-apple-blue:hover {
        background: rgba(10, 132, 255, 0.22);
        border-color: rgba(10, 132, 255, 0.3);
    }

    /* Premium Tables */
    .premium-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13.5px;
    }

    .premium-table th {
        background: rgba(52, 199, 89, 0.08) !important;
        color: #248a3d !important;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 10.5px;
        letter-spacing: 0.08em;
        padding: 14px 18px;
        text-align: left;
        border-bottom: 2px solid rgba(52, 199, 89, 0.2) !important;
    }

    .dark .premium-table th {
        background: rgba(48, 209, 88, 0.15) !important;
        color: #30d158 !important;
        border-bottom-color: rgba(48, 209, 88, 0.3) !important;
    }

    .premium-table td {
        padding: 14px 18px;
        border-bottom: 1px solid rgba(0, 0, 0, 0.04);
        color: #1c1c1e;
        font-variant-numeric: tabular-nums;
    }

    .dark .premium-table td {
        color: #e5e5ea;
        border-bottom-color: rgba(255, 255, 255, 0.04);
    }

    .premium-table tr:hover td {
        background: rgba(0, 0, 0, 0.007);
    }
    .dark .premium-table tr:hover td {
        background: rgba(255, 255, 255, 0.01);
    }

    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        border-radius: 16px;
        border: 1px solid rgba(0, 0, 0, 0.05);
    }
    .dark .table-responsive {
        border-color: rgba(255, 255, 255, 0.05);
    }

    /* Modal Apple Sheet style */
    .apple-modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0, 0, 0, 0.4);
        backdrop-filter: blur(25px);
        -webkit-backdrop-filter: blur(25px);
    }

    .apple-modal-content {
        background-color: #ffffff;
        margin: 8% auto;
        padding: 28px;
        border: 1px solid rgba(0, 0, 0, 0.08);
        border-radius: 28px;
        width: 90%;
        max-width: 520px;
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.15);
        animation: sheetUp 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .dark .apple-modal-content {
        background-color: #1c1c1e;
        border-color: rgba(255, 255, 255, 0.08);
        color: #ffffff;
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
    }

    @keyframes sheetUp {
        from { transform: translateY(60px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
</style>

<!-- Page Header -->
<div class="mb-8">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-150 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                    Módulo de Finanzas
                </span>
            </div>
            <h1 class="text-3xl md:text-4xl font-extrabold tracking-tight text-gray-900 dark:text-white mb-1">Ingresos de Caja</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 font-medium">Registra cobros extras y ventas accesorias independientes del alojamiento</p>
        </div>
        <a href="<?php echo BASE_PATH; ?>/index.php" class="btn-apple btn-apple-secondary">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
            Volver al Panel
        </a>
    </div>
</div>

<!-- Widgets de Estadísticas Rápidas (Estilo Apple Widget Grid) -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-6 mb-8">
    <!-- Widget 1: Total -->
    <div class="apple-widget">
        <div class="flex justify-between items-start mb-3">
            <span class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Monto Filtrado</span>
            <div class="w-7 h-7 bg-green-500/10 rounded-full flex items-center justify-center">
                <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
            </div>
        </div>
        <div>
            <span class="text-3xl font-black text-green-600 dark:text-green-400 tracking-tight font-variant-numeric-tabular">
                Bs. <?php echo formatMoney($total_ingresos); ?>
            </span>
            <p class="text-xs text-gray-400 dark:text-gray-500 font-medium mt-1">Total recaudado en el periodo</p>
        </div>
    </div>

    <!-- Widget 2: Conteo -->
    <div class="apple-widget">
        <div class="flex justify-between items-start mb-3">
            <span class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Transacciones</span>
            <div class="w-7 h-7 bg-blue-500/10 rounded-full flex items-center justify-center">
                <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/>
                </svg>
            </div>
        </div>
        <div>
            <span class="text-3xl font-black text-noir dark:text-white tracking-tight">
                <?php echo $transacciones_ingresos; ?>
            </span>
            <p class="text-xs text-gray-400 dark:text-gray-500 font-medium mt-1">Registros cargados</p>
        </div>
    </div>

    <!-- Widget 3: Promedio -->
    <div class="apple-widget">
        <div class="flex justify-between items-start mb-3">
            <span class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Monto Promedio</span>
            <div class="w-7 h-7 bg-purple-500/10 rounded-full flex items-center justify-center">
                <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 14.25l6-6m4.5-3.75L3 18.75"/>
                </svg>
            </div>
        </div>
        <div>
            <span class="text-3xl font-black text-noir dark:text-white tracking-tight">
                Bs. <?php echo formatMoney($promedio_ingresos); ?>
            </span>
            <p class="text-xs text-gray-400 dark:text-gray-500 font-medium mt-1">Ticket medio por transacción</p>
        </div>
    </div>
</div>

<!-- Alert Messages -->
<?php if ($mensaje): ?>
    <div class="mb-8 animate-fade-in">
        <div class="bg-<?php echo $tipo_mensaje === 'success' ? 'green' : 'red'; ?>-50 dark:bg-<?php echo $tipo_mensaje === 'success' ? 'green' : 'red'; ?>-900/20 border border-<?php echo $tipo_mensaje === 'success' ? 'green' : 'red'; ?>-200 dark:border-<?php echo $tipo_mensaje === 'success' ? 'green' : 'red'; ?>-800 rounded-20 p-4">
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
                    <p class="text-sm font-semibold text-<?php echo $tipo_mensaje === 'success' ? 'green' : 'red'; ?>-800 dark:text-<?php echo $tipo_mensaje === 'success' ? 'green' : 'red'; ?>-200">
                        <?php echo $mensaje; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 lg:gap-8">
    <!-- Formulario de Registro Estilo Control Center -->
    <div class="lg:col-span-1">
        <div class="apple-card-clean lg:sticky lg:top-4 border-t-4 border-t-green-500">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-11 h-11 bg-gradient-to-br from-green-400 to-green-600 rounded-xl flex items-center justify-center shadow-sm">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-noir dark:text-white tracking-tight">Ingreso Extra</h2>
                    <p class="text-xs text-gray-400 dark:text-gray-500 font-medium">Registrar entrada de dinero</p>
                </div>
            </div>
            
            <form method="POST" action="" class="space-y-5">
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                        Concepto / Descripción <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="concepto" 
                           value="<?php echo isset($_POST['concepto']) ? htmlspecialchars($_POST['concepto']) : ''; ?>"
                           required
                           class="input-apple"
                           placeholder="Ej: Lavandería extra, Cafetería...">
                </div>
                
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                        Monto Recibido (Bs.) <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 dark:text-gray-500 font-bold text-sm">Bs.</span>
                        <input type="number" step="0.01" name="monto" 
                               value="<?php echo isset($_POST['monto']) ? htmlspecialchars($_POST['monto']) : ''; ?>"
                               min="0.01" required
                               class="input-apple input-apple-amount"
                               placeholder="0.00">
                    </div>
                </div>
                
                <!-- Campos de configuración oculta por defecto para ingresos rápidos -->
                <input type="hidden" name="ocupacion_id" value="">
                <input type="hidden" name="metodo_pago" value="efectivo">
                <input type="hidden" name="fecha" value="<?php echo date('Y-m-d'); ?>">
                <input type="hidden" name="observaciones" value="Ingreso extra rápido">
                
                <button type="submit" name="registrar_ingreso" class="w-full btn-apple btn-apple-green">
                    Registrar Ingreso
                </button>
            </form>
        </div>
    </div>
    
    <!-- Lista de Ingresos Reorganizada -->
    <div class="lg:col-span-2">
        <div class="apple-card-clean">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <div>
                    <h2 class="text-lg font-bold text-noir dark:text-white tracking-tight">Historial de Ingresos</h2>
                    <p class="text-xs text-gray-400 dark:text-gray-500 font-medium">Transacciones registradas en caja</p>
                </div>
            </div>
            
            <!-- Filtros de fecha estilo iOS -->
            <form method="GET" class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6 p-5 bg-gray-50 dark:bg-zinc-900/50 rounded-2xl border border-gray-100 dark:border-zinc-800">
                <div>
                    <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1.5">Fecha Inicio</label>
                    <input type="date" name="fecha_inicio" 
                           value="<?php echo $fecha_inicio; ?>"
                           class="input-apple">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1.5">Fecha Fin</label>
                    <input type="date" name="fecha_fin" 
                           value="<?php echo $fecha_fin; ?>"
                           class="input-apple">
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full btn-apple btn-apple-primary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z"/>
                        </svg>
                        Filtrar
                    </button>
                </div>
            </form>
            
            <!-- Contenedor de la Tabla -->
            <div class="table-responsive">
                <table class="premium-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Concepto</th>
                            <th>Huésped / Hab.</th>
                            <th>Método</th>
                            <th class="text-right">Monto</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-150 dark:divide-zinc-800">
                        <?php foreach ($ingresos as $ing): ?>
                            <tr>
                                <td class="text-gray-500 dark:text-gray-400 font-medium">
                                    <?php echo formatDate($ing['fecha']); ?>
                                </td>
                                <td class="font-semibold text-noir dark:text-white">
                                    <?php echo htmlspecialchars($ing['concepto']); ?>
                                </td>
                                <td>
                                    <?php 
                                    if ($ing['nombres_apellidos']) {
                                        echo '<span class="font-bold text-gray-700 dark:text-gray-300">' . htmlspecialchars($ing['nombres_apellidos']) . '</span><br>';
                                        echo '<span class="px-2 py-0.5 rounded text-[10.5px] font-bold bg-gray-100 dark:bg-zinc-800 text-gray-500 dark:text-gray-400">Hab. ' . $ing['nro_pieza'] . '</span>';
                                    } else {
                                        echo '<span class="text-gray-450 dark:text-gray-500 font-medium italic">Externo</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider
                                        <?php 
                                        if ($ing['metodo_pago'] == 'efectivo') echo 'bg-green-50 text-green-700 dark:bg-green-950/20 dark:text-green-400';
                                        elseif ($ing['metodo_pago'] == 'qr') echo 'bg-blue-50 text-blue-700 dark:bg-blue-950/20 dark:text-blue-400';
                                        elseif ($ing['metodo_pago'] == 'tarjeta') echo 'bg-purple-50 text-purple-700 dark:bg-purple-950/20 dark:text-purple-400';
                                        elseif ($ing['metodo_pago'] == 'pendiente') echo 'bg-orange-50 text-orange-700 dark:bg-orange-950/20 dark:text-orange-400';
                                        else echo 'bg-gray-50 text-gray-700 dark:bg-zinc-850 dark:text-gray-400';
                                        ?>">
                                        <?php echo strtoupper($ing['metodo_pago']); ?>
                                    </span>
                                </td>
                                <td class="text-right font-extrabold text-green-600 dark:text-green-400 font-variant-numeric-tabular">
                                    Bs. <?php echo formatMoney($ing['monto']); ?>
                                </td>
                                <td class="text-center">
                                    <button 
                                        onclick="abrirModalEditarMetodo(<?php echo $ing['id']; ?>, '<?php echo htmlspecialchars($ing['concepto']); ?>', '<?php echo $ing['metodo_pago']; ?>', <?php echo $ing['monto']; ?>)"
                                        class="btn-apple-blue inline-flex items-center gap-1.5"
                                        title="Editar método de pago"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/>
                                        </svg>
                                        Editar
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($ingresos)): ?>
                            <tr>
                                <td colspan="6" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                                    <svg class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-650 mb-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    No se encontraron ingresos registrados en este periodo.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pie de totales estilo pastilla flotante Apple -->
            <div class="mt-6 flex justify-end">
                <div class="bg-noir dark:bg-black text-white px-6 py-4 rounded-2xl flex items-center gap-6 shadow-sm border border-gray-800">
                    <span class="text-xs font-bold uppercase tracking-widest text-gray-400">Total Ingresos:</span>
                    <span class="text-xl font-black text-green-400 font-variant-numeric-tabular">
                        Bs. <?php echo formatMoney($total_ingresos); ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para editar método de pago (Estilo iPadOS Sheet) -->
<div id="modalEditarMetodo" class="apple-modal">
    <div class="apple-modal-content">
        <div class="flex items-center justify-between mb-6 pb-2 border-b border-gray-100 dark:border-zinc-800">
            <h2 class="text-xl font-black text-noir dark:text-white tracking-tight flex items-center gap-2">
                <span class="w-8 h-8 rounded-full bg-blue-500/10 flex items-center justify-center">
                    <svg class="w-4.5 h-4.5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/>
                    </svg>
                </span>
                Editar Pago
            </h2>
            <button onclick="cerrarModalEdit()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        
        <form method="POST" id="formEditarMetodo" class="space-y-5">
            <input type="hidden" name="editar_metodo_pago" value="1">
            <input type="hidden" name="ingreso_id" id="edit_ingreso_id">
            
            <div class="grid grid-cols-2 gap-4">
                <div class="p-4 bg-gray-50 dark:bg-zinc-900 rounded-2xl">
                    <label class="block text-[10px] font-bold text-gray-400 dark:text-gray-550 uppercase tracking-widest mb-1">Concepto</label>
                    <p id="edit_concepto" class="text-sm font-bold text-noir dark:text-white truncate"></p>
                </div>
                
                <div class="p-4 bg-gray-50 dark:bg-zinc-900 rounded-2xl">
                    <label class="block text-[10px] font-bold text-gray-400 dark:text-gray-550 uppercase tracking-widest mb-1">Monto</label>
                    <p id="edit_monto" class="text-base font-extrabold text-green-600 dark:text-green-400"></p>
                </div>
            </div>
            
            <div class="p-4 bg-orange-50/50 dark:bg-orange-950/10 border border-orange-200/50 dark:border-orange-900/30 rounded-2xl flex items-center justify-between">
                <span class="text-xs font-bold text-orange-800 dark:text-orange-400 uppercase tracking-wider">Método Actual:</span>
                <span id="edit_metodo_actual" class="px-3 py-0.5 rounded-full text-xs font-extrabold bg-orange-100 dark:bg-orange-900/30 text-orange-850 dark:text-orange-300 uppercase"></span>
            </div>
            
            <div class="space-y-2">
                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    Seleccionar Nuevo Método: <span class="text-red-500">*</span>
                </label>
                
                <div class="grid grid-cols-3 gap-3">
                    <label class="cursor-pointer">
                        <input 
                            type="radio" 
                            name="metodo_pago_nuevo" 
                            value="efectivo"
                            onchange="toggleNumeroTransaccionEdit()"
                            class="hidden"
                            id="edit_metodo_efectivo"
                        >
                        <div id="edit_btn_efectivo" class="flex flex-col items-center justify-center gap-1.5 p-3.5 border-2 border-gray-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 rounded-2xl transition-all duration-200">
                            <span class="text-lg">💵</span>
                            <span class="font-bold text-xs text-gray-600 dark:text-gray-300">Efectivo</span>
                        </div>
                    </label>
                    
                    <label class="cursor-pointer">
                        <input 
                            type="radio" 
                            name="metodo_pago_nuevo" 
                            value="qr"
                            onchange="toggleNumeroTransaccionEdit()"
                            class="hidden"
                            id="edit_metodo_qr"
                        >
                        <div id="edit_btn_qr" class="flex flex-col items-center justify-center gap-1.5 p-3.5 border-2 border-gray-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 rounded-2xl transition-all duration-200">
                            <span class="text-lg">📱</span>
                            <span class="font-bold text-xs text-gray-600 dark:text-gray-300">QR</span>
                        </div>
                    </label>
                    
                    <label class="cursor-pointer">
                        <input 
                            type="radio" 
                            name="metodo_pago_nuevo" 
                            value="pendiente"
                            onchange="toggleNumeroTransaccionEdit()"
                            class="hidden"
                            id="edit_metodo_pendiente"
                        >
                        <div id="edit_btn_pendiente" class="flex flex-col items-center justify-center gap-1.5 p-3.5 border-2 border-gray-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 rounded-2xl transition-all duration-200">
                            <span class="text-lg">⏳</span>
                            <span class="font-bold text-xs text-gray-600 dark:text-gray-300">Pendiente</span>
                        </div>
                    </label>
                </div>
            </div>
            
            <div id="edit_numero_transaccion_container" class="space-y-1.5" style="display: none;">
                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                    Número de Transacción (Opcional):
                </label>
                <input 
                    type="text" 
                    name="numero_transaccion" 
                    class="input-apple"
                    placeholder="Ej: 832948123">
            </div>
            
            <div class="flex gap-4 mt-6">
                <button type="button" onclick="cerrarModalEdit()" class="flex-1 btn-apple btn-apple-secondary">
                    Cancelar
                </button>
                <button type="submit" class="flex-1 btn-apple btn-apple-primary shadow-md">
                    Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirModalEditarMetodo(id, concepto, metodoActual, monto) {
    document.getElementById('edit_ingreso_id').value = id;
    document.getElementById('edit_concepto').textContent = concepto;
    document.getElementById('edit_monto').textContent = 'Bs. ' + parseFloat(monto).toFixed(2);
    document.getElementById('edit_metodo_actual').textContent = metodoActual.toUpperCase();
    document.getElementById('modalEditarMetodo').style.display = 'block';
    
    // Seleccionar el método actual por defecto
    const radioId = 'edit_metodo_' + metodoActual;
    const radio = document.getElementById(radioId);
    if (radio) {
        radio.checked = true;
        cambiarMetodoPagoEdit(metodoActual);
    }
}

function cerrarModalEdit() {
    document.getElementById('modalEditarMetodo').style.display = 'none';
}

function cambiarMetodoPagoEdit(metodo) {
    const btnEfectivo = document.getElementById('edit_btn_efectivo');
    const btnQr = document.getElementById('edit_btn_qr');
    const btnPendiente = document.getElementById('edit_btn_pendiente');
    
    const baseClass = 'flex flex-col items-center justify-center gap-1.5 p-3.5 border-2 border-gray-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 rounded-2xl transition-all duration-200';
    
    // Resetear todos
    btnEfectivo.className = baseClass;
    btnQr.className = baseClass;
    btnPendiente.className = baseClass;
    
    // Activar el seleccionado con colores premium HSL
    if (metodo === 'efectivo') {
        btnEfectivo.className = 'flex flex-col items-center justify-center gap-1.5 p-3.5 border-2 border-green-500 bg-green-50/50 dark:bg-green-950/20 rounded-2xl transition-all duration-200 shadow-sm';
        btnEfectivo.querySelector('span:last-child').className = 'font-bold text-xs text-green-700 dark:text-green-400';
    } else if (metodo === 'qr') {
        btnQr.className = 'flex flex-col items-center justify-center gap-1.5 p-3.5 border-2 border-blue-500 bg-blue-50/50 dark:bg-blue-950/20 rounded-2xl transition-all duration-200 shadow-sm';
        btnQr.querySelector('span:last-child').className = 'font-bold text-xs text-blue-700 dark:text-blue-400';
    } else if (metodo === 'pendiente') {
        btnPendiente.className = 'flex flex-col items-center justify-center gap-1.5 p-3.5 border-2 border-orange-500 bg-orange-50/50 dark:bg-orange-950/20 rounded-2xl transition-all duration-200 shadow-sm';
        btnPendiente.querySelector('span:last-child').className = 'font-bold text-xs text-orange-700 dark:text-orange-400';
    }
}

function toggleNumeroTransaccionEdit() {
    const metodoQr = document.getElementById('edit_metodo_qr').checked;
    const container = document.getElementById('edit_numero_transaccion_container');
    container.style.display = metodoQr ? 'block' : 'none';
    
    // Actualizar visualización
    let metodo;
    if (document.getElementById('edit_metodo_efectivo').checked) metodo = 'efectivo';
    else if (document.getElementById('edit_metodo_qr').checked) metodo = 'qr';
    else if (document.getElementById('edit_metodo_pendiente').checked) metodo = 'pendiente';
    
    if (metodo) cambiarMetodoPagoEdit(metodo);
}

// Cerrar modal al hacer clic fuera de él
window.onclick = function(event) {
    const modal = document.getElementById('modalEditarMetodo');
    if (event.target == modal) {
        cerrarModalEdit();
    }
}

// Registrar listeners en los radio buttons del modal
document.getElementById('edit_metodo_efectivo').addEventListener('change', function() {
    cambiarMetodoPagoEdit('efectivo');
});

document.getElementById('edit_metodo_qr').addEventListener('change', function() {
    cambiarMetodoPagoEdit('qr');
});

document.getElementById('edit_metodo_pendiente').addEventListener('change', function() {
    cambiarMetodoPagoEdit('pendiente');
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
