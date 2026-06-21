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
            <a href="<?php echo BASE_URL; ?>/admin/businesses" class="text-sm font-semibold text-gray-600 hover:text-gray-900 transition">
                &larr; Volver
            </a>
        </div>

        <?php if ($msg = \App\Core\Session::getFlash('error')): ?>
            <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 rounded-r-xl text-sm text-red-700"><?php echo $msg; ?></div>
        <?php endif; ?>

        <form action="<?php echo $actionUrl; ?>" method="POST" enctype="multipart/form-data" class="space-y-6">

            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">

            <div class="space-y-4">
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider">Identidad Visual</label>

                <div class="relative">
                    <div class="w-full h-40 bg-gray-100 rounded-xl overflow-hidden border-2 border-dashed border-gray-300 relative flex flex-col items-center justify-center group hover:border-[#059669] transition-colors">
                        <?php $heroSrc = !empty($business['hero_path'] ?? '') ? BASE_URL . '/public/uploads/businesses/' . $business['hero_path'] : ''; ?>
                        <img id="preview-hero" src="<?php echo $heroSrc; ?>" class="absolute inset-0 w-full h-full object-cover <?php echo empty($heroSrc) ? 'hidden' : ''; ?>">

                        <div id="placeholder-hero" class="text-center p-4 z-10 <?php echo !empty($heroSrc) ? 'hidden group-hover:flex flex-col items-center justify-center absolute inset-0 bg-black bg-opacity-40 text-white' : ''; ?>">
                            <span class="text-xl">🖼️</span>
                            <p class="text-xs font-semibold mt-1">Subir Banner</p>
                        </div>
                        <input type="file" id="input-hero" name="hero" accept="image/*" class="absolute inset-0 opacity-0 cursor-pointer z-20">
                    </div>

                    <div class="absolute -bottom-6 left-6 w-20 h-20 bg-white rounded-full p-1 shadow-md border border-gray-200 flex items-center justify-center overflow-hidden group/logo hover:border-[#059669] transition-colors z-30">
                        <?php $logoSrc = !empty($business['logo_path'] ?? '') ? BASE_URL . '/public/uploads/businesses/' . $business['logo_path'] : ''; ?>
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
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Nombre del Comercio</label>
                    <input type="text" id="nombre" name="nombre" required value="<?php echo htmlspecialchars($business['nombre']); ?>" class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#059669] outline-none text-sm transition">
                </div>

                <div class="relative">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Asignar Propietario (Buscar por Nombre o Email)</label>
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
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Teléfono corporativo</label>
                        <input type="text" name="telefono" value="<?php echo htmlspecialchars($business['telefono']); ?>" class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#059669] outline-none text-sm transition">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Email de contacto</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($business['email']); ?>" class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#059669] outline-none text-sm transition">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Sitio Web (URL)</label>
                    <input type="url" name="web" value="<?php echo htmlspecialchars($business['web'] ?? ''); ?>" placeholder="https://..." class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#059669] outline-none text-sm transition">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Descripción Comercial</label>
                    <textarea id="descripcion" name="descripcion" rows="3" class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#059669] outline-none text-sm transition"><?php echo htmlspecialchars($business['descripcion'] ?? ''); ?></textarea>
                </div>
            </div>

            <div class="space-y-4">
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mt-6">Datos de Ubicación</label>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Calle</label>
                        <input type="text" name="calle" required value="<?php echo htmlspecialchars($business['calle'] ?? ''); ?>" class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#059669] outline-none text-sm transition">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Número</label>
                        <input type="text" name="numero" value="<?php echo htmlspecialchars($business['numero'] ?? ''); ?>" class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#059669] outline-none text-sm transition">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Código Postal</label>
                        <input type="text" name="codigo_postal" required value="<?php echo htmlspecialchars($business['codigo_postal'] ?? ''); ?>" class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#059669] outline-none text-sm transition">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Ciudad</label>
                        <input type="text" name="ciudad" required value="<?php echo htmlspecialchars($business['ciudad'] ?? ''); ?>" class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#059669] outline-none text-sm transition">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Provincia</label>
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
        <div class="bg-white rounded-2xl shadow-2xl max-w-sm w-full overflow-hidden border border-gray-100 transform scale-95 transition-transform duration-300">
            <div class="px-5 py-3 bg-gray-50 border-b flex justify-between items-center">
                <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider">Tarjeta Pública</h3>
                <button type="button" id="btn-cerrar-previa" class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
            </div>

            <div class="p-6 bg-gray-50 flex justify-center">
                <div class="bg-white rounded-2xl shadow-md overflow-hidden w-full border border-gray-200 relative">
                    <div class="h-28 bg-gray-300 relative">
                        <img id="modal-prev-hero" src="" class="w-full h-full object-cover">
                    </div>
                    <div class="absolute top-16 left-4 w-14 h-14 bg-white rounded-full p-0.5 shadow border overflow-hidden">
                        <img id="modal-prev-logo" src="" class="w-full h-full object-cover rounded-full">
                    </div>
                    <div class="p-4 pt-6">
                        <h4 id="modal-prev-nombre" class="text-base font-bold text-gray-900 truncate">Nombre</h4>
                        <p id="modal-prev-desc" class="text-xs text-gray-500 line-clamp-2 mt-1">Texto...</p>
                        <div class="mt-3 pt-2 border-t flex justify-between items-center text-xs text-[#059669] font-semibold">
                            <span>Visitar tienda &rarr;</span>
                            <span class="bg-[#ecfdf5] text-[#047857] px-2 py-0.5 rounded-full text-[10px]">Nuevo</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // --- 1. Lógica de Previsualización de Imágenes locales ---
            const inputHero = document.getElementById('input-hero');
            const inputLogo = document.getElementById('input-logo');
            const prevHero = document.getElementById('preview-hero');
            const prevLogo = document.getElementById('preview-logo');
            const placeholderHero = document.getElementById('placeholder-hero');
            const placeholderLogo = document.getElementById('placeholder-logo');

            function gestionarArchivo(input, imgElement, placeholderElement, esHero) {
                if (input && imgElement) {
                    input.addEventListener('change', function() {
                        if (this.files && this.files[0]) {
                            const reader = new FileReader();
                            reader.onload = function(e) {
                                imgElement.src = e.target.result;
                                imgElement.classList.remove('hidden');
                                if (esHero) {
                                    placeholderElement.className = "text-center p-4 absolute inset-0 bg-black bg-opacity-40 text-white flex flex-col items-center justify-center opacity-0 hover:opacity-100 transition-opacity rounded-xl z-10";
                                } else {
                                    placeholderElement.className = "text-center p-1 absolute inset-0 bg-black bg-opacity-50 text-white flex flex-col items-center justify-center opacity-0 hover:opacity-100 transition-opacity rounded-full z-10";
                                }
                            }
                            reader.readAsDataURL(this.files[0]);
                        }
                    });
                }
            }
            gestionarArchivo(inputHero, prevHero, placeholderHero, true);
            gestionarArchivo(inputLogo, prevLogo, placeholderLogo, false);

            // --- 2. Lógica del Modal ---
            const btnPrevisualizar = document.getElementById('btn-previsualizar');
            const modal = document.getElementById('modal-vista-previa');
            const btnCerrar = document.getElementById('btn-cerrar-previa');

            if (btnPrevisualizar && modal) {
                btnPrevisualizar.addEventListener('click', function() {
                    document.getElementById('modal-prev-nombre').textContent = document.getElementById('nombre').value || 'Comercio Nuevo';
                    document.getElementById('modal-prev-desc').textContent = document.getElementById('descripcion').value || 'Sin descripción comercial añadida todavía.';
                    document.getElementById('modal-prev-hero').src = prevHero.src || 'https://images.unsplash.com/photo-1542838132-92c53300491e?w=500';
                    document.getElementById('modal-prev-logo').src = prevLogo.src || 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=150';
                    modal.classList.remove('hidden');
                });
                btnCerrar.addEventListener('click', () => modal.classList.add('hidden'));
            }

            // --- 3. LÓGICA DEL AUTOCOMPLETADO ASÍNCRONO (MÁXIMA EFICIENCIA) ---
            const searchInput = document.getElementById('user-search-input');
            const hiddenInput = document.getElementById('user_id_hidden');
            const resultsBox = document.getElementById('autocomplete-results');
            const searchIcon = document.getElementById('search-icon');
            let debounceTimer;

            searchInput.addEventListener('input', function() {
                const query = this.value.trim();

                // Si el usuario borra lo escrito, limpiamos el ID oculto obligado
                if (query.length === 0) {
                    hiddenInput.value = '';
                    resultsBox.innerHTML = '';
                    resultsBox.classList.add('hidden');
                    return;
                }

                // No busques hasta tener al menos 2 caracteres (ahorra peticiones al servidor)
                if (query.length < 2) return;

                // Técnica Debounce: Espera a que el usuario deje de teclear durante 300ms antes de disparar AJAX
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    searchIcon.textContent = '⏳'; // Feedback visual de carga

                    // Petición fetch asíncrona a tu API REST de PHP
                    fetch(`<?php echo BASE_URL; ?>/api/users/search?q=${encodeURIComponent(query)}`)
                        .then(response => response.json())
                        .then(users => {
                            searchIcon.textContent = '🔍';
                            resultsBox.innerHTML = '';

                            if (users.length === 0) {
                                resultsBox.innerHTML = '<div class="p-3 text-xs text-gray-500">No se encontraron usuarios</div>';
                                resultsBox.classList.remove('hidden');
                                return;
                            }

                            // Inyectamos dinámicamente los resultados devueltos por la BD
                            users.forEach(user => {
                                const div = document.createElement('div');
                                div.className = "px-4 py-2 hover:bg-gray-100 text-sm cursor-pointer border-b border-gray-50 last:border-none transition-colors";
                                div.innerHTML = `<span class="font-semibold text-gray-800">${user.nombre}</span> <span class="text-xs text-gray-400">(${user.email})</span>`;

                                // Evento al hacer click en una sugerencia
                                div.addEventListener('click', function() {
                                    searchInput.value = `${user.nombre} (${user.email})`;
                                    hiddenInput.value = user.id; // ¡Guardamos el ID en el input oculto!
                                    resultsBox.classList.add('hidden');
                                });
                                resultsBox.appendChild(div);
                            });

                            resultsBox.classList.remove('hidden');
                        })
                        .catch(err => {
                            console.error('Error en autocomplete:', err);
                            searchIcon.textContent = '🔍';
                        });
                }, 300);
            });

            // Cerrar la caja de resultados si se hace click fuera del componente
            document.addEventListener('click', function(e) {
                if (e.target !== searchInput && e.target !== resultsBox) {
                    resultsBox.classList.add('hidden');
                }
            });
        });
    </script>
</body>

</html>