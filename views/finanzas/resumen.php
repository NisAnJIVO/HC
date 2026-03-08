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
@media print {
    .no-print { display: none !important; }
    
    /* Ocultar completamente todo el header/nav */
    nav,
    header,
    .fixed,
    body > nav,
    [role="navigation"],
    #mobile-menu,
    #mobile-menu-btn,
    button#theme-toggle {
        display: none !important;
        visibility: hidden !important;
        height: 0 !important;
        overflow: hidden !important;
    }
    
    /* Asegurar que nada con position fixed se muestre */
    * {
        position: static !important;
    }
    
    /* Ocultar scripts y elementos no deseados */
    script,
    noscript,
    .hidden {
        display: none !important;
    }
    
    /* Resetear body para impresión */
    body {
        padding-top: 0 !important;
        margin-top: 0 !important;
    }
    
    /* Resetear contenedores principales */
    .pt-12,
    .pt-20,
    .py-12,
    .py-8,
    .px-6 {
        padding: 0 !important;
    }
    
    .max-w-7xl {
        max-width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    
    /* Asegurar que el contenido principal sea limpio */
    body > div,
    body > div > div {
        padding: 0 !important;
        margin: 0 !important;
    }
    
    /* Solo el print-container debe tener padding */
    body > * {
        padding: 0 !important;
        margin: 0 !important;
    }
    
    body .print-container {
        padding: 0.5cm !important;
    }
    
    @page {
        size: letter portrait;
        margin: 1.2cm 1cm 1cm 1cm;
    }
    
    /* Forzar impresi\u00f3n de colores de fondo */
    * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        color-adjust: exact !important;
    }
    
    body {
        background: white !important;
        padding: 0 !important;
        font-size: 9pt;
        color: #000 !important;
    }
    .print-container {
        display: block !important;
        background: white !important;
        box-shadow: none !important;
        border: none !important;
        border-radius: 0 !important;
        margin: 0 !important;
        padding: 0.5cm !important;
        max-width: 100% !important;
        width: 100% !important;
    }
    
    /* Eliminar todos los bordes redondeados */
    .rounded,
    .rounded-xl,
    .rounded-2xl,
    .rounded-lg {
        border-radius: 0 !important;
    }
    
    /* Header del informe - ocultar en impresión */
    .print-header {
        display: none !important;
    }
    
    .print-header-formal {
        display: block !important;
        margin-bottom: 0.3cm !important;
        padding-bottom: 0.15cm !important;
        page-break-after: avoid;
    }
    
    /* Tablas pueden dividirse entre páginas */
    table { 
        page-break-inside: auto; 
        font-size: 8pt;
        margin-bottom: 0.3cm !important;
        border-collapse: collapse !important;
    }
    
    /* Evitar que las filas se corten */
    table tr {
        page-break-inside: avoid;
        page-break-after: auto;
    }
    
    /* Headers de tablas se repiten */
    thead {
        display: table-header-group;
        font-weight: bold !important;
    }
    
    /* Estilo formal para headers de tabla */
    table thead tr {
        background-color: #e8e8e8 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    
    table thead th {
        border: 1px solid #000 !important;
        background-color: #e8e8e8 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    
    /* Bordes de celdas */
    table td,
    table th {
        border: 1px solid #666 !important;
    }
    
    /* Footers de tablas */
    tfoot {
        display: table-footer-group;
    }
    
    table th,
    table td {
        padding: 2px 4px !important;
        line-height: 1.3 !important;
    }
    
    /* Filas totales destacadas */
    tr.font-bold {
        background-color: #f0f0f0 !important;
        font-weight: bold !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    
    tr.bg-green-100 {
        background-color: #d1fae5 !important;
        font-weight: bold !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    
    tr.bg-blue-100 {
        background-color: #dbeafe !important;
        font-weight: bold !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    
    tr.bg-red-100 {
        background-color: #fee2e2 !important;
        font-weight: bold !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    
    .signature-section { 
        margin-top: 1.5cm !important; 
        page-break-inside: avoid;
        border-top: 1px solid #000 !important;
        padding-top: 0.3cm !important;
    }
    
    .signature-section .border-t {
        border-top: 1px solid #000 !important;
    }
    
    /* Estilos para el resumen consolidado */
    .mb-3.border {
        border: 1px solid #000 !important;
    }
    
    /* Tabla del resumen consolidado */
    .mb-3.border table {
        border: none !important;
    }
    
    .mb-3.border table td {
        border: none !important;
        border-bottom: 1px solid #ddd !important;
        padding: 0.1cm 0.2cm !important;
    }
    
    .mb-3.border table tr:last-child td {
        border-bottom: none !important;
    }
    
    /* Fila de "Efectivo a Entregar" destacada */
    .mb-3.border table tr.border-y td,
    .mb-3.border table tr.bg-yellow-50 td {
        border-top: 2px solid #000 !important;
        border-bottom: 2px solid #000 !important;
        background-color: #fef3c7 !important;
        font-size: 10pt !important;
        padding: 0.15cm 0.2cm !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    
    /* Mantener colores de texto en resumen */
    .mb-3.border .text-green-600 {
        color: #059669 !important;
    }
    
    .mb-3.border .text-blue-600 {
        color: #2563eb !important;
    }
    
    .mb-3.border .text-red-600 {
        color: #dc2626 !important;
    }
    
    .mb-3.border .text-yellow-800 {
        color: #92400e !important;
    }
    
    h1 { 
        font-size: 15pt; 
        margin-bottom: 0.15cm !important; 
        line-height: 1.2 !important;
    }
    h2 { 
        font-size: 12pt; 
        margin-bottom: 0.15cm !important; 
        line-height: 1.2 !important;
    }
    h3 { 
        font-size: 10pt; 
        margin-bottom: 0.1cm !important;
        line-height: 1.2 !important;
    }
    
    /* Reducir espacios entre secciones */
    .print-container > div {
        margin-bottom: 0.3cm !important;
    }
    
    /* Eliminar espacio superior de la primera sección después del resumen */
    .summary-table + .mb-4,
    .summary-table ~ .mb-4:first-of-type {
        margin-top: 0 !important;
        page-break-before: avoid !important;
    }
    
    /* Hacer títulos de secciones más compactos y formales */
    .bg-gray-800,
    .bg-green-600,
    .bg-blue-600,
    .bg-red-600 {
        color: #fff !important;
        border: none !important;
        border-bottom: 2px solid #000 !important;
        padding: 0.15cm 0.2cm !important;
        margin-bottom: 0.15cm !important;
        page-break-after: avoid;
        font-size: 10pt !important;
        font-weight: bold !important;
        text-transform: uppercase;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    
    .bg-green-600 {
        background-color: #059669 !important;
    }
    
    .bg-blue-600 {
        background-color: #2563eb !important;
    }
    
    .bg-red-600 {
        background-color: #dc2626 !important;
    }
    
    .bg-gray-800 {
        background-color: #1f2937 !important;
    }
    
    .bg-green-600 span,
    .bg-blue-600 span,
    .bg-red-600 span {
        display: inline !important;
        color: rgba(255, 255, 255, 0.9) !important;
    }
    
    /* Resumen ejecutivo - cambiar a tabla formal */
    .summary-cards {
        display: none !important;
    }
    
    .summary-table {
        display: table !important;
        width: 100% !important;
        margin-bottom: 0.25cm !important;
        border: 1px solid #000 !important;
        page-break-after: avoid !important;
    }
    
    /* Evitar que secciones se corten al inicio */
    .mb-4 {
        page-break-inside: auto;
        margin-bottom: 0.3cm !important;
    }
    
    /* Primera sección debe estar pegada al resumen */
    .mb-4:first-of-type {
        margin-top: 0 !important;
        page-break-before: avoid !important;
    }
    
    /* Asegurar que no haya espacios extra */
    .summary-table {
        margin-bottom: 0.25cm !important;
    }
    
    /* Siguiente elemento después de la tabla de resumen */
    .print-container > .summary-table + * {
        margin-top: 0 !important;
        padding-top: 0 !important;
    }
    
    /* Asegurar que subtotales no se separen de sus tablas */
    tr.font-bold {
        page-break-before: avoid;
    }
    
    /* Mantener colores de fondo para mejor legibilidad */
    .bg-green-50 {
        background-color: #f0fdf4 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    
    .bg-blue-50 {
        background-color: #eff6ff !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    
    .bg-red-50 {
        background-color: #fef2f2 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    
    .bg-yellow-50 {
        background-color: #fefce8 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    
    .bg-gray-50 {
        background-color: #f9fafb !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    
    /* Mantener colores de texto legibles */
    .text-green-600,
    .text-green-700,
    .text-green-800 {
        color: #065f46 !important;
    }
    
    .text-blue-600,
    .text-blue-700 {
        color: #1e40af !important;
    }
    
    .text-red-600,
    .text-red-700 {
        color: #b91c1c !important;
    }
    
    .text-yellow-700,
    .text-yellow-800 {
        color: #a16207 !important;
    }
    
    .text-gray-500,
    .text-gray-600,
    .text-gray-700,
    .text-gray-900 {
        color: #000 !important;
    }
    
    /* Texto blanco para headers de secciones */
    .bg-green-600 *,
    .bg-blue-600 *,
    .bg-red-600 *,
    .bg-gray-800 * {
        color: #fff !important;
    }
    
    /* Enlaces y botones */
    a, button {
        color: #000 !important;
        text-decoration: none !important;
    }
    
    /* Bordes visibles */
    .border-gray-300,
    .border-gray-200,
    .border-gray-700,
    .border-gray-800 {
        border-color: #666 !important;
    }
}

.compact-table {
    font-size: 10px;
    line-height: 1.3;
}
.compact-table th,
.compact-table td {
    padding: 3px 6px;
}

@media (min-width: 768px) {
    .compact-table {
        font-size: 11px;
    }
    .compact-table th,
    .compact-table td {
        padding: 4px 8px;
    }
}

/* Hacer tablas scrolleables en móvil */
.table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

@media (max-width: 767px) {
    .compact-table thead th {
        font-size: 9px;
        padding: 2px 4px;
    }
    .compact-table tbody td {
        font-size: 9px;
        padding: 2px 4px;
    }
}
</style>

<!-- Botones de acción (no se imprimen) -->
<div class="no-print mb-8">
    <?php if (isset($_GET['error']) && $_GET['error'] === 'acceso_denegado'): ?>
    <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl">
        <div class="flex items-center gap-3">
            <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400"></i>
            <div>
                <p class="text-sm font-semibold text-red-900 dark:text-red-300">Acceso Denegado</p>
                <p class="text-xs text-red-700 dark:text-red-400 mt-1">No tienes permisos para acceder a esa sección. Solo los administradores pueden registrar ingresos y egresos.</p>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-4xl font-bold text-noir dark:text-white mb-2">Resumen Financiero</h1>
            <p class="text-sm md:text-base text-gray-500 dark:text-gray-400">Informe detallado de caja para liquidación</p>
        </div>
        <div class="flex gap-2 md:gap-3">
            <button onclick="window.print()" class="flex-1 md:flex-none px-3 md:px-6 py-2 md:py-3 bg-gray-900 dark:bg-gray-700 text-white rounded-lg md:rounded-xl text-sm md:text-base font-medium hover:bg-gray-800 dark:hover:bg-gray-600 transition-all duration-200">
                <span class="hidden md:inline"></span>Imprimir
            </button>
            <a href="<?php echo BASE_PATH; ?>/index.php" class="flex-1 md:flex-none px-3 md:px-6 py-2 md:py-3 border border-gray-300 dark:border-gray-700 rounded-lg md:rounded-xl text-gray-700 dark:text-gray-300 text-sm md:text-base font-medium hover:bg-mist dark:hover:bg-gray-800 transition-all duration-200 text-center">
                ← Volver
            </a>
        </div>
    </div>
</div>

<!-- Filtro de Fechas (no se imprime) -->
<div class="no-print bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 overflow-hidden mb-8">
    <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-800 bg-gradient-to-r from-blue-50 to-white dark:from-blue-900/20 dark:to-gray-900">
        <h2 class="text-xl font-semibold text-noir dark:text-white">Período de Análisis</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Selecciona el rango de fechas para el informe</p>
    </div>
    
    <form method="GET" class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="space-y-2">
                <label class="block text-sm font-semibold text-noir dark:text-white">Fecha Inicio</label>
                <input type="date" name="fecha_inicio" 
                       value="<?php echo $fecha_inicio; ?>"
                       class="w-full px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 text-noir dark:text-white bg-white dark:bg-gray-800">
            </div>
            <div class="space-y-2">
                <label class="block text-sm font-semibold text-noir dark:text-white">Fecha Fin</label>
                <input type="date" name="fecha_fin" 
                       value="<?php echo $fecha_fin; ?>"
                       class="w-full px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 text-noir dark:text-white bg-white dark:bg-gray-800">
            </div>
            <div class="space-y-2">
                <label class="block text-sm font-semibold text-noir dark:text-white">
                    <i class="fas fa-user text-gray-500 mr-1"></i>
                    Recepcionista
                </label>
                <select name="recepcionista" id="recepcionista_select"
                        onchange="toggleRecepcionistaOtro()"
                        class="w-full px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 text-noir dark:text-white bg-white dark:bg-gray-800">
                    <option value="Isaac Vargas" <?php echo ($recepcionista === 'Isaac Vargas') ? 'selected' : ''; ?>>Isaac Vargas</option>
                    <option value="Gabriel Duran" <?php echo ($recepcionista === 'Gabriel Duran') ? 'selected' : ''; ?>>Gabriel Duran</option>
                    <option value="otro" <?php echo (!in_array($recepcionista, ['Isaac Vargas', 'Gabriel Duran'])) ? 'selected' : ''; ?>>Otro (escribir nombre)</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" 
                        class="w-full px-6 py-3.5 bg-gray-900 dark:bg-gray-700 text-white font-semibold rounded-xl hover:bg-gray-800 dark:hover:bg-gray-600 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:scale-105">
                    Actualizar Resumen
                </button>
            </div>
        </div>
        
        <!-- Campo de texto para "Otro recepcionista" -->
        <div id="recepcionista_otro_div" class="mt-4" style="display: <?php echo (!in_array($recepcionista, ['Isaac Vargas', 'Gabriel Duran'])) ? 'block' : 'none'; ?>;">
            <label class="block text-sm font-semibold text-noir dark:text-white mb-2">
                <i class="fas fa-pencil-alt text-gray-500 mr-1"></i>
                Nombre del Recepcionista
            </label>
            <input type="text" 
                   name="recepcionista_otro" 
                   id="recepcionista_otro_input"
                   value="<?php echo (!in_array($recepcionista, ['Isaac Vargas', 'Gabriel Duran'])) ? htmlspecialchars($recepcionista) : ''; ?>"
                   placeholder="Ej: María López"
                   class="w-full md:w-1/2 px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 text-noir dark:text-white bg-white dark:bg-gray-800">
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Ingresa el nombre completo del recepcionista</p>
        </div>
    </form>
</div>

<!-- INFORME IMPRIMIBLE -->
<div class="print-container bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 overflow-hidden p-6">
    
    <!-- Header decorativo (solo pantalla) -->
    <div class="print-header mb-4 pb-3 border-b-2 border-gray-800 dark:border-gray-600">
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">HOTEL CECIL</h1>
                <h2 class="text-base font-semibold text-gray-700 dark:text-gray-300">Informe de Liquidación de Caja</h2>
            </div>
            <div class="text-right text-xs text-gray-600 dark:text-gray-400">
                <p><strong>Período:</strong> <?php echo formatDate($fecha_inicio); ?> - <?php echo formatDate($fecha_fin); ?></p>
                <p><strong>Emitido:</strong> <?php echo date('d/m/Y H:i'); ?></p>
                <p><strong>Recepcionista:</strong> <?php echo htmlspecialchars($recepcionista); ?></p>
            </div>
        </div>
    </div>
    
    <!-- Header formal (solo impresión) -->
    <div class="print-header-formal" style="display: none;">
        <table class="w-full text-xs" style="border: none;">
            <tr>
                <td style="width: 70%; vertical-align: top; border: none;">
                    <div style="font-size: 16pt; font-weight: bold; margin-bottom: 0.1cm;">HOTEL CECIL</div>
                    <div style="font-size: 11pt; font-weight: 600; margin-bottom: 0.1cm;">INFORME DE LIQUIDACIÓN DE CAJA</div>
                    <div style="font-size: 9pt;">Recepcionista: <?php echo htmlspecialchars($recepcionista); ?></div>
                </td>
                <td style="width: 30%; vertical-align: top; text-align: right; border: none;">
                    <div style="font-size: 9pt; line-height: 1.4;">
                        <strong>Período:</strong><br>
                        <?php echo formatDate($fecha_inicio); ?><br>
                        al <?php echo formatDate($fecha_fin); ?><br><br>
                        <strong>Fecha de Emisión:</strong><br>
                        <?php echo date('d/m/Y H:i'); ?>
                    </div>
                </td>
            </tr>
        </table>
        <div style="border-bottom: 2px solid #000; margin-top: 0.2cm; margin-bottom: 0.3cm;"></div>
    </div>

    <!-- Resumen Ejecutivo (solo pantalla) -->
    <div class="summary-cards grid grid-cols-2 md:grid-cols-4 gap-2 md:gap-3 mb-4 text-xs">
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded p-2">
            <p class="text-green-700 dark:text-green-400 font-semibold mb-1 text-[10px] md:text-xs">Ingresos Efectivo</p>
            <p class="text-sm md:text-lg font-bold text-green-900 dark:text-green-300">Bs. <?php echo formatMoney($total_efectivo); ?></p>
            <p class="text-[9px] md:text-[10px] text-green-600 dark:text-green-500"><?php echo count($ingresos_efectivo); ?> transac.</p>
        </div>
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded p-2">
            <p class="text-blue-700 dark:text-blue-400 font-semibold mb-1 text-[10px] md:text-xs">Ingresos QR</p>
            <p class="text-sm md:text-lg font-bold text-blue-900 dark:text-blue-300">Bs. <?php echo formatMoney($total_qr); ?></p>
            <p class="text-[9px] md:text-[10px] text-blue-600 dark:text-blue-500"><?php echo count($ingresos_qr); ?> transac.</p>
        </div>
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded p-2">
            <p class="text-red-700 dark:text-red-400 font-semibold mb-1 text-[10px] md:text-xs">Egresos</p>
            <p class="text-sm md:text-lg font-bold text-red-900 dark:text-red-300">Bs. <?php echo formatMoney($total_egresos); ?></p>
            <p class="text-[9px] md:text-[10px] text-red-600 dark:text-red-500"><?php echo count($egresos); ?> transac.</p>
        </div>
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded p-2">
            <p class="text-yellow-700 dark:text-yellow-400 font-semibold mb-1 text-[10px] md:text-xs">Balance Caja</p>
            <p class="text-sm md:text-lg font-bold text-yellow-900 dark:text-yellow-300">Bs. <?php echo formatMoney($balance_recepcionista); ?></p>
            <p class="text-[9px] md:text-[10px] text-yellow-600 dark:text-yellow-500">A entregar</p>
        </div>
    </div>
    
    <!-- Resumen Ejecutivo formal (solo impresión) -->
    <table class="summary-table w-full text-xs mb-4" style="display: none; border-collapse: collapse;">
        <thead>
            <tr style="background-color: #e8e8e8; -webkit-print-color-adjust: exact; print-color-adjust: exact;">
                <th style="border: 1px solid #000; padding: 0.15cm; text-align: left; font-weight: bold;">CONCEPTO</th>
                <th style="border: 1px solid #000; padding: 0.15cm; text-align: center; font-weight: bold;">TRANSACCIONES</th>
                <th style="border: 1px solid #000; padding: 0.15cm; text-align: right; font-weight: bold;">MONTO (Bs.)</th>
            </tr>
        </thead>
        <tbody>
            <tr style="background-color: #d1fae5;">
                <td style="border: 1px solid #000; padding: 0.1cm;">Ingresos en Efectivo</td>
                <td style="border: 1px solid #000; padding: 0.1cm; text-align: center;"><?php echo count($ingresos_efectivo); ?></td>
                <td style="border: 1px solid #000; padding: 0.1cm; text-align: right; font-weight: 600; color: #065f46;"><?php echo formatMoney($total_efectivo); ?></td>
            </tr>
            <tr style="background-color: #dbeafe;">
                <td style="border: 1px solid #000; padding: 0.1cm;">Ingresos por QR (Banco)</td>
                <td style="border: 1px solid #000; padding: 0.1cm; text-align: center;"><?php echo count($ingresos_qr); ?></td>
                <td style="border: 1px solid #000; padding: 0.1cm; text-align: right; font-weight: 600; color: #1e40af;"><?php echo formatMoney($total_qr); ?></td>
            </tr>
            <tr style="background-color: #fee2e2;">
                <td style="border: 1px solid #000; padding: 0.1cm;">Egresos (Gastos)</td>
                <td style="border: 1px solid #000; padding: 0.1cm; text-align: center;"><?php echo count($egresos); ?></td>
                <td style="border: 1px solid #000; padding: 0.1cm; text-align: right; font-weight: 600; color: #b91c1c;"><?php echo formatMoney($total_egresos); ?></td>
            </tr>
            <tr style="background-color: #fef3c7; font-weight: bold;">
                <td style="border: 1px solid #000; padding: 0.1cm; font-weight: bold;" colspan="2">BALANCE DE CAJA (A Entregar)</td>
                <td style="border: 1px solid #000; padding: 0.1cm; text-align: right; font-size: 10pt; font-weight: bold; color: #92400e;"><?php echo formatMoney($balance_recepcionista); ?></td>
            </tr>
        </tbody>
    </table>

    <!-- SECCIÓN 1: INGRESOS EN EFECTIVO -->
    <div class="mb-4">
        <div class="bg-green-600 text-white px-2 md:px-3 py-1.5 mb-2 flex flex-col md:flex-row md:items-center md:justify-between gap-1">
            <h3 class="text-xs md:text-sm font-bold">1. INGRESOS EN EFECTIVO</h3>
            <span class="text-[10px] md:text-xs opacity-90 hidden md:inline">Dinero físico manejado por el recepcionista</span>
        </div>
        
        <div class="table-responsive">
            <table class="w-full compact-table border border-gray-300 dark:border-gray-700">
            <thead class="bg-gray-100 dark:bg-gray-800">
                <tr>
                    <th class="text-left border-b border-gray-300 dark:border-gray-700">Fecha</th>
                    <th class="text-left border-b border-gray-300 dark:border-gray-700">Concepto/Descripción</th>
                    <th class="text-center border-b border-gray-300 dark:border-gray-700">Hab.</th>
                    <th class="text-left border-b border-gray-300 dark:border-gray-700">Huésped</th>
                    <th class="text-right border-b border-gray-300 dark:border-gray-700">Monto (Bs.)</th>
                </tr>
            </thead>
            <tbody class="text-gray-900 dark:text-gray-300">
                <?php if (empty($ingresos_efectivo)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-3 text-gray-500 dark:text-gray-400 italic">Sin movimientos en efectivo</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($ingresos_efectivo as $ing): ?>
                        <tr class="border-b border-gray-200 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/50">
                            <td class="whitespace-nowrap"><?php echo date('d/m/Y', strtotime($ing['fecha'])); ?></td>
                            <td><?php echo htmlspecialchars($ing['concepto']); ?></td>
                            <td class="text-center"><?php echo $ing['nro_pieza'] ?? '-'; ?></td>
                            <td class="text-xs"><?php echo $ing['nombres_apellidos'] ? htmlspecialchars($ing['nombres_apellidos']) : '-'; ?></td>
                            <td class="text-right font-semibold"><?php echo formatMoney($ing['monto']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="bg-green-100 dark:bg-green-900/30 font-bold">
                        <td colspan="4" class="text-right py-2">SUBTOTAL EFECTIVO:</td>
                        <td class="text-right">Bs. <?php echo formatMoney($total_efectivo); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>

    <!-- SECCIÓN 2: INGRESOS POR QR -->
    <div class="mb-4">
        <div class="bg-blue-600 text-white px-2 md:px-3 py-1.5 mb-2 flex flex-col md:flex-row md:items-center md:justify-between gap-1">
            <h3 class="text-xs md:text-sm font-bold">2. INGRESOS POR QR</h3>
            <span class="text-[10px] md:text-xs opacity-90 hidden md:inline">Transferencias bancarias directas</span>
        </div>
        
        <div class="table-responsive">
            <table class="w-full compact-table border border-gray-300 dark:border-gray-700">
            <thead class="bg-gray-100 dark:bg-gray-800">
                <tr>
                    <th class="text-left border-b border-gray-300 dark:border-gray-700">Fecha</th>
                    <th class="text-left border-b border-gray-300 dark:border-gray-700">Concepto/Descripción</th>
                    <th class="text-center border-b border-gray-300 dark:border-gray-700">Hab.</th>
                    <th class="text-left border-b border-gray-300 dark:border-gray-700">Huésped</th>
                    <th class="text-right border-b border-gray-300 dark:border-gray-700">Monto (Bs.)</th>
                </tr>
            </thead>
            <tbody class="text-gray-900 dark:text-gray-300">
                <?php if (empty($ingresos_qr)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-3 text-gray-500 dark:text-gray-400 italic">Sin pagos QR registrados</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($ingresos_qr as $ing): ?>
                        <tr class="border-b border-gray-200 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/50">
                            <td class="whitespace-nowrap"><?php echo date('d/m/Y', strtotime($ing['fecha'])); ?></td>
                            <td><?php echo htmlspecialchars($ing['concepto']); ?></td>
                            <td class="text-center"><?php echo $ing['nro_pieza'] ?? '-'; ?></td>
                            <td class="text-xs"><?php echo $ing['nombres_apellidos'] ? htmlspecialchars($ing['nombres_apellidos']) : '-'; ?></td>
                            <td class="text-right font-semibold"><?php echo formatMoney($ing['monto']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="bg-blue-100 dark:bg-blue-900/30 font-bold">
                        <td colspan="4" class="text-right py-2">SUBTOTAL QR (YA EN BANCO):</td>
                        <td class="text-right">Bs. <?php echo formatMoney($total_qr); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>

    <!-- SECCIÓN 3: EGRESOS -->
    <div class="mb-4">
        <div class="bg-red-600 text-white px-2 md:px-3 py-1.5 mb-2 flex flex-col md:flex-row md:items-center md:justify-between gap-1">
            <h3 class="text-xs md:text-sm font-bold">3. EGRESOS</h3>
            <span class="text-[10px] md:text-xs opacity-90 hidden md:inline">Salidas de caja del recepcionista</span>
        </div>
        
        <div class="table-responsive">
            <table class="w-full compact-table border border-gray-300 dark:border-gray-700">
            <thead class="bg-gray-100 dark:bg-gray-800">
                <tr>
                    <th class="text-left border-b border-gray-300 dark:border-gray-700">Fecha</th>
                    <th class="text-left border-b border-gray-300 dark:border-gray-700">Categoría</th>
                    <th class="text-left border-b border-gray-300 dark:border-gray-700">Descripción del Gasto</th>
                    <th class="text-right border-b border-gray-300 dark:border-gray-700">Monto (Bs.)</th>
                </tr>
            </thead>
            <tbody class="text-gray-900 dark:text-gray-300">
                <?php if (empty($egresos)): ?>
                    <tr>
                        <td colspan="4" class="text-center py-3 text-gray-500 dark:text-gray-400 italic">Sin egresos registrados</td>
                    </tr>
                <?php else: ?>
                    <?php 
                    $egresos_por_categoria = [];
                    foreach ($egresos as $egr) {
                        $cat = $egr['categoria'] ?? 'Sin categoría';
                        if (!isset($egresos_por_categoria[$cat])) {
                            $egresos_por_categoria[$cat] = 0;
                        }
                        $egresos_por_categoria[$cat] += $egr['monto'];
                    ?>
                        <tr class="border-b border-gray-200 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/50">
                            <td class="whitespace-nowrap"><?php echo date('d/m/Y', strtotime($egr['fecha'])); ?></td>
                            <td class="text-xs"><?php echo htmlspecialchars($cat); ?></td>
                            <td><?php echo htmlspecialchars($egr['concepto']); ?></td>
                            <td class="text-right font-semibold"><?php echo formatMoney($egr['monto']); ?></td>
                        </tr>
                    <?php } ?>
                    <tr class="bg-red-100 dark:bg-red-900/30 font-bold">
                        <td colspan="3" class="text-right py-2">TOTAL EGRESOS:</td>
                        <td class="text-right">Bs. <?php echo formatMoney($total_egresos); ?></td>
                    </tr>
                    <tr class="bg-gray-50 dark:bg-gray-800 text-xs">
                        <td colspan="4" class="py-2 px-3">
                            <strong>Desglose por categoría:</strong>
                            <?php foreach ($egresos_por_categoria as $cat => $monto): ?>
                                <span class="inline-block mr-3"><?php echo htmlspecialchars($cat); ?>: Bs. <?php echo formatMoney($monto); ?></span>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>

    <!-- RESUMEN CONSOLIDADO Y LIQUIDACIÓN -->
    <div class="mb-3 border border-gray-800 dark:border-gray-600" style="border: 1px solid #000;">
        <div class="bg-gray-800 dark:bg-gray-700 text-white px-3 py-1">
            <h3 class="text-xs font-bold uppercase tracking-wide">4. Resumen y Liquidación Final</h3>
        </div>
        
        <div class="p-2 sm:p-3">
            <table class="w-full text-[10px] sm:text-xs">
                <tbody>
                    <tr class="border-b border-gray-200 dark:border-gray-700">
                        <td class="py-1 text-gray-700 dark:text-gray-300">Ingresos Efectivo (Caja)</td>
                        <td class="py-1 text-right font-semibold text-green-600 dark:text-green-400">+ Bs. <?php echo formatMoney($total_efectivo); ?></td>
                    </tr>
                    <tr class="border-b border-gray-200 dark:border-gray-700">
                        <td class="py-1 text-gray-700 dark:text-gray-300">Egresos (Gastos)</td>
                        <td class="py-1 text-right font-semibold text-red-600 dark:text-red-400">- Bs. <?php echo formatMoney($total_egresos); ?></td>
                    </tr>
                    <tr class="bg-yellow-50 dark:bg-yellow-900/10 border-y border-yellow-300 dark:border-yellow-800">
                        <td class="py-1 sm:py-1.5 font-semibold text-yellow-800 dark:text-yellow-300">Efectivo a Entregar</td>
                        <td class="py-1 sm:py-1.5 text-right font-bold text-base sm:text-lg text-yellow-800 dark:text-yellow-300">Bs. <?php echo formatMoney($balance_recepcionista); ?></td>
                    </tr>
                    <tr class="border-b border-gray-200 dark:border-gray-700">
                        <td class="py-1 text-gray-600 dark:text-gray-400 text-[9px] sm:text-xs">Ingresos QR (Banco Sol)</td>
                        <td class="py-1 text-right font-semibold text-blue-600 dark:text-blue-400">Bs. <?php echo formatMoney($total_qr); ?></td>
                    </tr>
                    <tr class="bg-gray-50 dark:bg-gray-800 border-t border-gray-300 dark:border-gray-700">
                        <td class="py-1 sm:py-1.5 font-semibold text-gray-900 dark:text-white">Ingreso Bruto Total</td>
                        <td class="py-1 sm:py-1.5 text-right font-bold text-sm sm:text-base text-gray-900 dark:text-white">Bs. <?php echo formatMoney($total_efectivo + $total_qr); ?></td>
                    </tr>
                    <tr class="bg-green-50 dark:bg-green-900/10">
                        <td class="py-1 sm:py-1.5 font-semibold text-green-800 dark:text-green-300">Utilidad Neta</td>
                        <td class="py-1 sm:py-1.5 text-right font-bold text-sm sm:text-base text-green-700 dark:text-green-400">Bs. <?php echo formatMoney($total_efectivo + $total_qr - $total_egresos); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- FIRMAS Y VALIDACIÓN -->
    <div class="signature-section mt-6 pt-3" style="border-top: 1px solid #000; margin-top: 0.5cm; padding-top: 0.3cm; text-align: center;">
        <p style="font-size: 8pt; margin-bottom: 0.8cm;">
            Yo, <strong>Rodolfo Moscoso</strong>, recibo <strong>Bs. <?php echo formatMoney($balance_recepcionista); ?></strong> 
            desde la fecha <strong><?php echo date('d/m/Y', strtotime($fecha_inicio)); ?></strong> 
            hasta la fecha <strong><?php echo date('d/m/Y', strtotime($fecha_fin)); ?></strong>
        </p>
        <div style="margin: 1.5cm auto 0 auto; border-top: 1px solid #000; width: 40%; text-align: center; padding-top: 0.1cm; font-size: 7pt;">
            Firma
        </div>
    </div>

</div>

<!-- Vista rápida en pantalla (no se imprime) -->
<div class="no-print mt-8">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Efectivo (Caja) -->
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6 hover:border-gray-400 dark:hover:border-gray-500 transition-colors">
            <div class="flex items-center justify-between mb-4">
                <span class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400 font-medium">Efectivo (Caja)</span>
                <i class="fas fa-money-bill-wave text-gray-400 dark:text-gray-500 text-sm"></i>
            </div>
            <p class="text-3xl font-semibold text-gray-900 dark:text-white">Bs. <?php echo formatMoney($total_efectivo); ?></p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Ingresos en efectivo</p>
        </div>

        <!-- QR (Don Rodolfo) -->
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6 hover:border-gray-400 dark:hover:border-gray-500 transition-colors">
            <div class="flex items-center justify-between mb-4">
                <span class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400 font-medium">QR (Don Rodolfo)</span>
                <i class="fas fa-qrcode text-gray-400 dark:text-gray-500 text-sm"></i>
            </div>
            <p class="text-3xl font-semibold text-gray-900 dark:text-white">Bs. <?php echo formatMoney($total_qr); ?></p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Pagos directos a Don Rodolfo</p>
        </div>

        <!-- A Entregar -->
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6 hover:border-gray-400 dark:hover:border-gray-500 transition-colors">
            <div class="flex items-center justify-between mb-4">
                <span class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400 font-medium">A Entregar</span>
                <i class="fas fa-hand-holding-usd text-gray-400 dark:text-gray-500 text-sm"></i>
            </div>
            <p class="text-3xl font-semibold text-gray-900 dark:text-white">Bs. <?php echo formatMoney($balance_recepcionista); ?></p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Efectivo - Egresos</p>
        </div>
    </div>
</div>

<script>
function toggleRecepcionistaOtro() {
    const selectElement = document.getElementById('recepcionista_select');
    const otroDiv = document.getElementById('recepcionista_otro_div');
    const otroInput = document.getElementById('recepcionista_otro_input');
    
    if (selectElement.value === 'otro') {
        otroDiv.style.display = 'block';
        otroInput.required = true;
        otroInput.focus();
    } else {
        otroDiv.style.display = 'none';
        otroInput.required = false;
    }
}

// Ejecutar al cargar la página por si viene con "otro" seleccionado
document.addEventListener('DOMContentLoaded', function() {
    toggleRecepcionistaOtro();
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
