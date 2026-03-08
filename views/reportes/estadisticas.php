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

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white mb-1">Estadísticas y Análisis</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400"><?php echo $titulo_periodo; ?> · <?php echo date('d/m/Y', strtotime($fecha_inicio)); ?> - <?php echo date('d/m/Y', strtotime($fecha_fin)); ?></p>
    </div>

    <!-- Filtros de Período -->
    <div class="mb-6 flex flex-wrap gap-2">
        <a href="?periodo=7dias" class="px-3 py-1.5 text-xs font-medium rounded transition-colors <?php echo $periodo == '7dias' ? 'bg-gray-800 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600'; ?>">
            7 días
        </a>
        <a href="?periodo=30dias" class="px-3 py-1.5 text-xs font-medium rounded transition-colors <?php echo $periodo == '30dias' ? 'bg-gray-800 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600'; ?>">
            30 días
        </a>
        <a href="?periodo=mes_actual" class="px-3 py-1.5 text-xs font-medium rounded transition-colors <?php echo $periodo == 'mes_actual' ? 'bg-gray-800 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600'; ?>">
            Mes actual
        </a>
        <a href="?periodo=mes_anterior" class="px-3 py-1.5 text-xs font-medium rounded transition-colors <?php echo $periodo == 'mes_anterior' ? 'bg-gray-800 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600'; ?>">
            Mes anterior
        </a>
        <a href="?periodo=anio" class="px-3 py-1.5 text-xs font-medium rounded transition-colors <?php echo $periodo == 'anio' ? 'bg-gray-800 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600'; ?>">
            Año completo
        </a>
    </div>

    <!-- Resumen Rápido -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <?php
        $total_ingresos = array_sum(array_column($ingresos_data, 'efectivo')) + array_sum(array_column($ingresos_data, 'qr'));
        $total_egresos = array_sum(array_column($egresos_data, 'total'));
        $total_huespedes = array_sum(array_column($huespedes_data, 'cantidad'));
        $balance = $total_ingresos - $total_egresos;
        ?>
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Ingresos Totales</p>
            <p class="text-xl font-semibold text-gray-900 dark:text-white">Bs. <?php echo number_format($total_ingresos, 2); ?></p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Egresos Totales</p>
            <p class="text-xl font-semibold text-gray-900 dark:text-white">Bs. <?php echo number_format($total_egresos, 2); ?></p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Balance</p>
            <p class="text-xl font-semibold <?php echo $balance >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'; ?>">
                Bs. <?php echo number_format($balance, 2); ?>
            </p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Total Huéspedes</p>
            <p class="text-xl font-semibold text-gray-900 dark:text-white"><?php echo $total_huespedes; ?></p>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Gráfico 1: Ingresos Efectivo vs QR -->
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-5">
            <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-4">Ingresos: Efectivo vs QR</h3>
            <div class="relative" style="height: 250px;">
                <canvas id="chartIngresos"></canvas>
            </div>
        </div>

        <!-- Gráfico 2: Huéspedes por Día -->
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-5">
            <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-4">Check-ins por Día</h3>
            <div class="relative" style="height: 250px;">
                <canvas id="chartHuespedes"></canvas>
            </div>
        </div>

        <!-- Gráfico 3: Ingresos vs Egresos -->
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-5">
            <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-4">Ingresos vs Egresos</h3>
            <div class="relative" style="height: 250px;">
                <canvas id="chartBalance"></canvas>
            </div>
        </div>

        <!-- Gráfico 4: Ocupación de Habitaciones -->
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-5">
            <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-4">Ocupación de Habitaciones (%)</h3>
            <div class="relative" style="height: 250px;">
                <canvas id="chartOcupacion"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
// Datos desde PHP
const ingresosData = <?php echo $ingresos_json; ?>;
const egresosData = <?php echo $egresos_json; ?>;
const huespedesData = <?php echo $huespedes_json; ?>;
const ocupacionData = <?php echo $ocupacion_json; ?>;
const totalHabitaciones = <?php echo $total_habitaciones; ?>;

// Configuración de colores mate
const colors = {
    efectivo: '#6b7c3e',  // Verde olivo
    qr: '#5a6833',        // Verde olivo oscuro
    egresos: '#7f1d1d',   // Rojo oscuro
    ingresos: '#374151',  // Gris oscuro
    ocupacion: '#4b5563'  // Gris medio
};

// 1. Gráfico de Ingresos (Efectivo vs QR)
const ctxIngresos = document.getElementById('chartIngresos').getContext('2d');
new Chart(ctxIngresos, {
    type: 'line',
    data: {
        labels: ingresosData.map(d => new Date(d.fecha).toLocaleDateString('es-BO')),
        datasets: [
            {
                label: 'Efectivo',
                data: ingresosData.map(d => parseFloat(d.efectivo)),
                borderColor: colors.efectivo,
                backgroundColor: colors.efectivo + '20',
                borderWidth: 2,
                tension: 0.3,
                fill: true
            },
            {
                label: 'QR',
                data: ingresosData.map(d => parseFloat(d.qr)),
                borderColor: colors.qr,
                backgroundColor: colors.qr + '20',
                borderWidth: 2,
                tension: 0.3,
                fill: true
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
                labels: { font: { size: 11 }, color: '#6b7280' }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { color: '#9ca3af', font: { size: 10 } },
                grid: { color: '#e5e7eb50' }
            },
            x: {
                ticks: { color: '#9ca3af', font: { size: 10 } },
                grid: { display: false }
            }
        }
    }
});

// 2. Gráfico de Huéspedes
const ctxHuespedes = document.getElementById('chartHuespedes').getContext('2d');
new Chart(ctxHuespedes, {
    type: 'bar',
    data: {
        labels: huespedesData.map(d => new Date(d.fecha).toLocaleDateString('es-BO')),
        datasets: [{
            label: 'Huéspedes',
            data: huespedesData.map(d => parseInt(d.cantidad)),
            backgroundColor: colors.ingresos,
            borderRadius: 4
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
                ticks: { color: '#9ca3af', font: { size: 10 }, stepSize: 1 },
                grid: { color: '#e5e7eb50' }
            },
            x: {
                ticks: { color: '#9ca3af', font: { size: 10 } },
                grid: { display: false }
            }
        }
    }
});

// 3. Gráfico Ingresos vs Egresos
// Combinar fechas de ingresos y egresos
const todasFechas = [...new Set([...ingresosData.map(d => d.fecha), ...egresosData.map(d => d.fecha)])].sort();
const ctxBalance = document.getElementById('chartBalance').getContext('2d');
new Chart(ctxBalance, {
    type: 'line',
    data: {
        labels: todasFechas.map(f => new Date(f).toLocaleDateString('es-BO')),
        datasets: [
            {
                label: 'Ingresos',
                data: todasFechas.map(fecha => {
                    const ing = ingresosData.find(d => d.fecha === fecha);
                    return ing ? parseFloat(ing.efectivo) + parseFloat(ing.qr) : 0;
                }),
                borderColor: colors.efectivo,
                backgroundColor: colors.efectivo + '20',
                borderWidth: 2,
                tension: 0.3,
                fill: true
            },
            {
                label: 'Egresos',
                data: todasFechas.map(fecha => {
                    const egr = egresosData.find(d => d.fecha === fecha);
                    return egr ? parseFloat(egr.total) : 0;
                }),
                borderColor: colors.egresos,
                backgroundColor: colors.egresos + '20',
                borderWidth: 2,
                tension: 0.3,
                fill: true
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
                labels: { font: { size: 11 }, color: '#6b7280' }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { color: '#9ca3af', font: { size: 10 } },
                grid: { color: '#e5e7eb50' }
            },
            x: {
                ticks: { color: '#9ca3af', font: { size: 10 } },
                grid: { display: false }
            }
        }
    }
});

// 4. Gráfico de Ocupación
const ctxOcupacion = document.getElementById('chartOcupacion').getContext('2d');
new Chart(ctxOcupacion, {
    type: 'line',
    data: {
        labels: ocupacionData.map(d => new Date(d.fecha).toLocaleDateString('es-BO')),
        datasets: [{
            label: 'Ocupación (%)',
            data: ocupacionData.map(d => ((parseInt(d.ocupadas) / totalHabitaciones) * 100).toFixed(1)),
            borderColor: colors.ocupacion,
            backgroundColor: colors.ocupacion + '30',
            borderWidth: 2,
            tension: 0.3,
            fill: true
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
                ticks: { 
                    color: '#9ca3af', 
                    font: { size: 10 },
                    callback: function(value) { return value + '%'; }
                },
                grid: { color: '#e5e7eb50' }
            },
            x: {
                ticks: { color: '#9ca3af', font: { size: 10 } },
                grid: { display: false }
            }
        }
    }
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
