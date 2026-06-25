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
                        <option value="pendiente" <?= ($_GET['status'] ?? '') === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                        <option value="pagado" <?= ($_GET['status'] ?? '') === 'pagado' ? 'selected' : '' ?>>Pagado</option>
                        <option value="en-preparacion" <?= ($_GET['status'] ?? '') === 'en-preparacion' ? 'selected' : '' ?>>En Preparación</option>
                        <option value="enviado" <?= ($_GET['status'] ?? '') === 'enviado' ? 'selected' : '' ?>>Enviado</option>
                        <option value="entregado" <?= ($_GET['status'] ?? '') === 'entregado' ? 'selected' : '' ?>>Entregado</option>
                        <option value="cancelado" <?= ($_GET['status'] ?? '') === 'cancelado' ? 'selected' : '' ?>>Cancelado</option>
                    </select>
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="flex-grow bg-primary hover:bg-orange-600 text-white font-bold py-2.5 px-4 rounded-xl text-sm transition-all shadow-sm flex items-center justify-center gap-2">
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

                <ul class="divide-y divide-gray-100">
                    <?php foreach ($orders as $o): ?>
                        <li class="p-8 hover:bg-gray-50 flex justify-between items-center transition-colors">

                            <div class="flex items-center gap-6">
                                <div class="w-14 h-14 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center text-xl font-bold shadow-sm">
                                    #<?= $o['id'] ?>
                                </div>
                                <div>
                                    <p class="font-bold text-gray-900 text-xl mb-1"><?= htmlspecialchars($o['client_name']) ?></p>
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
                                        <option value="PAGADO" <?= $o['estado'] === 'PAGADO' ? 'selected' : '' ?>>Pagado</option>
                                        <option value="EN PREPARACION" <?= $o['estado'] === 'EN PREPARACION' ? 'selected' : '' ?>>En Preparación</option>
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
</main>

<?php require_once ROOT_DIR . '/resources/views/layout/footer_dashboard.php'; ?>