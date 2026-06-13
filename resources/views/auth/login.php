<?php require_once ROOT_DIR . '/resources/views/main_header.php'; ?>
<div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 bg-gray-50">
    <div class="max-w-md w-full space-y-8 bg-white p-10 rounded-2xl shadow-sm border border-gray-100">
        <div>
            <div class="mx-auto flex items-center justify-center">
                <?php
                $logoSrc = file_exists(ROOT_DIR . '/public/img/mercalocal-logo.png')
                    ? BASE_URL . '/img/mercalocal-logo.png'
                    : BASE_URL . '/img/mercalocal-logo.svg';
                ?>
                <img src="<?= $logoSrc ?>" alt="Mercalocal" style="height:56px; width:auto;">
            </div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-secondary">Entra a tu cuenta</h2>
        </div>
        <?php if (isset($_GET['error'])): ?>
            <div class="bg-red-50 text-red-600 p-4 rounded-xl text-sm border border-red-100 flex items-center">
                <i class="fa-solid fa-circle-exclamation mr-2"></i><?= htmlspecialchars($_GET['error']) ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['success'])): ?>
            <div class="bg-green-50 text-green-600 p-4 rounded-xl text-sm border border-green-100 flex items-center">
                <i class="fa-solid fa-circle-check mr-2"></i><?= htmlspecialchars($_GET['success']) ?>
            </div>
        <?php endif; ?>
        <form class="mt-8 space-y-6" action="/login" method="POST">
            <!-- CSRF token para proteger el login -->
            <input type="hidden" name="csrf_token" value="<?= \App\Core\Session::generateCsrfToken() ?>">
            <div class="space-y-4 rounded-md shadow-sm">
                <div>
                    <label for="identificador" class="block text-sm font-bold text-gray-700 mb-1">Email o Teléfono</label>
                    <input id="identificador" name="identificador" type="text" required class="appearance-none rounded-xl relative block w-full px-4 py-3 border border-gray-300 placeholder-gray-400 text-gray-900 focus:outline-none focus:ring-primary bg-gray-50" placeholder="Correo electrónico o teléfono">
                </div>
                <div>
                    <label for="password" class="block text-sm font-bold text-gray-700 mb-1">Contraseña</label>
                    <input id="password" name="password" type="password" required class="appearance-none rounded-xl relative block w-full px-4 py-3 border border-gray-300 placeholder-gray-400 text-gray-900 focus:outline-none focus:ring-primary bg-gray-50" placeholder="••••••••">
                </div>
            </div>
            <div>
                <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent text-sm font-bold rounded-xl text-white bg-primary hover:bg-orange-600 transition-all">Entrar</button>
            </div>
        </form>
        <div class="flex items-center justify-between mt-4">
            <span class="text-sm text-gray-500">¿No tienes cuenta? <a href="/register" class="font-bold text-primary hover:text-orange-600">Regístrate</a></span>
            <a href="#" onclick="mostrarEnDesarrollo('El restablecimiento de contraseña por correo'); return false;" class="text-sm font-medium text-primary hover:text-green-700">
                ¿Olvidaste tu contraseña?
            </a>
        </div>
    </div>
</div>
<?php require_once ROOT_DIR . '/resources/views/layout/footer.php'; ?>