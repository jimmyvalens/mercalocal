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

        <form action="<?= isset($service) ? BASE_URL . '/business/dashboard/services/' . $service->id . '/update' : BASE_URL . '/business/dashboard/services/store' ?>" method="POST" class="p-8 space-y-6">
            <?= \App\Core\Session::csrfField() ?>
            <div class="grid grid-cols-1 gap-6">
                <!-- Nombre -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Nombre del Servicio <span class="text-red-500">*</span></label>
                    <input name="nombre" value="<?= htmlspecialchars($service->nombre ?? '') ?>" required
                        class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-xl bg-gray-50 text-gray-900 focus:outline-none focus:ring-primary focus:border-transparent transition-all"
                        placeholder="Ej. Corte de pelo caballero">
                </div>

                <!-- Descripción -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Descripción <span class="text-red-500">*</span></label>
                    <textarea name="descripcion" required rows="4"
                        class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-xl bg-gray-50 text-gray-900 focus:outline-none focus:ring-primary focus:border-transparent transition-all"
                        placeholder="Cuéntales a tus clientes sobre este servicio..."><?= htmlspecialchars($service->descripcion ?? '') ?></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Duración -->
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

                    <!-- Precio -->
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

                    <!-- Activo -->
                    <div class="flex items-end pb-3">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="activo" value="1" class="sr-only peer" <?= (isset($service) && !$service->activo) ? '' : 'checked' ?>>
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-orange-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:width-5 after:transition-all peer-checked:bg-primary"></div>
                            <span class="ml-3 text-sm font-bold text-gray-700">Servicio activo</span>
                        </label>
                    </div>
                </div>

                <!-- Categoría -->
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
