<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/Habitacion.php';
require_once __DIR__ . '/../../models/RegistroOcupacion.php';

$page_title = 'Informe Diario del Hotel';

$habitacionModel = new Habitacion();
$registroModel = new RegistroOcupacion();

// Obtener todas las habitaciones
$habitaciones = $habitacionModel->obtenerTodas();

// Obtener ocupaciones activas
$ocupaciones_activas = $registroModel->obtenerActivos();

// Clasificar habitaciones
$ocupadas = [];
$disponibles = [];
$mantenimiento = [];
$limpieza = [];

foreach ($habitaciones as $hab) {
    switch ($hab['estado']) {
        case 'ocupada':
            $ocupadas[] = $hab['numero'];
            break;
        case 'disponible':
            $disponibles[] = $hab['numero'];
            break;
        case 'mantenimiento':
            $mantenimiento[] = $hab['numero'];
            break;
        case 'limpieza':
            $limpieza[] = $hab['numero'];
            break;
    }
}

// Contar huéspedes y total de habitaciones
$total_huespedes = count($ocupaciones_activas);
$total_habitaciones = count($habitaciones);

// Información del día
$fecha_hoy = date('d/m/Y');
$dia_semana = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'][date('w')];

include __DIR__ . '/../../includes/header.php';
?>

<style>
@media print {
    .no-print {
        display: none !important;
    }
    
    body {
        background: white !important;
        color: black !important;
    }
    
    .informe-container {
        box-shadow: none !important;
        border: 1px solid #000 !important;
        background: white !important;
        color: black !important;
    }
    
    * {
        print-color-adjust: exact !important;
        -webkit-print-color-adjust: exact !important;
    }
    
    @page {
        size: letter;
        margin: 1.5cm;
    }
}

.informe-container {
    font-family: 'Times New Roman', Times, serif;
    line-height: 1.3;
    color: #000;
    background: white;
}

.section-title {
    font-size: 11pt;
    font-weight: bold;
    margin: 8px 0 4px 0;
    text-transform: uppercase;
    border-bottom: 1px solid #000;
    padding-bottom: 2px;
}

.info-line {
    font-size: 10pt;
    margin: 3px 0;
    padding-left: 15px;
}

.info-line:before {
    content: "• ";
    margin-left: -15px;
    font-weight: bold;
}

table.compact-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 9pt;
    margin: 5px 0;
}

table.compact-table td {
    padding: 3px 5px;
    border: 1px solid #000;
}

.notes-area {
    border: 1px solid #000;
    min-height: 80px;
    padding: 8px;
    font-size: 10pt;
    margin-top: 10px;
}
</style>

<!-- Controles (no se imprimen) -->
<div class="no-print mb-6 sm:mb-8">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-4 sm:mb-6">
        <div class="flex-1">
            <h1 class="text-xl sm:text-2xl md:text-4xl font-bold text-noir dark:text-white mb-1 sm:mb-2">Informe Diario del Hotel</h1>
            <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">Resumen ejecutivo para el personal</p>
        </div>
        <button onclick="window.print()" class="px-4 py-2 sm:px-6 sm:py-3 text-sm sm:text-base bg-noir dark:bg-white text-white dark:text-noir rounded-lg sm:rounded-xl font-medium hover:opacity-90 transition-all duration-200 shadow-md sm:shadow-lg flex items-center justify-center gap-2">
            <i class="fas fa-print"></i>
            Imprimir
        </button>
    </div>
    
    <div class="flex gap-2 sm:gap-4">
        <a href="<?php echo BASE_PATH; ?>/index.php" class="flex-1 sm:flex-initial px-3 py-2 sm:px-4 text-sm sm:text-base text-center border border-gray-300 dark:border-gray-700 rounded-lg text-gray-700 dark:text-gray-300 font-medium hover:bg-gray-50 dark:hover:bg-gray-800 transition-all">
            ← Volver
        </a>
    </div>
</div>

<!-- Informe -->
<div class="informe-container bg-white rounded-xl sm:rounded-2xl border border-gray-300 sm:border-2 sm:border-gray-900 p-4 sm:p-6 max-w-4xl mx-auto shadow-lg">
    
    <!-- Encabezado -->
    <div class="text-center" style="margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #000;">
        <h1 style="font-size: 16pt; font-weight: bold; margin: 0; text-transform: uppercase;">HOTEL CECIL</h1>
        <p style="font-size: 9pt; margin: 2px 0;">Av. Ostria Gutierrez #106 • Tel: 64-24658</p>
        <p style="font-size: 11pt; font-weight: bold; margin: 8px 0 2px 0;">INFORME DIARIO DE OPERACIONES</p>
        <p style="font-size: 9pt; margin: 0;"><?php echo $dia_semana . ', ' . $fecha_hoy; ?> - <?php echo date('H:i'); ?> hrs</p>
    </div>

    <!-- Resumen Estadístico -->
    <div style="margin-bottom: 12px;">
        <div class="section-title">I. RESUMEN ESTADÍSTICO</div>
        <table class="compact-table">
            <tr>
                <td style="width: 50%; font-weight: bold;">Total Huéspedes:</td>
                <td style="width: 50%;"><?php echo $total_huespedes; ?></td>
            </tr>
            <tr>
                <td style="font-weight: bold;">Habitaciones Ocupadas:</td>
                <td><?php echo count($ocupadas); ?> de <?php echo $total_habitaciones; ?></td>
            </tr>
            <tr>
                <td style="font-weight: bold;">Habitaciones Disponibles:</td>
                <td><?php echo count($disponibles); ?></td>
            </tr>
            <tr>
                <td style="font-weight: bold;">Pendiente de Limpieza:</td>
                <td><?php echo count($limpieza); ?></td>
            </tr>
            <tr>
                <td style="font-weight: bold;">En Mantenimiento:</td>
                <td><?php echo count($mantenimiento); ?></td>
            </tr>
        </table>
    </div>

    <!-- Estado de Habitaciones -->
    <div style="margin-bottom: 12px;">
        <div class="section-title">II. ESTADO DE HABITACIONES</div>
        
        <?php if (count($ocupadas) > 0): ?>
        <div class="info-line">
            <strong>OCUPADAS:</strong> <?php echo implode(', ', $ocupadas); ?>
        </div>
        <?php endif; ?>
        
        <?php if (count($limpieza) > 0): ?>
        <div class="info-line">
            <strong>PENDIENTE LIMPIEZA:</strong> <?php echo implode(', ', $limpieza); ?>
        </div>
        <?php endif; ?>
        
        <?php if (count($mantenimiento) > 0): ?>
        <div class="info-line">
            <strong>MANTENIMIENTO:</strong> <?php echo implode(', ', $mantenimiento); ?>
        </div>
        <?php endif; ?>
        
        <?php if (count($disponibles) > 0): ?>
        <div class="info-line">
            <strong>DISPONIBLES:</strong> <?php echo implode(', ', $disponibles); ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Huéspedes Registrados -->
    <?php if ($total_huespedes > 0): ?>
    <div style="margin-bottom: 12px;">
        <div class="section-title">III. HUÉSPEDES REGISTRADOS (<?php echo $total_huespedes; ?>)</div>
        <table class="compact-table">
            <tr style="background: #e0e0e0; font-weight: bold;">
                <td style="width: 10%;">Hab.</td>
                <td style="width: 45%;">Nombre</td>
                <td style="width: 25%;">Ingreso</td>
                <td style="width: 20%;">Días</td>
            </tr>
            <?php foreach ($ocupaciones_activas as $ocu): ?>
            <tr>
                <td style="text-align: center;"><?php echo $ocu['nro_pieza']; ?></td>
                <td><?php echo htmlspecialchars($ocu['nombres_apellidos']); ?></td>
                <td><?php echo date('d/m/Y', strtotime($ocu['fecha_ingreso'])); ?></td>
                <td style="text-align: center;"><?php echo $ocu['nro_dias']; ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
    <?php endif; ?>

    <!-- Observaciones y Notas Adicionales -->
    <div style="margin-bottom: 12px;">
        <div class="section-title">IV. OBSERVACIONES Y NOTAS ADICIONALES</div>
        <textarea 
            id="notas_adicionales" 
            class="notes-area no-print w-full border border-gray-900 rounded p-2"
            placeholder="Escriba aquí cualquier observación, incidencia o nota importante del día..."
            style="font-family: 'Times New Roman', Times, serif; resize: vertical; min-height: 100px;"
        ></textarea>
        <div id="notas_imprimir" class="notes-area" style="display: none;"></div>
    </div>


    <!-- Firma -->
    <div style="margin-top: 30px; text-align: center;">
        <div style="border-top: 1px solid #000; width: 50%; margin: 0 auto; padding-top: 5px; font-size: 9pt;">
            FIRMA RESPONSABLE DE TURNO
        </div>
    </div>

    <div style="text-align: center; margin-top: 15px; padding-top: 10px; border-top: 1px solid #ccc; font-size: 8pt; color: #666;">
        Documento generado automáticamente por Sistema de Gestión Hotel Cecil
    </div>

</div>

<script>
// Copiar notas al área de impresión antes de imprimir
window.onbeforeprint = function() {
    const notas = document.getElementById('notas_adicionales').value;
    const notasImprimir = document.getElementById('notas_imprimir');
    if (notas.trim()) {
        notasImprimir.textContent = notas;
        notasImprimir.style.display = 'block';
    } else {
        notasImprimir.textContent = 'Sin observaciones registradas.';
        notasImprimir.style.display = 'block';
    }
};
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
