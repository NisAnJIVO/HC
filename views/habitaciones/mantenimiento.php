<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/Mantenimiento.php';
require_once __DIR__ . '/../../models/Habitacion.php';

$page_title = 'Mantenimiento de Habitaciones';
$mantenimientoModel = new Mantenimiento();
$habitacionModel = new Habitacion();

// Procesar acciones
$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['accion'])) {
            switch ($_POST['accion']) {
                case 'crear':
                    // Procesar imagen si se subió
                    $imagen_nombre = null;
                    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
                        $archivo = $_FILES['imagen'];
                        $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
                        $extensiones_permitidas = ['jpg', 'jpeg', 'png'];
                        $tamano_maximo = 5 * 1024 * 1024; // 5MB
                        
                        if (in_array($extension, $extensiones_permitidas) && $archivo['size'] <= $tamano_maximo) {
                            // Generar nombre único: habitacion_fecha_hora.extension
                            $habitacion_num = clean_input($_POST['habitacion_numero']);
                            $timestamp = date('Ymd_His');
                            $imagen_nombre = $habitacion_num . '_' . $timestamp . '.' . $extension;
                            
                            // Ruta donde se guardará
                            $ruta_destino = __DIR__ . '/../../assets/img/Mantenimiento/' . $imagen_nombre;
                            
                            // Mover archivo
                            if (!move_uploaded_file($archivo['tmp_name'], $ruta_destino)) {
                                $imagen_nombre = null;
                                $mensaje = 'Advertencia: No se pudo guardar la imagen, pero el mantenimiento fue registrado';
                                $tipo_mensaje = 'warning';
                            }
                        }
                    }
                    
                    $datos = [
                        'habitacion_numero' => clean_input($_POST['habitacion_numero']),
                        'titulo' => clean_input($_POST['titulo']),
                        'descripcion' => clean_input($_POST['descripcion']),
                        'prioridad' => $_POST['prioridad'],
                        'tipo' => $_POST['tipo'],
                        'estado' => 'pendiente',
                        'costo_estimado' => !empty($_POST['costo_estimado']) ? floatval($_POST['costo_estimado']) : null,
                        'fecha_inicio' => $_POST['fecha_inicio'],
                        'fecha_fin_estimada' => !empty($_POST['fecha_fin_estimada']) ? $_POST['fecha_fin_estimada'] : null,
                        'responsable' => !empty($_POST['responsable']) ? clean_input($_POST['responsable']) : null,
                        'observaciones' => !empty($_POST['observaciones']) ? clean_input($_POST['observaciones']) : null,
                        'imagen' => $imagen_nombre
                    ];
                    
                    if ($mantenimientoModel->crear($datos)) {
                        // Actualizar estado de la habitación a 'mantenimiento'
                        $conn = getConnection();
                        $sql = "UPDATE habitaciones SET estado = 'mantenimiento' WHERE numero = :numero";
                        $stmt = $conn->prepare($sql);
                        $stmt->execute([':numero' => $datos['habitacion_numero']]);
                        
                        if (!isset($mensaje)) {
                            $mensaje = 'Mantenimiento registrado correctamente';
                            $tipo_mensaje = 'success';
                        }
                    } else {
                        $mensaje = 'Error al registrar mantenimiento';
                        $tipo_mensaje = 'error';
                    }
                    break;
                    
                case 'cambiar_estado':
                    $id = intval($_POST['id']);
                    $estado = $_POST['estado'];
                    $fecha_fin = ($estado === 'completado') ? date('Y-m-d') : null;
                    $costo_real = !empty($_POST['costo_real']) ? floatval($_POST['costo_real']) : null;
                    
                    if ($mantenimientoModel->cambiarEstado($id, $estado, $fecha_fin, $costo_real)) {
                        // Si se completó el mantenimiento, cambiar habitación a limpieza
                        if ($estado === 'completado') {
                            $mant = $mantenimientoModel->obtenerPorId($id);
                            if ($mant) {
                                $conn = getConnection();
                                $sql = "UPDATE habitaciones SET estado = 'limpieza' WHERE numero = :numero";
                                $stmt = $conn->prepare($sql);
                                $stmt->execute([':numero' => $mant['habitacion_numero']]);
                            }
                        }
                        
                        $mensaje = 'Estado actualizado correctamente';
                        $tipo_mensaje = 'success';
                    } else {
                        $mensaje = 'Error al actualizar estado';
                        $tipo_mensaje = 'error';
                    }
                    break;
                    
                case 'eliminar':
                    $id = intval($_POST['id']);
                    if ($mantenimientoModel->eliminar($id)) {
                        $mensaje = 'Mantenimiento eliminado correctamente';
                        $tipo_mensaje = 'success';
                    } else {
                        $mensaje = 'Error al eliminar mantenimiento';
                        $tipo_mensaje = 'error';
                    }
                    break;
            }
        }
    } catch (Exception $e) {
        $mensaje = 'Error: ' . $e->getMessage();
        $tipo_mensaje = 'error';
    }
}

// Obtener datos
$mantenimientos = $mantenimientoModel->obtenerActivos();
$estadisticas = $mantenimientoModel->obtenerEstadisticas();
$habitaciones = $habitacionModel->obtenerTodas();

include __DIR__ . '/../../includes/header.php';
?>



<div class="container mx-auto px-4 py-6 sm:py-8" id="print-content">
    <!-- Header -->
    <div class="mb-6 sm:mb-8">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
            <div class="flex-1">
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white mb-1 sm:mb-2">Mantenimiento de Habitaciones</h1>
                <p class="text-sm sm:text-base text-gray-600 dark:text-gray-400">Control y seguimiento de mantenimientos preventivos y correctivos</p>
            </div>
            <div class="flex gap-2 no-print">
                <button onclick="window.open('<?php echo BASE_PATH; ?>/views/habitaciones/generar_pdf_mantenimientos.php', '_blank')" class="px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-lg transition flex items-center gap-2 text-sm shadow-md">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Generar PDF
                </button>
                <a href="<?php echo BASE_PATH; ?>/index.php" class="px-3 py-2 sm:px-4 text-sm sm:text-base border border-gray-300 dark:border-gray-700 rounded-lg text-gray-700 dark:text-gray-300 font-medium hover:bg-gray-50 dark:hover:bg-gray-800 transition-all text-center">
                    ← Volver
                </a>
            </div>
        </div>
    </div>

    <?php if ($mensaje): ?>
    <div id="mensaje-alerta" class="mb-6 p-4 rounded-lg <?php echo $tipo_mensaje === 'success' ? 'bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-200 border border-green-200 dark:border-green-800' : 'bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-200 border border-red-200 dark:border-red-800'; ?>">
        <?php echo $mensaje; ?>
    </div>
    <script>
        setTimeout(function() {
            const alerta = document.getElementById('mensaje-alerta');
            if (alerta) {
                alerta.style.transition = 'opacity 0.5s';
                alerta.style.opacity = '0';
                setTimeout(() => alerta.remove(), 500);
            }
        }, 2000);
    </script>
    <?php endif; ?>

    <!-- Botón Nuevo Mantenimiento -->
    <div class="mb-6 flex justify-center sm:justify-end no-print">
        <button onclick="abrirModal()" class="px-4 py-2 sm:px-6 sm:py-3 text-sm sm:text-base bg-gray-900 dark:bg-gray-700 hover:bg-gray-800 dark:hover:bg-gray-600 text-white rounded-lg transition-all duration-200 hover:shadow-md flex items-center gap-2">
            <i class="fas fa-plus text-sm sm:text-base"></i>
            Nuevo Mantenimiento
        </button>
    </div>

    <!-- Lista de Mantenimientos -->
    <?php if (empty($mantenimientos)): ?>
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6 sm:p-12 text-center">
        <i class="fas fa-wrench text-4xl sm:text-6xl text-gray-300 dark:text-gray-600 mb-3 sm:mb-4"></i>
        <p class="text-gray-600 dark:text-gray-400 text-base sm:text-lg">No hay mantenimientos activos</p>
        <p class="text-gray-500 dark:text-gray-500 text-xs sm:text-sm mt-2">Haz clic en "Nuevo Mantenimiento" para registrar uno</p>
    </div>
    <?php else: ?>
    
    <!-- Vista de cuadrícula (pantalla) -->
    <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8 xl:grid-cols-10 gap-2 sm:gap-3 no-print">
        <?php foreach ($mantenimientos as $mant): 
            $prioridad_colores = [
                'baja' => ['border' => 'border-gray-300 dark:border-gray-600', 'bg' => 'bg-gray-50 dark:bg-gray-700'],
                'media' => ['border' => 'border-blue-300 dark:border-blue-700', 'bg' => 'bg-blue-50 dark:bg-blue-900/30'],
                'alta' => ['border' => 'border-orange-300 dark:border-orange-700', 'bg' => 'bg-orange-50 dark:bg-orange-900/30'],
                'urgente' => ['border' => 'border-red-300 dark:border-red-700', 'bg' => 'bg-red-50 dark:bg-red-900/30']
            ];
            $colores = $prioridad_colores[$mant['prioridad']];
        ?>
        <button onclick='abrirDetalleMantenimiento(<?php echo json_encode($mant, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)' 
                class="aspect-[3/4] rounded-lg border-2 <?php echo $colores['border']; ?> <?php echo $colores['bg']; ?> hover:shadow-lg transition-all duration-200 sm:hover:scale-105 relative group">
            <div class="absolute inset-0 flex flex-col items-center justify-center p-2 sm:p-3">
                <span class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white"><?php echo $mant['habitacion_numero']; ?></span>
                <span class="text-xs text-gray-600 dark:text-gray-300 mt-1 sm:mt-2 text-center line-clamp-3 sm:line-clamp-4 leading-tight"><?php echo htmlspecialchars($mant['titulo']); ?></span>
            </div>
            <?php if ($mant['prioridad'] === 'urgente'): ?>
            <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full animate-pulse"></span>
            <?php endif; ?>
            <div class="absolute bottom-1 left-1 right-1">
                <span class="text-xs px-2 py-0.5 rounded-full <?php echo $colores['border']; ?> <?php echo $colores['bg']; ?> block text-center font-semibold text-gray-700 dark:text-gray-300">
                    <?php echo ucfirst(str_replace('_', ' ', $mant['estado'])); ?>
                </span>
            </div>
        </button>
        <?php endforeach; ?>
    </div>
    
    <?php endif; ?>
</div>

<!-- Modal Nuevo Mantenimiento -->
<div id="modalMantenimiento" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-3 sm:p-4">
    <div class="bg-white dark:bg-gray-800 rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-4 sm:p-6 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <h2 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">Nuevo Mantenimiento</h2>
                <button onclick="cerrarModal()" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    <i class="fas fa-times text-lg sm:text-xl"></i>
                </button>
            </div>
        </div>

        <form method="POST" action="" enctype="multipart/form-data" class="p-4 sm:p-6 space-y-4 sm:space-y-6">
            <input type="hidden" name="accion" value="crear">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                <div>
                    <label class="block text-xs sm:text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        Habitación <span class="text-red-500">*</span>
                    </label>
                    <select name="habitacion_numero" required class="w-full px-3 py-2 sm:px-4 sm:py-3 text-sm sm:text-base border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-gray-500 dark:bg-gray-700 dark:text-white">
                        <option value="">Seleccione habitación</option>
                        <?php foreach ($habitaciones as $hab): ?>
                        <option value="<?php echo $hab['numero']; ?>">
                            Hab. <?php echo $hab['numero']; ?> - <?php echo $hab['tipo']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs sm:text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        Prioridad <span class="text-red-500">*</span>
                    </label>
                    <select name="prioridad" required class="w-full px-3 py-2 sm:px-4 sm:py-3 text-sm sm:text-base border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-gray-500 dark:bg-gray-700 dark:text-white">
                        <option value="baja">Baja</option>
                        <option value="media" selected>Media</option>
                        <option value="alta">Alta</option>
                        <option value="urgente">Urgente</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                <div>
                    <label class="block text-xs sm:text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        Tipo <span class="text-red-500">*</span>
                    </label>
                    <select name="tipo" required class="w-full px-3 py-2 sm:px-4 sm:py-3 text-sm sm:text-base border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-gray-500 dark:bg-gray-700 dark:text-white">
                        <option value="preventivo">Preventivo</option>
                        <option value="correctivo" selected>Correctivo</option>
                        <option value="emergencia">Emergencia</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs sm:text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        Fecha Inicio <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="fecha_inicio" value="<?php echo date('Y-m-d'); ?>" required 
                           class="w-full px-3 py-2 sm:px-4 sm:py-3 text-sm sm:text-base border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-gray-500 dark:bg-gray-700 dark:text-white">
                </div>
            </div>

            <div>
                <label class="block text-xs sm:text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    Título <span class="text-red-500">*</span>
                </label>
                <input type="text" name="titulo" required 
                       placeholder="Ej: Reparación de tubería"
                       class="w-full px-3 py-2 sm:px-4 sm:py-3 text-sm sm:text-base border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-gray-500 dark:bg-gray-700 dark:text-white">
            </div>

            <div>
                <label class="block text-xs sm:text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    Descripción <span class="text-red-500">*</span>
                </label>
                <textarea name="descripcion" required rows="3" class="sm:rows-4"
                          placeholder="Describa el problema..."
                          class="w-full px-3 py-2 sm:px-4 sm:py-3 text-sm sm:text-base border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-gray-500 dark:bg-gray-700 dark:text-white"></textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                <div>
                    <label class="block text-xs sm:text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        Responsable
                    </label>
                    <input type="text" name="responsable" 
                           placeholder="Nombre del responsable"
                           class="w-full px-3 py-2 sm:px-4 sm:py-3 text-sm sm:text-base border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-gray-500 dark:bg-gray-700 dark:text-white">
                </div>

                <div>
                    <label class="block text-xs sm:text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        Costo Estimado (Bs.)
                    </label>
                    <input type="number" name="costo_estimado" step="0.01" min="0"
                           placeholder="0.00"
                           class="w-full px-3 py-2 sm:px-4 sm:py-3 text-sm sm:text-base border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-gray-500 dark:bg-gray-700 dark:text-white">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        Fecha Fin Estimada
                    </label>
                    <input type="date" name="fecha_fin_estimada"
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-gray-500 dark:bg-gray-700 dark:text-white">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        Observaciones
                    </label>
                    <input type="text" name="observaciones"
                           placeholder="Notas adicionales"
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-gray-500 dark:bg-gray-700 dark:text-white">
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    Fotografía (Opcional)
                </label>
                <div class="flex items-center gap-3">
                    <label for="imagen-mantenimiento" class="flex-1 cursor-pointer">
                        <div class="w-full px-4 py-3 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg hover:border-gray-400 dark:hover:border-gray-500 transition-colors">
                            <div class="flex items-center gap-3">
                                <i class="fas fa-camera text-gray-400 dark:text-gray-500"></i>
                                <span class="text-sm text-gray-600 dark:text-gray-400" id="filename-display">Subir imagen (JPG, PNG - Máx. 5MB)</span>
                            </div>
                        </div>
                        <input type="file" id="imagen-mantenimiento" name="imagen" 
                               accept="image/jpeg,image/jpg,image/png" 
                               class="hidden"
                               onchange="document.getElementById('filename-display').textContent = this.files[0] ? this.files[0].name : 'Subir imagen (JPG, PNG - Máx. 5MB)'">
                    </label>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                    <i class="fas fa-info-circle"></i> Puedes adjuntar una foto del problema (fuga, daño, etc.)
                </p>
            </div>

            <div class="flex flex-col sm:flex-row justify-end gap-2 sm:gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                <button type="button" onclick="cerrarModal()" 
                        class="px-4 py-2 sm:px-6 sm:py-3 text-sm sm:text-base border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-all duration-200">
                    Cancelar
                </button>
                <button type="submit" 
                        class="px-4 py-2 sm:px-6 sm:py-3 text-sm sm:text-base bg-gray-900 dark:bg-gray-700 hover:bg-gray-800 dark:hover:bg-gray-600 text-white rounded-lg transition-all duration-200">
                    Registrar Mantenimiento
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Detalle Mantenimiento -->
<div id="modalDetalle" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-3 sm:p-4">
    <div class="bg-white dark:bg-gray-800 rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-4 sm:p-6 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between gap-2">
                <div class="flex items-center gap-2 sm:gap-3 flex-1 min-w-0">
                    <h2 class="text-lg sm:text-2xl font-bold text-gray-900 dark:text-white truncate">Hab. <span id="d-numero"></span></h2>
                    <span id="d-prioridad-badge" class="px-2 py-0.5 sm:px-3 sm:py-1 rounded-full text-xs font-semibold uppercase whitespace-nowrap"></span>
                </div>
                <button onclick="cerrarDetalle()" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 flex-shrink-0">
                    <i class="fas fa-times text-lg sm:text-xl"></i>
                </button>
            </div>
        </div>

        <div class="p-4 sm:p-6 space-y-4 sm:space-y-6">
            <!-- Título y tipo -->
            <div>
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2" id="d-titulo"></h3>
                <div class="flex items-center gap-2">
                    <span class="px-3 py-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg text-sm font-medium" id="d-tipo"></span>
                    <span class="px-3 py-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg text-sm font-medium" id="d-estado"></span>
                </div>
            </div>

            <!-- Descripción -->
            <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-4">
                <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Descripción</p>
                <p class="text-sm text-gray-600 dark:text-gray-400" id="d-descripcion"></p>
            </div>

            <!-- Imagen si existe -->
            <div id="d-imagen-container" class="hidden">
                <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Fotografía del problema</p>
                <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-2">
                    <img id="d-imagen" src="" alt="Imagen del mantenimiento" 
                         class="w-full h-auto rounded-lg cursor-pointer hover:opacity-90 transition-opacity"
                         onclick="abrirImagenFullscreen(this.src)">
                </div>
            </div>

            <!-- Información en grid -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Fecha Inicio</p>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white" id="d-fecha-inicio"></p>
                </div>
                <div id="d-fecha-fin-container" class="hidden">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Fecha Fin Estimada</p>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white" id="d-fecha-fin"></p>
                </div>
                <div id="d-responsable-container" class="hidden">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Responsable</p>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white" id="d-responsable"></p>
                </div>
                <div id="d-costo-container" class="hidden">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Costo Estimado</p>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white" id="d-costo"></p>
                </div>
            </div>

            <!-- Observaciones -->
            <div id="d-observaciones-container" class="hidden">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Observaciones</p>
                <p class="text-sm text-gray-600 dark:text-gray-400" id="d-observaciones"></p>
            </div>
        </div>

        <!-- Botones de acción -->
        <div class="p-6 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <button onclick="eliminarMantenimientoModal()" 
                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-all duration-200 flex items-center gap-2">
                <i class="fas fa-trash"></i>
                Eliminar
            </button>
            <div class="flex gap-2">
                <button id="btn-iniciar" onclick="cambiarEstadoModal('en_proceso')" 
                        class="hidden px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-all duration-200 flex items-center gap-2">
                    <i class="fas fa-play"></i>
                    Iniciar
                </button>
                <button id="btn-completar" onclick="completarMantenimientoModal()" 
                        class="hidden px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-all duration-200 flex items-center gap-2">
                    <i class="fas fa-check"></i>
                    Completar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let mantenimientoActual = null;

function abrirModal() {
    document.getElementById('modalMantenimiento').classList.remove('hidden');
}

function cerrarModal() {
    document.getElementById('modalMantenimiento').classList.add('hidden');
}

function abrirDetalleMantenimiento(mant) {
    mantenimientoActual = mant;
    
    // Llenar datos básicos
    document.getElementById('d-numero').textContent = mant.habitacion_numero;
    document.getElementById('d-titulo').textContent = mant.titulo;
    document.getElementById('d-tipo').textContent = mant.tipo.charAt(0).toUpperCase() + mant.tipo.slice(1);
    document.getElementById('d-estado').textContent = mant.estado.replace('_', ' ').charAt(0).toUpperCase() + mant.estado.replace('_', ' ').slice(1);
    document.getElementById('d-descripcion').textContent = mant.descripcion;
    document.getElementById('d-fecha-inicio').textContent = new Date(mant.fecha_inicio).toLocaleDateString('es-BO');
    
    // Prioridad badge
    const prioridadColors = {
        'baja': 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300',
        'media': 'bg-blue-200 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300',
        'alta': 'bg-orange-200 dark:bg-orange-900/50 text-orange-700 dark:text-orange-300',
        'urgente': 'bg-red-200 dark:bg-red-900/50 text-red-700 dark:text-red-300'
    };
    const badge = document.getElementById('d-prioridad-badge');
    badge.textContent = mant.prioridad.charAt(0).toUpperCase() + mant.prioridad.slice(1);
    badge.className = 'px-3 py-1 rounded-full text-xs font-semibold uppercase ' + prioridadColors[mant.prioridad];
    
    // Campos opcionales
    const fechaFinContainer = document.getElementById('d-fecha-fin-container');
    if (mant.fecha_fin_estimada) {
        fechaFinContainer.classList.remove('hidden');
        document.getElementById('d-fecha-fin').textContent = new Date(mant.fecha_fin_estimada).toLocaleDateString('es-BO');
    } else {
        fechaFinContainer.classList.add('hidden');
    }
    
    const responsableContainer = document.getElementById('d-responsable-container');
    if (mant.responsable) {
        responsableContainer.classList.remove('hidden');
        document.getElementById('d-responsable').textContent = mant.responsable;
    } else {
        responsableContainer.classList.add('hidden');
    }
    
    const costoContainer = document.getElementById('d-costo-container');
    if (mant.costo_estimado) {
        costoContainer.classList.remove('hidden');
        document.getElementById('d-costo').textContent = 'Bs. ' + parseFloat(mant.costo_estimado).toFixed(2);
    } else {
        costoContainer.classList.add('hidden');
    }
    
    const observacionesContainer = document.getElementById('d-observaciones-container');
    if (mant.observaciones) {
        observacionesContainer.classList.remove('hidden');
        document.getElementById('d-observaciones').textContent = mant.observaciones;
    } else {
        observacionesContainer.classList.add('hidden');
    }
    
    // Imagen si existe
    const imagenContainer = document.getElementById('d-imagen-container');
    if (mant.imagen) {
        imagenContainer.classList.remove('hidden');
        document.getElementById('d-imagen').src = '<?php echo BASE_PATH; ?>/assets/img/Mantenimiento/' + mant.imagen;
    } else {
        imagenContainer.classList.add('hidden');
    }
    
    // Botones según estado
    const btnIniciar = document.getElementById('btn-iniciar');
    const btnCompletar = document.getElementById('btn-completar');
    
    if (mant.estado === 'pendiente') {
        btnIniciar.classList.remove('hidden');
        btnCompletar.classList.add('hidden');
    } else if (mant.estado === 'en_proceso') {
        btnIniciar.classList.add('hidden');
        btnCompletar.classList.remove('hidden');
    } else {
        btnIniciar.classList.add('hidden');
        btnCompletar.classList.add('hidden');
    }
    
    document.getElementById('modalDetalle').classList.remove('hidden');
}

function cerrarDetalle() {
    document.getElementById('modalDetalle').classList.add('hidden');
    mantenimientoActual = null;
}

function cambiarEstadoModal(nuevoEstado) {
    if (mantenimientoActual && confirm('¿Cambiar estado del mantenimiento?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="accion" value="cambiar_estado">
            <input type="hidden" name="id" value="${mantenimientoActual.id}">
            <input type="hidden" name="estado" value="${nuevoEstado}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function completarMantenimientoModal() {
    if (mantenimientoActual) {
        const costo = prompt('Ingrese el costo real del mantenimiento (Bs):');
        if (costo !== null) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="accion" value="cambiar_estado">
                <input type="hidden" name="id" value="${mantenimientoActual.id}">
                <input type="hidden" name="estado" value="completado">
                <input type="hidden" name="costo_real" value="${costo}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }
}

function eliminarMantenimientoModal() {
    if (mantenimientoActual && confirm('¿Está seguro de eliminar este mantenimiento? Esta acción no se puede deshacer.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="accion" value="eliminar">
            <input type="hidden" name="id" value="${mantenimientoActual.id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// Cerrar modal al hacer clic fuera
document.getElementById('modalDetalle').addEventListener('click', function(e) {
    if (e.target === this) {
        cerrarDetalle();
    }
});

// Cerrar con ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        cerrarDetalle();
        cerrarModal();
    }
});

// Función para abrir imagen en pantalla completa
function abrirImagenFullscreen(src) {
    const overlay = document.createElement('div');
    overlay.className = 'fixed inset-0 bg-black bg-opacity-90 z-[100] flex items-center justify-center p-4';
    overlay.onclick = function() { this.remove(); };
    
    const img = document.createElement('img');
    img.src = src;
    img.className = 'max-w-full max-h-full object-contain rounded-lg';
    img.onclick = function(e) { e.stopPropagation(); };
    
    const closeBtn = document.createElement('button');
    closeBtn.innerHTML = '<i class="fas fa-times text-2xl"></i>';
    closeBtn.className = 'absolute top-4 right-4 text-white hover:text-gray-300 bg-black bg-opacity-50 rounded-full w-12 h-12 flex items-center justify-center';
    closeBtn.onclick = function() { overlay.remove(); };
    
    overlay.appendChild(img);
    overlay.appendChild(closeBtn);
    document.body.appendChild(overlay);
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
