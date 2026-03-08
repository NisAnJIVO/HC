<?php
/**
 * Script para verificar automáticamente los checkouts
 * Este script debe ejecutarse diariamente (por ejemplo, a las 12:00 del mediodía)
 * 
 * Puedes ejecutarlo:
 * 1. Manualmente desde el navegador: http://localhost/Sistem%20Hotel%20Cecil/cron_checkout.php
 * 2. Con cron (Linux/Mac): 0 12 * * * php /path/to/cron_checkout.php
 * 3. Con Programador de tareas (Windows)
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/models/RegistroOcupacion.php';

// Verificar que no se esté ejecutando desde el navegador (opcional, puedes comentar esto)
// if (php_sapi_name() !== 'cli' && !isset($_GET['manual'])) {
//     die('Este script debe ejecutarse desde la línea de comandos o con el parámetro ?manual=1');
// }

$registroModel = new RegistroOcupacion();

echo "=== Verificando checkouts automáticos ===\n";
echo "Fecha/Hora: " . date('Y-m-d H:i:s') . "\n\n";

$ocupaciones_finalizadas = $registroModel->verificarSalidasAutomaticas();

echo "Ocupaciones finalizadas: $ocupaciones_finalizadas\n";
echo "Habitaciones cambiadas a estado 'limpieza'\n";
echo "\n=== Proceso completado ===\n";

// Log del proceso
error_log("Cron checkout ejecutado: $ocupaciones_finalizadas ocupaciones finalizadas");
?>
