<?php

/**
 * @var array $business Información del comercio con propietario
 * @var array $products Array de productos del comercio
 * @var array $services Array de servicios del comercio
 * @var array $businessStats Estadísticas de ventas e ingresos
 */
require_once ROOT_DIR . '/resources/views/main_header.php';

// Salvaguardas para evitar errores de índices no definidos
$businessStats['total_sales'] = $businessStats['total_sales'] ?? 0;
$businessStats['total_revenue'] = $businessStats['total_revenue'] ?? 0.00;
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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
                <a href="<?= BASE_URL ?>/admin/businesses" class="flex items-center gap-3 px-4 py-3 bg-orange-50 text-primary font-bold rounded-xl">
                    <i class="fa-solid fa-store w-5"></i> Comercios
                </a>
                <a href="<?= BASE_URL ?>/logout" class="flex items-center gap-3 px-4 py-3 mt-4 text-red-600 hover:bg-red-50 font-medium rounded-xl transition-colors">
                    <i class="fa-solid fa-arrow-right-from-bracket w-5"></i> Cerrar Sesión
                </a>
            </nav>
        </div>
    </div>

    <div class="flex-grow min-w-0">

        <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-4 mb-8 pb-6 border-b border-gray-100">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <a href="<?= BASE_URL ?>/admin/businesses" class="text-sm text-blue-600 hover:underline flex items-center gap-1">
                        <i class="fa-solid fa-arrow-left"></i> Volver al listado
                    </a>
                </div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">
                    <?= htmlspecialchars($business['nombre']) ?>
                </h1>
                <p class="text-gray-600 max-w-3xl">
                    <?= htmlspecialchars($business['descripcion'] ?? 'Sin descripción disponible.') ?>
                </p>
            </div>

            <div class="flex items-center gap-2 self-end lg:self-start shrink-0">
                <a href="<?= BASE_URL ?>/admin/business/<?= $business['id'] ?>/edit" class="inline-flex items-center gap-2 bg-white border border-gray-200 text-gray-700 px-4 py-2.5 rounded-xl hover:bg-gray-50 transition-colors shadow-sm font-medium text-sm">
                    <i class="fa-solid fa-pen-to-square text-orange-500"></i> Editar
                </a>
                <form action="<?= BASE_URL ?>/admin/business/<?= $business['id'] ?>/delete" method="POST" class="inline-block form-eliminar-detalle">
                    <?= \App\Core\Session::csrfField() ?>
                    <button type="submit" class="inline-flex items-center gap-2 bg-red-50 text-red-600 px-4 py-2.5 rounded-xl hover:bg-red-600 hover:text-white transition-colors shadow-sm font-medium text-sm">
                        <i class="fa-solid fa-trash"></i> Eliminar Comercio
                    </button>
                </form>
            </div>
        </div>

        <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
            <i class="fa-solid fa-circle-info text-blue-500"></i> Información General
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm flex items-center gap-3">
                <div class="w-10 h-10 bg-gray-50 rounded-xl flex items-center justify-center text-gray-500">
                    <i class="fa-solid fa-user"></i>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Propietario</p>
                    <p class="text-gray-900 font-bold text-sm"><?= htmlspecialchars($business['owner_name']) ?></p>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm flex items-center gap-3">
                <div class="w-10 h-10 bg-gray-50 rounded-xl flex items-center justify-center text-gray-500">
                    <i class="fa-solid fa-envelope-open"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Email Propietario</p>
                    <p class="text-gray-900 font-bold text-xs truncate" title="<?= htmlspecialchars($business['owner_email']) ?>">
                        <?= htmlspecialchars($business['owner_email']) ?>
                    </p>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm flex items-center gap-3">
                <div class="w-10 h-10 bg-gray-50 rounded-xl flex items-center justify-center text-gray-500">
                    <i class="fa-solid fa-phone"></i>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Teléfono Propietario</p>
                    <p class="text-gray-900 font-bold text-sm"><?= htmlspecialchars($business['owner_phone'] ?? 'No disponible') ?></p>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm flex items-center gap-3">
                <div class="w-10 h-10 bg-gray-50 rounded-xl flex items-center justify-center">
                    <i class="fa-solid fa-circle-dot <?= $business['activo'] == 1 ? 'text-green-500' : 'text-red-500' ?>"></i>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Estado del Local</p>
                    <?php if ($business['activo'] == 1): ?>
                        <span class="inline-flex items-center text-green-700 text-xs font-bold">Activo</span>
                    <?php else: ?>
                        <span class="inline-flex items-center text-red-700 text-xs font-bold">Inactivo</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm flex items-center gap-3">
                <div class="w-10 h-10 bg-gray-50 rounded-xl flex items-center justify-center text-gray-500">
                    <i class="fa-solid fa-at"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Email Comercio</p>
                    <p class="text-gray-900 font-bold text-xs truncate" title="<?= htmlspecialchars($business['email']) ?>">
                        <?= htmlspecialchars($business['email']) ?>
                    </p>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm flex items-center gap-3">
                <div class="w-10 h-10 bg-gray-50 rounded-xl flex items-center justify-center text-gray-500">
                    <i class="fa-solid fa-store"></i>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Teléfono Comercio</p>
                    <p class="text-gray-900 font-bold text-sm"><?= htmlspecialchars($business['telefono']) ?></p>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm flex items-center gap-3">
                <div class="w-10 h-10 bg-gray-50 rounded-xl flex items-center justify-center text-gray-500">
                    <i class="fa-solid fa-globe"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Sitio Web</p>
                    <p class="text-gray-900 font-bold text-xs truncate">
                        <?php if (!empty($business['web'])): ?>
                            <a href="<?= htmlspecialchars($business['web']) ?>" target="_blank" class="text-blue-600 hover:underline">
                                <?= htmlspecialchars($business['web']) ?>
                            </a>
                        <?php else: ?>
                            <span class="text-gray-400 font-normal">No disponible</span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm flex items-center gap-3">
                <div class="w-10 h-10 bg-gray-50 rounded-xl flex items-center justify-center text-gray-500">
                    <i class="fa-solid fa-calendar-days"></i>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Registrado El</p>
                    <p class="text-gray-900 font-bold text-sm">
                        <?= isset($business['created_at']) ? date('d/m/Y H:i', strtotime($business['created_at'])) : '---' ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 text-center hover:shadow-md transition-shadow">
                <p class="text-sm font-medium text-gray-500 mb-2">Productos en Catálogo</p>
                <p class="text-3xl font-bold text-gray-900"><?= count($products) ?></p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 text-center hover:shadow-md transition-shadow">
                <p class="text-sm font-medium text-gray-500 mb-2">Servicios Ofrecidos</p>
                <p class="text-3xl font-bold text-gray-900"><?= count($services) ?></p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 text-center hover:shadow-md transition-shadow">
                <p class="text-sm font-medium text-gray-500 mb-2">Ventas Realizadas</p>
                <p class="text-3xl font-bold text-gray-900"><?= htmlspecialchars($businessStats['total_sales']) ?></p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 text-center hover:shadow-md transition-shadow">
                <p class="text-sm font-medium text-gray-500 mb-2">Facturación Total</p>
                <p class="text-3xl font-bold text-blue-600">€<?= number_format($businessStats['total_revenue'], 2) ?></p>
            </div>
        </div>

        <div class="mb-12">
            <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                <i class="fa-solid fa-box text-blue-500"></i> Productos de la tienda (<?= count($products) ?>)
            </h2>

            <?php if (empty($products)): ?>
                <div class="text-center py-12 bg-white rounded-2xl border border-gray-100 shadow-sm text-gray-500">
                    <p><i class="fa-solid fa-box-open text-2xl mb-2 text-gray-300 block"></i> No hay productos publicados actualmente.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($products as $product): ?>
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-shadow">
                            <?php if (!empty($product['imagen'])): ?>
                                <img src="<?= BASE_URL ?>/img/products/<?= htmlspecialchars($product['imagen']) ?>"
                                    alt="<?= htmlspecialchars($product['nombre']) ?>"
                                    class="w-full h-48 object-cover">
                            <?php else: ?>
                                <div class="w-full h-48 bg-gradient-to-br from-gray-50 to-gray-100 flex items-center justify-center">
                                    <i class="fa-solid fa-image text-3xl text-gray-300"></i>
                                </div>
                            <?php endif; ?>

                            <div class="p-5">
                                <h3 class="font-bold text-gray-900 mb-2 truncate">
                                    <?= htmlspecialchars($product['nombre']) ?>
                                </h3>

                                <?php if (!empty($product['category_name'])): ?>
                                    <p class="mb-3">
                                        <span class="bg-blue-50 text-blue-700 px-2.5 py-0.5 rounded-lg text-xs font-semibold border border-blue-100">
                                            <?= htmlspecialchars($product['category_name']) ?>
                                        </span>
                                    </p>
                                <?php endif; ?>

                                <p class="text-sm text-gray-600 mb-4 line-clamp-2">
                                    <?= htmlspecialchars($product['descripcion'] ?? 'Sin descripción.') ?>
                                </p>

                                <div class="text-xs text-gray-500 mb-4 bg-gray-50 p-2 rounded-xl flex justify-between">
                                    <span><strong>Stock:</strong> <?= $product['stock'] ?> uds</span>
                                    <span>ID: <?= $product['id'] ?></span>
                                </div>

                                <div class="flex justify-between items-center pt-2 border-t border-gray-50">
                                    <span class="text-lg font-bold text-gray-950">
                                        €<?= number_format($product['precio'], 2) ?>
                                    </span>
                                    <?php if ($product['activo'] == 1): ?>
                                        <span class="bg-green-50 text-green-700 px-2.5 py-0.5 rounded-full text-xs font-bold border border-green-100">Activo</span>
                                    <?php else: ?>
                                        <span class="bg-gray-100 text-gray-600 px-2.5 py-0.5 rounded-full text-xs font-bold">Inactivo</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="mb-12">
            <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                <i class="fa-solid fa-bell-concierge text-purple-500"></i> Servicios ofrecidos (<?= count($services) ?>)
            </h2>

            <?php if (empty($services)): ?>
                <div class="text-center py-12 bg-white rounded-2xl border border-gray-100 shadow-sm text-gray-500">
                    <p><i class="fa-solid fa-handshake-slash text-2xl mb-2 text-gray-300 block"></i> No hay servicios publicados actualmente.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($services as $service): ?>
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-shadow">
                            <div class="w-full h-44 bg-gradient-to-br from-purple-500 to-indigo-600 flex items-center justify-center relative">
                                <i class="fa-solid fa-gears text-5xl text-white opacity-20"></i>
                                <span class="absolute top-4 right-4 bg-white/20 backdrop-blur-md text-white text-xs px-2.5 py-1 rounded-lg font-medium">
                                    <i class="fa-regular fa-clock"></i> <?= $service['duracion'] ?> min
                                </span>
                            </div>

                            <div class="p-5">
                                <h3 class="font-bold text-gray-900 mb-2 truncate">
                                    <?= htmlspecialchars($service['nombre']) ?>
                                </h3>

                                <?php if (!empty($service['category_name'])): ?>
                                    <p class="mb-3">
                                        <span class="bg-purple-50 text-purple-700 px-2.5 py-0.5 rounded-lg text-xs font-semibold border border-purple-100">
                                            <?= htmlspecialchars($service['category_name']) ?>
                                        </span>
                                    </p>
                                <?php endif; ?>

                                <p class="text-sm text-gray-600 mb-4 line-clamp-2">
                                    <?= htmlspecialchars($service['descripcion'] ?? 'Sin descripción.') ?>
                                </p>

                                <div class="flex justify-between items-center pt-4 border-t border-gray-50">
                                    <span class="text-lg font-bold text-gray-950">
                                        €<?= number_format($service['precio'], 2) ?>
                                    </span>
                                    <?php if ($service['activo'] == 1): ?>
                                        <span class="bg-green-50 text-green-700 px-2.5 py-0.5 rounded-full text-xs font-bold border border-green-100">Activo</span>
                                    <?php else: ?>
                                        <span class="bg-gray-100 text-gray-600 px-2.5 py-0.5 rounded-full text-xs font-bold">Inactivo</span>
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

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const formBorrar = document.querySelector('.form-eliminar-detalle');
        if (formBorrar) {
            formBorrar.addEventListener('submit', function(e) {
                e.preventDefault();

                Swal.fire({
                    title: '¿Confirmas la eliminación del comercio?',
                    text: "Esta acción dará de baja el comercio y todo su catálogo asociado.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#3b82f6',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar',
                    background: '#ffffff',
                    customClass: {
                        popup: 'rounded-2xl border border-gray-100 shadow-xl'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        formBorrar.submit();
                    }
                });
            });
        }
    });
</script>

<?php require_once ROOT_DIR . '/resources/views/layout/footer.php'; ?>