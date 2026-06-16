<?php

/**
 * @var array $businesses Array de comercios con información del propietario
 * @var array $categories Array de categorías para el filtro
 */
require_once ROOT_DIR . '/resources/views/main_header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 flex flex-col md:flex-row gap-8">

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
                <a href="<?= BASE_URL ?>/admin/businesses" class="flex items-center gap-3 px-4 py-3 bg-orange-50 text-orange-600 font-bold rounded-xl">
                    <i class="fa-solid fa-store w-5"></i> Comercios
                </a>
                <a href="<?= BASE_URL ?>/logout" class="flex items-center gap-3 px-4 py-3 mt-4 text-red-600 hover:bg-red-50 font-medium rounded-xl transition-colors">
                    <i class="fa-solid fa-arrow-right-from-bracket w-5"></i> Cerrar Sesión
                </a>
            </nav>
        </div>
    </div>

    <div class="flex-grow min-w-0">

        <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-8 gap-4">
            <h1 class="text-3xl font-bold text-gray-900">📊 Gestión de Comercios</h1>
            <a href="<?= BASE_URL ?>/admin/business/create" class="inline-flex items-center gap-2 bg-blue-600 text-white px-5 py-2.5 rounded-xl hover:bg-blue-700 transition-colors shadow-sm font-medium">
                <i class="fa-solid fa-plus"></i> Crear Comercio
            </a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 text-center hover:shadow-md transition-shadow">
                <p class="text-sm font-medium text-gray-500 mb-2">Comercios Totales</p>
                <p class="text-3xl font-bold text-gray-900"><?= count($businesses) ?></p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 text-center hover:shadow-md transition-shadow">
                <p class="text-sm font-medium text-gray-500 mb-2">Comercios Activos</p>
                <p class="text-3xl font-bold text-green-600"><?= count(array_filter($businesses, fn($b) => ($b['status'] ?? '') === 'ACTIVE')) ?></p>
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

        <form method="GET" action="<?= BASE_URL ?>/admin/businesses" class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 mb-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-4 items-end">

            <div class="lg:col-span-4 w-full">
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Buscar comercio o propietario</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fa-solid fa-search text-gray-400"></i>
                    </div>
                    <input type="text" name="search" id="search" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" placeholder="Nombre, email, teléfono..." class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-xl leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition-colors">
                </div>
            </div>

            <div class="lg:col-span-2 w-full">
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                <select name="status" id="status" class="block w-full py-2 pl-3 pr-10 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-xl transition-colors">
                    <option value="">Todos</option>
                    <option value="PENDING" <?= ($_GET['status'] ?? '') === 'PENDING' ? 'selected' : '' ?>>Pendientes</option>
                    <option value="ACTIVE" <?= ($_GET['status'] ?? '') === 'ACTIVE' ? 'selected' : '' ?>>Activos</option>
                    <option value="SUSPENDED" <?= ($_GET['status'] ?? '') === 'SUSPENDED' ? 'selected' : '' ?>>Suspendidos</option>
                </select>
            </div>

            <div class="lg:col-span-2 w-full">
                <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Tipo de Negocio</label>
                <select name="type" id="type" class="block w-full py-2 pl-3 pr-10 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-xl transition-colors">
                    <option value="">Todos</option>
                    <option value="PRODUCTS" <?= ($_GET['type'] ?? '') === 'PRODUCTS' ? 'selected' : '' ?>>Productos</option>
                    <option value="SERVICES" <?= ($_GET['type'] ?? '') === 'SERVICES' ? 'selected' : '' ?>>Servicios</option>
                    <option value="HYBRID" <?= ($_GET['type'] ?? '') === 'HYBRID' ? 'selected' : '' ?>>Híbrido (Ambos)</option>
                </select>
            </div>

            <div class="lg:col-span-2 w-full">
                <label for="category" class="block text-sm font-medium text-gray-700 mb-1">Categoría</label>
                <select name="category" id="category" class="block w-full py-2 pl-3 pr-10 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-xl transition-colors">
                    <option value="">Todas</option>
                    <?php if (isset($categories)): foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= ($_GET['category'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['nombre']) ?>
                            </option>
                    <?php endforeach;
                    endif; ?>
                </select>
            </div>

            <div class="lg:col-span-2 flex gap-2 w-full">
                <button type="submit" class="flex-1 bg-gray-800 text-white py-2 rounded-xl hover:bg-gray-900 transition-colors font-medium text-sm">
                    Filtrar
                </button>
                <?php if (!empty($_GET['search']) || !empty($_GET['status']) || !empty($_GET['type']) || !empty($_GET['category'])): ?>
                    <a href="<?= BASE_URL ?>/admin/businesses" class="flex-1 text-center bg-gray-100 text-gray-600 py-2 rounded-xl hover:bg-gray-200 transition-colors font-medium text-sm block">
                        Limpiar
                    </a>
                <?php endif; ?>
            </div>
        </form>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Comercio</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Propietario</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Contacto</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Oferta</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Estado</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if (empty($businesses)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                    No se han encontrado comercios con los filtros aplicados.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($businesses as $business): ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4">
                                        <span class="font-bold text-gray-900 block">
                                            <?= htmlspecialchars($business['nombre']) ?>
                                        </span>
                                        <div class="flex items-center gap-2 mt-1">
                                            <span class="text-xs text-gray-400">ID: <?= $business['id'] ?></span>

                                            <?php
                                            $type = $business['business_type'] ?? 'PRODUCTS';
                                            if ($type === 'PRODUCTS'): ?>
                                                <span class="text-[10px] bg-blue-50 text-blue-600 px-1.5 py-0.5 rounded font-semibold border border-blue-100">Tienda</span>
                                            <?php elseif ($type === 'SERVICES'): ?>
                                                <span class="text-[10px] bg-purple-50 text-purple-600 px-1.5 py-0.5 rounded font-semibold border border-purple-100">Servicios</span>
                                            <?php else: ?>
                                                <span class="text-[10px] bg-orange-50 text-orange-600 px-1.5 py-0.5 rounded font-semibold border border-orange-100">Mixto</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 text-gray-600 font-medium text-sm">
                                        <?= htmlspecialchars($business['owner_name']) ?>
                                    </td>

                                    <td class="px-6 py-4 text-gray-600 text-sm">
                                        <div class="flex flex-col">
                                            <span><?= htmlspecialchars($business['owner_email']) ?></span>
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 text-center">
                                        <div class="flex flex-col gap-1 items-center">
                                            <span class="inline-flex items-center gap-1 bg-blue-50 text-blue-700 px-2.5 py-0.5 rounded-md text-xs font-medium border border-blue-100" title="Productos">
                                                <i class="fa-solid fa-box w-3"></i> <?= $business['product_count'] ?>
                                            </span>
                                            <span class="inline-flex items-center gap-1 bg-purple-50 text-purple-700 px-2.5 py-0.5 rounded-md text-xs font-medium border border-purple-100" title="Servicios">
                                                <i class="fa-solid fa-bell-concierge w-3"></i> <?= $business['service_count'] ?>
                                            </span>
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 text-center">
                                        <?php
                                        $status = $business['status'] ?? 'PENDING';
                                        if ($status === 'ACTIVE'): ?>
                                            <span class="inline-flex items-center gap-1 bg-green-50 text-green-700 px-3 py-1 rounded-full text-xs font-bold border border-green-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Activo
                                            </span>
                                        <?php elseif ($status === 'PENDING'): ?>
                                            <span class="inline-flex items-center gap-1 bg-amber-50 text-amber-700 px-3 py-1 rounded-full text-xs font-bold border border-amber-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span> Pendiente
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center gap-1 bg-red-50 text-red-700 px-3 py-1 rounded-full text-xs font-bold border border-red-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> Suspendido
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="px-6 py-4 text-center">
                                        <div class="flex items-center justify-center gap-4">
                                            <a href="<?= BASE_URL ?>/admin/business/<?= $business['id'] ?>"
                                                class="inline-flex items-center justify-center w-8 h-8 bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white rounded-lg transition-colors shadow-sm" title="Ver Detalles">
                                                <i class="fa-solid fa-eye"></i>
                                            </a>
                                            <a href="<?= BASE_URL ?>/admin/business/<?= $business['id'] ?>/edit"
                                                class="inline-flex items-center justify-center w-8 h-8 bg-orange-50 text-orange-600 hover:bg-orange-500 hover:text-white rounded-lg transition-colors shadow-sm" title="Editar">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </a>
                                            <form action="<?= BASE_URL ?>/admin/business/<?= $business['id'] ?>/delete" method="POST" class="inline-block" onsubmit="return confirm('¿Estás seguro de que deseas deshabilitar este comercio?');">
                                                <button type="submit" class="inline-flex items-center justify-center w-8 h-8 bg-red-50 text-red-600 hover:bg-red-600 hover:text-white rounded-lg transition-colors shadow-sm" title="Desactivar">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once ROOT_DIR . '/resources/views/layout/footer.php'; ?>