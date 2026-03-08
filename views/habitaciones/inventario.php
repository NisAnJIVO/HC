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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_masivo'])) {
    // Solo administradores pueden hacer actualización masiva
    if (!esAdmin()) {
        $mensaje = "⚠ No tienes permisos para realizar esta acción";
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
            $mensaje = "✓ Inventario actualizado: {$actualizados} habitaciones";
        } else {
            $mensaje = "⚠ Actualizado: {$actualizados} habitaciones, {$errores} errores";
        }
    }
}

// Procesar actualización individual
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_inventario'])) {
    $habitacion_numero = $_POST['habitacion_numero'];
    $tipo = $_POST['tipo'] ?? 'habitacion';
    
    // Solo administradores pueden editar el almacén
    if ($tipo === 'almacen' && !esAdmin()) {
        $mensaje = "⚠ No tienes permisos para editar el almacén";
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
            $mensaje = "✓ Inventario actualizado correctamente";
        } else {
            $mensaje = "✗ Error al actualizar inventario";
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

<div class="container mx-auto px-4 py-8">
    <div class="mb-6">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
            <div class="flex-1">
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white">Inventario de Habitaciones</h1>
                <p class="text-sm sm:text-base text-gray-600 dark:text-gray-400 mt-1 sm:mt-2">Control físico de elementos en cada habitación</p>
            </div>
            <?php if (esAdmin()): ?>
            <button onclick="toggleEdicionMasiva()" 
                    class="px-4 py-2 sm:px-6 sm:py-3 text-sm sm:text-base bg-gradient-to-r from-cyan-600 to-teal-600 hover:from-cyan-700 hover:to-teal-700 text-white rounded-lg shadow-md hover:shadow-lg transition-all duration-200">
                <i class="fas fa-table mr-2"></i>Edición Masiva
            </button>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($mensaje): ?>
    <div class="mb-6 p-4 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-100 rounded-lg shadow-sm">
        <?php echo $mensaje; ?>
    </div>
    <?php endif; ?>

    <!-- Almacén -->
    <div class="mb-8 bg-gradient-to-br from-teal-50 to-cyan-50 dark:from-teal-900/20 dark:to-cyan-900/20 rounded-lg p-6 border border-teal-200 dark:border-teal-800 shadow-sm hover:shadow-md hover:scale-[1.01] transition-all duration-300">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-xl font-semibold text-teal-900 dark:text-teal-300 flex items-center gap-2">
                    <i class="fas fa-warehouse text-teal-600 dark:text-teal-400"></i>ALMACÉN
                </h2>
                <p class="text-xs text-teal-700 dark:text-teal-400 mt-1">Stock de ropa blanca y elementos extras</p>
            </div>
            <?php if (esAdmin()): ?>
            <button onclick="abrirModalInventario('ALMACEN', 'almacen')" 
                    class="px-4 py-2 bg-teal-600 hover:bg-teal-700 hover:scale-105 text-white rounded-lg transition-all duration-200 shadow-sm hover:shadow-md text-sm">
                <i class="fas fa-edit mr-2"></i>Editar
            </button>
            <?php endif; ?>
        </div>
        
        <div class="grid grid-cols-2 md:grid-cols-5 gap-3 text-sm">
            <div class="bg-white dark:bg-gray-800 p-3 rounded border border-teal-200 dark:border-teal-800 hover:border-teal-400 dark:hover:border-teal-600 hover:shadow-md hover:scale-105 transition-all duration-200">
                <span class="text-gray-600 dark:text-gray-400">Manteles:</span>
                <strong class="ml-2 text-teal-700 dark:text-teal-300"><?php echo $almacen['manteles']; ?></strong>
            </div>
            <div class="bg-white dark:bg-gray-800 p-3 rounded border border-teal-200 dark:border-teal-800 hover:border-teal-400 dark:hover:border-teal-600 hover:shadow-md hover:scale-105 transition-all duration-200">
                <span class="text-gray-600 dark:text-gray-400">Cubrecamas:</span>
                <strong class="ml-2 text-teal-700 dark:text-teal-300"><?php echo $almacen['cubrecamas']; ?></strong>
            </div>
            <div class="bg-white dark:bg-gray-800 p-3 rounded border border-teal-200 dark:border-teal-800 hover:border-teal-400 dark:hover:border-teal-600 hover:shadow-md hover:scale-105 transition-all duration-200">
                <span class="text-gray-600 dark:text-gray-400">Sábanas ½ plaza:</span>
                <strong class="ml-2 text-teal-700 dark:text-teal-300"><?php echo $almacen['sabanas_media_plaza']; ?></strong>
            </div>
            <div class="bg-white dark:bg-gray-800 p-3 rounded border border-teal-200 dark:border-teal-800 hover:border-teal-400 dark:hover:border-teal-600 hover:shadow-md hover:scale-105 transition-all duration-200">
                <span class="text-gray-600 dark:text-gray-400">Sábanas 2 plazas:</span>
                <strong class="ml-2 text-teal-700 dark:text-teal-300"><?php echo $almacen['sabanas_doble_plaza']; ?></strong>
            </div>
            <div class="bg-white dark:bg-gray-800 p-3 rounded border border-teal-200 dark:border-teal-800 hover:border-teal-400 dark:hover:border-teal-600 hover:shadow-md hover:scale-105 transition-all duration-200">
                <span class="text-gray-600 dark:text-gray-400">Almohadas:</span>
                <strong class="ml-2 text-teal-700 dark:text-teal-300"><?php echo $almacen['almohadas']; ?></strong>
            </div>
            <div class="bg-white dark:bg-gray-800 p-3 rounded border border-teal-200 dark:border-teal-800 hover:border-teal-400 dark:hover:border-teal-600 hover:shadow-md hover:scale-105 transition-all duration-200">
                <span class="text-gray-600 dark:text-gray-400">Fundas:</span>
                <strong class="ml-2 text-teal-700 dark:text-teal-300"><?php echo $almacen['fundas']; ?></strong>
            </div>
            <div class="bg-white dark:bg-gray-800 p-3 rounded border border-teal-200 dark:border-teal-800 hover:border-teal-400 dark:hover:border-teal-600 hover:shadow-md hover:scale-105 transition-all duration-200">
                <span class="text-gray-600 dark:text-gray-400">Frazadas:</span>
                <strong class="ml-2 text-teal-700 dark:text-teal-300"><?php echo $almacen['frazadas']; ?></strong>
            </div>
            <div class="bg-white dark:bg-gray-800 p-3 rounded border border-teal-200 dark:border-teal-800 hover:border-teal-400 dark:hover:border-teal-600 hover:shadow-md hover:scale-105 transition-all duration-200">
                <span class="text-gray-600 dark:text-gray-400">Toallas:</span>
                <strong class="ml-2 text-teal-700 dark:text-teal-300"><?php echo $almacen['toallas']; ?></strong>
            </div>
            <div class="bg-white dark:bg-gray-800 p-3 rounded border border-teal-200 dark:border-teal-800 hover:border-teal-400 dark:hover:border-teal-600 hover:shadow-md hover:scale-105 transition-all duration-200">
                <span class="text-gray-600 dark:text-gray-400">Cortinas:</span>
                <strong class="ml-2 text-teal-700 dark:text-teal-300"><?php echo $almacen['cortinas_almacen']; ?></strong>
            </div>
            <div class="bg-white dark:bg-gray-800 p-3 rounded border border-teal-200 dark:border-teal-800 hover:border-teal-400 dark:hover:border-teal-600 hover:shadow-md hover:scale-105 transition-all duration-200">
                <span class="text-gray-600 dark:text-gray-400">Alfombras:</span>
                <strong class="ml-2 text-teal-700 dark:text-teal-300"><?php echo $almacen['alfombras_almacen']; ?></strong>
            </div>
        </div>
    </div>

    <!-- Habitaciones -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        <?php foreach ($inventarios_habitaciones as $inv): ?>
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-indigo-200 dark:border-indigo-900 hover:border-indigo-400 dark:hover:border-indigo-600 shadow-sm hover:shadow-lg hover:scale-105 hover:-translate-y-1 transition-all duration-300">
            <div class="bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-indigo-950 dark:to-purple-950 p-4 rounded-t-lg border-b border-indigo-200 dark:border-indigo-800">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-indigo-900 dark:text-indigo-300">Hab. <?php echo $inv['habitacion_numero']; ?></h3>
                    <button onclick="abrirModalInventario('<?php echo $inv['habitacion_numero']; ?>', 'habitacion')"
                            class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-200 hover:bg-indigo-100 dark:hover:bg-indigo-900 p-2 rounded transition-all duration-200 hover:scale-110">
                        <i class="fas fa-edit"></i>
                    </button>
                </div>
            </div>
            
            <div class="p-4 grid grid-cols-2 gap-2 text-sm">
                <div class="flex justify-between border-b border-gray-200 dark:border-gray-700 py-1">
                    <span class="text-gray-600 dark:text-gray-400">Cortinas</span>
                    <strong class="text-gray-900 dark:text-white"><?php echo $inv['cortinas']; ?></strong>
                </div>
                <div class="flex justify-between border-b border-gray-200 dark:border-gray-700 py-1">
                    <span class="text-gray-600 dark:text-gray-400">Veladores</span>
                    <strong class="text-gray-900 dark:text-white"><?php echo $inv['veladores']; ?></strong>
                </div>
                <div class="flex justify-between border-b border-gray-200 dark:border-gray-700 py-1">
                    <span class="text-gray-600 dark:text-gray-400">Roperos</span>
                    <strong class="text-gray-900 dark:text-white"><?php echo $inv['roperos']; ?></strong>
                </div>
                <div class="flex justify-between border-b border-gray-200 dark:border-gray-700 py-1">
                    <span class="text-gray-600 dark:text-gray-400">Colgadores</span>
                    <strong class="text-gray-900 dark:text-white"><?php echo $inv['colgadores']; ?></strong>
                </div>
                <div class="flex justify-between border-b border-gray-200 dark:border-gray-700 py-1">
                    <span class="text-gray-600 dark:text-gray-400">Basureros</span>
                    <strong class="text-gray-900 dark:text-white"><?php echo $inv['basureros']; ?></strong>
                </div>
                <div class="flex justify-between border-b border-gray-200 dark:border-gray-700 py-1">
                    <span class="text-gray-600 dark:text-gray-400">Shampoo</span>
                    <strong class="text-gray-900 dark:text-white"><?php echo $inv['shampoo']; ?></strong>
                </div>
                <div class="flex justify-between border-b border-gray-200 dark:border-gray-700 py-1">
                    <span class="text-gray-600 dark:text-gray-400">Jabón líq.</span>
                    <strong class="text-gray-900 dark:text-white"><?php echo $inv['jabon_liquido']; ?></strong>
                </div>
                <div class="flex justify-between border-b border-gray-200 dark:border-gray-700 py-1">
                    <span class="text-gray-600 dark:text-gray-400">Sillas</span>
                    <strong class="text-gray-900 dark:text-white"><?php echo $inv['sillas']; ?></strong>
                </div>
                <div class="flex justify-between border-b border-gray-200 dark:border-gray-700 py-1">
                    <span class="text-gray-600 dark:text-gray-400">Sillones</span>
                    <strong class="text-gray-900 dark:text-white"><?php echo $inv['sillones']; ?></strong>
                </div>
                <div class="flex justify-between border-b border-gray-200 dark:border-gray-700 py-1">
                    <span class="text-gray-600 dark:text-gray-400">Alfombras</span>
                    <strong class="text-gray-900 dark:text-white"><?php echo $inv['alfombras']; ?></strong>
                </div>
                <div class="flex justify-between border-b border-gray-200 dark:border-gray-700 py-1">
                    <span class="text-gray-600 dark:text-gray-400">Camas</span>
                    <strong class="text-gray-900 dark:text-white"><?php echo $inv['camas']; ?></strong>
                </div>
                <div class="flex justify-between border-b border-gray-200 dark:border-gray-700 py-1">
                    <span class="text-gray-600 dark:text-gray-400">Televisión</span>
                    <strong class="text-gray-900 dark:text-white"><?php echo $inv['television']; ?></strong>
                </div>
                <div class="flex justify-between border-b border-gray-200 dark:border-gray-700 py-1 col-span-2">
                    <span class="text-gray-600 dark:text-gray-400">Lámparas</span>
                    <strong class="text-gray-900 dark:text-white"><?php echo $inv['lamparas']; ?></strong>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Panel de Edición Masiva -->
    <div id="panel_edicion_masiva" class="hidden mt-6 bg-white dark:bg-gray-800 rounded-lg border-2 border-cyan-300 dark:border-cyan-700 shadow-xl p-4">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="fas fa-table text-cyan-600"></i>Edición Masiva de Habitaciones
                </h2>
                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">Edita todas las habitaciones a la vez desde esta tabla</p>
            </div>
            <button onclick="toggleEdicionMasiva()" 
                    class="px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-900 dark:text-white rounded-lg transition-all">
                <i class="fas fa-times mr-2"></i>Cerrar
            </button>
        </div>
        
        <form method="POST" class="overflow-x-auto">
            <input type="hidden" name="guardar_masivo" value="1">
            <table class="w-full text-xs border-collapse">
                <thead class="bg-gradient-to-r from-cyan-100 to-teal-100 dark:from-cyan-900 dark:to-teal-900 sticky top-0">
                    <tr>
                        <th class="border border-gray-300 dark:border-gray-600 px-2 py-1.5 text-left text-xs font-semibold text-gray-900 dark:text-white">Hab.</th>
                        <th class="border border-gray-300 dark:border-gray-600 px-2 py-1.5 text-center text-[10px] font-semibold text-gray-900 dark:text-white"><i class="fas fa-window-alt block mb-0.5"></i>Cortinas</th>
                        <th class="border border-gray-300 dark:border-gray-600 px-2 py-1.5 text-center text-[10px] font-semibold text-gray-900 dark:text-white"><i class="fas fa-lightbulb block mb-0.5"></i>Velador</th>
                        <th class="border border-gray-300 dark:border-gray-600 px-2 py-1.5 text-center text-[10px] font-semibold text-gray-900 dark:text-white"><i class="fas fa-door-open block mb-0.5"></i>Ropero</th>
                        <th class="border border-gray-300 dark:border-gray-600 px-2 py-1.5 text-center text-[10px] font-semibold text-gray-900 dark:text-white"><i class="fas fa-grip-lines-vertical block mb-0.5"></i>Colgador</th>
                        <th class="border border-gray-300 dark:border-gray-600 px-2 py-1.5 text-center text-[10px] font-semibold text-gray-900 dark:text-white"><i class="fas fa-trash block mb-0.5"></i>Basurero</th>
                        <th class="border border-gray-300 dark:border-gray-600 px-2 py-1.5 text-center text-[10px] font-semibold text-gray-900 dark:text-white"><i class="fas fa-pump-soap block mb-0.5"></i>Shampoo</th>
                        <th class="border border-gray-300 dark:border-gray-600 px-2 py-1.5 text-center text-[10px] font-semibold text-gray-900 dark:text-white"><i class="fas fa-hand-sparkles block mb-0.5"></i>Jabón</th>
                        <th class="border border-gray-300 dark:border-gray-600 px-2 py-1.5 text-center text-[10px] font-semibold text-gray-900 dark:text-white"><i class="fas fa-chair block mb-0.5"></i>Silla</th>
                        <th class="border border-gray-300 dark:border-gray-600 px-2 py-1.5 text-center text-[10px] font-semibold text-gray-900 dark:text-white"><i class="fas fa-couch block mb-0.5"></i>Sillón</th>
                        <th class="border border-gray-300 dark:border-gray-600 px-2 py-1.5 text-center text-[10px] font-semibold text-gray-900 dark:text-white"><i class="fas fa-rug block mb-0.5"></i>Alfombra</th>
                        <th class="border border-gray-300 dark:border-gray-600 px-2 py-1.5 text-center text-[10px] font-semibold text-gray-900 dark:text-white"><i class="fas fa-bed block mb-0.5"></i>Cama</th>
                        <th class="border border-gray-300 dark:border-gray-600 px-2 py-1.5 text-center text-[10px] font-semibold text-gray-900 dark:text-white"><i class="fas fa-tv block mb-0.5"></i>TV</th>
                        <th class="border border-gray-300 dark:border-gray-600 px-2 py-1.5 text-center text-[10px] font-semibold text-gray-900 dark:text-white"><i class="fas fa-lightbulb block mb-0.5"></i>Lámpara</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($inventarios_habitaciones as $inv): ?>
                    <tr class="hover:bg-cyan-50 dark:hover:bg-cyan-900/20 transition-colors">
                        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-xs font-semibold text-cyan-700 dark:text-cyan-300 bg-gray-50 dark:bg-gray-900">
                            <?php echo $inv['habitacion_numero']; ?>
                        </td>
                        <td class="border border-gray-300 dark:border-gray-600 px-1 py-1">
                            <input type="number" name="hab[<?php echo $inv['habitacion_numero']; ?>][cortinas]" 
                                   value="<?php echo $inv['cortinas']; ?>" min="0" 
                                   class="w-12 px-1 py-0.5 text-xs text-center border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-1 focus:ring-cyan-500">
                        </td>
                        <td class="border border-gray-300 dark:border-gray-600 px-1 py-1">
                            <input type="number" name="hab[<?php echo $inv['habitacion_numero']; ?>][veladores]" 
                                   value="<?php echo $inv['veladores']; ?>" min="0" 
                                   class="w-12 px-1 py-0.5 text-xs text-center border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-1 focus:ring-cyan-500">
                        </td>
                        <td class="border border-gray-300 dark:border-gray-600 px-1 py-1">
                            <input type="number" name="hab[<?php echo $inv['habitacion_numero']; ?>][roperos]" 
                                   value="<?php echo $inv['roperos']; ?>" min="0" 
                                   class="w-12 px-1 py-0.5 text-xs text-center border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-1 focus:ring-cyan-500">
                        </td>
                        <td class="border border-gray-300 dark:border-gray-600 px-1 py-1">
                            <input type="number" name="hab[<?php echo $inv['habitacion_numero']; ?>][colgadores]" 
                                   value="<?php echo $inv['colgadores']; ?>" min="0" 
                                   class="w-12 px-1 py-0.5 text-xs text-center border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-1 focus:ring-cyan-500">
                        </td>
                        <td class="border border-gray-300 dark:border-gray-600 px-1 py-1">
                            <input type="number" name="hab[<?php echo $inv['habitacion_numero']; ?>][basureros]" 
                                   value="<?php echo $inv['basureros']; ?>" min="0" 
                                   class="w-12 px-1 py-0.5 text-xs text-center border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-1 focus:ring-cyan-500">
                        </td>
                        <td class="border border-gray-300 dark:border-gray-600 px-1 py-1">
                            <input type="number" name="hab[<?php echo $inv['habitacion_numero']; ?>][shampoo]" 
                                   value="<?php echo $inv['shampoo']; ?>" min="0" 
                                   class="w-12 px-1 py-0.5 text-xs text-center border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-1 focus:ring-cyan-500">
                        </td>
                        <td class="border border-gray-300 dark:border-gray-600 px-1 py-1">
                            <input type="number" name="hab[<?php echo $inv['habitacion_numero']; ?>][jabon_liquido]" 
                                   value="<?php echo $inv['jabon_liquido']; ?>" min="0" 
                                   class="w-12 px-1 py-0.5 text-xs text-center border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-1 focus:ring-cyan-500">
                        </td>
                        <td class="border border-gray-300 dark:border-gray-600 px-1 py-1">
                            <input type="number" name="hab[<?php echo $inv['habitacion_numero']; ?>][sillas]" 
                                   value="<?php echo $inv['sillas']; ?>" min="0" 
                                   class="w-12 px-1 py-0.5 text-xs text-center border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-1 focus:ring-cyan-500">
                        </td>
                        <td class="border border-gray-300 dark:border-gray-600 px-1 py-1">
                            <input type="number" name="hab[<?php echo $inv['habitacion_numero']; ?>][sillones]" 
                                   value="<?php echo $inv['sillones']; ?>" min="0" 
                                   class="w-12 px-1 py-0.5 text-xs text-center border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-1 focus:ring-cyan-500">
                        </td>
                        <td class="border border-gray-300 dark:border-gray-600 px-1 py-1">
                            <input type="number" name="hab[<?php echo $inv['habitacion_numero']; ?>][alfombras]" 
                                   value="<?php echo $inv['alfombras']; ?>" min="0" 
                                   class="w-12 px-1 py-0.5 text-xs text-center border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-1 focus:ring-cyan-500">
                        </td>
                        <td class="border border-gray-300 dark:border-gray-600 px-1 py-1">
                            <input type="number" name="hab[<?php echo $inv['habitacion_numero']; ?>][camas]" 
                                   value="<?php echo $inv['camas']; ?>" min="0" 
                                   class="w-12 px-1 py-0.5 text-xs text-center border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-1 focus:ring-cyan-500">
                        </td>
                        <td class="border border-gray-300 dark:border-gray-600 px-1 py-1">
                            <input type="number" name="hab[<?php echo $inv['habitacion_numero']; ?>][television]" 
                                   value="<?php echo $inv['television']; ?>" min="0" 
                                   class="w-12 px-1 py-0.5 text-xs text-center border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-1 focus:ring-cyan-500">
                        </td>
                        <td class="border border-gray-300 dark:border-gray-600 px-1 py-1">
                            <input type="number" name="hab[<?php echo $inv['habitacion_numero']; ?>][lamparas]" 
                                   value="<?php echo $inv['lamparas']; ?>" min="0" 
                                   class="w-12 px-1 py-0.5 text-xs text-center border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-1 focus:ring-cyan-500">
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="mt-4 flex justify-end">
                <button type="submit" 
                        class="px-6 py-2 bg-gradient-to-r from-cyan-600 to-teal-600 hover:from-cyan-700 hover:to-teal-700 text-white rounded-lg shadow-md hover:shadow-lg transition-all duration-200 hover:scale-105 font-semibold">
                    <i class="fas fa-save mr-2"></i>Guardar Todo
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal para editar inventario -->
<div id="modal_inventario" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto border-2 border-indigo-200 dark:border-indigo-900">
        <div class="sticky top-0 bg-gradient-to-br from-indigo-600 to-purple-600 dark:from-indigo-800 dark:to-purple-800 text-white p-6 rounded-t-lg z-10 shadow-lg">
            <h2 class="text-2xl font-semibold" id="modal_titulo">Editar Inventario</h2>
            <p class="text-indigo-100 dark:text-indigo-200 mt-1 text-sm" id="modal_subtitulo"></p>
        </div>
        
        <form method="POST" class="p-6">
            <input type="hidden" name="guardar_inventario" value="1">
            <input type="hidden" name="habitacion_numero" id="input_habitacion_numero">
            <input type="hidden" name="tipo" id="input_tipo">
            
            <!-- Campos para habitación -->
            <div id="campos_habitacion" class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-window-alt mr-2"></i>Cortinas
                    </label>
                    <input type="number" name="cortinas" id="input_cortinas" min="0" 
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-lightbulb mr-2"></i>Veladores
                    </label>
                    <input type="number" name="veladores" id="input_veladores" min="0" 
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-door-open mr-2"></i>Roperos
                    </label>
                    <input type="number" name="roperos" id="input_roperos" min="0" 
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-grip-lines-vertical mr-2"></i>Colgadores
                    </label>
                    <input type="number" name="colgadores" id="input_colgadores" min="0" 
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-trash mr-2"></i>Basureros
                    </label>
                    <input type="number" name="basureros" id="input_basureros" min="0" 
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-pump-soap mr-2"></i>Shampoo
                    </label>
                    <input type="number" name="shampoo" id="input_shampoo" min="0" 
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-hand-sparkles mr-2"></i>Jabón líquido
                    </label>
                    <input type="number" name="jabon_liquido" id="input_jabon_liquido" min="0" 
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-chair mr-2"></i>Sillas
                    </label>
                    <input type="number" name="sillas" id="input_sillas" min="0" 
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-couch mr-2"></i>Sillones
                    </label>
                    <input type="number" name="sillones" id="input_sillones" min="0" 
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-rug mr-2"></i>Alfombras
                    </label>
                    <input type="number" name="alfombras" id="input_alfombras" min="0" 
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-bed mr-2"></i>Camas
                    </label>
                    <input type="number" name="camas" id="input_camas" min="0" 
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-tv mr-2"></i>Televisión
                    </label>
                    <input type="number" name="television" id="input_television" min="0" 
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-lightbulb mr-2"></i>Lámparas
                    </label>
                    <input type="number" name="lamparas" id="input_lamparas" min="0" 
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
            </div>
            
            <!-- Campos para almacén -->
            <div id="campos_almacen" class="hidden grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-utensils mr-2"></i>Manteles
                    </label>
                    <input type="number" name="manteles" id="input_manteles" min="0" 
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-bed mr-2"></i>Cubrecamas
                    </label>
                    <input type="number" name="cubrecamas" id="input_cubrecamas" min="0" 
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-bed mr-2"></i>Sábanas ½ plaza
                    </label>
                    <input type="number" name="sabanas_media_plaza" id="input_sabanas_media_plaza" min="0" 
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-bed mr-2"></i>Sábanas 2 plazas
                    </label>
                    <input type="number" name="sabanas_doble_plaza" id="input_sabanas_doble_plaza" min="0" 
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-pillow mr-2"></i>Almohadas
                    </label>
                    <input type="number" name="almohadas" id="input_almohadas" min="0" 
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-bed mr-2"></i>Fundas
                    </label>
                    <input type="number" name="fundas" id="input_fundas" min="0" 
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-bed mr-2"></i>Frazadas
                    </label>
                    <input type="number" name="frazadas" id="input_frazadas" min="0" 
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-shower mr-2"></i>Toallas
                    </label>
                    <input type="number" name="toallas" id="input_toallas" min="0" 
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-window-alt mr-2"></i>Cortinas
                    </label>
                    <input type="number" name="cortinas_almacen" id="input_cortinas_almacen" min="0" 
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-rug mr-2"></i>Alfombras
                    </label>
                    <input type="number" name="alfombras_almacen" id="input_alfombras_almacen" min="0" 
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
            </div>
            
            <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                <button type="button" onclick="cerrarModalInventario()" 
                        class="px-6 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-900 dark:text-white rounded-lg transition-all duration-200 hover:scale-105 shadow-sm hover:shadow-md">
                    Cancelar
                </button>
                <button type="submit" 
                        class="px-6 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white rounded-lg transition-all duration-200 hover:scale-105 shadow-sm hover:shadow-md">
                    <i class="fas fa-save mr-2"></i>Guardar
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
        document.getElementById('modal_subtitulo').textContent = 'Stock de ropa blanca y elementos extras';
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
        document.getElementById('modal_titulo').textContent = 'Habitación ' + habitacion_numero;
        document.getElementById('modal_subtitulo').textContent = 'Control de elementos físicos';
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

// Cerrar modal al hacer clic fuera
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
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
