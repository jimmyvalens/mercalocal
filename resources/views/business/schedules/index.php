<?php require_once ROOT_DIR . '/resources/views/main_header.php'; ?>
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

    <div class="flex items-center justify-between mb-8">
        <div>
            <a href="<?= BASE_URL ?>/business/dashboard" class="text-sm text-gray-500 hover:text-primary font-medium flex items-center gap-1 mb-2">
                <i class="fa-solid fa-arrow-left text-xs"></i> Volver al panel
            </a>
            <h1 class="text-3xl font-bold text-secondary flex items-center gap-3">
                <i class="fa-solid fa-clock text-primary"></i> Horarios
            </h1>
        </div>
    </div>

    <!-- Formulario añadir horario -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-8">
        <h2 class="font-bold text-gray-900 mb-4 pb-3 border-b border-gray-100">
            <i class="fa-solid fa-plus-circle text-primary mr-2"></i> Añadir horario
        </h2>
        <form action="<?= BASE_URL ?>/business/dashboard/schedules/store" method="POST"
            class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
            <?= \App\Core\Session::csrfField() ?>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Día</label>
                <select name="dia_semana"
                    class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-xl bg-gray-50 text-gray-900 focus:outline-none focus:ring-primary">
                    <?php for ($i = 0; $i < 7; $i++): ?>
                        <option value="<?= $i ?>" <?= (isset($_POST['dia_semana']) && $_POST['dia_semana'] == $i) ? 'selected' : '' ?>>
                            <?= ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'][$i] ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Apertura</label>
                <input type="time" name="hora_apertura"
                    value="<?= htmlspecialchars($_POST['hora_apertura'] ?? '09:00') ?>"
                    class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-xl bg-gray-50 text-gray-900 focus:outline-none focus:ring-primary" />
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Cierre</label>
                <input type="time" name="hora_cierre"
                    value="<?= htmlspecialchars($_POST['hora_cierre'] ?? '18:00') ?>"
                    class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-xl bg-gray-50 text-gray-900 focus:outline-none focus:ring-primary" />
            </div>
            <div>
                <button type="submit" class="w-full btn-primary py-3 flex items-center justify-center gap-2">
                    <i class="fa-solid fa-plus"></i> Agregar
                </button>
            </div>
        </form>
    </div>

    <!-- Lista de horarios -->
    <?php if (empty($schedules)): ?>
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-10 text-center">
            <i class="fa-solid fa-clock text-4xl text-gray-300 mb-3"></i>
            <p class="text-gray-500 font-medium">No hay horarios definidos todavía.</p>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <table class="w-full table-auto">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100">
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Día</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Apertura</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Cierre</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($schedules as $sch): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 font-medium text-gray-900">
                                <?= ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'][$sch->dia_semana] ?>
                            </td>
                            <td class="px-6 py-4 text-gray-600"><?= htmlspecialchars($sch->hora_apertura) ?></td>
                            <td class="px-6 py-4 text-gray-600"><?= htmlspecialchars($sch->hora_cierre) ?></td>
                            <td class="px-6 py-4 text-right">
                                <form action="<?= BASE_URL ?>/business/dashboard/schedules/<?= $sch->id ?>/delete"
                                    method="POST" onsubmit="return confirm('¿Eliminar este horario?');">
                                    <?= \App\Core\Session::csrfField() ?>
                                    <button type="submit"
                                        class="py-1.5 px-3 bg-white border border-red-200 text-red-600 font-bold text-xs rounded-lg hover:bg-red-50 transition-colors">
                                        <i class="fa-solid fa-trash mr-1"></i> Eliminar
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php require_once ROOT_DIR . '/resources/views/layout/footer_dashboard.php'; ?>