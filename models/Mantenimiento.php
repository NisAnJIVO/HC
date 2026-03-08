<?php
require_once __DIR__ . '/../config/config.php';

class Mantenimiento {
    private $conn;
    
    public function __construct() {
        $this->conn = getConnection();
    }
    
    // Obtener todos los mantenimientos con información de habitación
    public function obtenerTodos($filtros = []) {
        $sql = "SELECT m.*, h.tipo as habitacion_tipo, h.estado as habitacion_estado 
                FROM mantenimientos m 
                LEFT JOIN habitaciones h ON m.habitacion_numero = h.numero 
                WHERE 1=1";
        
        $params = [];
        
        if (isset($filtros['estado']) && !empty($filtros['estado'])) {
            $sql .= " AND m.estado = :estado";
            $params[':estado'] = $filtros['estado'];
        }
        
        if (isset($filtros['prioridad']) && !empty($filtros['prioridad'])) {
            $sql .= " AND m.prioridad = :prioridad";
            $params[':prioridad'] = $filtros['prioridad'];
        }
        
        if (isset($filtros['habitacion']) && !empty($filtros['habitacion'])) {
            $sql .= " AND m.habitacion_numero = :habitacion";
            $params[':habitacion'] = $filtros['habitacion'];
        }
        
        $sql .= " ORDER BY 
                  CASE m.prioridad 
                    WHEN 'urgente' THEN 1 
                    WHEN 'alta' THEN 2 
                    WHEN 'media' THEN 3 
                    WHEN 'baja' THEN 4 
                  END, 
                  m.fecha_inicio DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    // Obtener mantenimientos activos (pendiente o en proceso)
    public function obtenerActivos() {
        $sql = "SELECT m.*, h.tipo as habitacion_tipo, h.estado as habitacion_estado 
                FROM mantenimientos m 
                LEFT JOIN habitaciones h ON m.habitacion_numero = h.numero 
                WHERE m.estado IN ('pendiente', 'en_proceso')
                ORDER BY 
                  CASE m.prioridad 
                    WHEN 'urgente' THEN 1 
                    WHEN 'alta' THEN 2 
                    WHEN 'media' THEN 3 
                    WHEN 'baja' THEN 4 
                  END, 
                  m.fecha_inicio ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    // Obtener por ID
    public function obtenerPorId($id) {
        $sql = "SELECT m.*, h.tipo as habitacion_tipo, h.estado as habitacion_estado 
                FROM mantenimientos m 
                LEFT JOIN habitaciones h ON m.habitacion_numero = h.numero 
                WHERE m.id = :id";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }
    
    // Obtener por habitación
    public function obtenerPorHabitacion($numero) {
        $sql = "SELECT * FROM mantenimientos 
                WHERE habitacion_numero = :numero 
                ORDER BY fecha_inicio DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':numero' => $numero]);
        return $stmt->fetchAll();
    }
    
    // Crear nuevo mantenimiento
    public function crear($datos) {
        $sql = "INSERT INTO mantenimientos 
                (habitacion_numero, titulo, descripcion, prioridad, tipo, estado, 
                 costo_estimado, fecha_inicio, fecha_fin_estimada, responsable, observaciones, imagen) 
                VALUES 
                (:habitacion_numero, :titulo, :descripcion, :prioridad, :tipo, :estado,
                 :costo_estimado, :fecha_inicio, :fecha_fin_estimada, :responsable, :observaciones, :imagen)";
        
        $stmt = $this->conn->prepare($sql);
        $result = $stmt->execute([
            ':habitacion_numero' => $datos['habitacion_numero'],
            ':titulo' => $datos['titulo'],
            ':descripcion' => $datos['descripcion'],
            ':prioridad' => $datos['prioridad'],
            ':tipo' => $datos['tipo'],
            ':estado' => $datos['estado'] ?? 'pendiente',
            ':costo_estimado' => $datos['costo_estimado'] ?? null,
            ':fecha_inicio' => $datos['fecha_inicio'],
            ':fecha_fin_estimada' => $datos['fecha_fin_estimada'] ?? null,
            ':responsable' => $datos['responsable'] ?? null,
            ':observaciones' => $datos['observaciones'] ?? null,
            ':imagen' => $datos['imagen'] ?? null
        ]);
        
        return $result ? $this->conn->lastInsertId() : false;
    }
    
    // Actualizar mantenimiento
    public function actualizar($id, $datos) {
        $sql = "UPDATE mantenimientos SET 
                habitacion_numero = :habitacion_numero,
                titulo = :titulo,
                descripcion = :descripcion,
                prioridad = :prioridad,
                tipo = :tipo,
                estado = :estado,
                costo_estimado = :costo_estimado,
                costo_real = :costo_real,
                fecha_inicio = :fecha_inicio,
                fecha_fin_estimada = :fecha_fin_estimada,
                fecha_fin_real = :fecha_fin_real,
                responsable = :responsable,
                observaciones = :observaciones
                WHERE id = :id";
        
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':habitacion_numero' => $datos['habitacion_numero'],
            ':titulo' => $datos['titulo'],
            ':descripcion' => $datos['descripcion'],
            ':prioridad' => $datos['prioridad'],
            ':tipo' => $datos['tipo'],
            ':estado' => $datos['estado'],
            ':costo_estimado' => $datos['costo_estimado'] ?? null,
            ':costo_real' => $datos['costo_real'] ?? null,
            ':fecha_inicio' => $datos['fecha_inicio'],
            ':fecha_fin_estimada' => $datos['fecha_fin_estimada'] ?? null,
            ':fecha_fin_real' => $datos['fecha_fin_real'] ?? null,
            ':responsable' => $datos['responsable'] ?? null,
            ':observaciones' => $datos['observaciones'] ?? null
        ]);
    }
    
    // Cambiar estado del mantenimiento
    public function cambiarEstado($id, $estado, $fecha_fin_real = null, $costo_real = null) {
        $sql = "UPDATE mantenimientos SET 
                estado = :estado,
                fecha_fin_real = :fecha_fin_real,
                costo_real = :costo_real
                WHERE id = :id";
        
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':estado' => $estado,
            ':fecha_fin_real' => $fecha_fin_real,
            ':costo_real' => $costo_real
        ]);
    }
    
    // Eliminar mantenimiento
    public function eliminar($id) {
        $sql = "DELETE FROM mantenimientos WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
    
    // Obtener estadísticas
    public function obtenerEstadisticas() {
        $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
                SUM(CASE WHEN estado = 'en_proceso' THEN 1 ELSE 0 END) as en_proceso,
                SUM(CASE WHEN estado = 'completado' THEN 1 ELSE 0 END) as completados,
                SUM(CASE WHEN prioridad = 'urgente' THEN 1 ELSE 0 END) as urgentes,
                SUM(CASE WHEN prioridad = 'alta' THEN 1 ELSE 0 END) as alta_prioridad,
                SUM(costo_real) as costo_total_real,
                SUM(costo_estimado) as costo_total_estimado
                FROM mantenimientos
                WHERE estado IN ('pendiente', 'en_proceso', 'completado')";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetch();
    }
}
?>
