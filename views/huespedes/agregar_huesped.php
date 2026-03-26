<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/Huesped.php';
require_once __DIR__ . '/../../models/RegistroOcupacion.php';

$page_title = 'Agregar Huésped a Habitación';
$mensaje = '';
$tipo_mensaje = '';

// Validar administrador
if (!esAdmin()) {
    header('Location: ' . BASE_PATH . '/index.php');
    exit;
}

$registroModel = new RegistroOcupacion();
$huespedModel  = new Huesped();

// Habitación pre-seleccionada desde activos.php
$habitacion_preseleccionada = isset($_GET['habitacion_id']) ? (int)$_GET['habitacion_id'] : 0;


// ─── POST: guardar nuevo huésped ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $habitacion_id = (int)($_POST['habitacion_id'] ?? 0);

        if ($habitacion_id <= 0) {
            throw new Exception('Debe seleccionar una habitación válida.');
        }

        $ci = clean_input($_POST['ci_pasaporte']);

        // Buscar si el huésped ya existe por CI
        $huesped_existente = $huespedModel->buscarPorCI($ci);

        if ($huesped_existente) {
            $huesped_id = $huesped_existente['id'];
        } else {
            // Calcular edad desde fecha de nacimiento si fue proporcionada
            $edad = (int)$_POST['edad'];
            if (!empty($_POST['fecha_nacimiento'])) {
                $fechaNac = new DateTime($_POST['fecha_nacimiento']);
                $hoy = new DateTime();
                $edad = (int)$hoy->diff($fechaNac)->y;
            }

            // Resolver procedencia (select + input manual)
            $procedencia_raw = clean_input($_POST['procedencia_select'] ?? '');
            if ($procedencia_raw === 'otro' || $procedencia_raw === '') {
                $procedencia_raw = clean_input($_POST['procedencia'] ?? '');
            }

            $datos_huesped = [
                'nombres_apellidos' => clean_input($_POST['nombres_apellidos']),
                'genero'            => $_POST['genero'],
                'edad'              => $edad,
                'estado_civil'      => clean_input($_POST['estado_civil']),
                'nacionalidad'      => clean_input($_POST['nacionalidad']),
                'ci_pasaporte'      => $ci,
                'profesion'         => clean_input($_POST['profesion'] ?? ''),
                'objeto'            => clean_input($_POST['objeto'] ?? ''),
                'procedencia'       => $procedencia_raw,
            ];
            $huesped_id = $huespedModel->crear($datos_huesped);
            if (!$huesped_id) {
                throw new Exception('No se pudo registrar al huésped. Verifique los datos.');
            }
        }

        // Agregar a la habitación sin generar cobro
        $registroModel->agregarHuespedAHabitacion($habitacion_id, $huesped_id);

        header('Location: ' . BASE_PATH . '/views/huespedes/activos.php?msg=huesped_agregado');
        exit;

    } catch (Exception $e) {
        $mensaje      = 'Error: ' . $e->getMessage();
        $tipo_mensaje = 'danger';
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<!-- Page Header -->
<div class="mb-6 sm:mb-8">
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
        <div class="flex-1">
            <h1 class="text-2xl sm:text-4xl font-bold text-noir mb-1 sm:mb-2">Agregar Huésped a Habitación</h1>
            <p class="text-sm sm:text-base text-gray-500">
                Registra un acompañante o huésped adicional a una habitación ya ocupada, sin generar un nuevo cobro.
            </p>
        </div>
        <a href="<?php echo BASE_PATH; ?>/views/huespedes/activos.php"
           class="px-4 py-2 sm:px-6 sm:py-3 text-sm sm:text-base border border-gray-300 rounded-xl text-gray-700 font-medium hover:bg-mist transition-all duration-200 text-center">
            Cancelar
        </a>
    </div>
</div>

<!-- Alert Messages -->
<?php if ($mensaje): ?>
    <div class="mb-8 animate-fade-in">
        <div class="bg-<?php echo $tipo_mensaje === 'success' ? 'green' : 'red'; ?>-50 border border-<?php echo $tipo_mensaje === 'success' ? 'green' : 'red'; ?>-200 rounded-xl p-4">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <?php if ($tipo_mensaje === 'success'): ?>
                        <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    <?php else: ?>
                        <svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                    <?php endif; ?>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-<?php echo $tipo_mensaje === 'success' ? 'green' : 'red'; ?>-800">
                        <?php echo $mensaje; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Main Form -->
<form method="POST" action="" class="space-y-8">
    <!-- Habitación transmitida como campo oculto (viene de activos.php via ?habitacion_id=X) -->
    <input type="hidden" name="habitacion_id" value="<?php echo $habitacion_preseleccionada; ?>">

    <!-- ─── SECCIÓN 2: Información Personal (copia exacta de nuevo.php) ──── -->
    <div class="bg-white rounded-2xl border-2 border-gray-300 shadow-lg overflow-hidden hover:shadow-xl transition-shadow duration-300">
        <div class="px-8 py-6 border-b-2 border-gray-300 bg-gradient-to-r from-blue-50 to-indigo-50">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-gray-900">Información Personal</h2>
                    <p class="text-sm text-gray-600 mt-0.5">Datos de identificación del nuevo huésped</p>
                </div>
            </div>
        </div>

        <div class="p-8 space-y-6 bg-gray-50">

            <!-- Fila 1: CI y Nombres -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                    <label class="block text-sm font-bold text-gray-800 mb-2">
                        CI o Pasaporte <span class="text-red-600 text-base">*</span>
                    </label>
                    <div class="relative">
                        <input
                            type="text"
                            id="ci_pasaporte"
                            name="ci_pasaporte"
                            onblur="buscarHuespedPorCI()"
                            required
                            class="w-full px-4 py-4 border-2 border-gray-400 rounded-xl focus:ring-4
                                   focus:ring-blue-300 focus:border-blue-500 transition-all
                                   text-gray-900 font-medium placeholder-gray-400 hover:border-gray-500 shadow-sm"
                            placeholder="Ingrese CI o Pasaporte"
                        >
                        <!-- Indicador de búsqueda -->
                        <div id="busqueda_indicador" class="hidden absolute right-3 top-1/2 transform -translate-y-1/2">
                            <div class="w-5 h-5 border-2 border-gray-300 border-t-noir rounded-full animate-spin"></div>
                        </div>
                    </div>
                    <div id="busqueda_mensaje" class="text-xs mt-1.5"></div>
                    <p class="text-xs text-gray-500 mt-1.5">Si existe en el sistema, los datos se autocompletarán</p>
                </div>

                <div class="space-y-2">
                    <label class="block text-sm font-bold text-gray-800 mb-2">
                        Nombres y Apellidos <span class="text-red-600 text-base">*</span>
                    </label>
                    <input
                        type="text"
                        id="nombres_apellidos"
                        name="nombres_apellidos"
                        required
                        class="w-full px-4 py-4 border-2 border-gray-400 rounded-xl focus:ring-4
                               focus:ring-blue-300 focus:border-blue-500 transition-all
                               text-gray-900 font-medium placeholder-gray-400 hover:border-gray-500 shadow-sm"
                        placeholder="Nombre completo del huésped"
                    >
                </div>
            </div>

            <!-- Fila 2: Género, Fecha Nacimiento, Edad, Estado Civil, Nacionalidad -->
            <div class="grid grid-cols-1 md:grid-cols-5 gap-6">
                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-noir">
                        Género <span class="text-red-500">*</span>
                    </label>
                    <select id="genero" name="genero" required
                            class="w-full px-4 py-3.5 border border-gray-300 rounded-xl focus:ring-2
                                   focus:ring-noir focus:border-transparent transition-all
                                   text-noir appearance-none bg-white">
                        <option value="">Seleccione</option>
                        <option value="M">Masculino</option>
                        <option value="F">Femenino</option>
                    </select>
                </div>

                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-noir">
                        Fecha de Nacimiento <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="date"
                        id="fecha_nacimiento"
                        onchange="calcularEdad()"
                        required
                        class="w-full px-4 py-3.5 border border-gray-300 rounded-xl focus:ring-2
                               focus:ring-noir focus:border-transparent transition-all text-noir"
                    >
                    <p class="text-xs text-gray-500 mt-1.5">La edad se calcula automáticamente</p>
                </div>

                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-noir">
                        Edad <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="number"
                        id="edad"
                        name="edad"
                        required
                        readonly
                        min="1" max="120"
                        class="w-full px-4 py-3.5 border border-gray-300 rounded-xl bg-gray-50
                               text-noir font-semibold cursor-not-allowed"
                        placeholder="0"
                    >
                    <p class="text-xs text-gray-500 mt-1.5">Se calcula automáticamente</p>
                </div>

                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-noir">Estado Civil</label>
                    <select id="estado_civil" name="estado_civil"
                            class="w-full px-4 py-3.5 border border-gray-300 rounded-xl focus:ring-2
                                   focus:ring-noir focus:border-transparent transition-all
                                   text-noir appearance-none bg-white">
                        <option value="">Seleccione</option>
                        <option value="S">S</option>
                        <option value="C">C</option>
                        <option value="D">D</option>
                        <option value="V">V</option>
                    </select>
                </div>

                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-noir">
                        Nacionalidad <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        id="nacionalidad"
                        name="nacionalidad"
                        required
                        value="Boliviano"
                        class="w-full px-4 py-3.5 border border-gray-300 rounded-xl focus:ring-2
                               focus:ring-noir focus:border-transparent transition-all
                               text-noir placeholder-gray-400"
                        placeholder="País"
                    >
                </div>
            </div>

            <!-- Fila 3: Profesión, Objeto, Procedencia -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-noir">Profesión</label>
                    <input
                        type="text"
                        id="profesion"
                        name="profesion"
                        class="w-full px-4 py-3.5 border border-gray-300 rounded-xl focus:ring-2
                               focus:ring-noir focus:border-transparent transition-all
                               text-noir placeholder-gray-400"
                        placeholder="Ocupación laboral"
                    >
                </div>

                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-noir">Objeto del Viaje</label>
                    <select id="objeto" name="objeto"
                            class="w-full px-4 py-3.5 border border-gray-300 rounded-xl focus:ring-2
                                   focus:ring-noir focus:border-transparent transition-all
                                   text-noir appearance-none bg-white">
                        <option value="">Seleccione</option>
                        <option value="Turismo">Turismo</option>
                        <option value="Negocios">Negocios</option>
                        <option value="Salud">Salud</option>
                        <option value="Educación">Educación</option>
                        <option value="Familiar">Familiar</option>
                        <option value="Tránsito">Tránsito</option>
                        <option value="Paso">Paso</option>
                        <option value="Concierto">Concierto</option>
                        <option value="Trabajo">Trabajo</option>
                        <option value="Otro">Otro</option>
                    </select>
                </div>

                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-noir">Procedencia</label>
                    <!-- Select de departamentos -->
                    <select id="procedencia_select" name="procedencia_select"
                            onchange="toggleProcedenciaCustom()"
                            class="w-full px-4 py-3.5 border border-gray-300 rounded-xl focus:ring-2
                                   focus:ring-noir focus:border-transparent transition-all
                                   text-noir appearance-none bg-white">
                        <option value="">Seleccione departamento</option>
                        <option value="La Paz">La Paz</option>
                        <option value="Santa Cruz">Santa Cruz</option>
                        <option value="Cochabamba">Cochabamba</option>
                        <option value="Oruro">Oruro</option>
                        <option value="Potosí">Potosí</option>
                        <option value="Chuquisaca">Chuquisaca</option>
                        <option value="Tarija">Tarija</option>
                        <option value="Beni">Beni</option>
                        <option value="Pando">Pando</option>
                        <option value="otro">Otro lugar...</option>
                    </select>
                    <!-- Input para escribir manualmente -->
                    <input
                        type="text"
                        id="procedencia"
                        name="procedencia"
                        class="hidden w-full px-4 py-3.5 border border-gray-300 rounded-xl focus:ring-2
                               focus:ring-noir focus:border-transparent transition-all
                               text-noir placeholder-gray-400 mt-2"
                        placeholder="Escriba el país o ciudad de origen"
                    >
                </div>
            </div>
        </div>
    </div>

    <!-- Aviso -->
    <div class="bg-amber-50 border-2 border-amber-300 rounded-xl p-4 flex items-start gap-3">
        <svg class="w-5 h-5 text-amber-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <p class="text-sm text-amber-800">
            <strong>Nota:</strong> Este formulario agrega al huésped a la habitación seleccionada con las
            mismas fechas ya registradas. <strong>No se genera ningún cobro adicional.</strong>
        </p>
    </div>

    <!-- Botones -->
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 pt-2">
        <a href="<?php echo BASE_PATH; ?>/views/huespedes/activos.php"
           class="px-5 py-2.5 border-2 border-gray-300 rounded-lg text-gray-700 text-sm font-medium
                  hover:bg-mist transition-all text-center">
            Cancelar
        </a>
        <button type="submit"
                class="px-8 py-3 bg-green-600 text-white text-base font-bold rounded-xl
                       hover:bg-green-700 transition-all shadow-lg hover:shadow-xl
                       flex items-center justify-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
            </svg>
            Agregar Huésped a Habitación
        </button>
    </div>
</form>

<script>

// ── Cálculo de edad desde fecha de nacimiento ────────────────────────────────
function calcularEdad() {
    const fechaInput = document.getElementById('fecha_nacimiento').value;
    if (!fechaInput) return;
    const hoy = new Date();
    const nac = new Date(fechaInput);
    let edad = hoy.getFullYear() - nac.getFullYear();
    const m = hoy.getMonth() - nac.getMonth();
    if (m < 0 || (m === 0 && hoy.getDate() < nac.getDate())) edad--;
    if (edad >= 0 && edad <= 120) document.getElementById('edad').value = edad;
}

// ── Búsqueda de huésped por CI (al salir del campo) ─────────────────────────
function buscarHuespedPorCI() {
    const ci = document.getElementById('ci_pasaporte').value.trim();
    if (!ci) return;

    document.getElementById('busqueda_indicador').classList.remove('hidden');
    document.getElementById('busqueda_mensaje').textContent = '';

    fetch('<?php echo BASE_PATH; ?>/controllers/buscar_huesped_ci.php?ci=' + encodeURIComponent(ci))
        .then(r => r.json())
        .then(data => {
            document.getElementById('busqueda_indicador').classList.add('hidden');

            if (data && data.encontrado && data.datos) {
                const d = data.datos;
                setVal('nombres_apellidos', d.nombres_apellidos);
                setVal('genero',            d.genero);
                setVal('estado_civil',      d.estado_civil);
                setVal('nacionalidad',      d.nacionalidad);
                setVal('profesion',         d.profesion);
                setVal('objeto',            d.objeto);
                setVal('edad',              d.edad);

                // Fecha de nacimiento
                if (d.fecha_nacimiento) {
                    document.getElementById('fecha_nacimiento').value = d.fecha_nacimiento;
                }

                // Procedencia: intentar seleccionar en el dropdown primero
                const departamentos = ['La Paz','Santa Cruz','Cochabamba','Oruro','Potosí',
                                       'Chuquisaca','Tarija','Beni','Pando'];
                const procSel = document.getElementById('procedencia_select');
                const procInput = document.getElementById('procedencia');
                if (d.procedencia && departamentos.includes(d.procedencia)) {
                    procSel.value = d.procedencia;
                    procInput.classList.add('hidden');
                } else if (d.procedencia) {
                    procSel.value = 'otro';
                    procInput.classList.remove('hidden');
                    procInput.value = d.procedencia;
                }

                document.getElementById('busqueda_mensaje').innerHTML =
                    '<span class="text-green-600 font-medium">✔ Huésped encontrado — datos autocompletados</span>';
            } else {
                document.getElementById('busqueda_mensaje').innerHTML =
                    '<span class="text-gray-500">Huésped no encontrado — complete los datos manualmente</span>';
            }
        })
        .catch(() => {
            document.getElementById('busqueda_indicador').classList.add('hidden');
        });
}

function setVal(id, val) {
    const el = document.getElementById(id);
    if (el) el.value = val ?? '';
}

// ── Procedencia: toggle campo manual ────────────────────────────────────────
function toggleProcedenciaCustom() {
    const sel = document.getElementById('procedencia_select');
    const inp = document.getElementById('procedencia');
    if (sel.value === 'otro' || sel.value === '') {
        inp.classList.remove('hidden');
        inp.focus();
    } else {
        inp.classList.add('hidden');
        inp.value = '';
    }
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
