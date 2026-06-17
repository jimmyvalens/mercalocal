<?php

/**
 * @var array|null $business Datos del comercio (si estamos editando)
 * @var array $categories Lista de categorías para el select
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

    <!-- Cabecera -->
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

    <!-- Contenedor del Formulario -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sm:p-8 mb-8">
        <h2 class="font-bold text-gray-900 mb-6 pb-3 border-b border-gray-100">
            <i class="fa-solid fa-circle-info text-primary mr-2"></i> Información del Establecimiento
        </h2>

        <form action="<?= $actionUrl ?>" method="POST" class="space-y-6">
            <?= \App\Core\Session::csrfField() ?>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <!-- Nombre del Comercio -->
                <div class="sm:col-span-2">
                    <label class="block text-sm font-bold text-gray-700 mb-2">Nombre del Comercio *</label>
                    <input type="text" name="nombre" required
                        value="<?= htmlspecialchars($business['nombre'] ?? '') ?>"
                        class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-xl bg-gray-50 text-gray-900 focus:outline-none focus:ring-primary focus:border-primary transition-colors"
                        placeholder="Ej: Frutería Paco" />
                </div>

                <!-- ID del Usuario Propietario -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">ID Usuario Propietario *</label>
                    <input type="number" name="user_id" required
                        value="<?= htmlspecialchars($business['user_id'] ?? '') ?>"
                        class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-xl bg-gray-50 text-gray-900 focus:outline-none focus:ring-primary focus:border-primary transition-colors"
                        placeholder="Ej: 5" />
                    <p class="text-xs text-gray-500 mt-1">ID del usuario que administrará el comercio.</p>
                </div>

                <!-- Email de contacto -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Email de Contacto</label>
                    <input type="email" name="email"
                        value="<?= htmlspecialchars($business['email'] ?? '') ?>"
                        class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-xl bg-gray-50 text-gray-900 focus:outline-none focus:ring-primary focus:border-primary transition-colors"
                        placeholder="contacto@comercio.com" />
                </div>

                <!-- Teléfono -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Teléfono</label>
                    <input type="text" name="telefono"
                        value="<?= htmlspecialchars($business['telefono'] ?? '') ?>"
                        class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-xl bg-gray-50 text-gray-900 focus:outline-none focus:ring-primary focus:border-primary transition-colors"
                        placeholder="600 000 000" />
                </div>

                <!-- Estado (Solo visible si es edición, o por defecto Activo) -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Estado del Comercio</label>
                    <select name="activo"
                        class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-xl bg-gray-50 text-gray-900 focus:outline-none focus:ring-primary focus:border-primary transition-colors">
                        <option value="1" <?= (($business['activo'] ?? 1) == 1) ? 'selected' : '' ?>>Activo (Visible)</option>
                        <option value="0" <?= (($business['activo'] ?? 1) == 0) ? 'selected' : '' ?>>Inactivo (Oculto)</option>
                    </select>
                </div>

                <!-- Descripción -->
                <div class="sm:col-span-2">
                    <label class="block text-sm font-bold text-gray-700 mb-2">Descripción del Comercio</label>
                    <textarea name="descripcion" rows="4"
                        class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-xl bg-gray-50 text-gray-900 focus:outline-none focus:ring-primary focus:border-primary transition-colors"
                        placeholder="Breve descripción de los productos o servicios que ofrece..."><?= htmlspecialchars($business['descripcion'] ?? '') ?></textarea>
                </div>
            </div>

            <!-- Botones de Acción -->
            <div class="mt-8 pt-6 border-t border-gray-100 flex flex-col sm:flex-row gap-4 justify-end">
                <a href="<?= BASE_URL ?>/admin/businesses"
                    class="px-6 py-3 border border-gray-300 text-gray-700 rounded-xl font-bold text-center hover:bg-gray-50 transition-colors">
                    Cancelar
                </a>
                <button type="submit" class="btn-primary px-8 py-3 rounded-xl font-bold flex items-center justify-center gap-2">
                    <i class="fa-solid fa-save"></i> <?= $isEdit ? 'Guardar Cambios' : 'Crear Comercio' ?>
                </button>
            </div>

        </form>
    </div>
</div>

<?php require_once ROOT_DIR . '/resources/views/layout/footer.php'; ?>
