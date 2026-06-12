<?php require_once ROOT_DIR . '/resources/views/main_header.php'; ?>
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
                <a href="<?= BASE_URL ?>/admin/dashboard" class="flex items-center gap-3 px-4 py-3 bg-orange-50 text-primary font-bold rounded-xl">
                    <i class="fa-solid fa-chart-pie w-5"></i> Resumen
                </a>
                <a href="<?= BASE_URL ?>/admin/businesses" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 hover:text-primary font-medium rounded-xl transition-colors">
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
        <h1 class="text-3xl font-bold text-secondary mb-8">Panel de Administración</h1>

        <!-- Stats cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex items-center gap-4 hover:shadow-md transition-shadow">
                <div class="w-14 h-14 rounded-xl bg-blue-50 text-blue-500 flex items-center justify-center text-2xl">
                    <i class="fa-solid fa-users"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500 mb-1">Usuarios</p>
                    <p class="text-2xl font-bold text-gray-900"><?= number_format($stats['users'] ?? 0) ?></p>
                </div>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex items-center gap-4 hover:shadow-md transition-shadow">
                <div class="w-14 h-14 rounded-xl bg-orange-50 text-primary flex items-center justify-center text-2xl">
                    <i class="fa-solid fa-store"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500 mb-1">Comercios</p>
                    <p class="text-2xl font-bold text-gray-900"><?= number_format($stats['businesses'] ?? 0) ?></p>
                </div>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex items-center gap-4 hover:shadow-md transition-shadow">
                <div class="w-14 h-14 rounded-xl bg-green-50 text-green-500 flex items-center justify-center text-2xl">
                    <i class="fa-solid fa-money-bill-wave"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500 mb-1">Ventas Totales</p>
                    <p class="text-2xl font-bold text-gray-900">€<?= number_format($stats['sales'] ?? 0, 2) ?></p>
                </div>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex items-center gap-4 hover:shadow-md transition-shadow">
                <div class="w-14 h-14 rounded-xl bg-purple-50 text-purple-500 flex items-center justify-center text-2xl">
                    <i class="fa-solid fa-chart-line"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500 mb-1">Ingresos</p>
                    <p class="text-2xl font-bold text-gray-900">€<?= number_format($stats['sales'] ?? 0, 2) ?></p>
                </div>
            </div>
        </div>

        <!-- Segunda fila de stats -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-sm font-medium text-gray-500">Comercios Activos</p>
                    <i class="fa-solid fa-check text-green-500"></i>
                </div>
                <p class="text-2xl font-bold text-gray-900"><?= number_format($stats['businesses_active'] ?? 0) ?></p>
                <p class="text-xs text-gray-400 mt-2">De <?= number_format($stats['businesses'] ?? 0) ?> totales</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-sm font-medium text-gray-500">Productos</p>
                    <i class="fa-solid fa-box text-orange-500"></i>
                </div>
                <p class="text-2xl font-bold text-gray-900"><?= number_format($stats['products'] ?? 0) ?></p>
                <p class="text-xs text-gray-400 mt-2">En catálogo</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-sm font-medium text-gray-500">Servicios</p>
                    <i class="fa-solid fa-wrench text-blue-500"></i>
                </div>
                <p class="text-2xl font-bold text-gray-900"><?= number_format($stats['services'] ?? 0) ?></p>
                <p class="text-xs text-gray-400 mt-2">Disponibles</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-sm font-medium text-gray-500">Pedidos</p>
                    <i class="fa-solid fa-shopping-cart text-emerald-500"></i>
                </div>
                <p class="text-2xl font-bold text-gray-900"><?= number_format($stats['orders'] ?? 0) ?></p>
                <p class="text-xs text-gray-400 mt-2">Completados</p>
            </div>
        </div>

        <!-- Acciones rápidas -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-8">
            <div class="px-6 py-5 border-b border-gray-100">
                <h2 class="text-lg font-bold text-secondary">Acciones Rápidas</h2>
            </div>
            <div class="p-6 grid grid-cols-1 sm:grid-cols-3 gap-4">
                <a href="<?= BASE_URL ?>/admin/businesses" class="bg-orange-50 hover:bg-orange-100 border border-orange-200 rounded-lg p-4 text-center transition-colors">
                    <i class="fa-solid fa-store text-2xl text-primary mb-2 block"></i>
                    <p class="font-semibold text-gray-900">Ver Comercios</p>
                    <p class="text-xs text-gray-500">Gestiona todos los comercios</p>
                </a>
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-center opacity-60">
                    <i class="fa-solid fa-chart-bar text-2xl text-blue-500 mb-2 block"></i>
                    <p class="font-semibold text-gray-900">Reportes</p>
                    <p class="text-xs text-gray-500">Próximamente</p>
                </div>
                <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-center opacity-60">
                    <i class="fa-solid fa-cog text-2xl text-green-500 mb-2 block"></i>
                    <p class="font-semibold text-gray-900">Configuración</p>
                    <p class="text-xs text-gray-500">Próximamente</p>
                </div>
            </div>
        </div>

        <!-- Información -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-100">
                <h2 class="text-lg font-bold text-secondary">Información de la Plataforma</h2>
            </div>
            <div class="p-6 space-y-4">
                <div class="flex justify-between items-center pb-4 border-b border-gray-100">
                    <p class="text-gray-600">Nombre de la Plataforma</p>
                    <p class="font-semibold text-gray-900">Mercalocal</p>
                </div>
                <div class="flex justify-between items-center pb-4 border-b border-gray-100">
                    <p class="text-gray-600">Versión</p>
                    <p class="font-semibold text-gray-900">1.0.0 (MVP)</p>
                </div>
                <div class="flex justify-between items-center">
                    <p class="text-gray-600">Estado</p>
                    <p class="font-semibold text-green-600">✓ En Producción</p>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once ROOT_DIR . '/resources/views/layout/footer.php'; ?>