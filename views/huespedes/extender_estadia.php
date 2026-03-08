<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/RegistroOcupacion.php';
require_once __DIR__ . '/../../models/Habitacion.php';
require_once __DIR__ . '/../../models/Finanzas.php';

$page_title = 'Extender Estadía - Por Habitación';
$mensaje = '';
$tipo_mensaje = '';

// Verificar que se recibió un ID de ocupación
if (!isset($_GET['id']) && !isset($_POST['habitacion_id'])) {
    header('Location: activos.php');
    exit;
}

$registroModel = new RegistroOcupacion();
$finanzasModel = new Finanzas();

// Obtener habitacion_id (desde ocupacion_id o directamente)
if (isset($_POST['habitacion_id'])) {
    $habitacion_id = (int)$_POST['habitacion_id'];
} else {
    $ocupacion_id = (int)$_GET['id'];
    // Obtener habitacion_id desde la ocupación
    $sql = "SELECT habitacion_id FROM registro_ocupacion WHERE id = :id";
    $stmt = $registroModel->conn->prepare($sql);
    $stmt->execute([':id' => $ocupacion_id]);
    $temp = $stmt->fetch();
    if (!$temp) {
        header('Location: activos.php');
        exit;
    }
    $habitacion_id = $temp['habitacion_id'];
}

// Obtener TODOS los huéspedes de esta habitación (activos o finalizados recientes)
$huespedes = $registroModel->obtenerHuespedesActivosPorHabitacion($habitacion_id);

// Si no hay activos, intentar obtener finalizados recientes de esa habitación
if (empty($huespedes)) {
    $sql = "SELECT ro.*, h.nombres_apellidos, h.ci_pasaporte, h.genero, h.edad, 
            hab.numero, hab.precio_dia
            FROM registro_ocupacion ro
            INNER JOIN huespedes h ON ro.huesped_id = h.id
            INNER JOIN habitaciones hab ON ro.habitacion_id = hab.id
            WHERE ro.habitacion_id = :habitacion_id 
            AND ro.estado = 'finalizado'
            AND ro.fecha_salida_real >= DATE_SUB(NOW(), INTERVAL 48 HOUR)
            ORDER BY ro.fecha_ingreso ASC";
    
    $stmt = $registroModel->conn->prepare($sql);
    $stmt->execute([':habitacion_id' => $habitacion_id]);
    $huespedes = $stmt->fetchAll();
}

if (empty($huespedes)) {
    header('Location: activos.php');
    exit;
}

// Usar el primer huésped para obtener datos comunes de la habitación
$habitacion = $huespedes[0];

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $dias_adicionales = (int)$_POST['dias_adicionales'];
        $metodo_pago = $_POST['metodo_pago'];
        
        if ($dias_adicionales < 1) {
            throw new Exception('Debe especificar al menos 1 día adicional');
        }
        
        // Extender la estadía de TODA LA HABITACIÓN (todos los huéspedes)
        $resultado = $registroModel->extenderEstadiaHabitacion($habitacion_id, $dias_adicionales);
        
        if ($resultado['success']) {
            // Calcular monto - EL PRECIO SE COBRA UNA SOLA VEZ POR HABITACIÓN
            $monto_a_pagar = $habitacion['precio_dia'] * $dias_adicionales;
            
            // Aplicar descuento si existe
            $descuento = 0;
            $motivo_descuento = '';
            if (isset($_POST['descuento']) && !empty($_POST['descuento'])) {
                $descuento = floatval($_POST['descuento']);
                $monto_a_pagar = $monto_a_pagar - $descuento;
                $motivo_descuento = isset($_POST['motivo_descuento']) && !empty($_POST['motivo_descuento']) 
                    ? clean_input($_POST['motivo_descuento']) 
                    : 'Descuento aplicado';
            }
            
            // Preparar concepto con todos los huéspedes
            $nombres_huespedes = array_map(function($h) { return $h['nombres_apellidos']; }, $huespedes);
            $lista_huespedes = implode(', ', $nombres_huespedes);
            
            $concepto = "Extensión de estadía - Hab. {$habitacion['numero']} - {$lista_huespedes} ({$resultado['huespedes_actualizados']} " . 
                        ($resultado['huespedes_actualizados'] == 1 ? 'huésped' : 'huéspedes') . 
                        ", {$dias_adicionales} " . ($dias_adicionales == 1 ? 'día' : 'días') . ")";
            
            if ($descuento > 0) {
                $concepto .= " (Descuento: Bs. " . number_format($descuento, 2) . " - {$motivo_descuento})";
            }
            
            // Registrar ingreso financiero (UNA SOLA VEZ)
            $datos_ingreso = [
                'concepto' => $concepto,
                'monto' => $monto_a_pagar,
                'fecha' => date('Y-m-d'),
                'metodo_pago' => $metodo_pago,
                'categoria' => 'alojamiento'
            ];
            
            $ingreso_id = $finanzasModel->registrarIngreso($datos_ingreso);
            
            if ($ingreso_id) {
                $mensaje = "Estadía extendida exitosamente para {$resultado['huespedes_actualizados']} " . 
                          ($resultado['huespedes_actualizados'] == 1 ? 'huésped' : 'huéspedes') . 
                          ". Nueva fecha de salida: " . date('d/m/Y', strtotime($resultado['nueva_fecha_salida']));
                $tipo_mensaje = 'success';
                
                // Redirigir después de 2 segundos
                header("refresh:2;url=activos.php");
            } else {
                throw new Exception('Error al registrar el pago');
            }
        } else {
            throw new Exception($resultado['error'] ?? 'Error al extender estadía');
        }
        
    } catch (Exception $e) {
        $mensaje = 'Error: ' . $e->getMessage();
        $tipo_mensaje = 'danger';
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-3xl mx-auto">
        <!-- Título -->
        <div class="mb-6">
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white">
                <i class="fas fa-calendar-plus text-blue-600 dark:text-blue-400 mr-2"></i>
                Extender Estadía - Habitación <?php echo $habitacion['numero']; ?>
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Todos los huéspedes de la habitación se extenderán juntos • Pago único por habitación
            </p>
        </div>

        <?php if ($habitacion['estado'] === 'finalizado'): ?>
        <div class="mb-6 p-4 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800">
            <div class="flex items-start">
                <i class="fas fa-info-circle text-blue-600 dark:text-blue-400 mt-0.5 mr-3"></i>
                <div>
                    <p class="text-sm font-medium text-blue-800 dark:text-blue-200">Reactivando estadía</p>
                    <p class="text-sm text-blue-700 dark:text-blue-300 mt-1">
                        Esta ocupación está finalizada. Al extender, se reactivará automáticamente y la habitación volverá a estado "ocupada".
                    </p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($mensaje): ?>
        <div class="mb-6 p-4 rounded-lg <?php echo $tipo_mensaje === 'success' ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800' : 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800'; ?>">
            <p class="<?php echo $tipo_mensaje === 'success' ? 'text-green-800 dark:text-green-200' : 'text-red-800 dark:text-red-200'; ?>">
                <i class="fas <?php echo $tipo_mensaje === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?> mr-2"></i>
                <?php echo $mensaje; ?>
            </p>
        </div>
        <?php endif; ?>

        <!-- Información de la Habitación y Huéspedes -->
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                <i class="fas fa-door-open text-blue-500 mr-2"></i>
                Información de la Habitación
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm mb-6">
                <div>
                    <p class="text-gray-500 dark:text-gray-400">Habitación</p>
                    <p class="font-semibold text-gray-900 dark:text-white text-lg"><?php echo $habitacion['numero']; ?></p>
                </div>
                <div>
                    <p class="text-gray-500 dark:text-gray-400">Precio por Noche</p>
                    <p class="font-semibold text-gray-900 dark:text-white">Bs. <?php echo number_format($habitacion['precio_dia'], 2); ?></p>
                </div>
                <div>
                    <p class="text-gray-500 dark:text-gray-400">Fecha de Ingreso</p>
                    <p class="font-semibold text-gray-900 dark:text-white"><?php echo date('d/m/Y', strtotime($habitacion['fecha_ingreso'])); ?></p>
                </div>
                <div>
                    <p class="text-gray-500 dark:text-gray-400">Fecha de Salida Actual</p>
                    <p class="font-semibold text-red-600 dark:text-red-400"><?php echo date('d/m/Y', strtotime($habitacion['fecha_salida_estimada'])); ?></p>
                </div>
                <div>
                    <p class="text-gray-500 dark:text-gray-400">Días Actuales</p>
                    <p class="font-semibold text-gray-900 dark:text-white"><?php echo $habitacion['nro_dias']; ?> <?php echo $habitacion['nro_dias'] == 1 ? 'día' : 'días'; ?></p>
                </div>
                <div>
                    <p class="text-gray-500 dark:text-gray-400">Nro. de Huéspedes</p>
                    <p class="font-semibold text-blue-600 dark:text-blue-400">
                        <i class="fas fa-users mr-1"></i>
                        <?php echo count($huespedes); ?> <?php echo count($huespedes) == 1 ? 'huésped' : 'huéspedes'; ?>
                    </p>
                </div>
            </div>

            <!-- Lista de Huéspedes -->
            <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                    <i class="fas fa-users text-gray-400 mr-2"></i>
                    Huéspedes en esta habitación:
                </p>
                <div class="space-y-2">
                    <?php foreach ($huespedes as $idx => $huesped): ?>
                    <div class="flex items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                        <div class="flex-shrink-0 w-8 h-8 flex items-center justify-center bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-300 rounded-full font-semibold text-sm">
                            <?php echo $idx + 1; ?>
                        </div>
                        <div class="ml-3 flex-1">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                <?php echo htmlspecialchars($huesped['nombres_apellidos']); ?>
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                CI: <?php echo htmlspecialchars($huesped['ci_pasaporte']); ?> · 
                                <?php echo $huesped['genero'] == 'M' ? 'Masculino' : 'Femenino'; ?> · 
                                <?php echo $huesped['edad']; ?> años
                            </p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Formulario de Extensión -->
        <form method="POST" class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <input type="hidden" name="habitacion_id" value="<?php echo $habitacion_id; ?>">
            
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                <i class="fas fa-calendar-plus text-green-500 mr-2"></i>
                Datos de la Extensión
            </h2>

            <div class="space-y-4">
                <!-- Días Adicionales -->
                <div>
                    <label for="dias_adicionales" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Días Adicionales <span class="text-red-500">*</span>
                    </label>
                    <input type="number" 
                           id="dias_adicionales" 
                           name="dias_adicionales" 
                           min="1" 
                           max="30"
                           required
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                           oninput="calcularTotal()">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        ¿Cuántos días más se quedará el huésped?
                    </p>
                </div>

                <!-- Nueva Fecha de Salida (calculada) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Nueva Fecha de Salida
                    </label>
                    <div id="nueva_fecha_salida" class="px-4 py-2 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg text-blue-900 dark:text-blue-200 font-semibold">
                        Ingrese los días adicionales
                    </div>
                </div>

                <!-- Total de Días -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Total de Días
                    </label>
                    <div id="total_dias" class="px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white font-semibold">
                        <?php echo $habitacion['nro_dias']; ?> días actuales
                    </div>
                </div>

                <!-- Monto a Pagar -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Monto a Pagar por Extensión
                    </label>
                    <div id="monto_pagar" class="px-4 py-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                        <span class="text-2xl font-bold text-green-700 dark:text-green-300">Bs. 0.00</span>
                    </div>
                </div>

                <!-- Descuento (Opcional) -->
                <div>
                    <label for="descuento" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Descuento (Opcional)
                    </label>
                    <input type="number" 
                           id="descuento" 
                           name="descuento" 
                           min="0" 
                           step="0.01"
                           placeholder="0.00"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                           oninput="calcularTotal()">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Monto del descuento en Bs.
                    </p>
                </div>

                <!-- Motivo del Descuento -->
                <div id="motivo_div" style="display: none;">
                    <label for="motivo_descuento" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Motivo del Descuento
                    </label>
                    <input type="text" 
                           id="motivo_descuento" 
                           name="motivo_descuento" 
                           placeholder="Ej: Cliente frecuente, Estadía larga, etc."
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                </div>

                <!-- Monto Final -->
                <div id="monto_final_div" style="display: none;">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Monto Final a Pagar
                    </label>
                    <div id="monto_final" class="px-4 py-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                        <span class="text-2xl font-bold text-yellow-700 dark:text-yellow-300">Bs. 0.00</span>
                    </div>
                </div>

                <!-- Método de Pago -->
                <div>
                    <label for="metodo_pago" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Método de Pago <span class="text-red-500">*</span>
                    </label>
                    <select id="metodo_pago" 
                            name="metodo_pago" 
                            required
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                        <option value="">Seleccione...</option>
                        <option value="efectivo">💵 Efectivo</option>
                        <option value="qr">📱 QR (Don Rodolfo)</option>
                        <option value="pendiente">⏳ Pago Pendiente</option>
                    </select>
                </div>
            </div>

            <!-- Botones -->
            <div class="flex flex-col sm:flex-row gap-3 mt-6">
                <button type="submit" 
                        class="flex-1 bg-green-600 hover:bg-green-700 text-white font-semibold py-2.5 px-4 rounded-lg transition-colors">
                    <i class="fas fa-check mr-2"></i>
                    Confirmar Extensión
                </button>
                <a href="activos.php" 
                   class="flex-1 bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2.5 px-4 rounded-lg text-center transition-colors">
                    <i class="fas fa-times mr-2"></i>
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

<script>
const precioNoche = <?php echo $habitacion['precio_dia']; ?>;
const fechaSalidaActual = new Date('<?php echo $habitacion['fecha_salida_estimada']; ?>');
const diasActuales = <?php echo $habitacion['nro_dias']; ?>;
const numHuespedes = <?php echo count($huespedes); ?>;

function calcularTotal() {
    const diasAdicionales = parseInt(document.getElementById('dias_adicionales').value) || 0;
    const descuento = parseFloat(document.getElementById('descuento').value) || 0;
    
    // Mostrar/ocultar campo de motivo del descuento
    if (descuento > 0) {
        document.getElementById('motivo_div').style.display = 'block';
        document.getElementById('monto_final_div').style.display = 'block';
    } else {
        document.getElementById('motivo_div').style.display = 'none';
        document.getElementById('monto_final_div').style.display = 'none';
    }
    
    if (diasAdicionales > 0) {
        // Calcular nueva fecha
        const nuevaFecha = new Date(fechaSalidaActual);
        nuevaFecha.setDate(nuevaFecha.getDate() + diasAdicionales);
        
        const opciones = { year: 'numeric', month: '2-digit', day: '2-digit' };
        const fechaFormateada = nuevaFecha.toLocaleDateString('es-BO', opciones);
        
        document.getElementById('nueva_fecha_salida').innerHTML = 
            `<i class="fas fa-calendar-check mr-2"></i>${fechaFormateada}`;
        
        // Total de días
        const totalDias = diasActuales + diasAdicionales;
        document.getElementById('total_dias').innerHTML = 
            `<i class="fas fa-moon mr-2"></i>${totalDias} ${totalDias === 1 ? 'día' : 'días'} (${diasActuales} + ${diasAdicionales})`;
        
        // Monto a pagar - PRECIO ÚNICO POR HABITACIÓN (no por huésped)
        const monto = precioNoche * diasAdicionales;
        document.getElementById('monto_pagar').innerHTML = 
            `<span class="text-2xl font-bold text-green-700 dark:text-green-300">Bs. ${monto.toFixed(2)}</span>
             <span class="text-xs text-green-600 dark:text-green-400 ml-2">(${diasAdicionales} ${diasAdicionales === 1 ? 'noche' : 'noches'} × Bs. ${precioNoche.toFixed(2)})</span>
             <p class="text-xs text-green-600 dark:text-green-400 mt-1">✓ Precio único para los ${numHuespedes} ${numHuespedes === 1 ? 'huésped' : 'huéspedes'}</p>`;
        
        // Monto final con descuento
        if (descuento > 0) {
            const montoFinal = monto - descuento;
            if (montoFinal < 0) {
                document.getElementById('monto_final').innerHTML = 
                    `<span class="text-lg font-bold text-red-700 dark:text-red-300">⚠️ El descuento no puede ser mayor al monto</span>`;
            } else {
                document.getElementById('monto_final').innerHTML = 
                    `<span class="text-2xl font-bold text-yellow-700 dark:text-yellow-300">Bs. ${montoFinal.toFixed(2)}</span>
                     <span class="text-xs text-yellow-600 dark:text-yellow-400 ml-2">(Bs. ${monto.toFixed(2)} - Bs. ${descuento.toFixed(2)})</span>`;
            }
        }
    } else {
        document.getElementById('nueva_fecha_salida').textContent = 'Ingrese los días adicionales';
        document.getElementById('total_dias').innerHTML = `${diasActuales} días actuales`;
        document.getElementById('monto_pagar').innerHTML = 
            '<span class="text-2xl font-bold text-green-700 dark:text-green-300">Bs. 0.00</span>';
    }
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
