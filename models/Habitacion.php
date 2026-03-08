<?php
require_once __DIR__ . '/../config/config.php';

class Habitacion {
    private $conn;
    
    public function __construct() {
        $this->conn = getConnection();
    }
    
    public function obtenerTodas() {
        $sql = "SELECT * FROM habitaciones ORDER BY numero";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function obtenerDisponibles() {
        $sql = "SELECT * FROM habitaciones WHERE estado = 'disponible' ORDER BY numero";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function obtenerPorNumero($numero) {
        $sql = "SELECT * FROM habitaciones WHERE numero = :numero";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':numero' => $numero]);
        return $stmt->fetch();
    }
    
    public function obtenerPorId($id) {
        $sql = "SELECT * FROM habitaciones WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }
}
?>
