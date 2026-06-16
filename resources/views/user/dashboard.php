<?php
// Forzamos compatibilidad para que el header tenga los datos que necesita
if (isset($user) && !isset($_SESSION['user_name'])) {
    $_SESSION['user_name'] = $user->first_name ?? 'Usuario';
}
require ROOT_DIR . '/resources/views/main_header.php';
?>

<main class="flex-grow flex flex-col bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 flex flex-col md:flex-row gap-8 w-full">

        <div class="w-full md:w-64 shrink-0">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sticky top-24">
                <div class="flex items-center gap-3 mb-6 pb-6 border-b border-gray-100">
                    <div class="w-12 h-12 bg-orange-100 text-primary rounded-xl flex items-center justify-center text-xl shadow-sm">
                        <i class="fa-solid fa-user"></i>
                    </div>
                    <div class="min-w-0">
                        <h3 class="font-bold text-gray-900 leading-tight truncate"><?= htmlspecialchars($user->first_name ?? 'Usuario') ?></h3>
                        <p class="text-xs text-gray-500 uppercase tracking-wider font-bold">Mi Área</p>
                    </div>
                </div>
                <nav class="space-y-2">
                    <a href="<?= BASE_URL ?>/businesses" class="flex items-center gap-3 px-4 py-3 bg-orange-50 text-primary font-bold rounded-xl border border-orange-100 transition-all hover:bg-orange-100">
                        <i class="fa-solid fa-shop w-5"></i> Empezar a comprar
                    </a>
                    <a href="<?= BASE_URL ?>/profile" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 hover:text-primary font-medium rounded-xl transition-colors">
                        <i class="fa-solid fa-user-circle w-5"></i> Mi Perfil
                    </a>
                    <a href="<?= BASE_URL ?>/orders" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 hover:text-primary font-medium rounded-xl transition-colors">
                        <i class="fa-solid fa-box-open w-5"></i> Mis Pedidos
                    </a>
                    <a href="#" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 hover:text-primary font-medium rounded-xl transition-colors" onclick="alert('Funcionalidad de Favoritos en desarrollo')">
                        <i class="fa-solid fa-heart w-5"></i> Mis Favoritos
                    </a>
                    <div class="pt-4 mt-4 border-t border-gray-100">
                        <a href="<?= BASE_URL ?>/logout" class="flex items-center gap-3 px-4 py-3 text-red-600 hover:bg-red-50 font-bold rounded-xl transition-colors">
                            <i class="fa-solid fa-arrow-right-from-bracket w-5"></i> Cerrar Sesión
                        </a>
                    </div>
                </nav>
            </div>
        </div>

        <div class="flex-grow min-w-0">
            <h1 class="text-4xl font-bold text-secondary mb-8">¡Hola, <?= htmlspecialchars($user->first_name ?? 'Usuario') ?>! <span class="text-3xl">👋</span></h1>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-8 mb-10">
                <a href="<?= BASE_URL ?>/orders" class="bg-white rounded-3xl shadow-sm border border-gray-100 p-8 flex flex-col justify-center gap-6 hover:shadow-xl transition-all">
                    <h2 class="text-2xl font-bold text-gray-900">Mis Pedidos</h2>
                </a>
                <a href="<?= BASE_URL ?>/profile" class="bg-white rounded-3xl shadow-sm border border-gray-100 p-8 flex flex-col justify-center gap-6 hover:shadow-xl transition-all">
                    <h2 class="text-2xl font-bold text-gray-900">Mi Perfil</h2>
                </a>
            </div>

            <div class="mt-6 bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-8 py-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                    <h2 class="text-xl font-bold text-secondary">Actividad Reciente</h2>
                </div>

                <div class="p-4">
                    <?php if (empty($recentActivity)): ?>
                        <div class="p-10 text-center">
                            <p class="text-gray-500 font-medium">No hay actividad reciente.</p>
                        </div>
                    <?php else: ?>
                        <ul class="divide-y divide-gray-50">
                            <?php foreach ($recentActivity as $activity): ?>
                                <li class="p-6 flex items-center gap-6">
                                    <div class="flex-1">
                                        <p class="text-lg font-bold"><?= htmlspecialchars($activity['title']) ?></p>
                                        <p class="text-sm text-gray-500"><?= htmlspecialchars($activity['description']) ?></p>
                                    </div>
                                    <div class="text-xs font-bold text-gray-400 uppercase">
                                        <?= $activity['time'] ?>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once ROOT_DIR . '/resources/views/layout/footer.php'; ?>