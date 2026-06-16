<?php

/**
 * @var array|null $business Datos del comercio (si estamos editando)
 * @var array $categories Lista de categorías para el select
 * @var array $users Lista de usuarios registrados en el sistema para el propietario
 */
require_once ROOT_DIR . '/resources/views/main_header.php';

// Detectar si estamos en modo Edición o Creación
$isEdit = isset($business) && !empty($business);
$actionUrl = $isEdit
    ? BASE_URL . "/admin/business/{$business['id']}/update"
    : BASE_URL . "/admin/business/store";

$pageTitle = $isEdit ? 'Editar Comercio' : 'Nuevo Comercio';
$icon = $isEdit ? 'fa-pen-to-square' : 'fa-store';
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

    <div class="flex items-center justify-between mb-8">
        <div>
            <a href="<?= BASE_URL ?>/admin/businesses" class="text-sm text-gray-500 hover:text-primary font-medium flex items-center gap-1 mb-2 transition-colors">
                <i class="fa-solid fa-arrow-left text-xs"></i> Volver a comercios
            </a>
            <h1 class="text-3xl font-bold text-secondary flex items-center gap-3">
                <i class="fa-solid <?= $icon ?> text-primary"></i> <?= $pageTitle ?>
            </h1>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sm:p-8 mb-8">
        <h2 class="font-bold text-gray-900 mb-6 pb-3 border-b border-gray-100">
            <i class="fa-solid fa-circle-info text-primary mr-2"></i> Información del Establecimiento
        </h2>

        <form action="<?= $actionUrl ?>" method="POST" enctype="multipart/form-data" class="space-y-6">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">

                <div class="sm:col-span-2">
                    <label class="block text-sm font-bold text-gray-700 mb-2">Nombre del Comercio *</label>
                    <input type="text" name="nombre" required
                        value="<?= htmlspecialchars($business['nombre'] ?? '') ?>"
                        class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-xl bg-gray-50 text-gray-900 focus:outline-none focus:ring-primary focus:border-primary transition-colors"
                        placeholder="Ej: Frutería Paco" />
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Categoría *</label>
                    <select name="categoria_id" required
                        class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-xl bg-gray-50 text-gray-900 focus:outline-none focus:ring-primary focus:border-primary transition-colors">
                        <option value="">Selecciona una categoría...</option>
                        <?php if (isset($categories)): foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= (($business['categoria_id'] ?? '') == $cat['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['nombre']) ?>
                                </option>
                        <?php endforeach;
                        endif; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Usuario Propietario *</label>
                    <select name="user_id" required
                        class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-xl bg-gray-50 text-gray-900 focus:outline-none focus:ring-primary focus:border-primary transition-colors">
                        <option value="">Selecciona un propietario...</option>
                        <?php if (isset($users)): foreach ($users as $user): ?>
                                <option value="<?= $user['id'] ?>" <?= (($business['user_id'] ?? '') == $user['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($user['nombre']) ?> (<?= htmlspecialchars($user['email']) ?>)
                                </option>
                        <?php endforeach;
                        endif; ?>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Usuario de la plataforma que gestionará esta ficha.</p>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Email de Contacto</label>
                    <input type="email" name="email"
                        value="<?= htmlspecialchars($business['email'] ?? '') ?>"
                        class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-xl bg-gray-50 text-gray-900 focus:outline-none focus:ring-primary focus:border-primary transition-colors"
                        placeholder="contacto@comercio.com" />
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Teléfono</label>
                    <input type="text" name="telefono"
                        value="<?= htmlspecialchars($business['telefono'] ?? '') ?>"
                        class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-xl bg-gray-50 text-gray-900 focus:outline-none focus:ring-primary focus:border-primary transition-colors"
                        placeholder="600 000 000" />
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Tipo de Negocio *</label>
                    <select name="business_type" required
                        class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-xl bg-gray-50 text-gray-900 focus:outline-none focus:ring-primary focus:border-primary transition-colors">
                        <option value="PRODUCTS" <?= (($business['business_type'] ?? 'PRODUCTS') === 'PRODUCTS') ? 'selected' : '' ?>>Tienda física / Venta de Productos</option>
                        <option value="SERVICES" <?= (($business['business_type'] ?? 'PRODUCTS') === 'SERVICES') ? 'selected' : '' ?>>Proveedor de Servicios</option>
                        <option value="HYBRID" <?= (($business['business_type'] ?? 'PRODUCTS') === 'HYBRID') ? 'selected' : '' ?>>Modelo Mixto (Productos y Servicios)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Estado de Moderación *</label>
                    <select name="status" required
                        class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-xl bg-gray-50 text-gray-900 focus:outline-none focus:ring-primary focus:border-primary transition-colors">
                        <option value="PENDING" <?= (($business['status'] ?? 'ACTIVE') === 'PENDING') ? 'selected' : '' ?>>Pendiente de Revisión</option>
                        <option value="ACTIVE" <?= (($business['status'] ?? 'ACTIVE') === 'ACTIVE') ? 'selected' : '' ?>>Activo / Aprobado</option>
                        <option value="SUSPENDED" <?= (($business['status'] ?? 'ACTIVE') === 'SUSPENDED') ? 'selected' : '' ?>>Suspendido / Bloqueado</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Visibilidad en Catálogo</label>
                    <select name="activo"
                        class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-xl bg-gray-50 text-gray-900 focus:outline-none focus:ring-primary focus:border-primary transition-colors">
                        <option value="1" <?= (($business['activo'] ?? 1) == 1) ? 'selected' : '' ?>>Visible al público</option>
                        <option value="0" <?= (($business['activo'] ?? 1) == 0) ? 'selected' : '' ?>>Oculto temporalmente</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Sitio Web / Redes Sociales</label>
                    <input type="url" name="web"
                        value="<?= htmlspecialchars($business['web'] ?? '') ?>"
                        class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-xl bg-gray-50 text-gray-900 focus:outline-none focus:ring-primary focus:border-primary transition-colors"
                        placeholder="https://www.tucomercio.com" />
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Logo del Comercio</label>

                    <label for="logo-input" class="flex items-center bg-gray-50 rounded-xl border border-gray-300 overflow-hidden focus-within:ring-2 focus-within:ring-primary focus-within:border-primary transition-colors cursor-pointer w-full">

                        <input type="file" id="logo-input" name="logo" accept="image/jpeg,image/png,image/webp"
                            class="hidden" onchange="updateFileName(this, 'logo-name')" />

                        <div class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-3 font-semibold text-sm flex items-center gap-2 shrink-0 transition-colors">
                            <i class="fa-solid fa-image"></i> Seleccionar Logo
                        </div>

                        <span id="logo-name" class="text-sm text-gray-500 px-4 truncate">
                            Ningún archivo seleccionado
                        </span>
                    </label>

                    <?php if ($isEdit && !empty($business['logo_path'])): ?>
                        <p class="text-xs text-gray-400 mt-1.5">Archivo actual: <span class="font-mono"><?= basename($business['logo_path']) ?></span></p>
                    <?php endif; ?>
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-sm font-bold text-gray-700 mb-2">Imagen de Portada (Hero Banner)</label>

                    <label for="hero-input" class="block relative group border-2 border-dashed border-gray-300 hover:border-purple-500 rounded-2xl bg-gray-50 p-6 text-center transition-colors cursor-pointer min-h-[140px] flex flex-col items-center justify-center">

                        <input type="file" id="hero-input" name="hero" accept="image/jpeg,image/png,image/webp"
                            class="hidden" onchange="updateFileName(this, 'hero-name', true)" />

                        <div class="flex flex-col items-center justify-center space-y-2 pointer-events-none">
                            <div class="w-12 h-12 bg-purple-100 text-purple-600 rounded-xl flex items-center justify-center text-xl group-hover:scale-110 transition-transform">
                                <i class="fa-solid fa-cloud-arrow-up"></i>
                            </div>
                            <p class="text-sm font-bold text-gray-700">Haz clic para examinar o arrastra la portada aquí</p>
                            <p class="text-xs text-gray-400">Formatos aceptados: WEBP, PNG o JPG (Recomendado: diseño apaisado)</p>

                            <span id="hero-name" class="inline-block text-xs bg-purple-50 text-purple-700 px-2.5 py-1 rounded-md font-medium border border-purple-100 mt-2 hidden">
                                Ningún archivo seleccionado
                            </span>
                        </div>
                    </label>

                    <?php if ($isEdit && !empty($business['hero_path'])): ?>
                        <p class="text-xs text-gray-400 mt-1.5">Archivo actual: <span class="font-mono"><?= basename($business['hero_path']) ?></span></p>
                    <?php endif; ?>
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-sm font-bold text-gray-700 mb-2">Descripción del Comercio</label>
                    <textarea name="descripcion" rows="6" style="resize: none;"
                        class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-xl bg-gray-50 text-gray-900 focus:outline-none focus:ring-primary focus:border-primary transition-colors"
                        placeholder="Breve descripción de lo que ofrece el negocio local..."><?= htmlspecialchars($business['descripcion'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="mt-8 pt-6 border-t border-gray-100 flex flex-col sm:flex-row gap-4 justify-end">
                <a href="<?= BASE_URL ?>/admin/businesses"
                    class="px-6 py-3 border border-gray-300 text-gray-700 rounded-xl font-bold text-center hover:bg-gray-50 transition-colors">
                    Cancelar
                </a>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-xl font-bold flex items-center justify-center gap-2 transition-colors shadow-sm">
                    <i class="fa-solid fa-save"></i> <?= $isEdit ? 'Guardar Cambios' : 'Crear Comercio' ?>
                </button>
            </div>

        </form>
    </div>
</div>

<script>
    function updateFileName(input, elementId, isDropzone = false) {
        const label = document.getElementById(elementId);

        if (input.files && input.files.length > 0) {
            label.textContent = "📁 " + input.files[0].name;
            if (isDropzone) {
                label.classList.remove('hidden');
            }
        } else {
            label.textContent = "Ningún archivo seleccionado";
            if (isDropzone) {
                label.classList.add('hidden');
            }
        }
    }
</script>

<?php require_once ROOT_DIR . '/resources/views/layout/footer.php'; ?>