<?php
/**
 * @var array|null $business Información del comercio (null si es creación)
 */
require_once ROOT_DIR . '/resources/views/main_header.php';
$isEdit = isset($business) && $business;
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-secondary">
            <?= $isEdit ? '✏️ Editar Comercio: ' . htmlspecialchars($business['nombre']) : '➕ Nuevo Comercio' ?>
        </h1>
        <p class="text-gray-600 mt-2">
            Completa la información del comercio. Puedes incluir logo y banner promocional.
        </p>
    </div>

    <form action="<?= BASE_URL ?>/admin/business/<?= $isEdit ? $business['id'] . '/update' : 'store' ?>" method="POST" enctype="multipart/form-data" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 space-y-6">
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Nombre -->
            <div>
                <label for="nombre" class="block text-sm font-medium text-gray-700 mb-2">Nombre del Comercio *</label>
                <input type="text" id="nombre" name="nombre" required 
                       value="<?= $isEdit ? htmlspecialchars($business['nombre']) : '' ?>"
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all">
            </div>

            <!-- Email -->
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email de Contacto</label>
                <input type="email" id="email" name="email" 
                       value="<?= $isEdit ? htmlspecialchars($business['email'] ?? '') : '' ?>"
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all">
            </div>

            <!-- Teléfono -->
            <div>
                <label for="telefono" class="block text-sm font-medium text-gray-700 mb-2">Teléfono</label>
                <input type="text" id="telefono" name="telefono" 
                       value="<?= $isEdit ? htmlspecialchars($business['telefono'] ?? '') : '' ?>"
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all">
            </div>

            <!-- Sitio Web -->
            <div>
                <label for="web" class="block text-sm font-medium text-gray-700 mb-2">Sitio Web</label>
                <input type="url" id="web" name="web" 
                       value="<?= $isEdit ? htmlspecialchars($business['web'] ?? '') : '' ?>"
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all">
            </div>
        </div>

        <!-- Descripción -->
        <div>
            <label for="descripcion" class="block text-sm font-medium text-gray-700 mb-2">Descripción</label>
            <textarea id="descripcion" name="descripcion" rows="4" 
                      class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all"><?= $isEdit ? htmlspecialchars($business['descripcion'] ?? '') : '' ?></textarea>
        </div>

        <!-- Imágenes -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 border-t border-gray-100">
            <!-- Logo -->
            <div>
                <label for="logo" class="block text-sm font-medium text-gray-700 mb-2">Logo del Comercio</label>
                <input type="file" id="logo" name="logo" accept="image/*"
                       class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 transition-all">
                <?php if ($isEdit && !empty($business['logo_path'])): ?>
                    <p class="text-xs text-gray-500 mt-2">Logo actual: <?= htmlspecialchars(basename($business['logo_path'])) ?></p>
                <?php endif; ?>
            </div>

            <!-- Hero (Banner) -->
            <div>
                <label for="hero" class="block text-sm font-medium text-gray-700 mb-2">Banner (Hero)</label>
                <input type="file" id="hero" name="hero" accept="image/*"
                       class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 transition-all">
                <?php if ($isEdit && !empty($business['hero_path'])): ?>
                    <p class="text-xs text-gray-500 mt-2">Banner actual: <?= htmlspecialchars(basename($business['hero_path'])) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Activo -->
        <div class="pt-4 border-t border-gray-100">
            <label class="flex items-center gap-3">
                <input type="checkbox" name="activo" value="1" 
                       <?= (!$isEdit || $business['activo'] == 1) ? 'checked' : '' ?>
                       class="w-5 h-5 text-primary border-gray-300 rounded focus:ring-primary">
                <span class="text-sm font-medium text-gray-700">Comercio Activo</span>
            </label>
        </div>

        <!-- Botones -->
        <div class="flex items-center justify-end gap-4 pt-6 mt-6 border-t border-gray-100">
            <a href="<?= BASE_URL ?>/admin/businesses" class="px-6 py-3 text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl font-medium transition-colors">
                Cancelar
            </a>
            <button type="submit" class="px-8 py-3 bg-primary hover:bg-orange-600 text-white rounded-xl font-bold shadow-sm transition-colors">
                <?= $isEdit ? 'Actualizar Comercio' : 'Crear Comercio' ?>
            </button>
        </div>
    </form>
</div>

<?php require_once ROOT_DIR . '/resources/views/layout/footer.php'; ?>
