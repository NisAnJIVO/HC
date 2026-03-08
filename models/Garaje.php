<?php
require_once __DIR__ . '/../config/config.php';

class Garaje {
    
    /**
     * Registrar uso de garaje
     */
    public function registrar($datos) {
        try {
            $conn = getConnection();
            $sql = "INSERT INTO registro_garaje (ocupacion_id, huesped_nombre, placa, tipo_vehiculo, fecha, costo, observaciones) 
                    VALUES (:ocupacion_id, :huesped_nombre, :placa, :tipo_vehiculo, :fecha, :costo, :observaciones)";
            
            $stmt = $conn->prepare($sql);
            $result = $stmt->execute([
                ':ocupacion_id' => $datos['ocupacion_id'],
                ':huesped_nombre' => $datos['huesped_nombre'],
                ':placa' => $datos['placa'] ?? null,
                ':tipo_vehiculo' => $datos['tipo_vehiculo'] ?? null,
                ':fecha' => $datos['fecha'],
                ':costo' => $datos['costo'] ?? 10.00,
                ':observaciones' => $datos['observaciones'] ?? null
            ]);
            
            return $result ? $conn->lastInsertId() : false;
        } catch (PDOException $e) {
            error_log("Error al registrar garaje: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener registros de garaje por rango de fechas
     */
    public function obtenerPorFechas($fecha_inicio, $fecha_fin) {
        try {
            $conn = getConnection();
            $sql = "SELECT * FROM registro_garaje 
                    WHERE fecha BETWEEN :inicio AND :fin 
                    ORDER BY fecha DESC, id DESC";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':inicio' => $fecha_inicio,
                ':fin' => $fecha_fin
            ]);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error al obtener garajes: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener resumen de garajes (total y cantidad)
     */
    public function obtenerResumen($fecha_inicio, $fecha_fin) {
        try {
            $conn = getConnection();
            $sql = "SELECT 
                        COUNT(*) as cantidad,
                        SUM(costo) as total
                    FROM registro_garaje 
                    WHERE fecha BETWEEN :inicio AND :fin";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':inicio' => $fecha_inicio,
                ':fin' => $fecha_fin
            ]);
            
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error al obtener resumen de garajes: " . $e->getMessage());
            return ['cantidad' => 0, 'total' => 0];
        }
    }
    
    /**
     * Verificar si una ocupación tiene garaje registrado
     */
    public function tieneGaraje($ocupacion_id) {
        try {
            $conn = getConnection();
            $sql = "SELECT id FROM registro_garaje WHERE ocupacion_id = :ocupacion_id LIMIT 1";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':ocupacion_id' => $ocupacion_id]);
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            error_log("Error al verificar garaje: " . $e->getMessage());
            return false;
        }
    }
}
