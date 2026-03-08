<?php
/**
 * Modelo para gestionar turnos de recepcionistas
 * 
 * Controla qué recepcionista está actualmente en turno y 
 * mantiene el historial de cambios de turno.
 */

require_once __DIR__ . '/../config/config.php';

class TurnoRecepcionista {
    private $conn;
    
    // Recepcionistas disponibles
    const RECEPCIONISTAS = [
        'Isaac Vargas',
        'Gabriel Duran'
    ];
    
    public function __construct() {
        $this->conn = getConnection();
    }
    
    /**
     * Obtener el turno activo actual
     * @return array|false Datos del turno activo o false si no hay ninguno
     */
    public function obtenerTurnoActivo() {
        $sql = "SELECT * FROM turno_recepcionista 
                WHERE activo = 1 
                ORDER BY fecha_inicio DESC 
                LIMIT 1";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetch();
    }
    
    /**
     * Obtener el nombre del recepcionista actualmente en turno
     * @return string Nombre del recepcionista o 'Isaac Vargas' por defecto
     */
    public function obtenerRecepcionistaActivo() {
        $turno = $this->obtenerTurnoActivo();
        return $turno ? $turno['recepcionista_nombre'] : 'Isaac Vargas';
    }
    
    /**
     * Cambiar el turno al nuevo recepcionista
     * @param string $nuevo_recepcionista Nombre del nuevo recepcionista
     * @param string $observaciones Observaciones opcionales del cambio de turno
     * @return bool True si el cambio fue exitoso
     */
    public function cambiarTurno($nuevo_recepcionista, $observaciones = null) {
        try {
            $this->conn->beginTransaction();
            
            // Validar que el recepcionista esté en la lista
            if (!in_array($nuevo_recepcionista, self::RECEPCIONISTAS)) {
                throw new Exception("Recepcionista no válido: $nuevo_recepcionista");
            }
            
            // Verificar si el recepcionista ya está en turno
            $turno_actual = $this->obtenerTurnoActivo();
            if ($turno_actual && $turno_actual['recepcionista_nombre'] === $nuevo_recepcionista) {
                // Ya está en turno, no hacer nada
                $this->conn->rollBack();
                return true;
            }
            
            // Cerrar el turno actual
            if ($turno_actual) {
                $sql = "UPDATE turno_recepcionista 
                        SET activo = 0, fecha_fin = NOW() 
                        WHERE id = :id";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([':id' => $turno_actual['id']]);
            }
            
            // Crear el nuevo turno
            $sql = "INSERT INTO turno_recepcionista 
                    (recepcionista_nombre, fecha_inicio, activo, observaciones)
                    VALUES (:recepcionista, NOW(), 1, :observaciones)";
            
            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute([
                ':recepcionista' => $nuevo_recepcionista,
                ':observaciones' => $observaciones
            ]);
            
            $this->conn->commit();
            return $result;
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Error en cambiarTurno: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener historial de turnos
     * @param int $limit Número de registros a obtener
     * @return array Lista de turnos
     */
    public function obtenerHistorial($limit = 50) {
        $sql = "SELECT * FROM turno_recepcionista 
                ORDER BY fecha_inicio DESC 
                LIMIT :limit";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener estadísticas del turno activo
     * @return array Estadísticas del turno (ingresos, egresos, balance)
     */
    public function obtenerEstadisticasTurnoActivo() {
        $turno = $this->obtenerTurnoActivo();
        if (!$turno) {
            return [
                'total_efectivo' => 0,
                'total_qr' => 0,
                'total_egresos' => 0,
                'balance' => 0,
                'num_transacciones' => 0
            ];
        }
        
        $fecha_inicio = $turno['fecha_inicio'];
        $recepcionista = $turno['recepcionista_nombre'];
        
        // Calcular ingresos en efectivo
        $sql = "SELECT COALESCE(SUM(monto), 0) as total FROM ingresos 
                WHERE metodo_pago = 'efectivo' 
                AND recepcionista = :recepcionista
                AND CONCAT(fecha, ' ', COALESCE(hora, '00:00:00')) >= :fecha_inicio";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':recepcionista' => $recepcionista, ':fecha_inicio' => $fecha_inicio]);
        $result = $stmt->fetch();
        $total_efectivo = floatval($result['total']);
        
        // Calcular ingresos por QR
        $sql = "SELECT COALESCE(SUM(monto), 0) as total FROM ingresos 
                WHERE metodo_pago = 'qr' 
                AND recepcionista = :recepcionista
                AND CONCAT(fecha, ' ', COALESCE(hora, '00:00:00')) >= :fecha_inicio";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':recepcionista' => $recepcionista, ':fecha_inicio' => $fecha_inicio]);
        $result = $stmt->fetch();
        $total_qr = floatval($result['total']);
        
        // Calcular egresos
        $sql = "SELECT COALESCE(SUM(monto), 0) as total FROM egresos 
                WHERE recepcionista = :recepcionista
                AND CONCAT(fecha, ' ', COALESCE(hora, '00:00:00')) >= :fecha_inicio";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':recepcionista' => $recepcionista, ':fecha_inicio' => $fecha_inicio]);
        $result = $stmt->fetch();
        $total_egresos = floatval($result['total']);
        
        // Contar transacciones
        $sql = "SELECT 
                (SELECT COUNT(*) FROM ingresos WHERE recepcionista = :recepcionista1 
                 AND CONCAT(fecha, ' ', COALESCE(hora, '00:00:00')) >= :fecha_inicio1) +
                (SELECT COUNT(*) FROM egresos WHERE recepcionista = :recepcionista2 
                 AND CONCAT(fecha, ' ', COALESCE(hora, '00:00:00')) >= :fecha_inicio2) as total";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':recepcionista1' => $recepcionista,
            ':fecha_inicio1' => $fecha_inicio,
            ':recepcionista2' => $recepcionista,
            ':fecha_inicio2' => $fecha_inicio
        ]);
        $result = $stmt->fetch();
        $num_transacciones = intval($result['total']);
        
        return [
            'recepcionista' => $recepcionista,
            'fecha_inicio' => $fecha_inicio,
            'total_efectivo' => $total_efectivo,
            'total_qr' => $total_qr,
            'total_egresos' => $total_egresos,
            'balance' => ($total_efectivo + $total_qr) - $total_egresos,
            'num_transacciones' => $num_transacciones
        ];
    }
    
    /**
     * Obtener la lista de recepcionistas disponibles
     * @return array Lista de nombres de recepcionistas
     */
    public static function obtenerRecepcionistas() {
        return self::RECEPCIONISTAS;
    }
    
    /**
     * Verificar si un turno está activo hace más de 24 horas (alerta)
     * @return bool True si el turno lleva más de 24 horas
     */
    public function turnoExcedido() {
        $turno = $this->obtenerTurnoActivo();
        if (!$turno) {
            return false;
        }
        
        $fecha_inicio = strtotime($turno['fecha_inicio']);
        $ahora = time();
        $horas_transcurridas = ($ahora - $fecha_inicio) / 3600;
        
        return $horas_transcurridas > 24;
    }
}
?>
