<?php
require_once __DIR__ . '/../config/config.php';

class CierreCaja {
    private $conn;
    
    public function __construct() {
        $this->conn = getConnection();
    }
    
    /**
     * Obtener la fecha de apertura actual (última fecha de cierre o inicio del sistema)
     */
    public function obtenerFechaAperturaActual() {
        $sql = "SELECT fecha_cierre as ultima_apertura FROM cierres_caja 
                ORDER BY fecha_cierre DESC LIMIT 1";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch();
        
        if ($result) {
            return $result['ultima_apertura'];
        }
        
        // Si no hay cierres previos, usar fecha actual (caja en 0)
        return date('Y-m-d H:i:s');
    }
    
    /**
     * Verificar si hay una caja abierta actualmente
     */
    public function hayCajaAbierta() {
        $fecha_apertura = $this->obtenerFechaAperturaActual();
        
        // Si hay movimientos después del último cierre, la caja está abierta
        $sql = "SELECT COUNT(*) as total FROM ingresos 
                WHERE CONCAT(fecha, ' ', COALESCE(hora, '00:00:00')) > :fecha_apertura";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':fecha_apertura' => $fecha_apertura]);
        $result = $stmt->fetch();
        
        return intval($result['total']) > 0;
    }
    
    /**
     * Obtener el saldo final del último cierre (lo que dejó el recepcionista anterior)
     */
    public function obtenerSaldoInicial() {
        $sql = "SELECT balance_efectivo FROM cierres_caja 
                ORDER BY fecha_cierre DESC LIMIT 1";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch();
        
        return $result ? floatval($result['balance_efectivo']) : 0;
    }
    
    /**
     * Calcular el resumen actual desde la última apertura
     */
    public function calcularResumenActual() {
        $fecha_apertura = $this->obtenerFechaAperturaActual();
        $fecha_actual = date('Y-m-d H:i:s');
        
        $resumen = [
            'fecha_apertura' => $fecha_apertura,
            'fecha_actual' => $fecha_actual,
            'total_efectivo' => 0,
            'total_qr' => 0,
            'total_egresos' => 0,
            'balance_efectivo' => 0,
            'balance_total' => 0,
            'detalles_ingresos' => [],
            'detalles_egresos' => [],
            'por_recepcionista' => []
        ];
        
        // Calcular ingresos en efectivo
        $sql = "SELECT SUM(monto) as total FROM ingresos 
                WHERE metodo_pago = 'efectivo' 
                AND DATE(fecha) >= DATE(:fecha_apertura)
                AND CONCAT(fecha, ' ', COALESCE(hora, '00:00:00')) >= :fecha_apertura_completa";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':fecha_apertura' => date('Y-m-d', strtotime($fecha_apertura)),
            ':fecha_apertura_completa' => $fecha_apertura
        ]);
        $result = $stmt->fetch();
        $resumen['total_efectivo'] = floatval($result['total'] ?? 0);
        
        // Calcular ingresos por QR
        $sql = "SELECT SUM(monto) as total FROM ingresos 
                WHERE metodo_pago = 'qr' 
                AND DATE(fecha) >= DATE(:fecha_apertura)
                AND CONCAT(fecha, ' ', COALESCE(hora, '00:00:00')) >= :fecha_apertura_completa";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':fecha_apertura' => date('Y-m-d', strtotime($fecha_apertura)),
            ':fecha_apertura_completa' => $fecha_apertura
        ]);
        $result = $stmt->fetch();
        $resumen['total_qr'] = floatval($result['total'] ?? 0);
        
        // Calcular egresos
        $sql = "SELECT SUM(monto) as total FROM egresos 
                WHERE DATE(fecha) >= DATE(:fecha_apertura)
                AND CONCAT(fecha, ' ', COALESCE(hora, '00:00:00')) >= :fecha_apertura_completa";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':fecha_apertura' => date('Y-m-d', strtotime($fecha_apertura)),
            ':fecha_apertura_completa' => $fecha_apertura
        ]);
        $result = $stmt->fetch();
        $resumen['total_egresos'] = floatval($result['total'] ?? 0);
        
        // Obtener detalles de ingresos
        $sql = "SELECT i.*, ro.nro_pieza, h.nombres_apellidos 
                FROM ingresos i
                LEFT JOIN registro_ocupacion ro ON i.ocupacion_id = ro.id
                LEFT JOIN huespedes h ON ro.huesped_id = h.id
                WHERE DATE(i.fecha) >= DATE(:fecha_apertura)
                AND CONCAT(i.fecha, ' ', COALESCE(i.hora, '00:00:00')) >= :fecha_apertura_completa
                ORDER BY i.fecha DESC, i.hora DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':fecha_apertura' => date('Y-m-d', strtotime($fecha_apertura)),
            ':fecha_apertura_completa' => $fecha_apertura
        ]);
        $resumen['detalles_ingresos'] = $stmt->fetchAll();
        
        // Obtener detalles de egresos
        $sql = "SELECT * FROM egresos 
                WHERE DATE(fecha) >= DATE(:fecha_apertura)
                AND CONCAT(fecha, ' ', COALESCE(hora, '00:00:00')) >= :fecha_apertura_completa
                ORDER BY fecha DESC, hora DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':fecha_apertura' => date('Y-m-d', strtotime($fecha_apertura)),
            ':fecha_apertura_completa' => $fecha_apertura
        ]);
        $resumen['detalles_egresos'] = $stmt->fetchAll();
        
        // Calcular balances
        $resumen['balance_efectivo'] = $resumen['total_efectivo'] - $resumen['total_egresos'];
        $resumen['balance_total'] = $resumen['total_efectivo'] + $resumen['total_qr'] - $resumen['total_egresos'];
        
        // Calcular resumen por recepcionista
        $resumen['por_recepcionista'] = $this->calcularPorRecepcionista($fecha_apertura);
        
        return $resumen;
    }
    
    /**
     * Calcular resumen separado por recepcionista
     */
    public function calcularPorRecepcionista($fecha_apertura) {
        // Obtener dinámicamente los usuarios del sistema (no hardcodeado)
        $stmt = $this->conn->prepare("SELECT nombre_completo FROM usuarios WHERE activo = 1 ORDER BY nombre_completo ASC");
        $stmt->execute();
        $recepcionistas = array_column($stmt->fetchAll(), 'nombre_completo');
        $resumen_por_recep = [];
        
        foreach ($recepcionistas as $recep) {
            // Ingresos en efectivo
            $sql = "SELECT COALESCE(SUM(monto), 0) as total FROM ingresos 
                    WHERE metodo_pago = 'efectivo' 
                    AND recepcionista = :recepcionista
                    AND DATE(fecha) >= DATE(:fecha_apertura)
                    AND CONCAT(fecha, ' ', COALESCE(hora, '00:00:00')) >= :fecha_apertura_completa";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':recepcionista' => $recep,
                ':fecha_apertura' => date('Y-m-d', strtotime($fecha_apertura)),
                ':fecha_apertura_completa' => $fecha_apertura
            ]);
            $result = $stmt->fetch();
            $efectivo = floatval($result['total']);
            
            // Ingresos por QR
            $sql = "SELECT COALESCE(SUM(monto), 0) as total FROM ingresos 
                    WHERE metodo_pago = 'qr' 
                    AND recepcionista = :recepcionista
                    AND DATE(fecha) >= DATE(:fecha_apertura)
                    AND CONCAT(fecha, ' ', COALESCE(hora, '00:00:00')) >= :fecha_apertura_completa";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':recepcionista' => $recep,
                ':fecha_apertura' => date('Y-m-d', strtotime($fecha_apertura)),
                ':fecha_apertura_completa' => $fecha_apertura
            ]);
            $result = $stmt->fetch();
            $qr = floatval($result['total']);
            
            // Egresos
            $sql = "SELECT COALESCE(SUM(monto), 0) as total FROM egresos 
                    WHERE recepcionista = :recepcionista
                    AND DATE(fecha) >= DATE(:fecha_apertura)
                    AND CONCAT(fecha, ' ', COALESCE(hora, '00:00:00')) >= :fecha_apertura_completa";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':recepcionista' => $recep,
                ':fecha_apertura' => date('Y-m-d', strtotime($fecha_apertura)),
                ':fecha_apertura_completa' => $fecha_apertura
            ]);
            $result = $stmt->fetch();
            $egresos = floatval($result['total']);
            
            // Detalles de ingresos del recepcionista
            $sql = "SELECT i.*, ro.nro_pieza, h.nombres_apellidos 
                    FROM ingresos i
                    LEFT JOIN registro_ocupacion ro ON i.ocupacion_id = ro.id
                    LEFT JOIN huespedes h ON ro.huesped_id = h.id
                    WHERE i.recepcionista = :recepcionista
                    AND DATE(i.fecha) >= DATE(:fecha_apertura)
                    AND CONCAT(i.fecha, ' ', COALESCE(i.hora, '00:00:00')) >= :fecha_apertura_completa
                    ORDER BY i.fecha DESC, i.hora DESC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':recepcionista' => $recep,
                ':fecha_apertura' => date('Y-m-d', strtotime($fecha_apertura)),
                ':fecha_apertura_completa' => $fecha_apertura
            ]);
            $detalles_ingresos = $stmt->fetchAll();
            
            // Detalles de egresos del recepcionista
            $sql = "SELECT * FROM egresos 
                    WHERE recepcionista = :recepcionista
                    AND DATE(fecha) >= DATE(:fecha_apertura)
                    AND CONCAT(fecha, ' ', COALESCE(hora, '00:00:00')) >= :fecha_apertura_completa
                    ORDER BY fecha DESC, hora DESC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':recepcionista' => $recep,
                ':fecha_apertura' => date('Y-m-d', strtotime($fecha_apertura)),
                ':fecha_apertura_completa' => $fecha_apertura
            ]);
            $detalles_egresos = $stmt->fetchAll();
            
            $resumen_por_recep[$recep] = [
                'nombre' => $recep,
                'total_efectivo' => $efectivo,
                'total_qr' => $qr,
                'total_ingresos' => $efectivo + $qr,
                'total_egresos' => $egresos,
                'balance_efectivo' => $efectivo - $egresos,
                'balance_total' => ($efectivo + $qr) - $egresos,
                'detalles_ingresos' => $detalles_ingresos,
                'detalles_egresos' => $detalles_egresos
            ];
        }
        
        return $resumen_por_recep;
    }
    
    /**
     * Registrar un nuevo cierre de caja
     */
    /**
     * Registrar un nuevo cierre de caja
     */
    public function registrarCierre($observaciones = null, $recepcionista = null) {
        try {
            $this->conn->beginTransaction();
            
            // Calcular el resumen actual
            $resumen = $this->calcularResumenActual();
            
            // Si no se especifica recepcionista, usar el de sesión o 'Sistema'
            if (!$recepcionista) {
                $recepcionista = $_SESSION['recepcionista_actual'] ?? $_SESSION['usuario'] ?? 'Sistema';
            }
            
            // Insertar el cierre
            $sql = "INSERT INTO cierres_caja 
                    (fecha_apertura, fecha_cierre, usuario_id, usuario_nombre, recepcionista,
                    total_efectivo, total_qr, total_egresos, balance_efectivo, balance_total, observaciones)
                    VALUES 
                    (:fecha_apertura, :fecha_cierre, :usuario_id, :usuario_nombre, :recepcionista,
                    :total_efectivo, :total_qr, :total_egresos, :balance_efectivo, :balance_total, :observaciones)";
            
            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute([
                ':fecha_apertura' => $resumen['fecha_apertura'],
                ':fecha_cierre' => date('Y-m-d H:i:s'),
                ':usuario_id' => $_SESSION['user_id'] ?? null,
                ':usuario_nombre' => $_SESSION['usuario'] ?? 'Sistema',
                ':recepcionista' => $recepcionista,
                ':total_efectivo' => $resumen['total_efectivo'],
                ':total_qr' => $resumen['total_qr'],
                ':total_egresos' => $resumen['total_egresos'],
                ':balance_efectivo' => $resumen['balance_efectivo'],
                ':balance_total' => $resumen['balance_total'],
                ':observaciones' => $observaciones
            ]);
            
            if ($result) {
                $cierre_id = $this->conn->lastInsertId();
                $this->conn->commit();
                return $cierre_id;
            }
            
            $this->conn->rollBack();
            return false;
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Error en registrarCierre: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener el historial de cierres
     */
    public function obtenerHistorial($limit = 50) {
        $sql = "SELECT * FROM cierres_caja 
                ORDER BY fecha_cierre DESC 
                LIMIT :limit";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener un cierre específico por ID
     */
    public function obtenerCierrePorId($id) {
        $sql = "SELECT * FROM cierres_caja WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }
    
    /**
     * Obtener detalles de ingresos de un cierre específico
     */
    public function obtenerDetallesIngresosCierre($cierre_id) {
        $cierre = $this->obtenerCierrePorId($cierre_id);
        if (!$cierre) {
            return [];
        }
        
        $sql = "SELECT i.*, ro.nro_pieza, h.nombres_apellidos 
                FROM ingresos i
                LEFT JOIN registro_ocupacion ro ON i.ocupacion_id = ro.id
                LEFT JOIN huespedes h ON ro.huesped_id = h.id
                WHERE DATE(i.fecha) >= DATE(:fecha_apertura)
                AND CONCAT(i.fecha, ' ', COALESCE(i.hora, '00:00:00')) >= :fecha_apertura_completa
                AND CONCAT(i.fecha, ' ', COALESCE(i.hora, '00:00:00')) <= :fecha_cierre
                ORDER BY i.fecha DESC, i.hora DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':fecha_apertura' => date('Y-m-d', strtotime($cierre['fecha_apertura'])),
            ':fecha_apertura_completa' => $cierre['fecha_apertura'],
            ':fecha_cierre' => $cierre['fecha_cierre']
        ]);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener detalles de egresos de un cierre específico
     */
    public function obtenerDetallesEgresosCierre($cierre_id) {
        $cierre = $this->obtenerCierrePorId($cierre_id);
        if (!$cierre) {
            return [];
        }
        
        $sql = "SELECT * FROM egresos 
                WHERE DATE(fecha) >= DATE(:fecha_apertura)
                AND CONCAT(fecha, ' ', COALESCE(hora, '00:00:00')) >= :fecha_apertura_completa
                AND CONCAT(fecha, ' ', COALESCE(hora, '00:00:00')) <= :fecha_cierre
                ORDER BY fecha DESC, hora DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':fecha_apertura' => date('Y-m-d', strtotime($cierre['fecha_apertura'])),
            ':fecha_apertura_completa' => $cierre['fecha_apertura'],
            ':fecha_cierre' => $cierre['fecha_cierre']
        ]);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener detalles de pagos QR de un cierre específico
     */
    public function obtenerDetallesPagosQRCierre($cierre_id) {
        $cierre = $this->obtenerCierrePorId($cierre_id);
        if (!$cierre) {
            return [];
        }
        
        $sql = "SELECT pqr.*, ro.nro_pieza, h.nombres_apellidos
                FROM pagos_qr pqr
                LEFT JOIN registro_ocupacion ro ON pqr.ocupacion_id = ro.id
                LEFT JOIN huespedes h ON ro.huesped_id = h.id
                WHERE DATE(pqr.fecha) >= DATE(:fecha_apertura)
                AND CONCAT(pqr.fecha, ' ', COALESCE(pqr.hora, '00:00:00')) >= :fecha_apertura_completa
                AND CONCAT(pqr.fecha, ' ', COALESCE(pqr.hora, '00:00:00')) <= :fecha_cierre
                ORDER BY pqr.fecha DESC, pqr.hora DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':fecha_apertura' => date('Y-m-d', strtotime($cierre['fecha_apertura'])),
            ':fecha_apertura_completa' => $cierre['fecha_apertura'],
            ':fecha_cierre' => $cierre['fecha_cierre']
        ]);
        return $stmt->fetchAll();
    }
    
    /**
     * Verificar si hay movimientos sin cerrar
     */
    public function hayMovimientosSinCerrar() {
        $fecha_apertura = $this->obtenerFechaAperturaActual();
        
        $sql = "SELECT COUNT(*) as total FROM ingresos 
                WHERE CONCAT(fecha, ' ', COALESCE(hora, '00:00:00')) >= :fecha_apertura";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':fecha_apertura' => $fecha_apertura]);
        $result = $stmt->fetch();
        
        return intval($result['total']) > 0;
    }
}
?>
