<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/models/RegistroOcupacion.php';
require_once __DIR__ . '/models/Habitacion.php';

$page_title = 'Inicio';

$registroModel = new RegistroOcupacion();
$habitacionModel = new Habitacion();

// Es el verificador de Estados que  al ejecutar el dashboard
$registroModel->verificarSalidasAutomaticas();

$ocupaciones_activas = $registroModel->obtenerActivos();
$habitaciones = $habitacionModel->obtenerTodas();

$total_habitaciones = count($habitaciones);

// Habitaciones que realmente tienen un huésped activo ahora mismo
$habitaciones_ocupadas = count(array_unique(array_column($ocupaciones_activas, 'nro_pieza')));

// Las demás siguen usando el estado de la tabla habitaciones
$habitaciones_limpieza = count(array_filter($habitaciones, function($h) { 
    return $h['estado'] == 'limpieza'; 
}));
$habitaciones_mantenimiento = count(array_filter($habitaciones, function($h) { 
    return $h['estado'] == 'mantenimiento'; 
}));

// Disponibles = rest (no ocupadas, no en limpieza, no en mantenimiento)
$habitaciones_disponibles = $total_habitaciones - $habitaciones_ocupadas - $habitaciones_limpieza - $habitaciones_mantenimiento;

include __DIR__ . '/includes/header.php';
?>

<style>
body {
    transition: background-color 0.3s ease;
}

body:not(.dark *) {
    background: linear-gradient(135deg, #fafafa 0%, #f5f5f5 100%);
}

.dark body {
    background: linear-gradient(135deg, #0a0a0a 0%, #171717 100%);
}

.glass-card {
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.8);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.04);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

body:not(.dark *) .glass-card {
    background: rgba(255, 255, 255, 0.7);
}

.dark .glass-card {
    background: rgba(23, 23, 23, 0.7);
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
}

.glass-card:hover {
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.08);
    transform: translateY(-2px);
}

.dark .glass-card:hover {
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.5);
}

.stat-card {
    backdrop-filter: blur(10px);
    border: 1px solid rgba(0, 0, 0, 0.04);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

body:not(.dark *) .stat-card {
    background: rgba(255, 255, 255, 0.8);
}

.dark .stat-card {
    background: rgba(30, 30, 30, 0.8);
    border: 1px solid rgba(255, 255, 255, 0.08);
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 28px rgba(0, 0, 0, 0.08);
    border-color: rgba(0, 0, 0, 0.08);
}

.dark .stat-card:hover {
    box-shadow: 0 12px 28px rgba(0, 0, 0, 0.5);
    border-color: rgba(255, 255, 255, 0.15);
}

.action-card {
    backdrop-filter: blur(10px);
    border: 1px solid rgba(0, 0, 0, 0.04);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

body:not(.dark *) .action-card {
    background: rgba(255, 255, 255, 0.9);
}

.dark .action-card {
    background: rgba(30, 30, 30, 0.9);
    border: 1px solid rgba(255, 255, 255, 0.08);
}

.action-card:hover {
    border-color: #171717;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
    transform: translateY(-2px);
}

.dark .action-card:hover {
    border-color: rgba(255, 255, 255, 0.3);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.5);
}

.icon-container {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.action-card:hover .icon-container {
    transform: scale(1.05);
}

.guest-item {
    transition: all 0.2s ease;
    border-left: 2px solid transparent;
}

.guest-item:hover {
    border-left-color: #171717;
}

body:not(.dark *) .guest-item:hover {
    background: rgba(0, 0, 0, 0.02);
}

.dark .guest-item:hover {
    background: rgba(255, 255, 255, 0.05);
    border-left-color: #ffffff;
}
</style>

<!-- Hero Section - Compacto -->
<div class="max-w-7xl mx-auto px-4 py-4 mb-6 animate-fade-in">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 sm:gap-4">
        <div class="flex items-center gap-3">
            <!-- Logo/Icon - Optimizado para móvil -->
            <div class="w-10 h-10 sm:w-12 sm:h-12 bg-gradient-to-br from-gray-900 to-gray-700 dark:from-gray-100 dark:to-gray-300 rounded-xl flex items-center justify-center shadow-lg">
                <svg class="w-5 h-5 sm:w-6 sm:h-6 text-white dark:text-gray-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
            </div>
            
            <!-- Title - Optimizado para móvil -->
            <div>
                <h1 class="text-lg sm:text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">
                    Sistema de Gestión Hotelera
                </h1>
                <p class="text-xs text-gray-500 dark:text-gray-400 hidden sm:block">Hotel Cecil Donde el Confort Reside</p>
            </div>
        </div>
        
        <!-- Date/Time Badge - Optimizado para móvil -->
        <div class="glass-card px-3 py-1.5 sm:px-4 sm:py-2 rounded-lg w-full sm:w-auto">
            <div class="flex items-center justify-between sm:flex-col sm:items-start gap-2 sm:gap-0">
                <div class="text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400">Hoy</div>
                <div class="text-xs sm:text-sm font-medium text-gray-900 dark:text-white"><?php echo date('d M Y'); ?> • <?php echo date('H:i'); ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Estadísticas Principales - Más compactas -->
<div class="max-w-7xl mx-auto px-4 mb-6">
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-2 sm:gap-3">
        
        <!-- Huéspedes Activos -->
        <div class="stat-card p-3 sm:p-4">
            <div class="flex items-start justify-between mb-2 sm:mb-3">
                <div class="w-7 h-7 sm:w-8 sm:h-8 bg-gradient-to-br from-gray-900 to-gray-700 flex items-center justify-center icon-container">
                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                </div>
            </div>
            <div class="text-xl sm:text-2xl font-light text-gray-900 dark:text-white mb-0.5"><?php echo count($ocupaciones_activas); ?></div>
            <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Huéspedes</div>
        </div>
        
        <!-- Disponibles -->
        <div class="stat-card p-3 sm:p-4">
            <div class="flex items-start justify-between mb-2 sm:mb-3">
                <div class="w-7 h-7 sm:w-8 sm:h-8 bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center icon-container">
                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
            </div>
            <div class="text-xl sm:text-2xl font-light text-gray-900 dark:text-white mb-0.5"><?php echo $habitaciones_disponibles; ?></div>
            <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Disponibles</div>
        </div>
        
        <!-- Ocupadas -->
        <div class="stat-card p-3 sm:p-4">
            <div class="flex items-start justify-between mb-2 sm:mb-3">
                <div class="w-7 h-7 sm:w-8 sm:h-8 bg-gradient-to-br from-red-500 to-red-600 flex items-center justify-center icon-container">
                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                </div>
            </div>
            <div class="text-xl sm:text-2xl font-light text-gray-900 dark:text-white mb-0.5"><?php echo $habitaciones_ocupadas; ?></div>
            <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Ocupadas</div>
        </div>
        
        <!-- Total -->
        <div class="stat-card p-3 sm:p-4">
            <div class="flex items-start justify-between mb-2 sm:mb-3">
                <div class="w-7 h-7 sm:w-8 sm:h-8 bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center icon-container">
                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                </div>
            </div>
            <div class="text-xl sm:text-2xl font-light text-gray-900 dark:text-white mb-0.5"><?php echo $total_habitaciones; ?></div>
            <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total</div>
        </div>
    </div>
</div>

<!-- Estados Especiales - Más compacto -->
<?php if ($habitaciones_limpieza > 0 || $habitaciones_mantenimiento > 0): ?>
<div class="max-w-7xl mx-auto px-4 mb-6">
    <div class="glass-card p-2.5 sm:p-3 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 sm:gap-0">
        <div class="flex items-center gap-3 sm:gap-4 flex-wrap">
            <?php if ($habitaciones_limpieza > 0): ?>
            <div class="flex items-center gap-2">
                <div class="w-2 h-2 bg-yellow-500 rounded-full"></div>
                <span class="text-xs text-gray-700 dark:text-gray-300"><?php echo $habitaciones_limpieza; ?> en limpieza</span>
            </div>
            <?php endif; ?>
            <?php if ($habitaciones_mantenimiento > 0): ?>
            <div class="flex items-center gap-2">
                <div class="w-2 h-2 bg-orange-500 rounded-full"></div>
                <span class="text-xs text-gray-700 dark:text-gray-300"><?php echo $habitaciones_mantenimiento; ?> en mantenimiento</span>
            </div>
            <?php endif; ?>
        </div>
        <a href="<?php echo BASE_PATH; ?>/views/habitaciones/estado.php" class="text-xs text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition">
            Gestionar →
        </a>
    </div>
</div>
<?php endif; ?>

<!-- Grid Principal - Más compacto -->
<div class="max-w-7xl mx-auto px-4 pb-6">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-3 sm:gap-4">
        
        <!-- Accesos Rápidos (2/3) -->
        <div class="lg:col-span-2 space-y-2 sm:space-y-3">
            <h2 class="text-xs uppercase tracking-wider text-gray-400 mb-2 sm:mb-3">Accesos Rápidos</h2>
            
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 sm:gap-2.5">
                <!-- Nuevo Huésped -->
                <a href="<?php echo BASE_PATH; ?>/views/huespedes/nuevo.php" class="action-card p-3 sm:p-3.5 block">
                    <div class="flex items-start justify-between mb-1.5 sm:mb-2">
                        <div class="w-7 h-7 sm:w-8 sm:h-8 bg-gray-900 flex items-center justify-center icon-container">
                            <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                            </svg>
                        </div>
                        <svg class="w-3 h-3 sm:w-3.5 sm:h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                    <div class="font-medium text-gray-900 dark:text-white text-xs sm:text-sm mb-0.5">Nuevo Huésped</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 hidden sm:block">Registrar check-in</div>
                </a>
                
                <!-- Huéspedes Activos -->
                <a href="<?php echo BASE_PATH; ?>/views/huespedes/activos.php" class="action-card p-3 sm:p-3.5 block">
                    <div class="flex items-start justify-between mb-1.5 sm:mb-2">
                        <div class="w-7 h-7 sm:w-8 sm:h-8 bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center icon-container">
                            <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                        </div>
                        <svg class="w-3 h-3 sm:w-3.5 sm:h-3.5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                    <div class="font-medium text-gray-900 dark:text-white text-xs sm:text-sm mb-0.5">Huéspedes Activos</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 hidden sm:block">Ver estadías</div>
                </a>
                
                <!-- Ingresos -->
                <a href="<?php echo BASE_PATH; ?>/views/finanzas/ingresos.php" class="action-card p-3 sm:p-3.5 block">
                    <div class="flex items-start justify-between mb-1.5 sm:mb-2">
                        <div class="w-7 h-7 sm:w-8 sm:h-8 bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center icon-container">
                            <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <svg class="w-3 h-3 sm:w-3.5 sm:h-3.5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                    <div class="font-medium text-gray-900 dark:text-white text-xs sm:text-sm mb-0.5">Ingresos</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 hidden sm:block">Registrar extras</div>
                </a>
                
                <!-- Reportes -->
                <a href="<?php echo BASE_PATH; ?>/views/reportes/planilla.php" class="action-card p-3 sm:p-3.5 block">
                    <div class="flex items-start justify-between mb-1.5 sm:mb-2">
                        <div class="w-7 h-7 sm:w-8 sm:h-8 bg-gradient-to-br from-gray-600 to-gray-700 flex items-center justify-center icon-container">
                            <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <svg class="w-3 h-3 sm:w-3.5 sm:h-3.5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                    <div class="font-medium text-gray-900 dark:text-white text-xs sm:text-sm mb-0.5">Planilla General</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 hidden sm:block">Ver registros</div>
                </a>
                
                <!-- Parte Diario -->
                <a href="<?php echo BASE_PATH; ?>/views/reportes/parte_diario.php" class="action-card p-3 sm:p-3.5 block">
                    <div class="flex items-start justify-between mb-1.5 sm:mb-2">
                        <div class="w-7 h-7 sm:w-8 sm:h-8 bg-gradient-to-br from-purple-600 to-purple-700 flex items-center justify-center icon-container">
                            <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <svg class="w-3 h-3 sm:w-3.5 sm:h-3.5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                    <div class="font-medium text-gray-900 dark:text-white text-xs sm:text-sm mb-0.5">Parte Diario</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 hidden sm:block">Planilla mensual</div>
                </a>
                
                <!-- Inventario de Habitaciones -->
                <a href="<?php echo BASE_PATH; ?>/views/habitaciones/inventario.php" class="action-card p-3 sm:p-3.5 block">
                    <div class="flex items-start justify-between mb-1.5 sm:mb-2">
                        <div class="w-7 h-7 sm:w-8 sm:h-8 bg-gradient-to-br from-teal-500 to-teal-600 flex items-center justify-center icon-container">
                            <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                            </svg>
                        </div>
                        <svg class="w-3 h-3 sm:w-3.5 sm:h-3.5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                    <div class="font-medium text-gray-900 dark:text-white text-xs sm:text-sm mb-0.5">Inventario</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 hidden sm:block">Control de elementos</div>
                </a>
                
                <!-- Mantenimiento -->
                <a href="<?php echo BASE_PATH; ?>/views/habitaciones/mantenimiento.php" class="action-card p-3 sm:p-3.5 block">
                    <div class="flex items-start justify-between mb-1.5 sm:mb-2">
                        <div class="w-7 h-7 sm:w-8 sm:h-8 bg-gradient-to-br from-orange-500 to-orange-600 flex items-center justify-center icon-container">
                            <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                        </div>
                        <svg class="w-3 h-3 sm:w-3.5 sm:h-3.5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                    <div class="font-medium text-gray-900 dark:text-white text-xs sm:text-sm mb-0.5">Mantenimiento</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 hidden sm:block">Reparaciones y servicios</div>
                </a>
                
                <!-- Estado Habitaciones -->
                <a href="<?php echo BASE_PATH; ?>/views/habitaciones/estado.php" class="action-card p-3 sm:p-3.5 block">
                    <div class="flex items-start justify-between mb-1.5 sm:mb-2">
                        <div class="w-7 h-7 sm:w-8 sm:h-8 bg-gradient-to-br from-amber-500 to-amber-600 flex items-center justify-center icon-container">
                            <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                        </div>
                        <svg class="w-3 h-3 sm:w-3.5 sm:h-3.5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                    <div class="font-medium text-gray-900 dark:text-white text-xs sm:text-sm mb-0.5">Estado</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 hidden sm:block">Ver habitaciones</div>
                </a>
                
                <!-- Informe Diario -->
                <a href="<?php echo BASE_PATH; ?>/views/reportes/informe_diario.php" class="action-card p-3 sm:p-3.5 block">
                    <div class="flex items-start justify-between mb-1.5 sm:mb-2">
                        <div class="w-7 h-7 sm:w-8 sm:h-8 bg-gradient-to-br from-red-500 to-red-600 flex items-center justify-center icon-container">
                            <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                            </svg>
                        </div>
                        <svg class="w-3 h-3 sm:w-3.5 sm:h-3.5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                    <div class="font-medium text-gray-900 dark:text-white text-xs sm:text-sm mb-0.5">Informe Diario</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 hidden sm:block">Resumen del día</div>
                </a>
            </div>
        </div>
        
        <!-- Estadías Activas (1/3) - Más compacto -->
        <div class="lg:col-span-1">
            <h2 class="text-xs uppercase tracking-wider text-gray-400 mb-2 sm:mb-3">Estadías Activas</h2>
            
            <div class="glass-card overflow-hidden max-h-[350px] sm:max-h-[400px] overflow-y-auto">
                <?php if (empty($ocupaciones_activas)): ?>
                    <div class="p-6 sm:p-8 text-center">
                        <div class="w-10 h-10 sm:w-12 sm:h-12 bg-gray-100 dark:bg-gray-800 flex items-center justify-center mx-auto mb-2 rounded-lg">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                        </div>
                        <p class="text-xs text-gray-400">Sin huéspedes</p>
                    </div>
                <?php else: ?>
                    <div class="divide-y divide-gray-100 dark:divide-gray-800">
                        <?php foreach (array_slice($ocupaciones_activas, 0, 15) as $ocu): ?>
                            <div class="guest-item p-2.5 sm:p-3 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                <div class="flex items-center justify-between mb-1 sm:mb-1.5">
                                    <span class="inline-flex items-center justify-center w-5 h-5 sm:w-6 sm:h-6 bg-gray-900 dark:bg-white text-white dark:text-noir text-xs font-medium rounded">
                                        <?php echo htmlspecialchars($ocu['nro_pieza']); ?>
                                    </span>
                                    <span class="text-xs text-gray-400"><?php echo date('d/m', strtotime($ocu['fecha_ingreso'])); ?></span>
                                </div>
                                <div class="text-xs sm:text-sm text-gray-700 dark:text-gray-300 font-medium truncate"><?php echo htmlspecialchars($ocu['nombres_apellidos']); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
