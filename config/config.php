<?php
// Cargar variables de entorno desde .env
function loadEnv($path) {
    if (!file_exists($path)) {
        die("Error: Archivo .env no encontrado. Copia .env.example como .env y configúralo.");
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Ignorar comentarios
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Parsear línea
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        // Definir constante si no existe
        if (!defined($name)) {
            define($name, $value);
        }
    }
}

// Cargar configuración desde .env
loadEnv(__DIR__ . '/../.env');

// Configuración general del sistema
define('SITE_NAME', 'Hotel Cecil - Sistema de Gestión');
define('TIMEZONE', 'America/La_Paz');

// Detectar entorno y configurar rutas
$isProduction = (defined('ENVIRONMENT') && constant('ENVIRONMENT') === 'production');

if ($isProduction) {
    // Configuración para Hostinger (ajustar cuando subas)
    define('BASE_PATH', ''); // En Hostinger suele estar en la raíz
} else {
    // Configuración local (XAMPP con ngrok)
    define('BASE_PATH', '/HotelCecil');
}

// Detectar si se accede por ngrok o localhost
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
define('BASE_URL', $protocol . '://' . $host . BASE_PATH);

// Establecer zona horaria
date_default_timezone_set(TIMEZONE);

// Conexión a la base de datos
function getConnection() {
    static $conn = null;
    
    if ($conn === null) {
        try {
            $conn = new PDO(
                "mysql:host=" . constant('DB_HOST') . ";dbname=" . constant('DB_NAME') . ";charset=utf8mb4",
                constant('DB_USER'),
                constant('DB_PASS'),
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
            // Establecer charset explícitamente
            $conn->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
        } catch (PDOException $e) {
            die("Error de conexión: " . $e->getMessage());
        }
    }
    
    return $conn;
}

// Función para limpiar datos de entrada
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Función para formatear fechas
function formatDate($date) {
    if (empty($date)) return '';
    return date('d/m/Y', strtotime($date));
}

// Función para formatear montos
function formatMoney($amount) {
    return number_format($amount, 2, '.', ',');
}

// Función para verificar si el usuario es administrador
function esAdmin() {
    return isset($_SESSION['rol']) && $_SESSION['rol'] === 'administrador';
}

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
