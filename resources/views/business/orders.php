<?php require_once ROOT_DIR . '/resources/views/main_header.php'; ?>

<?php
function estadoBadge(string $estado): string {
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
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 w-full">
        <div class="flex items-center justify-between mb-8">
            <h1 class="text-3xl font-bold text-secondary flex items-center gap-4">
                <i class="fa-solid fa-receipt text-primary"></i> Pedidos Recibidos
            </h1>
            <a href="<?= BASE_URL ?>/business/dashboard" class="text-gray-500 hover:text-primary transition-colors flex items-center gap-2 font-medium">
                <i class="fa-solid fa-arrow-left"></i> Volver al panel
            </a>
        </div>

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
                            <div class="text-right">
                                <p class="font-bold text-secondary text-2xl mb-2"><?= number_format($o['total'], 2) ?> &euro;</p>
                                <span class="text-xs font-bold px-3 py-1.5 rounded-lg border <?= estadoBadge($o['estado']) ?> uppercase tracking-wider inline-block shadow-sm">
                                    <?= htmlspecialchars($o['estado']) ?>
                                </span>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php require_once ROOT_DIR . '/resources/views/layout/footer.php'; ?>