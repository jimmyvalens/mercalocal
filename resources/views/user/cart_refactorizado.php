<?php

use App\Core\Session;
use App\Models\User;

// 1. CALCULO SEGURO DEL TOTAL (Evita el error de variable indefinida)
$total = 0;
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $total += $item['precio'] * $item['cantidad'];
    }
}

// 2. DATOS DEL CLIENTE DINAMICOS
$direccionEnvio = 'No especificada en el perfil';
$telefonoContacto = 'No especificado';

if (Session::get('user_id')) {
    $currentUser = User::findById(Session::get('user_id'));
    if ($currentUser) {
        $direccionEnvio = $currentUser->direccion ?? 'No especificada en el perfil';
        $telefonoContacto = $currentUser->telefono ?? 'No especificado';
    }
}

require_once ROOT_DIR . '/resources/views/main_header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 flex flex-col md:flex-row gap-8">
    <div class="mx-auto max-w-[1050px] px-5">
        <!-- TOP NAV: SEGUIR COMPRANDO & TU CESTA -->
        <div class="mb-10 flex items-center justify-between gap-5 max-md:mb-8 max-md:flex-col max-md:items-start">
            <div class="flex flex-col gap-2">
                <div class="flex flex-wrap gap-2 text-[11px] font-bold uppercase tracking-[1.5px] text-slate-400">
                    <a href="<?= BASE_URL ?>/" class="text-slate-400 no-underline transition-colors hover:text-primary">Inicio</a>
                    <span>/</span>
                    <span class="text-secondary">Tu Cesta</span>
                </div>
                <h1 class="m-0 text-3xl font-black tracking-normal text-secondary">Tu Cesta</h1>
            </div>

            <a href="<?= BASE_URL ?>/businesses" class="flex w-fit items-center justify-center gap-2 rounded-xl border-2 border-accent bg-white px-5 py-3 text-[13px] font-extrabold text-accent no-underline transition-colors hover:bg-accent hover:text-white">
                <i class="fa-solid fa-arrow-left"></i> Seguir comprando
            </a>
        </div>

        <?php if (empty($_SESSION['cart'])): ?>
            <div class="rounded-[2rem] border border-border-custom bg-white px-5 py-20 text-center">
                <i class="fa-solid fa-basket-shopping mb-8 block text-6xl text-border-custom"></i>
                <h2 class="mb-2.5 text-2xl font-extrabold text-secondary">¡Vaya! Tu cesta est&aacute; vac&iacute;a</h2>
                <p class="mb-10 font-medium text-text-muted">Parece que a&uacute;n no has a&ntilde;adido ning&uacute;n producto local.</p>
                <a href="<?= BASE_URL ?>/businesses" class="btn-primary px-10 py-4 text-xs uppercase tracking-[1px]">
                    Explorar comercios
                </a>
            </div>
        <?php else: ?>

            <!-- VACIAR CESTA -->
            <div class="mb-5 flex justify-end pr-2.5">
                <form action="<?= BASE_URL ?>/cart/clear" method="POST" onsubmit="return confirm('¿Vaciar toda la cesta?')">
                    <?= \App\Core\Session::csrfField() ?>
                    <button type="submit" class="flex cursor-pointer items-center gap-2 border-0 bg-transparent text-[11px] font-extrabold uppercase tracking-[1px] text-slate-400 transition-colors hover:text-red-500">
                        <i class="fa-solid fa-trash-can"></i> Vaciar mi cesta
                    </button>
                </form>
            </div>

            <div class="flex items-start gap-10 max-lg:flex-col">

                <!-- COLUMNA PRODUCTOS -->
                <div class="basis-[62%] max-lg:w-full max-lg:basis-full">
                    <?php foreach ($_SESSION['cart'] as $id => $item): ?>
                        <div class="relative mb-6 flex gap-6 rounded-3xl border border-border-custom bg-white p-8 shadow-sm max-md:flex-col max-md:gap-4 max-md:p-5">

                            <!-- BOTON ELIMINAR (X) -->
                            <form action="<?= BASE_URL ?>/cart/remove" method="POST" class="absolute right-6 top-6">
                                <?= \App\Core\Session::csrfField() ?>
                                <input type="hidden" name="product_id" value="<?= $id ?>">
                                <button type="submit" class="flex size-8 cursor-pointer items-center justify-center rounded-full border border-border-custom bg-white text-slate-400 shadow-sm transition-colors hover:border-red-500 hover:text-red-500">
                                    <i class="fa-solid fa-xmark text-base"></i>
                                </button>
                            </form>

                            <div class="flex size-[120px] shrink-0 items-center justify-center rounded-2xl border border-border-custom bg-background max-md:h-[140px] max-md:w-full">
                                <i class="fa-solid fa-box-open text-4xl text-border-custom"></i>
                            </div>

                            <div class="flex grow flex-col justify-between">
                                <div>
                                    <h3 class="m-0 mb-3 text-lg font-black leading-tight text-secondary max-md:pr-10">
                                        <?= htmlspecialchars($item['nombre'], ENT_QUOTES, 'UTF-8') ?>
                                    </h3>
                                    <div class="flex flex-col gap-2">
                                        <div class="flex items-center">
                                            <i class="fa-solid fa-circle-check mr-3 text-sm text-accent"></i>
                                            <span class="text-[11px] font-bold text-text-muted">Recogida en tienda gratuita</span>
                                        </div>
                                        <div class="flex items-center">
                                            <i class="fa-solid fa-truck-fast mr-3 text-sm text-accent"></i>
                                            <span class="text-[11px] font-bold text-text-muted">Env&iacute;o local disponible</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-5 flex items-center justify-between border-t border-slate-100 pt-5 max-md:flex-col max-md:items-start max-md:gap-4">
                                    <div class="flex items-center gap-4 max-md:w-full max-md:flex-wrap max-md:gap-2.5">
                                        <span class="text-[11px] font-extrabold uppercase text-slate-400">Cantidad</span>

                                        <div class="flex items-center rounded-full border border-border-custom bg-slate-100 p-1">
                                            <form action="<?= BASE_URL ?>/cart/update" method="POST" class="m-0">
                                                <?= \App\Core\Session::csrfField() ?>
                                                <input type="hidden" name="product_id" value="<?= $id ?>">
                                                <input type="hidden" name="accion" value="restar">
                                                <button type="submit" class="flex size-7 cursor-pointer items-center justify-center rounded-full border-0 bg-white font-black text-accent transition-colors hover:bg-accent hover:text-white">&minus;</button>
                                            </form>
                                            <span class="w-8 text-center text-[13px] font-extrabold text-secondary"><?= $item['cantidad'] ?></span>
                                            <form action="<?= BASE_URL ?>/cart/update" method="POST" class="m-0">
                                                <?= \App\Core\Session::csrfField() ?>
                                                <input type="hidden" name="product_id" value="<?= $id ?>">
                                                <input type="hidden" name="accion" value="sumar">
                                                <button type="submit" class="flex size-7 cursor-pointer items-center justify-center rounded-full border-0 bg-white font-black text-accent transition-colors hover:bg-accent hover:text-white">&plus;</button>
                                            </form>
                                        </div>

                                        <span class="whitespace-nowrap rounded-lg bg-slate-100 px-2 py-1 text-xs font-bold text-text-muted">&times; <?= number_format($item['precio'], 2) ?> &euro;</span>
                                    </div>

                                    <div class="text-right max-md:flex max-md:w-full max-md:items-baseline max-md:justify-between max-md:border-t max-md:border-dashed max-md:border-border-custom max-md:pt-2.5 max-md:before:text-[11px] max-md:before:font-extrabold max-md:before:uppercase max-md:before:text-slate-400 max-md:before:content-['Subtotal:']">
                                        <span class="whitespace-nowrap text-xl font-black tracking-normal text-secondary"><?= number_format($item['precio'] * $item['cantidad'], 2) ?> &euro;</span>
                                    </div>
                                </div>

                                <!-- VOLVER AL COMERCIO -->
                                <div class="mt-4 border-t border-dashed border-border-custom pt-4">
                                    <a href="<?= BASE_URL ?>/business/<?= $item['business_id'] ?>" class="inline-flex items-center gap-1.5 text-[10px] font-extrabold uppercase tracking-[0.5px] text-accent no-underline transition-colors hover:text-primary">
                                        <i class="fa-solid fa-shop"></i> Volver al comercio
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- COLUMNA RESUMEN -->
                <div class="basis-[34%] max-lg:mt-5 max-lg:w-full max-lg:basis-full">
                    <div class="sticky top-24 rounded-[2rem] border border-border-custom bg-white p-9 shadow-xl shadow-slate-200/60 max-lg:static">
                        <h2 class="mb-6 border-b border-slate-100 pb-4 text-xl font-black text-secondary">Resumen de pedido</h2>

                        <!-- OPCIONES ENTREGA -->
                        <div class="mb-8 flex flex-col gap-3">
                            <label class="flex cursor-pointer items-center gap-4 rounded-2xl border border-accent bg-green-50 p-5 transition-colors hover:border-accent hover:bg-green-50">
                                <input type="radio" name="d" checked class="size-[18px] accent-accent">
                                <div class="flex flex-col">
                                    <span class="text-[13px] font-extrabold text-secondary">Env&iacute;o a Domicilio</span>
                                    <span class="text-[10px] font-extrabold uppercase text-accent">Ma&ntilde;ana mismo</span>
                                </div>
                            </label>
                            <label class="flex cursor-pointer items-center gap-4 rounded-2xl border border-border-custom bg-white p-5 transition-colors hover:border-accent hover:bg-green-50">
                                <input type="radio" name="d" class="size-[18px] accent-accent">
                                <div class="flex flex-col">
                                    <span class="text-[13px] font-extrabold text-secondary">Recogida en Tienda</span>
                                    <span class="text-[10px] font-extrabold uppercase text-slate-400">Gratis</span>
                                </div>
                            </label>
                        </div>

                        <!-- TOTALES -->
                        <div class="mb-9">
                            <div class="mb-2.5 flex justify-between">
                                <span class="text-[11px] font-bold uppercase tracking-[0.5px] text-slate-400">Subtotal</span>
                                <span class="whitespace-nowrap text-[13px] font-extrabold text-secondary"><?= number_format($total, 2) ?> &euro;</span>
                            </div>
                            <div class="mb-5 flex justify-between">
                                <span class="text-[11px] font-bold uppercase tracking-[0.5px] text-slate-400">Gesti&oacute;n env&iacute;o</span>
                                <span class="text-[13px] font-extrabold text-accent">Gratis</span>
                            </div>

                            <div class="mb-5 h-px bg-border-custom"></div>

                            <div class="flex items-baseline justify-between">
                                <span class="text-sm font-black uppercase text-secondary">Total</span>
                                <span class="whitespace-nowrap text-3xl font-black leading-none tracking-normal text-secondary"><?= number_format($total, 2) ?> &euro;</span>
                            </div>
                        </div>

                        <!-- BOTON PAGO -->
                        <form action="<?= BASE_URL ?>/checkout" method="POST">
                            <?= \App\Core\Session::csrfField() ?>
                            <button type="submit" class="flex w-full cursor-pointer items-center justify-center gap-2.5 rounded-[14px] border-0 bg-accent p-4 text-[15px] font-black uppercase tracking-[0.5px] text-white shadow-md shadow-green-500/20 transition-colors hover:bg-green-600">
                                Tramitar Pedido <i class="fa-solid fa-arrow-right-long"></i>
                            </button>
                        </form>

                        <div class="mt-6 text-center">
                            <p class="flex items-center justify-center gap-2 text-[10px] font-extrabold uppercase tracking-[1.5px] text-slate-300">
                                <i class="fa-solid fa-shield-check text-[13px] text-accent"></i> Seguridad Certificada
                            </p>
                        </div>
                    </div>
                </div>

            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once ROOT_DIR . '/resources/views/layout/footer.php'; ?>