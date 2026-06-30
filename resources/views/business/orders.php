<?php require_once ROOT_DIR . '/resources/views/main_header.php'; ?>

<?php
function estadoBadge(?string $estado): string
{
    // Limpiamos todo al extremo
    $e = strtolower(trim($estado));

    // Quitamos tildes para comparar sin miedo
    $e = str_replace(['á', 'é', 'í', 'ó', 'ú'], ['a', 'e', 'i', 'o', 'u'], $e);

    if (str_contains($e, 'pend')) return 'bg-amber-50 text-amber-800 border-amber-200';
    if (str_contains($e, 'prepar')) return 'bg-blue-50 text-blue-800 border-blue-200';
    if (str_contains($e, 'list')) return 'bg-indigo-50 text-indigo-800 border-indigo-200';
    if (str_contains($e, 'complet')) return 'bg-teal-50 text-teal-800 border-teal-200';
    if (str_contains($e, 'cancel')) return 'bg-red-50 text-red-800 border-red-200';

    return 'bg-gray-50 text-gray-700 border-gray-200';
}
?>

<main class="flex-grow flex flex-col bg-gray-50">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 w-full">
        <div class="flex items-center justify-between mb-8">
            <h1 class="text-3xl font-bold text-secondary flex items-center gap-4">
                <i class="fa-solid fa-receipt text-primary"></i> Pedidos Recibidos
            </h1>
            <a href="<?= BASE_URL ?>/business/dashboard" class="text-gray-500 hover:text-primary transition-colors flex items-center gap-2 font-medium">
                <i class="fa-solid fa-arrow-left"></i> Volver al panel
            </a>
        </div>

        <form method="GET" action="<?= BASE_URL ?>/business/dashboard/orders" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">

                <div class="md:col-span-2">
                    <label for="search" class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Buscar Cliente / Teléfono</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </span>
                        <input type="text" name="search" id="search"
                            value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                            placeholder="Ej. Juan Pérez o 600123456..."
                            class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 focus:bg-white transition-all">
                    </div>
                </div>

                <div>
                    <label for="status" class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Estado del Pedido</label>
                    <select name="status" id="status"
                        class="w-full px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 focus:bg-white transition-all text-gray-700">
                        <option value="">Todos los estados</option>
                        <option value="PENDIENTE" <?= ['estado'] === 'PENDIENTE' ? 'selected' : '' ?>>Pendiente</option>
                        <option value="PREPARANDO" <?= ['estado'] === 'PREPARANDO' ? 'selected' : '' ?>>Preparando</option>
                        <option value="LISTO" <?= ['estado'] === 'LISTO' ? 'selected' : '' ?>>Listo</option>
                        <option value="COMPLETADO" <?= ['estado'] === 'COMPLETADO' ? 'selected' : '' ?>>Completado</option>
                        <option value="CANCELADO" <?= ['estado'] === 'CANCELADO' ? 'selected' : '' ?>>Cancelado</option>
                    </select>
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="flex-grow bg-primary hover:bg-orange-600 text-white font-bold py-2.5 px-4 rounded-xl text-sm transition-all shadow-sm flex items-center justify-center gap-2 cursor-pointer">
                        <i class="fa-solid fa-filter"></i> Filtrar
                    </button>

                    <?php if (!empty($_GET['search']) || !empty($_GET['status'])): ?>
                        <a href="<?= BASE_URL ?>/business/dashboard/orders" class="bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold py-2.5 px-3 rounded-xl text-sm transition-all flex items-center justify-center" title="Limpiar filtros">
                            <i class="fa-solid fa-trash-can"></i>
                        </a>
                    <?php endif; ?>
                </div>

            </div>
        </form>

        <div class="bg-white shadow-sm border border-gray-100 rounded-2xl overflow-hidden">
            <?php if (empty($orders)): ?>
                <div class="p-16 text-center">
                    <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fa-solid fa-inbox text-4xl text-gray-300"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-secondary mb-2">Aún no hay pedidos</h3>
                    <p class="text-gray-500 max-w-sm mx-auto mb-8 text-lg">Los pedidos que realicen tus clientes aparecerán aquí automáticamente.</p>
                </div>
            <?php else: ?>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden w-full max-w-full">
                    <div class="overflow-x-auto">
                        <ul class="divide-y divide-gray-100">
                            <?php foreach ($orders as $o): ?>
                                <li class="p-8 hover:bg-gray-50 flex flex-col gap-4 transition-colors">

                                    <div class="flex justify-between items-center">
                                        <div class="flex items-center gap-6">
                                            <div class="w-14 h-14 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center text-xl font-bold shadow-sm">
                                                #<?= $o['id'] ?>
                                            </div>
                                            <div>
                                                <div class="flex items-center gap-3 mb-1">
                                                    <p class="font-bold text-gray-900 text-xl"><?= htmlspecialchars($o['client_name']) ?></p>

                                                    <?php if (($o['delivery_method'] ?? 'domicilio') === 'tienda'): ?>
                                                        <span class="bg-amber-50 text-amber-700 text-xs font-bold px-2.5 py-1 rounded-md flex items-center gap-1.5 border border-amber-200">
                                                            <i class="fa-solid fa-shop"></i> Recogida
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="bg-indigo-50 text-indigo-700 text-xs font-bold px-2.5 py-1 rounded-md flex items-center gap-1.5 border border-indigo-200">
                                                            <i class="fa-solid fa-motorcycle"></i> Domicilio
                                                        </span>
                                                    <?php endif; ?>
                                                </div>

                                                <p class="text-sm text-gray-500 flex items-center gap-2">
                                                    <i class="fa-regular fa-clock"></i> <?= date('d-m-Y H:i', strtotime($o['created_at'])) ?>
                                                </p>
                                            </div>
                                        </div>

                                        <div class="text-right flex flex-col items-end">
                                            <p class="font-bold text-secondary text-2xl mb-2"><?= number_format($o['total'], 2, ',', '.') ?> &euro;</p>

                                            <form action="/business/dashboard/orders/update-status" method="POST">
                                                <input type="hidden" name="purchase_id" value="<?= $o['id'] ?>">
                                                <?= \App\Core\Session::csrfField() ?>

                                                <select name="nuevo_estado" onchange="this.form.submit()"
                                                    class="text-xs font-bold px-3 py-1.5 rounded-lg border <?= estadoBadge($o['estado']) ?> uppercase tracking-wider shadow-sm cursor-pointer focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all text-center">
                                                    <option value="PENDIENTE" <?= $o['estado'] === 'PENDIENTE' ? 'selected' : '' ?>>Pendiente</option>
                                                    <option value="PREPARANDO" <?= $o['estado'] === 'PREPARANDO' ? 'selected' : '' ?>>Preparando</option>
                                                    <option value="LISTO" <?= $o['estado'] === 'LISTO' ? 'selected' : '' ?>>Listo</option>
                                                    <option value="COMPLETADO" <?= $o['estado'] === 'COMPLETADO' ? 'selected' : '' ?>>Completado</option>
                                                    <option value="CANCELADO" <?= $o['estado'] === 'CANCELADO' ? 'selected' : '' ?>>Cancelado</option>
                                                </select>
                                            </form>
                                        </div>
                                    </div>

                                    <div class="ml-20 grid grid-cols-1 md:grid-cols-2 gap-4 bg-slate-50 border border-slate-100 rounded-xl p-4 text-sm text-gray-600">
                                        <div class="flex items-start gap-2.5">
                                            <div class="p-2 bg-blue-100 text-blue-600 rounded-lg mt-0.5">
                                                <i class="fa-solid fa-phone text-xs"></i>
                                            </div>
                                            <div>
                                                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-0.5">Teléfono del cliente</p>
                                                <p class="font-semibold text-gray-900 font-mono"><?= htmlspecialchars($o['client_phone'] ?? 'No disponible') ?></p>
                                            </div>
                                        </div>

                                        <div class="flex items-start gap-2.5">
                                            <?php if (($o['delivery_method'] ?? 'domicilio') === 'tienda'): ?>
                                                <div class="p-2 bg-amber-100 text-amber-700 rounded-lg mt-0.5">
                                                    <i class="fa-solid fa-store text-xs"></i>
                                                </div>
                                                <div>
                                                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-0.5">Destino del pedido</p>
                                                    <p class="font-medium text-amber-800">Sin envío. El cliente recogerá el pedido presencialmente.</p>
                                                </div>
                                            <?php else: ?>
                                                <div class="p-2 bg-indigo-100 text-indigo-600 rounded-lg mt-0.5">
                                                    <i class="fa-solid fa-location-dot text-xs"></i>
                                                </div>
                                                <div>
                                                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-0.5">Dirección de envío</p>
                                                    <p class="font-semibold text-gray-900">
                                                        <?= htmlspecialchars($o['calle'] ?? 'Dirección omitida') ?>, Nº <?= htmlspecialchars($o['numero'] ?? '-') ?>
                                                    </p>
                                                    <p class="text-xs text-gray-500 mt-0.5">
                                                        <?= htmlspecialchars($o['codigo_postal'] ?? '-') ?> - <?= htmlspecialchars($o['ciudad'] ?? '-') ?> (<?= htmlspecialchars($o['provincia'] ?? '-') ?>)
                                                    </p>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <?php if (!empty($o['items'])): ?>
                                        <div class="bg-gray-50 border border-gray-100 rounded-xl p-4 ml-20">
                                            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Productos a preparar:</p>
                                            <ul class="space-y-1.5">
                                                <?php foreach ($o['items'] as $item): ?>
                                                    <li class="text-sm text-gray-700 flex justify-between items-center max-w-md">
                                                        <div class="flex items-center gap-2">
                                                            <span class="bg-orange-100 text-orange-700 px-2 py-0.5 rounded text-xs font-extrabold">
                                                                x<?= $item['cantidad'] ?>
                                                            </span>
                                                            <span class="font-medium"><?= htmlspecialchars($item['producto_nombre']) ?></span>
                                                        </div>
                                                        <span class="text-gray-400 text-xs">(<?= number_format($item['precio_unitario'], 2, ',', '.') ?> €/u)</span>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php endif; ?>

                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php require_once ROOT_DIR . '/resources/views/layout/footer_dashboard.php'; ?>