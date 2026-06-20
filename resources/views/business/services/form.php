<?php

/** @var array $cats Categorías filtradas para el formulario */
/** @var App\Models\Product|null $product Objeto producto si estamos editando */
?>

<?php require_once ROOT_DIR . '/resources/views/main_header.php'; ?>
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="mb-8 flex items-center gap-4">
        <a href="<?= BASE_URL ?>/business/dashboard/services" class="text-gray-500 hover:text-primary transition-colors flex items-center gap-2">
            <i class="fa-solid fa-arrow-left"></i> Volver a la lista
        </a>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-8 py-6 bg-gray-50 border-b border-gray-100 flex items-center gap-4">
            <div class="w-12 h-12 bg-purple-100 text-purple-600 rounded-xl flex items-center justify-center text-xl">
                <i class="fa-solid fa-handshake-angle"></i>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-secondary"><?= isset($service) ? 'Editar Servicio' : 'Nuevo Servicio' ?></h1>
                <p class="text-sm text-gray-500">Gestiona los detalles de tu servicio para tus clientes.</p>
            </div>
        </div>

        <form action="<?= isset($service) ? BASE_URL . '/business/dashboard/services/' . $service->id . '/update' : BASE_URL . '/business/dashboard/services/store' ?>"
            method="POST"
            enctype="multipart/form-data"
            class="p-8 space-y-6">

            <?= \App\Core\Session::csrfField() ?>
            <div class="grid grid-cols-1 gap-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Nombre del Servicio <span class="text-red-500">*</span></label>
                    <input name="nombre" value="<?= htmlspecialchars($service->nombre ?? '') ?>" required
                        class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-xl bg-gray-50 text-gray-900 focus:outline-none focus:ring-primary focus:border-transparent transition-all"
                        placeholder="Ej. Corte de pelo caballero">
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Descripción <span class="text-red-500">*</span></label>
                    <textarea name="descripcion" required rows="4"
                        class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-xl bg-gray-50 text-gray-900 focus:outline-none focus:ring-primary focus:border-transparent transition-all"
                        placeholder="Cuéntales a tus clientes sobre este servicio..."><?= htmlspecialchars($service->descripcion ?? '') ?></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Duración (min) <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                <i class="fa-solid fa-clock"></i>
                            </div>
                            <input name="duracion" type="number" value="<?= htmlspecialchars($service->duracion ?? '30') ?>" required
                                class="pl-10 appearance-none block w-full px-4 py-3 border border-gray-300 rounded-xl bg-gray-50 text-gray-900 focus:outline-none focus:ring-primary focus:border-transparent transition-all">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Precio (€) <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                <i class="fa-solid fa-euro-sign"></i>
                            </div>
                            <input name="precio" type="number" step="0.01" value="<?= htmlspecialchars($service->precio ?? '0.00') ?>" required
                                class="pl-10 appearance-none block w-full px-4 py-3 border border-gray-300 rounded-xl bg-gray-50 text-gray-900 focus:outline-none focus:ring-primary focus:border-transparent transition-all">
                        </div>
                    </div>

                    <div class="flex items-end pb-3">
                        <label class="inline-flex items-center gap-3 cursor-pointer select-none">
                            <input type="checkbox" name="activo" value="1"
                                class="w-5 h-5 rounded border-gray-300 text-primary focus:ring-primary focus:ring-offset-0 transition-all cursor-pointer"
                                <?= (isset($service) && !$service->activo) ? '' : 'checked' ?>>
                            <span class="text-sm font-bold text-gray-700">Servicio activo</span>
                        </label>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Categoría</label>
                    <select name="category_id" class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-xl bg-gray-50 text-gray-900 focus:outline-none focus:ring-primary focus:border-transparent transition-all">
                        <option value="">-- Seleccionar Categoría --</option>
                        <?php foreach ($cats as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= (isset($service) && $service->category_id == $c['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Imagen del Servicio</label>

                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-xl bg-gray-50 hover:bg-gray-100/50 transition-all relative group overflow-hidden">

                        <img id="servicio-preview"
                            src="<?= !empty($service->imagen) ? BASE_URL . '/img/services/' . $service->imagen : '' ?>"
                            alt="Vista previa"
                            class="<?= !empty($service->imagen) ? '' : 'hidden' ?> absolute inset-0 w-full h-full object-cover z-10">

                        <div id="dropzone-content" class="<?= !empty($service->imagen) ? 'opacity-0' : 'flex' ?> space-y-2 text-center flex-col items-center justify-center transition-opacity duration-200 z-20 pointer-events-none">
                            <div class="mx-auto h-12 w-12 text-gray-400 flex items-center justify-center text-3xl mb-1 group-hover:scale-105 transition-transform">
                                <i class="fa-solid fa-cloud-arrow-up text-primary"></i>
                            </div>
                            <div class="flex gap-1 text-sm text-gray-600 justify-center font-bold">
                                <span class="text-primary hover:text-green-700 transition-colors">Selecciona un archivo</span>
                                <p class="text-gray-500 font-normal">o arrastra aquí</p>
                            </div>
                            <p class="text-xs text-gray-400">Formatos: JPG, PNG, GIF o WebP (Máx. 5MB)</p>
                        </div>

                        <input id="imagen" name="imagen" type="file" accept="image/*"
                            class="absolute inset-0 w-full h-full opacity-0 text-transparent cursor-pointer z-30"
                            style="opacity: 0 !important; font-size: 0 !important;"
                            onchange="mostrarNombreArchivo(this)">
                    </div>

                    <div class="flex flex-col gap-1 mt-2">
                        <p id="nombre-archivo-elegido" class="text-xs font-semibold text-emerald-600 bg-emerald-50 rounded-lg py-1 px-3 inline-block self-start hidden"></p>

                        <?php if (!empty($service->imagen)): ?>
                            <p id="aviso-imagen-existente" class="text-xs text-amber-600 font-semibold">Ya tiene una imagen asignada. Sube otra si deseas cambiarla.</p>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <div class="flex gap-4 pt-6 border-t border-gray-100">
                <button type="submit" class="flex-1 bg-primary hover:bg-green-700 text-white font-bold py-3 px-6 rounded-xl transition-all shadow-sm flex items-center justify-center gap-2">
                    <i class="fa-solid fa-check"></i> <?= isset($service) ? 'Guardar Cambios' : 'Crear Servicio' ?>
                </button>
                <a href="<?= BASE_URL ?>/business/dashboard/services" class="flex-1 text-center py-3 px-6 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl transition-all">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>
<?php require_once ROOT_DIR . '/resources/views/layout/footer.php'; ?>