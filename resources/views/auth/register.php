<?php require_once ROOT_DIR . '/resources/views/main_header.php'; ?>
<?php
// Recuperar valores anteriores y errores de la sesion guardados por el controlador
$old    = \App\Core\Session::get('register_old', []);
$errors = \App\Core\Session::get('register_errors', []);
// Limpiar de sesion para que no persistan en recargas manuales
\App\Core\Session::remove('register_old');
\App\Core\Session::remove('register_errors');

// Devuelve el valor antiguo escapado
$v = function (string $field) use ($old) {
    return htmlspecialchars($old[$field] ?? '');
};

// Clases del input: borde rojo si hay error, normal si no
$ic = function (string $field) use ($errors) {
    if (isset($errors[$field])) {
        return 'w-full px-4 py-3 rounded-xl border-2 border-red-400 bg-red-50 text-gray-900 placeholder-gray-400 focus:outline-none focus:border-red-500 font-inherit text-sm';
    }
    return 'w-full px-4 py-3 rounded-xl border border-gray-300 bg-gray-50 text-gray-900 placeholder-gray-400 focus:outline-none text-sm';
};
?>

<div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8" style="background:var(--ml-bg);">
    <div class="max-w-md w-full bg-white p-10 rounded-2xl shadow-sm" style="border:1px solid var(--ml-border);">

        <!-- Logo -->
        <div class="text-center mb-6">
            <div class="flex justify-center mb-3">
                <?php
                $logoSrc = file_exists(ROOT_DIR . '/public/img/mercalocal-logo.png')
                    ? BASE_URL . '/img/mercalocal-logo.png'
                    : BASE_URL . '/img/mercalocal-logo.svg';
                ?>
                <img src="<?= $logoSrc ?>" alt="Mercalocal" style="height:56px; width:auto;">
            </div>
            <h2 class="text-2xl font-extrabold" style="color:var(--ml-text);">Crea tu cuenta gratis</h2>
            <p class="text-sm mt-1" style="color:var(--ml-text-muted);">Unete para comprar, reservar y apoyar al comercio local</p>
        </div>

        <!-- Formulario -->
        <form action="<?= BASE_URL ?>/register" method="POST" novalidate class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= \App\Core\Session::generateCsrfToken() ?>">

            <!-- Nombre + Apellidos -->
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="nombre" class="block text-sm font-bold mb-1" style="color:var(--ml-text);">
                        Nombre <span class="text-red-500">*</span>
                    </label>
                    <input id="nombre" name="nombre" type="text"
                        value="<?= $v('nombre') ?>"
                        placeholder="Juan"
                        class="<?= $ic('nombre') ?>">
                    <?php if (isset($errors['nombre'])): ?>
                        <p class="mt-1 text-xs text-red-600"><i class="fa-solid fa-circle-exclamation mr-1"></i><?= htmlspecialchars($errors['nombre']) ?></p>
                    <?php endif; ?>
                </div>
                <div>
                    <label for="apellidos" class="block text-sm font-bold mb-1" style="color:var(--ml-text);">Apellidos</label>
                    <input id="apellidos" name="apellidos" type="text"
                        value="<?= $v('apellidos') ?>"
                        placeholder="Perez"
                        class="<?= $ic('apellidos') ?>">
                    <?php if (isset($errors['apellidos'])): ?>
                        <p class="mt-1 text-xs text-red-600"><i class="fa-solid fa-circle-exclamation mr-1"></i><?= htmlspecialchars($errors['apellidos']) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Correo o Teléfono -->
            <div>
                <label for="identificador" class="block text-sm font-bold mb-1" style="color:var(--ml-text);">
                    Correo electrónico o Teléfono <span class="text-red-500">*</span>
                </label>
                <input id="identificador" name="identificador" type="text"
                    value="<?= $v('identificador') ?>"
                    placeholder="correo@ejemplo.com o 600000000"
                    class="<?= $ic('identificador') ?>">
                <?php if (isset($errors['identificador'])): ?>
                    <p class="mt-1 text-xs text-red-600"><i class="fa-solid fa-circle-exclamation mr-1"></i><?= htmlspecialchars($errors['identificador']) ?></p>
                <?php endif; ?>
            </div>

            <!-- Contrasena -->
            <div>
                <label for="password" class="block text-sm font-bold mb-1" style="color:var(--ml-text);">
                    Contrasena <span class="text-red-500">*</span>
                </label>
                <input id="password" name="password" type="password"
                    placeholder="Minimo 8 caracteres"
                    class="<?= $ic('password') ?>">
                <?php if (isset($errors['password'])): ?>
                    <p class="mt-1 text-xs text-red-600"><i class="fa-solid fa-circle-exclamation mr-1"></i><?= htmlspecialchars($errors['password']) ?></p>
                <?php else: ?>
                    <p class="mt-1 text-xs" style="color:var(--ml-text-muted);">Minimo 8 caracteres</p>
                <?php endif; ?>
            </div>

            <!-- Tipo de cuenta -->
            <div>
                <label for="rol" class="block text-sm font-bold mb-1" style="color:var(--ml-text);">Tipo de cuenta</label>
                <select id="rol" name="rol"
                    class="w-full px-4 py-3 rounded-xl border border-gray-300 bg-gray-50 text-gray-900 focus:outline-none cursor-pointer text-sm">
                    <option value="USER" <?= ($old['rol'] ?? 'USER') === 'USER'     ? 'selected' : '' ?>>Soy Cliente (Comprar/Reservar)</option>
                    <option value="BUSINESS" <?= ($old['rol'] ?? '')      === 'BUSINESS' ? 'selected' : '' ?>>Soy Comercio (Vender productos)</option>
                </select>
            </div>

            <!-- Boton -->
            <div class="pt-2">
                <button type="submit" class="btn-primary w-full py-3 text-base">
                    Registrarme
                </button>
            </div>
        </form>

        <div class="text-center mt-4">
            <span class="text-sm" style="color:var(--ml-text-muted);">
                Ya tienes cuenta?
                <a href="<?= BASE_URL ?>/login" class="font-bold" style="color:var(--ml-green);">Inicia sesion</a>
            </span>
        </div>
    </div>
</div>

<?php require_once ROOT_DIR . '/resources/views/layout/footer.php'; ?>