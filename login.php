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
    <title>Hotel Cecil</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { 
            font-family: -apple-system, BlinkMacSystemFont, 'Inter', 'Segoe UI', system-ui, sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            overflow: hidden;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        @keyframes fadeInUp {
            from { 
                opacity: 0; 
                transform: translateY(20px);
            }
            to { 
                opacity: 1; 
                transform: translateY(0);
            }
        }
        
        @keyframes snowfall {
            0% {
                transform: translateY(-10vh) translateX(0) rotate(0deg);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateY(110vh) translateX(var(--drift)) rotate(360deg);
                opacity: 0;
            }
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-8px); }
        }
        
        /* Nieve */
        .snowflake {
            position: fixed;
            top: -10vh;
            color: rgba(255, 255, 255, 0.9);
            font-size: var(--size);
            opacity: 0;
            animation: snowfall var(--duration) linear infinite;
            animation-delay: var(--delay);
            pointer-events: none;
            z-index: 9999;
            user-select: none;
            text-shadow: 0 0 8px rgba(255, 255, 255, 0.5);
        }
        
        /* Card principal estilo Apple */
        .login-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(60px) saturate(180%);
            -webkit-backdrop-filter: blur(60px) saturate(180%);
            border: 0.5px solid rgba(255, 255, 255, 0.4);
            box-shadow: 
                0 20px 60px rgba(0, 0, 0, 0.08),
                0 2px 8px rgba(0, 0, 0, 0.04),
                inset 0 0 0 1px rgba(255, 255, 255, 0.5);
            animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }
        
        /* Input estilo Apple */
        .apple-input {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px);
            border: 0.5px solid rgba(0, 0, 0, 0.08);
            transition: all 0.15s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 
                0 1px 2px rgba(0, 0, 0, 0.04),
                inset 0 1px 0 rgba(255, 255, 255, 0.7);
        }
        
        .apple-input:hover {
            background: rgba(255, 255, 255, 0.95);
            border-color: rgba(0, 0, 0, 0.12);
        }
        
        .apple-input:focus {
            background: rgba(255, 255, 255, 1);
            border-color: rgba(0, 122, 255, 0.6);
            outline: none;
            box-shadow: 
                0 0 0 4px rgba(0, 122, 255, 0.08),
                0 1px 2px rgba(0, 0, 0, 0.04),
                inset 0 1px 0 rgba(255, 255, 255, 0.7);
        }
        
        /* Botón estilo Apple azul */
        .apple-button {
            background: linear-gradient(180deg, #007AFF 0%, #0051D5 100%);
            border: none;
            box-shadow: 
                0 2px 8px rgba(0, 122, 255, 0.25),
                0 1px 2px rgba(0, 0, 0, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.15);
            transition: all 0.15s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        .apple-button:hover {
            background: linear-gradient(180deg, #0066E0 0%, #0047BB 100%);
            box-shadow: 
                0 4px 12px rgba(0, 122, 255, 0.35),
                0 2px 4px rgba(0, 0, 0, 0.15),
                inset 0 1px 0 rgba(255, 255, 255, 0.15);
            transform: translateY(-0.5px);
        }
        
        .apple-button:active {
            transform: translateY(0px);
            box-shadow: 
                0 1px 4px rgba(0, 122, 255, 0.2),
                inset 0 1px 2px rgba(0, 0, 0, 0.1);
        }
        
        /* Logo estilo Apple */
        .logo-text {
            background: linear-gradient(135deg, #1a1a1a 0%, #4a4a4a 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: -0.03em;
        }
        
        /* Error sutil */
        .error-box {
            background: rgba(255, 59, 48, 0.08);
            backdrop-filter: blur(20px);
            border: 0.5px solid rgba(255, 59, 48, 0.2);
            animation: fadeInUp 0.3s ease-out;
        }
        
        /* Label estilo Apple */
        .apple-label {
            font-size: 13px;
            font-weight: 500;
            letter-spacing: -0.01em;
            color: #1d1d1f;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center bg-gradient-to-br from-slate-50 via-blue-50/30 to-slate-100">
    
    <!-- Nieve de fondo -->
    <div id="snow-container" class="fixed inset-0 pointer-events-none"></div>
    
    <!-- Blur decorativo -->
    <div class="absolute inset-0 pointer-events-none overflow-hidden">
        <div class="absolute top-20 right-20 w-96 h-96 bg-blue-400/20 rounded-full blur-3xl"></div>
        <div class="absolute bottom-20 left-20 w-96 h-96 bg-purple-400/10 rounded-full blur-3xl"></div>
    </div>
    
    <!-- Contenedor del login -->
    <div class="relative w-full max-w-md px-6 z-10">
        
        <!-- Card de login -->
        <div class="login-card rounded-3xl p-10">
            
            <!-- Logo -->
            <div class="text-center mb-12">
                <h1 class="logo-text text-4xl font-bold tracking-tight mb-2">Hotel Cecil</h1>
                <p class="text-sm text-gray-500 font-medium">Sistema de Gestión</p>
            </div>

            <!-- Mensaje de error solo para credenciales inválidas -->
            <?php if ($error && $error === 'invalid'): ?>
            <div class="error-box mb-6 px-4 py-3 rounded-xl">
                <p class="text-sm text-red-600 text-center font-medium">
                    Credenciales incorrectas
                </p>
            </div>
            <?php endif; ?>

            <!-- Formulario -->
            <form action="controllers/auth.php" method="POST" class="space-y-5">
                
                <!-- Usuario -->
                <div>
                    <label for="usuario" class="apple-label block mb-2">
                        Usuario
                    </label>
                    <input 
                        type="text" 
                        id="usuario" 
                        name="usuario" 
                        required
                        autocomplete="username"
                        class="apple-input w-full px-4 py-3 rounded-xl text-base text-gray-900 placeholder-gray-400"
                        placeholder="Ingrese su usuario"
                        autofocus
                    >
                </div>

                <!-- Contraseña -->
                <div>
                    <label for="password" class="apple-label block mb-2">
                        Contraseña
                    </label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required
                        autocomplete="current-password"
                        class="apple-input w-full px-4 py-3 rounded-xl text-base text-gray-900 placeholder-gray-400"
                        placeholder="Ingrese su contraseña"
                    >
                </div>

                <!-- Botón -->
                <button 
                    type="submit"
                    class="apple-button w-full py-3.5 rounded-xl text-white font-semibold text-base mt-8"
                >
                    Iniciar Sesión
                </button>

            </form>

            <!-- Footer -->
            <div class="mt-10 text-center">
                <p class="text-xs text-gray-400">
                    Sistema protegido
                </p>
            </div>

        </div>

    </div>

    <script>
        // Crear nieve realista
        function createSnowflakes() {
            const container = document.getElementById('snow-container');
            
            for (let i = 0; i < 80; i++) {
                const snowflake = document.createElement('div');
                snowflake.className = 'snowflake';
                snowflake.textContent = ['❄', '❅', '❆'][Math.floor(Math.random() * 3)];
                
                const size = Math.random() * 0.6 + 0.6;
                const duration = Math.random() * 8 + 12;
                const delay = Math.random() * 12;
                const startPos = Math.random() * 100;
                const drift = (Math.random() - 0.5) * 60;
                
                snowflake.style.cssText = `
                    --size: ${size}em;
                    --duration: ${duration}s;
                    --delay: ${delay}s;
                    --drift: ${drift}px;
                    left: ${startPos}%;
                `;
                
                container.appendChild(snowflake);
            }
        }
        
        createSnowflakes();

        // Auto-dismiss error
        setTimeout(() => {
            const error = document.querySelector('.error-box');
            if (error) {
                error.style.transition = 'opacity 0.3s, transform 0.3s';
                error.style.opacity = '0';
                error.style.transform = 'translateY(-10px)';
                setTimeout(() => error.remove(), 300);
            }
        }, 4000);
        
        // Efecto hover sutil en inputs
        document.querySelectorAll('.apple-input').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'translateY(-2px)';
                this.parentElement.style.transition = 'transform 0.2s ease';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'translateY(0)';
            });
        });
    </script>

</body>
</html>
