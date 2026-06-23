<?php

/**
 * @var \App\Models\Business $business Indica al editor que esta variable es el objeto Comercio
 * @var array $products                Indica al editor que esto es un array de productos
 * @var array $schedules               Indica al editor que esto es un array de horarios
 */
?>
<?php require_once ROOT_DIR . '/resources/views/main_header.php'; ?>

<div class="relative bg-secondary h-80 overflow-hidden"
    <?php if (!empty($business->hero_path)): ?>
    style="background-image: url('<?= BASE_URL . '/' . htmlspecialchars($business->hero_path) ?>'); background-size: cover; background-position: center;"
    <?php endif; ?>>

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

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 gap-x-12">

    <div class="flex flex-row items-center justify-between w-full mt-12 mb-12 bg-white border border-gray-100 shadow-sm rounded-2xl p-0">

        <div class="flex items-center gap-4 ml-6 my-6 border-r border-gray-100 pr-6">
            <div class="w-12 h-12 rounded-xl bg-orange-50 flex items-center justify-center text-primary shrink-0">
                <i class="fa-solid fa-phone text-lg"></i>
            </div>
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Teléfono</p>
                <a href="tel:<?= htmlspecialchars($business->telefono) ?>" class="text-sm font-bold text-gray-800"><?= htmlspecialchars($business->telefono) ?></a>
            </div>
        </div>

        <div class="flex items-center gap-4 my-6 border-r border-gray-100 pr-6">
            <div class="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600 shrink-0">
                <i class="fa-solid fa-envelope text-lg"></i>
            </div>
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Correo</p>
                <a href="mailto:<?= htmlspecialchars($business->email) ?>" class="text-sm font-bold text-gray-800"><?= htmlspecialchars($business->email) ?></a>
            </div>
        </div>

        <div class="flex items-center gap-4 mr-6 my-6">
            <div class="w-12 h-12 rounded-xl bg-green-50 flex items-center justify-center text-green-600 shrink-0">
                <i class="fa-solid fa-globe text-lg"></i>
            </div>
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Sitio Web</p>
                <a href="<?= htmlspecialchars($business->web) ?>" class="text-sm font-bold text-primary"><?= htmlspecialchars(preg_replace('/^https?:\/\/(www\.)?/', '', $business->web)) ?></a>
            </div>
        </div>

    </div>

    <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm mt-16 mb-16">
        <h3 class="text-lg font-bold text-secondary mb-6 flex items-center gap-2">
            <i class="fa-solid fa-clock text-primary"></i> Horario de Atención al Público
        </h3>

        <?php if (empty($schedules)): ?>
            <div class="p-4 bg-gray-50 rounded-xl border border-gray-100 text-gray-500 text-sm italic">
                <i class="fa-solid fa-circle-info mr-1 text-gray-400"></i> Este comercio no ha especificado sus horarios de apertura todavía.
            </div>
        <?php else: ?>
            <?php
            $diasSemana = [
                1 => 'Lunes',
                2 => 'Martes',
                3 => 'Miércoles',
                4 => 'Jueves',
                5 => 'Viernes',
                6 => 'Sábado',
                7 => 'Domingo'
            ];

            $horariosAgrupados = [];
            foreach ($schedules as $s) {
                $diaNum = (int)($s['dia_semana'] ?? 0);
                $horariosAgrupados[$diaNum][] = $s;
            }
            ksort($horariosAgrupados);
            ?>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-7 gap-4">
                <?php foreach ($horariosAgrupados as $diaNum => $franjas): ?>
                    <div class="bg-gray-50 p-4 rounded-xl border border-gray-100 text-center flex flex-col min-h-[110px] shadow-sm hover:border-gray-200 transition-colors">
                        <span class="text-xs font-bold text-gray-400 uppercase tracking-wide border-b border-gray-200/60 pb-1.5 mb-2 block">
                            <?= $diasSemana[$diaNum] ?? 'Día ' . $diaNum ?>
                        </span>
                        <div class="flex flex-col gap-1.5 justify-center flex-grow">
                            <?php foreach ($franjas as $f): ?>
                                <span class="text-xs font-extrabold text-gray-700 block">
                                    <?php if (!empty($f['hora_apertura']) && !empty($f['hora_cierre'])): ?>
                                        <?= date('H:i', strtotime($f['hora_apertura'])) ?> - <?= date('H:i', strtotime($f['hora_cierre'])) ?>
                                    <?php else: ?>
                                        <span class="text-red-400 font-semibold bg-red-50 px-1.5 py-0.5 rounded">Cerrado</span>
                                    <?php endif; ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <section id="productos" class="mt-16">
        <h2 class="text-2xl font-bold text-secondary mb-6 flex items-center gap-2">
            <i class="fa-solid fa-box-open text-primary"></i> Productos disponibles (<?= count($products) ?>)
        </h2>

        <?php if (empty($products)): ?>
            <div class="text-center py-12 bg-white rounded-2xl border border-gray-100 shadow-sm text-gray-500">
                <p><i class="fa-solid fa-box-open text-2xl mb-2 text-gray-300 block"></i> No hay productos publicados actualmente.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($products as $p): ?>
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-all flex flex-col group">

                        <div class="w-full h-48 overflow-hidden bg-gray-100 relative flex items-center justify-center">
                            <?php if (!empty($p['imagen'])): ?>
                                <img src="<?= BASE_URL ?>/img/products/<?= htmlspecialchars($p['imagen']) ?>"
                                    alt="<?= htmlspecialchars($p['nombre']) ?>"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                    onerror="this.style.display='none'; document.getElementById('placeholder-<?= $p['id'] ?>').classList.remove('hidden');">
                            <?php endif; ?>

                            <div id="placeholder-<?= $p['id'] ?>" class="<?= !empty($p['imagen']) ? 'hidden' : '' ?> w-full h-full bg-gradient-to-br from-gray-50 to-gray-150 flex flex-col items-center justify-center gap-2 select-none">
                                <div class="w-12 h-12 rounded-full bg-gray-200/60 flex items-center justify-center text-gray-400 group-hover:bg-orange-50 group-hover:text-primary transition-colors duration-300">
                                    <i class="fa-solid fa-box text-xl"></i>
                                </div>
                                <span class="text-xs font-semibold text-gray-400 tracking-wide uppercase">Sin imagen</span>
                            </div>
                        </div>

                        <div class="p-5 flex flex-col flex-grow">
                            <div class="flex justify-between items-start mb-2 gap-2">
                                <h3 class="font-bold text-gray-900 text-lg truncate group-hover:text-primary transition-colors">
                                    <?= htmlspecialchars($p['nombre']) ?>
                                </h3>
                                <span class="bg-blue-50 text-blue-700 px-2.5 py-0.5 rounded-lg text-xs font-semibold border border-blue-100 shrink-0">
                                    <?= htmlspecialchars($p['category_name'] ?? 'General') ?>
                                </span>
                            </div>

                            <p class="text-sm text-gray-500 mb-4 line-clamp-2 flex-grow">
                                <?= htmlspecialchars($p['descripcion'] ?? 'Sin descripción disponible.') ?>
                            </p>

                            <div class="flex justify-between items-end mb-4 pt-2 border-t border-gray-50">
                                <span class="text-2xl font-extrabold text-secondary leading-none">
                                    <?= number_format($p['precio'], 2) ?> €
                                </span>
                                <span class="text-xs text-gray-400 font-medium bg-gray-50 px-2 py-1 rounded-md">
                                    <?= $p['stock'] > 0 ? 'Stock: ' . $p['stock'] . ' uds' : 'Agotado' ?>
                                </span>
                            </div>

                            <div class="pt-3 border-t border-gray-50">
                                <form action="<?= BASE_URL ?>/cart/add" method="POST" class="flex gap-2">
                                    <?= \App\Core\Session::csrfField() ?>
                                    <input type="hidden" name="product_id" value="<?= $p['id'] ?>">

                                    <input type="number" name="cantidad" value="1" min="1" max="<?= $p['stock'] ?>"
                                        class="block w-16 rounded-xl border-gray-200 py-2 text-center text-gray-900 border text-sm focus:border-primary focus:ring-primary focus:outline-none">

                                    <button type="submit"
                                        class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-xl text-sm font-bold text-white bg-primary hover:bg-orange-600 transition-colors shadow-sm gap-2">
                                        <i class="fa-solid fa-cart-shopping text-xs"></i> Añadir
                                    </button>
                                </form>
                            </div>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

</div>

<?php require_once ROOT_DIR . '/resources/views/layout/footer.php'; ?>