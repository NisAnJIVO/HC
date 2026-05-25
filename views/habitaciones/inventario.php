<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/InventarioHabitacion.php';
require_once __DIR__ . '/../../models/Habitacion.php';

$inventario = new InventarioHabitacion();
$habitacionModel = new Habitacion();

// Obtener todas las habitaciones para inicializar
$habitaciones = $habitacionModel->obtenerTodas();
$numeros = array_map(function($h) { return $h['numero']; }, $habitaciones);
$inventario->inicializarHabitaciones($numeros);

// Procesar actualización masiva
$mensaje = '';
$tipo_mensaje = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_masivo'])) {
    // Solo administradores pueden hacer actualización masiva
    if (!esAdmin()) {
        $mensaje = "No tienes suficientes permisos para realizar esta acción";
        $tipo_mensaje = "danger";
    } else {
        $habitaciones_data = $_POST['hab'] ?? [];
        $errores = 0;
        $actualizados = 0;
    
        foreach ($habitaciones_data as $numero => $datos) {
            $datos_completos = [
                'habitacion_numero' => $numero,
                'tipo' => 'habitacion',
                'cortinas' => intval($datos['cortinas'] ?? 0),
                'veladores' => intval($datos['veladores'] ?? 0),
                'roperos' => intval($datos['roperos'] ?? 0),
                'colgadores' => intval($datos['colgadores'] ?? 0),
                'basureros' => intval($datos['basureros'] ?? 0),
                'shampoo' => intval($datos['shampoo'] ?? 0),
                'jabon_liquido' => intval($datos['jabon_liquido'] ?? 0),
                'sillas' => intval($datos['sillas'] ?? 0),
                'sillones' => intval($datos['sillones'] ?? 0),
                'alfombras' => intval($datos['alfombras'] ?? 0),
                'camas' => intval($datos['camas'] ?? 0),
                'television' => intval($datos['television'] ?? 0),
                'lamparas' => intval($datos['lamparas'] ?? 0),
                'manteles' => 0,
                'cubrecamas' => 0,
                'sabanas_media_plaza' => 0,
                'sabanas_doble_plaza' => 0,
                'almohadas' => 0,
                'fundas' => 0,
                'frazadas' => 0,
                'toallas' => 0,
                'cortinas_almacen' => 0,
                'alfombras_almacen' => 0
            ];
            
            if ($inventario->guardar($datos_completos)) {
                $actualizados++;
            } else {
                $errores++;
            }
        }
    
        if ($errores === 0) {
            $mensaje = "Inventario masivo actualizado correctamente para {$actualizados} habitaciones.";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Se actualizaron {$actualizados} habitaciones, pero ocurrieron {$errores} errores.";
            $tipo_mensaje = "warning";
        }
    }
}

// Procesar actualización individual
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_inventario'])) {
    $habitacion_numero = $_POST['habitacion_numero'];
    $tipo = $_POST['tipo'] ?? 'habitacion';
    
    // Solo administradores pueden editar el almacén
    if ($tipo === 'almacen' && !esAdmin()) {
        $mensaje = "No tienes suficientes permisos para editar el almacén";
        $tipo_mensaje = "danger";
    } else {
        $datos = [
            'habitacion_numero' => $habitacion_numero,
            'tipo' => $_POST['tipo'] ?? 'habitacion',
            'cortinas' => intval($_POST['cortinas'] ?? 0),
            'veladores' => intval($_POST['veladores'] ?? 0),
            'roperos' => intval($_POST['roperos'] ?? 0),
            'colgadores' => intval($_POST['colgadores'] ?? 0),
            'basureros' => intval($_POST['basureros'] ?? 0),
            'shampoo' => intval($_POST['shampoo'] ?? 0),
            'jabon_liquido' => intval($_POST['jabon_liquido'] ?? 0),
            'sillas' => intval($_POST['sillas'] ?? 0),
            'sillones' => intval($_POST['sillones'] ?? 0),
            'alfombras' => intval($_POST['alfombras'] ?? 0),
            'camas' => intval($_POST['camas'] ?? 0),
            'television' => intval($_POST['television'] ?? 0),
            'lamparas' => intval($_POST['lamparas'] ?? 0),
            'manteles' => intval($_POST['manteles'] ?? 0),
            'cubrecamas' => intval($_POST['cubrecamas'] ?? 0),
            'sabanas_media_plaza' => intval($_POST['sabanas_media_plaza'] ?? 0),
            'sabanas_doble_plaza' => intval($_POST['sabanas_doble_plaza'] ?? 0),
            'almohadas' => intval($_POST['almohadas'] ?? 0),
            'fundas' => intval($_POST['fundas'] ?? 0),
            'frazadas' => intval($_POST['frazadas'] ?? 0),
            'toallas' => intval($_POST['toallas'] ?? 0),
            'cortinas_almacen' => intval($_POST['cortinas_almacen'] ?? 0),
            'alfombras_almacen' => intval($_POST['alfombras_almacen'] ?? 0)
        ];
        
        if ($inventario->guardar($datos)) {
            $mensaje = "Inventario actualizado correctamente.";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al intentar guardar los datos de inventario.";
            $tipo_mensaje = "danger";
        }
    }
}

// Obtener todo el inventario
$inventarios = $inventario->obtenerTodo();
$almacen = null;
$inventarios_habitaciones = [];

foreach ($inventarios as $inv) {
    if ($inv['tipo'] === 'almacen') {
        $almacen = $inv;
    } else {
        $inventarios_habitaciones[] = $inv;
    }
}

// Si no existe el almacén, crearlo
if (!$almacen) {
    $almacen = [
        'habitacion_numero' => 'ALMACEN',
        'tipo' => 'almacen',
        'manteles' => 0, 'cubrecamas' => 0, 'sabanas_media_plaza' => 0,
        'sabanas_doble_plaza' => 0, 'almohadas' => 0, 'fundas' => 0,
        'frazadas' => 0, 'toallas' => 0, 'cortinas_almacen' => 0, 'alfombras_almacen' => 0
    ];
}

include __DIR__ . '/../../includes/header.php';
?>

<style>
/* ═══════════════════════════════════════════════
   Apple Premium Room Inventory Aesthetic
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

/* Almacen Special Card styling */
.almacen-card {
    background: #ffffff;
    border: 1px solid rgba(0, 0, 0, 0.06);
    border-radius: 20px;
    padding: 24px;
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.dark .almacen-card {
    background: rgba(255, 255, 255, 0.02);
    border-color: rgba(255, 255, 255, 0.06);
}

/* Grid mini boxes for Almacén items */
.stock-box {
    background: rgba(0, 0, 0, 0.02);
    border: 1px solid rgba(0, 0, 0, 0.04);
    border-radius: 12px;
    padding: 12px;
    transition: all 0.2s ease;
}

.dark .stock-box {
    background: rgba(255, 255, 255, 0.03);
    border-color: rgba(255, 255, 255, 0.02);
}

.stock-box:hover {
    transform: translateY(-1.5px);
    border-color: rgba(0, 0, 0, 0.08);
}

.dark .stock-box:hover {
    border-color: rgba(255, 255, 255, 0.08);
}

.stock-label {
    font-size: 11px;
    color: #86868b;
    font-weight: 550;
    margin-bottom: 2px;
}

.dark .stock-label {
    color: #aeaeb2;
}

.stock-num {
    font-size: 18px;
    font-weight: 750;
    color: #1d1d1f;
    font-variant-numeric: tabular-nums;
}

.dark .stock-num {
    color: #ffffff;
}

/* Premium Form Inputs */
.apple-input {
    width: 100%;
    background: rgba(0, 0, 0, 0.02);
    border: 1px solid rgba(0, 0, 0, 0.08);
    border-radius: 12px;
    padding: 10px 14px;
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

/* Premium Modals */
.modal-overlay {
    background: rgba(0, 0, 0, 0.35);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
}

.modal-sheet {
    background: #ffffff;
    border: 1px solid rgba(0, 0, 0, 0.05);
    border-radius: 24px;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.1);
}

.dark .modal-sheet {
    background: #1c1c1e;
    border-color: rgba(255, 255, 255, 0.08);
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.4);
}

/* Clean Spreadsheet-like Table */
.spreadsheet-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12.5px;
}

.spreadsheet-table th {
    background: #f5f5f7;
    color: #86868b;
    font-weight: 700;
    text-transform: uppercase;
    font-size: 9px;
    letter-spacing: 0.05em;
    padding: 10px 8px;
    text-align: center;
    border: 1px solid rgba(0, 0, 0, 0.06);
}

.dark .spreadsheet-table th {
    background: rgba(255, 255, 255, 0.03);
    color: #aeaeb2;
    border-color: rgba(255, 255, 255, 0.06);
}

.spreadsheet-table td {
    padding: 6px 4px;
    border: 1px solid rgba(0, 0, 0, 0.06);
    color: #1d1d1f;
    text-align: center;
}

.dark .spreadsheet-table td {
    color: #f5f5f7;
    border-color: rgba(255, 255, 255, 0.06);
}

.spreadsheet-table tr:hover td {
    background: rgba(0, 0, 0, 0.005);
}

.dark .spreadsheet-table tr:hover td {
    background: rgba(255, 255, 255, 0.005);
}

.table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    border-radius: 14px;
    border: 1px solid rgba(0, 0, 0, 0.06);
}

.dark .table-responsive {
    border-color: rgba(255, 255, 255, 0.06);
}
</style>

<div class="container mx-auto px-4 py-8">
    
    <!-- Title Header -->
    <div class="mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight text-gray-900 dark:text-white mb-1">Inventario de Habitaciones</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">Control físico y auditoría de los elementos en cada habitación y almacén</p>
            </div>
            <?php if (esAdmin()): ?>
            <button onclick="toggleEdicionMasiva()" class="px-4 py-2.5 bg-gray-900 hover:bg-gray-800 dark:bg-gray-100 dark:hover:bg-white text-white dark:text-gray-900 text-sm font-semibold rounded-xl transition duration-200 shadow-sm flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                Edición Masiva
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Alert Notices -->
    <?php if ($mensaje): ?>
    <div class="mb-6 p-4 rounded-xl border <?php 
        if ($tipo_mensaje === 'success') echo 'bg-green-50/80 dark:bg-green-950/20 text-green-800 dark:text-green-300 border-green-100 dark:border-green-900/30';
        elseif ($tipo_mensaje === 'warning') echo 'bg-amber-50/80 dark:bg-amber-950/20 text-amber-800 dark:text-amber-300 border-amber-100 dark:border-amber-900/30';
        else echo 'bg-red-50/80 dark:bg-red-950/20 text-red-800 dark:text-red-300 border-red-100 dark:border-red-900/30';
    ?> text-sm font-semibold">
        <?php echo $mensaje; ?>
    </div>
    <?php endif; ?>

    <!-- Almacén Section -->
    <div class="almacen-card mb-8">
        <div class="flex items-center justify-between pb-4 mb-5 border-b border-gray-100 dark:border-gray-800">
            <div>
                <h2 class="text-[16px] font-extrabold text-gray-900 dark:text-white uppercase tracking-wider">Stock de Almacén</h2>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Inventario de reserva de lencería blanca y artículos de uso</p>
            </div>
            <?php if (esAdmin()): ?>
            <button onclick="abrirModalInventario('ALMACEN', 'almacen')" class="px-4 py-2 bg-gray-50 hover:bg-gray-100 dark:bg-gray-800 dark:hover:bg-gray-750 text-gray-700 dark:text-gray-300 rounded-xl border border-gray-200 dark:border-gray-750 transition font-semibold text-xs shadow-sm">
                Editar Almacén
            </button>
            <?php endif; ?>
        </div>
        
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-4">
            <div class="stock-box">
                <div class="stock-label">Manteles</div>
                <div class="stock-num"><?php echo $almacen['manteles']; ?></div>
            </div>
            <div class="stock-box">
                <div class="stock-label">Cubrecamas</div>
                <div class="stock-num"><?php echo $almacen['cubrecamas']; ?></div>
            </div>
            <div class="stock-box">
                <div class="stock-label">Sábanas ½ Pl.</div>
                <div class="stock-num"><?php echo $almacen['sabanas_media_plaza']; ?></div>
            </div>
            <div class="stock-box">
                <div class="stock-label">Sábanas 2 Pl.</div>
                <div class="stock-num"><?php echo $almacen['sabanas_doble_plaza']; ?></div>
            </div>
            <div class="stock-box">
                <div class="stock-label">Almohadas</div>
                <div class="stock-num"><?php echo $almacen['almohadas']; ?></div>
            </div>
            <div class="stock-box">
                <div class="stock-label">Fundas</div>
                <div class="stock-num"><?php echo $almacen['fundas']; ?></div>
            </div>
            <div class="stock-box">
                <div class="stock-label">Frazadas</div>
                <div class="stock-num"><?php echo $almacen['frazadas']; ?></div>
            </div>
            <div class="stock-box">
                <div class="stock-label">Toallas</div>
                <div class="stock-num"><?php echo $almacen['toallas']; ?></div>
            </div>
            <div class="stock-box">
                <div class="stock-label">Cortinas</div>
                <div class="stock-num"><?php echo $almacen['cortinas_almacen']; ?></div>
            </div>
            <div class="stock-box">
                <div class="stock-label">Alfombras</div>
                <div class="stock-num"><?php echo $almacen['alfombras_almacen']; ?></div>
            </div>
        </div>
    </div>

    <!-- Panel de Edición Masiva (Screen only sheet) -->
    <div id="panel_edicion_masiva" class="hidden apple-card mb-8">
        <div class="flex items-center justify-between pb-4 mb-5 border-b border-gray-100 dark:border-gray-800">
            <div>
                <h2 class="text-[16px] font-extrabold text-gray-900 dark:text-white uppercase tracking-wider">Edición Masiva de Habitaciones</h2>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1 font-medium">Actualiza de forma rápida múltiples cuartos a la vez</p>
            </div>
            <button onclick="toggleEdicionMasiva()" class="px-3 py-1.5 bg-gray-50 hover:bg-gray-100 dark:bg-gray-800 dark:hover:bg-gray-750 text-gray-500 rounded-xl transition text-xs font-semibold">
                Ocultar
            </button>
        </div>
        
        <form method="POST">
            <input type="hidden" name="guardar_masivo" value="1">
            <div class="table-responsive">
                <table class="spreadsheet-table">
                    <thead>
                        <tr>
                            <th class="font-bold text-gray-800 dark:text-gray-300">Habit.</th>
                            <th>Cortinas</th>
                            <th>Veladores</th>
                            <th>Roperos</th>
                            <th>Colgadores</th>
                            <th>Basureros</th>
                            <th>Shampoo</th>
                            <th>Jabón L.</th>
                            <th>Sillas</th>
                            <th>Sillones</th>
                            <th>Alfombras</th>
                            <th>Camas</th>
                            <th>TV</th>
                            <th>Lámparas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inventarios_habitaciones as $inv): ?>
                        <tr>
                            <td class="font-bold text-gray-900 dark:text-white bg-gray-50/50 dark:bg-white/[0.02]">
                                <?php echo $inv['habitacion_numero']; ?>
                            </td>
                            <td>
                                <input type="number" name="hab[<?php echo $inv['habitacion_numero']; ?>][cortinas]" value="<?php echo $inv['cortinas']; ?>" min="0" class="w-12 text-center text-xs py-0.5 border border-gray-250 dark:border-gray-650 rounded bg-transparent text-gray-900 dark:text-white focus:outline-none focus:border-blue-500">
                            </td>
                            <td>
                                <input type="number" name="hab[<?php echo $inv['habitacion_numero']; ?>][veladores]" value="<?php echo $inv['veladores']; ?>" min="0" class="w-12 text-center text-xs py-0.5 border border-gray-250 dark:border-gray-650 rounded bg-transparent text-gray-900 dark:text-white focus:outline-none focus:border-blue-500">
                            </td>
                            <td>
                                <input type="number" name="hab[<?php echo $inv['habitacion_numero']; ?>][roperos]" value="<?php echo $inv['roperos']; ?>" min="0" class="w-12 text-center text-xs py-0.5 border border-gray-250 dark:border-gray-650 rounded bg-transparent text-gray-900 dark:text-white focus:outline-none focus:border-blue-500">
                            </td>
                            <td>
                                <input type="number" name="hab[<?php echo $inv['habitacion_numero']; ?>][colgadores]" value="<?php echo $inv['colgadores']; ?>" min="0" class="w-12 text-center text-xs py-0.5 border border-gray-250 dark:border-gray-650 rounded bg-transparent text-gray-900 dark:text-white focus:outline-none focus:border-blue-500">
                            </td>
                            <td>
                                <input type="number" name="hab[<?php echo $inv['habitacion_numero']; ?>][basureros]" value="<?php echo $inv['basureros']; ?>" min="0" class="w-12 text-center text-xs py-0.5 border border-gray-250 dark:border-gray-650 rounded bg-transparent text-gray-900 dark:text-white focus:outline-none focus:border-blue-500">
                            </td>
                            <td>
                                <input type="number" name="hab[<?php echo $inv['habitacion_numero']; ?>][shampoo]" value="<?php echo $inv['shampoo']; ?>" min="0" class="w-12 text-center text-xs py-0.5 border border-gray-250 dark:border-gray-650 rounded bg-transparent text-gray-900 dark:text-white focus:outline-none focus:border-blue-500">
                            </td>
                            <td>
                                <input type="number" name="hab[<?php echo $inv['habitacion_numero']; ?>][jabon_liquido]" value="<?php echo $inv['jabon_liquido']; ?>" min="0" class="w-12 text-center text-xs py-0.5 border border-gray-250 dark:border-gray-650 rounded bg-transparent text-gray-900 dark:text-white focus:outline-none focus:border-blue-500">
                            </td>
                            <td>
                                <input type="number" name="hab[<?php echo $inv['habitacion_numero']; ?>][sillas]" value="<?php echo $inv['sillas']; ?>" min="0" class="w-12 text-center text-xs py-0.5 border border-gray-250 dark:border-gray-650 rounded bg-transparent text-gray-900 dark:text-white focus:outline-none focus:border-blue-500">
                            </td>
                            <td>
                                <input type="number" name="hab[<?php echo $inv['habitacion_numero']; ?>][sillones]" value="<?php echo $inv['sillones']; ?>" min="0" class="w-12 text-center text-xs py-0.5 border border-gray-250 dark:border-gray-650 rounded bg-transparent text-gray-900 dark:text-white focus:outline-none focus:border-blue-500">
                            </td>
                            <td>
                                <input type="number" name="hab[<?php echo $inv['habitacion_numero']; ?>][alfombras]" value="<?php echo $inv['alfombras']; ?>" min="0" class="w-12 text-center text-xs py-0.5 border border-gray-250 dark:border-gray-650 rounded bg-transparent text-gray-900 dark:text-white focus:outline-none focus:border-blue-500">
                            </td>
                            <td>
                                <input type="number" name="hab[<?php echo $inv['habitacion_numero']; ?>][camas]" value="<?php echo $inv['camas']; ?>" min="0" class="w-12 text-center text-xs py-0.5 border border-gray-250 dark:border-gray-650 rounded bg-transparent text-gray-900 dark:text-white focus:outline-none focus:border-blue-500">
                            </td>
                            <td>
                                <input type="number" name="hab[<?php echo $inv['habitacion_numero']; ?>][television]" value="<?php echo $inv['television']; ?>" min="0" class="w-12 text-center text-xs py-0.5 border border-gray-250 dark:border-gray-650 rounded bg-transparent text-gray-900 dark:text-white focus:outline-none focus:border-blue-500">
                            </td>
                            <td>
                                <input type="number" name="hab[<?php echo $inv['habitacion_numero']; ?>][lamparas]" value="<?php echo $inv['lamparas']; ?>" min="0" class="w-12 text-center text-xs py-0.5 border border-gray-250 dark:border-gray-650 rounded bg-transparent text-gray-900 dark:text-white focus:outline-none focus:border-blue-500">
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="mt-5 flex justify-end">
                <button type="submit" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-semibold text-sm transition duration-200 shadow-sm">
                    Guardar Cambios Masivos
                </button>
            </div>
        </form>
    </div>

    <!-- Rooms Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5">
        <?php foreach ($inventarios_habitaciones as $inv): ?>
        <div class="apple-card flex flex-col justify-between hover:translate-y-[-2px] transition duration-200">
            <div>
                <div class="flex items-center justify-between pb-3 mb-4 border-b border-gray-100 dark:border-gray-800">
                    <h3 class="text-base font-extrabold text-gray-900 dark:text-white">Habitación <?php echo $inv['habitacion_numero']; ?></h3>
                    <button onclick="abrirModalInventario('<?php echo $inv['habitacion_numero']; ?>', 'habitacion')" class="text-gray-400 hover:text-blue-600 p-1.5 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition duration-150">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                    </button>
                </div>
                
                <div class="grid grid-cols-2 gap-x-4 gap-y-2 text-xs font-semibold">
                    <div class="flex justify-between border-b border-gray-50 dark:border-gray-800 py-1">
                        <span class="text-gray-400 dark:text-gray-500">Cortinas</span>
                        <span class="text-gray-900 dark:text-white font-bold font-variant-numeric-tabular"><?php echo $inv['cortinas']; ?></span>
                    </div>
                    <div class="flex justify-between border-b border-gray-50 dark:border-gray-800 py-1">
                        <span class="text-gray-400 dark:text-gray-500">Veladores</span>
                        <span class="text-gray-900 dark:text-white font-bold font-variant-numeric-tabular"><?php echo $inv['veladores']; ?></span>
                    </div>
                    <div class="flex justify-between border-b border-gray-50 dark:border-gray-800 py-1">
                        <span class="text-gray-400 dark:text-gray-500">Roperos</span>
                        <span class="text-gray-900 dark:text-white font-bold font-variant-numeric-tabular"><?php echo $inv['roperos']; ?></span>
                    </div>
                    <div class="flex justify-between border-b border-gray-50 dark:border-gray-800 py-1">
                        <span class="text-gray-400 dark:text-gray-500">Colgadores</span>
                        <span class="text-gray-900 dark:text-white font-bold font-variant-numeric-tabular"><?php echo $inv['colgadores']; ?></span>
                    </div>
                    <div class="flex justify-between border-b border-gray-50 dark:border-gray-800 py-1">
                        <span class="text-gray-400 dark:text-gray-500">Basureros</span>
                        <span class="text-gray-900 dark:text-white font-bold font-variant-numeric-tabular"><?php echo $inv['basureros']; ?></span>
                    </div>
                    <div class="flex justify-between border-b border-gray-50 dark:border-gray-800 py-1">
                        <span class="text-gray-400 dark:text-gray-500">Shampoo</span>
                        <span class="text-gray-900 dark:text-white font-bold font-variant-numeric-tabular"><?php echo $inv['shampoo']; ?></span>
                    </div>
                    <div class="flex justify-between border-b border-gray-50 dark:border-gray-800 py-1">
                        <span class="text-gray-400 dark:text-gray-500">Jabón L.</span>
                        <span class="text-gray-900 dark:text-white font-bold font-variant-numeric-tabular"><?php echo $inv['jabon_liquido']; ?></span>
                    </div>
                    <div class="flex justify-between border-b border-gray-50 dark:border-gray-800 py-1">
                        <span class="text-gray-400 dark:text-gray-500">Sillas</span>
                        <span class="text-gray-900 dark:text-white font-bold font-variant-numeric-tabular"><?php echo $inv['sillas']; ?></span>
                    </div>
                    <div class="flex justify-between border-b border-gray-50 dark:border-gray-800 py-1">
                        <span class="text-gray-400 dark:text-gray-500">Sillones</span>
                        <span class="text-gray-900 dark:text-white font-bold font-variant-numeric-tabular"><?php echo $inv['sillones']; ?></span>
                    </div>
                    <div class="flex justify-between border-b border-gray-50 dark:border-gray-800 py-1">
                        <span class="text-gray-400 dark:text-gray-500">Alfombras</span>
                        <span class="text-gray-900 dark:text-white font-bold font-variant-numeric-tabular"><?php echo $inv['alfombras']; ?></span>
                    </div>
                    <div class="flex justify-between border-b border-gray-50 dark:border-gray-800 py-1">
                        <span class="text-gray-400 dark:text-gray-500">Camas</span>
                        <span class="text-gray-900 dark:text-white font-bold font-variant-numeric-tabular"><?php echo $inv['camas']; ?></span>
                    </div>
                    <div class="flex justify-between border-b border-gray-50 dark:border-gray-800 py-1">
                        <span class="text-gray-400 dark:text-gray-500">Televisión</span>
                        <span class="text-gray-900 dark:text-white font-bold font-variant-numeric-tabular"><?php echo $inv['television']; ?></span>
                    </div>
                </div>
            </div>
            
            <div class="mt-4 pt-3 border-t border-gray-50 dark:border-gray-800 flex justify-between items-center text-[10px] font-bold text-gray-400">
                <span>Lámparas</span>
                <span class="text-gray-800 dark:text-gray-300 text-xs font-variant-numeric-tabular"><?php echo $inv['lamparas']; ?></span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Modal para editar inventario -->
<div id="modal_inventario" class="hidden fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50 p-4">
    <div class="modal-sheet w-full max-w-4xl max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-gray-50 dark:bg-gray-900 px-6 sm:px-8 py-5 border-b border-gray-100 dark:border-gray-800 rounded-t-3xl z-10">
            <h2 class="text-xl font-extrabold text-gray-900 dark:text-white" id="modal_titulo">Editar Inventario</h2>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1 font-semibold" id="modal_subtitulo"></p>
        </div>
        
        <form method="POST" class="p-6 sm:p-8">
            <input type="hidden" name="guardar_inventario" value="1">
            <input type="hidden" name="habitacion_numero" id="input_habitacion_numero">
            <input type="hidden" name="tipo" id="input_tipo">
            
            <!-- Campos para habitación -->
            <div id="campos_habitacion" class="grid grid-cols-2 md:grid-cols-3 gap-5 mb-6">
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Cortinas</label>
                    <input type="number" name="cortinas" id="input_cortinas" min="0" class="apple-input font-bold">
                </div>
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Veladores</label>
                    <input type="number" name="veladores" id="input_veladores" min="0" class="apple-input font-bold">
                </div>
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Roperos</label>
                    <input type="number" name="roperos" id="input_roperos" min="0" class="apple-input font-bold">
                </div>
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Colgadores</label>
                    <input type="number" name="colgadores" id="input_colgadores" min="0" class="apple-input font-bold">
                </div>
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Basureros</label>
                    <input type="number" name="basureros" id="input_basureros" min="0" class="apple-input font-bold">
                </div>
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Shampoo</label>
                    <input type="number" name="shampoo" id="input_shampoo" min="0" class="apple-input font-bold">
                </div>
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Jabón líquido</label>
                    <input type="number" name="jabon_liquido" id="input_jabon_liquido" min="0" class="apple-input font-bold">
                </div>
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Sillas</label>
                    <input type="number" name="sillas" id="input_sillas" min="0" class="apple-input font-bold">
                </div>
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Sillones</label>
                    <input type="number" name="sillones" id="input_sillones" min="0" class="apple-input font-bold">
                </div>
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Alfombras</label>
                    <input type="number" name="alfombras" id="input_alfombras" min="0" class="apple-input font-bold">
                </div>
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Camas</label>
                    <input type="number" name="camas" id="input_camas" min="0" class="apple-input font-bold">
                </div>
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Televisión</label>
                    <input type="number" name="television" id="input_television" min="0" class="apple-input font-bold">
                </div>
                <div class="space-y-1.5 col-span-2 md:col-span-1">
                    <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Lámparas</label>
                    <input type="number" name="lamparas" id="input_lamparas" min="0" class="apple-input font-bold">
                </div>
            </div>
            
            <!-- Campos para almacén -->
            <div id="campos_almacen" class="hidden grid grid-cols-2 md:grid-cols-3 gap-5 mb-6">
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Manteles</label>
                    <input type="number" name="manteles" id="input_manteles" min="0" class="apple-input font-bold">
                </div>
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Cubrecamas</label>
                    <input type="number" name="cubrecamas" id="input_cubrecamas" min="0" class="apple-input font-bold">
                </div>
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Sábanas ½ plaza</label>
                    <input type="number" name="sabanas_media_plaza" id="input_sabanas_media_plaza" min="0" class="apple-input font-bold">
                </div>
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Sábanas 2 plazas</label>
                    <input type="number" name="sabanas_doble_plaza" id="input_sabanas_doble_plaza" min="0" class="apple-input font-bold">
                </div>
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Almohadas</label>
                    <input type="number" name="almohadas" id="input_almohadas" min="0" class="apple-input font-bold">
                </div>
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Fundas</label>
                    <input type="number" name="fundas" id="input_fundas" min="0" class="apple-input font-bold">
                </div>
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Frazadas</label>
                    <input type="number" name="frazadas" id="input_frazadas" min="0" class="apple-input font-bold">
                </div>
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Toallas</label>
                    <input type="number" name="toallas" id="input_toallas" min="0" class="apple-input font-bold">
                </div>
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Cortinas</label>
                    <input type="number" name="cortinas_almacen" id="input_cortinas_almacen" min="0" class="apple-input font-bold">
                </div>
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Alfombras</label>
                    <input type="number" name="alfombras_almacen" id="input_alfombras_almacen" min="0" class="apple-input font-bold">
                </div>
            </div>
            
            <div class="flex justify-end gap-3 pt-5 border-t border-gray-100 dark:border-gray-800">
                <button type="button" onclick="cerrarModalInventario()" class="px-5 py-2.5 text-sm font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 dark:bg-gray-850 dark:text-gray-300 dark:hover:bg-gray-800 rounded-xl transition-colors">
                    Cancelar
                </button>
                <button type="submit" class="px-5 py-2.5 text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl transition-colors shadow-sm">
                    Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirModalInventario(habitacion_numero, tipo) {
    document.getElementById('input_habitacion_numero').value = habitacion_numero;
    document.getElementById('input_tipo').value = tipo;
    
    if (tipo === 'almacen') {
        document.getElementById('modal_titulo').textContent = 'Almacén - Inventario';
        document.getElementById('modal_subtitulo').textContent = 'Stock de lencería blanca y artículos de uso';
        document.getElementById('campos_habitacion').classList.add('hidden');
        document.getElementById('campos_almacen').classList.remove('hidden');
        
        // Cargar valores del almacén
        <?php if ($almacen): ?>
        document.getElementById('input_manteles').value = <?php echo $almacen['manteles']; ?>;
        document.getElementById('input_cubrecamas').value = <?php echo $almacen['cubrecamas']; ?>;
        document.getElementById('input_sabanas_media_plaza').value = <?php echo $almacen['sabanas_media_plaza']; ?>;
        document.getElementById('input_sabanas_doble_plaza').value = <?php echo $almacen['sabanas_doble_plaza']; ?>;
        document.getElementById('input_almohadas').value = <?php echo $almacen['almohadas']; ?>;
        document.getElementById('input_fundas').value = <?php echo $almacen['fundas']; ?>;
        document.getElementById('input_frazadas').value = <?php echo $almacen['frazadas']; ?>;
        document.getElementById('input_toallas').value = <?php echo $almacen['toallas']; ?>;
        document.getElementById('input_cortinas_almacen').value = <?php echo $almacen['cortinas_almacen']; ?>;
        document.getElementById('input_alfombras_almacen').value = <?php echo $almacen['alfombras_almacen']; ?>;
        <?php endif; ?>
    } else {
        document.getElementById('modal_titulo').textContent = 'Inventario: Habitación ' + habitacion_numero;
        document.getElementById('modal_subtitulo').textContent = 'Control y auditoría de elementos físicos';
        document.getElementById('campos_habitacion').classList.remove('hidden');
        document.getElementById('campos_almacen').classList.add('hidden');
        
        // Cargar valores de la habitación
        <?php foreach ($inventarios_habitaciones as $inv): ?>
        if (habitacion_numero === '<?php echo $inv['habitacion_numero']; ?>') {
            document.getElementById('input_cortinas').value = <?php echo $inv['cortinas']; ?>;
            document.getElementById('input_veladores').value = <?php echo $inv['veladores']; ?>;
            document.getElementById('input_roperos').value = <?php echo $inv['roperos']; ?>;
            document.getElementById('input_colgadores').value = <?php echo $inv['colgadores']; ?>;
            document.getElementById('input_basureros').value = <?php echo $inv['basureros']; ?>;
            document.getElementById('input_shampoo').value = <?php echo $inv['shampoo']; ?>;
            document.getElementById('input_jabon_liquido').value = <?php echo $inv['jabon_liquido']; ?>;
            document.getElementById('input_sillas').value = <?php echo $inv['sillas']; ?>;
            document.getElementById('input_sillones').value = <?php echo $inv['sillones']; ?>;
            document.getElementById('input_alfombras').value = <?php echo $inv['alfombras']; ?>;
            document.getElementById('input_camas').value = <?php echo $inv['camas']; ?>;
            document.getElementById('input_television').value = <?php echo $inv['television']; ?>;
            document.getElementById('input_lamparas').value = <?php echo $inv['lamparas']; ?>;
        }
        <?php endforeach; ?>
    }
    
    document.getElementById('modal_inventario').classList.remove('hidden');
}

function cerrarModalInventario() {
    document.getElementById('modal_inventario').classList.add('hidden');
}

// Cerrar modal al hacer clic en el backdrop
document.getElementById('modal_inventario').addEventListener('click', function(e) {
    if (e.target === this) {
        cerrarModalInventario();
    }
});

// Toggle panel de edición masiva
function toggleEdicionMasiva() {
    const panel = document.getElementById('panel_edicion_masiva');
    if (panel.classList.contains('hidden')) {
        panel.classList.remove('hidden');
        panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
    } else {
        panel.classList.add('hidden');
    }
}

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        cerrarModalInventario();
    }
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
