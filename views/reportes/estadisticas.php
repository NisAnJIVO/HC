<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/Finanzas.php';
require_once __DIR__ . '/../../models/RegistroOcupacion.php';
require_once __DIR__ . '/../../models/Habitacion.php';

$page_title = 'Estadísticas y Análisis';

// Obtener filtro de período (por defecto: últimos 7 días)
$periodo = isset($_GET['periodo']) ? $_GET['periodo'] : '7dias';

// Calcular fechas según el período
$fecha_fin = date('Y-m-d');
switch ($periodo) {
    case '7dias':
        $fecha_inicio = date('Y-m-d', strtotime('-7 days'));
        $titulo_periodo = 'Últimos 7 Días';
        break;
    case '30dias':
        $fecha_inicio = date('Y-m-d', strtotime('-30 days'));
        $titulo_periodo = 'Últimos 30 Días';
        break;
    case 'mes_actual':
        $fecha_inicio = date('Y-m-01');
        $titulo_periodo = 'Mes Actual';
        break;
    case 'mes_anterior':
        $fecha_inicio = date('Y-m-01', strtotime('first day of last month'));
        $fecha_fin = date('Y-m-t', strtotime('last day of last month'));
        $titulo_periodo = 'Mes Anterior';
        break;
    case 'anio':
        $fecha_inicio = date('Y-01-01');
        $titulo_periodo = 'Año ' . date('Y');
        break;
    default:
        $fecha_inicio = date('Y-m-d', strtotime('-7 days'));
        $titulo_periodo = 'Últimos 7 Días';
}

$conn = getConnection();
$finanzasModel = new Finanzas();
$registroModel = new RegistroOcupacion();
$habitacionModel = new Habitacion();

// 1. INGRESOS POR DÍA (Efectivo vs QR)
$sql_ingresos = "SELECT DATE(fecha) as fecha, 
                        SUM(CASE WHEN metodo_pago = 'efectivo' THEN monto ELSE 0 END) as efectivo,
                        SUM(CASE WHEN metodo_pago = 'qr' THEN monto ELSE 0 END) as qr
                 FROM ingresos 
                 WHERE fecha BETWEEN :inicio AND :fin
                 GROUP BY DATE(fecha)
                 ORDER BY fecha ASC";
$stmt = $conn->prepare($sql_ingresos);
$stmt->execute([':inicio' => $fecha_inicio, ':fin' => $fecha_fin]);
$ingresos_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. EGRESOS POR DÍA
$sql_egresos = "SELECT DATE(fecha) as fecha, SUM(monto) as total
                FROM egresos 
                WHERE fecha BETWEEN :inicio AND :fin
                GROUP BY DATE(fecha)
                ORDER BY fecha ASC";
$stmt = $conn->prepare($sql_egresos);
$stmt->execute([':inicio' => $fecha_inicio, ':fin' => $fecha_fin]);
$egresos_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. HUÉSPEDES POR DÍA (Ingresantes)
$sql_huespedes = "SELECT DATE(fecha_ingreso) as fecha, COUNT(DISTINCT huesped_id) as cantidad
                  FROM registro_ocupacion 
                  WHERE fecha_ingreso BETWEEN :inicio AND :fin
                  GROUP BY DATE(fecha_ingreso)
                  ORDER BY fecha ASC";
$stmt = $conn->prepare($sql_huespedes);
$stmt->execute([':inicio' => $fecha_inicio, ':fin' => $fecha_fin]);
$huespedes_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 4. OCUPACIÓN DE HABITACIONES (Promedio por día)
$total_habitaciones = count($habitacionModel->obtenerTodas());
$sql_ocupacion = "SELECT DATE(ro.fecha_ingreso) as fecha, 
                         COUNT(DISTINCT ro.habitacion_id) as ocupadas
                  FROM registro_ocupacion ro
                  WHERE ro.fecha_ingreso BETWEEN :inicio AND :fin
                  AND ro.estado = 'activo'
                  GROUP BY DATE(ro.fecha_ingreso)
                  ORDER BY fecha ASC";
$stmt = $conn->prepare($sql_ocupacion);
$stmt->execute([':inicio' => $fecha_inicio, ':fin' => $fecha_fin]);
$ocupacion_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Convertir datos PHP a JSON para JavaScript
$ingresos_json = json_encode($ingresos_data);
$egresos_json = json_encode($egresos_data);
$huespedes_json = json_encode($huespedes_data);
$ocupacion_json = json_encode($ocupacion_data);

include __DIR__ . '/../../includes/header.php';
?>

<!-- Chart.js UMD CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<style>
/* ═══════════════════════════════════════════════
   Apple Premium Statistics Aesthetic
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
    border-radius: 20px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.012);
    padding: 24px;
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.apple-card:hover {
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.024);
}

.dark .apple-card {
    background: #161616;
    border-color: rgba(255, 255, 255, 0.06);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
}

/* Premium Segmented Pill Selector */
.pill-tab {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 8px 16px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 600;
    color: #86868b;
    background: rgba(0, 0, 0, 0.03);
    border: 1px solid transparent;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

.pill-tab:hover {
    background: rgba(0, 0, 0, 0.05);
    color: #1d1d1f;
}

.pill-tab.active {
    background: #ffffff;
    color: #1d1d1f;
    border-color: rgba(0, 0, 0, 0.04);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

.dark .pill-tab {
    color: #aeaeb2;
    background: rgba(255, 255, 255, 0.04);
}

.dark .pill-tab:hover {
    background: rgba(255, 255, 255, 0.07);
    color: #ffffff;
}

.dark .pill-tab.active {
    background: #2c2c2e;
    color: #ffffff;
    border-color: rgba(255, 255, 255, 0.04);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
}

/* Quick Metrics styling */
.metric-box {
    background: #ffffff;
    border: 1px solid rgba(0, 0, 0, 0.05);
    border-radius: 16px;
    padding: 20px;
    transition: all 0.2s ease;
}

.dark .metric-box {
    background: rgba(255, 255, 255, 0.03);
    border-color: rgba(255, 255, 255, 0.05);
}

.metric-num {
    font-size: 24px;
    font-weight: 800;
    letter-spacing: -0.04em;
    color: #1d1d1f;
    margin-top: 4px;
    font-variant-numeric: tabular-nums;
}

.dark .metric-num {
    color: #ffffff;
}

.metric-lbl {
    font-size: 10px;
    font-weight: 650;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #86868b;
}

.dark .metric-lbl {
    color: #aeaeb2;
}
</style>

<div class="container mx-auto px-4 py-8">
    
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-extrabold tracking-tight text-gray-900 dark:text-white mb-1">Estadísticas y Análisis</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 font-medium">
            <?php echo $titulo_periodo; ?> · <?php echo date('d/m/Y', strtotime($fecha_inicio)); ?> al <?php echo date('d/m/Y', strtotime($fecha_fin)); ?>
        </p>
    </div>

    <!-- Segmented Tab Selector -->
    <div class="mb-8 flex flex-wrap gap-2">
        <a href="?periodo=7dias" class="pill-tab <?php echo $periodo === '7dias' ? 'active' : ''; ?>">
            7 días
        </a>
        <a href="?periodo=30dias" class="pill-tab <?php echo $periodo === '30dias' ? 'active' : ''; ?>">
            30 días
        </a>
        <a href="?periodo=mes_actual" class="pill-tab <?php echo $periodo === 'mes_actual' ? 'active' : ''; ?>">
            Mes actual
        </a>
        <a href="?periodo=mes_anterior" class="pill-tab <?php echo $periodo === 'mes_anterior' ? 'active' : ''; ?>">
            Mes anterior
        </a>
        <a href="?periodo=anio" class="pill-tab <?php echo $periodo === 'anio' ? 'active' : ''; ?>">
            Año completo
        </a>
    </div>

    <!-- Quick Metrics Row -->
    <?php
    $total_ingresos = array_sum(array_column($ingresos_data, 'efectivo')) + array_sum(array_column($ingresos_data, 'qr'));
    $total_egresos = array_sum(array_column($egresos_data, 'total'));
    $total_huespedes = array_sum(array_column($huespedes_data, 'cantidad'));
    $balance = $total_ingresos - $total_egresos;
    ?>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="metric-box">
            <div class="metric-lbl">Ingresos Totales</div>
            <div class="metric-num text-emerald-600 dark:text-emerald-450">Bs. <?php echo number_format($total_ingresos, 2); ?></div>
        </div>
        <div class="metric-box">
            <div class="metric-lbl">Egresos Totales</div>
            <div class="metric-num text-red-650 dark:text-red-400">Bs. <?php echo number_format($total_egresos, 2); ?></div>
        </div>
        <div class="metric-box">
            <div class="metric-lbl">Balance Neto</div>
            <div class="metric-num <?php echo $balance >= 0 ? 'text-gray-900 dark:text-white' : 'text-red-650 dark:text-red-400'; ?>">
                Bs. <?php echo number_format($balance, 2); ?>
            </div>
        </div>
        <div class="metric-box">
            <div class="metric-lbl">Nuevos Huéspedes</div>
            <div class="metric-num text-blue-600 dark:text-blue-450"><?php echo $total_huespedes; ?></div>
        </div>
    </div>

    <!-- Charts Grid Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <!-- 1. Ingresos: Efectivo vs QR -->
        <div class="apple-card">
            <div class="pb-3 mb-4 border-b border-gray-50 dark:border-gray-800">
                <h3 class="text-sm font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Ingresos: Efectivo vs QR</h3>
            </div>
            <div class="relative w-full h-[250px]">
                <canvas id="chartIngresos"></canvas>
            </div>
        </div>

        <!-- 2. Check-ins por Día -->
        <div class="apple-card">
            <div class="pb-3 mb-4 border-b border-gray-50 dark:border-gray-800">
                <h3 class="text-sm font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Flujo de Huéspedes (Check-ins)</h3>
            </div>
            <div class="relative w-full h-[250px]">
                <canvas id="chartHuespedes"></canvas>
            </div>
        </div>

        <!-- 3. Ingresos vs Egresos -->
        <div class="apple-card">
            <div class="pb-3 mb-4 border-b border-gray-50 dark:border-gray-800">
                <h3 class="text-sm font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Caja: Ingresos vs Egresos</h3>
            </div>
            <div class="relative w-full h-[250px]">
                <canvas id="chartBalance"></canvas>
            </div>
        </div>

        <!-- 4. Ocupación de Habitaciones -->
        <div class="apple-card">
            <div class="pb-3 mb-4 border-b border-gray-50 dark:border-gray-800">
                <h3 class="text-sm font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Ocupación de Habitaciones</h3>
            </div>
            <div class="relative w-full h-[250px]">
                <canvas id="chartOcupacion"></canvas>
            </div>
        </div>
        
    </div>
</div>

<script>
// Datos estructurados de PHP
const ingresosData = <?php echo $ingresos_json; ?>;
const egresosData = <?php echo $egresos_json; ?>;
const huespedesData = <?php echo $huespedes_json; ?>;
const ocupacionData = <?php echo $ocupacion_json; ?>;
const totalHabitaciones = <?php echo $total_habitaciones; ?>;

// Configuración de Paleta de Colores Apple Premium
const isDark = document.documentElement.classList.contains('dark');
const chartColors = {
    blue: '#0071e3',
    blueFade: 'rgba(0, 113, 227, 0.15)',
    green: '#34c759',
    greenFade: 'rgba(52, 199, 89, 0.15)',
    red: '#ff3b30',
    redFade: 'rgba(255, 59, 48, 0.15)',
    purple: '#af52de',
    purpleFade: 'rgba(175, 82, 222, 0.15)',
    gray: isDark ? '#ffffff' : '#1d1d1f',
    grayFade: isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(29, 29, 31, 0.1)',
    grid: isDark ? 'rgba(255, 255, 255, 0.05)' : 'rgba(0, 0, 0, 0.04)',
    text: isDark ? '#aeaeb2' : '#86868b'
};

// Configuración Global de Chart.js
Chart.defaults.font.family = '-apple-system, BlinkMacSystemFont, "Inter", "Segoe UI", sans-serif';
Chart.defaults.font.size = 10;
Chart.defaults.color = chartColors.text;
Chart.defaults.plugins.tooltip.backgroundColor = isDark ? '#2c2c2e' : '#ffffff';
Chart.defaults.plugins.tooltip.titleColor = isDark ? '#ffffff' : '#1d1d1f';
Chart.defaults.plugins.tooltip.bodyColor = isDark ? '#aeaeb2' : '#86868b';
Chart.defaults.plugins.tooltip.borderColor = chartColors.grid;
Chart.defaults.plugins.tooltip.borderWidth = 1;
Chart.defaults.plugins.tooltip.cornerRadius = 8;
Chart.defaults.plugins.tooltip.padding = 10;

// Sincronizar colores dinámicamente si cambia el tema en caliente
const syncCharts = () => {
    const activeDark = document.documentElement.classList.contains('dark');
    const newTextColor = activeDark ? '#aeaeb2' : '#86868b';
    const newGridColor = activeDark ? 'rgba(255, 255, 255, 0.05)' : 'rgba(0, 0, 0, 0.04)';
    const newTooltipBg = activeDark ? '#2c2c2e' : '#ffffff';
    const newTooltipText = activeDark ? '#aeaeb2' : '#86868b';
    
    [chart1, chart2, chart3, chart4].forEach(chart => {
        if (!chart) return;
        chart.options.scales.x.ticks.color = newTextColor;
        chart.options.scales.y.ticks.color = newTextColor;
        chart.options.scales.x.grid.color = newGridColor;
        chart.options.scales.y.grid.color = newGridColor;
        chart.options.plugins.tooltip.backgroundColor = newTooltipBg;
        chart.options.plugins.tooltip.bodyColor = newTooltipText;
        chart.update();
    });
};
new MutationObserver(syncCharts).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });

// 1. Gráfico de Ingresos (Efectivo vs QR)
const ctxIngresos = document.getElementById('chartIngresos').getContext('2d');
const chart1 = new Chart(ctxIngresos, {
    type: 'line',
    data: {
        labels: ingresosData.map(d => new Date(d.fecha + 'T00:00:00').toLocaleDateString('es-BO', {day: 'numeric', month: 'short'})),
        datasets: [
            {
                label: 'Efectivo',
                data: ingresosData.map(d => parseFloat(d.efectivo)),
                borderColor: chartColors.green,
                backgroundColor: chartColors.greenFade,
                borderWidth: 2,
                tension: 0.4,
                fill: true,
                pointRadius: 2,
                pointHoverRadius: 5
            },
            {
                label: 'QR',
                data: ingresosData.map(d => parseFloat(d.qr)),
                borderColor: chartColors.blue,
                backgroundColor: chartColors.blueFade,
                borderWidth: 2,
                tension: 0.4,
                fill: true,
                pointRadius: 2,
                pointHoverRadius: 5
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                position: 'bottom',
                labels: { boxWidth: 8, boxHeight: 8, padding: 15, font: { weight: 'bold' } }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: { color: chartColors.grid },
                ticks: { color: chartColors.text }
            },
            x: {
                grid: { display: false },
                ticks: { color: chartColors.text }
            }
        }
    }
});

// 2. Gráfico de Check-ins (Huéspedes por Día)
const ctxHuespedes = document.getElementById('chartHuespedes').getContext('2d');
const chart2 = new Chart(ctxHuespedes, {
    type: 'bar',
    data: {
        labels: huespedesData.map(d => new Date(d.fecha + 'T00:00:00').toLocaleDateString('es-BO', {day: 'numeric', month: 'short'})),
        datasets: [{
            label: 'Check-ins',
            data: huespedesData.map(d => parseInt(d.cantidad)),
            backgroundColor: chartColors.purple,
            borderRadius: 6,
            barThickness: 16
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: { color: chartColors.grid },
                ticks: { stepSize: 1, color: chartColors.text }
            },
            x: {
                grid: { display: false },
                ticks: { color: chartColors.text }
            }
        }
    }
});

// 3. Gráfico Ingresos vs Egresos (Balance)
const todasFechas = [...new Set([...ingresosData.map(d => d.fecha), ...egresosData.map(d => d.fecha)])].sort();
const ctxBalance = document.getElementById('chartBalance').getContext('2d');
const chart3 = new Chart(ctxBalance, {
    type: 'line',
    data: {
        labels: todasFechas.map(f => new Date(f + 'T00:00:00').toLocaleDateString('es-BO', {day: 'numeric', month: 'short'})),
        datasets: [
            {
                label: 'Ingresos',
                data: todasFechas.map(fecha => {
                    const ing = ingresosData.find(d => d.fecha === fecha);
                    return ing ? parseFloat(ing.efectivo) + parseFloat(ing.qr) : 0;
                }),
                borderColor: chartColors.green,
                backgroundColor: chartColors.greenFade,
                borderWidth: 2,
                tension: 0.4,
                fill: true,
                pointRadius: 2
            },
            {
                label: 'Egresos',
                data: todasFechas.map(fecha => {
                    const egr = egresosData.find(d => d.fecha === fecha);
                    return egr ? parseFloat(egr.total) : 0;
                }),
                borderColor: chartColors.red,
                backgroundColor: chartColors.redFade,
                borderWidth: 2,
                tension: 0.4,
                fill: true,
                pointRadius: 2
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                position: 'bottom',
                labels: { boxWidth: 8, boxHeight: 8, padding: 15, font: { weight: 'bold' } }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: { color: chartColors.grid },
                ticks: { color: chartColors.text }
            },
            x: {
                grid: { display: false },
                ticks: { color: chartColors.text }
            }
        }
    }
});

// 4. Gráfico de Ocupación de Habitaciones (%)
const ctxOcupacion = document.getElementById('chartOcupacion').getContext('2d');
const chart4 = new Chart(ctxOcupacion, {
    type: 'line',
    data: {
        labels: ocupacionData.map(d => new Date(d.fecha + 'T00:00:00').toLocaleDateString('es-BO', {day: 'numeric', month: 'short'})),
        datasets: [{
            label: 'Ocupación',
            data: ocupacionData.map(d => ((parseInt(d.ocupadas) / totalHabitaciones) * 100).toFixed(1)),
            borderColor: chartColors.gray,
            backgroundColor: chartColors.grayFade,
            borderWidth: 2.5,
            tension: 0.4,
            fill: true,
            pointRadius: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: {
                beginAtZero: true,
                max: 100,
                grid: { color: chartColors.grid },
                ticks: { 
                    color: chartColors.text,
                    callback: function(value) { return value + '%'; }
                }
            },
            x: {
                grid: { display: false },
                ticks: { color: chartColors.text }
            }
        }
    }
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
