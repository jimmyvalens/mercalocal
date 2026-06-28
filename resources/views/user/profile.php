<?php
$user = $user ?? \App\Models\User::findById(\App\Core\Session::get('user_id'));
if (!$user) {
    \App\Core\Session::destroy();
    header('Location: ' . BASE_URL . '/login');
    exit;
}

$role = strtoupper($user->rol ?? '');
$dashboardPath = match ($role) {
    'ADMIN' => 'admin/dashboard',
    'BUSINESS' => 'business/dashboard',
    default => 'user/dashboard',
};

require_once ROOT_DIR . '/resources/views/main_header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 w-full">

    <div class="flex items-center justify-between gap-4 mb-8 max-w-3xl mx-auto">
        <div class="flex items-center gap-3">
            <i class="fa-solid fa-user-circle text-primary text-2xl sm:text-3xl"></i>
            <h2 class="text-2xl sm:text-3xl font-bold text-secondary m-0">Mi Perfil</h2>
        </div>
        <a href="<?= BASE_URL ?>/user/dashboard" class="text-gray-500 hover:text-primary transition-colors flex items-center gap-2">
            <i class="fa-solid fa-arrow-left"></i> Volver al panel
        </a>
    </div>

    <div class="bg-white shadow-sm overflow-hidden sm:rounded-2xl border border-gray-100 p-8 max-w-3xl mx-auto">
        <form class="space-y-6 flex flex-col items-center sm:items-stretch" action="<?= BASE_URL ?>/user/profile/update" method="POST" enctype="multipart/form-data">
            <?= \App\Core\Session::csrfField() ?>
            <div class="flex flex-col sm:flex-row items-center gap-6 mb-8 justify-center sm:justify-start">
                <div class="relative cursor-pointer w-24 h-24 flex-shrink-0" id="avatar-container">
                    <?php if (!empty($user->imagen)): ?>
                        <img id="avatar-img" src="<?= BASE_URL ?>/<?= htmlspecialchars($user->imagen) ?>" class="w-24 h-24 rounded-full object-cover shadow-inner border-2 border-white ring-4 ring-orange-50" alt="Foto de perfil">
                    <?php else: ?>
                        <div id="avatar-placeholder" class="w-24 h-24 bg-orange-100 text-primary rounded-full flex items-center justify-center text-4xl shadow-inner border-2 border-white ring-4 ring-orange-50">
                            <i class="fa-solid fa-user"></i>
                        </div>
                    <?php endif; ?>
                    <label class="absolute bottom-0 right-0 z-10 flex items-center justify-center w-10 h-10 bg-primary text-white rounded-full shadow-lg cursor-pointer hover:bg-orange-600 transition-colors border-4 border-white" style="margin-right: -4px; margin-bottom: -4px;" title="Cambiar foto">
                        <i class="fa-solid fa-camera text-base"></i>
                        <input type="file" name="imagen" id="imagen-input" class="hidden" accept="image/*">
                    </label>
                </div>
                <div class="flex-grow text-center sm:text-left">
                    <h2 class="text-2xl font-bold text-gray-900"><?= htmlspecialchars($user->nombre . ' ' . ($user->apellidos ?? '')) ?></h2>
                    <p class="text-sm text-gray-500 font-mono mt-1 px-3 py-1 bg-gray-50 rounded-full inline-block border border-gray-200">ID: <?= $user->id ?></p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 w-full">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Nombre</label>
                    <input type="text" name="nombre" value="<?= htmlspecialchars($user->nombre) ?>" class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-xl bg-gray-50 text-gray-900 focus:outline-none focus:ring-primary focus:border-primary transition-colors">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Apellidos</label>
                    <input type="text" name="apellidos" value="<?= htmlspecialchars($user->apellidos ?? '') ?>" class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-xl bg-gray-50 text-gray-900 focus:outline-none focus:ring-primary focus:border-primary transition-colors">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 w-full mt-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Correo Electrónico</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fa-solid fa-envelope text-gray-400"></i>
                        </div>
                        <input type="email" value="<?= htmlspecialchars($user->email) ?>" class="pl-10 appearance-none block w-full px-4 py-3 border border-gray-300 rounded-xl bg-gray-50 text-gray-900 focus:outline-none focus:ring-primary transition-colors" readonly>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Teléfono</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fa-solid fa-phone text-gray-400"></i>
                        </div>
                        <input type="text" name="telefono" value="<?= htmlspecialchars($user->telefono ?? '') ?>" class="pl-10 appearance-none block w-full px-4 py-3 border border-gray-300 rounded-xl bg-gray-50 text-gray-900 focus:outline-none focus:ring-primary transition-colors">
                    </div>
                </div>
            </div>

            <div class="w-full mt-6">
                <label class="block text-sm font-bold text-gray-700 mb-2">Dirección <span class="text-xs font-normal text-gray-500 ml-2">(Para envíos a domicilio)</span></label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fa-solid fa-location-dot text-gray-400"></i>
                    </div>
                    <input type="text" name="direccion" value="<?= htmlspecialchars($user->direccion ?? '') ?>" placeholder="Ej: Calle Principal 123, Piso 2A" class="pl-10 appearance-none block w-full px-4 py-3 border border-gray-300 rounded-xl bg-gray-50 text-gray-900 focus:outline-none focus:ring-primary transition-colors">
                </div>
            </div>

            <div class="w-full mt-8 pt-6 border-t border-gray-100 text-right">
                <button type="submit" class="bg-primary hover:bg-green-700 text-white font-bold py-2.5 px-6 rounded-xl transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                    Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const fileInput = document.getElementById('imagen-input');
        const container = document.getElementById('avatar-container');

        fileInput.addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    let img = document.getElementById('avatar-img');
                    let placeholder = document.getElementById('avatar-placeholder');

                    if (placeholder && !img) {
                        img = document.createElement('img');
                        img.id = 'avatar-img';
                        img.className = 'w-24 h-24 rounded-full object-cover shadow-inner border-2 border-white ring-4 ring-orange-50';
                        img.alt = 'Foto de perfil';
                        container.insertBefore(img, placeholder);
                        placeholder.style.display = 'none';
                    }

                    if (img) {
                        img.src = e.target.result;
                    }
                }
                reader.readAsDataURL(this.files[0]);
            }
        });
    });
</script>

<?php require_once ROOT_DIR . '/resources/views/layout/footer.php'; ?>