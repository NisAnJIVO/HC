<?php
session_start();
require_once __DIR__ . '/../config/config.php';

// Procesar LOGIN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['action'])) {
    
    $usuario = clean_input($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($usuario) || empty($password)) {
        header('Location: ../login.php?error=invalid');
        exit;
    }
    
    try {
        $conn = getConnection();
        
        // Buscar usuario
        $stmt = $conn->prepare("SELECT id, usuario, password, nombre_completo, activo, rol FROM usuarios WHERE usuario = ? LIMIT 1");
        $stmt->execute([$usuario]);
        $user = $stmt->fetch();
        
        if ($user && $user['activo'] == 1) {
            // Verificar contraseña
            if (password_verify($password, $user['password'])) {
                // Login exitoso
                $_SESSION['usuario_id'] = $user['id'];
                $_SESSION['usuario'] = $user['usuario'];
                $_SESSION['nombre_completo'] = $user['nombre_completo'];
                $_SESSION['rol'] = $user['rol'];
                $_SESSION['login_time'] = time();
                
                // Actualizar último acceso
                $stmt = $conn->prepare("UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?");
                $stmt->execute([$user['id']]);
                
                header('Location: ../index.php');
                exit;
            }
        }
        
        // Credenciales inválidas
        header('Location: ../login.php?error=invalid');
        exit;
        
    } catch (PDOException $e) {
        error_log("Error en login: " . $e->getMessage());
        header('Location: ../login.php?error=system');
        exit;
    }
}

// Procesar LOGOUT
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    // Destruir todas las variables de sesión
    $_SESSION = array();
    
    // Destruir la cookie de sesión
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 42000, '/');
    }
    
    // Destruir la sesión
    session_destroy();
    
    header('Location: ../login.php');
    exit;
}

// Si no es POST o acción válida, redirigir al login
header('Location: ../login.php');
exit;
