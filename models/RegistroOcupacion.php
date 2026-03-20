<?php
require_once __DIR__ . '/../config/config.php';

class RegistroOcupacion {
    public $conn; // Hacer público para acceso a errores
    
    public function __construct() {
        $this->conn = getConnection();
    }
    
    public function crear($datos) {
        try {
            $sql = "INSERT INTO registro_ocupacion (huesped_id, habitacion_id, nro_pieza, prox_destino, 
                    via_ingreso, fecha_ingreso, nro_dias, fecha_salida_estimada) 
                    VALUES (:huesped_id, :habitacion_id, :nro_pieza, :prox_destino, :via_ingreso, 
                    :fecha_ingreso, :nro_dias, :fecha_salida_estimada)";
            
            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute([
                ':huesped_id' => $datos['huesped_id'],
                ':habitacion_id' => $datos['habitacion_id'],
                ':nro_pieza' => $datos['nro_pieza'],
                ':prox_destino' => $datos['prox_destino'] ?? null,
                ':via_ingreso' => $datos['via_ingreso'] ?? null,
                ':fecha_ingreso' => $datos['fecha_ingreso'],
                ':nro_dias' => $datos['nro_dias'],
                ':fecha_salida_estimada' => $datos['fecha_salida_estimada']
            ]);
            
            if ($result) {
                $insertId = $this->conn->lastInsertId();
                
                $actualizado = $this->actualizarEstadoHabitacion($datos['habitacion_id'], 'ocupada');
                if (!$actualizado) {
                    error_log("ADVERTENCIA: No se pudo actualizar estado de habitación ID: " . $datos['habitacion_id']);
                }
                
                return $insertId;
            }
            return false;
        } catch (PDOException $e) {
            error_log("Error en RegistroOcupacion::crear: " . $e->getMessage());
            throw $e; // Lanzar la excepción para que el controlador pueda manejarla
        }
    }
    
    public function obtenerActivos() {
        $sql = "SELECT ro.*, h.nombres_apellidos, h.ci_pasaporte, h.genero, h.edad, 
                h.estado_civil, h.nacionalidad, h.profesion, h.objeto, h.procedencia,
                hab.numero as numero_habitacion
                FROM registro_ocupacion ro
                INNER JOIN huespedes h ON ro.huesped_id = h.id
                INNER JOIN habitaciones hab ON ro.habitacion_id = hab.id
                WHERE ro.estado = 'activo'
                ORDER BY ro.fecha_ingreso DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener ocupaciones finalizadas recientemente (últimas 24-48 horas)
     * Útil para reactivar/extender si el huésped regresa el mismo día
     */
    public function obtenerRecientementeFinalizados($horas = 48) {
        $fecha_limite = date('Y-m-d H:i:s', strtotime("-$horas hours"));
        
        $sql = "SELECT ro.*, ro.huesped_id, h.nombres_apellidos, h.ci_pasaporte, h.genero, h.edad, 
                h.estado_civil, h.nacionalidad, h.profesion, h.objeto, h.procedencia,
                hab.numero as numero_habitacion
                FROM registro_ocupacion ro
                INNER JOIN huespedes h ON ro.huesped_id = h.id
                INNER JOIN habitaciones hab ON ro.habitacion_id = hab.id
                WHERE ro.estado = 'finalizado'
                AND ro.fecha_salida_real >= :fecha_limite
                ORDER BY ro.fecha_salida_real DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':fecha_limite' => $fecha_limite]);
        return $stmt->fetchAll();
    }
    
    public function obtenerTodos($limit = 100) {
        $sql = "SELECT ro.*, h.nombres_apellidos, h.ci_pasaporte, hab.numero as numero_habitacion
                FROM registro_ocupacion ro
                INNER JOIN huespedes h ON ro.huesped_id = h.id
                INNER JOIN habitaciones hab ON ro.habitacion_id = hab.id
                ORDER BY ro.fecha_ingreso DESC
                LIMIT :limit";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function finalizarOcupacion($id, $fecha_salida_real, $cambiar_estado = true) {
        $sql = "UPDATE registro_ocupacion SET 
                estado = 'finalizado',
                fecha_salida_real = :fecha_salida
                WHERE id = :id";
        
        $stmt = $this->conn->prepare($sql);
        $result = $stmt->execute([
            ':id' => $id,
            ':fecha_salida' => $fecha_salida_real
        ]);
        
        // Solo cambiar estado de habitación si se especifica
        if ($result && $cambiar_estado) {
            $ocupacion = $this->obtenerPorId($id);
            if ($ocupacion) {
                // Verificar si hay otros huéspedes activos en la misma habitación
                $sql = "SELECT COUNT(*) as total FROM registro_ocupacion 
                        WHERE habitacion_id = :habitacion_id 
                        AND estado = 'activo' 
                        AND id != :id_actual";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([
                    ':habitacion_id' => $ocupacion['habitacion_id'],
                    ':id_actual' => $id
                ]);
                $otros_huespedes = $stmt->fetch();
                
                // Si hay otros huéspedes activos, mantener habitación "ocupada"
                // Si no hay más huéspedes activos, cambiar a "limpieza"
                if ($otros_huespedes['total'] > 0) {
                    // Mantener como ocupada porque hay más huéspedes
                    $this->actualizarEstadoHabitacion($ocupacion['habitacion_id'], 'ocupada');
                } else {
                    // Cambiar a limpieza porque ya no hay huéspedes activos
                    $this->actualizarEstadoHabitacion($ocupacion['habitacion_id'], 'limpieza');
                }
            }
        }
        
        return $result;
    }
    
    public function obtenerPorId($id) {
        $sql = "SELECT * FROM registro_ocupacion WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }
    
    public function actualizar($id, $datos) {
        try {
            $sql = "UPDATE registro_ocupacion SET 
                    nro_pieza = :nro_pieza,
                    prox_destino = :prox_destino,
                    via_ingreso = :via_ingreso,
                    nro_dias = :nro_dias,
                    fecha_salida_estimada = DATE_ADD(fecha_ingreso, INTERVAL :nro_dias_calc DAY)
                    WHERE id = :id";
            
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                ':id' => $id,
                ':nro_pieza' => $datos['nro_pieza'],
                ':prox_destino' => $datos['prox_destino'] ?? null,
                ':via_ingreso' => $datos['via_ingreso'] ?? null,
                ':nro_dias' => $datos['nro_dias'],
                ':nro_dias_calc' => $datos['nro_dias']
            ]);
        } catch (PDOException $e) {
            error_log("Error en RegistroOcupacion::actualizar: " . $e->getMessage());
            throw $e;
        }
    }
    
    private function actualizarEstadoHabitacion($habitacion_id, $estado) {
        $sql = "UPDATE habitaciones SET estado = :estado WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([':estado' => $estado, ':id' => $habitacion_id]);
    }
    
    /**
     * Verifica automáticamente las fechas de salida estimadas
     * y cambia habitaciones a "limpieza" cuando el huésped debe salir
     * Considera la hora de checkout a las 12:00 PM (mediodía)
     */
    public function verificarSalidasAutomaticas() {
        try {
            // Obtener ocupaciones activas donde la fecha y hora de salida ya pasaron
            // Se agrega 12 horas (mediodía) a la fecha de salida estimada para el checkout
            $sql = "SELECT ro.*, hab.id as habitacion_id 
                    FROM registro_ocupacion ro
                    INNER JOIN habitaciones hab ON ro.habitacion_id = hab.id
                    WHERE ro.estado = 'activo' 
                    AND DATE_ADD(ro.fecha_salida_estimada, INTERVAL 12 HOUR) <= NOW()";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $ocupaciones_vencidas = $stmt->fetchAll();
            
            foreach ($ocupaciones_vencidas as $ocupacion) {
                // Finalizar la ocupación
                // finalizarOcupacion() ya se encarga de verificar si hay otros huéspedes
                // y cambiar el estado apropiadamente (ocupada o limpieza)
                $this->finalizarOcupacion($ocupacion['id'], date('Y-m-d'), true);
            }
            
            return count($ocupaciones_vencidas);
        } catch (PDOException $e) {
            error_log("Error en verificarSalidasAutomaticas: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Buscar registro reciente de un huésped en una habitación específica
     * Útil para detectar si se debe extender estadía en lugar de crear nuevo registro
     */
    public function buscarRegistroReciente($huesped_id, $habitacion_id, $dias_atras = 2) {
        try {
            $fecha_limite = date('Y-m-d', strtotime("-$dias_atras days"));
            
            $sql = "SELECT * FROM registro_ocupacion 
                    WHERE huesped_id = :huesped_id 
                    AND habitacion_id = :habitacion_id
                    AND fecha_salida_estimada >= :fecha_limite
                    ORDER BY fecha_salida_estimada DESC 
                    LIMIT 1";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':huesped_id' => $huesped_id,
                ':habitacion_id' => $habitacion_id,
                ':fecha_limite' => $fecha_limite
            ]);
            
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error en buscarRegistroReciente: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Obtener todos los huéspedes activos de una habitación
     */
    public function obtenerHuespedesActivosPorHabitacion($habitacion_id) {
        try {
            $sql = "SELECT ro.*, h.nombres_apellidos, h.ci_pasaporte, h.genero, h.edad, 
                    hab.numero, hab.precio_dia
                    FROM registro_ocupacion ro
                    INNER JOIN huespedes h ON ro.huesped_id = h.id
                    INNER JOIN habitaciones hab ON ro.habitacion_id = hab.id
                    WHERE ro.habitacion_id = :habitacion_id 
                    AND ro.estado = 'activo'
                    ORDER BY ro.fecha_ingreso ASC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':habitacion_id' => $habitacion_id]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error en obtenerHuespedesActivosPorHabitacion: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Extender la estadía de UNA HABITACIÓN (todos los huéspedes)
     * Actualiza fecha_salida_estimada, nro_dias y reactiva si está finalizado
     * TODOS los huéspedes de la habitación se extienden juntos
     */
    public function extenderEstadiaHabitacion($habitacion_id, $dias_adicionales) {
        try {
            $this->conn->beginTransaction();
            
            // Obtener TODOS los registros activos O finalizados recientes de esta habitación
            $sql = "SELECT * FROM registro_ocupacion 
                    WHERE habitacion_id = :habitacion_id 
                    AND (estado = 'activo' OR 
                         (estado = 'finalizado' AND fecha_salida_real >= DATE_SUB(NOW(), INTERVAL 48 HOUR)))
                    ORDER BY fecha_ingreso ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':habitacion_id' => $habitacion_id]);
            $ocupaciones = $stmt->fetchAll();
            
            if (empty($ocupaciones)) {
                throw new Exception("No hay huéspedes en esta habitación para extender");
            }
            
            // Usar la fecha de salida del primer registro (todos deberían tener la misma)
            $fecha_salida_actual = $ocupaciones[0]['fecha_salida_estimada'];
            $nueva_fecha_salida = date('Y-m-d', strtotime($fecha_salida_actual . " +$dias_adicionales days"));
            
            // Actualizar TODOS los registros de la habitación (activos y finalizados recientes)
            $sql = "UPDATE registro_ocupacion 
                    SET nro_dias = nro_dias + :dias_adicionales,
                        fecha_salida_estimada = :fecha_salida_estimada,
                        estado = 'activo',
                        fecha_salida_real = NULL
                    WHERE habitacion_id = :habitacion_id 
                    AND (estado = 'activo' OR 
                         (estado = 'finalizado' AND fecha_salida_real >= DATE_SUB(NOW(), INTERVAL 48 HOUR)))";
            
            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute([
                ':dias_adicionales' => $dias_adicionales,
                ':fecha_salida_estimada' => $nueva_fecha_salida,
                ':habitacion_id' => $habitacion_id
            ]);
            
            if ($result) {
                // Asegurar que la habitación esté en estado 'ocupada'
                $this->actualizarEstadoHabitacion($habitacion_id, 'ocupada');
                
                $this->conn->commit();
                
                $nuevo_total_dias = $ocupaciones[0]['nro_dias'] + $dias_adicionales;
                $huespedes_actualizados = count($ocupaciones);
                
                return [
                    'success' => true,
                    'nueva_fecha_salida' => $nueva_fecha_salida,
                    'total_dias' => $nuevo_total_dias,
                    'huespedes_actualizados' => $huespedes_actualizados
                ];
            }
            
            $this->conn->rollBack();
            return ['success' => false, 'error' => 'No se pudo actualizar los registros'];
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Error en extenderEstadiaHabitacion: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * @deprecated Use extenderEstadiaHabitacion() en su lugar
     * Mantener por compatibilidad, pero redirige al nuevo método
     */
    public function extenderEstadia($ocupacion_id, $dias_adicionales) {
        try {
            // Obtener la habitación de esta ocupación
            $sql = "SELECT habitacion_id FROM registro_ocupacion WHERE id = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':id' => $ocupacion_id]);
            $ocupacion = $stmt->fetch();
            
            if (!$ocupacion) {
                throw new Exception("Ocupación no encontrada");
            }
            
            // Usar el nuevo método que extiende toda la habitación
            return $this->extenderEstadiaHabitacion($ocupacion['habitacion_id'], $dias_adicionales);
            
        } catch (Exception $e) {
            error_log("Error en extenderEstadia: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
?>
