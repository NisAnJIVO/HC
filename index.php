<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/models/RegistroOcupacion.php';
require_once __DIR__ . '/models/Habitacion.php';

$page_title = 'Inicio';

$registroModel = new RegistroOcupacion();
$habitacionModel = new Habitacion();

// Verificador de estados al ejecutar el dashboard
$registroModel->verificarSalidasAutomaticas();

$ocupaciones_activas = $registroModel->obtenerActivos();
$habitaciones = $habitacionModel->obtenerTodas();

$total_habitaciones = count($habitaciones);

// Habitaciones que realmente tienen un huésped activo ahora mismo
$habitaciones_ocupadas = count(array_unique(array_column($ocupaciones_activas, 'nro_pieza')));

// Las demás siguen usando el estado de la tabla habitaciones
$habitaciones_limpieza = count(array_filter($habitaciones, function($h) {
    return $h['estado'] == 'limpieza';
}));
$habitaciones_mantenimiento = count(array_filter($habitaciones, function($h) {
    return $h['estado'] == 'mantenimiento';
}));

// Disponibles = rest (no ocupadas, no en limpieza, no en mantenimiento)
$habitaciones_disponibles = $total_habitaciones - $habitaciones_ocupadas - $habitaciones_limpieza - $habitaciones_mantenimiento;

include __DIR__ . '/includes/header.php';
?>

<style>
/* Dashboard v3 — AI / Apple / Airbnb aesthetic */
body { background: #f4f4f5; }
.dark body { background: #080808; }

/* Greeting */
.dash-eyebrow {
    font-size: 11px; letter-spacing: .12em;
    text-transform: uppercase; color: #aaa; font-weight: 500;
    margin-bottom: 8px;
}
.dash-h1 {
    font-size: clamp(1.75rem, 3.5vw, 2.4rem);
    font-weight: 650; letter-spacing: -.04em;
    color: #111; line-height: 1.05;
}
.dark .dash-h1 { color: #f2f2f2; }
.dash-clock {
    font-size: clamp(1.6rem, 3vw, 2.5rem);
    font-weight: 300; letter-spacing: -.05em;
    font-variant-numeric: tabular-nums; color: #111; line-height: 1;
}
.dark .dash-clock { color: #efefef; }

/* Metric cards */
.metric-card {
    background: #fff; border: 1px solid rgba(0,0,0,.07);
    border-radius: 18px; padding: 1.5rem;
    transition: transform .2s ease, box-shadow .2s ease;
}
.metric-card:hover { transform: translateY(-3px); box-shadow: 0 16px 48px rgba(0,0,0,.1); }
.dark .metric-card { background: #161616; border-color: rgba(255,255,255,.07); }
.dark .metric-card:hover { box-shadow: 0 16px 48px rgba(0,0,0,.55); }

.m-eye {
    display: flex; align-items: center; gap: 6px;
    font-size: 10px; font-weight: 600;
    letter-spacing: .1em; text-transform: uppercase;
    color: #999; margin-bottom: 14px;
}
.m-dot { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }
.m-dot-occ   { background: #111; }
.m-dot-avail { background: #22c55e; }
.m-dot-clean { background: #3b82f6; }
.m-dot-maint { background: #f59e0b; }
.dark .m-dot-occ { background: #e8e8e8; }

.m-num {
    font-size: 3.75rem; font-weight: 700;
    letter-spacing: -.06em; line-height: 1;
    color: #111; margin-bottom: 1.1rem;
    font-variant-numeric: tabular-nums;
}
.dark .m-num { color: #f0f0f0; }

.m-track { height: 2px; background: rgba(0,0,0,.07); border-radius: 99px; overflow: hidden; }
.dark .m-track { background: rgba(255,255,255,.07); }
.m-fill {
    height: 100%; border-radius: 99px; width: 0;
    transition: width 1.3s cubic-bezier(.4,0,.2,1);
}
.m-fill-occ   { background: #111; }
.m-fill-avail { background: #22c55e; }
.m-fill-clean { background: #3b82f6; }
.m-fill-maint { background: #f59e0b; }
.dark .m-fill-occ { background: #e8e8e8; }

.m-pct { text-align: right; font-size: 10px; color: #ccc; margin-top: 5px; font-variant-numeric: tabular-nums; }
.dark .m-pct { color: #444; }

/* Content cards */
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

/* Guest rows */
.g-row {
    display: grid; grid-template-columns: 38px 1fr auto;
    gap: 12px; align-items: center;
    padding: 11px 20px;
    border-bottom: 1px solid rgba(0,0,0,.04);
    transition: background .1s;
}
.dark .g-row { border-bottom-color: rgba(255,255,255,.04); }
.g-row:last-child { border-bottom: none; }
.g-row:hover { background: rgba(0,0,0,.018); }
.dark .g-row:hover { background: rgba(255,255,255,.022); }

.g-room {
    width: 38px; height: 38px;
    background: #111; color: #fff;
    border-radius: 10px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: 12px; font-weight: 600;
    letter-spacing: -.02em; font-variant-numeric: tabular-nums;
}
.dark .g-room { background: #efefef; color: #111; }

.g-name {
    font-size: 14px; font-weight: 500; color: #111;
    letter-spacing: -.01em; overflow: hidden;
    text-overflow: ellipsis; white-space: nowrap;
}
.dark .g-name { color: #e5e5e5; }
.g-sub { font-size: 11px; color: #999; margin-top: 1px; }

.g-stay { text-align: right; flex-shrink: 0; }
.g-days { font-size: 11px; font-weight: 600; color: #888; font-variant-numeric: tabular-nums; }
.g-bar {
    width: 58px; height: 3px; background: rgba(0,0,0,.08);
    border-radius: 99px; overflow: hidden; margin-top: 5px; margin-left: auto;
}
.dark .g-bar { background: rgba(255,255,255,.08); }
.g-bar-fill { height: 100%; background: #111; border-radius: 99px; }
.dark .g-bar-fill { background: #ddd; }

/* Occupancy ring */
.oc-body { padding: 1.25rem 1.5rem; display: flex; align-items: center; gap: 1.25rem; }
.oc-num { font-size: 2.25rem; font-weight: 700; letter-spacing: -.05em; color: #111; line-height: 1; font-variant-numeric: tabular-nums; }
.dark .oc-num { color: #f0f0f0; }
.oc-sub { font-size: 11px; color: #999; margin-top: 3px; }
.oc-legend { margin-top: 12px; display: flex; flex-direction: column; gap: 5px; }
.oc-leg { display: flex; align-items: center; gap: 6px; font-size: 11px; color: #888; }
.oc-leg-dot { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }

/* Room grid */
.rg-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(38px,1fr)); gap: 5px; padding: 1.1rem; }
.rg-chip {
    aspect-ratio: 1; border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: 10px; font-weight: 600;
    letter-spacing: -.02em; font-variant-numeric: tabular-nums;
    transition: transform .15s ease; cursor: default;
}
.rg-chip:hover { transform: scale(1.12); }
.rg-chip.ocupada       { background: #111; color: #fff; }
.rg-chip.disponible    { background: rgba(34,197,94,.1); color: #16a34a; border: 1px solid rgba(34,197,94,.18); }
.rg-chip.limpieza      { background: rgba(59,130,246,.1); color: #2563eb; border: 1px solid rgba(59,130,246,.15); }
.rg-chip.mantenimiento { background: rgba(245,158,11,.1); color: #b45309; border: 1px solid rgba(245,158,11,.15); }
.dark .rg-chip.ocupada       { background: #e8e8e8; color: #111; }
.dark .rg-chip.disponible    { background: rgba(34,197,94,.12); color: #4ade80; border-color: rgba(34,197,94,.2); }
.dark .rg-chip.limpieza      { background: rgba(59,130,246,.12); color: #60a5fa; border-color: rgba(59,130,246,.2); }
.dark .rg-chip.mantenimiento { background: rgba(245,158,11,.12); color: #fbbf24; border-color: rgba(245,158,11,.2); }

.rg-legend { display: flex; flex-wrap: wrap; gap: 10px; padding: 0 1.1rem 1.1rem; }
.rg-leg { display: flex; align-items: center; gap: 5px; font-size: 10px; color: #999; font-weight: 500; }
.rg-leg-dot { width: 7px; height: 7px; border-radius: 50%; }

/* Quick-access links */
.qa-link {
    display: flex; align-items: center; justify-content: space-between;
    padding: 12px 20px; border-bottom: 1px solid rgba(0,0,0,.05);
    transition: background .1s; text-decoration: none;
}
.dark .qa-link { border-bottom-color: rgba(255,255,255,.05); }
.qa-link:last-child { border-bottom: none; }
.qa-link:hover { background: rgba(0,0,0,.025); }
.dark .qa-link:hover { background: rgba(255,255,255,.03); }
.qa-link-inner { display: flex; align-items: center; gap: 12px; }
.qa-icon {
    width: 34px; height: 34px; border-radius: 9px;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.qa-name { font-size: 13px; font-weight: 500; color: #111; letter-spacing: -.01em; }
.dark .qa-name { color: #e0e0e0; }
.qa-desc { font-size: 11px; color: #999; margin-top: 1px; }
.qa-arrow { color: #ccc; flex-shrink: 0; }
.dark .qa-arrow { color: #444; }

/* Fade-up entrance */
@keyframes fadeUp {
    from { opacity: 0; transform: translateY(14px); }
    to   { opacity: 1; transform: translateY(0); }
}
.f-up { opacity: 0; animation: fadeUp .5s ease-out forwards; }
.f-up:nth-child(1) { animation-delay:   0ms; }
.f-up:nth-child(2) { animation-delay:  60ms; }
.f-up:nth-child(3) { animation-delay: 120ms; }
.f-up:nth-child(4) { animation-delay: 180ms; }
</style>

<!-- Greeting bar -->
<div class="flex items-end justify-between mb-10">
    <div>
        <p class="dash-eyebrow" id="dash-date">Cargando…</p>
        <h1 class="dash-h1">
            <?php
            $hora    = (int)date('H');
            $saludo  = $hora < 12 ? 'Buenos días' : ($hora < 19 ? 'Buenas tardes' : 'Buenas noches');
            $rawName = $_SESSION['nombre_completo'] ?? $_SESSION['usuario'] ?? 'Usuario';
            $nombre  = htmlspecialchars(explode(' ', trim($rawName))[0]);
            echo $saludo . ', ' . $nombre . '.';
            ?>
        </h1>
    </div>
    <div class="text-right hidden sm:flex flex-col items-end gap-1">
        <div class="dash-clock" id="dash-clock">--:--:--</div>
        <p class="text-[10px] tracking-widest uppercase text-gray-400">Bolivia · BOT</p>
    </div>
</div>

<!-- Metric cards -->
<?php
$total_h = max(1, $total_habitaciones);
$metrics = [
    ['label' => 'Ocupadas',      'val' => $habitaciones_ocupadas,     'dot' => 'm-dot-occ',   'fill' => 'm-fill-occ'],
    ['label' => 'Disponibles',   'val' => $habitaciones_disponibles,  'dot' => 'm-dot-avail', 'fill' => 'm-fill-avail'],
    ['label' => 'En limpieza',   'val' => $habitaciones_limpieza,     'dot' => 'm-dot-clean', 'fill' => 'm-fill-clean'],
    ['label' => 'Mantenimiento', 'val' => $habitaciones_mantenimiento,'dot' => 'm-dot-maint', 'fill' => 'm-fill-maint'],
];
?>
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-9">
    <?php foreach ($metrics as $m):
        $pct = round($m['val'] / $total_h * 100);
    ?>
    <div class="metric-card f-up">
        <div class="m-eye">
            <span class="m-dot <?= $m['dot'] ?>"></span>
            <?= $m['label'] ?>
        </div>
        <div class="m-num"><?= $m['val'] ?></div>
        <div class="m-track">
            <div class="m-fill <?= $m['fill'] ?>" data-w="<?= $pct ?>"></div>
        </div>
        <div class="m-pct"><?= $pct ?>% del total</div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Main grid layout -->
<div class="grid lg:grid-cols-3 gap-5">

    <!-- Left column: split occupancy & status map grid + quick access bottom -->
    <div class="lg:col-span-2 flex flex-col gap-4">

        <!-- Split 50/50 Ocupacion & Mapa -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

            <!-- Occupancy ring -->
            <div class="c-card flex flex-col h-full">
                <div class="c-head"><span class="c-title">Ocupación general</span></div>
                <?php
                $occ_pct  = round($habitaciones_ocupadas / $total_h * 100);
                $R        = 34;
                $circ     = round(2 * M_PI * $R, 2);
                $dash_len = round($circ * $occ_pct / 100, 2);
                $gap_len  = round($circ - $dash_len, 2);
                $offset   = round($circ * 0.25, 2);
                ?>
                <div class="oc-body flex-1 flex items-center gap-5">
                    <svg width="90" height="90" viewBox="0 0 90 90" style="flex-shrink:0">
                        <circle id="ring-bg" cx="45" cy="45" r="<?= $R ?>"
                                fill="none" stroke="rgba(0,0,0,.07)" stroke-width="7"/>
                        <circle id="ring-arc" cx="45" cy="45" r="<?= $R ?>"
                                fill="none" stroke="#111" stroke-width="7"
                                stroke-dasharray="<?= $dash_len . ' ' . $gap_len ?>"
                                stroke-dashoffset="<?= $offset ?>"
                                stroke-linecap="round"/>
                        <text x="45" y="45" text-anchor="middle" dominant-baseline="central"
                              font-size="14" font-weight="700" font-family="Inter,sans-serif"
                              id="ring-txt" fill="#111"><?= $occ_pct ?>%</text>
                    </svg>
                    <div>
                        <div class="oc-num"><?= $habitaciones_ocupadas ?></div>
                        <div class="oc-sub">de <?= $total_habitaciones ?> habitaciones</div>
                        <div class="oc-legend">
                            <div class="oc-leg"><span class="oc-leg-dot" id="leg-occ" style="background:#111"></span><?= $habitaciones_ocupadas ?> ocupadas</div>
                            <div class="oc-leg"><span class="oc-leg-dot" style="background:#22c55e"></span><?= $habitaciones_disponibles ?> disponibles</div>
                            <?php if ($habitaciones_limpieza > 0): ?>
                            <div class="oc-leg"><span class="oc-leg-dot" style="background:#3b82f6"></span><?= $habitaciones_limpieza ?> limpieza</div>
                            <?php endif; ?>
                            <?php if ($habitaciones_mantenimiento > 0): ?>
                            <div class="oc-leg"><span class="oc-leg-dot" style="background:#f59e0b"></span><?= $habitaciones_mantenimiento ?> mantenimiento</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Room status map -->
            <div class="c-card flex flex-col h-full">
                <div class="c-head">
                    <span class="c-title">Mapa de habitaciones</span>
                    <span class="c-badge"><?= $total_habitaciones ?></span>
                </div>
                <div class="rg-grid flex-1">
                    <?php foreach ($habitaciones as $h): ?>
                    <div class="rg-chip <?= htmlspecialchars($h['estado']) ?>"
                         title="Hab. <?= htmlspecialchars($h['numero']) ?> — <?= ucfirst(htmlspecialchars($h['estado'])) ?>">
                        <?= htmlspecialchars($h['numero']) ?>
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

        </div><!-- /split grid -->

        <!-- Quick access -->
        <div class="c-card">
            <div class="c-head"><span class="c-title">Accesos rápidos</span></div>
            <?php
            $qaLinks = [
                [
                    'href'  => BASE_PATH . '/views/huespedes/activos.php',
                    'label' => 'Huéspedes activos',
                    'sub'   => 'Ver estadías en curso',
                    'bg'    => '#111',
                    'icon'  => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0',
                    'admin' => false,
                ],
                [
                    'href'  => BASE_PATH . '/views/finanzas/ingresos.php',
                    'label' => 'Ingresos',
                    'sub'   => 'Registrar pagos extras',
                    'bg'    => '#16a34a',
                    'icon'  => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                    'admin' => true,
                ],
                [
                    'href'  => BASE_PATH . '/views/habitaciones/estado.php',
                    'label' => 'Estado habitaciones',
                    'sub'   => 'Ver mapa de habitaciones',
                    'bg'    => '#2563eb',
                    'icon'  => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
                    'admin' => false,
                ],
                [
                    'href'  => BASE_PATH . '/views/reportes/parte_diario.php',
                    'label' => 'Parte diario',
                    'sub'   => 'Planilla del día',
                    'bg'    => '#7c3aed',
                    'icon'  => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                    'admin' => false,
                ],
            ];
            foreach ($qaLinks as $qa):
                if ($qa['admin'] && !esAdmin()) continue;
            ?>
            <a href="<?= $qa['href'] ?>" class="qa-link">
                <div class="qa-link-inner">
                    <div class="qa-icon" style="background:<?= $qa['bg'] ?>">
                        <svg width="16" height="16" fill="none" stroke="#fff" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="<?= $qa['icon'] ?>"/>
                        </svg>
                    </div>
                    <div>
                        <div class="qa-name"><?= $qa['label'] ?></div>
                        <div class="qa-desc"><?= $qa['sub'] ?></div>
                    </div>
                </div>
                <svg class="qa-arrow" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
            <?php endforeach; ?>
            <?php if (esAdmin()): ?>
            <a href="<?= BASE_PATH ?>/views/huespedes/nuevo.php" class="qa-link">
                <div class="qa-link-inner">
                    <div class="qa-icon" style="background:#111">
                        <svg width="16" height="16" fill="none" stroke="#fff" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="qa-name">Nuevo huésped</div>
                        <div class="qa-desc">Registrar check-in</div>
                    </div>
                </div>
                <svg class="qa-arrow" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
            <?php endif; ?>
        </div>

    </div><!-- /left-side -->

    <!-- Right column: Active guest table -->
    <div class="c-card">
        <div class="c-head">
            <span class="c-title">Huéspedes activos</span>
            <span class="c-badge"><?= count($ocupaciones_activas) ?></span>
        </div>

        <?php if (empty($ocupaciones_activas)): ?>
        <div class="flex flex-col items-center justify-center py-12 gap-3 text-gray-400">
            <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.25" viewBox="0 0 24 24" class="text-gray-300 dark:text-gray-700">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0"/>
            </svg>
            <p class="text-sm">Sin huéspedes activos</p>
        </div>
        <?php else: ?>
        <div>
            <?php foreach ($ocupaciones_activas as $ocu):
                $dias_elap  = max(0, (int)floor((time() - strtotime($ocu['fecha_ingreso'])) / 86400));
                $total_dias = max(1, (int)($ocu['nro_dias'] ?? 1));
                $pct_stay   = min(100, (int)round($dias_elap / $total_dias * 100));
                if (!empty($ocu['fecha_salida_estimada'])) {
                    $salida = date('d/m', strtotime($ocu['fecha_salida_estimada']));
                } else {
                    $salida = date('d/m', strtotime($ocu['fecha_ingreso']) + $total_dias * 86400);
                }
            ?>
            <div class="g-row">
                <div class="g-room"><?= htmlspecialchars($ocu['nro_pieza']) ?></div>
                <div class="min-w-0">
                    <div class="g-name"><?= htmlspecialchars($ocu['nombres_apellidos']) ?></div>
                    <div class="g-sub">Ingresó <?= date('d M', strtotime($ocu['fecha_ingreso'])) ?> · sale <?= $salida ?></div>
                </div>
                <div class="g-stay">
                    <div class="g-days"><?= $dias_elap ?>/<?= $total_dias ?>d</div>
                    <div class="g-bar"><div class="g-bar-fill" style="width:<?= $pct_stay ?>%"></div></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div><!-- /guest-table -->

</div><!-- /grid -->

<script>
// Live clock
(function tick() {
    var n = new Date(), p = function(v){ return String(v).padStart(2,'0'); };
    var el = document.getElementById('dash-clock');
    if (el) el.textContent = p(n.getHours()) + ':' + p(n.getMinutes()) + ':' + p(n.getSeconds());
    setTimeout(tick, 1000);
})();

// Date label
(function(){
    var D = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
    var M = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
    var n = new Date(), el = document.getElementById('dash-date');
    if (el) el.textContent = D[n.getDay()] + ', ' + n.getDate() + ' de ' + M[n.getMonth()] + ' de ' + n.getFullYear();
})();

// Animate metric bar fills on load
window.addEventListener('load', function(){
    document.querySelectorAll('.m-fill[data-w]').forEach(function(el){
        setTimeout(function(){ el.style.width = el.getAttribute('data-w') + '%'; }, 150);
    });
});

// SVG ring dark-mode sync
(function(){
    var arc = document.getElementById('ring-arc'),
        bg  = document.getElementById('ring-bg'),
        txt = document.getElementById('ring-txt'),
        leg = document.getElementById('leg-occ');
    function sync(){
        var dk = document.documentElement.classList.contains('dark');
        if (arc) arc.setAttribute('stroke', dk ? '#e0e0e0' : '#111');
        if (bg)  bg.setAttribute('stroke',  dk ? 'rgba(255,255,255,.07)' : 'rgba(0,0,0,.07)');
        if (txt) txt.setAttribute('fill',   dk ? '#f0f0f0' : '#111');
        if (leg) leg.style.background = dk ? '#e0e0e0' : '#111';
    }
    sync();
    new MutationObserver(sync).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
})();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
