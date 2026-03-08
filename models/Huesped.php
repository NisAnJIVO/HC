<?php
require_once __DIR__ . '/../config/config.php';

class Huesped {
    private $conn;
    
    public function __construct() {
        $this->conn = getConnection();
    }
    
    public function crear($datos) {
        $sql = "INSERT INTO huespedes (nombres_apellidos, genero, edad, estado_civil, nacionalidad, 
                ci_pasaporte, profesion, objeto, procedencia) 
                VALUES (:nombres, :genero, :edad, :estado_civil, :nacionalidad, :ci, :profesion, :objeto, :procedencia)";
        
        $stmt = $this->conn->prepare($sql);
        
        try {
            $stmt->execute([
                ':nombres' => $datos['nombres_apellidos'],
                ':genero' => $datos['genero'],
                ':edad' => $datos['edad'],
                ':estado_civil' => $datos['estado_civil'],
                ':nacionalidad' => $datos['nacionalidad'],
                ':ci' => $datos['ci_pasaporte'],
                ':profesion' => $datos['profesion'],
                ':objeto' => $datos['objeto'],
                ':procedencia' => $datos['procedencia']
            ]);
            return $this->conn->lastInsertId();
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                return false;
            }
            throw $e;
        }
    }
    
    public function buscarPorCI($ci) {
        $sql = "SELECT * FROM huespedes WHERE ci_pasaporte = :ci LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':ci' => $ci]);
        return $stmt->fetch();
    }
    
    public function obtenerTodos($limit = 100, $offset = 0) {
        $sql = "SELECT * FROM huespedes ORDER BY fecha_registro DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function obtenerPorId($id) {
        $sql = "SELECT * FROM huespedes WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }
    
    public function actualizar($id, $datos) {
        $sql = "UPDATE huespedes SET 
                nombres_apellidos = :nombres,
                genero = :genero,
                edad = :edad,
                estado_civil = :estado_civil,
                nacionalidad = :nacionalidad,
                profesion = :profesion,
                objeto = :objeto,
                procedencia = :procedencia
                WHERE id = :id";
        
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':nombres' => $datos['nombres_apellidos'],
            ':genero' => $datos['genero'],
            ':edad' => $datos['edad'],
            ':estado_civil' => $datos['estado_civil'],
            ':nacionalidad' => $datos['nacionalidad'],
            ':profesion' => $datos['profesion'],
            ':objeto' => $datos['objeto'],
            ':procedencia' => $datos['procedencia']
        ]);
    }
}
?>
