<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/Usuario.php';

// Validar administrador
if (!esAdmin()) {
    header('Location: ' . BASE_PATH . '/index.php');
    exit;
}

$page_title = 'Gestión de Usuarios';
$userModel = new Usuario();
$usuarios = $userModel->obtenerTodos();

include __DIR__ . '/../../includes/header.php';
?>

<style>
/* ═══════════════════════════════════════════════
   Apple Premium Profile Cards Layout
   ═══════════════════════════════════════════════ */

:root {
    --apple-font: -apple-system, BlinkMacSystemFont, "SF Pro Text", "SF Pro Icons", "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
}

body {
    background-color: #f5f5f7;
    font-family: var(--apple-font);
    color: #1d1d1f;
}

.apple-card {
    background: #ffffff;
    border: 1px solid rgba(0, 0, 0, 0.05);
    border-radius: 24px;
    box-shadow: 0 4px 24px rgba(0, 0, 0, 0.012);
    padding: 24px;
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.apple-card:hover {
    box-shadow: 0 10px 32px rgba(0, 0, 0, 0.03);
    transform: translateY(-2px);
}

.dark .apple-card {
    background: #161616;
    border-color: rgba(255, 255, 255, 0.06);
    box-shadow: 0 4px 24px rgba(0, 0, 0, 0.15);
}

.dark .apple-card:hover {
    box-shadow: 0 10px 32px rgba(0, 0, 0, 0.4);
}

/* Premium Form Inputs */
.apple-input {
    width: 100%;
    background: rgba(0, 0, 0, 0.02);
    border: 1px solid rgba(0, 0, 0, 0.08);
    border-radius: 14px;
    padding: 12px 16px;
    font-size: 14px;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    color: #1d1d1f;
}

.apple-input:focus {
    outline: none;
    border-color: #0071e3;
    background: #ffffff;
    box-shadow: 0 0 0 4px rgba(0, 113, 227, 0.12);
}

.dark .apple-input {
    background: rgba(255, 255, 255, 0.03);
    border-color: rgba(255, 255, 255, 0.1);
    color: #f5f5f7;
}

.dark .apple-input:focus {
    background: #1c1c1e;
    border-color: #0071e3;
    box-shadow: 0 0 0 4px rgba(0, 113, 227, 0.25);
}

/* Custom Profile Avatar Chips */
.avatar-profile {
    width: 52px;
    height: 52px;
    border-radius: 50%;
    background: linear-gradient(135deg, #0071e3 0%, #8b5cf6 100%);
    color: #ffffff;
    font-weight: 700;
    font-size: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    letter-spacing: -0.03em;
    box-shadow: 0 4px 12px rgba(0, 113, 227, 0.15);
}

.dark .avatar-profile {
    background: linear-gradient(135deg, #2c2c2e 0%, #1c1c1e 100%);
    border: 1px solid rgba(255, 255, 255, 0.12);
    color: #eaeaea;
    box-shadow: none;
}

/* Badge Pills */
.pill-badge {
    display: inline-flex;
    align-items: center;
    padding: 4px 12px;
    border-radius: 50px;
    font-size: 11px;
    font-weight: 650;
    letter-spacing: -0.01em;
}

.pill-active {
    background-color: rgba(52, 199, 89, 0.08);
    color: #248a3d;
    border: 1.5px solid rgba(52, 199, 89, 0.15);
}

.dark .pill-active {
    background-color: rgba(52, 199, 89, 0.12);
    color: #30d158;
    border-color: rgba(52, 199, 89, 0.2);
}

.pill-inactive {
    background-color: rgba(255, 59, 48, 0.08);
    color: #d12727;
    border: 1.5px solid rgba(255, 59, 48, 0.15);
}

.dark .pill-inactive {
    background-color: rgba(255, 59, 48, 0.12);
    color: #ff453a;
    border-color: rgba(255, 59, 48, 0.2);
}

.pill-admin {
    background-color: rgba(175, 82, 222, 0.08);
    color: #af52de;
    border: 1px solid rgba(175, 82, 222, 0.18);
}

.dark .pill-admin {
    background-color: rgba(175, 82, 222, 0.12);
    color: #bf55ec;
    border-color: rgba(175, 82, 222, 0.2);
}

.pill-user {
    background-color: rgba(142, 142, 147, 0.08);
    color: #8e8e93;
    border: 1px solid rgba(142, 142, 147, 0.18);
}

.dark .pill-user {
    background-color: rgba(142, 142, 147, 0.12);
    color: #aeaeae;
    border-color: rgba(142, 142, 147, 0.2);
}

/* Premium Modals */
.modal-overlay {
    background: rgba(0, 0, 0, 0.35);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
}

.modal-sheet {
    background: #ffffff;
    border: 1px solid rgba(0, 0, 0, 0.05);
    border-radius: 24px;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.1);
}

.dark .modal-sheet {
    background: #1c1c1e;
    border-color: rgba(255, 255, 255, 0.08);
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.4);
}

/* Custom Switch styles */
.switch-label {
    display: inline-flex;
    align-items: center;
    cursor: pointer;
    user-select: none;
}

.switch-track {
    position: relative;
    width: 44px;
    height: 24px;
    background-color: #e5e5ea;
    border-radius: 9999px;
    transition: background-color 0.2s ease;
    margin-right: 10px;
}

.dark .switch-track {
    background-color: #3a3a3c;
}

.switch-thumb {
    position: absolute;
    top: 2px;
    left: 2px;
    width: 20px;
    height: 20px;
    background-color: #ffffff;
    border-radius: 50%;
    box-shadow: 0 1px 3px rgba(0,0,0,0.15);
    transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

input[type="checkbox"]:checked + .switch-track {
    background-color: #34c759;
}

input[type="checkbox"]:checked + .switch-track .switch-thumb {
    transform: translateX(20px);
}
</style>

<div class="max-w-6xl mx-auto px-4 py-8">
    
    <!-- Title Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-gray-900 dark:text-white mb-1">Usuarios del Sistema</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 font-medium">Administración de colaboradores, credenciales y niveles de seguridad</p>
        </div>
        <div class="flex gap-2">
            <button onclick="openModal('modal_crear')" class="px-5 py-3 bg-gray-900 hover:bg-gray-800 dark:bg-gray-100 dark:hover:bg-white text-white dark:text-gray-900 text-sm font-semibold rounded-2xl transition duration-200 shadow-sm flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                Añadir Usuario
            </button>
            <a href="<?php echo BASE_PATH; ?>/index.php" class="px-5 py-3 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-750 text-gray-700 dark:text-gray-300 rounded-2xl border border-gray-200 dark:border-gray-700 transition font-semibold text-sm text-center shadow-sm">
                Volver
            </a>
        </div>
    </div>

    <!-- Alert Notices -->
    <?php if (isset($_GET['msg'])): ?>
        <div class="mb-6 p-4 rounded-2xl border bg-green-50/80 dark:bg-green-950/20 text-green-800 dark:text-green-300 border-green-100 dark:border-green-900/30 text-sm font-semibold animate-fade-in">
            <?php echo htmlspecialchars($_GET['msg']); ?>
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="mb-6 p-4 rounded-2xl border bg-red-50/80 dark:bg-red-950/20 text-red-800 dark:text-red-300 border-red-100 dark:border-red-900/30 text-sm font-semibold animate-fade-in">
            <?php echo htmlspecialchars($_GET['error']); ?>
        </div>
    <?php endif; ?>

    <!-- Master Card-based Profile Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($usuarios as $u): 
            // Obtener iniciales para el chip avatar
            $partes = explode(' ', trim($u['nombre_completo']));
            $iniciales = '';
            if (count($partes) >= 2) {
                $iniciales = strtoupper(substr($partes[0], 0, 1) . substr($partes[1], 0, 1));
            } else {
                $iniciales = strtoupper(substr($u['nombre_completo'], 0, 2));
            }
        ?>
        <div class="apple-card flex flex-col justify-between">
            <div>
                <!-- Top Row: Avatar and User Details -->
                <div class="flex items-start gap-4 mb-5">
                    <div class="avatar-profile flex-shrink-0"><?php echo $iniciales; ?></div>
                    <div class="min-w-0 flex-1">
                        <div class="font-extrabold text-[17px] text-gray-900 dark:text-white leading-snug truncate" title="<?php echo htmlspecialchars($u['nombre_completo']); ?>">
                            <?php echo htmlspecialchars($u['nombre_completo']); ?>
                        </div>
                        <div class="text-xs text-gray-400 dark:text-gray-500 font-semibold mt-0.5">@<?php echo htmlspecialchars($u['usuario']); ?></div>
                        
                        <div class="flex flex-wrap gap-2 mt-3">
                            <span class="pill-badge <?php echo $u['rol'] === 'administrador' ? 'pill-admin' : 'pill-user'; ?>">
                                <?php echo $u['rol'] === 'administrador' ? 'Administrador' : 'Recepcionista'; ?>
                            </span>
                            <span class="pill-badge <?php echo $u['activo'] ? 'pill-active' : 'pill-inactive'; ?>">
                                <?php echo $u['activo'] ? 'Activo' : 'Inactivo'; ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Info Box details -->
                <div class="bg-gray-50/50 dark:bg-white/[0.01] border border-gray-100 dark:border-gray-800/80 rounded-2xl p-4 space-y-2 text-xs font-semibold">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-400 dark:text-gray-500">Última Conexión</span>
                        <span class="text-gray-800 dark:text-gray-300 font-variant-numeric-tabular">
                            <?php echo $u['ultimo_acceso'] ? date('d/m/Y · H:i', strtotime($u['ultimo_acceso'])) : 'Nunca conectado'; ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Bottom Row: Card Actions -->
            <div class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-800/80 flex items-center justify-end gap-1">
                <button onclick='editarUsuario(<?php echo json_encode($u); ?>)' class="px-3 py-1.5 text-blue-600 hover:text-blue-700 hover:bg-blue-50 dark:hover:bg-blue-900/20 text-xs font-bold rounded-xl transition duration-150">
                    Editar Datos
                </button>
                <button onclick='cambiarPassword(<?php echo $u['id']; ?>)' class="px-3 py-1.5 text-amber-600 hover:text-amber-700 hover:bg-amber-50 dark:hover:bg-amber-900/20 text-xs font-bold rounded-xl transition duration-150">
                    Clave
                </button>
                <?php if ($u['id'] !== $_SESSION['usuario_id']): ?>
                <a href="<?php echo BASE_PATH; ?>/controllers/usuarios.php?action=eliminar&id=<?php echo $u['id']; ?>" 
                   onclick="return confirm('¿Seguro que desea desactivar a este usuario?');"
                   class="px-3 py-1.5 text-red-650 hover:text-red-750 hover:bg-red-50 dark:hover:bg-red-900/20 text-xs font-bold rounded-xl transition duration-150">
                    Desactivar
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Modal Crear / Editar -->
<div id="modal_usuario" class="hidden fixed inset-0 z-50 flex items-center justify-center modal-overlay p-4" onclick="closeModal('modal_usuario')">
    <div class="modal-sheet w-full max-w-md p-6 sm:p-8" onclick="event.stopPropagation()">
        <div class="pb-3 mb-5 border-b border-gray-100 dark:border-gray-800">
            <h3 id="modal_title" class="text-lg font-extrabold text-gray-900 dark:text-white">Nuevo Usuario</h3>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Registra o actualiza los datos del colaborador</p>
        </div>
        
        <form action="<?php echo BASE_PATH; ?>/controllers/usuarios.php" method="POST" class="space-y-4">
            <input type="hidden" name="action" id="form_action" value="crear">
            <input type="hidden" name="id" id="u_id" value="">
            
            <div class="space-y-1.5">
                <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Nombre Completo</label>
                <input type="text" name="nombre_completo" id="u_nombre" required placeholder="Nombre completo" class="apple-input">
            </div>
            <div class="space-y-1.5">
                <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Usuario (Acceso)</label>
                <input type="text" name="usuario" id="u_usuario" required placeholder="Nombre de usuario" class="apple-input font-semibold tracking-wide">
            </div>
            <div id="password_container" class="space-y-1.5">
                <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Contraseña</label>
                <input type="password" name="password" id="u_password" required placeholder="Contraseña de acceso" class="apple-input">
            </div>
            <div class="space-y-1.5">
                <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Rol de Sistema</label>
                <select name="rol" id="u_rol" class="apple-input bg-white cursor-pointer">
                    <option value="usuario">Recepcionista</option>
                    <option value="administrador">Administrador</option>
                </select>
            </div>
            
            <!-- Custom Premium Switch Toggle -->
            <div class="pt-2">
                <label class="switch-label">
                    <input type="checkbox" name="activo" id="u_activo" value="1" checked class="hidden">
                    <div class="switch-track">
                        <div class="switch-thumb"></div>
                    </div>
                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Usuario Activo</span>
                </label>
            </div>
            
            <div class="pt-5 flex justify-end gap-3 border-t border-gray-100 dark:border-gray-800 mt-6">
                <button type="button" onclick="closeModal('modal_usuario')" class="px-5 py-2.5 text-sm font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-750 rounded-xl transition-colors">
                    Cancelar
                </button>
                <button type="submit" class="px-5 py-2.5 text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl transition-colors shadow-sm">
                    Guardar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Cambiar Contraseña -->
<div id="modal_password" class="hidden fixed inset-0 z-50 flex items-center justify-center modal-overlay p-4" onclick="closeModal('modal_password')">
    <div class="modal-sheet w-full max-w-sm p-6 sm:p-8" onclick="event.stopPropagation()">
        <div class="pb-3 mb-5 border-b border-gray-100 dark:border-gray-800">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Cambiar Contraseña</h3>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Establece una nueva clave para el usuario</p>
        </div>
        
        <form action="<?php echo BASE_PATH; ?>/controllers/usuarios.php" method="POST" class="space-y-4">
            <input type="hidden" name="action" value="cambiar_password">
            <input type="hidden" name="id" id="pwd_id" value="">
            
            <div class="space-y-1.5">
                <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Nueva Contraseña</label>
                <input type="password" name="password" required placeholder="Escriba la nueva contraseña" class="apple-input">
            </div>
            
            <div class="pt-5 flex justify-end gap-3 border-t border-gray-100 dark:border-gray-800 mt-6">
                <button type="button" onclick="closeModal('modal_password')" class="px-5 py-2.5 text-sm font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-750 rounded-xl transition-colors">
                    Cancelar
                </button>
                <button type="submit" class="px-5 py-2.5 text-sm font-bold text-white bg-amber-500 hover:bg-amber-600 rounded-xl transition-colors shadow-sm">
                    Actualizar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id) {
    if (id === 'modal_crear') {
        id = 'modal_usuario';
        document.getElementById('modal_title').textContent = 'Nuevo Usuario';
        document.getElementById('form_action').value = 'crear';
        document.getElementById('u_id').value = '';
        document.getElementById('u_nombre').value = '';
        document.getElementById('u_usuario').value = '';
        document.getElementById('u_rol').value = 'usuario';
        document.getElementById('u_activo').checked = true;
        document.getElementById('password_container').style.display = 'block';
        document.getElementById('u_password').required = true;
    }
    document.getElementById(id).classList.remove('hidden');
}

function closeModal(id) {
    document.getElementById(id).classList.add('hidden');
}

function editarUsuario(u) {
    document.getElementById('modal_title').textContent = 'Editar Usuario';
    document.getElementById('form_action').value = 'editar';
    document.getElementById('u_id').value = u.id;
    document.getElementById('u_nombre').value = u.nombre_completo;
    document.getElementById('u_usuario').value = u.usuario;
    document.getElementById('u_rol').value = u.rol;
    document.getElementById('u_activo').checked = (u.activo == 1);
    
    // Ocultar campo de contraseña al editar datos básicos
    document.getElementById('password_container').style.display = 'none';
    document.getElementById('u_password').required = false;
    
    openModal('modal_usuario');
}

function cambiarPassword(id) {
    document.getElementById('pwd_id').value = id;
    document.getElementById('modal_password').classList.remove('hidden');
}

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        closeModal('modal_usuario');
        closeModal('modal_password');
    }
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
