<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/RegistroOcupacion.php';

$page_title = 'Huéspedes Activos';

$registroModel = new RegistroOcupacion();
$ocupaciones = $registroModel->obtenerActivos();
$recientes = $registroModel->obtenerRecientementeFinalizados(48); // Últimas 48 horas

// Procesar finalización de ocupación
if (isset($_POST['finalizar_ocupacion'])) {
    $ocupacion_id = $_POST['ocupacion_id'];
    $fecha_salida = $_POST['fecha_salida'] ?? date('Y-m-d');
    
    if ($registroModel->finalizarOcupacion($ocupacion_id, $fecha_salida)) {
        header('Location: activos.php?msg=finalizado');
        exit;
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<!-- Page Header -->
<div class="mb-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Huéspedes Activos</h1>
        </div>
        <?php if (esAdmin()): ?>
        <a href="<?php echo BASE_PATH; ?>/views/huespedes/nuevo.php" 
           class="px-4 py-2 text-sm font-medium text-white bg-gray-800 hover:bg-gray-900 rounded transition-colors">
            Nuevo Registro
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Success Message -->
<?php if (isset($_GET['msg']) && $_GET['msg'] == 'finalizado'): ?>
    <div class="mb-4 px-4 py-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded text-sm text-green-800 dark:text-green-200">
        Ocupación finalizada correctamente
    </div>
<?php endif; ?>
<?php if (isset($_GET['msg']) && $_GET['msg'] == 'huesped_agregado'): ?>
    <div class="mb-4 px-4 py-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded text-sm text-blue-800 dark:text-blue-200">
        ✔ Huésped adicional registrado correctamente en la habitación.
    </div>
<?php endif; ?>

<!-- Content -->
<?php if (empty($ocupaciones)): ?>
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-12 text-center">
        <p class="text-gray-500 dark:text-gray-400 mb-4">No hay huéspedes activos</p>
        <?php if (esAdmin()): ?>
        <a href="<?php echo BASE_PATH; ?>/views/huespedes/nuevo.php" 
           class="inline-block px-4 py-2 text-sm font-medium text-white bg-gray-800 hover:bg-gray-900 rounded transition-colors">
            Registrar Huésped
        </a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <!-- Stats Summary -->
    <div class="flex items-center gap-6 mb-4 text-sm text-gray-600 dark:text-gray-400">
        <div>
            <span class="font-medium text-gray-900 dark:text-white"><?php echo count($ocupaciones); ?></span> activos
        </div>
        <div class="h-4 w-px bg-gray-300 dark:bg-gray-600"></div>
        <div>
            <span class="font-medium text-gray-900 dark:text-white"><?php echo count(array_unique(array_column($ocupaciones, 'nro_pieza'))); ?></span> habitaciones
        </div>
    </div>

    <!-- Tabla Minimalista -->
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200 dark:border-gray-700">
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Huésped</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Documento</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Hab.</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Check-in</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Check-out</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Días</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Procedencia</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                <?php foreach ($ocupaciones as $idx => $ocu): ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-900 dark:text-white"><?php echo htmlspecialchars($ocu['nombres_apellidos']); ?></div>
                            <div class="text-xs text-gray-500 dark:text-gray-400"><?php echo $ocu['genero'] == 'M' ? 'M' : 'F'; ?> · <?php echo $ocu['edad']; ?> años</div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($ocu['ci_pasaporte']); ?></div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-block px-2 py-1 text-xs font-semibold text-white bg-gray-800 dark:bg-gray-600 rounded">
                                <?php echo $ocu['nro_pieza']; ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                            <?php echo date('d/m/Y', strtotime($ocu['fecha_ingreso'])); ?>
                        </td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                            <?php echo date('d/m/Y', strtotime($ocu['fecha_salida_estimada'])); ?>
                        </td>
                        <td class="px-4 py-3 text-center text-gray-700 dark:text-gray-300">
                            <?php echo $ocu['nro_dias']; ?>
                        </td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                            <?php echo htmlspecialchars($ocu['procedencia'] ?: 'N/A'); ?>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-center gap-1.5">
                                <?php if (esAdmin()): ?>
                                <a href="<?php echo BASE_PATH; ?>/views/huespedes/editar.php?id=<?php echo $ocu['id']; ?>"
                                   class="px-2.5 py-1.5 text-xs font-medium text-white bg-gray-700 hover:bg-gray-800 rounded transition-colors"
                                   title="Editar datos del huésped">
                                    Editar
                                </a>
                                <a href="<?php echo BASE_PATH; ?>/views/huespedes/agregar_huesped.php?habitacion_id=<?php echo $ocu['habitacion_id']; ?>"
                                   class="px-2.5 py-1.5 text-xs font-medium text-white rounded transition-colors"
                                   style="background-color: #0e7490;"
                                   onmouseover="this.style.backgroundColor='#0c637d'"
                                   onmouseout="this.style.backgroundColor='#0e7490'"
                                   title="Agregar acompañante a esta habitación">
                                    + Huésped
                                </a>
                                <a href="<?php echo BASE_PATH; ?>/views/huespedes/extender_estadia.php?id=<?php echo $ocu['id']; ?>"
                                   class="px-2.5 py-1.5 text-xs font-medium text-white rounded transition-colors"
                                   style="background-color: #6b7c3e;"
                                   onmouseover="this.style.backgroundColor='#5a6833'"
                                   onmouseout="this.style.backgroundColor='#6b7c3e'"
                                   title="Extender estadía">
                                    Extender
                                </a>
                                <button type="button"
                                        onclick="abrirModalCheckout(<?php echo $ocu['id']; ?>, '<?php echo htmlspecialchars($ocu['nombres_apellidos']); ?>', '<?php echo $ocu['nro_pieza']; ?>')"
                                        class="px-2.5 py-1.5 text-xs font-medium text-white bg-red-800 hover:bg-red-900 rounded transition-colors"
                                        title="Check-out">
                                    Check-out
                                </button>
                                <?php else: ?>
                                <span class="text-xs text-gray-500 italic block">Solo Administrador</span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<!-- Checkouts Recientes (últimas 48 horas) -->
<?php if (!empty($recientes)): ?>
    <div class="mt-8">
        <div class="mb-4">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Checkouts Recientes</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Huéspedes finalizados en las últimas 48 horas · Puedes reactivar su estadía</p>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Huésped</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Documento</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Hab.</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Salida</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Estadía</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    <?php foreach ($recientes as $idx => $ocu): ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900 dark:text-white"><?php echo htmlspecialchars($ocu['nombres_apellidos']); ?></div>
                                <div class="text-xs text-gray-500 dark:text-gray-400"><?php echo $ocu['genero'] == 'M' ? 'M' : 'F'; ?> · <?php echo $ocu['edad']; ?> años</div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($ocu['ci_pasaporte']); ?></div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-block px-2 py-1 text-xs font-semibold text-white bg-gray-500 dark:bg-gray-600 rounded">
                                    <?php echo $ocu['nro_pieza']; ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                <?php 
                                $salida = strtotime($ocu['fecha_salida_real']);
                                $ahora = time();
                                $diff_horas = floor(($ahora - $salida) / 3600);
                                ?>
                                <div><?php echo date('d/m/Y H:i', $salida); ?></div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">hace <?php echo $diff_horas; ?>h</div>
                            </td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                <?php echo date('d/m/Y', strtotime($ocu['fecha_ingreso'])); ?> - <?php echo date('d/m/Y', strtotime($ocu['fecha_salida_estimada'])); ?>
                                <div class="text-xs text-gray-500 dark:text-gray-400"><?php echo $ocu['nro_dias']; ?> días</div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center gap-1.5">
                                    <?php if (esAdmin()): ?>
                                    <a href="<?php echo BASE_PATH; ?>/views/huespedes/extender_estadia.php?id=<?php echo $ocu['id']; ?>"
                                       class="px-2.5 py-1.5 text-xs font-medium text-white rounded transition-colors"
                                       style="background-color: #6b7c3e;"
                                       onmouseover="this.style.backgroundColor='#5a6833'"
                                       onmouseout="this.style.backgroundColor='#6b7c3e'"
                                       title="Reactivar y extender estadía">
                                        Extender
                                    </a>
                                    <?php else: ?>
                                    <span class="text-xs text-gray-400 italic">No disponible</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<!-- Modal de Confirmación de Checkout -->
<div id="modal_checkout" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full transform transition-all">
        <div class="p-6">
            <!-- Header -->
            <div class="flex items-center justify-center mb-4">
                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                </div>
            </div>
            
            <!-- Content -->
            <h3 class="text-2xl font-bold text-center text-noir mb-2">Confirmar Check-out</h3>
            <p class="text-center text-gray-600 mb-6">¿Está seguro de finalizar la estadía?</p>
            
            <!-- Info del huésped -->
            <div class="bg-mist rounded-xl p-4 mb-6">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm text-gray-600">Huésped:</span>
                    <span class="text-sm font-semibold text-noir" id="modal_nombre_huesped"></span>
                </div>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm text-gray-600">Habitación:</span>
                    <span class="text-sm font-semibold text-noir" id="modal_habitacion"></span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Fecha de salida:</span>
                    <span class="text-sm font-semibold text-noir"><?php echo date('d/m/Y'); ?></span>
                </div>
            </div>
            
            <!-- Buttons -->
            <div class="flex gap-3">
                <button 
                    type="button" 
                    onclick="cerrarModalCheckout()"
                    class="flex-1 px-6 py-3 border-2 border-gray-300 rounded-xl text-gray-700 font-semibold hover:bg-gray-50 transition-all duration-200"
                >
                    Cancelar
                </button>
                <button 
                    type="button" 
                    onclick="confirmarCheckout()"
                    class="flex-1 px-6 py-3 bg-red-500 rounded-xl text-white font-semibold hover:bg-red-600 transition-all duration-200 shadow-lg"
                >
                    Confirmar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Formulario oculto para checkout -->
<form id="form_checkout" method="POST" style="display: none;">
    <input type="hidden" name="ocupacion_id" id="checkout_ocupacion_id">
    <input type="hidden" name="fecha_salida" value="<?php echo date('Y-m-d'); ?>">
    <input type="hidden" name="finalizar_ocupacion" value="1">
</form>

<script>
function abrirModalCheckout(ocupacionId, nombreHuesped, habitacion) {
    document.getElementById('modal_nombre_huesped').textContent = nombreHuesped;
    document.getElementById('modal_habitacion').textContent = habitacion;
    document.getElementById('checkout_ocupacion_id').value = ocupacionId;
    document.getElementById('modal_checkout').classList.remove('hidden');
}

function cerrarModalCheckout() {
    document.getElementById('modal_checkout').classList.add('hidden');
}

function confirmarCheckout() {
    document.getElementById('form_checkout').submit();
}

// Cerrar modal al hacer clic fuera
document.getElementById('modal_checkout').addEventListener('click', function(e) {
    if (e.target === this) {
        cerrarModalCheckout();
    }
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
