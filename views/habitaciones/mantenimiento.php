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

// Leer mensajes pasados por redirección (PRG Pattern)
if (isset($_GET['msg'])) {
    $mensaje = urldecode($_GET['msg']);
    $tipo_mensaje = isset($_GET['type']) ? $_GET['type'] : 'info';
}

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
                            $habitacion_num = clean_input($_POST['habitacion_numero']);
                            $timestamp = date('Ymd_His');
                            $imagen_nombre = $habitacion_num . '_' . $timestamp . '.' . $extension;
                            
                            $ruta_destino = __DIR__ . '/../../assets/img/Mantenimiento/' . $imagen_nombre;
                            
                            if (!move_uploaded_file($archivo['tmp_name'], $ruta_destino)) {
                                $imagen_nombre = null;
                                $mensaje = 'Advertencia: No se pudo guardar la imagen, pero el mantenimiento fue registrado';
                                $tipo_mensaje = 'warning';
                            }
                        }
                    }
                    
                    // Registro simple simplificado a petición del usuario
                    $datos = [
                        'habitacion_numero' => clean_input($_POST['habitacion_numero']),
                        'titulo' => clean_input($_POST['titulo']),
                        'descripcion' => clean_input($_POST['descripcion']),
                        'prioridad' => 'media', // valor por defecto simplificado
                        'tipo' => 'correctivo', // valor por defecto simplificado
                        'estado' => 'pendiente',
                        'costo_estimado' => null,
                        'fecha_inicio' => $_POST['fecha_registro'],
                        'fecha_fin_estimada' => null,
                        'responsable' => null,
                        'observaciones' => null,
                        'imagen' => $imagen_nombre
                    ];
                    
                    if ($mantenimientoModel->crear($datos)) {
                        // Actualizar estado de la habitación a 'mantenimiento'
                        $conn = getConnection();
                        $sql = "UPDATE habitaciones SET estado = 'mantenimiento' WHERE numero = :numero";
                        $stmt = $conn->prepare($sql);
                        $stmt->execute([':numero' => $datos['habitacion_numero']]);
                        
                        $final_msg = !empty($mensaje) ? $mensaje : 'Mantenimiento registrado correctamente';
                        $final_type = !empty($tipo_mensaje) ? $tipo_mensaje : 'success';
                        
                        header("Location: " . BASE_PATH . "/views/habitaciones/mantenimiento.php?msg=" . urlencode($final_msg) . "&type=" . $final_type);
                        exit;
                    } else {
                        header("Location: " . BASE_PATH . "/views/habitaciones/mantenimiento.php?msg=" . urlencode('Error al registrar mantenimiento') . "&type=error");
                        exit;
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
                        
                        header("Location: " . BASE_PATH . "/views/habitaciones/mantenimiento.php?msg=" . urlencode('Estado actualizado correctamente') . "&type=success");
                        exit;
                    } else {
                        header("Location: " . BASE_PATH . "/views/habitaciones/mantenimiento.php?msg=" . urlencode('Error al actualizar estado') . "&type=error");
                        exit;
                    }
                    break;
                    
                case 'eliminar':
                    $id = intval($_POST['id']);
                    if ($mantenimientoModel->eliminar($id)) {
                        header("Location: " . BASE_PATH . "/views/habitaciones/mantenimiento.php?msg=" . urlencode('Mantenimiento eliminado correctamente') . "&type=success");
                        exit;
                    } else {
                        header("Location: " . BASE_PATH . "/views/habitaciones/mantenimiento.php?msg=" . urlencode('Error al eliminar mantenimiento') . "&type=error");
                        exit;
                    }
                    break;
            }
        }
    } catch (Exception $e) {
        header("Location: " . BASE_PATH . "/views/habitaciones/mantenimiento.php?msg=" . urlencode('Error: ' . $e->getMessage()) . "&type=error");
        exit;
    }
}

// Obtener datos
$mantenimientos = $mantenimientoModel->obtenerActivos();
$habitaciones = $habitacionModel->obtenerTodas();

include __DIR__ . '/../../includes/header.php';
?>

<style>
/* Estilos premium Apple-inspired */
.page-bg {
    background-color: #f5f5f7;
}
.dark .page-bg {
    background-color: #000000;
}

/* Room Grid Cards */
.maint-card {
    background: #ffffff;
    border: 1px solid rgba(0, 0, 0, 0.05);
    border-radius: 18px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
    transition: all 0.28s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    padding: 20px;
    position: relative;
    cursor: pointer;
    text-align: left;
}

.maint-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 16px 32px rgba(0, 0, 0, 0.04);
    border-color: rgba(0, 0, 0, 0.12);
}

.dark .maint-card {
    background: #1c1c1e;
    border-color: rgba(255, 255, 255, 0.06);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.dark .maint-card:hover {
    border-color: rgba(255, 255, 255, 0.15);
    box-shadow: 0 16px 32px rgba(0, 0, 0, 0.35);
}

/* Modals Overlay with premium Glassmorphism */
.modal-overlay {
    background: rgba(0, 0, 0, 0.4);
    backdrop-filter: blur(20px) saturate(190%);
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    position: fixed;
    inset: 0;
    z-index: 100;
    padding: 16px;
}

.modal-overlay.active {
    opacity: 1;
    pointer-events: auto;
}

.modal-box {
    background: rgba(255, 255, 255, 0.94);
    backdrop-filter: blur(30px);
    border: 1px solid rgba(255, 255, 255, 0.5);
    border-radius: 24px;
    box-shadow: 0 30px 60px rgba(0, 0, 0, 0.12);
    width: 100%;
    max-width: 500px;
    transform: scale(0.94) translateY(12px);
    opacity: 0;
    transition: transform 0.35s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.3s ease;
    display: flex;
    flex-direction: column;
    max-height: 85vh;
    overflow: hidden;
}

.modal-overlay.active .modal-box {
    transform: scale(1) translateY(0);
    opacity: 1;
}

.dark .modal-box {
    background: rgba(28, 28, 30, 0.92);
    border-color: rgba(255, 255, 255, 0.08);
    box-shadow: 0 30px 60px rgba(0, 0, 0, 0.4);
}

/* Inputs & Form Fields */
.maint-input {
    width: 100%;
    background: rgba(0, 0, 0, 0.02);
    border: 1px solid rgba(0, 0, 0, 0.1);
    border-radius: 12px;
    padding: 12px 16px;
    font-size: 15px;
    transition: all 0.2s ease;
    color: #1d1d1f;
}

.maint-input:focus {
    outline: none;
    border-color: #0071e3;
    background: #ffffff;
    box-shadow: 0 0 0 4px rgba(0, 113, 227, 0.12);
}

.dark .maint-input {
    background: rgba(255, 255, 255, 0.04);
    border-color: rgba(255, 255, 255, 0.12);
    color: #f5f5f7;
}

.dark .maint-input:focus {
    background: #1c1c1e;
    border-color: #0071e3;
    box-shadow: 0 0 0 4px rgba(0, 113, 227, 0.25);
}

/* Elegant badges */
.maint-badge {
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.03em;
    text-transform: uppercase;
    padding: 4px 10px;
    border-radius: 9999px;
    display: inline-flex;
    align-items: center;
}

.badge-pendiente {
    background: #fff9e6;
    color: #b27b00;
}
.dark .badge-pendiente {
    background: rgba(178, 123, 0, 0.15);
    color: #ffc233;
}

.badge-proceso {
    background: #e8f4ff;
    color: #0066cc;
}
.dark .badge-proceso {
    background: rgba(0, 102, 204, 0.15);
    color: #4da6ff;
}

.badge-completado {
    background: #eafaf1;
    color: #1a7f37;
}
.dark .badge-completado {
    background: rgba(26, 127, 55, 0.15);
    color: #56d364;
}

/* Sleek close button */
.close-btn {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: rgba(0, 0, 0, 0.04);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #86868b;
    transition: all 0.2s ease;
    border: none;
    cursor: pointer;
}

.close-btn:hover {
    background: rgba(0, 0, 0, 0.08);
    color: #1d1d1f;
}

.dark .close-btn {
    background: rgba(255, 255, 255, 0.06);
    color: #aeaeb2;
}

.dark .close-btn:hover {
    background: rgba(255, 255, 255, 0.12);
    color: #f5f5f7;
}
</style>

<div class="min-h-screen page-bg py-6 sm:py-10 transition-colors duration-300">
    <div class="container mx-auto px-4 max-w-6xl" id="print-content">
        <!-- Header -->
        <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight text-gray-900 dark:text-white mb-2">Mantenimiento</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">Control y registro minimalista de reparaciones y adecuaciones</p>
            </div>
            
            <div class="flex items-center gap-3 no-print">
                <button onclick="window.open('<?php echo BASE_PATH; ?>/views/habitaciones/generar_pdf_mantenimientos.php', '_blank')" 
                        class="px-4 py-2.5 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl border border-gray-200 dark:border-gray-700 transition font-medium text-sm flex items-center gap-2 shadow-sm">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    PDF
                </button>
                <a href="<?php echo BASE_PATH; ?>/index.php" 
                   class="px-4 py-2.5 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl border border-gray-200 dark:border-gray-700 transition font-medium text-sm text-center shadow-sm">
                    Volver
                </a>
            </div>
        </div>

        <?php if ($mensaje): ?>
        <div id="mensaje-alerta" class="mb-6 p-4 rounded-xl border <?php echo $tipo_mensaje === 'success' ? 'bg-green-50/80 dark:bg-green-950/20 text-green-800 dark:text-green-300 border-green-100 dark:border-green-900/30' : 'bg-red-50/80 dark:bg-red-950/20 text-red-800 dark:text-red-300 border-red-100 dark:border-red-900/30'; ?> transition-all duration-300">
            <span class="text-sm font-semibold"><?php echo $mensaje; ?></span>
        </div>
        <script>
            setTimeout(function() {
                const alerta = document.getElementById('mensaje-alerta');
                if (alerta) {
                    alerta.style.opacity = '0';
                    setTimeout(() => alerta.remove(), 400);
                }
            }, 2500);
        </script>
        <?php endif; ?>

        <!-- Actions Bar -->
        <div class="mb-6 flex justify-end no-print">
            <button onclick="abrirModal()" 
                    class="px-5 py-3 bg-gray-900 dark:bg-gray-100 text-white dark:text-gray-900 font-semibold rounded-xl hover:bg-gray-800 dark:hover:bg-white transition-all text-sm shadow-sm flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                Nuevo Mantenimiento
            </button>
        </div>

        <!-- Mantenimientos List -->
        <?php if (empty($mantenimientos)): ?>
        <div class="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-2xl p-12 text-center shadow-sm">
            <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
            </svg>
            <p class="text-gray-500 dark:text-gray-400 font-medium text-base">No hay mantenimientos activos registrados</p>
            <p class="text-gray-400 dark:text-gray-500 text-xs mt-1">Presiona "Nuevo Mantenimiento" para comenzar.</p>
        </div>
        <?php else: ?>
        
        <!-- Grid View -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4 no-print">
            <?php foreach ($mantenimientos as $mant): ?>
            <button onclick='abrirDetalleMantenimiento(<?php echo json_encode($mant, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)' 
                    class="maint-card min-h-[140px] w-full">
                
                <div class="flex justify-between items-start w-full mb-3">
                    <span class="text-xl sm:text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Hab. <?php echo $mant['habitacion_numero']; ?></span>
                    <?php if ($mant['imagen']): ?>
                    <span class="text-gray-400 dark:text-gray-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    </span>
                    <?php endif; ?>
                </div>
                
                <div class="flex-grow flex flex-col justify-start">
                    <p class="text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 line-clamp-2 leading-snug mb-4">
                        <?php echo htmlspecialchars($mant['titulo']); ?>
                    </p>
                </div>
                
                <div class="w-full flex justify-start items-center">
                    <?php
                        $estado_class = 'badge-pendiente';
                        $estado_label = 'Pendiente';
                        if ($mant['estado'] === 'en_proceso') {
                            $estado_class = 'badge-proceso';
                            $estado_label = 'En proceso';
                        } elseif ($mant['estado'] === 'completado') {
                            $estado_class = 'badge-completado';
                            $estado_label = 'Completado';
                        }
                    ?>
                    <span class="maint-badge <?php echo $estado_class; ?>">
                        <?php echo $estado_label; ?>
                    </span>
                </div>
            </button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Nuevo Mantenimiento -->
<div id="modalMantenimiento" class="modal-overlay" onclick="if(event.target === this) cerrarModal()">
    <div class="modal-box">
        <!-- Header -->
        <div class="p-6 border-b border-gray-100 dark:border-gray-800/80 flex items-center justify-between">
            <h2 class="text-xl font-bold tracking-tight text-gray-900 dark:text-white">Nuevo Mantenimiento</h2>
            <button type="button" onclick="cerrarModal()" class="close-btn">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <!-- Form -->
        <form method="POST" action="" enctype="multipart/form-data" class="flex-grow overflow-y-auto p-6 space-y-5">
            <input type="hidden" name="accion" value="crear">
            
            <!-- Habitación -->
            <div>
                <label class="block text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">
                    Habitación
                </label>
                <select name="habitacion_numero" required class="maint-input">
                    <option value="">Seleccione una habitación</option>
                    <?php foreach ($habitaciones as $hab): ?>
                    <option value="<?php echo $hab['numero']; ?>">
                        Habitación <?php echo $hab['numero']; ?> (<?php echo $hab['tipo']; ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Título -->
            <div>
                <label class="block text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">
                    ¿Qué mantenimiento es?
                </label>
                <input type="text" name="titulo" required 
                       placeholder="Ej. Reparación de grifo, Foco quemado"
                       class="maint-input">
            </div>

            <!-- Descripción -->
            <div>
                <label class="block text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">
                    Mini descripción
                </label>
                <textarea name="descripcion" required rows="3" 
                          placeholder="Describe brevemente los detalles del problema..."
                          class="maint-input resize-none"></textarea>
            </div>

            <!-- Fecha de Registro -->
            <div>
                <label class="block text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">
                    Fecha de registro
                </label>
                <input type="date" name="fecha_registro" value="<?php echo date('Y-m-d'); ?>" required 
                       class="maint-input">
            </div>

            <!-- Fotografía (Opcional) -->
            <div>
                <label class="block text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">
                    Fotografía (Opcional)
                </label>
                <div class="flex items-center gap-3">
                    <label for="imagen-mantenimiento" class="w-full cursor-pointer">
                        <div class="w-full px-4 py-4 border border-dashed border-gray-300 dark:border-gray-700 rounded-xl hover:border-gray-400 dark:hover:border-gray-500 transition-colors bg-gray-50/50 dark:bg-gray-900/30 flex items-center justify-center gap-3">
                            <svg class="w-5 h-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            <span class="text-sm font-medium text-gray-600 dark:text-gray-400" id="filename-display">Subir una imagen</span>
                        </div>
                        <input type="file" id="imagen-mantenimiento" name="imagen" 
                               accept="image/jpeg,image/jpg,image/png" 
                               class="hidden"
                               onchange="document.getElementById('filename-display').textContent = this.files[0] ? this.files[0].name : 'Subir una imagen'">
                    </label>
                </div>
            </div>

            <!-- Footer Buttons inside Box -->
            <div class="flex justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-800/80">
                <button type="button" onclick="cerrarModal()" 
                        class="px-5 py-3 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800 font-medium transition-all text-sm">
                    Cancelar
                </button>
                <button type="submit" 
                        class="px-5 py-3 bg-gray-900 dark:bg-gray-100 text-white dark:text-gray-900 rounded-xl hover:bg-gray-800 dark:hover:bg-white font-semibold transition-all text-sm">
                    Registrar Mantenimiento
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Detalle Mantenimiento -->
<div id="modalDetalle" class="modal-overlay" onclick="if(event.target === this) cerrarDetalle()">
    <div class="modal-box">
        <!-- Header -->
        <div class="p-6 border-b border-gray-100 dark:border-gray-800/80 flex items-center justify-between">
            <div>
                <span class="text-[10px] font-bold tracking-widest text-gray-450 dark:text-gray-500 uppercase">Detalles</span>
                <h2 class="text-xl font-bold tracking-tight text-gray-900 dark:text-white mt-0.5">Habitación <span id="d-numero"></span></h2>
            </div>
            <button type="button" onclick="cerrarDetalle()" class="close-btn">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <!-- Content -->
        <div class="flex-grow overflow-y-auto p-6 space-y-6">
            <!-- Título -->
            <div>
                <span class="block text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1.5">Mantenimiento</span>
                <p class="text-lg font-bold text-gray-900 dark:text-white leading-snug" id="d-titulo"></p>
            </div>

            <!-- Estado -->
            <div>
                <span class="block text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">Estado de atención</span>
                <span id="d-estado-badge" class="maint-badge"></span>
            </div>

            <!-- Descripción -->
            <div class="bg-gray-50 dark:bg-gray-900/30 border border-gray-100/50 dark:border-gray-800/50 rounded-2xl p-4">
                <span class="block text-[11px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">Detalles del problema</span>
                <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed" id="d-descripcion"></p>
            </div>

            <!-- Fecha Registro -->
            <div>
                <span class="block text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1.5">Fecha de registro</span>
                <p class="text-sm font-bold text-gray-800 dark:text-gray-200" id="d-fecha-inicio"></p>
            </div>

            <!-- Fotografía -->
            <div id="d-imagen-container" class="hidden">
                <span class="block text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">Fotografía adjunta</span>
                <div class="bg-gray-50/50 dark:bg-gray-900/30 border border-gray-100 dark:border-gray-800/50 rounded-2xl p-2 flex justify-center">
                    <img id="d-imagen" src="" alt="Imagen" 
                         class="max-h-[220px] w-auto object-cover rounded-xl cursor-pointer hover:opacity-95 transition-opacity shadow-sm"
                         onclick="abrirImagenFullscreen(this.src)">
                </div>
            </div>
        </div>

        <!-- Footer Actions inside Box -->
        <div class="p-6 border-t border-gray-100 dark:border-gray-800/80 flex items-center justify-between">
            <button onclick="eliminarMantenimientoModal()" 
                    class="px-4 py-2.5 bg-red-50 hover:bg-red-100 text-red-650 dark:text-red-400 dark:bg-red-950/20 dark:hover:bg-red-900/30 rounded-xl transition-all font-medium text-xs flex items-center gap-1.5 border border-red-200/40 dark:border-red-900/20">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                Eliminar
            </button>
            <div class="flex gap-2">
                <button id="btn-iniciar" onclick="cambiarEstadoModal('en_proceso')" 
                        class="hidden px-5 py-2.5 bg-gray-900 hover:bg-gray-800 dark:bg-gray-100 dark:hover:bg-white text-white dark:text-gray-900 rounded-xl transition-all font-semibold text-xs flex items-center gap-1.5 shadow-sm">
                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"></path></svg>
                    Iniciar trabajo
                </button>
                <button id="btn-completar" onclick="completarMantenimientoModal()" 
                        class="hidden px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl transition-all font-semibold text-xs flex items-center gap-1.5 shadow-sm">
                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                    Completar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let mantenimientoActual = null;

function abrirModal() {
    document.getElementById('modalMantenimiento').classList.add('active');
}

function cerrarModal() {
    document.getElementById('modalMantenimiento').classList.remove('active');
}

function abrirDetalleMantenimiento(mant) {
    mantenimientoActual = mant;
    
    // Llenar datos
    document.getElementById('d-numero').textContent = mant.habitacion_numero;
    document.getElementById('d-titulo').textContent = mant.titulo;
    document.getElementById('d-descripcion').textContent = mant.descripcion;
    
    // Parseo local de fecha
    const parts = mant.fecha_inicio.split('-');
    if (parts.length === 3) {
        const year = parseInt(parts[0], 10);
        const month = parseInt(parts[1], 10) - 1;
        const day = parseInt(parts[2], 10);
        const localDate = new Date(year, month, day);
        document.getElementById('d-fecha-inicio').textContent = localDate.toLocaleDateString('es-BO', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    } else {
        document.getElementById('d-fecha-inicio').textContent = mant.fecha_inicio;
    }
    
    // Badge de estado
    const badge = document.getElementById('d-estado-badge');
    badge.className = 'maint-badge';
    if (mant.estado === 'en_proceso') {
        badge.classList.add('badge-proceso');
        badge.textContent = 'En proceso';
    } else if (mant.estado === 'completado') {
        badge.classList.add('badge-completado');
        badge.textContent = 'Completado';
    } else {
        badge.classList.add('badge-pendiente');
        badge.textContent = 'Pendiente';
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
    
    document.getElementById('modalDetalle').classList.add('active');
}

function cerrarDetalle() {
    document.getElementById('modalDetalle').classList.remove('active');
    mantenimientoActual = null;
}

function cambiarEstadoModal(nuevoEstado) {
    if (mantenimientoActual && confirm('¿Deseas iniciar el trabajo en esta habitación?')) {
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
        const costo = prompt('Ingrese el costo real del mantenimiento (Bs.) o presione Aceptar para dejarlo en 0:');
        if (costo !== null) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="accion" value="cambiar_estado">
                <input type="hidden" name="id" value="${mantenimientoActual.id}">
                <input type="hidden" name="estado" value="completado">
                <input type="hidden" name="costo_real" value="${costo || 0}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }
}

function eliminarMantenimientoModal() {
    if (mantenimientoActual && confirm('¿Estás seguro de eliminar este registro de mantenimiento?')) {
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
    overlay.className = 'fixed inset-0 bg-black/90 z-[100] flex items-center justify-center p-4 cursor-pointer';
    overlay.onclick = function() { this.remove(); };
    
    const img = document.createElement('img');
    img.src = src;
    img.className = 'max-w-full max-h-full object-contain rounded-xl shadow-2xl';
    img.onclick = function(e) { e.stopPropagation(); };
    
    const closeBtn = document.createElement('button');
    closeBtn.innerHTML = '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>';
    closeBtn.className = 'absolute top-4 right-4 text-white hover:text-gray-300 bg-black/40 rounded-full w-12 h-12 flex items-center justify-center border border-white/10';
    closeBtn.onclick = function() { overlay.remove(); };
    
    overlay.appendChild(img);
    overlay.appendChild(closeBtn);
    document.body.appendChild(overlay);
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
