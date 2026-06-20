<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mercalocal</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- SweetAlert2 for nicer alerts -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="font-sans bg-background text-secondary min-h-screen flex flex-col">

    <?php
    // Leer el mensaje flash de un solo uso (devuelve ['type'=>..., 'message'=>...] o null)
    $flash     = \App\Core\Session::getFlash();
    $flashMsg  = $flash ? htmlspecialchars($flash['message'] ?? '', ENT_QUOTES, 'UTF-8') : '';
    $flashType = $flash ? htmlspecialchars($flash['type']    ?? '', ENT_QUOTES, 'UTF-8') : '';
    $userId     = \App\Core\Session::get('user_id');
    $userRole   = \App\Core\Session::get('user_role');
    $userName   = \App\Core\Session::get('user_name');
    $cartCount  = count($_SESSION['cart'] ?? []);
    $logoFile   = file_exists(ROOT_DIR . '/public/img/mercalocal-logo.png')
        ? BASE_URL . '/img/mercalocal-logo.png'
        : BASE_URL . '/img/mercalocal-logo.svg';
    ?>

    <!-- Navbar -->
    <nav style="background-color:#1e293b; border-bottom:1px solid #334155;" class="sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">

                <!-- Logo -->
                <a href="<?= BASE_URL ?>/" class="flex items-center gap-2">
                    <img src="<?= $logoFile ?>" alt="Mercalocal" class="h-8 w-auto"><span class="font-extrabold text-xl text-white">Merca<span class="text-primary">local</span></span>
                </a>

                <!-- Desktop Links (Center) -->
                <div class="hidden md:flex items-center space-x-8">
                    <a href="<?= BASE_URL ?>/" style="color:#cbd5e1" class="hover:text-white hover:bg-white/10 font-medium text-sm px-3 py-1.5 rounded-md transition-colors">Inicio</a>
                    <a href="<?= BASE_URL ?>/businesses" style="color:#cbd5e1" class="hover:text-white hover:bg-white/10 font-medium text-sm px-3 py-1.5 rounded-md transition-colors">Comercios</a>
                </div>

                <!-- Right Actions -->
                <div class="flex items-center gap-4">

                    <!-- Cart -->
                    <?php if ($userRole !== 'BUSINESS'): ?>
                        <!-- Cart -->
                        <a href="<?= BASE_URL ?>/cart" class="relative p-2 text-gray-500 hover:text-primary transition-colors hover:bg-green-50 rounded-full">
                            <i class="fa-solid fa-cart-shopping text-xl"></i>
                            <?php if ($cartCount > 0): ?>
                                <span class="absolute top-0 right-0 -mt-1 -mr-1 flex items-center justify-center w-5 h-5 bg-primary text-white text-[10px] font-bold rounded-full border-2 border-white">
                                    <?= $cartCount ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    <?php endif; ?>

                    <!-- Auth Buttons (Desktop) -->
                    <div class="hidden md:flex items-center gap-3">
                        <?php if ($userId): ?>
                            <?php if ($userRole === 'BUSINESS'): ?>
                                <a href="<?= BASE_URL ?>/business/dashboard" class="text-gray-600 hover:text-primary font-medium text-sm">Mi Comercio</a>
                            <?php elseif ($userRole === 'ADMIN'): ?>
                                <a href="<?= BASE_URL ?>/admin/dashboard" class="text-gray-600 hover:text-primary font-medium text-sm">Admin</a>
                            <?php endif; ?>

                            <div class="flex items-center gap-2 pl-4 border-l border-gray-200">
                                <a href="<?= BASE_URL ?>/profile" class="flex items-center gap-2 bg-gray-50 hover:bg-gray-100 rounded-full py-1 px-3 border border-gray-200 transition-colors">
                                    <div class="flex items-center justify-center w-6 h-6 rounded-full bg-primary text-white font-bold text-xs uppercase">
                                        <?= substr($userName ?? 'U', 0, 1) ?>
                                    </div>
                                    <span class="text-sm font-medium text-gray-700 max-w-[100px] truncate"><?= htmlspecialchars($userName ?? '') ?></span>
                                </a>
                                <a href="<?= BASE_URL ?>/logout" class="text-red-500 hover:text-red-600 p-2" title="Salir">
                                    <i class="fa-solid fa-right-from-bracket"></i>
                                </a>
                            </div>
                        <?php else: ?>
                            <a href="<?= BASE_URL ?>/login" style="color:#cbd5e1; border:1px solid #475569" class="text-sm font-medium px-4 py-1.5 rounded-md transition-colors hover:bg-white/10">Entrar</a>
                            <a href="<?= BASE_URL ?>/register" class="bg-primary hover:bg-green-700 text-white font-bold py-2 px-5 rounded-full transition-colors shadow-sm">Registrarse</a>
                        <?php endif; ?>
                    </div>

                    <!-- Mobile menu button -->
                    <button type="button" id="mobile-menu-btn" class="md:hidden p-2 text-gray-500 hover:text-primary hover:bg-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-inset focus:ring-primary">
                        <span class="sr-only">Abrir menú principal</span>
                        <i class="fa-solid fa-bars text-xl" id="mobile-menu-icon"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu dropdown -->
        <div style="background-color:#1e293b; border-top:1px solid #334155;" class="md:hidden hidden absolute w-full shadow-xl" id="mobile-menu">
            <div class="px-4 pt-2 pb-4 space-y-1">
                <a href="<?= BASE_URL ?>/" class="block px-3 py-3 rounded-md text-base font-medium text-gray-500 hover:text-primary hover:bg-green-50">Inicio</a>
                <a href="<?= BASE_URL ?>/businesses" class="block px-3 py-3 rounded-md text-base font-medium text-gray-500 hover:text-primary hover:bg-green-50">Comercios</a>

                <div class="border-t border-gray-200 pt-4 pb-2 mt-2">
                    <?php if ($userId): ?>
                        <div class="flex items-center px-4 mb-3">
                            <div class="flex-shrink-0">
                                <div class="flex items-center justify-center w-10 h-10 rounded-full bg-primary text-white font-bold text-sm uppercase">
                                    <?= substr($userName ?? 'U', 0, 1) ?>
                                </div>
                            </div>
                            <div class="ml-3">
                                <div class="text-base font-medium text-gray-800"><?= htmlspecialchars($userName ?? '') ?></div>
                            </div>
                        </div>
                        <div class="mt-3 space-y-1">
                            <?php if ($userRole !== 'ADMIN'): ?>
                                <a href="<?= BASE_URL ?>/profile" class="block px-3 py-2 rounded-md text-base font-medium text-gray-500 hover:text-primary hover:bg-gray-50">Tu Perfil</a>
                            <?php endif; ?>

                            <?php if ($userRole !== 'ADMIN' && $userRole !== 'BUSINESS'): ?>
                                <a href="<?= BASE_URL ?>/orders" class="block px-3 py-2 rounded-md text-base font-medium text-gray-500 hover:text-primary hover:bg-gray-50">Tus Pedidos</a>
                            <?php endif; ?>

                            <?php if ($userRole === 'BUSINESS'): ?>
                                <a href="<?= BASE_URL ?>/business/dashboard" class="block px-3 py-2 rounded-md text-base font-medium text-primary hover:bg-green-50">Panel de Comercio</a>
                            <?php elseif ($userRole === 'ADMIN'): ?>
                                <a href="<?= BASE_URL ?>/admin/dashboard" class="block px-3 py-2 rounded-md text-base font-medium text-primary hover:bg-gray-50">Administración</a>
                            <?php endif; ?>

                            <a href="<?= BASE_URL ?>/logout" class="block px-3 py-2 rounded-md text-base font-medium text-red-500 hover:bg-red-50">Cerrar Sesión</a>
                        </div>
                    <?php else: ?>
                        <div class="flex flex-col gap-2 mt-4 px-4">
                            <a href="<?= BASE_URL ?>/login" class="w-full text-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-base font-medium text-gray-700 bg-white hover:bg-gray-50">Entrar</a>
                            <a href="<?= BASE_URL ?>/register" class="w-full text-center px-4 py-2 border border-transparent rounded-md shadow-sm text-base font-medium text-white bg-primary hover:bg-green-700">Registrarse</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <script>
        // Mobile menu toggle logic
        document.addEventListener("DOMContentLoaded", () => {
            const btn = document.getElementById('mobile-menu-btn');
            const menu = document.getElementById('mobile-menu');
            const icon = document.getElementById('mobile-menu-icon');

            if (btn && menu && icon) {
                btn.addEventListener('click', () => {
                    menu.classList.toggle('hidden');
                    if (menu.classList.contains('hidden')) {
                        icon.classList.remove('fa-xmark');
                        icon.classList.add('fa-bars');
                    } else {
                        icon.classList.remove('fa-bars');
                        icon.classList.add('fa-xmark');
                    }
                });
            }
        });

        // FUNCIÓN GLOBAL PARA ELEMENTOS EN DESARROLLO
        function mostrarEnDesarrollo(funcionalidad = "Esta funcionalidad") {
            Swal.fire({
                icon: 'info',
                title: 'Próxima implementación',
                text: `${funcionalidad} estará disponible en la Fase 2 del despliegue (Mejoras futuras).`,
                confirmButtonColor: '#22c55e', // Verde primary de Tailwind
                confirmButtonText: 'Entendido'
            });
        }
    </script>

    <?php if ($flash): ?>
        <script>
            // Flash alert via SweetAlert2
            const isCartAddition = <?= json_encode(strpos($flashMsg, 'carrito') !== false || strpos($flashMsg, '✅') !== false) ?>;
            Swal.fire({
                toast: isCartAddition,
                position: isCartAddition ? 'top-end' : 'center',
                icon: <?= json_encode($flashType === 'error' ? 'error' : ($flashType === 'success' ? 'success' : ($flashType === 'warning' ? 'warning' : 'info'))) ?>,
                title: isCartAddition ? '' : <?= json_encode(ucfirst($flashType ?: '')) ?>,
                // Decodificamos las entidades HTML antes de pasarlo a JSON
                text: <?= json_encode(html_entity_decode($flashMsg, ENT_QUOTES, 'UTF-8')) ?>,
                timer: isCartAddition ? 3000 : 4000,
                timerProgressBar: true,
                showConfirmButton: false,
                customClass: {
                    popup: isCartAddition ? 'mt-16' : ''
                }
            });
        </script>
    <?php endif; ?>

    <!-- Main container for views -->
    <main class="flex-grow flex flex-col">