<?php

/** @var array $stats */ ?>
<?php require_once ROOT_DIR . '/resources/views/main_header.php'; ?>

<?php
function estadoBadge(?string $estado): string
{
    // Limpiamos todo al extremo
    $e = strtolower(trim($estado));

    // Quitamos tildes para comparar sin miedo
    $e = str_replace(['á', 'é', 'í', 'ó', 'ú'], ['a', 'e', 'i', 'o', 'u'], $e);

    if (str_contains($e, 'pend')) return 'bg-amber-50 text-amber-800 border-amber-200';
    if (str_contains($e, 'pagad')) return 'bg-emerald-50 text-emerald-800 border-emerald-200';
    if (str_contains($e, 'prepar')) return 'bg-blue-50 text-blue-800 border-blue-200';
    if (str_contains($e, 'enviad')) return 'bg-indigo-50 text-indigo-800 border-indigo-200';
    if (str_contains($e, 'entreg')) return 'bg-teal-50 text-teal-800 border-teal-200';
    if (str_contains($e, 'cancel')) return 'bg-red-50 text-red-800 border-red-200';

    return 'bg-gray-50 text-gray-700 border-gray-200';
}
?>

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
                <a href="<?= BASE_URL ?>/business/dashboard/schedules" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 hover:text-primary font-medium rounded-xl"><i class="fa-solid fa-clock w-5"></i> Horarios</a>
                <a href="<?= BASE_URL ?>/business/dashboard/orders" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 hover:text-primary font-medium rounded-xl"><i class="fa-solid fa-receipt w-5"></i> Pedidos <span class="ml-auto bg-primary text-white text-xs font-bold px-2 py-0.5 rounded-full"><?= $stats['pending_orders'] ?></span></a>
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
                    <p class="text-2xl font-bold text-gray-900"><?= number_format($stats['monthly_sales'], 2, ',', '.') ?> €</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-8">
            <div class="px-6 py-5 border-b border-gray-100 flex justify-between items-center">
                <h2 class="text-lg font-bold text-secondary">Pedidos Recientes</h2>
                <a href="/business/dashboard/orders" class="text-sm font-medium text-blue-600 hover:text-blue-700 transition-colors">
                    Ver todos
                </a>
            </div>

            <?php if (empty($recentOrders)): ?>
                <div class="p-8 text-center bg-gray-50 text-gray-500 text-sm">
                    Aún no hay actividad reciente. Una vez que añadas productos, tus pedidos aparecerán aquí.
                </div>
            <?php else: ?>
                <ul class="divide-y divide-gray-100">
                    <?php foreach ($recentOrders as $o): ?>
                        <li class="p-6 hover:bg-gray-50 flex justify-between items-center transition-colors">

                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center text-md font-bold shadow-sm">
                                    #<?= $o['id'] ?>
                                </div>
                                <div>
                                    <p class="font-bold text-gray-900 text-base mb-0.5"><?= htmlspecialchars($o['client_name']) ?></p>
                                    <p class="text-xs text-gray-500 flex items-center gap-1.5">
                                        <i class="fa-regular fa-clock"></i> <?= date('d-m-Y H:i', strtotime($o['created_at'])) ?>
                                    </p>
                                </div>
                            </div>

                            <div class="text-right flex flex-col items-end justify-center min-w-[130px]">
                                <p class="font-bold text-secondary text-base mb-1 block">
                                    <?= number_format($o['total'], 2, ',', '.') ?> &euro;
                                </p>

                                <form action="/business/dashboard/orders/update-status" method="POST" class="block text-right">
                                    <input type="hidden" name="purchase_id" value="<?= $o['id'] ?>">
                                    <?= \App\Core\Session::csrfField() ?>
                                    <select name="nuevo_estado" onchange="this.form.submit()"
                                        class="text-[10px] font-bold px-2 py-0.5 rounded-md border <?= estadoBadge($o['estado']) ?> uppercase tracking-wider shadow-sm cursor-pointer focus:outline-none focus:ring-1 focus:ring-blue-400 transition-all text-center"
                                        style="min-width: 110px; max-width: 130px;">
                                        <option value="PENDIENTE" <?= $o['estado'] === 'PENDIENTE' ? 'selected' : '' ?>>Pendiente</option>
                                        <option value="PAGADO" <?= $o['estado'] === 'PAGADO' ? 'selected' : '' ?>>Pagado</option>
                                        <option value="EN PREPARACION" <?= $o['estado'] === 'EN PREPARACION' ? 'selected' : '' ?>>En Prep.</option>
                                        <option value="ENVIADO" <?= $o['estado'] === 'ENVIADO' ? 'selected' : '' ?>>Enviado</option>
                                        <option value="ENTREGADO" <?= $o['estado'] === 'ENTREGADO' ? 'selected' : '' ?>>Entregado</option>
                                        <option value="CANCELADO" <?= $o['estado'] === 'CANCELADO' ? 'selected' : '' ?>>Cancelado</option>
                                    </select>
                                </form>
                            </div>

                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require_once ROOT_DIR . '/resources/views/layout/footer_dashboard.php'; ?>