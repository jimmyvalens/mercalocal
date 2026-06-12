<?php require_once ROOT_DIR . '/resources/views/main_header.php'; ?>

<?php
// Helper para clases del badge de estado
function estadoBadgeClass(string $estado): string {
    return match(strtolower($estado)) {
        'pendiente'   => 'bg-yellow-50 text-yellow-700 border-yellow-200',
        'completado'  => 'bg-green-50 text-green-700 border-green-200',
        'cancelado'   => 'bg-red-50 text-red-700 border-red-200',
        'en proceso'  => 'bg-blue-50 text-blue-700 border-blue-200',
        default       => 'bg-gray-50 text-gray-700 border-gray-200',
    };
}
?>

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
                    <p class="text-gray-500 text-sm sm:text-base max-w-sm mx-auto mb-8">Cuando realices tu primer pedido en algún comercio local, aparecerá aquí para que puedas seguir su estado.</p>
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
                                    <?php if (isset($o['business_name'])): ?>
                                        <p class="text-xs sm:text-sm text-primary font-medium mt-1 sm:mt-2 flex items-center gap-2">
                                            <i class="fa-solid fa-shop text-xs"></i> <?= htmlspecialchars($o['business_name']) ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                                <div class="text-left sm:text-right w-full sm:w-auto flex flex-row sm:flex-col justify-between items-center sm:items-end">
                                    <p class="font-bold text-secondary text-xl sm:text-2xl sm:mb-2"><?= number_format($o['total'], 2) ?> &euro;</p>
                                    <span class="text-[10px] sm:text-xs font-bold px-2 py-1 sm:px-3 sm:py-1.5 rounded-lg border <?= estadoBadgeClass($o['estado']) ?> uppercase tracking-wider inline-block">
                                        <?= htmlspecialchars($o['estado']) ?>
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Detalles del pedido -->
                            <details class="mt-4 group border border-gray-100 rounded-xl bg-white overflow-hidden">
                                <summary class="cursor-pointer text-sm font-semibold text-gray-600 bg-gray-50 px-4 py-3 hover:bg-gray-100 transition-colors flex justify-between items-center list-none">
                                    <span><i class="fa-solid fa-circle-info mr-2"></i>Ver detalles del pedido</span>
                                    <i class="fa-solid fa-chevron-down transition-transform group-open:rotate-180"></i>
                                </summary>
                                <div class="p-4 border-t border-gray-100">
                                    <h4 class="text-sm font-bold text-gray-700 mb-3 border-b pb-2">Artículos</h4>
                                    <?php if (!empty($o['items'])): ?>
                                        <ul class="space-y-2">
                                            <?php foreach ($o['items'] as $item): ?>
                                                <li class="flex justify-between text-sm">
                                                    <span class="text-gray-600"><span class="font-medium text-gray-800"><?= $item['cantidad'] ?>x</span> <?= htmlspecialchars($item['nombre'] ?? 'Producto') ?></span>
                                                    <span class="text-gray-800 font-medium"><?= number_format($item['precio_unitario'] * $item['cantidad'], 2) ?> &euro;</span>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <p class="text-sm text-gray-500">No hay detalles disponibles.</p>
                                    <?php endif; ?>
                                    
                                    <div class="mt-4 pt-3 border-t border-gray-100 text-sm flex flex-col gap-1 text-gray-600">
                                        <div class="flex justify-between">
                                            <span>Método de entrega:</span>
                                            <span class="font-medium">Envío a domicilio / Recogida</span> <!-- Dinamizar en el futuro -->
                                        </div>
                                    </div>
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