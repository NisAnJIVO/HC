<?php
// Evitar iniciar sesión múltiple
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Usuario.php';

// Validar que sea administrador
if (!esAdmin()) {
    header('Location: ' . BASE_PATH . '/index.php');
    exit;
}

$userModel = new Usuario();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos = [
        'usuario' => clean_input($_POST['usuario']),
        'nombre_completo' => clean_input($_POST['nombre_completo']),
        'rol' => clean_input($_POST['rol']),
        'activo' => isset($_POST['activo']) ? 1 : 0
    ];

    if ($action === 'crear') {
        if (empty($_POST['password'])) {
            header('Location: ' . BASE_PATH . '/views/usuarios/index.php?error=Contraseña requerida');
            exit;
        }
        $datos['password'] = $_POST['password'];
        
        if ($userModel->crear($datos)) {
            header('Location: ' . BASE_PATH . '/views/usuarios/index.php?msg=Usuario creado exitosamente');
        } else {
            header('Location: ' . BASE_PATH . '/views/usuarios/index.php?error=El nombre de usuario ya existe');
        }
        exit;
    }

    if ($action === 'editar') {
        $id = clean_input($_POST['id']);
        if ($userModel->actualizar($id, $datos)) {
            header('Location: ' . BASE_PATH . '/views/usuarios/index.php?msg=Usuario actualizado');
        } else {
            header('Location: ' . BASE_PATH . '/views/usuarios/index.php?error=El nombre de usuario ya existe');
        }
        exit;
    }

    if ($action === 'cambiar_password') {
        $id = clean_input($_POST['id']);
        $password = $_POST['password'];
        if (empty($password)) {
            header('Location: ' . BASE_PATH . '/views/usuarios/index.php?error=La contraseña no puede estar vacía');
            exit;
        }
        
        if ($userModel->cambiarPassword($id, $password)) {
            header('Location: ' . BASE_PATH . '/views/usuarios/index.php?msg=Contraseña actualizada');
        } else {
            header('Location: ' . BASE_PATH . '/views/usuarios/index.php?error=Error al cambiar la contraseña');
        }
        exit;
    }
}

// Para eliminar a través de Javascript/GET
if ($action === 'eliminar' && isset($_GET['id'])) {
    $id = clean_input($_GET['id']);
    if ($userModel->eliminar($id)) {
        header('Location: ' . BASE_PATH . '/views/usuarios/index.php?msg=Usuario desactivado');
    } else {
        header('Location: ' . BASE_PATH . '/views/usuarios/index.php?error=Error al desactivar');
    }
    exit;
}

header('Location: ' . BASE_PATH . '/views/usuarios/index.php');
exit;
?>
