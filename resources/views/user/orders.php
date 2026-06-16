<?php require_once ROOT_DIR . '/resources/views/main_header.php'; ?>

<main class="flex-grow flex flex-col bg-gray-50">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 w-full">
        <div class="flex items-center gap-4 mb-8">
            <a href="<?= BASE_URL ?>/user/dashboard" class="text-gray-500 hover:text-primary transition-colors flex items-center gap-2">
                <i class="fa-solid fa-arrow-left"></i> Volver al panel
            </a>
            <h1 class="text-2xl sm:text-3xl font-bold text-secondary m-0">Mis Pedidos</h1>
        </div>

        <div class="bg-white shadow-sm border border-gray-100 rounded-2xl overflow-hidden max-w-3xl mx-auto">
            <?php if (empty($orders)): ?>
                <div class="p-10 sm:p-16 text-center">
                    <div class="w-16 h-16 sm:w-20 sm:h-20 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fa-solid fa-box-open text-3xl sm:text-4xl text-gray-300"></i>
                    </div>
                    <h3 class="text-xl sm:text-2xl font-bold text-secondary mb-2">Aún no tienes pedidos</h3>
                    <p class="text-gray-500 text-sm sm:text-base max-w-sm mx-auto mb-8">Cuando realices tu primer pedido, aparecerá aquí para que puedas seguir su estado.</p>
                    <a href="<?= BASE_URL ?>/businesses" class="bg-primary hover:bg-green-700 text-white font-bold py-2 sm:py-3 px-6 sm:px-8 rounded-xl transition-all shadow-sm inline-flex items-center gap-2 text-sm sm:text-base">
                        <i class="fa-solid fa-store"></i> Explorar Comercios
                    </a>
                </div>
            <?php else: ?>
                <ul class="divide-y divide-gray-100">
                    <?php foreach ($orders as $o): ?>
                        <li class="p-4 sm:p-6 hover:bg-gray-50 transition-colors">
                            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                                <div>
                                    <p class="font-bold text-gray-900 text-lg sm:text-xl mb-1">Pedido #<?= str_pad($o['id'], 4, '0', STR_PAD_LEFT) ?></p>
                                    <p class="text-xs sm:text-sm text-gray-500 flex items-center gap-2">
                                        <i class="fa-regular fa-calendar"></i> <?= date('d-m-Y', strtotime($o['created_at'])) ?>
                                    </p>
                                </div>
                                <div class="text-left sm:text-right w-full sm:w-auto flex flex-row sm:flex-col justify-between items-center sm:items-end">
                                    <p class="font-bold text-secondary text-xl sm:text-2xl sm:mb-2"><?= number_format($o['total'], 2) ?> &euro;</p>

                                    <span class="... <?= \App\Core\BaseController::getStatusClass($o['status']) ?> ...">
                                        <?= htmlspecialchars(\App\Core\BaseController::translateStatus($o['status'])) ?>
                                    </span>
                                </div>
                            </div>

                            <details class="mt-4 group border border-gray-100 rounded-xl bg-white overflow-hidden">
                                <summary class="cursor-pointer text-sm font-semibold text-gray-600 bg-gray-50 px-4 py-3 hover:bg-gray-100 transition-colors flex justify-between items-center list-none">
                                    <span><i class="fa-solid fa-circle-info mr-2"></i>Ver detalles del pedido</span>
                                    <i class="fa-solid fa-chevron-down transition-transform group-open:rotate-180"></i>
                                </summary>
                                <div class="p-4 border-t border-gray-100">
                                    <h4 class="text-sm font-bold text-gray-700 mb-3 border-b pb-2">Artículos</h4>
                                    <?php
                                    // DEPURACIÓN: Esto te dirá si el array llega vacío o si tiene datos
                                    echo "<pre>DEBUG: Contenido de orders: ";
                                    print_r($orders);
                                    echo "</pre>";
                                    ?>
                                    <?php if (!empty($o['items'])): ?>
                                        <ul class="space-y-2">
                                            <?php foreach ($o['items'] as $item): ?>
                                                <li class="flex justify-between text-sm">
                                                    <span class="text-gray-600">
                                                        <span class="font-medium text-gray-800"><?= $item['quantity'] ?>x</span>
                                                        <?= htmlspecialchars($item['name'] ?? 'Producto') ?>
                                                    </span>
                                                    <span class="text-gray-800 font-medium"><?= number_format($item['unit_price'] * $item['quantity'], 2) ?> &euro;</span>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <p class="text-sm text-gray-500">No hay detalles disponibles.</p>
                                    <?php endif; ?>
                                </div>
                            </details>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php require_once ROOT_DIR . '/resources/views/layout/footer.php'; ?>