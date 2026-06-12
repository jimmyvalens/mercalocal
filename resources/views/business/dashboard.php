<?php require_once ROOT_DIR . '/resources/views/main_header.php'; ?>
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 flex flex-col md:flex-row gap-8">
    <div class="w-full md:w-64 shrink-0">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sticky top-24">
            <div class="flex items-center gap-3 mb-6 pb-6 border-b border-gray-100">
                <div class="w-12 h-12 bg-orange-100 text-primary rounded-xl flex items-center justify-center text-xl">
                    <i class="fa-solid fa-store"></i>
                </div>
                <div>
                    <h3 class="font-bold text-gray-900 leading-tight">Mi Comercio</h3>
                    <p class="text-xs text-gray-500">Panel de gestión</p>
                </div>
            </div>
            <nav class="space-y-2">
                <a href="<?= BASE_URL ?>/business/dashboard" class="flex items-center gap-3 px-4 py-3 bg-orange-50 text-primary font-bold rounded-xl"><i class="fa-solid fa-chart-pie w-5"></i> Resumen</a>
                <a href="<?= BASE_URL ?>/business/dashboard/products" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 hover:text-primary font-medium rounded-xl"><i class="fa-solid fa-box w-5"></i> Productos</a>
                <a href="<?= BASE_URL ?>/business/dashboard/services" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 hover:text-primary font-medium rounded-xl"><i class="fa-solid fa-handshake-angle w-5"></i> Servicios</a>
                <a href="<?= BASE_URL ?>/business/dashboard/schedules" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 hover:text-primary font-medium rounded-xl"><i class="fa-solid fa-clock w-5"></i> Horarios</a>
                <a href="<?= BASE_URL ?>/business/dashboard/orders" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 hover:text-primary font-medium rounded-xl"><i class="fa-solid fa-receipt w-5"></i> Pedidos <span class="ml-auto bg-primary text-white text-xs font-bold px-2 py-0.5 rounded-full">3</span></a>
                <a href="<?= BASE_URL ?>/business/dashboard/settings" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 hover:text-primary font-medium rounded-xl"><i class="fa-solid fa-gear w-5"></i> Configuración</a>
            </nav>
        </div>
    </div>

    <div class="flex-grow min-w-0">
        <h1 class="text-3xl font-bold text-secondary mb-8">Información General</h1>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex items-center gap-4">
                <div class="w-14 h-14 rounded-xl bg-blue-50 text-blue-500 flex items-center justify-center text-2xl"><i class="fa-solid fa-boxes-stacked"></i></div>
                <div>
                    <p class="text-sm font-medium text-gray-500 mb-1">Productos</p>
                    <p class="text-2xl font-bold text-gray-900"><?= number_format($stats['products'] ?? 0) ?></p>
                </div>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex items-center gap-4">
                <div class="w-14 h-14 rounded-xl bg-purple-50 text-purple-500 flex items-center justify-center text-2xl"><i class="fa-solid fa-handshake-angle"></i></div>
                <div>
                    <p class="text-sm font-medium text-gray-500 mb-1">Servicios</p>
                    <p class="text-2xl font-bold text-gray-900"><?= number_format($stats['services'] ?? 0) ?></p>
                </div>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex items-center gap-4">
                <div class="w-14 h-14 rounded-xl bg-green-50 text-green-500 flex items-center justify-center text-2xl"><i class="fa-solid fa-money-bill-wave"></i></div>
                <div>
                    <p class="text-sm font-medium text-gray-500 mb-1">Ventas del Mes</p>
                    <p class="text-2xl font-bold text-gray-900">1,240 €</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-8">
            <div class="px-6 py-5 border-b border-gray-100 flex justify-between items-center">
                <h2 class="text-lg font-bold text-secondary">Pedidos Recientes</h2>
            </div>
            <div class="p-8 text-center bg-gray-50 text-gray-500 text-sm">
                Aún no hay actividad reciente. Una vez que añadas productos, tus pedidos aparecerán aquí.
            </div>
        </div>
    </div>
</div>
<?php require_once ROOT_DIR . '/resources/views/layout/footer.php'; ?>