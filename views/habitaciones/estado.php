<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/Habitacion.php';
require_once __DIR__ . '/../../models/Mantenimiento.php';
require_once __DIR__ . '/../../models/RegistroOcupacion.php';
require_once __DIR__ . '/../../models/Huesped.php';

$page_title = 'Gestión de Estados';

$habitacionModel = new Habitacion();
$mantenimientoModel = new Mantenimiento();
$registroModel = new RegistroOcupacion();

// Obtener mantenimientos activos por habitación
$mantenimientos_activos = [];
foreach ($mantenimientoModel->obtenerActivos() as $mant) {
    $mantenimientos_activos[$mant['habitacion_numero']] = $mant;
}

// Obtener ocupaciones activas con datos de huésped
$conn = getConnection();
$sql = "SELECT ro.*, h.nombres_apellidos, h.genero, h.edad, h.ci_pasaporte, h.nacionalidad, 
               hab.numero as habitacion_numero, hab.estado as hab_estado
        FROM registro_ocupacion ro
        INNER JOIN huespedes h ON ro.huesped_id = h.id
        INNER JOIN habitaciones hab ON ro.habitacion_id = hab.id
        WHERE ro.estado = 'activo'";
$stmt = $conn->prepare($sql);
$stmt->execute();
$ocupaciones_activas = $stmt->fetchAll();

// Indexar por número de habitación - MODIFICADO para soportar múltiples huéspedes
$huespedes_por_habitacion = [];
foreach ($ocupaciones_activas as $ocup) {
    // Si ya existe un huésped en esta habitación, crear un array
    if (!isset($huespedes_por_habitacion[$ocup['habitacion_numero']])) {
        $huespedes_por_habitacion[$ocup['habitacion_numero']] = $ocup;
    } else {
        // Si ya hay un huésped, convertir a array de múltiples huéspedes
        if (!isset($huespedes_por_habitacion[$ocup['habitacion_numero']]['multiples'])) {
            // Guardar el primer huésped
            $primer_huesped = $huespedes_por_habitacion[$ocup['habitacion_numero']];
            $huespedes_por_habitacion[$ocup['habitacion_numero']] = [
                'multiples' => true,
                'huespedes' => [$primer_huesped, $ocup],
                'habitacion_numero' => $ocup['habitacion_numero'],
                'nro_dias' => $ocup['nro_dias'],
                'fecha_ingreso' => $ocup['fecha_ingreso']
            ];
        } else {
            // Agregar más huéspedes
            $huespedes_por_habitacion[$ocup['habitacion_numero']]['huespedes'][] = $ocup;
        }
    }
}

// Procesar cambios de estado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['habitacion_id'])) {
    $habitacion_id = clean_input($_POST['habitacion_id']);
    $nuevo_estado = clean_input($_POST['nuevo_estado']);
    
    $conn = getConnection();
    $sql = "UPDATE habitaciones SET estado = :estado WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $result = $stmt->execute([':estado' => $nuevo_estado, ':id' => $habitacion_id]);
    
    if ($result) {
        $mensaje = "Estado actualizado correctamente";
        $tipo_mensaje = "success";
    } else {
        $mensaje = "Error al actualizar el estado";
        $tipo_mensaje = "error";
    }
}

$habitaciones = $habitacionModel->obtenerTodas();

// IMPORTANTE: Refrescar los datos de habitaciones para obtener el estado actualizado
// porque puede haber cambiado después de registrar ocupaciones
$conn = getConnection();
$sql_refresh = "SELECT * FROM habitaciones ORDER BY numero";
$stmt_refresh = $conn->prepare($sql_refresh);
$stmt_refresh->execute();
$habitaciones = $stmt_refresh->fetchAll(PDO::FETCH_ASSOC);

// DEBUG TEMPORAL: Verificar habitación 104
foreach ($habitaciones as $h) {
    if ($h['numero'] == '104') {
        error_log("DEBUG - Habitación 104 después de fetch:");
        error_log("  numero: " . ($h['numero'] ?? 'NULL'));
        error_log("  estado: '" . ($h['estado'] ?? 'NULL') . "'");
        error_log("  tipo: " . ($h['tipo'] ?? 'NULL'));
        error_log("Array completo: " . print_r($h, true));
    }
}


// Debug: registrar habitaciones ocupadas (después de obtener $habitaciones)
error_log("=== DEBUG HABITACIONES OCUPADAS ===");
foreach ($habitaciones as $h) {
    if ($h['estado'] === 'ocupado' || $h['estado'] === 'ocupada') {
        error_log("Habitación {$h['numero']}: estado='{$h['estado']}'");
        error_log("Tiene huésped: " . (isset($huespedes_por_habitacion[$h['numero']]) ? 'SÍ' : 'NO'));
    }
}

// Agrupar por piso
$por_piso = [
    '3' => [],
    '2' => [],
    '1' => []
];

foreach ($habitaciones as $hab) {
    $primer_digito = substr($hab['numero'], 0, 1);
    if (isset($por_piso[$primer_digito])) {
        $por_piso[$primer_digito][] = $hab;
    }
}

// Ordenar habitaciones por número dentro de cada piso
foreach ($por_piso as $piso => $habs) {
    usort($por_piso[$piso], function($a, $b) {
        return (int)$a['numero'] - (int)$b['numero'];
    });
}

// Contar estados
$total_disponibles = count(array_filter($habitaciones, fn($h) => $h['estado'] === 'disponible'));
$total_ocupadas = count(array_filter($habitaciones, fn($h) => $h['estado'] === 'ocupada' || $h['estado'] === 'ocupado'));
$total_limpieza = count(array_filter($habitaciones, fn($h) => $h['estado'] === 'limpieza'));
$total_mantenimiento = count(array_filter($habitaciones, fn($h) => $h['estado'] === 'mantenimiento'));

include __DIR__ . '/../../includes/header.php';
?>

<style>
body {
    background: linear-gradient(135deg, #fafafa 0%, #f5f5f5 100%);
}

.dark body {
    background: linear-gradient(135deg, #0a0a0a 0%, #171717 100%);
}

.glass-card {
    background: rgba(255, 255, 255, 0.7);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.8);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.04);
}

.dark .glass-card {
    background: rgba(23, 23, 23, 0.7);
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
}

.room-cell {
    aspect-ratio: 1;
    border: 2px solid #e5e5e5;
    backdrop-filter: blur(10px);
    position: relative;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    overflow: hidden;
}

.room-cell:hover {
    border-color: #000;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
    transform: translateY(-2px);
}

/* Estados con todo el fondo de color para máxima visibilidad - IMPORTANTE: van después del base */
.room-cell.disponible { 
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%) !important;
    border-color: #10b981 !important;
}

.room-cell.ocupado,
.room-cell.ocupada { 
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%) !important;
    border-color: #f87171 !important;
}

.room-cell.limpieza { 
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%) !important;
    border-color: #f59e0b !important;
}

.room-cell.mantenimiento { 
    background: linear-gradient(135deg, #e5e7eb 0%, #d1d5db 100%) !important;
    border-color: #6b7280 !important;
}

/* Dark mode para habitaciones */
.dark .room-cell {
    border: 2px solid #374151;
}

.dark .room-cell:hover {
    border-color: #fff;
    box-shadow: 0 4px 16px rgba(255, 255, 255, 0.12);
}

.dark .room-cell.disponible { 
    background: linear-gradient(135deg, #064e3b 0%, #065f46 100%) !important;
    border-color: #10b981 !important;
}

.dark .room-cell.ocupado,
.dark .room-cell.ocupada { 
    background: linear-gradient(135deg, #7f1d1d 0%, #991b1b 100%) !important;
    border-color: #f87171 !important;
}

.dark .room-cell.limpieza { 
    background: linear-gradient(135deg, #78350f 0%, #92400e 100%) !important;
    border-color: #f59e0b !important;
}

.dark .room-cell.mantenimiento { 
    background: linear-gradient(135deg, #374151 0%, #4b5563 100%) !important;
    border-color: #9ca3af !important;
}

.floor-label {
    font-size: 11px;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: #9ca3af;
    font-weight: 500;
}

.status-dot {
    width: 16px;
    height: 16px;
    border-radius: 50%;
    display: inline-block;
    box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.9);
}

.status-dot.disponible { 
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    box-shadow: 0 0 0 3px #d1fae5;
}
.status-dot.ocupado,
.status-dot.ocupada { 
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    box-shadow: 0 0 0 3px #fee2e2;
}
.status-dot.limpieza { 
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    box-shadow: 0 0 0 3px #fef3c7;
}
.status-dot.mantenimiento { 
    background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
    box-shadow: 0 0 0 3px #e5e7eb;
}

.modal-overlay {
    background: rgba(0, 0, 0, 0.3);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
}

.modal-content {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(40px);
    -webkit-backdrop-filter: blur(40px);
    border: 1px solid rgba(255, 255, 255, 0.8);
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
}

.btn-state {
    border: 1px solid rgba(0, 0, 0, 0.06);
    background: rgba(255, 255, 255, 0.9);
    color: #171717;
    padding: 12px 20px;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer;
    backdrop-filter: blur(10px);
}

.btn-state:hover {
    border-color: rgba(0, 0, 0, 0.2);
    background: rgba(255, 255, 255, 1);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.btn-state:active {
    transform: translateY(0);
}

.btn-primary {
    background: linear-gradient(135deg, #171717 0%, #404040 100%);
    color: white;
    border: none;
    padding: 12px 24px;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
}

.btn-primary:hover {
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.18);
    transform: translateY(-2px);
}

.btn-primary:active {
    transform: translateY(0);
}

.legend-item {
    padding: 12px 20px;
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(10px);
    border: 2px solid rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    font-size: 15px;
    font-weight: 500;
}

.dark .legend-item {
    background: rgba(23, 23, 23, 0.9);
    border: 2px solid rgba(255, 255, 255, 0.1);
}

.legend-item:hover {
    background: rgba(255, 255, 255, 1);
    transform: translateY(-1px);
    border-color: rgba(0, 0, 0, 0.15);
}

.dark .legend-item:hover {
    background: rgba(30, 30, 30, 1);
    border-color: rgba(255, 255, 255, 0.2);
}
</style>

<div class="max-w-7xl mx-auto px-4 py-8">
    
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-0 mb-8 sm:mb-12">
        <div>
            <h1 class="text-2xl sm:text-3xl font-light tracking-tight text-gray-900 dark:text-white mb-1">Estado de Habitaciones</h1>
            <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">Hotel Cecil</p>
        </div>
        <a href="<?php echo BASE_PATH; ?>/index.php" class="px-3 py-2 sm:px-4 text-sm sm:text-base border border-gray-300 dark:border-gray-700 rounded-lg text-gray-700 dark:text-gray-300 font-medium hover:bg-gray-50 dark:hover:bg-gray-800 transition-all text-center" style="text-decoration: none;">
            ← Volver
        </a>
    </div>

    <?php if (isset($mensaje)): ?>
    <div class="mb-8 glass-card px-6 py-4">
        <p class="text-sm <?php echo $tipo_mensaje === 'success' ? 'text-green-900 dark:text-green-300' : 'text-red-900 dark:text-red-300'; ?>"><?php echo $mensaje; ?></p>
    </div>
    <?php endif; ?>

    <!-- Leyenda -->
    <div class="flex items-center gap-4 mb-12 text-sm flex-wrap">
        <div class="legend-item flex items-center gap-3 rounded-xl">
            <span class="status-dot disponible"></span>
            <span class="text-gray-900 dark:text-white font-semibold">Disponible</span>
            <span class="text-gray-500 dark:text-gray-400 font-medium ml-1">(<?php echo $total_disponibles; ?>)</span>
        </div>

        <div class="legend-item flex items-center gap-3 rounded-xl">
            <span class="status-dot ocupado"></span>
            <span class="text-gray-900 dark:text-white font-semibold">Ocupada</span>
            <span class="text-gray-500 dark:text-gray-400 font-medium ml-1">(<?php echo $total_ocupadas; ?>)</span>
        </div>

        <div class="legend-item flex items-center gap-3 rounded-xl">
            <span class="status-dot limpieza"></span>
            <span class="text-gray-900 dark:text-white font-semibold">Limpieza</span>
            <span class="text-gray-500 dark:text-gray-400 font-medium ml-1">(<?php echo $total_limpieza; ?>)</span>
        </div>

        <div class="legend-item flex items-center gap-3 rounded-xl">
            <span class="status-dot mantenimiento"></span>
            <span class="text-gray-900 dark:text-white font-semibold">Mantenimiento</span>
            <span class="text-gray-500 dark:text-gray-400 font-medium ml-1">(<?php echo $total_mantenimiento; ?>)</span>
        </div>
    </div>

    <!-- Pisos -->
    <?php foreach (['3', '2', '1'] as $num_piso): 
        if (empty($por_piso[$num_piso])) continue;
    ?>
    <div class="mb-12">
        <div class="flex items-baseline gap-4 mb-4">
            <h2 class="floor-label">Piso <?php echo $num_piso; ?></h2>
            <div class="h-px bg-gray-200 flex-1"></div>
        </div>
        
        <div class="grid grid-cols-4 sm:grid-cols-6 md:grid-cols-8 lg:grid-cols-10 gap-2w
        ">
            <?php foreach ($por_piso[$num_piso] as $hab): 
                $huesped_info = isset($huespedes_por_habitacion[$hab['numero']]) ? $huespedes_por_habitacion[$hab['numero']] : null;
                // Debug en comentario HTML
                echo "<!-- Habitación {$hab['numero']}: estado='{$hab['estado']}', tiene_huesped=" . ($huesped_info ? 'SI' : 'NO') . " -->";
            ?>
            <div class="room-cell <?php echo $hab['estado']; ?>" 
                 onclick='openModal(<?php echo json_encode($hab, JSON_HEX_APOS | JSON_HEX_QUOT); ?>, <?php echo isset($mantenimientos_activos[$hab['numero']]) ? json_encode($mantenimientos_activos[$hab['numero']], JSON_HEX_APOS | JSON_HEX_QUOT) : 'null'; ?>, <?php echo $huesped_info ? json_encode($huesped_info, JSON_HEX_APOS | JSON_HEX_QUOT) : 'null'; ?>)'
                 data-estado="<?php echo $hab['estado']; ?>"
                 data-numero="<?php echo $hab['numero']; ?>">
                <div class="absolute inset-0 flex flex-col items-center justify-center">
                    <span class="text-2xl font-semibold text-gray-900 dark:text-white"><?php echo $hab['numero']; ?></span>
                    <span class="text-xs text-gray-600 dark:text-gray-300 mt-1 font-medium"><?php echo $hab['tipo']; ?></span>
                    <!-- DEBUG: Estado actual = <?php echo $hab['estado']; ?> -->
                    <?php if (isset($mantenimientos_activos[$hab['numero']])): ?>
                    <span class="absolute top-1 right-1 w-2 h-2 bg-orange-500 rounded-full animate-pulse" title="Con mantenimiento activo"></span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>

</div>

<!-- Modal -->
<div id="modal" class="modal-overlay hidden fixed inset-0 z-50 flex items-center justify-center p-4" onclick="closeModal()">
    <div class="modal-content max-w-lg w-full p-8 max-h-[90vh] overflow-y-auto" onclick="event.stopPropagation()">
        <div class="mb-6">
            <div class="flex items-baseline gap-2 mb-1">
                <h3 class="text-2xl font-light text-gray-900" id="m-numero"></h3>
                <span class="text-sm text-gray-500" id="m-tipo"></span>
            </div>
            <p class="text-sm text-gray-500">Bs. <span id="m-precio"></span> por noche</p>
        </div>
        
        <div class="mb-6 pb-6 border-b border-gray-200 dark:border-gray-700">
            <p class="text-xs uppercase tracking-wider text-gray-400 mb-2">Estado actual</p>
            <div class="flex items-center gap-2">
                <span class="status-dot" id="m-status-dot"></span>
                <span class="text-sm text-gray-900 dark:text-white" id="m-status-text"></span>
            </div>
        </div>
        
        <!-- Información de mantenimiento si existe -->
        <div id="m-mantenimiento-info" class="hidden mb-6 pb-6 border-b border-gray-200 dark:border-gray-700">
            <div class="bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-lg p-4">
                <div class="flex items-start gap-2 mb-2">
                    <i class="fas fa-tools text-orange-600 dark:text-orange-400 mt-0.5"></i>
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-orange-900 dark:text-orange-300" id="m-mant-titulo"></p>
                        <p class="text-xs text-orange-700 dark:text-orange-400 mt-1" id="m-mant-tipo"></p>
                    </div>
                    <span class="text-xs px-2 py-1 rounded-full font-semibold" id="m-mant-prioridad"></span>
                </div>
                <p class="text-xs text-gray-700 dark:text-gray-300 mt-2" id="m-mant-descripcion"></p>
                <div class="mt-3 pt-3 border-t border-orange-200 dark:border-orange-700 flex items-center justify-between text-xs">
                    <span class="text-gray-600 dark:text-gray-400">Estado: <strong id="m-mant-estado"></strong></span>
                    <span class="text-gray-600 dark:text-gray-400">Inicio: <strong id="m-mant-fecha"></strong></span>
                </div>
                <a href="<?php echo BASE_PATH; ?>/views/habitaciones/mantenimiento.php" 
                   class="mt-3 block text-center text-xs text-orange-600 dark:text-orange-400 hover:text-orange-800 dark:hover:text-orange-300 font-medium">
                    Ver todos los mantenimientos →
                </a>
            </div>
        </div>
        
        <!-- Información del huésped si está ocupada -->
        <div id="m-huesped-info" class="hidden mb-6 pb-6 border-b border-gray-200 dark:border-gray-700">
            <!-- Este contenido se generará dinámicamente con JavaScript -->
        </div>
        
        <form method="POST" class="space-y-2"  id="form-cambiar-estado">
            <input type="hidden" name="habitacion_id" id="m-id">
            
            <button type="submit" name="nuevo_estado" value="disponible" class="btn-state w-full text-left">
                Disponible
            </button>
            
            <button type="submit" name="nuevo_estado" value="limpieza" class="btn-state w-full text-left">
                Limpieza
            </button>
            
            <div class="pt-4">
                <button type="button" onclick="closeModal()" class="text-sm text-gray-500 hover:text-gray-900 transition">
                    Cancelar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const statusMap = {
    'disponible': { text: 'Disponible', class: 'disponible' },
    'ocupada': { text: 'Ocupada', class: 'ocupado' },
    'ocupado': { text: 'Ocupada', class: 'ocupado' },
    'limpieza': { text: 'Necesita limpieza', class: 'limpieza' },
    'mantenimiento': { text: 'En mantenimiento', class: 'mantenimiento' }
};

const prioridadMap = {
    'baja': { text: 'Baja', class: 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300' },
    'media': { text: 'Media', class: 'bg-blue-200 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300' },
    'alta': { text: 'Alta', class: 'bg-orange-200 dark:bg-orange-900/50 text-orange-700 dark:text-orange-300' },
    'urgente': { text: 'Urgente', class: 'bg-red-200 dark:bg-red-900/50 text-red-700 dark:text-red-300' }
};

function openModal(room, mantenimiento = null, huesped = null) {
    console.log('Estado de habitación:', room.estado);
    console.log('Datos de huésped:', huesped);
    console.log('Room completo:', room);
    
    document.getElementById('m-numero').textContent = room.numero;
    document.getElementById('m-tipo').textContent = room.tipo;
    document.getElementById('m-precio').textContent = parseFloat(room.precio_dia).toFixed(2);
    document.getElementById('m-id').value = room.id;
    
    // Validar que el estado existe en statusMap
    const status = statusMap[room.estado] || statusMap['disponible'];
    if (!statusMap[room.estado]) {
        console.error('Estado no encontrado en statusMap:', room.estado);
    }
    
    document.getElementById('m-status-dot').className = 'status-dot ' + status.class;
    document.getElementById('m-status-text').textContent = status.text;
    
    // Mostrar información de mantenimiento si existe
    const mantInfo = document.getElementById('m-mantenimiento-info');
    if (mantenimiento && room.estado === 'mantenimiento') {
        mantInfo.classList.remove('hidden');
        document.getElementById('m-mant-titulo').textContent = mantenimiento.titulo;
        document.getElementById('m-mant-tipo').textContent = mantenimiento.tipo.charAt(0).toUpperCase() + mantenimiento.tipo.slice(1);
        document.getElementById('m-mant-descripcion').textContent = mantenimiento.descripcion;
        document.getElementById('m-mant-estado').textContent = mantenimiento.estado.replace('_', ' ');
        document.getElementById('m-mant-fecha').textContent = new Date(mantenimiento.fecha_inicio).toLocaleDateString('es-BO');
        
        const prioridad = prioridadMap[mantenimiento.prioridad];
        const prioridadEl = document.getElementById('m-mant-prioridad');
        prioridadEl.textContent = prioridad.text;
        prioridadEl.className = 'text-xs px-2 py-1 rounded-full font-semibold ' + prioridad.class;
    } else {
        mantInfo.classList.add('hidden');
    }
    
    // Mostrar información del huésped si la habitación está ocupada
    const huespedInfo = document.getElementById('m-huesped-info');
    const formEstado = document.getElementById('form-cambiar-estado');
    
    if (huesped && (room.estado === 'ocupada' || room.estado === 'ocupado')) {
        huespedInfo.classList.remove('hidden');
        formEstado.classList.add('hidden'); // Ocultar formulario de cambio de estado
        
        // Verificar si hay múltiples huéspedes
        if (huesped.multiples && huesped.huespedes) {
            // Hay múltiples huéspedes - mostrar lista
            const numHuespedes = huesped.huespedes.length;
            let htmlContent = `
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                    <div class="flex items-center gap-2 mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <p class="text-base font-bold text-blue-900 dark:text-blue-300">${numHuespedes} Huéspedes en esta habitación</p>
                    </div>
                    <div class="mb-3 pb-3 border-b border-blue-200 dark:border-blue-700">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Estadía</p>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">${huesped.nro_dias} días • Check-in: ${new Date(huesped.fecha_ingreso).toLocaleDateString('es-BO')}</p>
                    </div>
                    <div class="space-y-3">`;
            
            // Agregar cada huésped
            huesped.huespedes.forEach((h, index) => {
                htmlContent += `
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-3 ${index > 0 ? 'border-t border-gray-200 dark:border-gray-700' : ''}">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white mb-2">
                                <span class="inline-block w-5 h-5 bg-blue-500 text-white text-xs rounded-full text-center leading-5 mr-2">${index + 1}</span>
                                ${h.nombres_apellidos}
                            </p>
                            <div class="grid grid-cols-2 gap-2 text-xs ml-7">
                                <div>
                                    <span class="text-gray-500">CI/Pasaporte:</span>
                                    <span class="font-semibold text-gray-900 dark:text-white">${h.ci_pasaporte}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Género:</span>
                                    <span class="font-semibold text-gray-900 dark:text-white">${h.genero === 'M' ? 'Masculino' : 'Femenino'}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Edad:</span>
                                    <span class="font-semibold text-gray-900 dark:text-white">${h.edad} años</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Nacionalidad:</span>
                                    <span class="font-semibold text-gray-900 dark:text-white">${h.nacionalidad}</span>
                                </div>
                            </div>
                        </div>`;
            });
            
            htmlContent += `
                    </div>
                </div>`;
            
            huespedInfo.innerHTML = htmlContent;
        } else {
            // Un solo huésped - mostrar diseño tradicional
            huespedInfo.innerHTML = `
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                    <div class="flex items-start gap-3 mb-3">
                        <div class="flex items-center justify-center text-blue-600 dark:text-blue-400" style="min-width: 40px;">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p class="text-base font-bold text-blue-900 dark:text-blue-300">${huesped.nombres_apellidos}</p>
                            <p class="text-xs text-blue-700 dark:text-blue-400 mt-1">
                                <i class="fas fa-id-card mr-1"></i>CI/Pasaporte: <strong>${huesped.ci_pasaporte}</strong>
                            </p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3 mt-3 pt-3 border-t border-blue-200 dark:border-blue-700">
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Género</p>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">${huesped.genero === 'M' ? 'Masculino' : 'Femenino'}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Edad</p>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">${huesped.edad} años</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Días de estadía</p>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">${huesped.nro_dias} días</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Check-in</p>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">${new Date(huesped.fecha_ingreso).toLocaleDateString('es-BO')}</p>
                        </div>
                    </div>
                    <div class="mt-3 pt-3 border-t border-blue-200 dark:border-blue-700">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Nacionalidad</p>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">${huesped.nacionalidad}</p>
                    </div>
                </div>`;
        }
    } else {
        huespedInfo.classList.add('hidden');
        formEstado.classList.remove('hidden'); // Mostrar formulario si no está ocupada
    }
    
    document.getElementById('modal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('modal').classList.add('hidden');
}

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeModal();
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
