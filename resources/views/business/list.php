<?php
// Inicializar variables si no están definidas (en caso de carga directa o errores)
$page = $page ?? 1;
$totalPages = $totalPages ?? 1;
$businesses = $businesses ?? [];
$categories = $categories ?? [];
require_once ROOT_DIR . '/resources/views/main_header.php';
?>
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-8 mt-4">
        <form action="/businesses" method="GET" class="flex flex-col md:flex-row gap-4">
            <div class="flex-grow">
                <input type="text" name="q" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" placeholder="Busca comercios de barrio..." class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-xl bg-gray-50 text-gray-900 focus:outline-none focus:ring-primary">
            </div>
            <div class="w-full md:w-64">
                <select name="categoria" class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-xl bg-gray-50 text-gray-900 focus:outline-none focus:ring-primary">
                    <option value="">Todas las categorías</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat->id ?>" <?= ($_GET['categoria'] ?? '') == $cat->id ? 'selected' : '' ?>><?= htmlspecialchars($cat->nombre) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="w-full md:w-auto px-8 py-3 bg-primary text-white font-bold rounded-xl hover:bg-orange-600 transition-colors shadow cursor-pointer">Buscar</button>
        </form>
    </div>

    <div class="mb-6 flex items-center justify-between">
        <h2 class="text-xl font-bold text-secondary">Resultados (<?= count($businesses) ?>)</h2>
    </div>

    <?php if (empty($businesses)): ?>
        <div class="bg-white rounded-2xl border border-gray-100 p-12 text-center text-gray-500 shadow-sm">
            <i class="fa-solid fa-store-slash text-6xl text-gray-300 mb-4"></i>
            <h3 class="text-xl font-medium text-gray-700">No se encontraron comercios</h3>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 w-full">
            <?php foreach ($businesses as $b): ?>
                <a href="/business/<?= $b->id ?>" class="group bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-all flex flex-col h-full w-full">

                    <div class="h-48 bg-gray-200 relative w-full overflow-hidden flex-shrink-0">
                        <?php if (!empty($b->logo_path)): ?>
                            <img src="<?= htmlspecialchars($b->logo_path) ?>" alt="<?= htmlspecialchars($b->nombre) ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <?php endif; ?>

                        <div style="background-color:#1e293b" class="absolute inset-0 <?= !empty($b->logo_path) ? 'hidden' : 'flex' ?> items-center justify-center overflow-hidden group-hover:scale-105 transition-transform duration-500">
                            <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(circle at 20px 20px, #ff5722 2.5px, transparent 0); background-size: 30px 30px;"></div>
                            <i class="fa-solid fa-shop text-6xl text-primary opacity-80 relative z-10"></i>
                        </div>
                    </div>

                    <div class="p-6 flex-grow flex flex-col justify-between">
                        <div>
                            <h3 class="text-xl font-bold text-gray-900 group-hover:text-primary transition-colors mb-2">
                                <?= htmlspecialchars($b->nombre) ?>
                            </h3>

                            <p class="text-sm text-gray-500 line-clamp-2 mb-4">
                                <?= htmlspecialchars($b->descripcion) ?>
                            </p>
                        </div>

                        <div class="mt-auto space-y-2">
                            <?php if (!empty($b->categorias)): ?>
                                <div class="inline-flex items-center text-xs font-bold text-primary bg-orange-50 px-2 py-1 rounded max-w-full break-words">
                                    <i class="fa-solid fa-tag mr-1 flex-shrink-0"></i>
                                    <span class="truncate"><?= htmlspecialchars($b->categorias) ?></span>
                                </div>
                            <?php endif; ?>

                            <div class="flex items-center text-sm text-gray-600">
                                <i class="fa-solid fa-phone w-5 text-gray-400 mr-2 flex-shrink-0"></i><?= htmlspecialchars($b->telefono) ?>
                            </div>
                        </div>
                    </div>
                </a> <?php endforeach; ?>
        </div>
</div>

<?php if ($totalPages > 1): ?>
    <div class="mt-8 flex justify-center">
        <nav class="flex items-center space-x-1">
            <?php
            $queryParams = $_GET;
            unset($queryParams['page']);
            $baseUrl = '/businesses';
            if (!empty($queryParams)) {
                $baseUrl .= '?' . http_build_query($queryParams) . '&';
            } else {
                $baseUrl .= '?';
            }
            ?>

            <?php if ($page > 1): ?>
                <a href="<?= $baseUrl ?>page=<?= $page - 1 ?>" class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-l-md hover:bg-gray-50">
                    <i class="fa-solid fa-chevron-left"></i>
                </a>
            <?php endif; ?>

            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <a href="<?= $baseUrl ?>page=<?= $i ?>" class="px-3 py-2 text-sm font-medium border <?= $i == $page ? 'text-primary bg-primary bg-opacity-10 border-primary' : 'text-gray-500 bg-white border-gray-300 hover:bg-gray-50' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
                <a href="<?= $baseUrl ?>page=<?= $page + 1 ?>" class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-r-md hover:bg-gray-50">
                    <i class="fa-solid fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </nav>
    </div>
<?php endif; ?>
<?php endif; ?>
</div>
<?php require_once ROOT_DIR . '/resources/views/layout/footer.php'; ?>