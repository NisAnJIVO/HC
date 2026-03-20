<?php
require_once __DIR__ . '/../config/config.php';

class Usuario {
    private $conn;

    public function __construct() {
        $this->conn = getConnection();
    }

    public function obtenerTodos() {
        $stmt = $this->conn->prepare("SELECT id, usuario, nombre_completo, rol, activo, ultimo_acceso, fecha_creacion FROM usuarios ORDER BY nombre_completo");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function obtenerPorId($id) {
        $stmt = $this->conn->prepare("SELECT id, usuario, nombre_completo, rol, activo FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function crear($datos) {
        // Verificar si el usuario ya existe
        $stmt_check = $this->conn->prepare("SELECT id FROM usuarios WHERE usuario = ?");
        $stmt_check->execute([$datos['usuario']]);
        if ($stmt_check->fetch()) {
            return false; // Usuario duplicado
        }

        $hash = password_hash($datos['password'], PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO usuarios (usuario, password, nombre_completo, rol, activo) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $datos['usuario'],
            $hash,
            $datos['nombre_completo'],
            $datos['rol'],
            $datos['activo'] ?? 1
        ]);
    }

    public function actualizar($id, $datos) {
        // Verificar si el nuevo nombre de usuario ya existe en otro ID
        $stmt_check = $this->conn->prepare("SELECT id FROM usuarios WHERE usuario = ? AND id != ?");
        $stmt_check->execute([$datos['usuario'], $id]);
        if ($stmt_check->fetch()) {
            return false; // Usuario duplicado
        }

        $sql = "UPDATE usuarios SET usuario = ?, nombre_completo = ?, rol = ?, activo = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $datos['usuario'],
            $datos['nombre_completo'],
            $datos['rol'],
            $datos['activo'] ?? 1,
            $id
        ]);
    }

    public function cambiarPassword($id, $nuevo_password) {
        $hash = password_hash($nuevo_password, PASSWORD_DEFAULT);
        $sql = "UPDATE usuarios SET password = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$hash, $id]);
    }
    
    public function eliminar($id) {
        // En lugar de borrar, lo desactivamos por seguridad (soft delete)
        $sql = "UPDATE usuarios SET activo = 0 WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$id]);
    }
}
?>
