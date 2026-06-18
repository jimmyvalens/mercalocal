<?php require_once ROOT_DIR . '/resources/views/main_header.php'; ?>
<div class="relative bg-secondary h-80 overflow-hidden" 
    <?php if (!empty($business->hero_path)): ?>
        style="background-image: url('<?= BASE_URL . '/' . htmlspecialchars($business->hero_path) ?>'); background-size: cover; background-position: center;"
    <?php endif; ?>
>
    <!-- Abstract Pattern Background (fallback) -->
    <?php if (empty($business->hero_path)): ?>
    <div class="absolute inset-0 opacity-20">
        <svg class="h-full w-full" preserveAspectRatio="xMidYMid slice" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 1463 360">
            <path class="text-white text-opacity-40" fill="currentColor" d="M-82.673 72l1761.849 472.086-134.327 501.315-1761.85-472.086z" />
            <path class="text-primary text-opacity-40" fill="currentColor" d="M-217.088 544.086L1544.761 72l134.327 501.316-1761.849 472.086z" />
        </svg>
    </div>
    <?php endif; ?>
    <div class="absolute inset-0 bg-gradient-to-t from-gray-900/80 to-transparent"></div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-full flex flex-col justify-end pb-12 relative z-10">
        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-6">
            <div class="flex items-end gap-6">
                <?php if (!empty($business->logo_path)): ?>
                    <img src="<?= BASE_URL . '/' . htmlspecialchars($business->logo_path) ?>" alt="Logo <?= htmlspecialchars($business->nombre) ?>" class="w-24 h-24 rounded-2xl shadow-lg border-4 border-white object-cover bg-white">
                <?php endif; ?>
                <div>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-primary text-white mb-4 shadow-sm">
                        <i class="fa-solid fa-store mr-2"></i> Comercio Local
                    </span>
                    <h1 class="text-4xl md:text-5xl font-extrabold text-white tracking-tight mb-2"><?= htmlspecialchars($business->nombre) ?></h1>
                    <p class="text-xl text-gray-300 max-w-2xl"><?= htmlspecialchars($business->descripcion) ?></p>
                </div>
            </div>
            <div class="flex flex-col sm:flex-row gap-3 shrink-0">
                <a href="#productos" class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-bold rounded-xl text-white bg-primary hover:bg-orange-600 transition-colors shadow-sm">Catálogo</a>
            </div>
        </div>
    </div>
</div>
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="flex flex-col lg:flex-row gap-8">
        <div class="lg:w-2/3 space-y-12">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex flex-wrap gap-6 items-center">
                <div class="flex items-center text-gray-700">
                    <div class="w-10 h-10 rounded-full bg-orange-50 text-primary flex items-center justify-center mr-3"><i class="fa-solid fa-phone"></i></div>
                    <span class="font-medium"><?= htmlspecialchars($business->telefono) ?></span>
                </div>
                <?php if (!empty($business->web)): ?>
                    <div class="flex items-center text-gray-700">
                        <div class="w-10 h-10 rounded-full bg-blue-50 text-blue-500 flex items-center justify-center mr-3"><i class="fa-solid fa-globe"></i></div>
                        <a href="<?= htmlspecialchars($business->web) ?>" target="_blank" class="font-medium hover:text-primary transition-colors"><?= htmlspecialchars($business->web) ?></a>
                    </div>
                <?php endif; ?>
            </div>
            <section id="productos">
                <h2 class="text-2xl font-bold text-secondary mb-6 flex items-center"><i class="fa-solid fa-box-open text-primary mr-3"></i> Productos</h2>
                <?php if (empty($products)): ?>
                    <div class="bg-gray-50 border-2 border-dashed border-gray-200 rounded-2xl p-8 text-center">
                        <p class="text-gray-500">No hay productos.</p>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <?php foreach ($products as $p): ?>
                            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex flex-col group hover:shadow-md transition-all">
                                <div class="p-6 flex-grow">
                                    <div class="flex justify-between items-start mb-2">
                                        <h3 class="text-lg font-bold text-gray-900"><?= htmlspecialchars($p['nombre']) ?></h3>
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800"><?= htmlspecialchars($p['category_name'] ?? 'General') ?></span>
                                    </div>
                                    <p class="text-sm text-gray-500 mb-4"><?= htmlspecialchars($p['descripcion']) ?></p>
                                    <div class="text-2xl font-extrabold text-secondary mt-auto"><?= number_format($p['precio'], 2) ?> €</div>
                                </div>
                                <div class="p-4 bg-gray-50 border-t border-gray-100">
                                    <form action="<?= BASE_URL ?>/cart/add" method="POST" class="flex gap-3">
                                        <?= \App\Core\Session::csrfField() ?>
                                        <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                        <input type="number" name="cantidad" value="1" min="1" max="<?= $p['stock'] ?>" class="block w-24 rounded-xl border-gray-300 py-2.5 px-3 text-gray-900 border text-sm">
                                        <button type="submit" class="w-full justify-center px-4 py-2 border border-transparent rounded-xl text-sm font-bold text-white bg-primary hover:bg-orange-600 transition-colors">Añadir</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>
        <div class="lg:w-1/3">
            <div class="sticky top-24 space-y-6">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6" id="contacto">
                    <h3 class="font-bold text-gray-900 mb-4 pb-2 border-b border-gray-50">Información</h3>
                    <ul class="space-y-4 text-sm text-gray-600">
                        <li class="flex items-start"><i class="fa-solid fa-envelope mt-1 w-6 text-primary"></i> <a href="mailto:<?= htmlspecialchars($business->email) ?>" class="hover:text-primary"><?= htmlspecialchars($business->email) ?></a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once ROOT_DIR . '/resources/views/layout/footer.php'; ?>
