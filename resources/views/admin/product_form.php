<?php
require_once ROOT_DIR . '/resources/views/main_header.php';

$isEdit = isset($product) && !empty($product['id']);
$actionUrl = $isEdit
    ? BASE_URL . '/admin/product/' . $product['id'] . '/update'
    : BASE_URL . '/admin/business/' . $businessId . '/product/store';
$backUrl = $isEdit
    ? BASE_URL . '/admin/business/' . $product['business_id']
    : BASE_URL . '/admin/business/' . $businessId;
?>

<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="mb-8">
        <a href="<?= $backUrl ?>" class="text-gray-500 hover:text-primary transition-colors flex items-center gap-2">
            <i class="fa-solid fa-arrow-left"></i> Volver al comercio
        </a>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-8 py-6 bg-gray-50 border-b border-gray-100 flex items-center gap-4">
            <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-xl flex items-center justify-center text-xl">
                <i class="fa-solid fa-box"></i>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-secondary"><?= $isEdit ? 'Editar Producto' : 'Nuevo Producto' ?></h1>
                <p class="text-sm text-gray-500">Gestión de producto desde administración.</p>
            </div>
        </div>

        <form action="<?= $actionUrl ?>" method="POST" enctype="multipart/form-data" class="p-8 space-y-6">
            <?= \App\Core\Session::csrfField() ?>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Nombre <span class="text-red-500">*</span></label>
                <input name="nombre" required value="<?= htmlspecialchars($product['nombre'] ?? '') ?>"
                    class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-xl bg-gray-50 text-gray-900 focus:outline-none focus:ring-primary focus:border-transparent">
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Descripción</label>
                <textarea name="descripcion" rows="4"
                    class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-xl bg-gray-50 text-gray-900 focus:outline-none focus:ring-primary focus:border-transparent"><?= htmlspecialchars($product['descripcion'] ?? '') ?></textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Precio (€) <span class="text-red-500">*</span></label>
                    <input name="precio" type="number" step="0.01" min="0" required value="<?= htmlspecialchars($product['precio'] ?? '0.00') ?>"
                        class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-xl bg-gray-50 text-gray-900 focus:outline-none focus:ring-primary focus:border-transparent">
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Stock <span class="text-red-500">*</span></label>
                    <input name="stock" type="number" min="0" required value="<?= htmlspecialchars($product['stock'] ?? '0') ?>"
                        class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-xl bg-gray-50 text-gray-900 focus:outline-none focus:ring-primary focus:border-transparent">
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Categoría</label>
                    <select name="category_id" class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-xl bg-gray-50 text-gray-900 focus:outline-none focus:ring-primary focus:border-transparent">
                        <option value="">Sin categoría</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= $category['id'] ?>" <?= (($product['category_id'] ?? '') == $category['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($category['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Imagen</label>
                <input type="file" name="imagen" accept="image/*"
                    class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none focus:border-primary">
            </div>

            <label class="inline-flex items-center gap-3">
                <input type="checkbox" name="activo" value="1" <?= (($product['activo'] ?? 1) ? 'checked' : '') ?>
                    class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary">
                <span class="text-sm font-bold text-gray-700">Producto visible</span>
            </label>

            <div class="flex gap-4 pt-6 border-t border-gray-100">
                <button type="submit" class="flex-1 bg-primary hover:bg-green-700 text-white font-bold py-3 px-6 rounded-xl transition-all flex items-center justify-center gap-2">
                    <i class="fa-solid fa-check"></i> <?= $isEdit ? 'Guardar Cambios' : 'Crear Producto' ?>
                </button>
                <a href="<?= $backUrl ?>" class="flex-1 text-center py-3 px-6 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl transition-all">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

<?php require_once ROOT_DIR . '/resources/views/layout/footer.php'; ?>
