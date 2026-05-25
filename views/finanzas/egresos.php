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
            $_POST = []; // Limpiar formulario
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

// Calcular estadísticas rápidas para la cabecera (widgets)
$total_egresos = 0;
$transacciones_egresos = count($egresos);
$gastos_por_categoria = ['Externo' => 0, 'Cafetería' => 0];

foreach ($egresos as $egr) {
    $total_egresos += $egr['monto'];
    $cat = $egr['categoria'] ?? 'Otro';
    if (isset($gastos_por_categoria[$cat])) {
        $gastos_por_categoria[$cat] += $egr['monto'];
    } else {
        $gastos_por_categoria[$cat] = $egr['monto'];
    }
}

$promedio_egresos = $transacciones_egresos > 0 ? $total_egresos / $transacciones_egresos : 0;

$mayor_categoria = 'Ninguno';
$max_gasto = -1;
foreach ($gastos_por_categoria as $cat => $monto) {
    if ($monto > $max_gasto && $monto > 0) {
        $max_gasto = $monto;
        $mayor_categoria = $cat;
    }
}

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
        border-color: #ff3b30;
        box-shadow: 0 0 0 4px rgba(255, 59, 48, 0.15);
    }
    .dark .input-apple:focus {
        background: #1c1c1e;
        border-color: #ff453a;
        box-shadow: 0 0 0 4px rgba(255, 69, 58, 0.2);
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

    .btn-apple-red {
        background: #ff3b30;
        color: #ffffff;
        box-shadow: 0 4px 12px rgba(255, 59, 48, 0.2);
    }
    .btn-apple-red:hover {
        background: #e02e24;
        box-shadow: 0 6px 16px rgba(255, 59, 48, 0.3);
    }
    .dark .btn-apple-red {
        background: #ff453a;
        color: #000000;
        box-shadow: 0 4px 12px rgba(255, 69, 58, 0.15);
    }
    .dark .btn-apple-red:hover {
        background: #eb3b30;
        box-shadow: 0 6px 16px rgba(255, 69, 58, 0.25);
    }

    /* Premium Tables */
    .premium-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13.5px;
    }

    .premium-table th {
        background: rgba(255, 59, 48, 0.08) !important;
        color: #c92a2a !important;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 10.5px;
        letter-spacing: 0.08em;
        padding: 14px 18px;
        text-align: left;
        border-bottom: 2px solid rgba(255, 59, 48, 0.2) !important;
    }

    .dark .premium-table th {
        background: rgba(255, 69, 58, 0.15) !important;
        color: #ff453a !important;
        border-bottom-color: rgba(255, 69, 58, 0.3) !important;
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
</style>

<!-- Page Header -->
<div class="mb-8">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-50 text-red-700 dark:bg-red-950/30 dark:text-red-400">
                    Módulo de Finanzas
                </span>
            </div>
            <h1 class="text-3xl md:text-4xl font-extrabold tracking-tight text-gray-900 dark:text-white mb-1">Egresos / Salidas</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 font-medium">Registra gastos administrativos, servicios básicos y compras operativas del Hotel</p>
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
            <span class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Total Egresos</span>
            <div class="w-7 h-7 bg-red-500/10 rounded-full flex items-center justify-center">
                <svg class="w-4 h-4 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12h-15"/>
                </svg>
            </div>
        </div>
        <div>
            <span class="text-3xl font-black text-red-600 dark:text-red-400 tracking-tight font-variant-numeric-tabular">
                Bs. <?php echo formatMoney($total_egresos); ?>
            </span>
            <p class="text-xs text-gray-400 dark:text-gray-500 font-medium mt-1">Total egresado en el periodo</p>
        </div>
    </div>

    <!-- Widget 2: Conteo -->
    <div class="apple-widget">
        <div class="flex justify-between items-start mb-3">
            <span class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Transacciones</span>
            <div class="w-7 h-7 bg-gray-500/10 rounded-full flex items-center justify-center">
                <svg class="w-4 h-4 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25z"/>
                </svg>
            </div>
        </div>
        <div>
            <span class="text-3xl font-black text-noir dark:text-white tracking-tight">
                <?php echo $transacciones_egresos; ?>
            </span>
            <p class="text-xs text-gray-400 dark:text-gray-500 font-medium mt-1">Registros de egresos cargados</p>
        </div>
    </div>

    <!-- Widget 3: Mayor Categoría -->
    <div class="apple-widget">
        <div class="flex justify-between items-start mb-3">
            <span class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Mayor Categoría</span>
            <div class="w-7 h-7 bg-orange-500/10 rounded-full flex items-center justify-center">
                <svg class="w-4 h-4 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.5 4.5M21.75 6.75L12 16.5l-4.5-4.5"/>
                </svg>
            </div>
        </div>
        <div>
            <span class="text-2xl font-black text-noir dark:text-white tracking-tight truncate block max-w-full">
                <?php echo htmlspecialchars($mayor_categoria); ?>
            </span>
            <p class="text-xs text-gray-400 dark:text-gray-500 font-medium mt-1.5">Categoría con más desembolsos</p>
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
    <!-- Formulario de Registro Estilo Control Center con Segmento iOS -->
    <div class="lg:col-span-1">
        <div class="apple-card-clean lg:sticky lg:top-4 border-t-4 border-t-red-500">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-11 h-11 bg-gradient-to-br from-red-400 to-red-600 rounded-xl flex items-center justify-center shadow-sm">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12h-15"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-noir dark:text-white tracking-tight">Nueva Salida</h2>
                    <p class="text-xs text-gray-400 dark:text-gray-500 font-medium">Registrar egreso de dinero</p>
                </div>
            </div>
            
            <form method="POST" action="" class="space-y-5">
                <!-- Selector Segmentado Táctil estilo iOS (Reemplazo de select dropdown) -->
                <div class="space-y-2">
                    <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                        Categoría de Egreso <span class="text-red-500">*</span>
                    </label>
                    <div class="relative flex p-1 bg-gray-100 dark:bg-zinc-900 rounded-2xl border border-gray-200/50 dark:border-zinc-800/80">
                        <label class="flex-1 text-center cursor-pointer">
                            <input type="radio" name="categoria" value="Externo" class="sr-only peer" checked>
                            <div class="py-2.5 rounded-xl text-xs font-bold text-gray-500 dark:text-gray-450 peer-checked:bg-white dark:peer-checked:bg-zinc-800 peer-checked:text-red-600 dark:peer-checked:text-red-400 transition-all duration-200 shadow-sm peer-checked:shadow">
                                Gastos Externos
                            </div>
                        </label>
                        <label class="flex-1 text-center cursor-pointer">
                            <input type="radio" name="categoria" value="Cafetería" class="sr-only peer">
                            <div class="py-2.5 rounded-xl text-xs font-bold text-gray-500 dark:text-gray-450 peer-checked:bg-white dark:peer-checked:bg-zinc-800 peer-checked:text-red-600 dark:peer-checked:text-red-400 transition-all duration-200 shadow-sm peer-checked:shadow">
                                Cafetería
                            </div>
                        </label>
                    </div>
                </div>
                
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                        Concepto / Detalle <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="concepto" 
                           value="<?php echo isset($_POST['concepto']) ? htmlspecialchars($_POST['concepto']) : ''; ?>"
                           required
                           class="input-apple"
                           placeholder="Ej: Pago de luz, Compra de café...">
                </div>
                
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                        Monto Desembolsado (Bs.) <span class="text-red-500">*</span>
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
                
                <input type="hidden" name="fecha" value="<?php echo date('Y-m-d'); ?>">
                <input type="hidden" name="observaciones" value="Egreso administrativo">
                
                <button type="submit" name="registrar_egreso" class="w-full btn-apple btn-apple-red">
                    Registrar Egreso
                </button>
            </form>
        </div>
    </div>
    
    <!-- Lista de Egresos Reorganizada -->
    <div class="lg:col-span-2">
        <div class="apple-card-clean">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <div>
                    <h2 class="text-lg font-bold text-noir dark:text-white tracking-tight">Historial de Egresos</h2>
                    <p class="text-xs text-gray-400 dark:text-gray-500 font-medium">Transacciones de egresos y gastos operativos</p>
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
            
            <!-- Contenedor de la Tabla con Corrección de tr -->
            <div class="table-responsive">
                <table class="premium-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Concepto / Detalle</th>
                            <th>Categoría</th>
                            <th class="text-right">Monto</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-150 dark:divide-zinc-800">
                        <?php foreach ($egresos as $egr): ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-zinc-900/30 transition-colors">
                                <td class="text-gray-500 dark:text-gray-400 font-medium">
                                    <?php echo formatDate($egr['fecha']); ?>
                                </td>
                                <td class="font-semibold text-noir dark:text-white">
                                    <?php echo htmlspecialchars($egr['concepto']); ?>
                                </td>
                                <td>
                                    <?php if ($egr['categoria']): ?>
                                        <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider
                                            <?php 
                                            if ($egr['categoria'] == 'Cafetería') echo 'bg-orange-50 text-orange-700 dark:bg-orange-950/20 dark:text-orange-400';
                                            elseif ($egr['categoria'] == 'Externo') echo 'bg-red-50 text-red-700 dark:bg-red-950/20 dark:text-red-400';
                                            else echo 'bg-gray-50 text-gray-700 dark:bg-zinc-800 dark:text-gray-400';
                                            ?>">
                                            <?php echo htmlspecialchars($egr['categoria']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-gray-400 dark:text-gray-500 font-semibold">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-right font-extrabold text-red-600 dark:text-red-400 font-variant-numeric-tabular">
                                    Bs. <?php echo formatMoney($egr['monto']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($egresos)): ?>
                            <tr>
                                <td colspan="4" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                                    <svg class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-650 mb-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    No se encontraron egresos registrados en este periodo.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pie de totales estilo pastilla flotante Apple -->
            <div class="mt-6 flex justify-end">
                <div class="bg-noir dark:bg-black text-white px-6 py-4 rounded-2xl flex items-center gap-6 shadow-sm border border-gray-800">
                    <span class="text-xs font-bold uppercase tracking-widest text-gray-400">Total Egresos:</span>
                    <span class="text-xl font-black text-red-400 font-variant-numeric-tabular">
                        Bs. <?php echo formatMoney($total_egresos); ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
