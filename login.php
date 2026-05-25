<?php
session_start();

// Si ya está logueado, redirigir al dashboard
if (isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

// Manejar error de login
$error = isset($_GET['error']) ? $_GET['error'] : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel Cecil — Iniciar Sesión</title>
    <script>
        // Sincronizar tema de forma inmediata
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
                        'apple-blue': '#0071e3',
                        'apple-dark': '#1d1d1f',
                        'apple-light-bg': '#ffffff',
                    }
                }
            }
        }
    </script>
    <style>
        * {
            font-family: -apple-system, BlinkMacSystemFont, "SF Pro Text", "Inter", "Segoe UI", sans-serif;
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            background-color: #ffffff;
            color: #1d1d1f;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            transition: background-color 0.3s, color 0.3s;
        }
        
        .dark body {
            background-color: #000000;
            color: #f5f5f7;
        }
        
        /* Premium Flat Input Container */
        .input-group {
            position: relative;
            border: 1px solid rgba(0, 0, 0, 0.15);
            border-radius: 12px;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            background: #ffffff;
        }
        
        .dark .input-group {
            border-color: rgba(255, 255, 255, 0.2);
            background: #000000;
        }
        
        .input-group:focus-within {
            border-color: #0071e3;
            box-shadow: 0 0 0 4px rgba(0, 113, 227, 0.15);
        }
        
        .dark .input-group:focus-within {
            box-shadow: 0 0 0 4px rgba(0, 113, 227, 0.3);
        }
        
        .flat-input {
            width: 100%;
            border: none;
            outline: none;
            background: transparent;
            font-size: 15px;
            font-weight: 400;
            padding: 18px 16px 6px 16px;
            color: #1d1d1f;
        }
        
        .dark .flat-input {
            color: #f5f5f7;
        }
        
        .flat-label {
            position: absolute;
            left: 16px;
            top: 14px;
            font-size: 14px;
            color: #86868b;
            transition: all 0.15s ease-out;
            pointer-events: none;
            font-weight: 400;
        }
        
        /* Floating label states - Including browser autofill states to prevent clashing */
        .flat-input:focus ~ .flat-label,
        .flat-input:not(:placeholder-shown) ~ .flat-label,
        .flat-input:-webkit-autofill ~ .flat-label,
        .flat-input:autofill ~ .flat-label {
            top: 6px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        /* Prevent autofill yellow backgrounds and enforce proper text color */
        .flat-input:-webkit-autofill,
        .flat-input:-webkit-autofill:hover, 
        .flat-input:-webkit-autofill:focus, 
        .flat-input:-webkit-autofill:active {
            -webkit-text-fill-color: #1d1d1f !important;
            transition: background-color 5000s ease-in-out 0s;
        }
        
        .dark .flat-input:-webkit-autofill,
        .dark .flat-input:-webkit-autofill:hover, 
        .dark .flat-input:-webkit-autofill:focus, 
        .dark .flat-input:-webkit-autofill:active {
            -webkit-text-fill-color: #f5f5f7 !important;
        }
        
        /* Elegant Arrow Submit Button */
        .arrow-btn {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background-color: #0071e3;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            outline: none;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 2px 8px rgba(0, 113, 227, 0.25);
        }
        
        .arrow-btn:hover {
            background-color: #0077ed;
            box-shadow: 0 4px 12px rgba(0, 113, 227, 0.35);
            transform: scale(1.05);
        }
        
        .arrow-btn:active {
            transform: scale(0.95);
        }

        .error-banner {
            background: rgba(255, 59, 48, 0.08);
            border: 1px solid rgba(255, 59, 48, 0.18);
            color: #ff3b30;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 550;
            padding: 10px 14px;
            text-align: center;
            animation: fadeInDown 0.3s ease-out;
        }

        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-6px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center">

    <!-- Clean Centered Sign-in Sheet (No Card borders, Apple style flat) -->
    <div class="w-full max-w-[360px] px-6 py-12 flex flex-col items-center">
        
        <!-- Geometric SVG Hotel Logo -->
        <div class="mb-6 flex justify-center">
            <div class="w-14 h-14 rounded-2xl bg-gray-50 dark:bg-zinc-900 border border-gray-100 dark:border-zinc-800 flex items-center justify-center shadow-sm">
                <svg class="w-7 h-7 text-apple-blue dark:text-white" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12h-15m0 0l6.75 6.75M4.5 12l6.75-6.75"></path>
                </svg>
            </div>
        </div>

        <!-- Branding Headings -->
        <div class="text-center mb-8">
            <h1 class="text-[26px] font-extrabold tracking-tight text-gray-900 dark:text-white mb-2 leading-tight">Hotel Cecil</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 font-medium">Gestión de Operaciones</p>
        </div>

        <!-- Error Notice -->
        <?php if ($error && $error === 'invalid'): ?>
        <div class="w-full error-banner mb-6">
            Credenciales de acceso incorrectas
        </div>
        <?php endif; ?>

        <!-- Form -->
        <form action="controllers/auth.php" method="POST" class="w-full space-y-4">
            
            <!-- Username group -->
            <div class="input-group">
                <input 
                    type="text" 
                    id="usuario" 
                    name="usuario" 
                    required 
                    placeholder=" " 
                    autocomplete="username"
                    class="flat-input"
                    autofocus
                >
                <label for="usuario" class="flat-label">Usuario</label>
            </div>

            <!-- Password group -->
            <div class="input-group">
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    required 
                    placeholder=" " 
                    autocomplete="current-password"
                    class="flat-input"
                >
                <label for="password" class="flat-label">Contraseña</label>
            </div>

            <!-- Beautiful Round Arrow Submit Button -->
            <div class="pt-6 flex flex-col items-center">
                <button type="submit" class="arrow-btn" title="Iniciar Sesión">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12h15m0 0l-6.75-6.75M19.5 12l-6.75 6.75"></path>
                    </svg>
                </button>
                <span class="text-[11px] font-bold text-gray-400 dark:text-gray-550 uppercase tracking-widest mt-3">Iniciar Sesión</span>
            </div>

        </form>

        <!-- Divider line -->
        <div class="w-full border-t border-gray-100 dark:border-zinc-800/80 my-10"></div>

        <!-- Bottom details -->
        <div class="text-center space-y-1">
            <p class="text-[10px] font-bold uppercase tracking-wider text-gray-405 dark:text-gray-600">Servidor Protegido SSL</p>
            <p class="text-[9px] text-gray-400 dark:text-gray-500 font-medium">Hotel Cecil S.R.L. · Bolivia</p>
        </div>

    </div>

    <script>
        // Auto-dismiss the error banner smoothly
        setTimeout(() => {
            const banner = document.querySelector('.error-banner');
            if (banner) {
                banner.style.transition = 'all 0.3s cubic-bezier(0.16, 1, 0.3, 1)';
                banner.style.opacity = '0';
                banner.style.transform = 'translateY(-6px)';
                setTimeout(() => banner.remove(), 300);
            }
        }, 4000);
    </script>

</body>
</html>
