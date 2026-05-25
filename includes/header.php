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
$timeout_duration = 14400;
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
    <title><?php echo isset($page_title) ? $page_title . ' — ' : ''; ?>Hotel Cecil</title>
    <script>
        // Dark mode antes de renderizar para evitar flash
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        'noir': '#111111',
                        'pearl': '#fafafa',
                        'mist': '#f5f5f5',
                        'accent': '#FF385C',   /* Airbnb coral */
                        'surface': '#ffffff',
                    },
                    fontFamily: {
                        'sans': ['Inter', '-apple-system', 'BlinkMacSystemFont', 'sans-serif'],
                    },
                    boxShadow: {
                        'nav': '0 1px 0 0 rgba(0,0,0,0.08)',
                        'dropdown': '0 8px 32px rgba(0,0,0,0.12), 0 2px 8px rgba(0,0,0,0.06)',
                    }
                }
            }
        }
    </script>
    <style>
        * { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; }

        body {
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-6px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to   { opacity: 1; }
        }

        .animate-fade-in-down { animation: fadeInDown 0.18s ease-out; }
        .animate-fade-in      { animation: fadeIn 0.4s ease-out; }

        /* ── Navbar ── */
        .hc-nav {
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 50;
            height: 64px;
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            border-bottom: 1px solid rgba(0, 0, 0, 0.08);
            transition: background 0.2s;
        }

        .dark .hc-nav {
            background: rgba(10, 10, 10, 0.92);
            border-bottom-color: rgba(255, 255, 255, 0.08);
        }

        /* ── Nav links ── */
        .hc-navlink {
            position: relative;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 6px 10px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            color: #444;
            transition: background 0.15s, color 0.15s;
            cursor: pointer;
            user-select: none;
            letter-spacing: -0.01em;
        }

        .hc-navlink:hover { background: rgba(0,0,0,0.05); color: #111; }
        .dark .hc-navlink { color: #bbb; }
        .dark .hc-navlink:hover { background: rgba(255,255,255,0.07); color: #fff; }

        /* ── Dropdown ── */
        .hc-dropdown {
            position: absolute;
            top: calc(100% + 10px);
            left: 50%;
            transform: translateX(-50%);
            min-width: 220px;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.12), 0 2px 8px rgba(0,0,0,0.06);
            border: 1px solid rgba(0,0,0,0.06);
            overflow: hidden;
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transition: opacity 0.15s ease, transform 0.15s ease, visibility 0.15s;
            transform: translateX(-50%) translateY(-4px);
        }

        .dark .hc-dropdown {
            background: #1a1a1a;
            border-color: rgba(255,255,255,0.08);
            box-shadow: 0 8px 32px rgba(0,0,0,0.5);
        }

        .hc-dropdown-parent.hc-open .hc-dropdown {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
            transform: translateX(-50%) translateY(0);
        }

        .hc-dropdown-item {
            display: block;
            padding: 11px 16px;
            font-size: 14px;
            font-weight: 450;
            color: #333;
            transition: background 0.1s;
            letter-spacing: -0.01em;
        }

        .hc-dropdown-item:hover { background: #f5f5f5; }
        .dark .hc-dropdown-item { color: #ccc; }
        .dark .hc-dropdown-item:hover { background: #252525; }

        .hc-dropdown-label {
            padding: 8px 16px 4px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #999;
        }

        .hc-divider {
            height: 1px;
            background: rgba(0,0,0,0.06);
            margin: 4px 0;
        }

        .dark .hc-divider { background: rgba(255,255,255,0.06); }

        /* ── Icon buttons ── */
        .hc-icon-btn {
            width: 36px; height: 36px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            color: #555;
            transition: background 0.15s, color 0.15s;
        }

        .hc-icon-btn:hover { background: rgba(0,0,0,0.06); color: #111; }
        .dark .hc-icon-btn { color: #aaa; }
        .dark .hc-icon-btn:hover { background: rgba(255,255,255,0.08); color: #fff; }

        /* ── User chip ── */
        .hc-user-chip {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 5px 12px 5px 8px;
            border-radius: 50px;
            border: 1px solid rgba(0,0,0,0.1);
            font-size: 13px;
            font-weight: 500;
            color: #333;
            letter-spacing: -0.01em;
            transition: border-color 0.15s, box-shadow 0.15s;
        }

        .hc-user-chip:hover {
            border-color: rgba(0,0,0,0.2);
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .dark .hc-user-chip { border-color: rgba(255,255,255,0.12); color: #ccc; }
        .dark .hc-user-chip:hover { border-color: rgba(255,255,255,0.25); }

        .hc-avatar {
            width: 24px; height: 24px;
            border-radius: 50%;
            background: linear-gradient(135deg, #111 0%, #444 100%);
            display: flex; align-items: center; justify-content: center;
            font-size: 10px; font-weight: 600; color: #fff;
        }

        .dark .hc-avatar { background: linear-gradient(135deg, #555 0%, #999 100%); }

        /* ── Logout link ── */
        .hc-logout {
            font-size: 14px;
            font-weight: 500;
            color: #888;
            padding: 6px 10px;
            border-radius: 8px;
            transition: color 0.15s, background 0.15s;
            letter-spacing: -0.01em;
        }

        .hc-logout:hover { color: #c0392b; background: rgba(192,57,43,0.06); }
        .dark .hc-logout { color: #666; }
        .dark .hc-logout:hover { color: #e74c3c; background: rgba(231,76,60,0.1); }

        /* ── Mobile menu ── */
        #mobile-menu {
            position: fixed;
            top: 64px; left: 0; right: 0; bottom: 0;
            background: rgba(255,255,255,0.98);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            overflow-y: auto;
            transform: translateY(-100%);
            transition: transform 0.3s cubic-bezier(0.4,0,0.2,1);
            z-index: 40;
        }

        .dark #mobile-menu {
            background: rgba(10,10,10,0.98);
        }

        #mobile-menu.open { transform: translateY(0); }

        .mob-section-label {
            padding: 12px 20px 4px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: #999;
        }

        .mob-link {
            display: block;
            padding: 12px 20px;
            font-size: 15px;
            font-weight: 450;
            color: #333;
            letter-spacing: -0.01em;
            transition: background 0.1s;
        }

        .mob-link:hover { background: #f5f5f5; }
        .dark .mob-link { color: #ccc; }
        .dark .mob-link:hover { background: #1a1a1a; }

        /* ── Chevron ── */
        .hc-chevron {
            width: 14px; height: 14px;
            transition: transform 0.2s;
        }

        .hc-dropdown-parent.hc-open .hc-chevron { transform: rotate(180deg); }

        /* ── Content offset ── */
        .hc-content {
            padding-top: 64px; /* exact navbar height */
        }

        /* ── Dot indicator for active badge ── */
        .hc-dot {
            width: 6px; height: 6px;
            border-radius: 50%;
            display: inline-block;
        }
    </style>
</head>
<body class="min-h-screen bg-pearl dark:bg-noir transition-colors duration-200">

    <!-- ═══ NAVBAR ═══ -->
    <nav class="hc-nav">
        <div class="max-w-7xl mx-auto px-5 h-full flex items-center justify-between">

            <!-- Logo -->
            <a href="<?php echo BASE_PATH; ?>/index.php" class="flex items-center gap-2.5 shrink-0">
                <img src="<?php echo BASE_PATH; ?>/assets/img/logo.png"
                     alt="Hotel Cecil"
                     class="h-8 w-auto"
                     onerror="this.style.display='none'">
                <span class="font-semibold text-[17px] tracking-tight text-noir dark:text-white">Hotel Cecil</span>
            </a>

            <!-- Desktop nav -->
            <div class="hidden lg:flex items-center gap-0.5">

                <a href="<?php echo BASE_PATH; ?>/index.php" class="hc-navlink">Inicio</a>

                <!-- Huéspedes dropdown -->
                <div class="relative hc-dropdown-parent">
                    <button class="hc-navlink" type="button">
                        Huéspedes
                        <svg class="hc-chevron text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div class="hc-dropdown animate-fade-in-down">
                        <?php if (esAdmin()): ?>
                        <a href="<?php echo BASE_PATH; ?>/views/huespedes/nuevo.php" class="hc-dropdown-item">
                            Nuevo registro
                        </a>
                        <div class="hc-divider"></div>
                        <?php endif; ?>
                        <a href="<?php echo BASE_PATH; ?>/views/huespedes/activos.php" class="hc-dropdown-item">
                            Huéspedes activos
                        </a>
                        <a href="<?php echo BASE_PATH; ?>/views/huespedes/extender_estadia.php" class="hc-dropdown-item">
                            Extender estadía
                        </a>
                    </div>
                </div>

                <!-- Finanzas dropdown -->
                <div class="relative hc-dropdown-parent">
                    <button class="hc-navlink" type="button">
                        Finanzas
                        <svg class="hc-chevron text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div class="hc-dropdown animate-fade-in-down">
                        <?php if (esAdmin()): ?>
                        <div class="hc-dropdown-label">Movimientos</div>
                        <a href="<?php echo BASE_PATH; ?>/views/finanzas/ingresos.php" class="hc-dropdown-item">Ingresos</a>
                        <a href="<?php echo BASE_PATH; ?>/views/finanzas/egresos.php" class="hc-dropdown-item">Egresos</a>
                        <a href="<?php echo BASE_PATH; ?>/views/finanzas/pagos_qr.php" class="hc-dropdown-item">Pagos QR</a>
                        <a href="<?php echo BASE_PATH; ?>/views/finanzas/pagos_pendientes.php" class="hc-dropdown-item">
                            Pagos pendientes
                        </a>
                        <div class="hc-divider"></div>
                        <div class="hc-dropdown-label">Operaciones</div>
                        <a href="<?php echo BASE_PATH; ?>/views/finanzas/garajes.php" class="hc-dropdown-item">Garajes</a>
                        <div class="hc-divider"></div>
                        <div class="hc-dropdown-label">Admin</div>
                        <a href="<?php echo BASE_PATH; ?>/views/usuarios/index.php" class="hc-dropdown-item">Usuarios</a>
                        <?php endif; ?>
                        <a href="<?php echo BASE_PATH; ?>/views/finanzas/resumen.php" class="hc-dropdown-item">Resumen</a>
                    </div>
                </div>

                <!-- Habitaciones dropdown -->
                <div class="relative hc-dropdown-parent">
                    <button class="hc-navlink" type="button">
                        Habitaciones
                        <svg class="hc-chevron text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div class="hc-dropdown animate-fade-in-down">
                        <a href="<?php echo BASE_PATH; ?>/views/habitaciones/estado.php" class="hc-dropdown-item">Estado de habitaciones</a>
                        <a href="<?php echo BASE_PATH; ?>/views/habitaciones/inventario.php" class="hc-dropdown-item">Inventario</a>
                        <a href="<?php echo BASE_PATH; ?>/views/habitaciones/mantenimiento.php" class="hc-dropdown-item">Mantenimiento</a>
                    </div>
                </div>

                <!-- Reportes dropdown -->
                <div class="relative hc-dropdown-parent">
                    <button class="hc-navlink" type="button">
                        Reportes
                        <svg class="hc-chevron text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div class="hc-dropdown animate-fade-in-down">
                        <a href="<?php echo BASE_PATH; ?>/views/reportes/planilla.php" class="hc-dropdown-item">Planilla general</a>
                        <a href="<?php echo BASE_PATH; ?>/views/reportes/parte_diario.php" class="hc-dropdown-item">Parte diario</a>
                        <a href="<?php echo BASE_PATH; ?>/views/reportes/informe_diario.php" class="hc-dropdown-item">Informe diario</a>
                        <a href="<?php echo BASE_PATH; ?>/views/reportes/estadisticas.php" class="hc-dropdown-item">Estadísticas</a>
                    </div>
                </div>
            </div>

            <!-- Right side actions -->
            <div class="hidden lg:flex items-center gap-2">

                <!-- Dark mode toggle -->
                <button id="theme-toggle" class="hc-icon-btn" title="Cambiar tema">
                    <svg id="icon-sun" class="w-[18px] h-[18px] hidden dark:block" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z"/>
                    </svg>
                    <svg id="icon-moon" class="w-[18px] h-[18px] block dark:hidden" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"/>
                    </svg>
                </button>

                <!-- User chip -->
                <div class="hc-user-chip">
                    <div class="hc-avatar">
                        <?php echo strtoupper(substr($_SESSION['usuario'] ?? 'U', 0, 1)); ?>
                    </div>
                    <span class="hidden xl:block"><?php echo htmlspecialchars($_SESSION['usuario'] ?? ''); ?></span>
                </div>

                <!-- Logout -->
                <a href="<?php echo BASE_PATH; ?>/controllers/auth.php?action=logout"
                   onclick="return confirm('¿Cerrar sesión?')"
                   class="hc-logout">
                    Salir
                </a>
            </div>

            <!-- Mobile hamburger -->
            <button id="mobile-menu-btn" class="lg:hidden hc-icon-btn">
                <svg id="icon-menu" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
                <svg id="icon-close" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </nav>

    <!-- ═══ MOBILE MENU ═══ -->
    <div id="mobile-menu">
        <div class="py-4">
            <!-- User info -->
            <div class="px-5 py-3 flex items-center gap-3 border-b border-gray-100 dark:border-gray-800 mb-2">
                <div class="hc-avatar w-8 h-8 text-sm">
                    <?php echo strtoupper(substr($_SESSION['usuario'] ?? 'U', 0, 1)); ?>
                </div>
                <div>
                    <div class="text-sm font-semibold text-noir dark:text-white">
                        <?php echo htmlspecialchars($_SESSION['usuario'] ?? ''); ?>
                    </div>
                    <div class="text-xs text-gray-500">
                        <?php echo ucfirst($_SESSION['rol'] ?? 'usuario'); ?>
                    </div>
                </div>
            </div>

            <a href="<?php echo BASE_PATH; ?>/index.php" class="mob-link">Inicio</a>

            <div class="mob-section-label">Huéspedes</div>
            <?php if (esAdmin()): ?>
            <a href="<?php echo BASE_PATH; ?>/views/huespedes/nuevo.php" class="mob-link">Nuevo registro</a>
            <?php endif; ?>
            <a href="<?php echo BASE_PATH; ?>/views/huespedes/activos.php" class="mob-link">Huéspedes activos</a>
            <a href="<?php echo BASE_PATH; ?>/views/huespedes/extender_estadia.php" class="mob-link">Extender estadía</a>

            <div class="mob-section-label">Finanzas</div>
            <?php if (esAdmin()): ?>
            <a href="<?php echo BASE_PATH; ?>/views/finanzas/ingresos.php" class="mob-link">Ingresos</a>
            <a href="<?php echo BASE_PATH; ?>/views/finanzas/egresos.php" class="mob-link">Egresos</a>
            <a href="<?php echo BASE_PATH; ?>/views/finanzas/pagos_qr.php" class="mob-link">Pagos QR</a>
            <a href="<?php echo BASE_PATH; ?>/views/finanzas/pagos_pendientes.php" class="mob-link">Pagos pendientes</a>
            <a href="<?php echo BASE_PATH; ?>/views/finanzas/garajes.php" class="mob-link">Garajes</a>
            <a href="<?php echo BASE_PATH; ?>/views/usuarios/index.php" class="mob-link">Usuarios</a>
            <?php endif; ?>
            <a href="<?php echo BASE_PATH; ?>/views/finanzas/resumen.php" class="mob-link">Resumen</a>

            <div class="mob-section-label">Habitaciones</div>
            <a href="<?php echo BASE_PATH; ?>/views/habitaciones/estado.php" class="mob-link">Estado</a>
            <a href="<?php echo BASE_PATH; ?>/views/habitaciones/inventario.php" class="mob-link">Inventario</a>
            <a href="<?php echo BASE_PATH; ?>/views/habitaciones/mantenimiento.php" class="mob-link">Mantenimiento</a>

            <div class="mob-section-label">Reportes</div>
            <a href="<?php echo BASE_PATH; ?>/views/reportes/planilla.php" class="mob-link">Planilla general</a>
            <a href="<?php echo BASE_PATH; ?>/views/reportes/parte_diario.php" class="mob-link">Parte diario</a>
            <a href="<?php echo BASE_PATH; ?>/views/reportes/estadisticas.php" class="mob-link">Estadísticas</a>

            <div class="border-t border-gray-100 dark:border-gray-800 mt-4 pt-2">
                <a href="<?php echo BASE_PATH; ?>/controllers/auth.php?action=logout"
                   onclick="return confirm('¿Cerrar sesión?')"
                   class="mob-link text-red-500 dark:text-red-400 font-medium">
                    Cerrar sesión
                </a>
            </div>
        </div>
    </div>

    <script>
        // Mobile menu
        const mobileMenu    = document.getElementById('mobile-menu');
        const mobileBtn     = document.getElementById('mobile-menu-btn');
        const iconMenu      = document.getElementById('icon-menu');
        const iconClose     = document.getElementById('icon-close');

        mobileBtn.addEventListener('click', () => {
            const isOpen = mobileMenu.classList.toggle('open');
            iconMenu.classList.toggle('hidden', isOpen);
            iconClose.classList.toggle('hidden', !isOpen);
        });

        // Close mobile menu on link click
        mobileMenu.querySelectorAll('a').forEach(a => {
            a.addEventListener('click', () => {
                mobileMenu.classList.remove('open');
                iconMenu.classList.remove('hidden');
                iconClose.classList.add('hidden');
            });
        });

        // Dark mode toggle
        document.getElementById('theme-toggle').addEventListener('click', () => {
            const isDark = document.documentElement.classList.toggle('dark');
            localStorage.theme = isDark ? 'dark' : 'light';
        });

        // ── Dropdown hover with gap-tolerance (200ms grace period) ────────────
        // CSS :hover drops when mouse crosses the 10px gap between button & menu.
        // Solution: keep the menu open for 200ms after mouseleave so the cursor
        // can safely travel into the dropdown without it disappearing.
        document.querySelectorAll('.hc-dropdown-parent').forEach(function(parent) {
            let closeTimer;
            parent.addEventListener('mouseenter', function() {
                clearTimeout(closeTimer);
                parent.classList.add('hc-open');
            });
            parent.addEventListener('mouseleave', function() {
                closeTimer = setTimeout(function() {
                    parent.classList.remove('hc-open');
                }, 200);
            });
        });
    </script>

    <!-- Main content -->
    <div class="hc-content">
        <div class="max-w-7xl mx-auto px-5 py-8">
