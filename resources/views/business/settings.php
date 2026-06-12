<?php require_once ROOT_DIR . '/resources/views/main_header.php'; ?>
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="mb-8 flex items-center gap-4">
        <a href="<?= BASE_URL ?>/business/dashboard" class="text-gray-500 hover:text-primary transition-colors flex items-center gap-2">
            <i class="fa-solid fa-arrow-left"></i> Volver al panel
        </a>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-8 py-6 bg-gray-50 border-b border-gray-100 flex items-center gap-4">
            <div class="w-12 h-12 bg-green-100 text-primary rounded-xl flex items-center justify-center text-xl">
                <i class="fa-solid fa-gear"></i>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-secondary">Configuración del Comercio</h1>
                <p class="text-sm text-gray-500">Actualiza la información pública de tu negocio.</p>
            </div>
        </div>

        <form action="<?= BASE_URL ?>/business/dashboard/settings/update" method="POST" class="p-8 space-y-6">
            <div class="grid grid-cols-1 gap-6">
                <!-- Nombre -->
                <div>
                    <label for="nombre" class="block text-sm font-bold text-gray-700 mb-2">Nombre del Comercio <span class="text-red-500">*</span></label>
                    <input id="nombre" name="nombre" type="text" required value="<?= htmlspecialchars($business['nombre'] ?? '') ?>" 
                        class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-xl bg-gray-50 text-gray-900 focus:outline-none focus:ring-primary focus:border-transparent transition-all" />
                </div>

                <!-- Descripción -->
                <div>
                    <label for="descripcion" class="block text-sm font-bold text-gray-700 mb-2">Descripción <span class="text-red-500">*</span></label>
                    <textarea id="descripcion" name="descripcion" rows="4" required 
                        class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-xl bg-gray-50 text-gray-900 focus:outline-none focus:ring-primary focus:border-transparent transition-all"><?= htmlspecialchars($business['descripcion'] ?? '') ?></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Teléfono -->
                    <div>
                        <label for="telefono" class="block text-sm font-bold text-gray-700 mb-2">Teléfono <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                <i class="fa-solid fa-phone"></i>
                            </div>
                            <input id="telefono" name="telefono" type="tel" required value="<?= htmlspecialchars($business['telefono'] ?? '') ?>" 
                                class="pl-10 appearance-none block w-full px-4 py-3 border border-gray-300 rounded-xl bg-gray-50 text-gray-900 focus:outline-none focus:ring-primary focus:border-transparent transition-all" />
                        </div>
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-bold text-gray-700 mb-2">Correo Electrónico <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                <i class="fa-solid fa-envelope"></i>
                            </div>
                            <input id="email" name="email" type="email" required value="<?= htmlspecialchars($business['email'] ?? '') ?>" 
                                class="pl-10 appearance-none block w-full px-4 py-3 border border-gray-300 rounded-xl bg-gray-50 text-gray-900 focus:outline-none focus:ring-primary focus:border-transparent transition-all" />
                        </div>
                    </div>
                </div>

                <!-- Sitio Web -->
                <div>
                    <label for="web" class="block text-sm font-bold text-gray-700 mb-2">Sitio Web <span class="text-gray-400 font-normal">(Opcional)</span></label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                            <i class="fa-solid fa-globe"></i>
                        </div>
                        <input id="web" name="web" type="url" value="<?= htmlspecialchars($business['web'] ?? '') ?>" 
                            class="pl-10 appearance-none block w-full px-4 py-3 border border-gray-300 rounded-xl bg-gray-50 text-gray-900 focus:outline-none focus:ring-primary focus:border-transparent transition-all" placeholder="https://..." />
                    </div>
                </div>
            </div>

            <div class="flex gap-4 pt-6 border-t border-gray-100">
                <button type="submit" class="flex-1 bg-primary hover:bg-green-700 text-white font-bold py-3 px-6 rounded-xl transition-all shadow-sm flex items-center justify-center gap-2">
                    <i class="fa-solid fa-check"></i> Guardar Cambios
                </button>
                <a href="<?= BASE_URL ?>/business/dashboard" class="flex-1 text-center py-3 px-6 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl transition-all">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>
<?php require_once ROOT_DIR . '/resources/views/layout/footer.php'; ?>