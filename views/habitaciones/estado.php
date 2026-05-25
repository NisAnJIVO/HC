<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/Habitacion.php';
require_once __DIR__ . '/../../models/Mantenimiento.php';
require_once __DIR__ . '/../../models/RegistroOcupacion.php';
require_once __DIR__ . '/../../models/Huesped.php';

$page_title = 'Estado de Habitaciones';

$habitacionModel   = new Habitacion();
$mantenimientoModel = new Mantenimiento();
$registroModel     = new RegistroOcupacion();

$conn = getConnection();

$habitaciones       = $habitacionModel->obtenerTodas();
$ocupaciones_activas = $registroModel->obtenerActivos();

$huespedes_por_habitacion = [];
$huespedes_hoy_salida     = [];

foreach ($ocupaciones_activas as $ocup) {
    $hab_numero = $ocup['numero_habitacion'];
    if (!isset($huespedes_por_habitacion[$hab_numero])) {
        $huespedes_por_habitacion[$hab_numero] = [];
    }
    $huespedes_por_habitacion[$hab_numero][] = $ocup;

    $fecha_salida = date('Y-m-d', strtotime($ocup['fecha_salida_estimada']));
    $hoy          = date('Y-m-d');
    if ($fecha_salida === $hoy) {
        $huespedes_hoy_salida[$hab_numero][] = $ocup;
    }
}

$habitaciones_con_problema = [];
$estados_consolidados      = [];

foreach ($habitaciones as $hab) {
    $numero   = $hab['numero'];
    $estado_db = trim($hab['estado'] ?? '');

    if ($estado_db === '' || $estado_db === null) $estado_db = 'disponible';
    if ($estado_db === 'ocupado') $estado_db = 'ocupada';

    $tiene_ocupacion_activa = isset($huespedes_por_habitacion[$numero]);
    $tiene_mantenimiento    = isset($mantenimientoModel->obtenerActivos()[$numero]);

    if ($tiene_ocupacion_activa && $estado_db !== 'ocupada') {
        $habitaciones_con_problema[] = ['numero' => $numero, 'estado_actual' => $estado_db, 'deberia_ser' => 'ocupada'];
    }
    if (!$tiene_ocupacion_activa && $estado_db === 'ocupada') {
        $habitaciones_con_problema[] = ['numero' => $numero, 'estado_actual' => $estado_db, 'deberia_ser' => 'disponible'];
    }

    $estado_final = $estado_db;
    if ($tiene_ocupacion_activa) $estado_final = 'ocupada';
    if ($tiene_mantenimiento && $estado_db === 'mantenimiento') $estado_final = 'mantenimiento';

    $estados_consolidados[$numero] = [
        'habitacion'           => $hab,
        'estado_db'            => $estado_db,
        'estado_final'         => $estado_final,
        'tiene_ocupacion_activa' => $tiene_ocupacion_activa,
        'tiene_mantenimiento'  => $tiene_mantenimiento,
        'huespedes'            => $huespedes_por_habitacion[$numero] ?? [],
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['habitacion_id'])) {
    $habitacion_id = clean_input($_POST['habitacion_id']);
    $nuevo_estado  = clean_input($_POST['nuevo_estado']);
    if (!in_array($nuevo_estado, ['disponible', 'ocupada', 'limpieza', 'mantenimiento'])) {
        $nuevo_estado = 'disponible';
    }
    $conn = getConnection();
    $sql  = "UPDATE habitaciones SET estado = :estado WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':estado' => $nuevo_estado, ':id' => $habitacion_id]);
    header("Location: " . BASE_PATH . "/views/habitaciones/estado.php");
    exit;
}

$total_habitaciones  = count($habitaciones);
$total_ocupadas      = count(array_filter($estados_consolidados, fn($e) => $e['estado_final'] === 'ocupada'));
$total_disponibles   = count(array_filter($estados_consolidados, fn($e) => $e['estado_final'] === 'disponible'));
$total_limpieza      = count(array_filter($estados_consolidados, fn($e) => $e['estado_final'] === 'limpieza'));
$total_mantenimiento = count(array_filter($estados_consolidados, fn($e) => $e['estado_final'] === 'mantenimiento'));

$por_piso = ['3' => [], '2' => [], '1' => []];
foreach ($estados_consolidados as $numero => $data) {
    $primer_digito = substr($numero, 0, 1);
    if (isset($por_piso[$primer_digito])) {
        $por_piso[$primer_digito][$numero] = $data;
    }
}
foreach ($por_piso as $piso => &$habs) { ksort($habs); }
unset($habs);

$mantenimientos_activos = [];
foreach ($mantenimientoModel->obtenerActivos() as $mant) {
    $mantenimientos_activos[$mant['habitacion_numero']] = $mant;
}

include __DIR__ . '/../../includes/header.php';
?>

<style>
/* Page Styling & Themes */
body { background: #f4f4f5; }
.dark body { background: #080808; }

/* Page Header */
.page-eyebrow {
    font-size: 11px; letter-spacing: .12em;
    text-transform: uppercase; color: #aaa; font-weight: 500;
    margin-bottom: 8px;
}
.page-h1 {
    font-size: clamp(1.75rem, 3.5vw, 2.4rem);
    font-weight: 650; letter-spacing: -.04em;
    color: #111; line-height: 1.05;
}
.dark .page-h1 { color: #f2f2f2; }

/* Stat Cards */
.stat-card {
    background: #fff; border: 1px solid rgba(0,0,0,.07);
    border-radius: 18px; padding: 1.5rem;
    transition: transform .2s ease, box-shadow .2s ease;
}
.stat-card:hover { transform: translateY(-3px); box-shadow: 0 16px 48px rgba(0,0,0,.1); }
.dark .stat-card { background: #161616; border-color: rgba(255,255,255,.07); }
.dark .stat-card:hover { box-shadow: 0 16px 48px rgba(0,0,0,.55); }

.stat-label {
    font-size: 10px; font-weight: 600;
    letter-spacing: .1em; text-transform: uppercase;
    color: #999; margin-bottom: 14px;
    display: flex; align-items: center; gap: 6px;
}
.stat-dot { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }

.stat-number {
    font-size: 3.75rem; font-weight: 700;
    letter-spacing: -.06em; line-height: 1;
    color: #111; margin-bottom: 1.1rem;
    font-variant-numeric: tabular-nums;
}
.dark .stat-number { color: #f0f0f0; }

.stat-track { height: 2px; background: rgba(0,0,0,.07); border-radius: 99px; overflow: hidden; }
.dark .stat-track { background: rgba(255,255,255,.07); }
.stat-fill { height: 100%; border-radius: 99px; width: 0; transition: width 1.3s cubic-bezier(.4,0,.2,1); }

/* Content Cards */
.c-card {
    background: #fff; border: 1px solid rgba(0,0,0,.07);
    border-radius: 18px; overflow: hidden;
}
.dark .c-card { background: #141414; border-color: rgba(255,255,255,.07); }

.c-head {
    padding: 1.1rem 1.5rem; border-bottom: 1px solid rgba(0,0,0,.06);
    display: flex; align-items: center; justify-content: space-between;
}
.dark .c-head { border-bottom-color: rgba(255,255,255,.06); }

.c-title { font-size: 13px; font-weight: 600; color: #111; letter-spacing: -.015em; }
.dark .c-title { color: #eee; }

.c-badge {
    font-size: 11px; font-weight: 600;
    background: rgba(0,0,0,.05); color: #666;
    padding: 2px 9px; border-radius: 99px;
    font-variant-numeric: tabular-nums;
}
.dark .c-badge { background: rgba(255,255,255,.08); color: #777; }

/* Room Grid Chips */
.rg-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(38px,1fr)); gap: 5px; padding: 1.1rem; }
.rg-chip {
    aspect-ratio: 1; border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: 10px; font-weight: 600;
    letter-spacing: -.02em; font-variant-numeric: tabular-nums;
    transition: transform .15s ease; cursor: default;
}
.rg-chip:hover { transform: scale(1.12); }
.rg-chip.disponible    { background: rgba(34,197,94,.1);  color: #16a34a; border: 1px solid rgba(34,197,94,.18); }
.rg-chip.ocupada       { background: #111; color: #fff; }
.rg-chip.limpieza      { background: rgba(59,130,246,.1); color: #2563eb; border: 1px solid rgba(59,130,246,.15); }
.rg-chip.mantenimiento { background: rgba(245,158,11,.1); color: #b45309; border: 1px solid rgba(245,158,11,.15); }
.dark .rg-chip.disponible    { background: rgba(34,197,94,.12);  color: #4ade80; border-color: rgba(34,197,94,.2); }
.dark .rg-chip.ocupada       { background: #e8e8e8; color: #111; }
.dark .rg-chip.limpieza      { background: rgba(59,130,246,.12); color: #60a5fa; border-color: rgba(59,130,246,.2); }
.dark .rg-chip.mantenimiento { background: rgba(245,158,11,.12); color: #fbbf24; border-color: rgba(245,158,11,.2); }

.rg-legend { display: flex; flex-wrap: wrap; gap: 10px; padding: 0 1.1rem 1.1rem; }
.rg-leg    { display: flex; align-items: center; gap: 5px; font-size: 10px; color: #999; font-weight: 500; }
.rg-leg-dot { width: 7px; height: 7px; border-radius: 50%; }

/* Floor Section & Room Cells */
.floor-label {
    font-size: 11px; font-weight: 600; color: #999;
    letter-spacing: .12em; text-transform: uppercase; margin-bottom: 1rem;
}
.dark .floor-label { color: #666; }

.room-cell {
    position: relative; border-radius: 14px;
    aspect-ratio: 1; cursor: pointer;
    transition: transform .15s ease, box-shadow .15s ease;
    overflow: hidden;
}
.room-cell:hover { transform: scale(1.06); box-shadow: 0 8px 24px rgba(0,0,0,.14); }

.room-cell.disponible    { background: rgba(34,197,94,.1);  border: 1px solid rgba(34,197,94,.2); }
.room-cell.ocupada       { background: #111; }
.room-cell.limpieza      { background: rgba(59,130,246,.1); border: 1px solid rgba(59,130,246,.18); }
.room-cell.mantenimiento { background: rgba(245,158,11,.1); border: 1px solid rgba(245,158,11,.2); }
.dark .room-cell.disponible    { background: rgba(34,197,94,.12);  border-color: rgba(34,197,94,.25); }
.dark .room-cell.ocupada       { background: #dedede; }
.dark .room-cell.limpieza      { background: rgba(59,130,246,.12); border-color: rgba(59,130,246,.25); }
.dark .room-cell.mantenimiento { background: rgba(245,158,11,.12); border-color: rgba(245,158,11,.25); }

.room-number {
    font-size: 13px; font-weight: 700; letter-spacing: -.03em;
    font-variant-numeric: tabular-nums;
}
.room-cell.disponible    .room-number { color: #16a34a; }
.room-cell.ocupada       .room-number { color: #fff; }
.room-cell.limpieza      .room-number { color: #2563eb; }
.room-cell.mantenimiento .room-number { color: #b45309; }
.dark .room-cell.disponible    .room-number { color: #4ade80; }
.dark .room-cell.ocupada       .room-number { color: #111; }
.dark .room-cell.limpieza      .room-number { color: #60a5fa; }
.dark .room-cell.mantenimiento .room-number { color: #fbbf24; }

.room-type {
    font-size: 8px; font-weight: 500; letter-spacing: .03em;
    text-transform: uppercase; opacity: .65; margin-top: 2px;
}
.room-cell.disponible    .room-type { color: #16a34a; }
.room-cell.ocupada       .room-type { color: rgba(255,255,255,.75); }
.room-cell.limpieza      .room-type { color: #2563eb; }
.room-cell.mantenimiento .room-type { color: #b45309; }
.dark .room-cell.ocupada .room-type { color: rgba(0,0,0,.6); }

/* Alert Box */
.alert-box {
    background: rgba(239,68,68,.08); border: 1px solid rgba(239,68,68,.25);
    border-radius: 18px; padding: 1.5rem; margin-bottom: 2rem;
}
.dark .alert-box { background: rgba(239,68,68,.06); border-color: rgba(239,68,68,.18); }
.alert-title { font-size: 13px; font-weight: 600; color: #b91c1c; margin-bottom: .5rem; }
.dark .alert-title { color: #fca5a5; }
.alert-text  { font-size: 12px; color: #7f1d1d; margin-bottom: .75rem; }
.dark .alert-text  { color: #fed7d7; }

/* ─── APPLE MAX SLEEK MODAL ─── */
.modal-overlay {
    background: rgba(0, 0, 0, 0.35);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    animation: modalOverlayFadeIn 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
.modal-content {
    background: #ffffff;
    border: 1px solid rgba(0, 0, 0, 0.06);
    border-radius: 24px;
    box-shadow: 0 40px 80px rgba(0, 0, 0, 0.16);
    animation: modalContentScaleUp 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
.dark .modal-content {
    background: #1c1c1e;
    border-color: rgba(255, 255, 255, 0.08);
    box-shadow: 0 40px 80px rgba(0, 0, 0, 0.5);
}

@keyframes modalOverlayFadeIn {
    from { opacity: 0; }
    to   { opacity: 1; }
}
@keyframes modalContentScaleUp {
    from { opacity: 0; transform: scale(0.93) translateY(12px); }
    to   { opacity: 1; transform: scale(1) translateY(0); }
}

.modal-header-clean {
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    padding: 24px 28px;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}
.dark .modal-header-clean {
    border-bottom-color: rgba(255, 255, 255, 0.06);
}

.modal-room-num {
    font-size: 1.5rem;
    font-weight: 750;
    letter-spacing: -0.04em;
    color: #1c1c1e;
    line-height: 1.1;
}
.dark .modal-room-num {
    color: #ffffff;
}

.modal-room-tipo {
    font-size: 12px;
    font-weight: 450;
    letter-spacing: -0.01em;
}

.btn-close-clean {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: rgba(0, 0, 0, 0.04);
    color: #8e8e93;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.2s, color 0.2s, transform 0.15s;
}
.btn-close-clean:hover {
    background: rgba(0, 0, 0, 0.08);
    color: #1c1c1e;
}
.btn-close-clean:active {
    transform: scale(0.95);
}
.dark .btn-close-clean {
    background: rgba(255, 255, 255, 0.06);
    color: #aeaea2;
}
.dark .btn-close-clean:hover {
    background: rgba(255, 255, 255, 0.12);
    color: #ffffff;
}

/* Dynamic status badge */
.status-pill-clean {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 99px;
    font-size: 11px;
    font-weight: 600;
    letter-spacing: -0.01em;
}
.status-pill-clean.disponible { background: rgba(52, 199, 89, 0.1); color: #24b23e; }
.status-pill-clean.ocupada { background: rgba(142, 142, 147, 0.1); color: #8e8e93; }
.status-pill-clean.limpieza { background: rgba(0, 122, 255, 0.1); color: #007aff; }
.status-pill-clean.mantenimiento { background: rgba(255, 149, 0, 0.1); color: #ff9500; }

.dark .status-pill-clean.disponible { background: rgba(52, 199, 89, 0.15); color: #30d158; }
.dark .status-pill-clean.ocupada { background: rgba(142, 142, 147, 0.2); color: #a2a2a6; }
.dark .status-pill-clean.limpieza { background: rgba(10, 132, 255, 0.15); color: #0a84ff; }
.dark .status-pill-clean.mantenimiento { background: rgba(255, 159, 10, 0.15); color: #ff9f0a; }

/* Info Grid & Elements */
.info-pair {
    display: flex;
    flex-direction: column;
    gap: 2px;
}
.info-label {
    font-size: 9px;
    font-weight: 600;
    letter-spacing: .06em;
    text-transform: uppercase;
    color: #8e8e93;
}
.info-value {
    font-size: 13px;
    font-weight: 550;
    color: #1c1c1e;
    letter-spacing: -.01em;
}
.dark .info-value { color: #f5f5f7; }

/* Date cards style */
.date-card-clean {
    flex: 1;
    background: #f5f5f7;
    border: 1px solid rgba(0, 0, 0, 0.02);
    border-radius: 14px;
    padding: 12px 14px;
    display: flex;
    align-items: center;
    gap: 12px;
}
.dark .date-card-clean {
    background: rgba(255, 255, 255, 0.03);
    border-color: rgba(255, 255, 255, 0.02);
}

/* Guest avatar clean style */
.guest-avatar-clean {
    width: 46px;
    height: 46px;
    border-radius: 50%;
    background: linear-gradient(135deg, #f5f5f7 0%, #e5e5ea 100%);
    color: #1c1c1e;
    font-weight: 650;
    font-size: 17px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid rgba(0, 0, 0, 0.04);
}
.dark .guest-avatar-clean {
    background: linear-gradient(135deg, #2c2c2e 0%, #1c1c1e 100%);
    color: #f5f5f7;
    border-color: rgba(255, 255, 255, 0.06);
}

/* Maintenance card clean style */
.maintenance-card-clean {
    background: rgba(255, 149, 0, 0.04);
    border: 1px solid rgba(255, 149, 0, 0.15);
    border-radius: 18px;
    padding: 18px;
}
.dark .maintenance-card-clean {
    background: rgba(255, 159, 10, 0.03);
    border-color: rgba(255, 159, 10, 0.15);
}

/* State Change Buttons - Minimal and clean */
.btn-action-clean {
    width: 100%;
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 18px;
    border-radius: 14px;
    font-size: 13px;
    font-weight: 550;
    text-align: left;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    border: 1px solid transparent;
    cursor: pointer;
}
.btn-action-clean:hover {
    transform: translateY(-1px);
}
.btn-action-clean:active {
    transform: translateY(0) scale(0.985);
}

.btn-action-clean.disponible {
    background: #f5f5f7;
    color: #1c1c1e;
}
.btn-action-clean.disponible:hover {
    background: rgba(52, 199, 89, 0.08);
    color: #24b23e;
    border-color: rgba(52, 199, 89, 0.15);
}

.btn-action-clean.limpieza {
    background: #f5f5f7;
    color: #1c1c1e;
}
.btn-action-clean.limpieza:hover {
    background: rgba(0, 122, 255, 0.08);
    color: #007aff;
    border-color: rgba(0, 122, 255, 0.15);
}

.btn-action-clean.mantenimiento {
    background: #f5f5f7;
    color: #1c1c1e;
}
.btn-action-clean.mantenimiento:hover {
    background: rgba(255, 149, 0, 0.08);
    color: #ff9500;
    border-color: rgba(255, 149, 0, 0.15);
}

.dark .btn-action-clean {
    background: #2c2c2e;
    color: #f5f5f7;
}
.dark .btn-action-clean.disponible:hover {
    background: rgba(48, 209, 88, 0.12);
    color: #30d158;
    border-color: rgba(48, 209, 88, 0.2);
}
.dark .btn-action-clean.limpieza:hover {
    background: rgba(10, 132, 255, 0.12);
    color: #0a84ff;
    border-color: rgba(10, 132, 255, 0.2);
}
.dark .btn-action-clean.mantenimiento:hover {
    background: rgba(255, 159, 10, 0.12);
    color: #ff9f0a;
    border-color: rgba(255, 159, 10, 0.2);
}

/* Animations delay */
.f-up { opacity: 0; animation: fadeUp 0.45s ease-out forwards; }
.f-up:nth-child(1) { animation-delay: 0ms; }
.f-up:nth-child(2) { animation-delay: 40ms; }
.f-up:nth-child(3) { animation-delay: 80ms; }
.f-up:nth-child(4) { animation-delay: 120ms; }
.f-up:nth-child(5) { animation-delay: 160ms; }

@keyframes fadeUp {
    from { opacity: 0; transform: translateY(10px); }
    to   { opacity: 1; transform: translateY(0); }
}
</style>

<!-- Page header -->
<div class="flex items-start justify-between mb-8">
    <div>
        <p class="page-eyebrow">Habitaciones</p>
        <h1 class="page-h1">Estado y disponibilidad</h1>
    </div>
    <div class="flex gap-2">
        <a href="<?php echo BASE_PATH; ?>/index.php"
           class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-all">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
            Volver
        </a>
        <button onclick="location.reload()"
                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-gray-900 dark:bg-gray-700 rounded-xl hover:bg-gray-800 dark:hover:bg-gray-600 transition-all">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            Actualizar
        </button>
    </div>
</div>

<!-- Alert: inconsistencias -->
<?php if (!empty($habitaciones_con_problema)): ?>
<div class="alert-box">
    <div class="alert-title">Inconsistencia detectada</div>
    <div class="alert-text">Las siguientes habitaciones tienen discordancia entre su estado y las ocupaciones activas:</div>
    <div class="flex flex-wrap gap-2">
        <?php foreach ($habitaciones_con_problema as $prob): ?>
        <span class="text-xs font-semibold px-3 py-1.5 rounded-full bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400">
            Hab. <?php echo $prob['numero']; ?>: <?php echo $prob['estado_actual']; ?> → <?php echo $prob['deberia_ser']; ?>
        </span>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Metric cards -->
<?php
$total_h = max(1, $total_habitaciones);
$metrics = [
    ['label' => 'Total',       'val' => $total_habitaciones,  'color' => '#111',    'fill' => '#111',    'dot' => '#111',    'pct' => 100],
    ['label' => 'Disponibles', 'val' => $total_disponibles,   'color' => '#16a34a', 'fill' => '#22c55e', 'dot' => '#22c55e', 'pct' => round($total_disponibles   / $total_h * 100)],
    ['label' => 'Ocupadas',    'val' => $total_ocupadas,      'color' => '#111',    'fill' => '#111',    'dot' => '#111',    'pct' => round($total_ocupadas      / $total_h * 100)],
    ['label' => 'Limpieza',    'val' => $total_limpieza,      'color' => '#2563eb', 'fill' => '#3b82f6', 'dot' => '#3b82f6', 'pct' => round($total_limpieza      / $total_h * 100)],
    ['label' => 'Mantenim.',   'val' => $total_mantenimiento, 'color' => '#b45309', 'fill' => '#f59e0b', 'dot' => '#f59e0b', 'pct' => round($total_mantenimiento / $total_h * 100)],
];
?>
<div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
    <?php foreach ($metrics as $m): ?>
    <div class="stat-card f-up">
        <div class="stat-label">
            <span class="stat-dot" style="background:<?php echo $m['dot']; ?>"></span>
            <?php echo $m['label']; ?>
        </div>
        <div class="stat-number" style="color:<?php echo $m['color']; ?>"><?php echo $m['val']; ?></div>
        <div class="stat-track">
            <div class="stat-fill" data-w="<?php echo $m['pct']; ?>" style="background:<?php echo $m['fill']; ?>"></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Check-outs de hoy -->
<?php if (!empty($huespedes_hoy_salida)): ?>
<div class="c-card mb-8" style="border-left: 3px solid #f59e0b;">
    <div class="c-head">
        <h3 class="c-title" style="color:#b45309">Check-outs programados para hoy</h3>
        <span class="c-badge"><?php echo count($huespedes_hoy_salida); ?></span>
    </div>
    <div class="p-5">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            <?php foreach ($huespedes_hoy_salida as $hab_num => $huespedes): ?>
                <?php foreach ($huespedes as $h): ?>
                <div style="background:rgba(245,158,11,.07);border:1px solid rgba(245,158,11,.2)" class="rounded-xl p-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="font-bold text-sm" style="color:#b45309">Hab. <?php echo $hab_num; ?></span>
                        <span class="text-xs text-amber-600 dark:text-amber-400 font-semibold font-mono">12:00 PM</span>
                    </div>
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($h['nombres_apellidos']); ?></p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">CI: <?php echo htmlspecialchars($h['ci_pasaporte']); ?></p>
                </div>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Mapa rápido de chips -->
<div class="c-card mb-8">
    <div class="c-head">
        <span class="c-title">Mapa de habitaciones</span>
        <span class="c-badge"><?php echo $total_habitaciones; ?></span>
    </div>
    <div class="rg-grid">
        <?php foreach ($estados_consolidados as $numero => $data): ?>
        <div class="rg-chip <?php echo $data['estado_final']; ?>" title="Hab. <?php echo $numero; ?> — <?php echo ucfirst($data['estado_final']); ?>">
            <?php echo $numero; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <div class="rg-legend">
        <div class="rg-leg"><span class="rg-leg-dot" style="background:#111"></span>Ocupada</div>
        <div class="rg-leg"><span class="rg-leg-dot" style="background:#22c55e"></span>Libre</div>
        <div class="rg-leg"><span class="rg-leg-dot" style="background:#3b82f6"></span>Limpieza</div>
        <div class="rg-leg"><span class="rg-leg-dot" style="background:#f59e0b"></span>Mantto.</div>
    </div>
</div>

<!-- Habitaciones por piso -->
<?php foreach (['3', '2', '1'] as $num_piso):
    if (empty($por_piso[$num_piso])) continue;
    $ocupadas_piso = count(array_filter($por_piso[$num_piso], fn($e) => $e['estado_final'] === 'ocupada'));
?>
<div class="mb-8">
    <div class="flex items-center gap-4 mb-4">
        <h2 class="floor-label">Piso <?php echo $num_piso; ?></h2>
        <div class="h-px flex-1" style="background:rgba(0,0,0,.08)"></div>
        <span class="text-xs font-medium text-gray-400 dark:text-gray-600 font-mono">
            <?php echo $ocupadas_piso; ?>/<?php echo count($por_piso[$num_piso]); ?> ocupada(s)
        </span>
    </div>
    <div class="grid grid-cols-4 sm:grid-cols-5 md:grid-cols-6 lg:grid-cols-8 gap-3">
        <?php foreach ($por_piso[$num_piso] as $numero => $data):
            $estado   = $data['estado_final'];
            $huespedes = $data['huespedes'];
            $tiene_mant = isset($mantenimientos_activos[$numero]);
        ?>
        <div class="room-cell <?php echo $estado; ?>"
             onclick='openModal(<?php echo json_encode([
                 'id'                  => $data['habitacion']['id'],
                 'numero'              => $numero,
                 'tipo'                => $data['habitacion']['tipo'],
                 'precio_dia'          => $data['habitacion']['precio_dia'],
                 'estado'              => $estado,
                 'estado_db'           => $data['estado_db'],
                 'tiene_ocupacion_activa' => $data['tiene_ocupacion_activa'],
             ]); ?>, <?php echo isset($mantenimientos_activos[$numero]) ? json_encode($mantenimientos_activos[$numero]) : 'null'; ?>, <?php echo !empty($huespedes) ? json_encode([
                 'multiples' => count($huespedes) > 1,
                 'huespedes' => array_map(fn($h) => [
                     'nombres_apellidos'   => $h['nombres_apellidos'],
                     'ci_pasaporte'        => $h['ci_pasaporte'],
                     'genero'              => $h['genero'],
                     'edad'                => $h['edad'],
                     'nacionalidad'        => $h['nacionalidad'],
                     'nro_dias'            => $h['nro_dias'],
                     'fecha_ingreso'       => $h['fecha_ingreso'],
                     'fecha_salida_estimada' => $h['fecha_salida_estimada'],
                 ], $huespedes),
             ]) : 'null'; ?>)'>
            <div class="absolute inset-0 flex flex-col items-center justify-center">
                <span class="room-number"><?php echo $numero; ?></span>
                <span class="room-type"><?php echo $data['habitacion']['tipo']; ?></span>

                <?php if ($data['tiene_ocupacion_activa']): ?>
                <div class="mt-1.5 px-2 py-0.5 rounded-full text-white text-[10px] font-bold font-mono" style="background:rgba(255,255,255,.25)">
                    <?php echo count($huespedes); ?>
                </div>
                <?php endif; ?>

                <?php if ($tiene_mant): ?>
                <span class="absolute top-1 right-1 w-2.5 h-2.5 rounded-full animate-pulse bg-orange-500" title="Mantenimiento activo"></span>
                <?php endif; ?>

                <?php if (isset($huespedes_hoy_salida[$numero])): ?>
                <span class="absolute bottom-1 left-1/2 -translate-x-1/2 text-[8px] font-bold px-1.5 py-0.5 rounded bg-orange-500/20 text-orange-600">SALIDA</span>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endforeach; ?>

<!-- APPLE MAX SLEEK MODAL -->
<div id="modal" class="modal-overlay hidden fixed inset-0 z-50 flex items-center justify-center p-4" onclick="closeModal()">
    <div class="modal-content max-w-md w-full max-h-[92vh] overflow-hidden flex flex-col" onclick="event.stopPropagation()">

        <!-- Clean Header -->
        <div class="modal-header-clean" id="m-header">
            <div>
                <div class="modal-estado-badge mb-2.5" id="m-estado-badge"></div>
                <h2 class="modal-room-num" id="m-numero"></h2>
                <p class="modal-room-tipo text-gray-500 dark:text-gray-400 mt-1 flex items-center gap-1.5" id="m-tipo-sub">
                    <span id="m-tipo"></span>
                    <span class="text-gray-300 dark:text-gray-600">•</span>
                    <span class="font-semibold text-gray-800 dark:text-gray-200">Bs. <span id="m-precio" class="font-mono"></span> / noche</span>
                </p>
            </div>
            <button onclick="closeModal()" class="btn-close-clean">
                <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Scrollable body -->
        <div class="overflow-y-auto flex-1 p-6 space-y-5">

            <!-- Info mantenimiento -->
            <div id="m-mantenimiento-info" class="hidden"></div>

            <!-- Info huésped -->
            <div id="m-huesped-info" class="hidden"></div>

            <!-- Debug subtle state (BD) -->
            <p class="text-[9px] font-semibold text-gray-300 dark:text-gray-600 font-mono tracking-wider uppercase" id="m-db-status"></p>

            <!-- Acciones -->
            <form method="POST" class="space-y-2.5" id="form-cambiar-estado">
                <input type="hidden" name="habitacion_id" id="m-id">
                <p class="text-[10px] uppercase tracking-wider text-gray-400 dark:text-gray-500 font-semibold mb-2">Cambiar estado de habitación</p>
                
                <button type="submit" name="nuevo_estado" value="disponible"
                        class="btn-action-clean disponible">
                    <span class="w-5 h-5 rounded-full flex items-center justify-center bg-green-500/10 text-green-600 flex-shrink-0">
                        <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                    </span>
                    <span>Marcar como disponible</span>
                </button>
                
                <button type="submit" name="nuevo_estado" value="limpieza"
                        class="btn-action-clean limpieza">
                    <span class="w-5 h-5 rounded-full flex items-center justify-center bg-blue-500/10 text-blue-600 flex-shrink-0">
                        <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 21m0 0l-.813-5.096M9 21h3m-3.34-8.813a4.5 4.5 0 006.68 0M2 8.63a9 9 0 0112.42-7.9L22 6.24H12m0 0L8.24 3.76"/>
                        </svg>
                    </span>
                    <span>Iniciar limpieza</span>
                </button>
                
                <button type="submit" name="nuevo_estado" value="mantenimiento"
                        class="btn-action-clean mantenimiento">
                    <span class="w-5 h-5 rounded-full flex items-center justify-center bg-orange-500/10 text-orange-600 flex-shrink-0">
                        <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </span>
                    <span>Poner en mantenimiento</span>
                </button>
            </form>

            <button type="button" onclick="closeModal()"
                    class="w-full py-2.5 text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition font-semibold uppercase tracking-wider">
                Cerrar
            </button>
        </div>
    </div>
</div>

<script>
const statusMap = {
    'disponible':    { text: 'Disponible',      class: 'disponible',    color: '#34c759' },
    'ocupada':       { text: 'Ocupada',          class: 'ocupada',       color: '#8e8e93' },
    'limpieza':      { text: 'En limpieza',      class: 'limpieza',      color: '#007aff' },
    'mantenimiento': { text: 'En mantenimiento', class: 'mantenimiento', color: '#ff9500' },
};

/**
 * Parsea una fecha 'YYYY-MM-DD' o 'YYYY-MM-DD HH:MM:SS' como hora local
 * evitando el desfase UTC que causa que new Date('2026-05-26') muestre 25/5 en UTC-4.
 */
function fmtDate(str) {
    if (!str) return '—';
    const base = str.substring(0, 10); // 'YYYY-MM-DD'
    const d = new Date(base + 'T12:00:00');
    return d.toLocaleDateString('es-BO', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

function openModal(room, mantenimiento = null, huesped = null) {
    // Dynamic status badge inside clean header
    const sm = statusMap[room.estado] || statusMap['disponible'];
    const badge = document.getElementById('m-estado-badge');
    badge.className = 'status-pill-clean ' + sm.class;
    badge.innerHTML = `<span class="w-1.5 h-1.5 rounded-full" style="background:${sm.color}"></span> ${sm.text}`;

    document.getElementById('m-numero').textContent = 'Habitación ' + room.numero;
    document.getElementById('m-tipo').textContent   = room.tipo;
    document.getElementById('m-precio').textContent = parseFloat(room.precio_dia).toFixed(2);
    document.getElementById('m-id').value           = room.id;

    let dbTxt = 'BD: "' + (room.estado_db || 'vacío') + '"';
    if (room.tiene_ocupacion_activa && room.estado !== 'ocupada') dbTxt += ' • DEBERÍA SER: OCUPADA';
    if (!room.tiene_ocupacion_activa && room.estado === 'ocupada') dbTxt += ' • DEBERÍA SER: DISPONIBLE';
    document.getElementById('m-db-status').textContent = dbTxt;

    // Mantenimiento info
    const mantEl = document.getElementById('m-mantenimiento-info');
    if (mantenimiento && room.estado === 'mantenimiento') {
        mantEl.classList.remove('hidden');
        mantEl.innerHTML = `
            <div class="maintenance-card-clean">
                <div class="flex items-center gap-2.5 mb-2.5">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-orange-500/10 text-orange-500 flex-shrink-0">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-semibold text-sm text-gray-900 dark:text-white">${mantenimiento.titulo}</p>
                        <p class="text-[9px] text-orange-600 dark:text-orange-400 font-semibold tracking-wider uppercase">Orden de Mantenimiento</p>
                    </div>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed">${mantenimiento.descripcion}</p>
                <div class="flex items-center justify-between mt-4 pt-3 border-t border-orange-500/10 text-xs text-gray-500 dark:text-gray-400">
                    <span>Prioridad: <strong class="font-semibold text-gray-700 dark:text-gray-200">${mantenimiento.prioridad}</strong></span>
                    <span>Desde: <strong class="font-semibold text-gray-700 dark:text-gray-200">${fmtDate(mantenimiento.fecha_inicio)}</strong></span>
                </div>
            </div>`;
    } else {
        mantEl.classList.add('hidden');
    }

    // Huésped info & cambiar estado form visibility
    const huespedEl = document.getElementById('m-huesped-info');
    const formEl    = document.getElementById('form-cambiar-estado');

    if (huesped && room.estado === 'ocupada') {
        huespedEl.classList.remove('hidden');
        formEl.classList.add('hidden');

        if (huesped.multiples) {
            let html = `<div>
                <p class="text-[10px] uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-3 font-semibold">${huesped.huespedes.length} Huéspedes activos</p>
                <div class="space-y-3">`;
            huesped.huespedes.forEach((h, i) => {
                const initial = h.nombres_apellidos.charAt(0).toUpperCase();
                html += `<div class="rounded-2xl p-4 bg-gray-50/50 border border-gray-100 dark:bg-white/[0.02] dark:border-white/[0.05]">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="guest-avatar-clean">${initial}</div>
                        <div>
                            <p class="font-semibold text-sm text-gray-900 dark:text-white">${h.nombres_apellidos}</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500">CI: ${h.ci_pasaporte}</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3 mt-3 pt-3 border-t border-gray-100 dark:border-white/[0.05]">
                        <div class="info-pair"><span class="info-label">Edad</span><span class="info-value">${h.edad} años</span></div>
                        <div class="info-pair"><span class="info-label">Nacionalidad</span><span class="info-value">${h.nacionalidad}</span></div>
                    </div>
                    <div class="flex gap-2 mt-4">
                        <div class="date-card-clean">
                            <div class="w-7 h-7 rounded-lg flex items-center justify-center bg-green-500/10 text-green-600 flex-shrink-0">
                                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div class="info-pair">
                                <span class="info-label">Check-in</span>
                                <span class="info-value text-xs font-semibold">${fmtDate(h.fecha_ingreso)}</span>
                            </div>
                        </div>
                        <div class="date-card-clean">
                            <div class="w-7 h-7 rounded-lg flex items-center justify-center bg-red-500/10 text-red-600 flex-shrink-0">
                                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                </svg>
                            </div>
                            <div class="info-pair">
                                <span class="info-label">Check-out</span>
                                <span class="info-value text-xs font-semibold">${fmtDate(h.fecha_salida_estimada)}</span>
                            </div>
                        </div>
                    </div>
                </div>`;
            });
            html += '</div></div>';
            huespedEl.innerHTML = html;
        } else {
            const h = huesped.huespedes[0];
            const initial = h.nombres_apellidos.charAt(0).toUpperCase();
            huespedEl.innerHTML = `
                <div>
                    <!-- Guest identity -->
                    <div class="flex items-center gap-3.5 mb-5">
                        <div class="guest-avatar-clean">${initial}</div>
                        <div class="flex-1 min-w-0">
                            <p class="font-bold text-base text-gray-900 dark:text-white truncate">${h.nombres_apellidos}</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">CI / Pasaporte: <span class="font-medium text-gray-600 dark:text-gray-300">${h.ci_pasaporte}</span></p>
                        </div>
                        <div class="text-right flex-shrink-0">
                            <div class="text-sm font-bold text-gray-900 dark:text-white font-mono">${h.nro_dias} días</div>
                            <div class="text-[9px] uppercase tracking-wider text-gray-400 font-semibold">estadía</div>
                        </div>
                    </div>

                    <!-- Stats grid -->
                    <div class="grid grid-cols-3 gap-2.5 mb-5">
                        <div class="rounded-xl p-3 bg-gray-50/50 border border-gray-100 dark:bg-white/[0.02] dark:border-white/[0.05]">
                            <div class="info-label">Género</div>
                            <div class="info-value text-xs mt-1 text-gray-800 dark:text-gray-200 font-semibold">${h.genero === 'M' ? 'Masculino' : 'Femenino'}</div>
                        </div>
                        <div class="rounded-xl p-3 bg-gray-50/50 border border-gray-100 dark:bg-white/[0.02] dark:border-white/[0.05]">
                            <div class="info-label">Edad</div>
                            <div class="info-value text-xs mt-1 text-gray-800 dark:text-gray-200 font-semibold">${h.edad} años</div>
                        </div>
                        <div class="rounded-xl p-3 bg-gray-50/50 border border-gray-100 dark:bg-white/[0.02] dark:border-white/[0.05]">
                            <div class="info-label">Nac.</div>
                            <div class="info-value text-xs mt-1 text-gray-800 dark:text-gray-200 font-semibold truncate" title="${h.nacionalidad}">${h.nacionalidad}</div>
                        </div>
                    </div>

                    <!-- Date cards -->
                    <div class="flex gap-2">
                        <div class="date-card-clean">
                            <div class="w-7 h-7 rounded-lg flex items-center justify-center bg-green-500/10 text-green-600 flex-shrink-0">
                                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div class="info-pair">
                                <span class="info-label">Check-in</span>
                                <span class="info-value text-sm font-semibold">${fmtDate(h.fecha_ingreso)}</span>
                            </div>
                        </div>
                        <div class="date-card-clean">
                            <div class="w-7 h-7 rounded-lg flex items-center justify-center bg-red-500/10 text-red-600 flex-shrink-0">
                                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                </svg>
                            </div>
                            <div class="info-pair">
                                <span class="info-label">Check-out</span>
                                <span class="info-value text-sm font-semibold">${fmtDate(h.fecha_salida_estimada)}</span>
                            </div>
                        </div>
                    </div>
                </div>`;
        }
    } else {
        huespedEl.classList.add('hidden');
        formEl.classList.remove('hidden');
    }

    document.getElementById('modal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('modal').classList.add('hidden');
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

// Animate stat fills on load
window.addEventListener('load', function () {
    document.querySelectorAll('.stat-fill[data-w]').forEach(function (el) {
        setTimeout(function () { el.style.width = el.getAttribute('data-w') + '%'; }, 200);
    });
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
