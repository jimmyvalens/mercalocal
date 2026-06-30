<?php require_once __DIR__ . '/main_header.php'; ?>

<!-- ═══ Hero Section ═══ -->
<div class="relative bg-secondary overflow-hidden">

    <!-- Subtle grid pattern (GitHub-style) -->
    <div class="absolute inset-0 opacity-5" style="background-image: linear-gradient(#fff 1px, transparent 1px), linear-gradient(90deg, #fff 1px, transparent 1px); background-size: 40px 40px;"></div>

    <div class="max-w-7xl mx-auto relative z-10">
        <div class="relative pb-8 sm:pb-16 md:pb-20 lg:max-w-2xl lg:w-full lg:pb-28 xl:pb-32 pt-16 px-4 sm:px-6 lg:px-8">
            <main class="mt-8 mx-auto max-w-7xl sm:mt-12 md:mt-16 lg:mt-20 xl:mt-24">
                <div class="sm:text-center lg:text-left">
                    <!-- Eyebrow badge -->
                    <div class="inline-flex items-center gap-2 bg-white/10 border border-white/20 text-gray-300 text-xs font-semibold px-3 py-1 rounded-full mb-6 sm:mx-auto lg:mx-0">
                        <span class="w-2 h-2 bg-accent rounded-full animate-pulse"></span>
                        Comercio local online · Villafranca de los Barros
                    </div>

                    <h1 class="text-4xl tracking-tight font-black text-white sm:text-5xl md:text-6xl">
                        <span class="block mb-1">Descubre y apoya a</span>
                        <span class="block text-transparent bg-clip-text bg-gradient-to-r from-primary to-accent">tu comercio local</span>
                    </h1>
                    <p class="mt-4 text-base text-gray-400 sm:mt-5 sm:text-lg sm:max-w-xl sm:mx-auto md:mt-5 md:text-xl lg:mx-0 font-normal leading-relaxed">
                        Encuentra los mejores productos y comercios cerca de ti. Haz tu pedido y elige entre recibirlo en tu domicilio o recogerlo en el local sin esperas.
                    </p>
                    <div class="mt-8 sm:mt-10 flex flex-col sm:flex-row gap-3 sm:justify-center lg:justify-start">
                        <a href="/businesses"
                            class="btn-primary inline-flex items-center justify-center gap-2 px-8 py-3 text-base md:py-3.5 md:text-lg md:px-10">
                            <i class="fa-solid fa-store"></i> Explorar Comercios
                        </a>
                        <?php if (!\App\Core\Session::get('user_id')): ?>
                            <a href="/register"
                                class="inline-flex items-center justify-center gap-2 px-8 py-3 border border-white/30 text-white hover:bg-white/10 font-bold rounded-lg text-base md:py-3.5 md:text-lg md:px-10 transition-colors">
                                Únete ahora
                            </a>
                        <?php
                        endif; ?>
                    </div>

                    <!-- Stats strip -->
                    <div class="mt-10 flex flex-wrap gap-6 lg:gap-8">
                        <div class="text-center lg:text-left">
                            <p class="text-2xl font-black text-white">+200</p>
                            <p class="text-xs text-gray-500 mt-0.5 uppercase tracking-wide">Comercios</p>
                        </div>
                        <div class="text-center lg:text-left">
                            <p class="text-2xl font-black text-white">5</p>
                            <p class="text-xs text-gray-500 mt-0.5 uppercase tracking-wide">Categorías</p>
                        </div>
                        <div class="text-center lg:text-left">
                            <p class="text-2xl font-black text-white">100%</p>
                            <p class="text-xs text-gray-500 mt-0.5 uppercase tracking-wide">Local</p>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Right decorative panel -->
    <div class="lg:absolute lg:inset-y-0 lg:right-0 lg:w-1/2">
        <div class="h-56 w-full sm:h-72 md:h-96 lg:w-full lg:h-full bg-slate-800/80 flex items-center justify-center relative overflow-hidden">
            <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(circle at 20px 20px, #f97316 2px, transparent 0); background-size: 40px 40px;"></div>
            <i class="fa-solid fa-shop text-8xl text-primary opacity-60 relative z-10"></i>
        </div>
    </div>
</div>

<!-- ═══ Category Quick Links ═══ -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14">

    <!-- Section header -->
    <div class="flex items-center justify-between mb-8">
        <div>
            <h2 class="text-2xl font-bold text-secondary">¿Qué buscas hoy?</h2>
            <p class="text-gray-500 text-sm mt-1">Navega por categorías y descubre comercios cerca de ti</p>
        </div>
        <a href="/businesses" class="hidden sm:inline-flex items-center gap-1 text-sm text-gray-500 hover:text-primary transition-colors font-medium">
            Ver todos <i class="fa-solid fa-arrow-right text-xs"></i>
        </a>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-5 gap-3">

        <a href="/businesses?categoria=1"
            class="gh-card group p-5 text-center hover:border-primary transition-all flex flex-col items-center gap-3">
            <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-lg flex items-center justify-center group-hover:bg-primary group-hover:text-white transition-colors">
                <i class="fa-solid fa-basket-shopping text-xl"></i>
            </div>
            <span class="font-semibold text-gray-700 text-xs md:text-sm group-hover:text-primary transition-colors">Alimentación y Compra Diaria</span>
        </a>

        <a href="/businesses?categoria=2"
            class="gh-card group p-5 text-center hover:border-primary transition-all flex flex-col items-center gap-3">
            <div class="w-12 h-12 bg-orange-50 text-orange-600 rounded-lg flex items-center justify-center group-hover:bg-primary group-hover:text-white transition-colors">
                <i class="fa-solid fa-utensils text-xl"></i>
            </div>
            <span class="font-semibold text-gray-700 text-xs md:text-sm group-hover:text-primary transition-colors">Restauración y Comida Prepareda</span>
        </a>

        <a href="/businesses?categoria=3"
            class="gh-card group p-5 text-center hover:border-primary transition-all flex flex-col items-center gap-3">
            <div class="w-12 h-12 bg-pink-50 text-pink-600 rounded-lg flex items-center justify-center group-hover:bg-primary group-hover:text-white transition-colors">
                <i class="fa-solid fa-shirt text-xl"></i>
            </div>
            <span class="font-semibold text-gray-700 text-xs md:text-sm group-hover:text-primary transition-colors">Moda, Calzado y Complementos</span>
        </a>

        <a href="/businesses?categoria=4"
            class="gh-card group p-5 text-center hover:border-primary transition-all flex flex-col items-center gap-3">
            <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-lg flex items-center justify-center group-hover:bg-primary group-hover:text-white transition-colors">
                <i class="fa-solid fa-gift text-xl"></i>
            </div>
            <span class="font-semibold text-gray-700 text-xs md:text-sm group-hover:text-primary transition-colors">Hogar, Ocio y Regalos</span>
        </a>

        <a href="/businesses?categoria=5"
            class="gh-card group p-5 text-center hover:border-primary transition-all flex flex-col items-center gap-3">
            <div class="w-12 h-12 bg-teal-50 text-teal-600 rounded-lg flex items-center justify-center group-hover:bg-primary group-hover:text-white transition-colors">
                <i class="fa-solid fa-heart-pulse text-xl"></i>
            </div>
            <span class="font-semibold text-gray-700 text-xs md:text-sm group-hover:text-primary transition-colors">Cuidado Personal, Salud y Bienestar</span>
        </a>

    </div>
</div>

<!-- ═══ CTA Banner ═══ -->
<?php if (!\App\Core\Session::get('user_id')): ?>
    <div class="border-y border-gray-200 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 flex flex-col md:flex-row items-center justify-between gap-6">
            <div>
                <h3 class="text-xl font-bold text-secondary">¿Tienes un comercio local?</h3>
                <p class="text-gray-500 text-sm mt-1">Regístrate gratis y empieza a vender tus productos online hoy mismo.</p>
            </div>
            <a href="/register?rol=BUSINESS" class="btn-primary shrink-0 px-7 py-2.5">
                <i class="fa-solid fa-store mr-2"></i> Añadir mi comercio
            </a>
        </div>
    </div>
<?php
endif; ?>

<?php require_once __DIR__ . '/layout/footer.php'; ?>