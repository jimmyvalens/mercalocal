<?php

/**
 * @var array $business Información del comercio con propietario
 * @var array $products Array de productos del comercio
 * @var array $services Array de servicios del comercio
 * @var array $businessStats Estadísticas de ventas e ingresos
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
        <!-- Header del comercio -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-secondary mb-3">
                <?= htmlspecialchars($business['nombre']) ?>
            </h1>
            <p class="text-gray-600">
                <?= htmlspecialchars($business['descripcion']) ?>
            </p>
        </div>

        <!-- Info Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div class="bg-gray-50 rounded-lg p-4 border border-gray-100">
                <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Propietario</p>
                <p class="text-gray-900 font-medium">
                    <?= htmlspecialchars($business['owner_name']) ?>
                </p>
            </div>
            <div class="bg-gray-50 rounded-lg p-4 border border-gray-100">
                <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Email Propietario</p>
                <p class="text-gray-900 font-medium text-sm">
                    <?= htmlspecialchars($business['owner_email']) ?>
                </p>
            </div>
            <div class="bg-gray-50 rounded-lg p-4 border border-gray-100">
                <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Teléfono</p>
                <p class="text-gray-900 font-medium">
                    <?= htmlspecialchars($business['owner_phone'] ?? 'No disponible') ?>
                </p>
            </div>
            <div class="bg-gray-50 rounded-lg p-4 border border-gray-100">
                <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Email Comercio</p>
                <p class="text-gray-900 font-medium text-sm">
                    <?= htmlspecialchars($business['email']) ?>
                </p>
            </div>
            <div class="bg-gray-50 rounded-lg p-4 border border-gray-100">
                <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Teléfono Comercio</p>
                <p class="text-gray-900 font-medium">
                    <?= htmlspecialchars($business['telefono']) ?>
                </p>
            </div>
            <div class="bg-gray-50 rounded-lg p-4 border border-gray-100">
                <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Estado</p>
                <?php if ($business['activo'] == 1): ?>
                    <span class="inline-block bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-bold">
                        ✓ Activo
                    </span>
                <?php else: ?>
                    <span class="inline-block bg-red-100 text-red-700 px-3 py-1 rounded-full text-xs font-bold">
                        ✗ Inactivo
                    </span>
                <?php endif; ?>
            </div>
            <div class="bg-gray-50 rounded-lg p-4 border border-gray-100">
                <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Sitio Web</p>
                <p class="text-gray-900 font-medium text-sm">
                    <?php if ($business['web']): ?>
                        <a href="<?= htmlspecialchars($business['web']) ?>" target="_blank" class="text-primary hover:underline">
                            <?= htmlspecialchars($business['web']) ?>
                        </a>
                    <?php else: ?>
                        No disponible
                    <?php endif; ?>
                </p>
            </div>
            <div class="bg-gray-50 rounded-lg p-4 border border-gray-100">
                <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Registrado</p>
                <p class="text-gray-900 font-medium">
                    <?= date('d/m/Y H:i', strtotime($business['created_at'])) ?>
                </p>
            </div>
        </div>

        <!-- Estadísticas -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 text-center hover:shadow-md transition-shadow">
                <p class="text-sm font-medium text-gray-500 mb-2">Productos</p>
                <p class="text-3xl font-bold text-gray-900"><?= count($products) ?></p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 text-center hover:shadow-md transition-shadow">
                <p class="text-sm font-medium text-gray-500 mb-2">Servicios</p>
                <p class="text-3xl font-bold text-gray-900"><?= count($services) ?></p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 text-center hover:shadow-md transition-shadow">
                <p class="text-sm font-medium text-gray-500 mb-2">Ventas Totales</p>
                <p class="text-3xl font-bold text-gray-900"><?= $businessStats['total_sales'] ?? 0 ?></p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 text-center hover:shadow-md transition-shadow">
                <p class="text-sm font-medium text-gray-500 mb-2">Ingresos</p>
                <p class="text-3xl font-bold text-primary">€<?= number_format($businessStats['total_revenue'] ?? 0, 2) ?></p>
            </div>
        </div>

        <!-- PRODUCTOS -->
        <div class="mb-12">
            <h2 class="text-2xl font-bold text-secondary mb-6">📦 Productos (<?= count($products) ?>)</h2>

            <?php if (empty($products)): ?>
                <div class="text-center py-12 bg-gray-50 rounded-lg border border-gray-100">
                    <p class="text-gray-500">No hay productos publicados</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($products as $product): ?>
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-shadow">
                            <?php if ($product['imagen']): ?>
                                <img src="<?= BASE_URL ?>/img/products/<?= htmlspecialchars($product['imagen']) ?>"
                                    alt="<?= htmlspecialchars($product['nombre']) ?>"
                                    class="w-full h-48 object-cover">
                            <?php else: ?>
                                <div class="w-full h-48 bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center">
                                    <i class="fa-solid fa-image text-3xl text-gray-300"></i>
                                </div>
                            <?php endif; ?>

                            <div class="p-5">
                                <h3 class="font-bold text-gray-900 mb-2">
                                    <?= htmlspecialchars($product['nombre']) ?>
                                </h3>

                                <?php if ($product['category_name']): ?>
                                    <p class="text-xs text-gray-500 mb-2">
                                        <span class="bg-blue-100 text-blue-700 px-2 py-1 rounded">
                                            <?= htmlspecialchars($product['category_name']) ?>
                                        </span>
                                    </p>
                                <?php endif; ?>

                                <p class="text-sm text-gray-600 mb-3 line-clamp-2">
                                    <?= htmlspecialchars(substr($product['descripcion'] ?? '', 0, 100)) ?>...
                                </p>

                                <p class="text-xs text-gray-500 mb-3">
                                    <strong>Stock:</strong> <?= $product['stock'] ?> unidades
                                </p>

                                <div class="flex justify-between items-center">
                                    <span class="text-lg font-bold text-primary">
                                        €<?= number_format($product['precio'], 2) ?>
                                    </span>
                                    <?php if ($product['activo'] == 1): ?>
                                        <span class="bg-green-100 text-green-700 px-2 py-1 rounded-full text-xs font-bold">
                                            Activo
                                        </span>
                                    <?php else: ?>
                                        <span class="bg-gray-100 text-gray-700 px-2 py-1 rounded-full text-xs font-bold">
                                            Inactivo
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- SERVICIOS -->
        <div class="mb-12">
            <h2 class="text-2xl font-bold text-secondary mb-6">🔧 Servicios (<?= count($services) ?>)</h2>

            <?php if (empty($services)): ?>
                <div class="text-center py-12 bg-gray-50 rounded-lg border border-gray-100">
                    <p class="text-gray-500">No hay servicios publicados</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($services as $service): ?>
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-shadow">
                            <div class="w-full h-48 bg-gradient-to-br from-purple-400 to-pink-400 flex items-center justify-center">
                                <i class="fa-solid fa-tools text-5xl text-white opacity-80"></i>
                            </div>

                            <div class="p-5">
                                <h3 class="font-bold text-gray-900 mb-2">
                                    <?= htmlspecialchars($service['nombre']) ?>
                                </h3>

                                <?php if ($service['category_name']): ?>
                                    <p class="text-xs text-gray-500 mb-2">
                                        <span class="bg-purple-100 text-purple-700 px-2 py-1 rounded">
                                            <?= htmlspecialchars($service['category_name']) ?>
                                        </span>
                                    </p>
                                <?php endif; ?>

                                <p class="text-sm text-gray-600 mb-3 line-clamp-2">
                                    <?= htmlspecialchars(substr($service['descripcion'] ?? '', 0, 100)) ?>...
                                </p>

                                <p class="text-xs text-gray-500 mb-3">
                                    <strong>Duración:</strong> <?= $service['duracion'] ?> minutos
                                </p>

                                <div class="flex justify-between items-center">
                                    <span class="text-lg font-bold text-primary">
                                        €<?= number_format($service['precio'], 2) ?>
                                    </span>
                                    <?php if ($service['activo'] == 1): ?>
                                        <span class="bg-green-100 text-green-700 px-2 py-1 rounded-full text-xs font-bold">
                                            Activo
                                        </span>
                                    <?php else: ?>
                                        <span class="bg-gray-100 text-gray-700 px-2 py-1 rounded-full text-xs font-bold">
                                            Inactivo
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once ROOT_DIR . '/resources/views/layout/footer.php'; ?>