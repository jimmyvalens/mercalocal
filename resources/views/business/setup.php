<?php

// Búnker anti-errores: si el router duplica la carga, inicializamos en vacío y nos olvidamos
$categorias_padre = $categorias_padre ?? [];
$business = $business ?? [
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
    'provincia'     => ''
];

// Carga de la cabecera original de Mercalocal
require_once ROOT_DIR . '/resources/views/main_header.php';

?>

<script src="https://cdn.tailwindcss.com"></script>

<div class="min-h-screen bg-[#f9fdfa] py-12 px-4 sm:px-6 lg:px-8 flex items-center justify-center">
    <div class="max-w-2xl w-full space-y-8 bg-white p-8 md:p-10 rounded-2xl shadow-sm border border-gray-100">

        <div class="text-center">
            <div class="mx-auto w-16 h-16 bg-[#00b050] rounded-xl flex items-center justify-center shadow-lg text-white font-bold text-3xl">
                <i class="fa-solid fa-store"></i>
            </div>
            <h2 class="mt-6 text-3xl font-extrabold text-gray-900">Configura tu Perfil Comercial</h2>
            <p class="mt-2 text-sm text-gray-600">
                ¡Bienvenido a Mercalocal! Completa los detalles visuales e informativos de tu negocio para que el administrador pueda darte de alta.
            </p>
        </div>

        <?php if ($msg = \App\Core\Session::getFlash('error')): ?>
            <div class="p-4 bg-red-50 border-l-4 border-red-500 rounded-r-xl text-sm text-red-700">
                <strong>Error:</strong> <?php echo is_array($msg) ? implode(', ', $msg) : htmlspecialchars($msg); ?>
            </div>
        <?php endif; ?>

        <form id="setup_comercio" action="<?php echo BASE_URL; ?>/business/setup" method="POST" enctype="multipart/form-data" class="space-y-6" data-persist>

            <?php echo \App\Core\Session::csrfField(); ?>

            <div class="space-y-4">
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider">Identidad Visual</label>

                <div class="relative">
                    <div class="w-full h-40 bg-gray-100 rounded-xl overflow-hidden border-2 border-dashed border-gray-300 relative flex flex-col items-center justify-center group hover:border-[#00b050] transition-colors">
                        <img id="preview-hero" src="" class="absolute inset-0 w-full h-full object-cover hidden">

                        <div id="placeholder-hero" class="text-center p-4 z-10 flex flex-col items-center justify-center">
                            <span class="text-xl">🖼️</span>
                            <p class="text-xs font-semibold mt-1 text-gray-500">Subir Banner del Comercio</p>
                        </div>
                        <input type="file" id="input-hero" name="hero" accept="image/*" class="absolute inset-0 opacity-0 cursor-pointer z-20">
                    </div>

                    <div class="absolute -bottom-6 left-6 w-20 h-20 bg-white rounded-full p-1 shadow-md border border-gray-200 flex items-center justify-center overflow-hidden group/logo hover:border-[#00b050] transition-colors z-30">
                        <img id="preview-logo" src="" class="w-full h-full object-cover rounded-full hidden">

                        <div id="placeholder-logo" class="text-center p-1 z-10 flex flex-col items-center justify-center">
                            <span class="text-base">🏪</span>
                        </div>
                        <input type="file" id="input-logo" name="logo" accept="image/*" class="absolute inset-0 opacity-0 cursor-pointer z-40">
                    </div>
                </div>
                <div class="h-4"></div>
            </div>

            <div class="grid grid-cols-1 gap-4 pt-2">
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Nombre del Comercio <span class="text-red-500">*</span></label>
                    <input type="text" id="nombre" name="nombre" required value="<?php echo htmlspecialchars($business['nombre']); ?>" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#00b050] focus:border-transparent outline-none text-sm transition bg-gray-50" placeholder="Ej. Frutería Manolo o Zapatería Flores">
                </div>

                <div>
                    <label for="categoria_id" class="block text-xs font-bold text-gray-700 uppercase mb-1">Categoría del Comercio <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <select id="categoria_id" name="categoria_id" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#00b050] focus:border-transparent outline-none text-sm transition bg-gray-50 appearance-none cursor-pointer text-gray-700">
                            <option value="" disabled selected>Selecciona una categoría...</option>
                            <?php foreach ($categorias_padre as $categoria): ?>
                                <option value="<?= $categoria['id'] ?>" <?= (isset($business['categoria_id']) && $business['categoria_id'] == $categoria['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($categoria['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-gray-400">
                            <i class="fa-solid fa-chevron-down text-xs"></i>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Teléfono corporativo <span class="text-red-500">*</span></label>
                        <input type="text" name="telefono" required value="<?php echo htmlspecialchars($business['telefono']); ?>" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#00b050] focus:border-transparent outline-none text-sm transition bg-gray-50" placeholder="Ej. 600000000">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Email de contacto <span class="text-red-500">*</span></label>
                        <input type="email" name="email" required value="<?php echo htmlspecialchars($business['email']); ?>" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#00b050] focus:border-transparent outline-none text-sm transition bg-gray-50" placeholder="contacto@tucomercio.com">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Sitio Web o Redes Sociales <span class="text-gray-400 font-normal">(Opcional)</span></label>
                    <input type="url" name="web" id="web" <?php echo htmlspecialchars($business['web']); ?>" placeholder="https://..." class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#00b050] focus:border-transparent outline-none text-sm transition bg-gray-50">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Descripción Comercial <span class="text-red-500">*</span></label>
                    <textarea id="descripcion" name="descripcion" rows="3" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#00b050] focus:border-transparent outline-none text-sm transition bg-gray-50" placeholder="Describe los productos o servicios que ofreces..."><?php echo htmlspecialchars($business['descripcion']); ?></textarea>
                </div>
            </div>

            <div class="space-y-4 pt-2">
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider">Datos de Ubicación</label>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Calle / Avenida <span class="text-red-500">*</span></label>
                        <input type="text" name="calle" required value="<?php echo htmlspecialchars($business['calle'] ?? ''); ?>" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#00b050] focus:border-transparent outline-none text-sm transition bg-gray-50" placeholder="Ej. Calle Real">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Número</label>
                        <input type="text" name="numero" value="<?php echo htmlspecialchars($business['numero'] ?? ''); ?>" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#00b050] focus:border-transparent outline-none text-sm transition bg-gray-50" placeholder="Ej. 12 B">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Código Postal <span class="text-red-500">*</span></label>
                        <input type="text" name="codigo_postal" required value="<?php echo htmlspecialchars($business['codigo_postal'] ?? ''); ?>" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#00b050] focus:border-transparent outline-none text-sm transition bg-gray-50" placeholder="Ej. 06220">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Ciudad <span class="text-red-500">*</span></label>
                        <input type="text" name="ciudad" required value="<?php echo htmlspecialchars($business['ciudad'] ?? ''); ?>" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#00b050] focus:border-transparent outline-none text-sm transition bg-gray-50" placeholder="Ej. Villafranca de los Barros">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Provincia <span class="text-red-500">*</span></label>
                        <input type="text" name="provincia" required value="<?php echo htmlspecialchars($business['provincia'] ?? ''); ?>" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#00b050] focus:border-transparent outline-none text-sm transition bg-gray-50" placeholder="Ej. Badajoz">
                    </div>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row gap-4 pt-4">
                <button type="submit" class="flex-1 py-4 bg-[#ffe295] hover:bg-[#ebd082] text-black font-bold rounded-xl text-sm shadow-sm transition transform hover:-translate-y-0.5 flex justify-center items-center">
                    <i class="fa-solid fa-rocket mr-2"></i> Lanzar mi Perfil de Comercio
                </button>

                <button type="button" id="btn-previsualizar" class="px-5 py-4 bg-gray-800 hover:bg-gray-900 text-white font-semibold rounded-xl text-sm shadow-sm transition flex items-center justify-center gap-2">
                    👁️ Tarjeta Vista Previa
                </button>
            </div>
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
require_once ROOT_DIR . '/resources/views/layout/footer.php';
?>