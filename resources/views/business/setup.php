<?php require_once ROOT_DIR . '/resources/views/main_header.php'; ?>

<div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 bg-[#f9fdfa]">
    <div class="max-w-2xl w-full space-y-8 bg-white p-10 rounded-2xl shadow-sm border border-gray-100">
        <div class="text-center">
            <!-- Provisional logo styling adapting the new brand colours -->
            <div class="mx-auto w-16 h-16 bg-[#00b050] rounded-xl flex items-center justify-center shadow-lg text-white font-bold text-3xl">
                <i class="fa-solid fa-store"></i>
            </div>
            <h2 class="mt-6 text-3xl font-extrabold text-gray-900">Configura tu Perfil Comercial</h2>
            <p class="mt-2 text-sm text-gray-600">
                ¡Bienvenido a Mercalocal! Completa los detalles de tu negocio para empezar a vender y recibir reservas.
            </p>
        </div>


        <form class="mt-8 space-y-6" action="<?= BASE_URL ?>/business/setup" method="POST">
            <div class="rounded-md shadow-sm space-y-5">

                <!-- Nombre -->
                <div>
                    <label for="nombre" class="block text-sm font-bold text-gray-700 mb-1">Nombre del Comercio <span class="text-red-500">*</span></label>
                    <input id="nombre" name="nombre" type="text" required class="appearance-none rounded-xl relative block w-full px-4 py-3 border border-gray-300 placeholder-gray-400 text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#00b050] focus:border-transparent bg-gray-50 transition-all" placeholder="Ej. Frutería Manolo">
                </div>

                <!-- Descripción -->
                <div>
                    <label for="descripcion" class="block text-sm font-bold text-gray-700 mb-1">Descripción <span class="text-red-500">*</span></label>
                    <textarea id="descripcion" name="descripcion" rows="4" required class="appearance-none rounded-xl relative block w-full px-4 py-3 border border-gray-300 placeholder-gray-400 text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#00b050] focus:border-transparent bg-gray-50 transition-all" placeholder="Describe los productos o servicios que ofreces..."></textarea>
                </div>

                <!-- Teléfono -->
                <div>
                    <label for="telefono" class="block text-sm font-bold text-gray-700 mb-1">Teléfono <span class="text-red-500">*</span></label>
                    <input id="telefono" name="telefono" type="tel" required class="appearance-none rounded-xl relative block w-full px-4 py-3 border border-gray-300 placeholder-gray-400 text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#00b050] focus:border-transparent bg-gray-50 transition-all" placeholder="Ej. 600 000 000">
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-bold text-gray-700 mb-1">Correo Electrónico <span class="text-red-500">*</span></label>
                    <input id="email" name="email" type="email" required class="appearance-none rounded-xl relative block w-full px-4 py-3 border border-gray-300 placeholder-gray-400 text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#00b050] focus:border-transparent bg-gray-50 transition-all" placeholder="contacto@tucomercio.com">
                </div>

                <!-- Sitio Web (Opcional) -->
                <div>
                    <label for="web" class="block text-sm font-bold text-gray-700 mb-1">Sitio Web <span class="text-gray-400 font-normal">(Opcional)</span></label>
                    <input id="web" name="web" type="url" class="appearance-none rounded-xl relative block w-full px-4 py-3 border border-gray-300 placeholder-gray-400 text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#00b050] focus:border-transparent bg-gray-50 transition-all" placeholder="https://www.tucomercio.com">
                </div>

            </div>

            <div class="pt-4">
                <button type="submit" class="w-full flex justify-center py-4 px-4 border border-transparent text-sm font-bold rounded-xl text-black bg-[#ffe295] hover:bg-[#ebd082] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#00b050] shadow-sm transition-all transform hover:-translate-y-0.5">
                    <i class="fa-solid fa-rocket mr-2 mt-0.5"></i> Lanzar mi Perfil de Comercio
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once ROOT_DIR . '/resources/views/layout/footer.php'; ?>