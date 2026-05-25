<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/Finanzas.php';

$page_title = 'Resumen Financiero';

// Obtener fechas del filtro
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');

// Obtener recepcionista seleccionado
$recepcionista = $_GET['recepcionista'] ?? 'Isaac Vargas';
// Si es "otro", usar el valor personalizado
if ($recepcionista === 'otro' && !empty($_GET['recepcionista_otro'])) {
    $recepcionista = clean_input($_GET['recepcionista_otro']);
}

$finanzasModel = new Finanzas();
$resumen = $finanzasModel->obtenerResumen($fecha_inicio, $fecha_fin);
$ingresos = $finanzasModel->obtenerIngresos($fecha_inicio, $fecha_fin);
$egresos = $finanzasModel->obtenerEgresos($fecha_inicio, $fecha_fin);
$pagos_qr = $finanzasModel->obtenerPagosQR($fecha_inicio, $fecha_fin);

// Separar ingresos por método de pago
$ingresos_efectivo = array_filter($ingresos, function($ing) {
    return $ing['metodo_pago'] === 'efectivo';
});
$ingresos_qr = array_filter($ingresos, function($ing) {
    return $ing['metodo_pago'] === 'qr';
});

// Calcular totales separados
$total_efectivo = array_sum(array_column($ingresos_efectivo, 'monto'));
$total_qr = array_sum(array_column($ingresos_qr, 'monto'));
$total_egresos = array_sum(array_column($egresos, 'monto'));

// Balance del recepcionista (solo efectivo menos egresos)
$balance_recepcionista = $total_efectivo - $total_egresos;

include __DIR__ . '/../../includes/header.php';
?>

<style>
/* ═══════════════════════════════════════════════
   Apple Premium Finance Aesthetic (Screen & Print)
   ═══════════════════════════════════════════════ */

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
    box-shadow: 0 4px 24px rgba(0, 0, 0, 0.015);
    padding: 30px;
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.dark .apple-card {
    background: #161616;
    border-color: rgba(255, 255, 255, 0.06);
    box-shadow: 0 4px 24px rgba(0, 0, 0, 0.15);
}

/* Premium Form Inputs */
.apple-input {
    width: 100%;
    background: rgba(0, 0, 0, 0.02);
    border: 1px solid rgba(0, 0, 0, 0.08);
    border-radius: 14px;
    padding: 12px 16px;
    font-size: 14px;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    color: #1d1d1f;
}

.apple-input:focus {
    outline: none;
    border-color: #0071e3;
    background: #ffffff;
    box-shadow: 0 0 0 4px rgba(0, 113, 227, 0.12);
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

/* Premium Metric Cards */
.mini-metric {
    background: #ffffff;
    border: 1px solid rgba(0, 0, 0, 0.06);
    border-radius: 18px;
    padding: 20px;
    transition: all 0.2s ease;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.005);
}

.dark .mini-metric {
    background: rgba(255, 255, 255, 0.02);
    border-color: rgba(255, 255, 255, 0.05);
}

.mini-num {
    font-size: 24px;
    font-weight: 800;
    letter-spacing: -0.04em;
    color: #1d1d1f;
    font-variant-numeric: tabular-nums;
}

.dark .mini-num {
    color: #ffffff;
}

.mini-label {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #86868b;
    margin-bottom: 8px;
}

.dark .mini-label {
    color: #aeaeb2;
}

/* Premium Color Highlights for Metric Boxes */
.metric-efectivo {
    background: rgba(52, 199, 89, 0.04);
    border-color: rgba(52, 199, 89, 0.15);
}
.dark .metric-efectivo {
    background: rgba(52, 199, 89, 0.06);
    border-color: rgba(52, 199, 89, 0.2);
}

.metric-qr {
    background: rgba(0, 113, 227, 0.04);
    border-color: rgba(0, 113, 227, 0.15);
}
.dark .metric-qr {
    background: rgba(0, 113, 227, 0.06);
    border-color: rgba(0, 113, 227, 0.2);
}

.metric-egresos {
    background: rgba(255, 59, 48, 0.04);
    border-color: rgba(255, 59, 48, 0.15);
}
.dark .metric-egresos {
    background: rgba(255, 59, 48, 0.06);
    border-color: rgba(255, 59, 48, 0.2);
}

.metric-balance {
    background: rgba(255, 149, 0, 0.04);
    border-color: rgba(255, 149, 0, 0.15);
}
.dark .metric-balance {
    background: rgba(255, 149, 0, 0.06);
    border-color: rgba(255, 149, 0, 0.2);
}

/* Premium Tables */
.premium-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13.5px;
}

.premium-table th {
    background: #f5f5f7;
    color: #86868b;
    font-weight: 700;
    text-transform: uppercase;
    font-size: 10px;
    letter-spacing: 0.06em;
    padding: 12px 18px;
    text-align: left;
    border-bottom: 1px solid rgba(0, 0, 0, 0.06);
}

.dark .premium-table th {
    background: rgba(255, 255, 255, 0.03);
    color: #aeaeb2;
    border-bottom-color: rgba(255, 255, 255, 0.06);
}

.premium-table td {
    padding: 12px 18px;
    border-bottom: 1px solid rgba(0, 0, 0, 0.04);
    color: #1d1d1f;
    font-variant-numeric: tabular-nums;
}

.dark .premium-table td {
    color: #f5f5f7;
    border-bottom-color: rgba(255, 255, 255, 0.04);
}

.premium-table tr:last-child td {
    border-bottom: none;
}

.premium-table tr:hover td {
    background: rgba(0, 0, 0, 0.008);
}

.dark .premium-table tr:hover td {
    background: rgba(255, 255, 255, 0.008);
}

.table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    border-radius: 16px;
    border: 1px solid rgba(0, 0, 0, 0.05);
}

.dark .table-responsive {
    border-color: rgba(255, 255, 255, 0.06);
}

.section-divider {
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.07em;
    text-transform: uppercase;
    color: #86868b;
    border-bottom: 1px solid rgba(0, 0, 0, 0.06);
    padding-bottom: 6px;
    margin-bottom: 16px;
    margin-top: 10px;
}

.dark .section-divider {
    color: #aeaeb2;
    border-bottom-color: rgba(255, 255, 255, 0.08);
}

/* ═══════════════════════════════════════════════
   LUXURIOUS COLORFUL PRINT / PDF STYLES
   ═══════════════════════════════════════════════ */
@media print {
    .no-print { display: none !important; }
    
    nav, header, .fixed, body > nav, [role="navigation"], #mobile-menu, #mobile-menu-btn, button#theme-toggle {
        display: none !important;
        visibility: hidden !important;
        height: 0 !important;
        overflow: hidden !important;
    }
    
    * {
        position: static !important;
    }
    
    body {
        background-color: #f5f5f7 !important;
        color: #1d1d1f !important;
        font-size: 9.5pt !important;
        padding-top: 0 !important;
        margin: 0 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    
    .print-container {
        display: block !important;
        background-color: #ffffff !important;
        border: 1px solid rgba(0, 0, 0, 0.08) !important;
        border-radius: 24px !important;
        box-shadow: 0 4px 24px rgba(0, 0, 0, 0.015) !important;
        padding: 0.8cm !important;
        max-width: 100% !important;
        width: 100% !important;
        margin: 0 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    
    .summary-cards {
        display: grid !important;
        grid-template-columns: repeat(4, 1fr) !important;
        gap: 12px !important;
        margin-bottom: 0.6cm !important;
    }
    
    .mini-metric {
        background-color: #ffffff !important;
        border: 1px solid rgba(0, 0, 0, 0.06) !important;
        border-radius: 16px !important;
        padding: 14px !important;
        display: block !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    
    .metric-efectivo {
        background-color: rgba(52, 199, 89, 0.05) !important;
        border-color: rgba(52, 199, 89, 0.18) !important;
    }
    .metric-qr {
        background-color: rgba(0, 113, 227, 0.05) !important;
        border-color: rgba(0, 113, 227, 0.18) !important;
    }
    .metric-egresos {
        background-color: rgba(255, 59, 48, 0.05) !important;
        border-color: rgba(255, 59, 48, 0.18) !important;
    }
    .metric-balance {
        background-color: rgba(255, 149, 0, 0.05) !important;
        border-color: rgba(255, 149, 0, 0.18) !important;
    }

    .table-responsive {
        border-radius: 16px !important;
        border: 1px solid rgba(0, 0, 0, 0.06) !important;
        overflow: hidden !important;
        background-color: #ffffff !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    
    table { 
        page-break-inside: auto; 
        font-size: 8.5pt !important;
        margin-bottom: 0.5cm !important;
        border-collapse: collapse !important;
        width: 100% !important;
    }
    
    table tr {
        page-break-inside: avoid;
        page-break-after: auto;
    }
    
    thead {
        display: table-header-group;
    }
    
    .premium-table th {
        background-color: #f5f5f7 !important;
        color: #86868b !important;
        font-weight: 700 !important;
        border-bottom: 1px solid rgba(0, 0, 0, 0.08) !important;
        padding: 10px 14px !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    
    .premium-table td {
        border-bottom: 1px solid rgba(0, 0, 0, 0.05) !important;
        padding: 10px 14px !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    
    .section-divider {
        font-size: 10pt !important;
        font-weight: 800 !important;
        border-bottom: 1px solid rgba(0, 0, 0, 0.08) !important;
        padding-bottom: 5px !important;
        margin-top: 0.6cm !important;
        margin-bottom: 0.3cm !important;
        color: #86868b !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    
    .signature-section { 
        margin-top: 1.2cm !important; 
        page-break-inside: avoid;
        border-top: 1px solid rgba(0,0,0,0.1) !important;
        padding-top: 0.4cm !important;
    }
    
    @page {
        size: letter portrait;
        margin: 1.2cm 1cm 1cm 1cm;
    }
}
</style>

<!-- Action Header -->
<div class="no-print mb-8">
    <?php if (isset($_GET['error']) && $_GET['error'] === 'acceso_denegado'): ?>
    <div class="mb-6 p-4 bg-red-50 dark:bg-red-950/20 border border-red-200 dark:border-red-800/30 rounded-2xl animate-fade-in">
        <div class="flex items-center gap-3">
            <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <div>
                <p class="text-sm font-bold text-red-900 dark:text-red-300">Acceso Denegado</p>
                <p class="text-xs text-red-700 dark:text-red-450 mt-0.5">No tienes permisos para acceder a esa sección. Solo los administradores pueden registrar ingresos y egresos.</p>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-gray-900 dark:text-white mb-1">Resumen Financiero</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 font-medium">Liquidación de caja y auditoría financiera de ingresos y egresos</p>
        </div>
        <div class="flex gap-2">
            <button onclick="window.print()" class="px-5 py-3 bg-gray-900 hover:bg-gray-800 dark:bg-gray-100 dark:hover:bg-white text-white dark:text-gray-900 text-sm font-semibold rounded-2xl transition duration-205 shadow-sm flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Imprimir PDF
            </button>
            <a href="<?php echo BASE_PATH; ?>/index.php" class="px-5 py-3 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-750 text-gray-700 dark:text-gray-300 rounded-2xl border border-gray-200 dark:border-gray-700 transition font-semibold text-sm text-center shadow-sm">
                Volver
            </a>
        </div>
    </div>
</div>

<!-- Filter Sheet -->
<div class="no-print apple-card mb-8">
    <div class="pb-4 mb-5 border-b border-gray-100 dark:border-gray-800">
        <h2 class="text-base font-extrabold text-gray-900 dark:text-white">Período de Análisis</h2>
        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Selecciona el rango de fechas y recepcionista para el informe</p>
    </div>
    
    <form method="GET">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-5">
            <div class="space-y-2">
                <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Fecha Inicio</label>
                <input type="date" name="fecha_inicio" value="<?php echo $fecha_inicio; ?>" class="apple-input">
            </div>
            <div class="space-y-2">
                <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Fecha Fin</label>
                <input type="date" name="fecha_fin" value="<?php echo $fecha_fin; ?>" class="apple-input">
            </div>
            <div class="space-y-2">
                <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Recepcionista</label>
                <select name="recepcionista" id="recepcionista_select" onchange="toggleRecepcionistaOtro()" class="apple-input bg-white cursor-pointer">
                    <option value="Isaac Vargas" <?php echo ($recepcionista === 'Isaac Vargas') ? 'selected' : ''; ?>>Isaac Vargas</option>
                    <option value="Gabriel Duran" <?php echo ($recepcionista === 'Gabriel Duran') ? 'selected' : ''; ?>>Gabriel Duran</option>
                    <option value="otro" <?php echo (!in_array($recepcionista, ['Isaac Vargas', 'Gabriel Duran'])) ? 'selected' : ''; ?>>Otro...</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full px-5 py-3 bg-gray-900 dark:bg-gray-100 hover:bg-gray-800 dark:hover:bg-white text-white dark:text-gray-900 font-bold rounded-2xl transition duration-200 shadow-sm text-sm text-center">
                    Actualizar Resumen
                </button>
            </div>
        </div>
        
        <!-- Custom Receptionist Field -->
        <div id="recepcionista_otro_div" class="mt-4" style="display: <?php echo (!in_array($recepcionista, ['Isaac Vargas', 'Gabriel Duran'])) ? 'block' : 'none'; ?>;">
            <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">Nombre del Recepcionista</label>
            <input type="text" name="recepcionista_otro" id="recepcionista_otro_input" value="<?php echo (!in_array($recepcionista, ['Isaac Vargas', 'Gabriel Duran'])) ? htmlspecialchars($recepcionista) : ''; ?>" placeholder="Ej: María López" class="apple-input md:w-1/2">
        </div>
    </form>
</div>

<!-- PRINTABLE CONTAINER -->
<div class="print-container apple-card mb-8">
    
    <!-- Header -->
    <div class="mb-6 pb-5 border-b-2 border-gray-900 dark:border-gray-800">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-gray-900 dark:text-white uppercase tracking-tight">HOTEL CECIL</h1>
                <h2 class="text-xs font-bold text-gray-450 dark:text-gray-500 mt-1">Informe de Liquidación de Caja</h2>
            </div>
            <div class="text-left sm:text-right text-xs text-gray-500 space-y-1">
                <p><strong>Período:</strong> <?php echo formatDate($fecha_inicio); ?> al <?php echo formatDate($fecha_fin); ?></p>
                <p><strong>Generado:</strong> <?php echo date('d/m/Y H:i'); ?> hrs</p>
                <p><strong>Auditor/Responsable:</strong> <?php echo htmlspecialchars($recepcionista); ?></p>
            </div>
        </div>
    </div>

    <!-- Executive Metrics Grid (Kept beautifully in screen AND print PDF) -->
    <div class="summary-cards grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="mini-metric metric-efectivo">
            <div class="mini-label">Ingresos Efectivo</div>
            <div class="mini-num text-emerald-600 dark:text-emerald-450">Bs. <?php echo formatMoney($total_efectivo); ?></div>
            <p class="text-[10px] text-gray-400 dark:text-gray-500 mt-1 font-semibold"><?php echo count($ingresos_efectivo); ?> transacciones</p>
        </div>
        <div class="mini-metric metric-qr">
            <div class="mini-label">Ingresos QR</div>
            <div class="mini-num text-blue-600 dark:text-blue-450">Bs. <?php echo formatMoney($total_qr); ?></div>
            <p class="text-[10px] text-gray-400 dark:text-gray-500 mt-1 font-semibold"><?php echo count($ingresos_qr); ?> transacciones</p>
        </div>
        <div class="mini-metric metric-egresos">
            <div class="mini-label">Egresos (Gastos)</div>
            <div class="mini-num text-red-650 dark:text-red-400">Bs. <?php echo formatMoney($total_egresos); ?></div>
            <p class="text-[10px] text-gray-400 dark:text-gray-500 mt-1 font-semibold"><?php echo count($egresos); ?> transacciones</p>
        </div>
        <div class="mini-metric metric-balance">
            <div class="mini-label">Balance Neto Caja</div>
            <div class="mini-num text-amber-600 dark:text-amber-500">Bs. <?php echo formatMoney($balance_recepcionista); ?></div>
            <p class="text-[10px] text-gray-400 dark:text-gray-500 mt-1 font-semibold">Efectivo a liquidar</p>
        </div>
    </div>

    <!-- 1. INGRESOS EN EFECTIVO -->
    <div class="mb-8">
        <div class="section-divider">1. Ingresos en Efectivo</div>
        
        <div class="table-responsive">
            <table class="premium-table">
                <thead>
                    <tr>
                        <th style="width: 15%">Fecha</th>
                        <th style="width: 35%">Concepto / Detalle</th>
                        <th style="width: 10%; text-align: center;">Habit.</th>
                        <th style="width: 25%">Huésped</th>
                        <th style="width: 15%; text-align: right;">Monto (Bs.)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($ingresos_efectivo)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-gray-500 italic">Sin movimientos registrados en efectivo</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($ingresos_efectivo as $ing): ?>
                            <tr>
                                <td class="whitespace-nowrap font-semibold text-gray-700 dark:text-gray-300"><?php echo date('d/m/Y', strtotime($ing['fecha'])); ?></td>
                                <td><?php echo htmlspecialchars($ing['concepto']); ?></td>
                                <td class="text-center font-bold text-gray-900 dark:text-white"><?php echo $ing['nro_pieza'] ?? '-'; ?></td>
                                <td class="text-gray-500 dark:text-gray-400 font-medium"><?php echo $ing['nombres_apellidos'] ? htmlspecialchars($ing['nombres_apellidos']) : '-'; ?></td>
                                <td class="text-right font-bold text-emerald-600 dark:text-emerald-400"><?php echo formatMoney($ing['monto']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="bg-emerald-500/[0.03] dark:bg-emerald-500/[0.06] font-bold border-t border-emerald-100 dark:border-emerald-950">
                            <td colspan="4" class="text-right py-3.5 pr-4 text-emerald-800 dark:text-emerald-450 uppercase tracking-wider text-[10px]">Total Recaudado Efectivo:</td>
                            <td class="text-right text-emerald-600 dark:text-emerald-400 text-[14px]">Bs. <?php echo formatMoney($total_efectivo); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 2. INGRESOS POR QR -->
    <div class="mb-8">
        <div class="section-divider">2. Ingresos por Código QR</div>
        
        <div class="table-responsive">
            <table class="premium-table">
                <thead>
                    <tr>
                        <th style="width: 15%">Fecha</th>
                        <th style="width: 35%">Concepto / Detalle</th>
                        <th style="width: 10%; text-align: center;">Habit.</th>
                        <th style="width: 25%">Huésped</th>
                        <th style="width: 15%; text-align: right;">Monto (Bs.)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($ingresos_qr)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-gray-500 italic">Sin transferencias QR registradas</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($ingresos_qr as $ing): ?>
                            <tr>
                                <td class="whitespace-nowrap font-semibold text-gray-700 dark:text-gray-300"><?php echo date('d/m/Y', strtotime($ing['fecha'])); ?></td>
                                <td><?php echo htmlspecialchars($ing['concepto']); ?></td>
                                <td class="text-center font-bold text-gray-900 dark:text-white"><?php echo $ing['nro_pieza'] ?? '-'; ?></td>
                                <td class="text-gray-500 dark:text-gray-400 font-medium"><?php echo $ing['nombres_apellidos'] ? htmlspecialchars($ing['nombres_apellidos']) : '-'; ?></td>
                                <td class="text-right font-bold text-blue-600 dark:text-blue-400"><?php echo formatMoney($ing['monto']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="bg-blue-500/[0.03] dark:bg-blue-500/[0.06] font-bold border-t border-blue-100 dark:border-blue-950">
                            <td colspan="4" class="text-right py-3.5 pr-4 text-blue-800 dark:text-blue-450 uppercase tracking-wider text-[10px]">Total Acreditado QR:</td>
                            <td class="text-right text-blue-600 dark:text-blue-400 text-[14px]">Bs. <?php echo formatMoney($total_qr); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 3. EGRESOS -->
    <div class="mb-8">
        <div class="section-divider">3. Egresos (Gastos Operativos de Turno)</div>
        
        <div class="table-responsive">
            <table class="premium-table">
                <thead>
                    <tr>
                        <th style="width: 15%">Fecha</th>
                        <th style="width: 25%">Categoría</th>
                        <th style="width: 45%">Descripción del Gasto</th>
                        <th style="width: 15%; text-align: right;">Monto (Bs.)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($egresos)): ?>
                        <tr>
                            <td colspan="4" class="text-center py-4 text-gray-500 italic">Sin egresos cargados en el período</td>
                        </tr>
                    <?php else: ?>
                        <?php 
                        $egresos_por_categoria = [];
                        foreach ($egresos as $egr) {
                            $cat = $egr['categoria'] ?? 'General';
                            if (!isset($egresos_por_categoria[$cat])) {
                                $egresos_por_categoria[$cat] = 0;
                            }
                            $egresos_por_categoria[$cat] += $egr['monto'];
                        ?>
                            <tr>
                                <td class="whitespace-nowrap font-semibold text-gray-700 dark:text-gray-300"><?php echo date('d/m/Y', strtotime($egr['fecha'])); ?></td>
                                <td class="text-gray-900 dark:text-white font-extrabold uppercase text-[10.5px] tracking-wide"><?php echo htmlspecialchars($cat); ?></td>
                                <td class="text-gray-600 dark:text-gray-400 font-medium"><?php echo htmlspecialchars($egr['concepto']); ?></td>
                                <td class="text-right font-bold text-red-650 dark:text-red-400"><?php echo formatMoney($egr['monto']); ?></td>
                            </tr>
                        <?php } ?>
                        <tr class="bg-red-500/[0.03] dark:bg-red-500/[0.06] font-bold border-t border-red-100 dark:border-red-950">
                            <td colspan="3" class="text-right py-3.5 pr-4 text-red-800 dark:text-red-455 uppercase tracking-wider text-[10px]">Total Egresos Reportados:</td>
                            <td class="text-right text-red-650 dark:text-red-400 text-[14px]">Bs. <?php echo formatMoney($total_egresos); ?></td>
                        </tr>
                        <tr class="bg-gray-50 dark:bg-white/[0.01] text-xs">
                            <td colspan="4" class="py-3 px-5 text-gray-500 border-t border-gray-100 dark:border-gray-800">
                                <strong>Desglose consolidado:</strong>
                                <?php foreach ($egresos_por_categoria as $cat => $monto): ?>
                                    <span class="inline-block mr-5">· <?php echo htmlspecialchars($cat); ?>: <strong class="text-gray-850 dark:text-gray-300">Bs. <?php echo formatMoney($monto); ?></strong></span>
                                <?php endforeach; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 4. RESUMEN Y LIQUIDACIÓN CONSOLIDADO -->
    <div class="mb-6">
        <div class="section-divider">4. Resumen y Liquidación Final</div>
        
        <div class="border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden shadow-sm">
            <table class="w-full text-xs sm:text-sm">
                <tbody>
                    <tr class="border-b border-gray-100 dark:border-gray-800">
                        <td class="px-5 py-3.5 text-gray-600 dark:text-gray-400">Ingresos Efectivo (Caja física)</td>
                        <td class="px-5 py-3.5 text-right font-bold text-emerald-600 dark:text-emerald-450 font-variant-numeric-tabular">+ Bs. <?php echo formatMoney($total_efectivo); ?></td>
                    </tr>
                    <tr class="border-b border-gray-100 dark:border-gray-800">
                        <td class="px-5 py-3.5 text-gray-600 dark:text-gray-400">Egresos de Caja (Gastos autorizados)</td>
                        <td class="px-5 py-3.5 text-right font-bold text-red-650 dark:text-red-450 font-variant-numeric-tabular">- Bs. <?php echo formatMoney($total_egresos); ?></td>
                    </tr>
                    <tr class="bg-amber-500/[0.04] dark:bg-amber-500/[0.1] border-y border-amber-200 dark:border-amber-900/40">
                        <td class="px-5 py-4 font-extrabold text-amber-800 dark:text-amber-300 uppercase tracking-wider text-[11px]">Efectivo Neto a Entregar:</td>
                        <td class="px-5 py-4 text-right font-extrabold text-lg text-amber-800 dark:text-amber-300 font-variant-numeric-tabular">Bs. <?php echo formatMoney($balance_recepcionista); ?></td>
                    </tr>
                    <tr class="border-b border-gray-100 dark:border-gray-800">
                        <td class="px-5 py-3.5 text-gray-500">Ingresos por Código QR (Acreditado en Banco)</td>
                        <td class="px-5 py-3.5 text-right font-semibold text-blue-600 dark:text-blue-400 font-variant-numeric-tabular">Bs. <?php echo formatMoney($total_qr); ?></td>
                    </tr>
                    <tr class="bg-gray-50 dark:bg-white/[0.02] border-t border-gray-200 dark:border-gray-800 font-bold">
                        <td class="px-5 py-3.5 text-gray-900 dark:text-white uppercase tracking-wider text-[10px]">Ingreso Bruto Total:</td>
                        <td class="px-5 py-3.5 text-right font-extrabold text-base text-gray-900 dark:text-white font-variant-numeric-tabular">Bs. <?php echo formatMoney($total_efectivo + $total_qr); ?></td>
                    </tr>
                    <tr class="bg-emerald-500/[0.04] dark:bg-emerald-500/[0.1] font-bold text-emerald-800 dark:text-emerald-450">
                        <td class="px-5 py-3.5 uppercase tracking-wider text-[10px]">Utilidad de Operación Neta:</td>
                        <td class="px-5 py-3.5 text-right font-extrabold text-base font-variant-numeric-tabular">Bs. <?php echo formatMoney($total_efectivo + $total_qr - $total_egresos); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- FIRMAS Y VALIDACIÓN -->
    <div class="signature-section mt-8 pt-5 text-center">
        <p class="text-xs text-gray-700 dark:text-gray-400 font-medium">
            Yo, <strong>Rodolfo Moscoso</strong>, recibo de conformidad el importe líquido de <strong>Bs. <?php echo formatMoney($balance_recepcionista); ?></strong> 
            correspondiente a los turnos del <strong><?php echo date('d/m/Y', strtotime($fecha_inicio)); ?></strong> 
            al <strong><?php echo date('d/m/Y', strtotime($fecha_fin)); ?></strong>.
        </p>
        <div class="mt-14 mx-auto border-t border-gray-450 dark:border-gray-700 pt-2 text-[10px] uppercase font-bold tracking-widest text-gray-400 w-64">
            Firma Auditor / Receptor
        </div>
    </div>

</div>

<!-- Screen Quick Overview Grid (Hidden on print) -->
<div class="no-print mt-8">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="apple-card hover:translate-y-[-2px] transition duration-200">
            <div class="flex items-center justify-between mb-4">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Efectivo en Caja</span>
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
            </div>
            <p class="text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight font-variant-numeric-tabular">Bs. <?php echo formatMoney($total_efectivo); ?></p>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-2">Monto recaudado físicamente en caja</p>
        </div>

        <div class="apple-card hover:translate-y-[-2px] transition duration-200">
            <div class="flex items-center justify-between mb-4">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">QR Don Rodolfo</span>
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h.01M16 12h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <p class="text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight font-variant-numeric-tabular">Bs. <?php echo formatMoney($total_qr); ?></p>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-2">Depósitos bancarios directos por QR</p>
        </div>

        <div class="apple-card hover:translate-y-[-2px] transition duration-200">
            <div class="flex items-center justify-between mb-4">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Líquido a Entregar</span>
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
            </div>
            <p class="text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight font-variant-numeric-tabular">Bs. <?php echo formatMoney($balance_recepcionista); ?></p>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-2">Total en caja física neto de egresos</p>
        </div>
    </div>
</div>

<script>
function toggleRecepcionistaOtro() {
    const select = document.getElementById('recepcionista_select');
    const div = document.getElementById('recepcionista_otro_div');
    const input = document.getElementById('recepcionista_otro_input');
    
    if (select.value === 'otro') {
        div.style.display = 'block';
        input.required = true;
        input.focus();
    } else {
        div.style.display = 'none';
        input.required = false;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    toggleRecepcionistaOtro();
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
