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

<div class="max-w-6xl mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Usuarios del Sistema</h1>
            <p class="text-sm text-gray-500 mt-1">Gestión de accesos y roles</p>
        </div>
        <div class="flex gap-3">
            <a href="<?php echo BASE_PATH; ?>/index.php" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                ← Volver al Dashboard
            </a>
            <button onclick="openModal('modal_crear')" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition-colors shadow-sm flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Añadir Usuario
            </button>
        </div>
    </div>

    <?php if (isset($_GET['msg'])): ?>
        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg text-sm">
            <?php echo htmlspecialchars($_GET['msg']); ?>
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg text-sm">
            <?php echo htmlspecialchars($_GET['error']); ?>
        </div>
    <?php endif; ?>

    <div class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden shadow-sm">
        <table class="w-full text-left text-sm whitespace-nowrap">
            <thead class="bg-gray-50 dark:bg-slate-800/50 uppercase tracking-wider text-gray-500 text-xs border-b border-gray-200 dark:border-gray-700">
                <tr>
                    <th class="px-6 py-4 font-medium">Nombre / Usuario</th>
                    <th class="px-6 py-4 font-medium">Rol</th>
                    <th class="px-6 py-4 font-medium">Estado</th>
                    <th class="px-6 py-4 font-medium">Último Acceso</th>
                    <th class="px-6 py-4 font-medium text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700 text-gray-700 dark:text-gray-300">
                <?php foreach ($usuarios as $u): ?>
                <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/50 transition">
                    <td class="px-6 py-4">
                        <div class="font-medium text-gray-900 dark:text-white"><?php echo htmlspecialchars($u['nombre_completo']); ?></div>
                        <div class="text-xs text-gray-500 mt-0.5">@<?php echo htmlspecialchars($u['usuario']); ?></div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium <?php echo $u['rol'] === 'administrador' ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800'; ?>">
                            <?php echo ucfirst($u['rol']); ?>
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium <?php echo $u['activo'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                            <?php echo $u['activo'] ? 'Activo' : 'Inactivo'; ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 text-xs text-gray-500">
                        <?php echo $u['ultimo_acceso'] ? date('d/m/Y H:i', strtotime($u['ultimo_acceso'])) : 'Nunca'; ?>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <button onclick='editarUsuario(<?php echo json_encode($u); ?>)' class="text-blue-600 hover:text-blue-900 font-medium px-2">Editar</button>
                        <button onclick='cambiarPassword(<?php echo $u['id']; ?>)' class="text-orange-600 hover:text-orange-900 font-medium px-2">Clave</button>
                        <?php if ($u['id'] !== $_SESSION['usuario_id']): ?>
                        <a href="<?php echo BASE_PATH; ?>/controllers/usuarios.php?action=eliminar&id=<?php echo $u['id']; ?>" 
                           onclick="return confirm('¿Seguro que desea desactivar a este usuario?');"
                           class="text-red-600 hover:text-red-900 font-medium px-2">Desactivar</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Crear / Editar -->
<div id="modal_usuario" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4" onclick="closeModal('modal_usuario')">
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl w-full max-w-md p-6" onclick="event.stopPropagation()">
        <h3 id="modal_title" class="text-lg font-bold text-gray-900 dark:text-white mb-4">Nuevo Usuario</h3>
        <form action="<?php echo BASE_PATH; ?>/controllers/usuarios.php" method="POST" class="space-y-4">
            <input type="hidden" name="action" id="form_action" value="crear">
            <input type="hidden" name="id" id="u_id" value="">
            
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nombre Completo</label>
                <input type="text" name="nombre_completo" id="u_nombre" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-slate-900">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Usuario (Login)</label>
                <input type="text" name="usuario" id="u_usuario" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-slate-900">
            </div>
            <div id="password_container">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Contraseña</label>
                <input type="password" name="password" id="u_password" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-slate-900">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Rol</label>
                <select name="rol" id="u_rol" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-slate-900 bg-white">
                    <option value="usuario">Usuario Recepcionista</option>
                    <option value="administrador">Administrador</option>
                </select>
            </div>
            <div class="flex items-center gap-2 mt-2">
                <input type="checkbox" name="activo" id="u_activo" value="1" checked class="w-4 h-4 text-slate-900 focus:ring-slate-900 border-gray-300 rounded">
                <label class="text-sm text-gray-700 dark:text-gray-300">Usuario Activo</label>
            </div>
            
            <div class="pt-5 flex justify-end gap-3 border-t border-gray-100 dark:border-gray-700 mt-6">
                <button type="button" onclick="closeModal('modal_usuario')" class="px-5 py-2.5 text-sm font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600 rounded-xl transition-colors">
                    Cancelar
                </button>
                <button type="submit" class="px-5 py-2.5 text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl transition-colors shadow-sm">
                    Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Cambiar Contraseña -->
<div id="modal_password" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4" onclick="closeModal('modal_password')">
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl w-full max-w-sm p-6" onclick="event.stopPropagation()">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Resetear Contraseña</h3>
        <form action="<?php echo BASE_PATH; ?>/controllers/usuarios.php" method="POST" class="space-y-4">
            <input type="hidden" name="action" value="cambiar_password">
            <input type="hidden" name="id" id="pwd_id" value="">
            
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nueva Contraseña</label>
                <input type="password" name="password" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500">
            </div>
            
            <div class="pt-5 flex justify-end gap-3 border-t border-gray-100 dark:border-gray-700 mt-6">
                <button type="button" onclick="closeModal('modal_password')" class="px-5 py-2.5 text-sm font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600 rounded-xl transition-colors">
                    Cancelar
                </button>
                <button type="submit" class="px-5 py-2.5 text-sm font-bold text-white bg-amber-500 hover:bg-amber-600 rounded-xl transition-colors shadow-sm">
                    Actualizar Contraseña
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
        document.getElementById('password_container').classList.remove('hidden');
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
    
    // Ocultar campo de contraseña al editar datos
    document.getElementById('password_container').classList.add('hidden');
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
