<?php
require_once __DIR__ . '/../config/config.php';

class InventarioHabitacion {
    private $conn;
    
    public function __construct() {
        $this->conn = getConnection();
    }
    
    // Obtener inventario de una habitación específica
    public function obtenerPorHabitacion($habitacion_numero) {
        $query = "SELECT * FROM inventario_habitaciones WHERE habitacion_numero = :habitacion_numero";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':habitacion_numero', $habitacion_numero);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Obtener todo el inventario
    public function obtenerTodo() {
        $query = "SELECT * FROM inventario_habitaciones ORDER BY 
                  CASE WHEN tipo = 'almacen' THEN 1 ELSE 0 END,
                  CAST(habitacion_numero AS UNSIGNED), habitacion_numero";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Crear o actualizar inventario
    public function guardar($datos) {
        // Verificar si ya existe
        $existe = $this->obtenerPorHabitacion($datos['habitacion_numero']);
        
        if ($existe) {
            return $this->actualizar($datos);
        } else {
            return $this->crear($datos);
        }
    }
    
    private function crear($datos) {
        $query = "INSERT INTO inventario_habitaciones (
                    habitacion_numero, tipo, cortinas, veladores, roperos, colgadores,
                    basureros, shampoo, jabon_liquido, sillas, sillones, alfombras,
                    camas, television, lamparas, manteles, cubrecamas, sabanas_media_plaza,
                    sabanas_doble_plaza, almohadas, fundas, frazadas, toallas,
                    cortinas_almacen, alfombras_almacen
                  ) VALUES (
                    :habitacion_numero, :tipo, :cortinas, :veladores, :roperos, :colgadores,
                    :basureros, :shampoo, :jabon_liquido, :sillas, :sillones, :alfombras,
                    :camas, :television, :lamparas, :manteles, :cubrecamas, :sabanas_media_plaza,
                    :sabanas_doble_plaza, :almohadas, :fundas, :frazadas, :toallas,
                    :cortinas_almacen, :alfombras_almacen
                  )";
        
        $stmt = $this->conn->prepare($query);
        return $this->bindYEjecutar($stmt, $datos);
    }
    
    private function actualizar($datos) {
        $query = "UPDATE inventario_habitaciones SET
                    tipo = :tipo,
                    cortinas = :cortinas,
                    veladores = :veladores,
                    roperos = :roperos,
                    colgadores = :colgadores,
                    basureros = :basureros,
                    shampoo = :shampoo,
                    jabon_liquido = :jabon_liquido,
                    sillas = :sillas,
                    sillones = :sillones,
                    alfombras = :alfombras,
                    camas = :camas,
                    television = :television,
                    lamparas = :lamparas,
                    manteles = :manteles,
                    cubrecamas = :cubrecamas,
                    sabanas_media_plaza = :sabanas_media_plaza,
                    sabanas_doble_plaza = :sabanas_doble_plaza,
                    almohadas = :almohadas,
                    fundas = :fundas,
                    frazadas = :frazadas,
                    toallas = :toallas,
                    cortinas_almacen = :cortinas_almacen,
                    alfombras_almacen = :alfombras_almacen
                  WHERE habitacion_numero = :habitacion_numero";
        
        $stmt = $this->conn->prepare($query);
        return $this->bindYEjecutar($stmt, $datos);
    }
    
    private function bindYEjecutar($stmt, $datos) {
        $stmt->bindParam(':habitacion_numero', $datos['habitacion_numero']);
        $stmt->bindParam(':tipo', $datos['tipo']);
        $stmt->bindParam(':cortinas', $datos['cortinas']);
        $stmt->bindParam(':veladores', $datos['veladores']);
        $stmt->bindParam(':roperos', $datos['roperos']);
        $stmt->bindParam(':colgadores', $datos['colgadores']);
        $stmt->bindParam(':basureros', $datos['basureros']);
        $stmt->bindParam(':shampoo', $datos['shampoo']);
        $stmt->bindParam(':jabon_liquido', $datos['jabon_liquido']);
        $stmt->bindParam(':sillas', $datos['sillas']);
        $stmt->bindParam(':sillones', $datos['sillones']);
        $stmt->bindParam(':alfombras', $datos['alfombras']);
        $stmt->bindParam(':camas', $datos['camas']);
        $stmt->bindParam(':television', $datos['television']);
        $stmt->bindParam(':lamparas', $datos['lamparas']);
        $stmt->bindParam(':manteles', $datos['manteles']);
        $stmt->bindParam(':cubrecamas', $datos['cubrecamas']);
        $stmt->bindParam(':sabanas_media_plaza', $datos['sabanas_media_plaza']);
        $stmt->bindParam(':sabanas_doble_plaza', $datos['sabanas_doble_plaza']);
        $stmt->bindParam(':almohadas', $datos['almohadas']);
        $stmt->bindParam(':fundas', $datos['fundas']);
        $stmt->bindParam(':frazadas', $datos['frazadas']);
        $stmt->bindParam(':toallas', $datos['toallas']);
        $stmt->bindParam(':cortinas_almacen', $datos['cortinas_almacen']);
        $stmt->bindParam(':alfombras_almacen', $datos['alfombras_almacen']);
        
        return $stmt->execute();
    }
    
    // Inicializar inventario para todas las habitaciones
    public function inicializarHabitaciones($numeros_habitaciones) {
        foreach ($numeros_habitaciones as $numero) {
            $existe = $this->obtenerPorHabitacion($numero);
            if (!$existe) {
                $datos = [
                    'habitacion_numero' => $numero,
                    'tipo' => 'habitacion',
                    'cortinas' => 0, 'veladores' => 0, 'roperos' => 0, 'colgadores' => 0,
                    'basureros' => 0, 'shampoo' => 0, 'jabon_liquido' => 0, 'sillas' => 0,
                    'sillones' => 0, 'alfombras' => 0, 'camas' => 0, 'television' => 0,
                    'lamparas' => 0, 'manteles' => 0, 'cubrecamas' => 0, 'sabanas_media_plaza' => 0,
                    'sabanas_doble_plaza' => 0, 'almohadas' => 0, 'fundas' => 0, 'frazadas' => 0,
                    'toallas' => 0, 'cortinas_almacen' => 0, 'alfombras_almacen' => 0
                ];
                $this->crear($datos);
            }
        }
    }
}
