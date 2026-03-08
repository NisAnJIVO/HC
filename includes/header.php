<?php
// Verificación de sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si no está logueado, redirigir al login
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ' . BASE_URL . '/login.php?error=unauthorized');
    exit;
}

// Verificar timeout de sesión (4 horas)
$timeout_duration = 14400; // 4 horas en segundos
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > $timeout_duration) {
    session_unset();
    session_destroy();
    header('Location: ' . BASE_URL . '/login.php?error=timeout');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es" class="">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Hotel Cecil</title>
    <script>
        // Dark mode antes de cargar para evitar flash
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark')
        } else {
            document.documentElement.classList.remove('dark')
        }
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        'noir': '#0a0a0a',
                        'slate': '#1a1a1a',
                        'pearl': '#fafafa',
                        'mist': '#f5f5f5',
                        'accent': '#c9a962',
                    },
                    fontFamily: {
                        'sans': ['Inter', 'system-ui', 'sans-serif'],
                    },
                }
            }
        }
    </script>
    <style>
        * { font-family: 'Inter', system-ui, -apple-system, sans-serif; }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .animate-slide-down { animation: slideDown 0.3s ease-out; }
        .animate-fade-in { animation: fadeIn 0.5s ease-out; }
        
        .glass-effect {
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
        
        .glass-effect:not(.dark *) {
            background: rgba(255, 255, 255, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .dark .glass-effect {
            background: rgba(23, 23, 23, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .nav-link {
            position: relative;
            transition: color 0.3s ease;
        }
        
        .nav-link::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background: #c9a962;
            transition: width 0.3s ease;
        }
        
        .nav-link:hover::after {
            width: 100%;
        }
        
        body {
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            transition: background-color 0.3s ease;
        }
    </style>
</head>
<body class="min-h-screen bg-white dark:bg-noir transition-colors duration-300">
    
    <!-- Navigation Ultra Minimalista -->
    <nav class="fixed top-0 left-0 right-0 z-50 glass-effect border-b border-gray-100 dark:border-gray-800">
        <div class="max-w-7xl mx-auto px-6">
            <div class="flex items-center justify-between h-20">
                <!-- Logo Minimalista -->
                <a href="<?php echo BASE_PATH; ?>/index.php" class="flex items-center space-x-3 group">
                    <img src="<?php echo BASE_PATH; ?>/assets/img/logo.png" alt="Hotel Cecil" class="h-10 w-auto transition-transform duration-300 group-hover:scale-105">
                    <span class="text-4xl font-semibold text-noir dark:text-white tracking-tight">Hotel Cecil</span>
                </a>
                
                <!-- Desktop Navigation -->
                <div class="hidden lg:flex items-center space-x-1">
                    <!-- Usuario logueado -->
                    <div class="mr-3 px-3 py-1.5 rounded-lg bg-gray-100 dark:bg-gray-800">
                        <span class="text-xs font-medium text-gray-600 dark:text-gray-400">👤 <?php echo htmlspecialchars($_SESSION['usuario']); ?></span>
                    </div>
                    
                    <!-- Dark Mode Toggle -->
                    <button id="theme-toggle" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors mr-2" title="Toggle dark mode">
                        <svg id="theme-toggle-light-icon" class="w-5 h-5 text-gray-700 dark:text-gray-300 hidden dark:block" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z"></path>
                        </svg>
                        <svg id="theme-toggle-dark-icon" class="w-5 h-5 text-gray-700 dark:text-gray-300 block dark:hidden" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path>
                        </svg>
                    </button>
                    
                    <!-- Botón Logout -->
                    <a href="<?php echo BASE_PATH; ?>/controllers/auth.php?action=logout" 
                       onclick="return confirm('¿Está seguro que desea cerrar sesión?')"
                       class="p-2 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors group" 
                       title="Cerrar Sesión">
                        <svg class="w-5 h-5 text-gray-600 dark:text-gray-400 group-hover:text-red-600 dark:group-hover:text-red-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                        </svg>
                    </a>
                    
                    <div class="h-6 w-px bg-gray-200 dark:bg-gray-700 mx-2"></div>
                    
                    <a href="<?php echo BASE_PATH; ?>/index.php" class="nav-link px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-noir dark:hover:text-white">
                        Inicio
                    </a>
                    
                    <!-- Dropdown Huéspedes -->
                    <div class="relative group">
                        <button class="nav-link px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-noir dark:hover:text-white flex items-center space-x-1">
                            <span>Huéspedes</span>
                            <svg class="w-4 h-4 transition-transform group-hover:rotate-180 duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div class="absolute left-0 mt-2 w-64 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 animate-slide-down">
                            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-2xl border border-gray-100 dark:border-gray-800 overflow-hidden">
                                <a href="<?php echo BASE_PATH; ?>/views/huespedes/nuevo.php" class="block px-5 py-3.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-mist dark:hover:bg-gray-800 hover:text-noir dark:hover:text-white transition-colors duration-150">
                                    <div class="font-medium">Nuevo Registro</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Agregar nuevo huésped</div>
                                </a>
                                <div class="border-t border-gray-100 dark:border-gray-800"></div>
                                <a href="<?php echo BASE_PATH; ?>/views/huespedes/activos.php" class="block px-5 py-3.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-mist dark:hover:bg-gray-800 hover:text-noir dark:hover:text-white transition-colors duration-150">
                                    <div class="font-medium">Huéspedes Activos</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Ver estadías actuales</div>
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Dropdown Finanzas -->
                    <div class="relative group">
                        <button class="nav-link px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-noir dark:hover:text-white flex items-center space-x-1">
                            <span>Finanzas</span>
                            <svg class="w-4 h-4 transition-transform group-hover:rotate-180 duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div class="absolute left-0 mt-2 w-64 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 animate-slide-down">
                            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-2xl border border-gray-100 dark:border-gray-800 overflow-hidden">
                                <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'administrador'): ?>
                                <a href="<?php echo BASE_PATH; ?>/views/finanzas/ingresos.php" class="block px-5 py-3.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-mist dark:hover:bg-gray-800 transition-colors duration-150 group/item">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 bg-gray-100 dark:bg-gray-800 rounded-lg flex items-center justify-center">
                                            <svg class="w-4 h-4 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"></path>
                                            </svg>
                                        </div>
                                        <div class="flex-1">
                                            <div class="font-medium text-gray-900 dark:text-white">Ingresos</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-500">Registro de ingresos</div>
                                        </div>
                                        <div class="w-1.5 h-1.5 rounded-full bg-green-500 opacity-60"></div>
                                    </div>
                                </a>
                                <div class="border-t border-gray-100 dark:border-gray-800"></div>
                                <a href="<?php echo BASE_PATH; ?>/views/finanzas/egresos.php" class="block px-5 py-3.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-mist dark:hover:bg-gray-800 transition-colors duration-150 group/item">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 bg-gray-100 dark:bg-gray-800 rounded-lg flex items-center justify-center">
                                            <svg class="w-4 h-4 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6"></path>
                                            </svg>
                                        </div>
                                        <div class="flex-1">
                                            <div class="font-medium text-gray-900 dark:text-white">Egresos</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-500">Salidas de caja</div>
                                        </div>
                                        <div class="w-1.5 h-1.5 rounded-full bg-red-500 opacity-60"></div>
                                    </div>
                                </a>
                                <div class="border-t border-gray-100 dark:border-gray-800"></div>
                                <a href="<?php echo BASE_PATH; ?>/views/finanzas/pagos_qr.php" class="block px-5 py-3.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-mist dark:hover:bg-gray-800 transition-colors duration-150 group/item">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 bg-gray-100 dark:bg-gray-800 rounded-lg flex items-center justify-center">
                                            <svg class="w-4 h-4 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path>
                                            </svg>
                                        </div>
                                        <div class="flex-1">
                                            <div class="font-medium text-gray-900 dark:text-white">Pagos QR</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-500">Transferencias directas</div>
                                        </div>
                                        <div class="w-1.5 h-1.5 rounded-full bg-purple-500 opacity-60"></div>
                                    </div>
                                </a>
                                <div class="border-t border-gray-100 dark:border-gray-800"></div>
                                <a href="<?php echo BASE_PATH; ?>/views/finanzas/pagos_pendientes.php" class="block px-5 py-3.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-mist dark:hover:bg-gray-800 transition-colors duration-150 group/item">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 bg-gray-100 dark:bg-gray-800 rounded-lg flex items-center justify-center">
                                            <svg class="w-4 h-4 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        </div>
                                        <div class="flex-1">
                                            <div class="font-medium text-gray-900 dark:text-white">Pagos Pendientes</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-500">Completar pagos</div>
                                        </div>
                                        <div class="w-1.5 h-1.5 rounded-full bg-orange-500 opacity-60"></div>
                                    </div>
                                </a>
                                <div class="border-t border-gray-100 dark:border-gray-800"></div>
                                <a href="<?php echo BASE_PATH; ?>/views/finanzas/cierre_caja.php" class="block px-5 py-3.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-mist dark:hover:bg-gray-800 transition-colors duration-150 group/item">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 bg-gray-100 dark:bg-gray-800 rounded-lg flex items-center justify-center">
                                            <svg class="w-4 h-4 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                            </svg>
                                        </div>
                                        <div class="flex-1">
                                            <div class="font-medium text-gray-900 dark:text-white">Cierre de Caja</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-500">Rendición de cuentas</div>
                                        </div>
                                        <div class="w-1.5 h-1.5 rounded-full bg-red-500 opacity-60"></div>
                                    </div>
                                </a>
                                <div class="border-t border-gray-100 dark:border-gray-800"></div>
                                <?php endif; ?>
                                <a href="<?php echo BASE_PATH; ?>/views/finanzas/resumen.php" class="block px-5 py-3.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-mist dark:hover:bg-gray-800 transition-colors duration-150 group/item">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 bg-gray-100 dark:bg-gray-800 rounded-lg flex items-center justify-center">
                                            <svg class="w-4 h-4 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                            </svg>
                                        </div>
                                        <div class="flex-1">
                                            <div class="font-medium text-gray-900 dark:text-white">Resumen</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-500">Ver balance general</div>
                                        </div>
                                        <div class="w-1.5 h-1.5 rounded-full bg-blue-500 opacity-60"></div>
                                    </div>
                                </a>
                                <?php if (esAdmin()): ?>
                                <a href="<?php echo BASE_PATH; ?>/views/finanzas/garajes.php" class="block px-5 py-3.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-mist dark:hover:bg-gray-800 transition-colors duration-150 group/item">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 bg-gray-100 dark:bg-gray-800 rounded-lg flex items-center justify-center">
                                            <svg class="w-4 h-4 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"></path>
                                            </svg>
                                        </div>
                                        <div class="flex-1">
                                            <div class="font-medium text-gray-900 dark:text-white">Garajes</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-500">Control de garajes</div>
                                        </div>
                                        <div class="w-1.5 h-1.5 rounded-full bg-orange-500 opacity-60"></div>
                                    </div>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Dropdown Habitaciones -->
                    <div class="relative group">
                        <button class="nav-link px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-noir dark:hover:text-white flex items-center space-x-1">
                            <span>Habitaciones</span>
                            <svg class="w-4 h-4 transition-transform group-hover:rotate-180 duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div class="absolute left-0 mt-2 w-64 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 animate-slide-down">
                            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-2xl border border-gray-100 dark:border-gray-800 overflow-hidden">
                                <a href="<?php echo BASE_PATH; ?>/views/habitaciones/estado.php" class="block px-5 py-3.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-mist dark:hover:bg-gray-800 hover:text-noir dark:hover:text-white transition-colors duration-150">
                                    <div class="font-medium">Estado de Habitaciones</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Disponibilidad en tiempo real</div>
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <a href="<?php echo BASE_PATH; ?>/views/reportes/estadisticas.php" class="nav-link px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-noir dark:hover:text-white">
                        Estadísticas
                    </a>
                    
                    <a href="<?php echo BASE_PATH; ?>/views/reportes/planilla.php" class="nav-link px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-noir dark:hover:text-white">
                        Reportes
                    </a>
                </div>
                
                <!-- Mobile Menu Button -->
                <button id="mobile-menu-btn" class="lg:hidden p-2 rounded-lg hover:bg-mist dark:hover:bg-gray-800 transition-colors">
                    <svg class="w-6 h-6 text-noir dark:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
            </div>
        </div>
        
        <!-- Mobile Menu -->
        <div id="mobile-menu" class="hidden lg:hidden border-t border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900 max-h-[calc(100vh-4rem)] overflow-y-auto">
            <div class="px-6 py-4 space-y-1">
                <div class="px-4 py-3 mb-3 rounded-lg bg-gray-100 dark:bg-gray-800">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">👤 <?php echo htmlspecialchars($_SESSION['usuario']); ?></span>
                </div>
                <a href="<?php echo BASE_PATH; ?>/index.php" class="block px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-mist dark:hover:bg-gray-800 rounded-lg transition-colors">Dashboard</a>
                <div class="mt-4 px-3 py-2 border-l-2 border-gray-900 dark:border-white bg-gray-50 dark:bg-gray-800/50">
                    <div class="text-xs font-semibold text-gray-900 dark:text-white uppercase tracking-wider">Huéspedes</div>
                </div>
                <a href="<?php echo BASE_PATH; ?>/views/huespedes/nuevo.php" class="block px-4 py-2.5 text-sm text-gray-600 dark:text-gray-400 hover:bg-mist dark:hover:bg-gray-800 rounded-lg transition-colors">Nuevo Registro</a>
                <a href="<?php echo BASE_PATH; ?>/views/huespedes/activos.php" class="block px-4 py-2.5 text-sm text-gray-600 dark:text-gray-400 hover:bg-mist dark:hover:bg-gray-800 rounded-lg transition-colors">Activos</a>
                <div class="mt-4 px-3 py-2 border-l-2 border-gray-900 dark:border-white bg-gray-50 dark:bg-gray-800/50">
                    <div class="text-xs font-semibold text-gray-900 dark:text-white uppercase tracking-wider">Finanzas</div>
                </div>
                <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'administrador'): ?>
                <a href="<?php echo BASE_PATH; ?>/views/finanzas/ingresos.php" class="block px-4 py-2.5 text-sm text-gray-600 dark:text-gray-400 hover:bg-mist dark:hover:bg-gray-800 rounded-lg transition-colors">Ingresos</a>
                <a href="<?php echo BASE_PATH; ?>/views/finanzas/egresos.php" class="block px-4 py-2.5 text-sm text-gray-600 dark:text-gray-400 hover:bg-mist dark:hover:bg-gray-800 rounded-lg transition-colors">Egresos</a>
                <a href="<?php echo BASE_PATH; ?>/views/finanzas/pagos_qr.php" class="block px-4 py-2.5 text-sm text-gray-600 dark:text-gray-400 hover:bg-mist dark:hover:bg-gray-800 rounded-lg transition-colors">Pagos QR</a>
                <a href="<?php echo BASE_PATH; ?>/views/finanzas/pagos_pendientes.php" class="block px-4 py-2.5 text-sm text-gray-600 dark:text-gray-400 hover:bg-mist dark:hover:bg-gray-800 rounded-lg transition-colors">Pagos Pendientes</a>
                <a href="<?php echo BASE_PATH; ?>/views/finanzas/cierre_caja.php" class="block px-4 py-2.5 text-sm text-gray-600 dark:text-gray-400 hover:bg-mist dark:hover:bg-gray-800 rounded-lg transition-colors">Cierre de Caja</a>
                <?php endif; ?>
                <a href="<?php echo BASE_PATH; ?>/views/finanzas/resumen.php" class="block px-4 py-2.5 text-sm text-gray-600 dark:text-gray-400 hover:bg-mist dark:hover:bg-gray-800 rounded-lg transition-colors">Resumen</a>
                <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'administrador'): ?>
                <a href="<?php echo BASE_PATH; ?>/views/finanzas/garajes.php" class="block px-4 py-2.5 text-sm text-gray-600 dark:text-gray-400 hover:bg-mist dark:hover:bg-gray-800 rounded-lg transition-colors">Garajes</a>
                <?php endif; ?>
                <div class="mt-4 px-3 py-2 border-l-2 border-gray-900 dark:border-white bg-gray-50 dark:bg-gray-800/50">
                    <div class="text-xs font-semibold text-gray-900 dark:text-white uppercase tracking-wider">Habitaciones</div>
                </div>
                <a href="<?php echo BASE_PATH; ?>/views/habitaciones/estado.php" class="block px-4 py-2.5 text-sm text-gray-600 dark:text-gray-400 hover:bg-mist dark:hover:bg-gray-800 rounded-lg transition-colors">Estado</a>
                <div class="mt-4 px-3 py-2 border-l-2 border-gray-900 dark:border-white bg-gray-50 dark:bg-gray-800/50">
                    <div class="text-xs font-semibold text-gray-900 dark:text-white uppercase tracking-wider">Análisis</div>
                </div>
                <a href="<?php echo BASE_PATH; ?>/views/reportes/estadisticas.php" class="block px-4 py-2.5 text-sm text-gray-600 dark:text-gray-400 hover:bg-mist dark:hover:bg-gray-800 rounded-lg transition-colors">Estadísticas</a>
                <a href="<?php echo BASE_PATH; ?>/views/reportes/planilla.php" class="block px-4 py-2.5 text-sm text-gray-600 dark:text-gray-400 hover:bg-mist dark:hover:bg-gray-800 rounded-lg transition-colors">Reportes</a>
                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <a href="<?php echo BASE_PATH; ?>/controllers/auth.php?action=logout" 
                       onclick="return confirm('¿Está seguro que desea cerrar sesión?')"
                       class="block px-4 py-2.5 text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors">
                        Cerrar Sesión
                    </a>
                </div>
            </div>
        </div>
    </nav>
    
    <script>
        // Mobile menu toggle
        document.getElementById('mobile-menu-btn').addEventListener('click', function() {
            document.getElementById('mobile-menu').classList.toggle('hidden');
        });
        
        // Dark mode toggle
        const themeToggle = document.getElementById('theme-toggle');
        
        themeToggle.addEventListener('click', function() {
            if (document.documentElement.classList.contains('dark')) {
                document.documentElement.classList.remove('dark');
                localStorage.theme = 'light';
            } else {
                document.documentElement.classList.add('dark');
                localStorage.theme = 'dark';
            }
        });
    </script>
    
    <!-- Main Content Container -->
    <div class="pt-12">
        <div class="max-w-7xl mx-auto px-6 py-12">
