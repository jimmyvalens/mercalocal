<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>
<?php
// =========================================================================
// Blindaje para Intelephense y PHP Notices
// =========================================================================
$business = $business ?? [
    'id' => null,
    'nombre' => '',
    'descripcion' => '',
    'telefono' => '',
    'email' => '',
    'web' => '',
    'user_id' => '',
    'activo' => 1,
    'logo_path' => '',
    'hero_path' => '',
    'owner_name' => '',
    'categoria_id' => '',
    // Nuevos campos
    'calle' => '',
    'numero' => '',
    'codigo_postal' => '',
    'ciudad' => '',
    'provincia' => ''
];

$isEdit = !empty($business['id']);
$actionUrl = $isEdit ? BASE_URL . '/admin/business/' . $business['id'] . '/update' : BASE_URL . '/admin/business/store';
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Admin - <?php echo $isEdit ? 'Editar' : 'Nuevo'; ?> Comercio</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50 font-sans antialiased text-gray-900">

    <div class="max-w-2xl mx-auto my-12 p-8 bg-white rounded-2xl shadow-sm border border-gray-100">

        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight"><?php echo $isEdit ? '📝 Editar Comercio' : '🏬 Registrar Nuevo Comercio'; ?></h1>
                <p class="text-sm text-gray-500">Completa la identidad visual y datos informativos.</p>
            </div>
            <div class="flex items-center gap-3 mb-2 text-orange-500 ">
                <a href="<?= BASE_URL ?>/admin/businesses" class="text-sm text-gray-500 hover:text-primary font-medium flex items-center gap-1 mb-2">
                    <i class="fa-solid fa-arrow-left"></i> Volver al listado
                </a>
            </div>
        </div>

        <?php if ($msg = \App\Core\Session::getFlash('error')): ?>
            <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 rounded-r-xl text-sm text-red-700">
                <?php if (is_array($msg)): ?>
                    <ul class="list-disc pl-5 space-y-1">
                        <?php foreach ($msg as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <?= htmlspecialchars($msg) ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <form action="<?php echo $actionUrl; ?>" method="POST" enctype="multipart/form-data" class="space-y-6">

            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">

            <div class="space-y-4">
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider">Identidad Visual</label>

                <div class="relative">
                    <div class="w-full h-40 bg-gray-100 rounded-xl overflow-hidden border-2 border-dashed border-gray-300 relative flex flex-col items-center justify-center group hover:border-[#059669] transition-colors">
                        <?php $heroSrc = !empty($business['hero_path'] ?? '') ? BASE_URL . '/' . $business['hero_path'] : ''; ?>
                        <img id="preview-hero" src="<?php echo $heroSrc; ?>" class="absolute inset-0 w-full h-full object-cover <?php echo empty($heroSrc) ? 'hidden' : ''; ?>">

                        <div id="placeholder-hero" class="text-center p-4 z-10 <?php echo !empty($heroSrc) ? 'hidden group-hover:flex flex-col items-center justify-center absolute inset-0 bg-black bg-opacity-40 text-white' : ''; ?>">
                            <span class="text-xl">🖼️</span>
                            <p class="text-xs font-semibold mt-1">Subir Banner</p>
                        </div>
                        <input type="file" id="input-hero" name="hero" accept="image/*" class="absolute inset-0 opacity-0 cursor-pointer z-20">
                    </div>

                    <div class="absolute -bottom-6 left-6 w-20 h-20 bg-white rounded-full p-1 shadow-md border border-gray-200 flex items-center justify-center overflow-hidden group/logo hover:border-[#059669] transition-colors z-30">
                        <?php $logoSrc = !empty($business['logo_path'] ?? '') ? BASE_URL . '/' . $business['logo_path'] : ''; ?>
                        <img id="preview-logo" src="<?php echo $logoSrc; ?>" class="w-full h-full object-cover rounded-full <?php echo empty($logoSrc) ? 'hidden' : ''; ?>">

                        <div id="placeholder-logo" class="text-center p-1 z-10 <?php echo !empty($logoSrc) ? 'hidden group-hover/logo:flex flex-col items-center justify-center absolute inset-0 bg-black bg-opacity-50 text-white rounded-full' : ''; ?>">
                            <span class="text-base">🏪</span>
                        </div>
                        <input type="file" id="input-logo" name="logo" accept="image/*" class="absolute inset-0 opacity-0 cursor-pointer z-40">
                    </div>
                </div>
                <div class="h-4"></div>
            </div>

            <div class="grid grid-cols-1 gap-4">

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Nombre del Comercio<span class="text-red-500 font-black ml-0.5">*</span></label>
                    <input type="text" id="nombre" name="nombre" required value="<?php echo htmlspecialchars($business['nombre']); ?>" class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#059669] outline-none text-sm transition">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Categoría<span class="text-red-500 font-black ml-0.5">*</span></label>
                    <select name="categoria_id" required
                        class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-xl bg-gray-50 text-gray-900 focus:outline-none focus:ring-primary focus:border-primary transition-colors">
                        <option value="">Selecciona una categoría...</option>
                        <?php if (isset($category)): foreach ($category as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= (($business['categoria_id'] ?? '') == $cat['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['nombre']) ?>
                                </option>
                        <?php endforeach;
                        endif; ?>
                    </select>
                </div>

                <div class="relative">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Asignar Propietario (Buscar por Nombre o Email)<span class="text-red-500 font-black ml-0.5">*</span></label>
                    <div class="relative">

                        <input type="text" id="user-search-input" autocomplete="off" placeholder="Escribe para buscar usuario..." value="<?php echo htmlspecialchars($business['owner_name'] ?? ''); ?>" class="w-full px-4 py-2 pr-10 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#059669] outline-none text-sm transition">

                        <span id="search-icon" class="absolute right-3 top-2.5 text-gray-400 text-sm">🔍</span>
                    </div>

                    <input type="hidden" id="user_id_hidden" name="user_id" value="<?php echo htmlspecialchars($business['user_id']); ?>" required>

                    <div id="autocomplete-results" class="absolute z-50 w-full bg-white border border-gray-200 rounded-xl mt-1 shadow-lg max-h-48 overflow-y-auto hidden">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Teléfono<span class="text-red-500 font-black ml-0.5">*</span></label>
                        <input type="text" name="telefono" required value="<?php echo htmlspecialchars($business['telefono']); ?>" class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#059669] outline-none text-sm transition">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Email</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($business['email']); ?>" class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#059669] outline-none text-sm transition">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Sitio Web (URL)</label>
                    <input
                        type="url"
                        name="web"
                        id="web"
                        value="<?php echo htmlspecialchars($business['web'] ?? ''); ?>"
                        placeholder="https://..."
                        class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#059669] outline-none text-sm transition">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Descripción Comercial<span class="text-red-500 font-black ml-0.5">*</span></label>
                    <textarea id="descripcion" name="descripcion" rows="3" class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#059669] outline-none text-sm transition"><?php echo htmlspecialchars($business['descripcion'] ?? ''); ?></textarea>
                </div>
            </div>

            <div class="space-y-4">
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mt-6">Datos de Ubicación</label>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Calle<span class="text-red-500 font-black ml-0.5">*</span></label>
                        <input type="text" name="calle" required value="<?php echo htmlspecialchars($business['calle'] ?? ''); ?>" class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#059669] outline-none text-sm transition">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Número</label>
                        <input type="text" name="numero" value="<?php echo htmlspecialchars($business['numero'] ?? ''); ?>" class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#059669] outline-none text-sm transition">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Código Postal<span class="text-red-500 font-black ml-0.5">*</span></label>
                        <input type="text" name="codigo_postal" required value="<?php echo htmlspecialchars($business['codigo_postal'] ?? ''); ?>" class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#059669] outline-none text-sm transition">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Ciudad<span class="text-red-500 font-black ml-0.5">*</span></label>
                        <input type="text" name="ciudad" required value="<?php echo htmlspecialchars($business['ciudad'] ?? ''); ?>" class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#059669] outline-none text-sm transition">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Provincia<span class="text-red-500 font-black ml-0.5">*</span></label>
                        <input type="text" name="provincia" required value="<?php echo htmlspecialchars($business['provincia'] ?? ''); ?>" class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#059669] outline-none text-sm transition">
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl border border-gray-100">
                <div>
                    <h4 class="text-sm font-bold text-gray-800">Comercio Activo en el Marketplace</h4>
                    <p class="text-xs text-gray-400">Determina si los clientes pueden ver el comercio de inmediato.</p>
                </div>
                <label class="inline-flex items-center cursor-pointer relative">
                    <input type="checkbox" name="activo" value="1" <?php echo ($business['activo'] == 1) ? 'checked' : ''; ?> class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#059669]"></div>
                </label>
            </div>

            <div class="flex gap-4 pt-2">
                <button type="submit" class="flex-1 py-3 bg-[#059669] hover:bg-[#047857] text-white font-semibold rounded-xl text-sm shadow-sm transition">
                    <?php echo $isEdit ? 'Guardar Cambios' : 'Crear y Publicar'; ?>
                </button>
                <button type="button" id="btn-previsualizar" class="px-5 py-3 bg-gray-800 hover:bg-gray-900 text-white font-semibold rounded-xl text-sm shadow-sm transition flex items-center gap-2">
                    👁️ Vista Previa
                </button>
            </div>
        </form>
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

</body>

</html>