<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/Habitacion.php';
require_once __DIR__ . '/../../models/Mantenimiento.php';
require_once __DIR__ . '/../../models/RegistroOcupacion.php';
require_once __DIR__ . '/../../models/Huesped.php';

$page_title = 'Estado de Habitaciones';

$habitacionModel = new Habitacion();
$mantenimientoModel = new Mantenimiento();
$registroModel = new RegistroOcupacion();

$conn = getConnection();

$habitaciones = $habitacionModel->obtenerTodas();

$ocupaciones_activas = $registroModel->obtenerActivos();

$habitaciones_ocupadas_db = array_column(
    array_filter($habitaciones, fn($h) => $h['estado'] === 'ocupada' || $h['estado'] === 'ocupado'),
    'numero'
);

$huespedes_por_habitacion = [];
$huespedes_hoy_salida = [];
$habitaciones_con_problema = [];

foreach ($ocupaciones_activas as $ocup) {
    $hab_numero = $ocup['numero_habitacion'];
    
    if (!isset($huespedes_por_habitacion[$hab_numero])) {
        $huespedes_por_habitacion[$hab_numero] = [];
    }
    $huespedes_por_habitacion[$hab_numero][] = $ocup;
    
    $fecha_salida = date('Y-m-d', strtotime($ocup['fecha_salida_estimada']));
    $hoy = date('Y-m-d');
    $manana = date('Y-m-d', strtotime('+1 day'));
    
    if ($fecha_salida === $hoy) {
        $huespedes_hoy_salida[$hab_numero][] = $ocup;
    }
}

$estados_consolidados = [];
foreach ($habitaciones as $hab) {
    $numero = $hab['numero'];
    $estado_db = trim($hab['estado'] ?? '');
    
    $tiene_ocupacion_activa = isset($huespedes_por_habitacion[$numero]);
    $tiene_mantenimiento = isset($mantenimientoModel->obtenerActivos()[$numero]);
    
    if ($estado_db === '' || $estado_db === null) {
        $estado_db = 'disponible';
    }
    
    if ($estado_db === 'ocupado') {
        $estado_db = 'ocupada';
    }
    
    if ($tiene_ocupacion_activa && $estado_db !== 'ocupada') {
        $habitaciones_con_problema[] = [
            'numero' => $numero,
            'estado_actual' => $estado_db,
            'deberia_ser' => 'ocupada'
        ];
    }
    
    if (!$tiene_ocupacion_activa && $estado_db === 'ocupada') {
        $habitaciones_con_problema[] = [
            'numero' => $numero,
            'estado_actual' => $estado_db,
            'deberia_ser' => $estado_db === 'mantenimiento' ? 'mantenimiento' : 'disponible'
        ];
    }
    
    $estado_final = $estado_db;
    
    if ($tiene_ocupacion_activa) {
        $estado_final = 'ocupada';
    }
    
    if ($tiene_mantenimiento && $estado_db === 'mantenimiento') {
        $estado_final = 'mantenimiento';
    }
    
    $estados_consolidados[$numero] = [
        'habitacion' => $hab,
        'estado_db' => $estado_db,
        'estado_final' => $estado_final,
        'tiene_ocupacion_activa' => $tiene_ocupacion_activa,
        'tiene_mantenimiento' => $tiene_mantenimiento,
        'huespedes' => $huespedes_por_habitacion[$numero] ?? []
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['habitacion_id'])) {
    $habitacion_id = clean_input($_POST['habitacion_id']);
    $nuevo_estado = clean_input($_POST['nuevo_estado']);
    
    if (!in_array($nuevo_estado, ['disponible', 'ocupada', 'limpieza', 'mantenimiento'])) {
        $nuevo_estado = 'disponible';
    }
    
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
    
    header("Location: " . BASE_PATH . "/views/habitaciones/estado.php");
    exit;
}

$total_habitaciones = count($habitaciones);
$total_ocupadas = count(array_filter($estados_consolidados, fn($e) => $e['estado_final'] === 'ocupada'));
$total_disponibles = count(array_filter($estados_consolidados, fn($e) => $e['estado_final'] === 'disponible'));
$total_limpieza = count(array_filter($estados_consolidados, fn($e) => $e['estado_final'] === 'limpieza'));
$total_mantenimiento = count(array_filter($estados_consolidados, fn($e) => $e['estado_final'] === 'mantenimiento'));
$porcentaje_ocupacion = $total_habitaciones > 0 ? round(($total_ocupadas / $total_habitaciones) * 100) : 0;

$por_piso = ['3' => [], '2' => [], '1' => []];
foreach ($estados_consolidados as $numero => $data) {
    $primer_digito = substr($numero, 0, 1);
    if (isset($por_piso[$primer_digito])) {
        $por_piso[$primer_digito][$numero] = $data;
    }
}

foreach ($por_piso as $piso => &$habs) {
    ksort($habs);
}

$mantenimientos_activos = [];
foreach ($mantenimientoModel->obtenerActivos() as $mant) {
    $mantenimientos_activos[$mant['habitacion_numero']] = $mant;
}

include __DIR__ . '/../../includes/header.php';
?>

<style>
    body {
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    }

    .dark body {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    }

    .glass-card {
        background: rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.9);
        box-shadow: 0 4px 24px rgba(0, 0, 0, 0.05);
    }

    .dark .glass-card {
        background: rgba(30, 41, 59, 0.8);
        border: 1px solid rgba(255, 255, 255, 0.1);
        box-shadow: 0 4px 24px rgba(0, 0, 0, 0.3);
    }

    .stat-card {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
    }

    .dark .stat-card:hover {
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.4);
    }

    .room-cell {
        aspect-ratio: 1;
        border: 3px solid;
        position: relative;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        overflow: hidden;
    }

    .room-cell:hover {
        transform: scale(1.05);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        z-index: 10;
    }

    .room-cell.disponible { 
        background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
        border-color: #10b981;
    }

    .room-cell.ocupada { 
        background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
        border-color: #ef4444;
    }

    .room-cell.limpieza { 
        background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
        border-color: #f59e0b;
    }

    .room-cell.mantenimiento { 
        background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
        border-color: #6b7280;
    }

    .dark .room-cell.disponible { 
        background: linear-gradient(135deg, #064e3b 0%, #065f46 100%);
        border-color: #34d399;
    }

    .dark .room-cell.ocupada { 
        background: linear-gradient(135deg, #7f1d1d 0%, #991b1b 100%);
        border-color: #f87171;
    }

    .dark .room-cell.limpieza { 
        background: linear-gradient(135deg, #78350f 0%, #92400e 100%);
        border-color: #fbbf24;
    }

    .dark .room-cell.mantenimiento { 
        background: linear-gradient(135deg, #374151 0%, #4b5563 100%);
        border-color: #9ca3af;
    }

    .room-number {
        font-size: 1.5rem;
        font-weight: 700;
    }

    .dark .room-number {
        color: #f9fafb;
    }

    .room-type {
        font-size: 0.65rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .dark .room-type {
        color: #d1d5db;
    }

    .floor-title {
        font-size: 0.75rem;
        font-weight: 600;
        letter-spacing: 2px;
        text-transform: uppercase;
        color: #64748b;
    }

    .dark .floor-title {
        color: #94a3b8;
    }

    .indicator-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        display: inline-block;
    }

    .indicator-dot.disponible { background: #10b981; }
    .indicator-dot.ocupada { background: #ef4444; }
    .indicator-dot.limpieza { background: #f59e0b; }
    .indicator-dot.mantenimiento { background: #6b7280; }

    .alert-badge {
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.7; }
    }

    .progress-bar {
        transition: width 0.5s ease-out;
    }

    .modal-overlay {
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(8px);
    }

    .modal-content {
        background: rgba(255, 255, 255, 0.98);
        backdrop-filter: blur(40px);
        border: 1px solid rgba(255, 255, 255, 0.9);
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
    }

    .dark .modal-content {
        background: rgba(30, 41, 59, 0.98);
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .btn-state {
        padding: 14px 20px;
        font-size: 0.875rem;
        font-weight: 500;
        border: 2px solid;
        transition: all 0.2s ease;
        cursor: pointer;
    }

    .btn-state:hover {
        transform: translateY(-1px);
    }

    .btn-disponible {
        background: #d1fae5;
        border-color: #10b981;
        color: #065f46;
    }

    .btn-disponible:hover {
        background: #10b981;
        color: white;
    }

    .dark .btn-disponible {
        background: #064e3b;
        border-color: #34d399;
        color: #d1fae5;
    }

    .dark .btn-disponible:hover {
        background: #34d399;
        color: #064e3b;
    }

    .btn-limpieza {
        background: #fef3c7;
        border-color: #f59e0b;
        color: #92400e;
    }

    .btn-limpieza:hover {
        background: #f59e0b;
        color: white;
    }

    .dark .btn-limpieza {
        background: #78350f;
        border-color: #fbbf24;
        color: #fef3c7;
    }

    .dark .btn-limpieza:hover {
        background: #fbbf24;
        color: #78350f;
    }

    .btn-mantenimiento {
        background: #f3f4f6;
        border-color: #6b7280;
        color: #374151;
    }

    .btn-mantenimiento:hover {
        background: #6b7280;
        color: white;
    }

    .dark .btn-mantenimiento {
        background: #374151;
        border-color: #9ca3af;
        color: #f3f4f6;
    }

    .dark .btn-mantenimiento:hover {
        background: #9ca3af;
        color: #374151;
    }

    .checkout-badge {
        background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
        border: 1px solid #f59e0b;
    }

    .dark .checkout-badge {
        background: linear-gradient(135deg, #78350f 0%, #92400e 100%);
        border-color: #fbbf24;
    }

    .problem-badge {
        background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
        border: 1px solid #ef4444;
        animation: pulse 2s infinite;
    }

    .dark .problem-badge {
        background: linear-gradient(135deg, #7f1d1d 0%, #991b1b 100%);
        border-color: #f87171;
    }
</style>

<div class="max-w-7xl mx-auto px-4 py-6">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900 dark:text-white tracking-tight">
                Dashboard de Habitaciones
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                <?php echo date('l, d F Y'); ?>
            </p>
        </div>
        <div class="flex gap-3">
            <a href="<?php echo BASE_PATH; ?>/index.php" 
               class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-slate-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 transition-all">
                ← Volver
            </a>
            <button onclick="location.reload()" 
                    class="px-4 py-2 text-sm font-medium text-white bg-slate-800 dark:bg-slate-700 rounded-lg hover:bg-slate-900 dark:hover:bg-slate-600 transition-all flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Actualizar
            </button>
        </div>
    </div>

    <?php if (!empty($habitaciones_con_problema)): ?>
    <div class="mb-6 problem-badge rounded-xl p-4">
        <div class="flex items-start gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-600 dark:text-red-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <div class="flex-1">
                <h3 class="font-semibold text-red-900 dark:text-red-300">Habitaciones con inconsistencia detectada</h3>
                <p class="text-sm text-red-700 dark:text-red-400 mt-1">
                    Las siguientes habitaciones tienen discordancia entre su estado en la base de datos y las ocupaciones activas:
                </p>
                <div class="flex flex-wrap gap-2 mt-3">
                    <?php foreach ($habitaciones_con_problema as $prob): ?>
                    <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-semibold rounded-full bg-red-100 dark:bg-red-900/50 text-red-800 dark:text-red-300">
                        <span class="w-2 h-2 rounded-full bg-red-500"></span>
                        Hab. <?php echo $prob['numero']; ?>: 
                        <?php echo $prob['estado_actual'] ?? 'vacío'; ?> → <strong><?php echo $prob['deberia_ser']; ?></strong>
                    </span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
        <div class="stat-card glass-card rounded-xl p-4 lg:p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Total</p>
                    <p class="text-2xl lg:text-3xl font-bold text-gray-900 dark:text-white mt-1"><?php echo $total_habitaciones; ?></p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-slate-100 dark:bg-slate-700 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-slate-600 dark:text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="stat-card glass-card rounded-xl p-4 lg:p-5 border-l-4 border-l-emerald-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Disponibles</p>
                    <p class="text-2xl lg:text-3xl font-bold text-emerald-600 dark:text-emerald-400 mt-1"><?php echo $total_disponibles; ?></p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center">
                    <span class="indicator-dot disponible"></span>
                </div>
            </div>
        </div>

        <div class="stat-card glass-card rounded-xl p-4 lg:p-5 border-l-4 border-l-red-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Ocupadas</p>
                    <p class="text-2xl lg:text-3xl font-bold text-red-600 dark:text-red-400 mt-1"><?php echo $total_ocupadas; ?></p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                    <span class="indicator-dot ocupada"></span>
                </div>
            </div>
        </div>

        <div class="stat-card glass-card rounded-xl p-4 lg:p-5 border-l-4 border-l-amber-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Limpieza</p>
                    <p class="text-2xl lg:text-3xl font-bold text-amber-600 dark:text-amber-400 mt-1"><?php echo $total_limpieza; ?></p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                    <span class="indicator-dot limpieza"></span>
                </div>
            </div>
        </div>

        <div class="stat-card glass-card rounded-xl p-4 lg:p-5 border-l-4 border-l-slate-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Mantenim.</p>
                    <p class="text-2xl lg:text-3xl font-bold text-slate-600 dark:text-slate-400 mt-1"><?php echo $total_mantenimiento; ?></p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-slate-100 dark:bg-slate-700 flex items-center justify-center">
                    <span class="indicator-dot mantenimiento"></span>
                </div>
            </div>
        </div>
    </div>

    <div class="glass-card rounded-xl p-4 mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-3">
            <div class="flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-600 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Ocupación del hotel</span>
            </div>
            <span class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo $porcentaje_ocupacion; ?>%</span>
        </div>
        <div class="h-3 bg-gray-200 dark:bg-slate-700 rounded-full overflow-hidden">
            <div class="progress-bar h-full bg-gradient-to-r from-emerald-500 to-emerald-400 rounded-full" 
                 style="width: <?php echo $porcentaje_ocupacion; ?>%"></div>
        </div>
    </div>

    <?php if (!empty($huespedes_hoy_salida)): ?>
    <div class="checkout-badge rounded-xl p-4 mb-6">
        <div class="flex items-start gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-amber-600 dark:text-amber-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div class="flex-1">
                <h3 class="font-semibold text-amber-900 dark:text-amber-300">Check-outs programados para hoy</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 mt-3">
                    <?php foreach ($huespedes_hoy_salida as $hab_num => $huespedes): ?>
                        <?php foreach ($huespedes as $h): ?>
                        <div class="bg-white dark:bg-slate-800/50 rounded-lg p-3 border border-amber-200 dark:border-amber-700">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-amber-800 dark:text-amber-300">Hab. <?php echo $hab_num; ?></span>
                                <span class="text-xs text-amber-600 dark:text-amber-400">12:00 PM</span>
                            </div>
                            <p class="text-sm text-gray-700 dark:text-gray-300 mt-1"><?php echo $h['nombres_apellidos']; ?></p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">CI: <?php echo $h['ci_pasaporte']; ?></p>
                        </div>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="flex items-center gap-4 mb-4 text-sm">
        <div class="flex items-center gap-2">
            <span class="indicator-dot disponible"></span>
            <span class="text-gray-700 dark:text-gray-300 font-medium">Disponible</span>
        </div>
        <div class="flex items-center gap-2">
            <span class="indicator-dot ocupada"></span>
            <span class="text-gray-700 dark:text-gray-300 font-medium">Ocupada</span>
        </div>
        <div class="flex items-center gap-2">
            <span class="indicator-dot limpieza"></span>
            <span class="text-gray-700 dark:text-gray-300 font-medium">Limpieza</span>
        </div>
        <div class="flex items-center gap-2">
            <span class="indicator-dot mantenimiento"></span>
            <span class="text-gray-700 dark:text-gray-300 font-medium">Mantenimiento</span>
        </div>
    </div>

    <?php foreach (['3', '2', '1'] as $num_piso): 
        if (empty($por_piso[$num_piso])) continue;
    ?>
    <div class="mb-8">
        <div class="flex items-center gap-4 mb-4">
            <h2 class="floor-title">Piso <?php echo $num_piso; ?></h2>
            <div class="h-px bg-gray-200 dark:bg-slate-700 flex-1"></div>
            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                <?php echo count(array_filter($por_piso[$num_piso], fn($e) => $e['estado_final'] === 'ocupada')); ?>/<?php echo count($por_piso[$num_piso]); ?> ocupada(s)
            </span>
        </div>
        
        <div class="grid grid-cols-4 sm:grid-cols-5 md:grid-cols-6 lg:grid-cols-8 gap-3">
            <?php foreach ($por_piso[$num_piso] as $numero => $data): 
                $estado = $data['estado_final'];
                $huespedes = $data['huespedes'];
                $tiene_mant = isset($mantenimientos_activos[$numero]);
                $primero = $huespedes[0] ?? null;
            ?>
            <div class="room-cell <?php echo $estado; ?>" 
                 onclick='openModal(<?php echo json_encode([
                     'id' => $data['habitacion']['id'],
                     'numero' => $numero,
                     'tipo' => $data['habitacion']['tipo'],
                     'precio_dia' => $data['habitacion']['precio_dia'],
                     'estado' => $estado,
                     'estado_db' => $data['estado_db'],
                     'tiene_ocupacion_activa' => $data['tiene_ocupacion_activa']
                 ]); ?>, <?php echo isset($mantenimientos_activos[$numero]) ? json_encode($mantenimientos_activos[$numero]) : 'null'; ?>, <?php echo !empty($huespedes) ? json_encode([
                     'multiples' => count($huespedes) > 1,
                     'huespedes' => array_map(fn($h) => [
                         'nombres_apellidos' => $h['nombres_apellidos'],
                         'ci_pasaporte' => $h['ci_pasaporte'],
                         'genero' => $h['genero'],
                         'edad' => $h['edad'],
                         'nacionalidad' => $h['nacionalidad'],
                         'nro_dias' => $h['nro_dias'],
                         'fecha_ingreso' => $h['fecha_ingreso'],
                         'fecha_salida_estimada' => $h['fecha_salida_estimada']
                     ], $huespedes)
                 ]) : 'null'; ?>)'>
                <div class="absolute inset-0 flex flex-col items-center justify-center">
                    <span class="room-number"><?php echo $numero; ?></span>
                    <span class="room-type mt-1"><?php echo $data['habitacion']['tipo']; ?></span>
                    
                    <?php if ($data['tiene_ocupacion_activa']): ?>
                    <div class="mt-2 px-2 py-0.5 bg-red-500 text-white text-xs font-bold rounded-full">
                        <?php echo count($huespedes); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($tiene_mant): ?>
                    <span class="absolute top-1 right-1 w-3 h-3 bg-orange-500 rounded-full animate-pulse" 
                          title="Con mantenimiento activo"></span>
                    <?php endif; ?>
                    
                    <?php if (isset($huespedes_hoy_salida[$numero])): ?>
                    <span class="absolute bottom-1 left-1/2 transform -translate-x-1/2 text-[10px] font-bold text-amber-700 dark:text-amber-300 bg-amber-100 dark:bg-amber-900/50 px-1.5 py-0.5 rounded">
                        SALIDA
                    </span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div id="modal" class="modal-overlay hidden fixed inset-0 z-50 flex items-center justify-center p-4" onclick="closeModal()">
    <div class="modal-content max-w-lg w-full p-6 rounded-2xl max-h-[90vh] overflow-y-auto" onclick="event.stopPropagation()">
        <div class="flex items-start justify-between mb-4">
            <div>
                <div class="flex items-baseline gap-2">
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white" id="m-numero"></h3>
                    <span class="text-sm text-gray-500" id="m-tipo"></span>
                </div>
                <p class="text-sm text-gray-500 mt-1">Bs. <span id="m-precio"></span> por noche</p>
            </div>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        
        <div class="mb-4 pb-4 border-b border-gray-200 dark:border-gray-700">
            <p class="text-xs uppercase tracking-wider text-gray-400 mb-2">Estado</p>
            <div class="flex items-center gap-2">
                <span class="indicator-dot" id="m-status-dot"></span>
                <span class="font-semibold text-gray-900 dark:text-white" id="m-status-text"></span>
            </div>
            <p class="text-xs text-gray-500 mt-2" id="m-db-status"></p>
        </div>
        
        <div id="m-mantenimiento-info" class="hidden mb-4 pb-4 border-b border-gray-200 dark:border-gray-700">
        </div>
        
        <div id="m-huesped-info" class="hidden mb-4 pb-4 border-b border-gray-200 dark:border-gray-700">
        </div>
        
        <form method="POST" class="space-y-2" id="form-cambiar-estado">
            <input type="hidden" name="habitacion_id" id="m-id">
            
            <button type="submit" name="nuevo_estado" value="disponible" class="btn-state w-full text-left rounded-xl btn-disponible">
                ✓ Disponible
            </button>
            
            <button type="submit" name="nuevo_estado" value="limpieza" class="btn-state w-full text-left rounded-xl btn-limpieza">
                🧹 Limpieza
            </button>
            
            <button type="submit" name="nuevo_estado" value="mantenimiento" class="btn-state w-full text-left rounded-xl btn-mantenimiento">
                🔧 Mantenimiento
            </button>
            
            <button type="button" onclick="closeModal()" class="w-full py-3 text-sm text-gray-500 hover:text-gray-900 dark:hover:text-gray-300 transition">
                Cancelar
            </button>
        </form>
    </div>
</div>

<script>
const statusMap = {
    'disponible': { text: 'Disponible', class: 'disponible', icon: '✓' },
    'ocupada': { text: 'Ocupada', class: 'ocupada', icon: '👤' },
    'limpieza': { text: 'En limpieza', class: 'limpieza', icon: '🧹' },
    'mantenimiento': { text: 'En mantenimiento', class: 'mantenimiento', icon: '🔧' }
};

function openModal(room, mantenimiento = null, huesped = null) {
    document.getElementById('m-numero').textContent = room.numero;
    document.getElementById('m-tipo').textContent = room.tipo;
    document.getElementById('m-precio').textContent = parseFloat(room.precio_dia).toFixed(2);
    document.getElementById('m-id').value = room.id;
    
    const status = statusMap[room.estado] || statusMap['disponible'];
    const statusDot = document.getElementById('m-status-dot');
    statusDot.className = 'indicator-dot ' + status.class;
    document.getElementById('m-status-text').textContent = status.text;
    
    let dbStatusText = 'Estado en BD: ';
    if (room.tiene_ocupacion_activa && room.estado !== 'ocupada') {
        dbStatusText += ` "${room.estado_db || 'vacío'}" (debería ser: ocupada)`;
    } else if (!room.tiene_ocupacion_activa && room.estado === 'ocupada') {
        dbStatusText += ` "${room.estado_db}" (debería ser: disponible)`;
    } else {
        dbStatusText += `"${room.estado_db}"`;
    }
    document.getElementById('m-db-status').textContent = dbStatusText;
    
    const mantInfo = document.getElementById('m-mantenimiento-info');
    if (mantenimiento && room.estado === 'mantenimiento') {
        mantInfo.classList.remove('hidden');
        mantInfo.innerHTML = `
            <div class="bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-xl p-4">
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-orange-600 dark:text-orange-400">🔧</span>
                    <p class="font-semibold text-orange-900 dark:text-orange-300">${mantenimiento.titulo}</p>
                </div>
                <p class="text-sm text-orange-700 dark:text-orange-400">${mantenimiento.descripcion}</p>
                <div class="flex items-center justify-between mt-3 pt-3 border-t border-orange-200 dark:border-orange-700 text-xs text-orange-600 dark:text-orange-400">
                    <span>Prioridad: ${mantenimiento.prioridad}</span>
                    <span>Desde: ${new Date(mantenimiento.fecha_inicio).toLocaleDateString('es-BO')}</span>
                </div>
            </div>`;
    } else {
        mantInfo.classList.add('hidden');
    }
    
    const huespedInfo = document.getElementById('m-huesped-info');
    const formEstado = document.getElementById('form-cambiar-estado');
    
    if (huesped && room.estado === 'ocupada') {
        huespedInfo.classList.remove('hidden');
        formEstado.classList.add('hidden');
        
        if (huesped.multiples) {
            let html = `
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="text-blue-600 dark:text-blue-400">👥</span>
                        <p class="font-bold text-blue-900 dark:text-blue-300">${huesped.huespedes.length} Huéspedes</p>
                    </div>
                    <div class="space-y-3">`;
            
            huesped.huespedes.forEach((h, i) => {
                const fechaSalida = new Date(h.fecha_salida_estimada).toLocaleDateString('es-BO');
                html += `
                    <div class="bg-white dark:bg-slate-800 rounded-lg p-3">
                        <p class="font-semibold text-gray-900 dark:text-white">${i + 1}. ${h.nombres_apellidos}</p>
                        <div class="grid grid-cols-2 gap-2 mt-2 text-xs">
                            <div><span class="text-gray-500">CI:</span> <span class="font-medium text-gray-700 dark:text-gray-300">${h.ci_pasaporte}</span></div>
                            <div><span class="text-gray-500">Edad:</span> <span class="font-medium text-gray-700 dark:text-gray-300">${h.edad} años</span></div>
                            <div><span class="text-gray-500">Nac.:</span> <span class="font-medium text-gray-700 dark:text-gray-300">${h.nacionalidad}</span></div>
                            <div><span class="text-gray-500">Días:</span> <span class="font-medium text-gray-700 dark:text-gray-300">${h.nro_dias}</span></div>
                        </div>
                        <div class="mt-2 pt-2 border-t border-gray-100 dark:border-gray-700 text-xs">
                            <span class="text-gray-500">Check-in: </span>
                            <span class="font-medium text-gray-700 dark:text-gray-300">${new Date(h.fecha_ingreso).toLocaleDateString('es-BO')}</span>
                            <span class="text-gray-400 mx-1">|</span>
                            <span class="text-gray-500">Check-out: </span>
                            <span class="font-medium text-gray-700 dark:text-gray-300">${fechaSalida}</span>
                        </div>
                    </div>`;
            });
            
            html += '</div></div>';
            huespedInfo.innerHTML = html;
        } else {
            const h = huesped.huespedes[0];
            const fechaSalida = new Date(h.fecha_salida_estimada).toLocaleDateString('es-BO');
            huespedInfo.innerHTML = `
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center text-white text-xl font-bold">
                            ${h.nombres_apellidos.charAt(0)}
                        </div>
                        <div>
                            <p class="font-bold text-blue-900 dark:text-blue-300">${h.nombres_apellidos}</p>
                            <p class="text-sm text-blue-700 dark:text-blue-400">CI: ${h.ci_pasaporte}</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div><span class="text-gray-500">Género:</span> <span class="font-medium text-gray-700 dark:text-gray-300">${h.genero === 'M' ? 'Masculino' : 'Femenino'}</span></div>
                        <div><span class="text-gray-500">Edad:</span> <span class="font-medium text-gray-700 dark:text-gray-300">${h.edad} años</span></div>
                        <div><span class="text-gray-500">Estadía:</span> <span class="font-medium text-gray-700 dark:text-gray-300">${h.nro_dias} días</span></div>
                        <div><span class="text-gray-500">Nacionalidad:</span> <span class="font-medium text-gray-700 dark:text-gray-300">${h.nacionalidad}</span></div>
                    </div>
                    <div class="mt-3 pt-3 border-t border-blue-200 dark:border-blue-700">
                        <div class="flex items-center justify-between text-sm">
                            <div><span class="text-gray-500">Check-in:</span> <span class="font-medium text-gray-700 dark:text-gray-300">${new Date(h.fecha_ingreso).toLocaleDateString('es-BO')}</span></div>
                            <div><span class="text-gray-500">Check-out:</span> <span class="font-medium text-gray-700 dark:text-gray-300">${fechaSalida}</span></div>
                        </div>
                    </div>
                </div>`;
        }
    } else {
        huespedInfo.classList.add('hidden');
        formEstado.classList.remove('hidden');
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
