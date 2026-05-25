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
/* Estilos premium Apple-style e impresión ultra-limpia */
:root {
    --apple-font: -apple-system, BlinkMacSystemFont, "SF Pro Text", "SF Pro Icons", "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
}

body {
    background-color: #f5f5f7;
    font-family: var(--apple-font);
}

.informe-container {
    font-family: var(--apple-font);
    background: #ffffff;
    border: 1px solid rgba(0, 0, 0, 0.06);
    border-radius: 24px;
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.03);
    color: #1d1d1f;
    line-height: 1.5;
}

.dark .informe-container {
    background: #1c1c1e;
    border-color: rgba(255, 255, 255, 0.06);
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.3);
    color: #f5f5f7;
}

.section-title {
    font-size: 11px;
    font-weight: 750;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    color: #86868b;
    border-bottom: 1px solid rgba(0, 0, 0, 0.06);
    padding-bottom: 6px;
    margin-bottom: 14px;
    margin-top: 28px;
}

.dark .section-title {
    color: #aeaeb2;
    border-bottom-color: rgba(255, 255, 255, 0.08);
}

/* Stat Grid Dashboard */
.stat-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
}

@media (min-width: 640px) {
    .stat-grid {
        grid-template-columns: repeat(5, 1fr);
    }
}

.stat-box {
    background: #f5f5f7;
    border-radius: 14px;
    padding: 14px;
    text-align: center;
    border: 1px solid rgba(0, 0, 0, 0.02);
    transition: transform 0.2s ease;
}

.dark .stat-box {
    background: rgba(255, 255, 255, 0.04);
    border-color: rgba(255, 255, 255, 0.02);
}

.stat-number {
    font-size: 26px;
    font-weight: 800;
    letter-spacing: -0.03em;
    color: #1d1d1f;
}

.dark .stat-number {
    color: #ffffff;
}

.stat-label {
    font-size: 11px;
    font-weight: 600;
    color: #86868b;
    margin-top: 3px;
    text-transform: uppercase;
    letter-spacing: 0.02em;
}

.dark .stat-label {
    color: #aeaeb2;
}

/* Room Lists */
.info-line {
    font-size: 13.5px;
    margin: 10px 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
    flex-shrink: 0;
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
}

/* Table styling */
.premium-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13.5px;
    margin: 14px 0;
}

.premium-table th {
    background: #f5f5f7;
    color: #86868b;
    font-weight: 700;
    text-transform: uppercase;
    font-size: 10px;
    letter-spacing: 0.04em;
    padding: 12px 16px;
    text-align: left;
    border-bottom: 1px solid rgba(0, 0, 0, 0.06);
}

.dark .premium-table th {
    background: rgba(255, 255, 255, 0.04);
    color: #aeaeb2;
    border-bottom-color: rgba(255, 255, 255, 0.06);
}

.premium-table td {
    padding: 12px 16px;
    border-bottom: 1px solid rgba(0, 0, 0, 0.04);
    color: #1d1d1f;
}

.dark .premium-table td {
    color: #f5f5f7;
    border-bottom-color: rgba(255, 255, 255, 0.04);
}

.premium-table tr:last-child td {
    border-bottom: none;
}

/* Input & Output Text Area */
.notes-area {
    width: 100%;
    background: rgba(0, 0, 0, 0.02);
    border: 1px solid rgba(0, 0, 0, 0.1);
    border-radius: 16px;
    padding: 16px;
    font-size: 14px;
    transition: all 0.2s ease;
    color: #1d1d1f;
    line-height: 1.6;
    font-family: var(--apple-font);
}

.notes-area:focus {
    outline: none;
    border-color: #0071e3;
    background: #ffffff;
    box-shadow: 0 0 0 4px rgba(0, 113, 227, 0.12);
}

.dark .notes-area {
    background: rgba(255, 255, 255, 0.03);
    border-color: rgba(255, 255, 255, 0.1);
    color: #f5f5f7;
}

.dark .notes-area:focus {
    background: #1c1c1e;
    border-color: #0071e3;
    box-shadow: 0 0 0 4px rgba(0, 113, 227, 0.25);
}

#notas_imprimir {
    white-space: pre-wrap;
    background: #fafafa;
    border: 1px solid rgba(0, 0, 0, 0.06);
    border-radius: 16px;
    padding: 16px;
    font-size: 13px;
    color: #1d1d1f;
    min-height: 100px;
    line-height: 1.6;
    display: none;
}

.dark #notas_imprimir {
    background: rgba(255, 255, 255, 0.02);
    border-color: rgba(255, 255, 255, 0.06);
    color: #f5f5f7;
}

.print-only {
    display: none;
}

/* Print Overrides & Robust Isolation */
@media print {
    body * {
        visibility: hidden;
    }
    
    .informe-container, .informe-container * {
        visibility: visible;
    }
    
    .informe-container {
        position: absolute;
        left: 0;
        top: 0;
        width: 100% !important;
        box-shadow: none !important;
        border: none !important;
        background: white !important;
        color: #000000 !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    
    .no-print {
        display: none !important;
    }
    
    body {
        background: white !important;
        color: #000000 !important;
        font-size: 8.5pt !important;
    }
    
    .section-title {
        font-size: 9px !important;
        margin-top: 14px !important;
        margin-bottom: 6px !important;
        padding-bottom: 3px !important;
    }
    
    .stat-grid {
        display: grid !important;
        grid-template-columns: repeat(5, 1fr) !important;
        gap: 6px !important;
    }
    
    .stat-box {
        background: #f5f5f7 !important;
        border: 1px solid rgba(0, 0, 0, 0.05) !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        padding: 8px 4px !important;
        border-radius: 8px !important;
    }
    
    .stat-number {
        color: #000000 !important;
        font-size: 16px !important;
    }

    .stat-label {
        font-size: 8px !important;
        margin-top: 0px !important;
    }
    
    .info-line {
        font-size: 11px !important;
        margin: 4px 0 !important;
        gap: 6px !important;
    }
    
    .premium-table {
        margin: 6px 0 !important;
    }

    .premium-table th {
        background: #f5f5f7 !important;
        color: #444444 !important;
        border-bottom: 1px solid rgba(0, 0, 0, 0.1) !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        padding: 5px 8px !important;
        font-size: 8px !important;
    }
    
    .premium-table td {
        color: #000000 !important;
        border-bottom: 1px solid rgba(0, 0, 0, 0.08) !important;
        padding: 4px 8px !important;
        font-size: 9px !important;
    }
    
    #notas_imprimir {
        display: block !important;
        white-space: pre-wrap !important;
        background: #fafafa !important;
        border: 1px solid rgba(0, 0, 0, 0.08) !important;
        color: #000000 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        padding: 8px !important;
        font-size: 9.5px !important;
        min-height: 40px !important;
    }
    
    .print-only {
        display: block !important;
    }

    .print-only.mt-16 {
        margin-top: 14px !important;
    }

    * {
        print-color-adjust: exact !important;
        -webkit-print-color-adjust: exact !important;
    }
    
    @page {
        size: letter;
        margin: 0.8cm;
    }
}
</style>

<div class="container mx-auto px-4 py-8 max-w-4xl">
    <!-- Controles (no se imprimen) -->
    <div class="no-print mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight text-gray-900 dark:text-white mb-2">Informe Diario</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">Resumen y estado general de operaciones para el turno</p>
            </div>
            
            <div class="flex items-center gap-3">
                <button onclick="window.print()" 
                        class="px-5 py-3 bg-gray-900 dark:bg-gray-100 text-white dark:text-gray-900 font-semibold rounded-xl hover:bg-gray-800 dark:hover:bg-white transition text-sm shadow-sm flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    Imprimir Informe
                </button>
                <a href="<?php echo BASE_PATH; ?>/index.php" 
                   class="px-4 py-3 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl border border-gray-200 dark:border-gray-700 transition font-medium text-sm text-center shadow-sm">
                    Volver
                </a>
            </div>
        </div>
    </div>

    <!-- Informe -->
    <div class="informe-container p-6 sm:p-10">
        
        <!-- Encabezado -->
        <div class="text-center pb-6 mb-6 border-b border-gray-100 dark:border-gray-800/80">
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-gray-900 dark:text-white uppercase">HOTEL CECIL</h1>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Av. Ostria Gutierrez #106 • Tel: 64-24658</p>
            <div class="inline-block px-4 py-1.5 bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-300 rounded-full text-xs font-bold uppercase tracking-widest mt-4">
                INFORME DIARIO DE OPERACIONES
            </div>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-3 font-medium">
                <?php echo $dia_semana . ', ' . $fecha_hoy; ?> — <?php echo date('H:i'); ?> hrs
            </p>
        </div>

        <!-- I. Resumen Estadístico -->
        <div class="mb-8">
            <div class="section-title">I. Resumen Estadístico</div>
            <div class="stat-grid">
                <div class="stat-box">
                    <div class="stat-number"><?php echo $total_huespedes; ?></div>
                    <div class="stat-label">Huéspedes</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?php echo count($ocupadas); ?></div>
                    <div class="stat-label">Ocupadas</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?php echo count($disponibles); ?></div>
                    <div class="stat-label">Disponibles</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?php echo count($limpieza); ?></div>
                    <div class="stat-label">En Limpieza</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?php echo count($mantenimiento); ?></div>
                    <div class="stat-label">Mantenimiento</div>
                </div>
            </div>
        </div>

        <!-- II. Estado de Habitaciones -->
        <div class="mb-8">
            <div class="section-title">II. Estado de Habitaciones</div>
            <div class="space-y-3">
                <?php if (count($ocupadas) > 0): ?>
                <div class="info-line">
                    <span class="dot bg-red-500"></span>
                    <span class="text-sm font-semibold text-gray-500 dark:text-gray-400">OCUPADAS:</span> 
                    <span class="text-sm text-gray-900 dark:text-white font-bold tracking-wide"><?php echo implode(', ', $ocupadas); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if (count($limpieza) > 0): ?>
                <div class="info-line">
                    <span class="dot bg-blue-500"></span>
                    <span class="text-sm font-semibold text-gray-500 dark:text-gray-400">PENDIENTE LIMPIEZA:</span> 
                    <span class="text-sm text-gray-900 dark:text-white font-bold tracking-wide"><?php echo implode(', ', $limpieza); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if (count($mantenimiento) > 0): ?>
                <div class="info-line">
                    <span class="dot bg-amber-500"></span>
                    <span class="text-sm font-semibold text-gray-500 dark:text-gray-400">MANTENIMIENTO:</span> 
                    <span class="text-sm text-gray-900 dark:text-white font-bold tracking-wide"><?php echo implode(', ', $mantenimiento); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if (count($disponibles) > 0): ?>
                <div class="info-line">
                    <span class="dot bg-green-500"></span>
                    <span class="text-sm font-semibold text-gray-500 dark:text-gray-400">DISPONIBLES:</span> 
                    <span class="text-sm text-gray-900 dark:text-white font-bold tracking-wide"><?php echo implode(', ', $disponibles); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- III. Huéspedes Registrados -->
        <?php if ($total_huespedes > 0): ?>
        <div class="mb-8">
            <div class="section-title">III. Huéspedes Registrados (<?php echo $total_huespedes; ?>)</div>
            <div class="overflow-x-auto">
                <table class="premium-table">
                    <thead>
                        <tr>
                            <th style="width: 15%;">Hab.</th>
                            <th style="width: 50%;">Nombre Completo</th>
                            <th style="width: 20%;">F. Ingreso</th>
                            <th style="width: 15%; text-align: center;">Días</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ocupaciones_activas as $ocu): ?>
                        <tr>
                            <td class="font-bold text-gray-900 dark:text-white">Hab. <?php echo $ocu['nro_pieza']; ?></td>
                            <td class="font-medium"><?php echo htmlspecialchars($ocu['nombres_apellidos']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($ocu['fecha_ingreso'])); ?></td>
                            <td style="text-align: center;" class="font-semibold text-gray-550 dark:text-gray-300"><?php echo $ocu['nro_dias']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- IV. Observaciones y Notas Adicionales -->
        <div class="mb-8">
            <div class="section-title">IV. Observaciones y Notas Adicionales</div>
            <textarea 
                id="notas_adicionales" 
                class="notes-area no-print resize-y"
                placeholder="Escriba aquí cualquier observación, incidencia o notas importantes del turno. Puede añadir guiones, puntos y presionar Enter para hacer saltos de línea..."
                style="min-height: 120px;"
                oninput="copiarNotas(this.value)"
            ></textarea>
            <div id="notas_imprimir"></div>
        </div>

        <!-- Firma -->
        <div class="mt-12 flex justify-center no-print">
            <div class="border-t border-gray-200 dark:border-gray-700 pt-3 text-center text-xs text-gray-400 w-64 uppercase tracking-widest font-bold">
                Firma Responsable de Turno
            </div>
        </div>
        
        <div class="print-only mt-16">
            <div class="flex justify-center">
                <div class="border-t border-black pt-2 text-center text-[10px] w-64 uppercase tracking-widest font-bold">
                    Firma Responsable de Turno
                </div>
            </div>
        </div>

        <div class="text-center mt-12 pt-4 border-t border-gray-150 dark:border-gray-800/80 text-[10px] text-gray-400 dark:text-gray-550 font-medium">
            Documento generado automáticamente por Sistema de Gestión Hotel Cecil
        </div>

    </div>
</div>

<script>
function copiarNotas(val) {
    const notasImprimir = document.getElementById('notas_imprimir');
    if (val.trim()) {
        notasImprimir.textContent = val;
        notasImprimir.style.display = 'block';
    } else {
        notasImprimir.textContent = 'Sin observaciones registradas.';
        notasImprimir.style.display = 'block';
    }
}

// Inicializar al cargar para que las notas por defecto se procesen
document.addEventListener("DOMContentLoaded", function() {
    const notasAdicionales = document.getElementById('notas_adicionales');
    if (notasAdicionales) {
        copiarNotas(notasAdicionales.value);
    }
});

// Doble confirmación antes de la impresión
window.onbeforeprint = function() {
    const staticNotes = document.getElementById('notas_adicionales').value;
    copiarNotas(staticNotes);
};
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
