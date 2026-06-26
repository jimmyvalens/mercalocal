<?php
// 1. RESTRICCIÓN DE SEGURIDAD E IMPORTACIONES
use App\Core\Session;

// 1. Recuperamos los datos antiguos y los errores específicos si existen
// 💡 Limpio: Como tenemos el 'use' arriba, ya no hace falta poner \App\Core\
$old = Session::get('setup_old') ?? [];
$errors = Session::get('field_errors') ?? [];

// Limpiamos los flashes temporales de los inputs usando tu método remove()
Session::remove('setup_old');
Session::remove('field_errors');

// 2. DETECCIÓN PREVIA DE DATOS (Esencial ponerlo arriba del todo)
$business = $business ?? [];
$isEdit   = !empty($business['id']);

// 3. DETECCIÓN DEL ROL
$rol = $rol ?? Session::get('user_role', 'BUSINESS');
$rol_limpio = strtolower($rol);
$is_admin = ($rol_limpio === 'admin');

// 4. CONTROL DE ACCIÓN ASOCIADA
$action = $action ?? ($isEdit ? 'edit' : ($is_admin ? 'create' : 'setup'));


// ==========================================
// CONFIGURACIÓN DINÁMICA BASADA EN VARIABLES YA COBRADAS
// ==========================================

// 1. Configuración de la ruta "Volver atrás"
$back_url = $is_admin
    ? BASE_URL . '/admin/businesses'
    : ($isEdit ? BASE_URL . '/business/dashboard' : BASE_URL . '/logout');

// 2. Diccionario de textos (Títulos, Subtítulos y Botones) según el Rol y la Acción
if ($is_admin) {
    $titulo       = ($action === 'create') ? 'Registrar Nuevo Comercio' : 'Editar Comercio';
    $subtitulo    = ($action === 'create') ? 'Introduce la información básica y localización para dar de alta un nuevo negocio.' : 'Modifica los datos y la ubicación del comercio seleccionado.';
    $texto_boton  = ($action === 'create') ? 'Crear Comercio' : 'Guardar Cambios';
} else {
    $titulo       = ($action === 'setup') ? 'Configuración Inicial de tu Comercio' : 'Mi Perfil de Comercio';
    $subtitulo    = ($action === 'setup') ? '¡Estás a un paso de lanzar tu negocio! Rellena los datos para activar tu cuenta.' : 'Gestiona la información pública, imágenes y contacto de tu negocio.';
    $texto_boton  = ($action === 'setup') ? 'Lanzar Comercio' : 'Actualizar Comercio';
}

// 3. URL de envío del formulario (Action)
if ($is_admin) {
    $actionUrl = $isEdit
        ? BASE_URL . '/admin/business/' . $business['id'] . '/update'
        : BASE_URL . '/admin/business/store';
} else {
    $actionUrl = $isEdit
        ? BASE_URL . '/business/dashboard/settings/update'
        : BASE_URL . '/business/setup';
}

// 4. Búnker anti-errores para el array $business
$categorias_padre = $categorias_padre ?? [];
$business = array_merge([
    'nombre'        => '',
    'telefono'      => '',
    'email'         => '',
    'web'           => '',
    'descripcion'   => '',
    'categoria_id'  => '',
    'calle'         => '',
    'numero'        => '',
    'codigo_postal' => '',
    'ciudad'        => '',
    'provincia'     => '',
    'activo'        => NULL,
    'user_id'       => NULL,
    'logo_path'     => '',
    'hero_path'     => ''
], $business);

// 5. Carga de la cabecera original de Mercalocal
require_once ROOT_DIR . '/resources/views/main_header.php';
?>

<script src="https://cdn.tailwindcss.com"></script>

<div class="min-h-screen bg-[#f9fdfa] py-12 px-4 sm:px-6 lg:px-8 flex items-center justify-center">
    <div class="max-w-2xl w-full space-y-8 bg-white p-8 md:p-10 rounded-2xl shadow-sm border border-gray-100">

        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">
                <?php
                if ($is_admin) {
                    echo $isEdit ? 'Editar Comercio desde Admin' : 'Registrar Nuevo Comercio';
                } else {
                    echo $isEdit ? 'Modificar mi Perfil Comercial' : 'Configuración Inicial de tu Comercio';
                }
                ?>
            </h1>
            <p class="text-sm text-gray-500">
                <?php echo $is_admin ? 'Gestión de registros del marketplace.' : 'Mantén actualizados los datos de cara a tus clientes locales.'; ?>
            </p>
        </div>

        <?php if ($msg = \App\Core\Session::getFlash()): ?>
            <div class="p-4 bg-red-50 border-l-4 border-red-500 rounded-r-xl text-sm text-red-700">
                <strong>Error:</strong> <?php echo is_array($msg) ? implode(', ', $msg) : htmlspecialchars($msg); ?>
            </div>
        <?php endif; ?>

        <form id="setup_comercio" action="<?php echo $actionUrl; ?>" method="POST" enctype="multipart/form-data" class="space-y-6" data-persist>

            <?php echo \App\Core\Session::csrfField(); ?>

            <div class="space-y-4">
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider flex items-center gap-2">
                    <i class="fa-solid fa-image text-gray-400"></i> Identidad Visual
                </label>

                <div class="relative">

                    <div class="w-full h-40 bg-gray-100 rounded-xl overflow-hidden border-2 border-dashed border-gray-300 relative flex flex-col items-center justify-center group hover:border-[#00b050] transition-colors shadow-inner">

                        <?php $has_banner = !empty($business['hero_path']); ?>

                        <img id="preview-hero"
                            src="<?php echo $has_banner ? BASE_URL . '/' . $business['hero_path'] : ''; ?>"
                            class="absolute inset-0 w-full h-full object-cover <?php echo $has_banner ? '' : 'hidden'; ?>">

                        <div id="placeholder-hero" class="text-center p-4 z-10 flex flex-col items-center justify-center <?php echo $has_banner ? 'hidden' : ''; ?>">
                            <i class="fa-solid fa-panorama text-2xl text-gray-400 group-hover:text-[#00b050] transition-colors mb-1"></i>
                            <p class="text-xs font-bold text-gray-500 group-hover:text-gray-700 transition-colors">Subir Banner del Comercio</p>
                            <p class="text-[10px] text-gray-400 mt-0.5">Dimensión recomendada: 1200x400px</p>
                        </div>

                        <input type="file" id="input-hero" name="hero" accept="image/*" class="absolute inset-0 opacity-0 cursor-pointer z-20">
                    </div>


                    <div class="absolute -bottom-6 left-6 w-20 h-20 bg-white rounded-full p-1 shadow-md border border-gray-200 flex items-center justify-center overflow-hidden group/logo hover:border-[#00b050] transition-colors z-30">

                        <?php $has_logo = !empty($business['logo_path']); ?>

                        <img id="preview-logo"
                            src="<?php echo $has_logo ? BASE_URL . '/' . $business['logo_path'] : ''; ?>"
                            class="w-full h-full object-cover rounded-full <?php echo $has_logo ? '' : 'hidden'; ?>">

                        <div id="placeholder-logo" class="text-center p-1 z-10 flex flex-col items-center justify-center <?php echo $has_logo ? 'hidden' : ''; ?>">
                            <i class="fa-solid fa-store text-xl text-gray-400 group-hover/logo:text-[#00b050] transition-colors"></i>
                        </div>

                        <input type="file" id="input-logo" name="logo" accept="image/*" class="absolute inset-0 opacity-0 cursor-pointer z-40">
                    </div>
                </div>

                <div class="h-6"></div>
            </div>

            <div class="grid grid-cols-1 gap-4 pt-2">
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Nombre del Comercio <span class="text-red-500">*</span></label>
                    <input type="text" id="nombre" name="nombre" required
                        value="<?php echo htmlspecialchars($old['nombre'] ?? $business['nombre'] ?? ''); ?>"
                        class="w-full px-4 py-3 border rounded-xl outline-none text-sm transition bg-gray-50 focus:ring-2 <?= isset($errors['nombre']) ? 'border-red-500 focus:ring-red-500 focus:border-transparent' : 'border-gray-300 focus:ring-[#00b050] focus:border-transparent' ?>"
                        placeholder="Ej. Frutería Manolo o Zapatería Flores">
                    <?php if (isset($errors['nombre'])): ?>
                        <p class="mt-1 text-xs text-red-600 font-semibold"><i class="fa-solid fa-circle-exclamation mr-1"></i><?= $errors['nombre'] ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label for="categoria_id" class="block text-xs font-bold text-gray-700 uppercase mb-1">Categoría del Comercio <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <select id="categoria_id" name="categoria_id" required
                            class="w-full px-4 py-3 border rounded-xl outline-none text-sm transition bg-gray-50 appearance-none cursor-pointer text-gray-700 focus:ring-2 <?= isset($errors['categoria_id']) ? 'border-red-500 focus:ring-red-500 focus:border-transparent' : 'border-gray-300 focus:ring-[#00b050] focus:border-transparent' ?>">

                            <?php $selectedCat = (int)($old['categoria_id'] ?? $business['id_categoria'] ?? $business['categoria_id'] ?? 0); ?>
                            <option value="" disabled <?= empty($selectedCat) ? 'selected' : '' ?>>Selecciona una categoría...</option>

                            <?php foreach ($categorias_padre as $categoria): ?>
                                <option value="<?= $categoria['id'] ?>" <?= ($selectedCat === (int)$categoria['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($categoria['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-gray-400">
                            <i class="fa-solid fa-chevron-down text-xs"></i>
                        </div>
                    </div>
                    <?php if (isset($errors['categoria_id'])): ?>
                        <p class="mt-1 text-xs text-red-600 font-semibold"><i class="fa-solid fa-circle-exclamation mr-1"></i><?= $errors['categoria_id'] ?></p>
                    <?php endif; ?>
                </div>

                <?php if ($is_admin): ?>
                    <div class="relative">
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Asignar Propietario (Buscar por Nombre o Email)<span class="text-red-500 font-black ml-0.5">*</span></label>
                        <div class="relative">
                            <input type="text" id="user-search-input" autocomplete="off" placeholder="Escribe para buscar usuario..."
                                value="<?php echo htmlspecialchars($old['user_search_text'] ?? $business['owner_name'] ?? $business['user_name'] ?? ''); ?>"
                                class="w-full px-4 py-2 pr-10 border rounded-xl outline-none text-sm transition focus:ring-2 <?= isset($errors['user_id']) ? 'border-red-500 focus:ring-red-500' : 'border-gray-200 focus:ring-[#059669]' ?>">
                            <span id="search-icon" class="absolute right-3 top-2.5 text-gray-400 text-sm">🔍</span>
                        </div>
                        <input type="hidden" id="user_id_hidden" name="user_id" value="<?php echo htmlspecialchars($old['user_id'] ?? $business['user_id'] ?? ''); ?>" required>
                        <div id="autocomplete-results" class="absolute z-50 w-full bg-white border border-gray-200 rounded-xl mt-1 shadow-lg max-h-48 overflow-y-auto hidden"></div>
                        <?php if (isset($errors['user_id'])): ?>
                            <p class="mt-1 text-xs text-red-600 font-semibold"><i class="fa-solid fa-circle-exclamation mr-1"></i><?= $errors['user_id'] ?></p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Teléfono corporativo <span class="text-red-500">*</span></label>
                        <input type="text" name="telefono" required
                            value="<?php echo htmlspecialchars($old['telefono'] ?? $business['telefono'] ?? ''); ?>"
                            class="w-full px-4 py-3 border rounded-xl outline-none text-sm transition bg-gray-50 focus:ring-2 <?= isset($errors['telefono']) ? 'border-red-500 focus:ring-red-500 focus:border-transparent' : 'border-gray-300 focus:ring-[#00b050] focus:border-transparent' ?>"
                            placeholder="Ej. 600000000">
                        <?php if (isset($errors['telefono'])): ?>
                            <p class="mt-1 text-xs text-red-600 font-semibold"><i class="fa-solid fa-circle-exclamation mr-1"></i><?= $errors['telefono'] ?></p>
                        <?php endif; ?>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Email de contacto <span class="text-red-500">*</span></label>
                        <input type="email" name="email" required
                            value="<?php echo htmlspecialchars($old['email'] ?? $business['email'] ?? ''); ?>"
                            class="w-full px-4 py-3 border rounded-xl outline-none text-sm transition bg-gray-50 focus:ring-2 <?= isset($errors['email']) ? 'border-red-500 focus:ring-red-500 focus:border-transparent' : 'border-gray-300 focus:ring-[#00b050] focus:border-transparent' ?>"
                            placeholder="contacto@tucomercio.com">
                        <?php if (isset($errors['email'])): ?>
                            <p class="mt-1 text-xs text-red-600 font-semibold"><i class="fa-solid fa-circle-exclamation mr-1"></i><?= $errors['email'] ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Sitio Web o Redes Sociales <span class="text-gray-400 font-normal">(Opcional)</span></label>
                    <input type="url" name="web" id="web"
                        value="<?php echo htmlspecialchars($old['web'] ?? $business['web'] ?? ''); ?>"
                        placeholder="https://..."
                        class="w-full px-4 py-3 border rounded-xl outline-none text-sm transition bg-gray-50 focus:ring-2 <?= isset($errors['web']) ? 'border-red-500 focus:ring-red-500 focus:border-transparent' : 'border-gray-300 focus:ring-[#00b050] focus:border-transparent' ?>">
                    <?php if (isset($errors['web'])): ?>
                        <p class="mt-1 text-xs text-red-600 font-semibold"><i class="fa-solid fa-circle-exclamation mr-1"></i><?= $errors['web'] ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Descripción Comercial <span class="text-red-500">*</span></label>
                    <textarea id="descripcion" name="descripcion" rows="3" required
                        class="w-full px-4 py-3 border rounded-xl outline-none text-sm transition bg-gray-50 focus:ring-2 <?= isset($errors['descripcion']) ? 'border-red-500 focus:ring-red-500 focus:border-transparent' : 'border-gray-300 focus:ring-[#00b050] focus:border-transparent' ?>"
                        placeholder="Describe los productos o servicios que ofreces..."><?php echo htmlspecialchars($old['descripcion'] ?? $business['descripcion'] ?? ''); ?></textarea>
                    <?php if (isset($errors['descripcion'])): ?>
                        <p class="mt-1 text-xs text-red-600 font-semibold"><i class="fa-solid fa-circle-exclamation mr-1"></i><?= $errors['descripcion'] ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="space-y-4 pt-2">
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider">Datos de Ubicación</label>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Calle / Avenida <span class="text-red-500">*</span></label>
                        <input type="text" name="calle" required
                            value="<?php echo htmlspecialchars($old['calle'] ?? $business['calle'] ?? ''); ?>"
                            class="w-full px-4 py-3 border rounded-xl outline-none text-sm transition bg-gray-50 focus:ring-2 <?= isset($errors['calle']) ? 'border-red-500 focus:ring-red-500 focus:border-transparent' : 'border-gray-300 focus:ring-[#00b050] focus:border-transparent' ?>"
                            placeholder="Ej. Calle Real">
                        <?php if (isset($errors['calle'])): ?>
                            <p class="mt-1 text-xs text-red-600 font-semibold"><i class="fa-solid fa-circle-exclamation mr-1"></i><?= $errors['calle'] ?></p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Número</label>
                        <input type="text" name="numero"
                            value="<?php echo htmlspecialchars($old['numero'] ?? $business['numero'] ?? ''); ?>"
                            class="w-full px-4 py-3 border rounded-xl outline-none text-sm transition bg-gray-50 focus:ring-2 <?= isset($errors['numero']) ? 'border-red-500 focus:ring-red-500 focus:border-transparent' : 'border-gray-300 focus:ring-[#00b050] focus:border-transparent' ?>"
                            placeholder="Ej. 12 B">
                        <?php if (isset($errors['numero'])): ?>
                            <p class="mt-1 text-xs text-red-600 font-semibold"><i class="fa-solid fa-circle-exclamation mr-1"></i><?= $errors['numero'] ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Código Postal <span class="text-red-500">*</span></label>
                        <input type="text" name="codigo_postal" required
                            value="<?php echo htmlspecialchars($old['codigo_postal'] ?? $business['codigo_postal'] ?? ''); ?>"
                            class="w-full px-4 py-3 border rounded-xl outline-none text-sm transition bg-gray-50 focus:ring-2 <?= isset($errors['codigo_postal']) ? 'border-red-500 focus:ring-red-500 focus:border-transparent' : 'border-gray-300 focus:ring-[#00b050] focus:border-transparent' ?>"
                            placeholder="Ej. 06220">
                        <?php if (isset($errors['codigo_postal'])): ?>
                            <p class="mt-1 text-xs text-red-600 font-semibold"><i class="fa-solid fa-circle-exclamation mr-1"></i><?= $errors['codigo_postal'] ?></p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Ciudad <span class="text-red-500">*</span></label>
                        <input type="text" name="ciudad" required
                            value="<?php echo htmlspecialchars($old['ciudad'] ?? $business['ciudad'] ?? ''); ?>"
                            class="w-full px-4 py-3 border rounded-xl outline-none text-sm transition bg-gray-50 focus:ring-2 <?= isset($errors['ciudad']) ? 'border-red-500 focus:ring-red-500 focus:border-transparent' : 'border-gray-300 focus:ring-[#00b050] focus:border-transparent' ?>"
                            placeholder="Ej. Villafranca de los Barros">
                        <?php if (isset($errors['ciudad'])): ?>
                            <p class="mt-1 text-xs text-red-600 font-semibold"><i class="fa-solid fa-circle-exclamation mr-1"></i><?= $errors['ciudad'] ?></p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Provincia <span class="text-red-500">*</span></label>
                        <input type="text" name="provincia" required
                            value="<?php echo htmlspecialchars($old['provincia'] ?? $business['provincia'] ?? ''); ?>"
                            class="w-full px-4 py-3 border rounded-xl outline-none text-sm transition bg-gray-50 focus:ring-2 <?= isset($errors['provincia']) ? 'border-red-500 focus:ring-red-500 focus:border-transparent' : 'border-gray-300 focus:ring-[#00b050] focus:border-transparent' ?>"
                            placeholder="Ej. Badajoz">
                        <?php if (isset($errors['provincia'])): ?>
                            <p class="mt-1 text-xs text-red-600 font-semibold"><i class="fa-solid fa-circle-exclamation mr-1"></i><?= $errors['provincia'] ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if (!$is_admin): ?>
                <div class="flex flex-col sm:flex-row gap-4 pt-4">

                    <a href="<?php echo $back_url; ?>" class="px-5 py-4 border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 font-semibold rounded-xl text-sm shadow-sm transition transform hover:-translate-y-0.5 hover:shadow-md flex items-center justify-center gap-2 text-center">
                        <i class="fa-solid fa-arrow-left opacity-70"></i>
                        <?php echo $isEdit ? 'Cancelar y Volver' : 'Salir / Cancelar'; ?>
                    </a>

                    <?php if ($isEdit): ?>
                        <button type="submit" class="flex-1 py-4 bg-[#00b050] hover:bg-[#009443] text-white font-bold rounded-xl text-sm shadow-sm transition transform hover:-translate-y-0.5 hover:shadow-md flex justify-center items-center">
                            <i class="fa-solid fa-floppy-disk mr-2"></i> Actualizar mi Perfil
                        </button>
                    <?php else: ?>
                        <button type="submit" class="flex-1 py-4 bg-[#ffe295] hover:bg-[#ebd082] text-black font-bold rounded-xl text-sm shadow-sm transition transform hover:-translate-y-0.5 hover:shadow-md flex justify-center items-center">
                            <i class="fa-solid fa-rocket mr-2"></i> Lanzar mi Perfil de Comercio
                        </button>
                    <?php endif; ?>

                    <button type="button" id="btn-previsualizar" class="px-5 py-4 bg-orange-500 hover:bg-orange-600 text-white font-semibold rounded-xl text-sm shadow-sm transition transform hover:-translate-y-0.5 hover:shadow-md flex items-center justify-center gap-2">
                        <i class="fa-solid fa-eye"></i> Vista Previa Tarjeta
                    </button>
                </div>

            <?php else: ?>
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl border border-gray-100">
                    <div>
                        <h4 class="text-sm font-bold text-gray-800">Comercio Activo en el Marketplace</h4>
                        <p class="text-xs text-gray-400">Determina si los clientes pueden ver el comercio de inmediato.</p>
                    </div>
                    <label class="inline-flex items-center cursor-pointer relative">
                        <input type="checkbox" name="activo" value="1" <?php echo (($business['activo'] ?? 0) == 1) ? 'checked' : ''; ?> class="sr-only peer">
                        <div class="relative w-12 h-6 bg-gray-200 rounded-full peer-focus:outline-none peer-checked:bg-[#059669] after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:h-5 after:w-5 after:bg-white after:border after:border-gray-300 after:rounded-full after:transition-all peer-checked:after:translate-x-6"></div>
                    </label>
                </div>

                <div class="flex flex-col sm:flex-row gap-4 pt-2">
                    <a href="<?php echo $back_url; ?>" class="px-5 py-3 border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 font-semibold rounded-xl text-sm shadow-sm transition transform hover:-translate-y-0.5 hover:shadow-md flex items-center justify-center gap-2 text-center">
                        Cancelar
                    </a>

                    <button type="submit" class="flex-1 py-3 bg-[#059669] hover:bg-[#047857] text-white font-semibold rounded-xl text-sm shadow-sm transition transform hover:-translate-y-0.5 hover:shadow-md">
                        <?php echo $isEdit ? 'Guardar Cambios' : 'Crear y Publicar'; ?>
                    </button>

                    <button type="button" id="btn-previsualizar" class="px-5 py-3 bg-orange-500 hover:bg-orange-600 text-white font-semibold rounded-xl text-sm shadow-sm transition transform hover:-translate-y-0.5 hover:shadow-md flex items-center justify-center gap-2">
                        <i class="fa-solid fa-eye"></i> Vista Previa
                    </button>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<div id="modal-vista-previa" class="fixed inset-0 bg-gray-900 bg-opacity-60 flex items-center justify-center z-50 hidden p-4 transition-all duration-300">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden border border-gray-100 transform scale-95 transition-transform duration-300">
        <div class="px-5 py-3 bg-gray-50 border-b flex justify-between items-center">
            <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider">Tarjeta Pública</h3>
            <button type="button" id="btn-cerrar-previa" class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
        </div>

        <div class="p-6 bg-gray-50 flex justify-center">
            <div class="bg-white rounded-2xl shadow-md overflow-hidden w-full border border-gray-200 relative">

                <div class="h-36 bg-gray-300 relative">
                    <img id="modal-prev-hero" src="" class="w-full h-full object-cover">
                </div>

                <div class="absolute top-24 left-4 w-16 h-16 bg-white rounded-full p-0.5 shadow border overflow-hidden">
                    <img id="modal-prev-logo" src="" class="w-full h-full object-cover rounded-full">
                </div>

                <div class="p-5 pt-10">
                    <h4 id="modal-prev-nombre" class="text-lg font-bold text-gray-900 truncate">Nombre</h4>
                    <p id="modal-prev-desc" class="text-sm text-gray-500 line-clamp-2 mt-1">Texto...</p>

                    <div class="mt-4 pt-2 border-t flex justify-between items-center text-sm text-[#059669] font-semibold">
                        <span>Visitar tienda &rarr;</span>
                        <span class="bg-[#ecfdf5] text-[#047857] px-2 py-0.5 rounded-full text-[11px]">Nuevo</span>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
    window.BASE_URL = "<?= BASE_URL ?>";
</script>
<script src="<?= BASE_URL ?>/js/main.js"></script>

<?php
// Carga del footer original de Mercalocal
require_once ROOT_DIR . '/resources/views/layout/footer_dashboard.php';
?>