<?php require_once ROOT_DIR . '/resources/views/main_header.php'; ?>
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

    <div class="flex items-center justify-between mb-8">
        <div>
            <a href="<?= BASE_URL ?>/business/dashboard" class="text-sm text-gray-500 hover:text-primary font-medium flex items-center gap-1 mb-2">
                <i class="fa-solid fa-arrow-left text-xs"></i> Volver al panel
            </a>
            <h1 class="text-3xl font-bold text-secondary flex items-center gap-3">
                <i class="fa-solid fa-handshake-angle text-primary"></i> Mis Servicios
            </h1>
        </div>
        <a href="<?= BASE_URL ?>/business/dashboard/services/create"
           class="btn-primary inline-flex items-center gap-2 px-5 py-2.5">
            <i class="fa-solid fa-plus"></i> Nuevo Servicio
        </a>
    </div>

    <?php if (empty($services)): ?>
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-12 text-center">
            <i class="fa-solid fa-handshake-angle text-5xl text-gray-300 mb-4"></i>
            <h3 class="text-xl font-medium text-gray-700 mb-1">Sin servicios todavía</h3>
            <p class="text-gray-500 text-sm mb-6">Añade servicios para que los clientes puedan reservar citas.</p>
            <a href="<?= BASE_URL ?>/business/dashboard/services/create" class="btn-primary px-6 py-2.5">
                <i class="fa-solid fa-plus mr-2"></i> Crear primer servicio
            </a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($services as $s): ?>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex flex-col hover:shadow-md transition-all group">
                    <div class="p-6 flex-grow">
                        <div class="flex justify-between items-start mb-3">
                            <h3 class="text-lg font-bold text-gray-900 group-hover:text-primary transition-colors">
                                <?= htmlspecialchars($s->nombre) ?>
                            </h3>
                            <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-purple-100 text-purple-700">
                                <?= intval($s->duracion) ?> min
                            </span>
                        </div>
                        <p class="text-sm text-gray-500 mb-4 line-clamp-2"><?= htmlspecialchars($s->descripcion) ?></p>
                        <div class="text-2xl font-extrabold text-secondary">
                            <?= number_format($s->precio, 2) ?> €
                        </div>
                    </div>
                    <div class="p-4 bg-gray-50 border-t border-gray-100 flex gap-2">
                        <a href="<?= BASE_URL ?>/business/dashboard/services/<?= $s->id ?>/edit"
                           class="flex-1 text-center py-2 px-3 bg-white border border-gray-200 text-gray-700 font-bold text-sm rounded-xl hover:bg-gray-100 transition-colors">
                            <i class="fa-solid fa-pencil mr-1"></i> Editar
                        </a>
                        <form action="<?= BASE_URL ?>/business/dashboard/services/<?= $s->id ?>/delete" method="POST"
                              onsubmit="return confirm('¿Eliminar este servicio?');">
                            <?= \App\Core\Session::csrfField() ?>
                            <button type="submit"
                                class="py-2 px-3 bg-white border border-red-200 text-red-600 font-bold text-sm rounded-xl hover:bg-red-50 transition-colors">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php require_once ROOT_DIR . '/resources/views/layout/footer.php'; ?>
