<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/TurnoRecepcionista.php';

// Verificar que el usuario sea administrador
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'administrador') {
    header('Location: ' . BASE_PATH . '/views/finanzas/resumen.php?error=acceso_denegado');
    exit;
}

$page_title = 'Cambio de Turno';
$mensaje = '';
$tipo_mensaje = '';

$turnoModel = new TurnoRecepcionista();

// Procesar cambio de turno
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_turno'])) {
    try {
        $nuevo_recepcionista = clean_input($_POST['recepcionista']);
        $observaciones = !empty($_POST['observaciones']) ? clean_input($_POST['observaciones']) : null;
        
        if ($turnoModel->cambiarTurno($nuevo_recepcionista, $observaciones)) {
            $mensaje = '¡Turno cambiado exitosamente a ' . htmlspecialchars($nuevo_recepcionista) . '!';
            $tipo_mensaje = 'success';
        } else {
            throw new Exception('Error al cambiar el turno');
        }
    } catch (Exception $e) {
        $mensaje = 'Error: ' . $e->getMessage();
        $tipo_mensaje = 'danger';
        error_log("Error al cambiar turno: " . $e->getMessage());
    }
}

// Obtener turno activo
$turno_activo = $turnoModel->obtenerTurnoActivo();
$estadisticas = $turnoModel->obtenerEstadisticasTurnoActivo();
$recepcionistas = TurnoRecepcionista::obtenerRecepcionistas();
$historial = $turnoModel->obtenerHistorial(20);

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

.recepcionista-card {
    transition: border-color 0.2s;
}

.recepcionista-card:hover {
    border-color: rgb(59 130 246);
}

.recepcionista-card.activo {
    border-color: rgb(59 130 246);
    background: rgb(239 246 255);
}

.dark .recepcionista-card.activo {
    border-color: rgb(37 99 235);
    background: rgba(30, 58, 138, 0.2);
}

.recepcionista-card input[type="radio"] {
    width: 24px;
    height: 24px;
}
</style>

<!-- Page Header -->
<div class="mb-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-noir dark:text-white mb-1">
                Cambio de Turno
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Gestiona qué recepcionista está actualmente en turno
            </p>
        </div>
        <a href="<?php echo BASE_PATH; ?>/views/finanzas/cierre_caja.php" class="px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg text-gray-700 dark:text-gray-300 text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors text-center">
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

<!-- Turno Activo Actual -->
<div class="glass-card rounded-lg p-5 mb-6">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-noir dark:text-white">
            Turno Activo
        </h2>
        <span class="px-3 py-1 bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 rounded text-xs font-medium">
            En Turno
        </span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Info del Turno -->
        <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
            <div class="mb-3">
                <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">Recepcionista en Turno</p>
                <p class="text-xl font-semibold text-gray-900 dark:text-white">
                    <?php echo htmlspecialchars($turno_activo['recepcionista_nombre']); ?>
                </p>
            </div>
            <div class="space-y-1.5 text-xs text-gray-600 dark:text-gray-400">
                <p>
                    <strong>Inicio:</strong> <?php echo date('d/m/Y H:i', strtotime($turno_activo['fecha_inicio'])); ?>
                </p>
                <p>
                    <strong>Duración:</strong> 
                    <?php 
                    $inicio = strtotime($turno_activo['fecha_inicio']);
                    $ahora = time();
                    $horas = floor(($ahora - $inicio) / 3600);
                    $minutos = floor((($ahora - $inicio) % 3600) / 60);
                    echo "$horas hrs $minutos min";
                    ?>
                </p>
            </div>
        </div>

        <!-- Estadísticas del Turno -->
        <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                Estadísticas del Turno
            </h3>
            <div class="space-y-2 text-xs">
                <div class="flex justify-between items-center">
                    <span class="text-gray-600 dark:text-gray-400">Efectivo:</span>
                    <span class="font-semibold text-gray-900 dark:text-white">
                        Bs. <?php echo number_format($estadisticas['total_efectivo'], 2); ?>
                    </span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600 dark:text-gray-400">QR:</span>
                    <span class="font-semibold text-gray-900 dark:text-white">
                        Bs. <?php echo number_format($estadisticas['total_qr'], 2); ?>
                    </span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600 dark:text-gray-400">Egresos:</span>
                    <span class="font-semibold text-gray-900 dark:text-white">
                        Bs. <?php echo number_format($estadisticas['total_egresos'], 2); ?>
                    </span>
                </div>
                <div class="flex justify-between items-center pt-2 border-t border-gray-300 dark:border-gray-600">
                    <span class="text-gray-700 dark:text-gray-300 font-medium">Balance:</span>
                    <span class="font-bold text-base text-gray-900 dark:text-white">
                        Bs. <?php echo number_format($estadisticas['balance'], 2); ?>
                    </span>
                </div>
                <div class="flex justify-between items-center text-xs text-gray-500 dark:text-gray-400">
                    <span>Transacciones:</span>
                    <span class="font-medium"><?php echo $estadisticas['num_transacciones']; ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Formulario de Cambio de Turno -->
<div class="glass-card rounded-lg p-5 mb-6">
    <h2 class="text-lg font-semibold text-noir dark:text-white mb-4">
        Cambiar Turno
    </h2>

    <form method="POST" action="">
        <input type="hidden" name="cambiar_turno" value="1">
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-4">
            <?php foreach ($recepcionistas as $recepcionista): ?>
                <?php 
                $es_activo = $turno_activo && $turno_activo['recepcionista_nombre'] === $recepcionista;
                ?>
                <label class="recepcionista-card <?php echo $es_activo ? 'activo' : ''; ?> bg-white dark:bg-gray-800 rounded-lg p-4 border <?php echo $es_activo ? 'border-blue-500 dark:border-blue-600' : 'border-gray-200 dark:border-gray-700'; ?> cursor-pointer hover:border-blue-300 dark:hover:border-blue-700">
                    <div class="flex items-center gap-3">
                        <input 
                            type="radio" 
                            name="recepcionista" 
                            value="<?php echo htmlspecialchars($recepcionista); ?>"
                            <?php echo $es_activo ? 'checked' : ''; ?>
                            required
                            class="w-4 h-4"
                        >
                        <div class="flex-1">
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">
                                <?php echo htmlspecialchars($recepcionista); ?>
                            </p>
                            <?php if ($es_activo): ?>
                                <p class="text-xs text-blue-600 dark:text-blue-400 mt-0.5">
                                    Turno activo
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </label>
            <?php endforeach; ?>
        </div>

        <div class="mb-4">
            <label for="observaciones" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                Observaciones (Opcional)
            </label>
            <textarea 
                name="observaciones" 
                id="observaciones" 
                rows="2"
                class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-800 dark:text-white focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                placeholder="Ej: Cambio de turno por fin de jornada laboral..."
            ></textarea>
        </div>

        <div>
            <button 
                type="submit" 
                class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors"
            >
                Cambiar Turno
            </button>
        </div>
    </form>
</div>

<!-- Historial de Turnos -->
<div class="glass-card rounded-lg p-5">
    <h2 class="text-lg font-semibold text-noir dark:text-white mb-4">
        Historial de Turnos
    </h2>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-100 dark:bg-gray-800">
                <tr>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 dark:text-gray-300">
                        Recepcionista
                    </th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 dark:text-gray-300">
                        Fecha Inicio
                    </th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 dark:text-gray-300">
                        Fecha Fin
                    </th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 dark:text-gray-300">
                        Duración
                    </th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 dark:text-gray-300">
                        Estado
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                <?php foreach ($historial as $turno): ?>
                    <tr>
                        <td class="px-3 py-2 text-xs font-medium text-gray-900 dark:text-white">
                            <?php echo htmlspecialchars($turno['recepcionista_nombre']); ?>
                        </td>
                        <td class="px-3 py-2 text-xs text-gray-600 dark:text-gray-400">
                            <?php echo date('d/m/Y H:i', strtotime($turno['fecha_inicio'])); ?>
                        </td>
                        <td class="px-3 py-2 text-xs text-gray-600 dark:text-gray-400">
                            <?php 
                            if ($turno['fecha_fin']) {
                                echo date('d/m/Y H:i', strtotime($turno['fecha_fin']));
                            } else {
                                echo '<span class="text-blue-600 dark:text-blue-400 font-medium">En curso</span>';
                            }
                            ?>
                        </td>
                        <td class="px-3 py-2 text-xs text-gray-600 dark:text-gray-400">
                            <?php 
                            $inicio = strtotime($turno['fecha_inicio']);
                            $fin = $turno['fecha_fin'] ? strtotime($turno['fecha_fin']) : time();
                            $duracion_horas = floor(($fin - $inicio) / 3600);
                            $duracion_minutos = floor((($fin - $inicio) % 3600) / 60);
                            echo "$duracion_horas hrs $duracion_minutos min";
                            ?>
                        </td>
                        <td class="px-3 py-2 text-xs">
                            <?php if ($turno['activo']): ?>
                                <span class="px-2 py-0.5 bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 rounded text-xs font-medium">
                                    Activo
                                </span>
                            <?php else: ?>
                                <span class="px-2 py-0.5 bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400 rounded text-xs">
                                    Finalizado
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Auto-submit cuando se selecciona un recepcionista diferente (opcional)
document.querySelectorAll('input[name="recepcionista"]').forEach(radio => {
    radio.addEventListener('change', function() {
        // Opcional: podrías hacer que se envíe automáticamente
        // this.form.submit();
    });
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
