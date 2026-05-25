<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/Garaje.php';
require_once __DIR__ . '/../../models/RegistroOcupacion.php';

// Solo administrador
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'administrador') {
    header('Location: ' . BASE_PATH . '/views/finanzas/resumen.php?error=acceso_denegado');
    exit;
}

$page_title = 'Garajes';
$mensaje = '';
$tipo_mensaje = '';

// Procesar nuevo registro manual
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar_garaje'])) {
    try {
        $garajeModel = new Garaje();

        $datos = [
            'ocupacion_id'  => $_POST['ocupacion_id'] ?: null,
            'huesped_nombre'=> clean_input($_POST['huesped_nombre'] ?? ''),
            'placa'         => !empty($_POST['placa']) ? strtoupper(clean_input($_POST['placa'])) : null,
            'tipo_vehiculo' => !empty($_POST['tipo_vehiculo']) ? clean_input($_POST['tipo_vehiculo']) : null,
            'fecha'         => $_POST['fecha'],
            'costo'         => floatval($_POST['costo']),
            'observaciones' => !empty($_POST['observaciones']) ? clean_input($_POST['observaciones']) : null,
        ];

        if ($garajeModel->registrar($datos)) {
            $mensaje      = 'Registro de garaje guardado correctamente.';
            $tipo_mensaje = 'success';
            $_POST = [];
        } else {
            throw new Exception('Error al guardar el registro');
        }
    } catch (Exception $e) {
        $mensaje      = 'Error: ' . $e->getMessage();
        $tipo_mensaje = 'error';
        error_log("Error garaje: " . $e->getMessage());
    }
}

$garajeModel        = new Garaje();
$fecha_inicio       = $_GET['fecha_inicio'] ?? date('Y-m-d', strtotime('-30 days'));
$fecha_fin          = $_GET['fecha_fin']    ?? date('Y-m-d');
$registros          = $garajeModel->obtenerPorFechas($fecha_inicio, $fecha_fin);
$resumen            = $garajeModel->obtenerResumen($fecha_inicio, $fecha_fin);

$registroModel      = new RegistroOcupacion();
$ocupaciones_activas = $registroModel->obtenerActivos();

include __DIR__ . '/../../includes/header.php';
?>

<style>
.hc-card {
    background: #fff;
    border: 1px solid rgba(0,0,0,0.07);
    border-radius: 16px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.04);
    transition: box-shadow 0.2s;
}
.dark .hc-card {
    background: #161616;
    border-color: rgba(255,255,255,0.07);
    box-shadow: 0 2px 12px rgba(0,0,0,0.3);
}
.hc-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.08); }
.dark .hc-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,0.45); }

.hc-input {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid rgba(0,0,0,0.12);
    border-radius: 10px;
    font-size: 14px;
    font-weight: 450;
    color: #111;
    background: #fff;
    transition: border-color 0.15s, box-shadow 0.15s;
    outline: none;
}
.hc-input:focus {
    border-color: #111;
    box-shadow: 0 0 0 3px rgba(17,17,17,0.06);
}
.dark .hc-input {
    background: #1e1e1e;
    border-color: rgba(255,255,255,0.12);
    color: #f0f0f0;
}
.dark .hc-input:focus { border-color: #fff; box-shadow: 0 0 0 3px rgba(255,255,255,0.08); }

.hc-label {
    display: block;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #888;
    margin-bottom: 6px;
}
.dark .hc-label { color: #666; }

.hc-btn-primary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 11px 20px;
    background: #111;
    color: #fff;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    letter-spacing: -0.01em;
    transition: background 0.15s, transform 0.1s;
    cursor: pointer;
    width: 100%;
}
.hc-btn-primary:hover { background: #333; transform: translateY(-1px); }
.dark .hc-btn-primary { background: #fff; color: #111; }
.dark .hc-btn-primary:hover { background: #e5e5e5; }

.hc-btn-secondary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 10px 18px;
    background: transparent;
    border: 1px solid rgba(0,0,0,0.15);
    color: #333;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 500;
    transition: background 0.15s, border-color 0.15s;
    cursor: pointer;
    width: 100%;
}
.hc-btn-secondary:hover { background: #f5f5f5; border-color: rgba(0,0,0,0.25); }
.dark .hc-btn-secondary { border-color: rgba(255,255,255,0.12); color: #ccc; }
.dark .hc-btn-secondary:hover { background: #1e1e1e; }

.hc-badge {
    display: inline-flex;
    align-items: center;
    padding: 2px 8px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 500;
}

.hc-table th {
    padding: 10px 16px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #888;
    text-align: left;
    border-bottom: 1px solid rgba(0,0,0,0.06);
}
.dark .hc-table th {
    color: #555;
    border-bottom-color: rgba(255,255,255,0.06);
}

.hc-table td {
    padding: 13px 16px;
    font-size: 14px;
    color: #333;
    border-bottom: 1px solid rgba(0,0,0,0.04);
}
.dark .hc-table td {
    color: #ccc;
    border-bottom-color: rgba(255,255,255,0.04);
}

.hc-table tr:last-child td { border-bottom: none; }
.hc-table tr:hover td { background: rgba(0,0,0,0.015); }
.dark .hc-table tr:hover td { background: rgba(255,255,255,0.025); }
</style>

<!-- Page header -->
<div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight text-noir dark:text-white">Control de Garajes</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Registros de estacionamiento para huéspedes</p>
    </div>
    <a href="<?php echo BASE_PATH; ?>/index.php"
       class="self-start sm:self-center text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-noir dark:hover:text-white transition-colors">
        Volver al inicio
    </a>
</div>

<!-- Alert -->
<?php if ($mensaje): ?>
<div class="mb-6 animate-fade-in">
    <div class="flex items-start gap-3 p-4 rounded-xl border
        <?php echo $tipo_mensaje === 'success'
            ? 'bg-green-50 border-green-200 dark:bg-green-900/20 dark:border-green-800'
            : 'bg-red-50 border-red-200 dark:bg-red-900/20 dark:border-red-800'; ?>">
        <svg class="w-4 h-4 mt-0.5 shrink-0 <?php echo $tipo_mensaje === 'success' ? 'text-green-600' : 'text-red-600'; ?>"
             fill="currentColor" viewBox="0 0 20 20">
            <?php if ($tipo_mensaje === 'success'): ?>
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            <?php else: ?>
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
            <?php endif; ?>
        </svg>
        <p class="text-sm font-medium <?php echo $tipo_mensaje === 'success' ? 'text-green-800 dark:text-green-300' : 'text-red-800 dark:text-red-300'; ?>">
            <?php echo $mensaje; ?>
        </p>
    </div>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- ── Formulario ── -->
    <div class="lg:col-span-1">
        <div class="hc-card p-6 lg:sticky lg:top-20">
            <div class="mb-5">
                <h2 class="text-base font-semibold text-noir dark:text-white">Registrar uso</h2>
                <p class="text-xs text-gray-400 mt-0.5">Asignar espacio de garaje</p>
            </div>

            <form method="POST" action="" class="space-y-4" id="formGaraje">

                <div>
                    <label class="hc-label">Huésped</label>
                    <select name="ocupacion_id" id="ocupacion_id"
                            class="hc-input"
                            onchange="syncNombreHuesped(this)">
                        <option value="">Externo / sin estadía</option>
                        <?php foreach ($ocupaciones_activas as $ocu): ?>
                        <option value="<?php echo $ocu['id']; ?>"
                                data-nombre="<?php echo htmlspecialchars($ocu['nombres_apellidos']); ?>">
                            Hab. <?php echo $ocu['nro_pieza']; ?> — <?php echo htmlspecialchars($ocu['nombres_apellidos']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" name="huesped_nombre" id="huesped_nombre">
                </div>

                <div>
                    <label class="hc-label">Nombre (si es externo)</label>
                    <input type="text" name="nombre_manual" id="nombre_manual"
                           placeholder="Nombre del cliente"
                           class="hc-input">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="hc-label">Placa</label>
                        <input type="text" name="placa"
                               placeholder="ABC-123"
                               class="hc-input uppercase"
                               style="text-transform:uppercase">
                    </div>
                    <div>
                        <label class="hc-label">Tipo de vehículo</label>
                        <select name="tipo_vehiculo" class="hc-input">
                            <option value="">Seleccione</option>
                            <option value="Auto">Auto</option>
                            <option value="Camioneta">Camioneta</option>
                            <option value="Motocicleta">Motocicleta</option>
                            <option value="Camión">Camión</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="hc-label">Fecha</label>
                        <input type="date" name="fecha"
                               value="<?php echo date('Y-m-d'); ?>"
                               required
                               class="hc-input">
                    </div>
                    <div>
                        <label class="hc-label">Costo (Bs.)</label>
                        <input type="number" step="0.01" name="costo"
                               value="10.00" min="0" required
                               class="hc-input">
                    </div>
                </div>

                <div>
                    <label class="hc-label">Observaciones</label>
                    <textarea name="observaciones" rows="2"
                              placeholder="Notas adicionales..."
                              class="hc-input resize-none"></textarea>
                </div>

                <button type="submit" name="registrar_garaje" class="hc-btn-primary">
                    Registrar
                </button>
            </form>
        </div>
    </div>

    <!-- ── Registros ── -->
    <div class="lg:col-span-2 space-y-5">

        <!-- Summary stats -->
        <div class="grid grid-cols-2 gap-4">
            <div class="hc-card p-5">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1">Registros</p>
                <p class="text-3xl font-light text-noir dark:text-white"><?php echo $resumen['cantidad'] ?? 0; ?></p>
            </div>
            <div class="hc-card p-5">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1">Total recaudado</p>
                <p class="text-3xl font-light text-noir dark:text-white">
                    Bs. <?php echo formatMoney($resumen['total'] ?? 0); ?>
                </p>
            </div>
        </div>

        <!-- Filters -->
        <div class="hc-card p-5">
            <form method="GET" class="flex flex-col sm:flex-row gap-3 items-end">
                <div class="flex-1">
                    <label class="hc-label">Desde</label>
                    <input type="date" name="fecha_inicio"
                           value="<?php echo $fecha_inicio; ?>"
                           class="hc-input">
                </div>
                <div class="flex-1">
                    <label class="hc-label">Hasta</label>
                    <input type="date" name="fecha_fin"
                           value="<?php echo $fecha_fin; ?>"
                           class="hc-input">
                </div>
                <div class="shrink-0 w-full sm:w-auto">
                    <button type="submit" class="hc-btn-secondary">Filtrar</button>
                </div>
            </form>
        </div>

        <!-- Table -->
        <div class="hc-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full hc-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Huésped</th>
                            <th>Placa</th>
                            <th>Vehículo</th>
                            <th>Observaciones</th>
                            <th class="text-right">Costo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($registros)): ?>
                        <tr>
                            <td colspan="6" class="py-16 text-center">
                                <svg class="w-10 h-10 mx-auto mb-3 text-gray-200 dark:text-gray-700"
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                          d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/>
                                </svg>
                                <p class="text-sm text-gray-400">Sin registros en este período</p>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($registros as $reg): ?>
                        <tr>
                            <td>
                                <span class="font-medium text-noir dark:text-white">
                                    <?php echo formatDate($reg['fecha']); ?>
                                </span>
                            </td>
                            <td class="font-medium text-noir dark:text-white">
                                <?php echo htmlspecialchars($reg['huesped_nombre'] ?? '—'); ?>
                            </td>
                            <td>
                                <?php if (!empty($reg['placa'])): ?>
                                <span class="hc-badge bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 font-mono tracking-wide">
                                    <?php echo htmlspecialchars($reg['placa']); ?>
                                </span>
                                <?php else: ?>
                                <span class="text-gray-300 dark:text-gray-700">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-gray-600 dark:text-gray-400">
                                <?php echo htmlspecialchars($reg['tipo_vehiculo'] ?? '—'); ?>
                            </td>
                            <td class="text-gray-500 dark:text-gray-500 text-xs max-w-[160px] truncate">
                                <?php echo $reg['observaciones'] ? htmlspecialchars($reg['observaciones']) : '—'; ?>
                            </td>
                            <td class="text-right">
                                <span class="font-semibold text-noir dark:text-white">
                                    Bs. <?php echo formatMoney($reg['costo']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function syncNombreHuesped(select) {
    const option = select.options[select.selectedIndex];
    const nombre = option.getAttribute('data-nombre') || '';
    document.getElementById('huesped_nombre').value = nombre;
    const manualInput = document.getElementById('nombre_manual');
    if (nombre) {
        manualInput.value = nombre;
        manualInput.readOnly = true;
        manualInput.style.opacity = '0.5';
    } else {
        manualInput.readOnly = false;
        manualInput.style.opacity = '1';
    }
}

// Sync nombre manual al huesped_nombre hidden field on submit
document.getElementById('formGaraje').addEventListener('submit', function() {
    const huesped_nombre = document.getElementById('huesped_nombre');
    const nombre_manual  = document.getElementById('nombre_manual');
    if (!huesped_nombre.value && nombre_manual.value) {
        huesped_nombre.value = nombre_manual.value;
    }
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
