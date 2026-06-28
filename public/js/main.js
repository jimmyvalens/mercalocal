document.addEventListener('DOMContentLoaded', function () {


    // =========================================================================
    // CORRECCIÓN PUNTO 1: Formateo automático de la URL del Comercio
    // =========================================================================
    const inputWeb = document.getElementById('web'); // Tu input name="web" id="web"
    if (inputWeb) {
        inputWeb.addEventListener('blur', function () {
            let value = this.value.trim();
            if (value && !/^https?:\/\//i.test(value)) {
                this.value = 'http://' + value;
            }
        });
    }

    // Delegación de eventos: escuchamos el click en el body
    document.body.addEventListener('submit', (e) => {
        const form = e.target;

        // Identificamos si es un formulario de eliminación
        const isEliminar = form.matches('.form-eliminar');
        const isEliminarDetalle = form.matches('.form-eliminar-detalle');

        if (isEliminar || isEliminarDetalle) {
            e.preventDefault();

            // 🌟 TEXTOS DINÁMICOS: Leemos de los atributos data- del HTML, o usamos los de por defecto
            const title = form.getAttribute('data-titulo') ||
                (isEliminarDetalle ? '¿Confirmas la eliminación del comercio?' : '¿Estás seguro de eliminar el comercio?');

            const text = form.getAttribute('data-texto') ||
                (isEliminarDetalle ? 'Esta acción dará de baja el comercio y todo su catálogo asociado.' : 'Esta acción no se puede deshacer');

            Swal.fire({
                title: title,
                text: text,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444', // Red 500 de Tailwind
                cancelButtonColor: '#3b82f6',  // Blue 500 de Tailwind
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                background: '#ffffff',
                customClass: { popup: 'rounded-2xl border border-gray-100 shadow-xl' }
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit(); // Envía el formulario y recarga la página
                }
            });
        }
    });

    // ==========================================
    // 2. CONTROL DE UNIDAD DE MEDIDA Y STOCK
    // ==========================================
    const selectUnidad = document.getElementById('unidad_medida');
    const inputStock = document.getElementById('stock_input');

    if (selectUnidad && inputStock) {
        selectUnidad.addEventListener('change', function () {
            if (this.value === 'kg') {
                inputStock.setAttribute('step', '0.01');
                inputStock.setAttribute('placeholder', 'Ej: 15.50');
            } else {
                inputStock.setAttribute('step', '1');
                inputStock.setAttribute('placeholder', 'Ej: 10');

                // Si el usuario cambia a unidades teniendo decimales, redondeamos
                if (inputStock.value) {
                    inputStock.value = Math.round(parseFloat(inputStock.value));
                }
            }
        });
    }


    // =========================================================================
    // 1. Lógica de Previsualización de Imágenes locales (Hero y Logo)
    // =========================================================================
    const inputHero = document.getElementById('input-hero');
    const inputLogo = document.getElementById('input-logo');
    const prevHero = document.getElementById('preview-hero');
    const prevLogo = document.getElementById('preview-logo');
    const placeholderHero = document.getElementById('placeholder-hero');
    const placeholderLogo = document.getElementById('placeholder-logo');

    function gestionarArchivo(input, imgElement, placeholderElement, esHero) {
        if (input && imgElement) {
            input.addEventListener('change', function () {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        imgElement.src = e.target.result;
                        imgElement.classList.remove('hidden');
                        if (esHero) {
                            placeholderElement.className = "text-center p-4 absolute inset-0 bg-black bg-opacity-40 text-white flex flex-col items-center justify-center opacity-0 hover:opacity-100 transition-opacity rounded-xl z-10";
                        } else {
                            placeholderElement.className = "text-center p-1 absolute inset-0 bg-black bg-opacity-50 text-white flex flex-col items-center justify-center opacity-0 hover:opacity-100 transition-opacity rounded-full z-10";
                        }
                    }
                    reader.readAsDataURL(this.files[0]);
                }
            });
        }
    }

    // Al estar protegidos dentro de la función con 'if (input)', no romperán si no existen
    gestionarArchivo(inputHero, prevHero, placeholderHero, true);
    gestionarArchivo(inputLogo, prevLogo, placeholderLogo, false);

    // =========================================================================
    // 2. Lógica de Previsualización para el Dropzone de Productos
    // =========================================================================
    const inputProducto = document.getElementById('foto-producto');

    if (inputProducto) {
        inputProducto.addEventListener('change', function () {
            const box = document.getElementById('nombre-archivo-elegido');
            const preview = document.getElementById('producto-preview');
            const content = document.getElementById('dropzone-content');
            const avisoExisting = document.getElementById('aviso-imagen-existente');

            if (this.files && this.files[0]) {
                const file = this.files[0];

                // Controlamos la existencia de los elementos por seguridad antes de aplicar clases
                if (box) {
                    box.textContent = "✓ Cambiado: " + file.name;
                    box.classList.remove('hidden');
                }
                if (avisoExisting) {
                    avisoExisting.classList.add('hidden');
                }

                const reader = new FileReader();
                reader.onload = function (e) {
                    if (preview) {
                        preview.src = e.target.result;
                        preview.classList.remove('hidden');
                    }
                    if (content) {
                        content.classList.add('opacity-0');
                    }
                }
                reader.readAsDataURL(file);
            } else {
                if (box) box.classList.add('hidden');
            }
        });
    }


    // =========================================================================
    // 2. Lógica del Modal de Vista Previa
    // =========================================================================
    const btnPrevisualizar = document.getElementById('btn-previsualizar');
    const modal = document.getElementById('modal-vista-previa');
    const btnCerrar = document.getElementById('btn-cerrar-previa');

    if (btnPrevisualizar && modal) {
        btnPrevisualizar.addEventListener('click', function () {
            // Aseguramos que existan los elementos de origen antes de leer su valor
            const nombreEl = document.getElementById('nombre');
            const descEl = document.getElementById('descripcion');

            document.getElementById('modal-prev-nombre').textContent = (nombreEl ? nombreEl.value : '') || 'Comercio Nuevo';
            document.getElementById('modal-prev-desc').textContent = (descEl ? descEl.value : '') || 'Sin descripción comercial añadida todavía.';
            document.getElementById('modal-prev-hero').src = (prevHero ? prevHero.src : '') || 'https://images.unsplash.com/photo-1542838132-92c53300491e?w=500';
            document.getElementById('modal-prev-logo').src = (prevLogo ? prevLogo.src : '') || 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=150';

            modal.classList.remove('hidden');
        });

        if (btnCerrar) {
            btnCerrar.addEventListener('click', () => modal.classList.add('hidden'));
        }

        // Cerrar al hacer click en el fondo (fuera de la tarjeta del modal)
        modal.addEventListener('click', function (e) {
            // Si el elemento donde se hizo click es exactamente el contenedor del modal (el fondo oscuro)
            if (e.target === modal) {
                modal.classList.add('hidden');
            }
        });
    }


    // =========================================================================
    // 3. Lógica del Autocompletado Asíncrono (Usuarios) - ¡BLINDADO!
    // =========================================================================
    const searchInput = document.getElementById('user-search-input');
    const hiddenInput = document.getElementById('user_id_hidden');
    const resultsBox = document.getElementById('autocomplete-results');
    const searchIcon = document.getElementById('search-icon');
    let debounceTimer;

    // CRUCIAL: Solo se ejecuta si estamos en la vista que contiene el buscador de usuarios
    if (searchInput && hiddenInput && resultsBox) {

        searchInput.addEventListener('input', function () {
            const query = this.value.trim();

            if (query.length === 0) {
                hiddenInput.value = '';
                resultsBox.innerHTML = '';
                resultsBox.classList.add('hidden');
                return;
            }

            if (query.length < 2) return;

            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                if (searchIcon) searchIcon.textContent = '⏳';

                // Usamos la constante global que definiremos en el layout/footer
                fetch(`${window.BASE_URL}/api/users/search?q=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(users => {
                        if (searchIcon) searchIcon.textContent = '🔍';
                        resultsBox.innerHTML = '';

                        if (users.length === 0) {
                            resultsBox.innerHTML = '<div class="p-3 text-xs text-gray-500">No se encontraron usuarios</div>';
                            resultsBox.classList.remove('hidden');
                            return;
                        }

                        users.forEach(user => {
                            const div = document.createElement('div');
                            div.className = "px-4 py-2 hover:bg-gray-100 text-sm cursor-pointer border-b border-gray-50 last:border-none transition-colors";
                            div.innerHTML = `<span class="font-semibold text-gray-800">${user.nombre}</span> <span class="text-xs text-gray-400">(${user.email})</span>`;

                            div.addEventListener('click', function () {
                                searchInput.value = `${user.nombre} (${user.email})`;
                                hiddenInput.value = user.id;
                                resultsBox.classList.add('hidden');
                            });
                            resultsBox.appendChild(div);
                        });

                        resultsBox.classList.remove('hidden');
                    })
                    .catch(err => {
                        console.error('Error en autocomplete:', err);
                        if (searchIcon) searchIcon.textContent = '🔍';
                    });
            }, 300);
        });

        // Cerrar la caja si se hace click fuera del componente
        document.addEventListener('click', function (e) {
            if (e.target !== searchInput && e.target !== resultsBox) {
                resultsBox.classList.add('hidden');
            }
        });
    }

    // === PERSISTENCIA DE FORMULARIOS (Corrección para Archivos/Enctype) ===
    const forms = document.querySelectorAll('form[data-persist]');

    forms.forEach(form => {
        const formIdentifier = form.id || form.getAttribute('action') || 'generic';
        const storageKey = `mercalocal_draft_${window.location.pathname}_${formIdentifier}`;

        // 1. Restaurar datos al cargar la página
        const savedData = JSON.parse(localStorage.getItem(storageKey)) || {};
        Object.keys(savedData).forEach(name => {
            const input = form.elements[name];
            if (input) {
                // Evitamos tocar los inputs de tipo archivo para que JS no salte por los aires
                if (input.type === 'file') return;

                if (input.type === 'checkbox') {
                    input.checked = !!savedData[name];
                } else if (input.type === 'radio') {
                    if (input.value === savedData[name]) input.checked = true;
                } else {
                    input.value = savedData[name];
                }
            }
        });

        // 2. Guardar datos en cada cambio
        form.addEventListener('input', (e) => {
            // Ignoramos por completo los eventos que vengan de un input de archivo
            if (e.target.type === 'file') return;

            const formData = new FormData(form);
            const data = {};
            formData.forEach((value, key) => {
                // Filtramos contraseñas, tokens y objetos de tipo archivo (File)
                if (key !== 'csrf_token' && key !== 'password' && key !== 'password_confirm' && !(value instanceof File)) {
                    data[key] = value;
                }
            });
            localStorage.setItem(storageKey, JSON.stringify(data));
        });

        // 3. Limpiar al enviar
        form.addEventListener('submit', () => {
            localStorage.removeItem(storageKey);
        });
    });
});