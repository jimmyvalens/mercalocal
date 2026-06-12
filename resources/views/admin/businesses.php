<?php

/**
 * @var array $businesses Array de comercios con información del propietario
 */
require_once ROOT_DIR . '/resources/views/main_header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 flex flex-col md:flex-row gap-8">

    <!-- Sidebar Admin -->
    <div class="w-full md:w-64 shrink-0">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sticky top-24">
            <div class="flex items-center gap-3 mb-6 pb-6 border-b border-gray-100">
                <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-xl flex items-center justify-center text-xl">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <div>
                    <h3 class="font-bold text-gray-900 leading-tight">Administración</h3>
                    <p class="text-xs text-gray-500">Panel de control</p>
                </div>
            </div>
            <nav class="space-y-2">
                <a href="<?= BASE_URL ?>/admin/dashboard" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 hover:text-primary font-medium rounded-xl transition-colors">
                    <i class="fa-solid fa-chart-pie w-5"></i> Resumen
                </a>
                <a href="<?= BASE_URL ?>/admin/businesses" class="flex items-center gap-3 px-4 py-3 bg-orange-50 text-primary font-bold rounded-xl">
                    <i class="fa-solid fa-store w-5"></i> Comercios
                </a>
                <a href="<?= BASE_URL ?>/logout" class="flex items-center gap-3 px-4 py-3 mt-4 text-red-600 hover:bg-red-50 font-medium rounded-xl transition-colors">
                    <i class="fa-solid fa-arrow-right-from-bracket w-5"></i> Cerrar Sesión
                </a>
            </nav>
        </div>
    </div>

    <!-- Contenido -->
    <div class="flex-grow min-w-0">
        <h1 class="text-3xl font-bold text-secondary mb-8">📊 Gestión de Comercios</h1>

        <!-- Stats cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 text-center hover:shadow-md transition-shadow">
                <p class="text-sm font-medium text-gray-500 mb-2">Comercios Totales</p>
                <p class="text-3xl font-bold text-gray-900"><?= count($businesses) ?></p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 text-center hover:shadow-md transition-shadow">
                <p class="text-sm font-medium text-gray-500 mb-2">Comercios Activos</p>
                <p class="text-3xl font-bold text-gray-900"><?= count(array_filter($businesses, fn($b) => $b['activo'] == 1)) ?></p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 text-center hover:shadow-md transition-shadow">
                <p class="text-sm font-medium text-gray-500 mb-2">Productos Totales</p>
                <p class="text-3xl font-bold text-gray-900"><?= array_sum(array_column($businesses, 'product_count')) ?></p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 text-center hover:shadow-md transition-shadow">
                <p class="text-sm font-medium text-gray-500 mb-2">Servicios Totales</p>
                <p class="text-3xl font-bold text-gray-900"><?= array_sum(array_column($businesses, 'service_count')) ?></p>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gradient-to-r from-secondary to-blue-600 text-white">
                        <tr>
                            <th class="px-6 py-4 text-left text-sm font-semibold">Comercio</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold">Propietario</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold">Email</th>
                            <th class="px-6 py-4 text-center text-sm font-semibold">Productos</th>
                            <th class="px-6 py-4 text-center text-sm font-semibold">Servicios</th>
                            <th class="px-6 py-4 text-center text-sm font-semibold">Estado</th>
                            <th class="px-6 py-4 text-center text-sm font-semibold">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($businesses as $business): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4">
                                    <span class="font-semibold text-gray-900">
                                        <?= htmlspecialchars($business['nombre']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-gray-600">
                                    <?= htmlspecialchars($business['owner_name']) ?>
                                </td>
                                <td class="px-6 py-4 text-gray-600 text-sm">
                                    <?= htmlspecialchars($business['owner_email']) ?>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-block bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-sm font-medium">
                                        <?= $business['product_count'] ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-block bg-purple-100 text-purple-700 px-3 py-1 rounded-full text-sm font-medium">
                                        <?= $business['service_count'] ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <?php if ($business['activo'] == 1): ?>
                                        <span class="inline-block bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-bold">
                                            ✓ Activo
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-block bg-red-100 text-red-700 px-3 py-1 rounded-full text-xs font-bold">
                                            ✗ Inactivo
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="<?= BASE_URL ?>/admin/business/<?= $business['id'] ?>"
                                            class="inline-flex items-center justify-center w-8 h-8 bg-blue-50 text-blue-600 hover:bg-blue-100 rounded-lg transition-colors" title="Ver Detalles">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>
                                        <a href="<?= BASE_URL ?>/admin/business/<?= $business['id'] ?>/edit"
                                            class="inline-flex items-center justify-center w-8 h-8 bg-orange-50 text-orange-600 hover:bg-orange-100 rounded-lg transition-colors" title="Editar">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </a>
                                        <form action="<?= BASE_URL ?>/admin/business/<?= $business['id'] ?>/delete" method="POST" class="inline-block" onsubmit="return confirm('¿Estás seguro de que deseas eliminar (desactivar) este comercio?');">
                                            <button type="submit" class="inline-flex items-center justify-center w-8 h-8 bg-red-50 text-red-600 hover:bg-red-100 rounded-lg transition-colors" title="Eliminar">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once ROOT_DIR . '/resources/views/layout/footer.php'; ?>