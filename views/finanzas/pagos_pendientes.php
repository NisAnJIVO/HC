<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/Finanzas.php';

// Verificar que el usuario sea administrador o recepcionista
if (!isset($_SESSION['rol'])) {
    header('Location: ' . BASE_PATH . '/login.php');
    exit;
}

$page_title = 'Pagos Pendientes';
$mensaje = '';
$tipo_mensaje = '';

// Procesar completar pago
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['completar_pago'])) {
    try {
        $finanzasModel = new Finanzas();
        
        $ingreso_id = intval($_POST['ingreso_id']);
        $metodo_pago = clean_input($_POST['metodo_pago']);
        $numero_transaccion = !empty($_POST['numero_transaccion']) ? clean_input($_POST['numero_transaccion']) : null;
        
        if ($finanzasModel->completarPagoPendiente($ingreso_id, $metodo_pago, $numero_transaccion)) {
            $mensaje = 'Pago completado correctamente con método: ' . strtoupper($metodo_pago);
            $tipo_mensaje = 'success';
        } else {
            throw new Exception('Error al completar el pago');
        }
    } catch (Exception $e) {
        $mensaje = 'Error: ' . $e->getMessage();
        $tipo_mensaje = 'danger';
        error_log("Error al completar pago pendiente: " . $e->getMessage());
    }
}

// Obtener pagos pendientes
$finanzasModel = new Finanzas();
$pagos_pendientes = $finanzasModel->obtenerIngresosPendientes();

include __DIR__ . '/../../includes/header.php';
?>

<style>
.glass-card {
    background: rgba(255, 255, 255, 0.7);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.8);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.04);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.dark .glass-card {
    background: rgba(23, 23, 23, 0.7);
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
}

.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background-color: #fefefe;
    margin: 5% auto;
    padding: 20px;
    border: 1px solid #888;
    border-radius: 12px;
    width: 90%;
    max-width: 500px;
}
</style>

<!-- Page Header -->
<div class="mb-8">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-4xl font-bold text-noir dark:text-white mb-2">
                <i class="fas fa-clock text-orange-500"></i> Pagos Pendientes
            </h1>
            <p class="text-sm md:text-base text-gray-500 dark:text-gray-400">
                Gestiona los pagos que quedaron pendientes al momento del check-in
            </p>
        </div>
        <a href="<?php echo BASE_PATH; ?>/index.php" class="px-3 md:px-6 py-2 md:py-3 border border-gray-300 dark:border-gray-700 rounded-lg md:rounded-xl text-gray-700 dark:text-gray-300 text-sm md:text-base font-medium hover:bg-mist dark:hover:bg-gray-800 transition-all duration-200 text-center">
            ← Volver
        </a>
    </div>
</div>

<!-- Alert Messages -->
<?php if ($mensaje): ?>
    <div class="mb-8 animate-fade-in">
        <div class="bg-<?php echo $tipo_mensaje === 'success' ? 'green' : 'red'; ?>-50 dark:bg-<?php echo $tipo_mensaje === 'success' ? 'green' : 'red'; ?>-900/20 border border-<?php echo $tipo_mensaje === 'success' ? 'green' : 'red'; ?>-200 dark:border-<?php echo $tipo_mensaje === 'success' ? 'green' : 'red'; ?>-800 rounded-xl p-4">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <?php if ($tipo_mensaje === 'success'): ?>
                        <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                    <?php else: ?>
                        <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                        </svg>
                    <?php endif; ?>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-<?php echo $tipo_mensaje === 'success' ? 'green' : 'red'; ?>-800 dark:text-<?php echo $tipo_mensaje === 'success' ? 'green' : 'red'; ?>-200">
                        <?php echo $mensaje; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Tabla de Pagos Pendientes -->
<div class="glass-card rounded-xl p-6">
    <?php if (empty($pagos_pendientes)): ?>
        <div class="text-center py-12">
            <i class="fas fa-check-circle text-6xl text-green-500 mb-4"></i>
            <h3 class="text-xl font-semibold text-gray-700 dark:text-gray-300 mb-2">
                ¡No hay pagos pendientes!
            </h3>
            <p class="text-gray-500 dark:text-gray-400">
                Todos los pagos están al día
            </p>
        </div>
    <?php else: ?>
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-xl font-bold text-noir dark:text-white">
                <i class="fas fa-list"></i> Lista de Pagos Pendientes
            </h2>
            <span class="px-3 py-1 bg-orange-100 text-orange-800 rounded-full text-sm font-semibold">
                <?php echo count($pagos_pendientes); ?> pendiente(s)
            </span>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-100 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Fecha</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Concepto</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Huésped</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Habitación</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Monto</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Estado</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <?php foreach ($pagos_pendientes as $pago): ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">
                                <?php echo date('d/m/Y', strtotime($pago['fecha'])); ?>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">
                                <?php echo htmlspecialchars($pago['concepto']); ?>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">
                                <?php echo htmlspecialchars($pago['nombres_apellidos'] ?? 'N/A'); ?>
                                <?php if ($pago['ci_pasaporte']): ?>
                                    <br><span class="text-xs text-gray-500">CI: <?php echo htmlspecialchars($pago['ci_pasaporte']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">
                                <?php echo htmlspecialchars($pago['nro_pieza'] ?? 'N/A'); ?>
                            </td>
                            <td class="px-4 py-3 text-sm font-semibold text-gray-900 dark:text-gray-100 text-right">
                                Bs. <?php echo number_format($pago['monto'], 2); ?>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-2 py-1 bg-orange-100 text-orange-800 rounded-full text-xs font-semibold">
                                    <i class="fas fa-clock"></i> PENDIENTE
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <button 
                                    onclick="abrirModalCompletarPago(<?php echo $pago['id']; ?>, '<?php echo htmlspecialchars($pago['concepto']); ?>', <?php echo $pago['monto']; ?>)"
                                    class="px-3 py-1.5 bg-green-700 hover:bg-green-800 text-white rounded-lg text-xs font-medium transition-colors shadow-sm"
                                >
                                    <i class="fas fa-check"></i> Completar Pago
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Resumen -->
        <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
            <div class="flex justify-between items-center">
                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Total Pendiente:</span>
                <span class="text-2xl font-bold text-orange-600">
                    Bs. <?php echo number_format(array_sum(array_column($pagos_pendientes, 'monto')), 2); ?>
                </span>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Modal para completar pago -->
<div id="modalCompletarPago" class="modal">
    <div class="modal-content">
        <h2 class="text-xl font-bold text-noir mb-4">
            <i class="fas fa-money-bill-wave text-green-600"></i> Completar Pago
        </h2>
        
        <form method="POST" id="formCompletarPago">
            <input type="hidden" name="completar_pago" value="1">
            <input type="hidden" name="ingreso_id" id="pago_id">
            
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Concepto:</label>
                <p id="pago_concepto" class="text-gray-600 bg-gray-50 p-3 rounded-lg"></p>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Monto:</label>
                <p id="pago_monto" class="text-2xl font-bold text-green-600"></p>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Método de Pago: <span class="text-red-500">*</span>
                </label>
                
                <div class="grid grid-cols-2 gap-3">
                    <label class="cursor-pointer">
                        <input 
                            type="radio" 
                            name="metodo_pago" 
                            value="efectivo" 
                            checked
                            onchange="toggleNumeroTransaccion()"
                            class="hidden"
                            id="modal_metodo_efectivo"
                        >
                        <div id="modal_btn_efectivo" class="flex items-center justify-center gap-2 p-3 border-2 border-green-500 bg-green-50 rounded-lg">
                            <i class="fas fa-money-bill-wave text-green-600"></i>
                            <span class="font-semibold text-sm text-green-700">Efectivo</span>
                        </div>
                    </label>
                    
                    <label class="cursor-pointer">
                        <input 
                            type="radio" 
                            name="metodo_pago" 
                            value="qr"
                            onchange="toggleNumeroTransaccion()"
                            class="hidden"
                            id="modal_metodo_qr"
                        >
                        <div id="modal_btn_qr" class="flex items-center justify-center gap-2 p-3 border-2 border-gray-300 bg-white rounded-lg">
                            <i class="fas fa-qrcode text-gray-600"></i>
                            <span class="font-semibold text-sm text-gray-700">QR</span>
                        </div>
                    </label>
                </div>
            </div>
            
            <div id="numero_transaccion_container" style="display: none;" class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Número de Transacción (Opcional):
                </label>
                <input 
                    type="text" 
                    name="numero_transaccion" 
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"
                    placeholder="Ingrese el número de transacción"
                >
            </div>
            
            <div class="flex gap-3 mt-6">
                <button 
                    type="submit"
                    class="flex-1 px-4 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg font-semibold transition-colors"
                >
                    <i class="fas fa-check"></i> Confirmar Pago
                </button>
                <button 
                    type="button"
                    onclick="cerrarModal()"
                    class="px-4 py-3 bg-gray-300 hover:bg-gray-400 text-gray-700 rounded-lg font-semibold transition-colors"
                >
                    Cancelar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirModalCompletarPago(id, concepto, monto) {
    document.getElementById('pago_id').value = id;
    document.getElementById('pago_concepto').textContent = concepto;
    document.getElementById('pago_monto').textContent = 'Bs. ' + parseFloat(monto).toFixed(2);
    document.getElementById('modalCompletarPago').style.display = 'block';
    
    // Resetear formulario
    document.getElementById('modal_metodo_efectivo').checked = true;
    cambiarMetodoPagoModal('efectivo');
}

function cerrarModal() {
    document.getElementById('modalCompletarPago').style.display = 'none';
}

function cambiarMetodoPagoModal(metodo) {
    const btnEfectivo = document.getElementById('modal_btn_efectivo');
    const btnQr = document.getElementById('modal_btn_qr');
    
    if (metodo === 'efectivo') {
        btnEfectivo.className = 'flex items-center justify-center gap-2 p-3 border-2 border-green-500 bg-green-50 rounded-lg';
        btnQr.className = 'flex items-center justify-center gap-2 p-3 border-2 border-gray-300 bg-white rounded-lg';
    } else {
        btnEfectivo.className = 'flex items-center justify-center gap-2 p-3 border-2 border-gray-300 bg-white rounded-lg';
        btnQr.className = 'flex items-center justify-center gap-2 p-3 border-2 border-purple-500 bg-purple-50 rounded-lg';
    }
}

function toggleNumeroTransaccion() {
    const metodoQr = document.getElementById('modal_metodo_qr').checked;
    const container = document.getElementById('numero_transaccion_container');
    container.style.display = metodoQr ? 'block' : 'none';
    
    // Actualizar visualización
    const metodo = metodoQr ? 'qr' : 'efectivo';
    cambiarMetodoPagoModal(metodo);
}

// Cerrar modal al hacer clic fuera de él
window.onclick = function(event) {
    const modal = document.getElementById('modalCompletarPago');
    if (event.target == modal) {
        cerrarModal();
    }
}

// Añadir listeners a los radio buttons del modal
document.getElementById('modal_metodo_efectivo').addEventListener('change', function() {
    cambiarMetodoPagoModal('efectivo');
});

document.getElementById('modal_metodo_qr').addEventListener('change', function() {
    cambiarMetodoPagoModal('qr');
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
