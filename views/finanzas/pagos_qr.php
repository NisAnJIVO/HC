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

// Calcular estadísticas rápidas para la cabecera (widgets)
$total_qr = 0;
$transacciones_qr = count($pagos_qr);
foreach ($pagos_qr as $pqr) {
    $total_qr += $pqr['monto'];
}
$ultimo_trx = 'Ninguno';
if ($transacciones_qr > 0) {
    $ultimo_trx = $pagos_qr[0]['numero_transaccion'] ?: 'Sin TRX';
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
        border-color: #5856d6;
        box-shadow: 0 0 0 4px rgba(88, 86, 214, 0.15);
    }
    .dark .input-apple:focus {
        background: #1c1c1e;
        border-color: #5e5ce6;
        box-shadow: 0 0 0 4px rgba(94, 92, 230, 0.2);
    }
    .input-apple-amount {
        padding-left: 48px !important;
    }
    .select-apple {
        appearance: none !important;
        -webkit-appearance: none !important;
        -moz-appearance: none !important;
        background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='10' height='6' viewBox='0 0 10 6'><path fill='%23666666' d='M0,0 L5,5 L10,0 Z'/></svg>") !important;
        background-repeat: no-repeat !important;
        background-position: right 16px center !important;
        background-size: 10px 6px !important;
        padding-right: 40px !important;
    }
    .dark .select-apple {
        background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='10' height='6' viewBox='0 0 10 6'><path fill='%23cccccc' d='M0,0 L5,5 L10,0 Z'/></svg>") !important;
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

    .btn-apple-purple {
        background: #5856d6;
        color: #ffffff;
        box-shadow: 0 4px 12px rgba(88, 86, 214, 0.2);
    }
    .btn-apple-purple:hover {
        background: #4745c4;
        box-shadow: 0 6px 16px rgba(88, 86, 214, 0.3);
    }
    .dark .btn-apple-purple {
        background: #5e5ce6;
        color: #ffffff;
        box-shadow: 0 4px 12px rgba(94, 92, 230, 0.15);
    }
    .dark .btn-apple-purple:hover {
        background: #4e4cd4;
        box-shadow: 0 6px 16px rgba(94, 92, 230, 0.25);
    }

    /* Premium Tables */
    .premium-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13.5px;
    }

    .premium-table th {
        background: rgba(88, 86, 214, 0.08) !important;
        color: #4c4ab2 !important;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 10.5px;
        letter-spacing: 0.08em;
        padding: 14px 18px;
        text-align: left;
        border-bottom: 2px solid rgba(88, 86, 214, 0.2) !important;
    }

    .dark .premium-table th {
        background: rgba(94, 92, 230, 0.15) !important;
        color: #9896f1 !important;
        border-bottom-color: rgba(94, 92, 230, 0.3) !important;
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

    .stat-badge {
        background: linear-gradient(135deg, rgba(88, 86, 214, 0.08), rgba(94, 92, 230, 0.08));
        border: 1px solid rgba(88, 86, 214, 0.15);
    }
    .dark .stat-badge {
        background: linear-gradient(135deg, rgba(88, 86, 214, 0.15), rgba(94, 92, 230, 0.15));
        border: 1px solid rgba(88, 86, 214, 0.25);
    }
</style>

<!-- Hero Section -->
<div class="mb-8">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-purple-50 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300">
                    Módulo de Finanzas
                </span>
            </div>
            <h1 class="text-3xl md:text-4xl font-extrabold tracking-tight text-gray-900 dark:text-white mb-1">Pagos QR</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 font-medium font-medium">Visualización y control de transacciones bancarias digitales rápidas</p>
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
    <!-- Widget 1: Total QR -->
    <div class="apple-widget">
        <div class="flex justify-between items-start mb-3">
            <span class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Total QR</span>
            <div class="w-7 h-7 bg-purple-500/10 rounded-full flex items-center justify-center">
                <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-6 15h9m-9-3h9m-9-3h9m-9-3h9"/>
                </svg>
            </div>
        </div>
        <div>
            <span class="text-3xl font-black text-purple-600 dark:text-purple-400 tracking-tight font-variant-numeric-tabular">
                Bs. <?php echo formatMoney($total_qr); ?>
            </span>
            <p class="text-xs text-gray-400 dark:text-gray-500 font-medium mt-1">Total recaudado por transferencias</p>
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
                <?php echo $transacciones_qr; ?>
            </span>
            <p class="text-xs text-gray-400 dark:text-gray-500 font-medium mt-1">Registros digitales activos</p>
        </div>
    </div>

    <!-- Widget 3: Último Código -->
    <div class="apple-widget">
        <div class="flex justify-between items-start mb-3">
            <span class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Último TRX</span>
            <div class="w-7 h-7 bg-blue-500/10 rounded-full flex items-center justify-center">
                <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z"/>
                </svg>
            </div>
        </div>
        <div>
            <span class="text-lg font-mono font-black text-noir dark:text-white tracking-tight truncate block max-w-full">
                <?php echo htmlspecialchars($ultimo_trx); ?>
            </span>
            <p class="text-xs text-gray-400 dark:text-gray-500 font-medium mt-2">Última TRX QR validada</p>
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

<!-- Grid Principal -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 lg:gap-8">
    
    <!-- Formulario Registro estilo Control Center con Selector Táctil (1/3) -->
    <div class="lg:col-span-1">
        <div class="apple-card-clean lg:sticky lg:top-4 border-t-4 border-t-purple-500">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-11 h-11 bg-gradient-to-br from-purple-400 to-purple-600 rounded-xl flex items-center justify-center shadow-sm">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path>
                    </svg>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-noir dark:text-white tracking-tight">Registrar Pago</h2>
                    <p class="text-xs text-gray-400 dark:text-gray-500 font-medium">Nueva entrada por QR</p>
                </div>
            </div>
            
            <form method="POST" action="" class="space-y-5">
                <!-- Selector Segmentado Táctil estilo iOS (Reorganización de Tipo de Cobro) -->
                <div class="space-y-2">
                    <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                        Tipo de Cobro <span class="text-red-500">*</span>
                    </label>
                    <div class="relative flex p-1 bg-gray-100 dark:bg-zinc-900 rounded-2xl border border-gray-200/50 dark:border-zinc-800/80">
                        <label class="flex-1 text-center cursor-pointer">
                            <input type="radio" name="tipo_pago" value="huesped" class="sr-only peer" checked onchange="toggleTipoPago()">
                            <div class="py-2.5 rounded-xl text-xs font-bold text-gray-500 dark:text-gray-450 peer-checked:bg-white dark:peer-checked:bg-zinc-800 peer-checked:text-purple-600 dark:peer-checked:text-purple-400 transition-all duration-200 shadow-sm peer-checked:shadow">
                                Huésped
                            </div>
                        </label>
                        <label class="flex-1 text-center cursor-pointer">
                            <input type="radio" name="tipo_pago" value="externo" class="sr-only peer" onchange="toggleTipoPago()">
                            <div class="py-2.5 rounded-xl text-xs font-bold text-gray-500 dark:text-gray-450 peer-checked:bg-white dark:peer-checked:bg-zinc-800 peer-checked:text-purple-600 dark:peer-checked:text-purple-400 transition-all duration-200 shadow-sm peer-checked:shadow">
                                Externo
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Ocupación (Huésped) - Se muestra / oculta con animación suave en JS -->
                <div id="ocupacion_div" class="space-y-1.5 transition-all duration-300 origin-top">
                    <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                        Huésped Hospedado <span class="text-gray-400 text-[10px]">(Opcional)</span>
                    </label>
                    <div class="relative">
                        <select name="ocupacion_id" class="input-apple select-apple">
                            <option value="">Sin vincular (Solo guardar cobro)</option>
                            <?php foreach ($ocupaciones_activas as $ocu): ?>
                                <option value="<?php echo $ocu['id']; ?>">
                                    Hab <?php echo $ocu['nro_pieza']; ?> - <?php echo htmlspecialchars($ocu['nombres_apellidos']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Concepto (Externo) - Se muestra / oculta dinámicamente -->
                <div id="concepto_div" class="space-y-1.5 transition-all duration-300 origin-top" style="display: none;">
                    <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                        Concepto del Cobro <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        name="concepto"
                        id="concepto_input"
                        placeholder="Ej: Consumo frigobar, Lavandería externa..."
                        class="input-apple"
                    >
                </div>
                
                <!-- Monto -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                        Monto QR (Bs.) <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 dark:text-gray-500 font-bold text-sm">Bs.</span>
                        <input 
                            type="number" 
                            step="0.01" 
                            name="monto" 
                            required
                            class="input-apple input-apple-amount"
                            placeholder="0.00"
                        >
                    </div>
                </div>
                
                <!-- Número de Transacción -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                        Nro de Transacción (TRX) <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        name="numero_transaccion"
                        required
                        class="input-apple font-mono uppercase"
                        placeholder="Ej: 82934823"
                    >
                </div>
                
                <!-- Fecha -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                        Fecha <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="date" 
                        name="fecha" 
                        value="<?php echo date('Y-m-d'); ?>"
                        required
                        class="input-apple"
                    >
                </div>
                
                <!-- Observaciones -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                        Observaciones Adicionales
                    </label>
                    <textarea 
                        name="observaciones" 
                        rows="2"
                        class="input-apple resize-none h-16"
                        placeholder="Detalles de la transferencia..."
                    ></textarea>
                </div>
                
                <!-- Botón de Envío -->
                <button 
                    type="submit" 
                    name="registrar_pago_qr"
                    class="w-full btn-apple btn-apple-purple"
                >
                    Registrar Pago QR
                </button>
            </form>
        </div>
    </div>
    
    <!-- Lista de Pagos Púrpura (2/3) -->
    <div class="lg:col-span-2">
        <div class="apple-card-clean">
            <!-- Header con Filtros -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <div>
                    <h2 class="text-lg font-bold text-noir dark:text-white tracking-tight">Historial de Pagos QR</h2>
                    <p class="text-xs text-gray-400 dark:text-gray-500 font-medium">Registro de transferencias y conciliaciones</p>
                </div>
            </div>
            
            <!-- Filtro de Fechas estilo iOS -->
            <form method="GET" class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6 p-5 bg-gray-50 dark:bg-zinc-900/50 rounded-2xl border border-gray-100 dark:border-zinc-800">
                <div>
                    <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1.5">Desde:</label>
                    <input 
                        type="date" 
                        name="fecha_inicio" 
                        value="<?php echo $fecha_inicio; ?>"
                        class="input-apple"
                    >
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1.5">Hasta:</label>
                    <input 
                        type="date" 
                        name="fecha_fin" 
                        value="<?php echo $fecha_fin; ?>"
                        class="input-apple"
                    >
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
            
            <!-- Tabla Púrpura Premium -->
            <div class="table-responsive">
                <table class="premium-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>Huésped / Concepto</th>
                            <th>Código TRX</th>
                            <th class="text-right">Monto</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-150 dark:divide-zinc-800">
                        <?php 
                        foreach ($pagos_qr as $pqr): 
                            $es_externo = ($pqr['tipo'] ?? 'huesped') === 'externo';
                        ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-zinc-900/30 transition-colors">
                                <td class="text-gray-500 dark:text-gray-400 font-medium">
                                    <?php echo date('d/m/Y', strtotime($pqr['fecha'])); ?>
                                </td>
                                <td>
                                    <?php if ($es_externo): ?>
                                        <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-blue-50 text-blue-700 dark:bg-blue-950/20 dark:text-blue-400">
                                            Externo
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-purple-50 text-purple-700 dark:bg-purple-950/20 dark:text-purple-400">
                                            Huésped
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    if ($es_externo && !empty($pqr['concepto'])) {
                                        echo '<span class="font-bold text-noir dark:text-white">' . htmlspecialchars($pqr['concepto']) . '</span>';
                                        if (!empty($pqr['observaciones'])) {
                                            echo '<br><span class="text-[11px] text-gray-400 dark:text-gray-500 font-medium">' . htmlspecialchars($pqr['observaciones']) . '</span>';
                                        }
                                    } elseif ($pqr['nombres_apellidos']) {
                                        echo '<span class="font-bold text-noir dark:text-white">' . htmlspecialchars($pqr['nombres_apellidos']) . '</span><br>';
                                        echo '<span class="px-2 py-0.5 rounded text-[10.5px] font-bold bg-gray-150 dark:bg-zinc-800 text-gray-500 dark:text-gray-400">Hab. ' . $pqr['nro_pieza'] . '</span>';
                                    } else {
                                        echo '<span class="text-gray-400 dark:text-gray-550 font-medium italic">Sin detalles</span>';
                                    }
                                    ?>
                                </td>
                                <td class="font-mono text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wide">
                                    <?php echo htmlspecialchars($pqr['numero_transaccion'] ?? '-'); ?>
                                </td>
                                <td class="text-right font-extrabold text-purple-600 dark:text-purple-400 font-variant-numeric-tabular">
                                    Bs. <?php echo number_format($pqr['monto'], 2); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($pagos_qr)): ?>
                            <tr>
                                <td colspan="5" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                                    <svg class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-650 mb-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path>
                                    </svg>
                                    No se encontraron pagos QR en este periodo.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pie de totales flotante estilo Apple -->
            <div class="mt-6 flex justify-end">
                <div class="stat-badge px-6 py-4 rounded-2xl flex items-center gap-6 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-widest text-purple-750 dark:text-purple-300">Total QR:</span>
                    <span class="text-xl font-black text-purple-700 dark:text-purple-300 font-variant-numeric-tabular">
                        Bs. <?php echo number_format($total_qr, 2); ?>
                    </span>
                </div>
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
        ocupacionDiv.style.opacity = '0';
        setTimeout(() => {
            ocupacionDiv.style.display = 'none';
            conceptoDiv.style.display = 'block';
            setTimeout(() => {
                conceptoDiv.style.opacity = '1';
            }, 50);
        }, 200);
        conceptoInput.required = true;
    } else {
        conceptoDiv.style.opacity = '0';
        setTimeout(() => {
            conceptoDiv.style.display = 'none';
            ocupacionDiv.style.display = 'block';
            setTimeout(() => {
                ocupacionDiv.style.opacity = '1';
            }, 50);
        }, 200);
        conceptoInput.required = false;
    }
}

// Inicializar al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    // Configurar estilos iniciales para transiciones fluidas
    const ocupacionDiv = document.getElementById('ocupacion_div');
    const conceptoDiv = document.getElementById('concepto_div');
    
    ocupacionDiv.style.transition = 'opacity 0.25s ease';
    conceptoDiv.style.transition = 'opacity 0.25s ease';
    
    toggleTipoPago();
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
