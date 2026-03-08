<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/CierreCaja.php';

// Verificar que el usuario esté logueado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ' . BASE_PATH . '/login.php');
    exit;
}

$page_title   = 'Cierre de Caja';
$mensaje      = '';
$tipo_mensaje = '';

// Datos del usuario logueado
$usuario_id     = $_SESSION['usuario_id'];
$usuario_nombre = $_SESSION['nombre_completo'] ?? $_SESSION['usuario'] ?? 'Usuario';
$usuario_rol    = $_SESSION['rol'] ?? 'usuario';

$cierreModel = new CierreCaja();

// ── Procesar cierre de caja ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cerrar_caja'])) {
    try {
        $observaciones = !empty($_POST['observaciones']) ? clean_input($_POST['observaciones']) : null;

        // Guardar quién hizo el cierre mediante la sesión
        $_SESSION['recepcionista_actual'] = $usuario_nombre;

        $cierre_id = $cierreModel->registrarCierre($observaciones, $usuario_nombre);

        if ($cierre_id) {
            unset($_SESSION['recepcionista_actual']);
            $mensaje      = "¡Cierre registrado exitosamente por {$usuario_nombre}!";
            $tipo_mensaje = 'success';
        } else {
            throw new Exception('Error al registrar el cierre');
        }
    } catch (Exception $e) {
        $mensaje      = 'Error: ' . $e->getMessage();
        $tipo_mensaje = 'danger';
        error_log("Error al cerrar caja: " . $e->getMessage());
    }
}

// ── Datos para la vista ──────────────────────────────────────────────────────
$resumen_actual = $cierreModel->calcularResumenActual();
$historial      = $cierreModel->obtenerHistorial(50);

include __DIR__ . '/../../includes/header.php';
?>

<!-- Page Header -->
<div class="mb-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Cierre de Caja</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Sesión de: <span class="font-medium text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($usuario_nombre); ?></span>
                · <?php echo date('d/m/Y H:i'); ?>
            </p>
        </div>
        <a href="<?php echo BASE_PATH; ?>/index.php"
           class="px-4 py-2 text-sm font-medium border border-gray-300 dark:border-gray-700 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
            ← Volver
        </a>
    </div>
</div>

<!-- Alert Messages -->
<?php if ($mensaje): ?>
    <div class="mb-6 px-4 py-3 rounded-lg text-sm <?php echo $tipo_mensaje === 'success' ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200' : 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-200'; ?>">
        <?php echo htmlspecialchars($mensaje); ?>
    </div>
<?php endif; ?>

<!-- Resumen del turno actual -->
<div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6 mb-6">
    <div class="flex items-center justify-between mb-5">
        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Resumen del turno actual</h2>
        <button onclick="document.getElementById('modalCierre').classList.remove('hidden')"
                class="px-4 py-2 text-sm font-medium text-white bg-gray-800 hover:bg-gray-900 dark:bg-gray-700 dark:hover:bg-gray-600 rounded-lg transition-colors">
            Registrar Cierre
        </button>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 border border-green-200 dark:border-green-700">
            <p class="text-xs font-medium text-green-600 dark:text-green-400 mb-1">Ingresos Efectivo</p>
            <p class="text-2xl font-bold text-green-700 dark:text-green-300">
                Bs. <?php echo number_format($resumen_actual['total_efectivo'], 2); ?>
            </p>
        </div>
        <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4 border border-purple-200 dark:border-purple-700">
            <p class="text-xs font-medium text-purple-600 dark:text-purple-400 mb-1">Pagos QR</p>
            <p class="text-2xl font-bold text-purple-700 dark:text-purple-300">
                Bs. <?php echo number_format($resumen_actual['total_qr'], 2); ?>
            </p>
        </div>
        <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4 border border-red-200 dark:border-red-700">
            <p class="text-xs font-medium text-red-600 dark:text-red-400 mb-1">Egresos</p>
            <p class="text-2xl font-bold text-red-700 dark:text-red-300">
                Bs. <?php echo number_format($resumen_actual['total_egresos'], 2); ?>
            </p>
        </div>
        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-700">
            <p class="text-xs font-medium text-blue-600 dark:text-blue-400 mb-1">Balance Total</p>
            <p class="text-2xl font-bold text-blue-700 dark:text-blue-300">
                Bs. <?php echo number_format($resumen_actual['balance_total'], 2); ?>
            </p>
        </div>
    </div>
</div>

<!-- Historial de Cierres -->
<div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Historial de Cierres</h2>
    </div>

    <?php if (empty($historial)): ?>
        <div class="p-12 text-center text-sm text-gray-500 dark:text-gray-400">
            No hay cierres registrados aún.
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700">
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">#</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Fecha / Hora</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Usuario</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Efectivo</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">QR</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Balance</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">PDF</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    <?php foreach ($historial as $cierre): ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <td class="px-4 py-3 text-gray-400 dark:text-gray-500">#<?php echo $cierre['id']; ?></td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900 dark:text-white"><?php echo date('d/m/Y', strtotime($cierre['fecha_cierre'])); ?></div>
                                <div class="text-xs text-gray-500 dark:text-gray-400"><?php echo date('H:i', strtotime($cierre['fecha_cierre'])); ?></div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="font-medium text-gray-900 dark:text-white">
                                    <?php echo htmlspecialchars($cierre['recepcionista'] ?? $cierre['usuario_nombre'] ?? 'Sistema'); ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right font-medium text-green-600 dark:text-green-400">
                                Bs. <?php echo number_format($cierre['total_efectivo'], 2); ?>
                            </td>
                            <td class="px-4 py-3 text-right font-medium text-purple-600 dark:text-purple-400">
                                Bs. <?php echo number_format($cierre['total_qr'], 2); ?>
                            </td>
                            <td class="px-4 py-3 text-right font-bold text-blue-600 dark:text-blue-400">
                                Bs. <?php echo number_format($cierre['balance_total'], 2); ?>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <a href="<?php echo BASE_PATH; ?>/views/finanzas/generar_pdf_cierre.php?id=<?php echo $cierre['id']; ?>"
                                   target="_blank"
                                   class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-white bg-red-600 hover:bg-red-700 rounded transition-colors">
                                    PDF
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Modal Registrar Cierre -->
<div id="modalCierre" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full p-6">
        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-1">Registrar Cierre</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">
            Usuario: <span class="font-semibold text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($usuario_nombre); ?></span>
        </p>

        <div class="grid grid-cols-2 gap-3 mb-5 text-sm">
            <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-3 border border-green-200 dark:border-green-700">
                <p class="text-xs text-green-600 dark:text-green-400">Efectivo</p>
                <p class="font-bold text-green-700 dark:text-green-300">Bs. <?php echo number_format($resumen_actual['total_efectivo'], 2); ?></p>
            </div>
            <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-3 border border-purple-200 dark:border-purple-700">
                <p class="text-xs text-purple-600 dark:text-purple-400">QR</p>
                <p class="font-bold text-purple-700 dark:text-purple-300">Bs. <?php echo number_format($resumen_actual['total_qr'], 2); ?></p>
            </div>
            <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-3 border border-red-200 dark:border-red-700">
                <p class="text-xs text-red-600 dark:text-red-400">Egresos</p>
                <p class="font-bold text-red-700 dark:text-red-300">Bs. <?php echo number_format($resumen_actual['total_egresos'], 2); ?></p>
            </div>
            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-3 border border-blue-200 dark:border-blue-700">
                <p class="text-xs text-blue-600 dark:text-blue-400">Balance</p>
                <p class="font-bold text-blue-700 dark:text-blue-300">Bs. <?php echo number_format($resumen_actual['balance_total'], 2); ?></p>
            </div>
        </div>

        <form method="POST">
            <input type="hidden" name="cerrar_caja" value="1">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Observaciones <span class="text-gray-400 font-normal">(opcional)</span>
                </label>
                <textarea name="observaciones" rows="3"
                          class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-gray-400 dark:bg-gray-700 dark:text-gray-200"
                          placeholder="Ej: Entregado al dueño, depósito realizado, etc."></textarea>
            </div>
            <div class="flex gap-3">
                <button type="button"
                        onclick="document.getElementById('modalCierre').classList.add('hidden')"
                        class="flex-1 px-4 py-2.5 text-sm font-medium border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    Cancelar
                </button>
                <button type="submit"
                        class="flex-1 px-4 py-2.5 text-sm font-medium text-white bg-gray-800 hover:bg-gray-900 dark:bg-gray-700 dark:hover:bg-gray-600 rounded-lg transition-colors">
                    Confirmar Cierre
                </button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
