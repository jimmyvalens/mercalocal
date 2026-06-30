</main>

<footer style="background-color:#1e293b; color:white; padding:3rem 1.5rem; margin-top:auto;">
    <div style="max-width:80rem; margin:0 auto;">
        <?php
        $logoFile = file_exists(ROOT_DIR . '/public/img/mercalocal-logo.png')
            ? BASE_URL . '/img/mercalocal-logo.png'
            : BASE_URL . '/img/mercalocal-logo.svg';
        ?>
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:2rem; margin-bottom:2.5rem;">
            <div>
                <a href="<?= BASE_URL ?>/" style="display:flex; align-items:center; gap:.6rem; margin-bottom:1rem;">
                    <img src="<?= $logoFile ?>" alt="Mercalocal" style="height:38px; width:auto;">
                    <span style="font-weight:800; font-size:1.15rem; color:white;">Merca<span style="color:#f97316;">local</span></span>
                </a>
                <p style="color:#9dc9a8; font-size:.85rem; line-height:1.7; margin:0;">Conectando vecinos con los comercios de su barrio.</p>
                <div style="display:flex; gap:.75rem; margin-top:1rem;">
                    <a href="#" style="color:#9dc9a8; font-size:1.1rem;" onmouseover="this.style.color='var(--ml-beige)'" onmouseout="this.style.color='#9dc9a8'"><i class="fa-brands fa-instagram"></i></a>
                    <a href="#" style="color:#9dc9a8; font-size:1.1rem;" onmouseover="this.style.color='var(--ml-beige)'" onmouseout="this.style.color='#9dc9a8'"><i class="fa-brands fa-facebook"></i></a>
                </div>
            </div>

            <div>
                <p style="font-size:.7rem; font-weight:700; color:#9dc9a8; text-transform:uppercase; letter-spacing:.08em; margin:0 0 .875rem;">Clientes</p>
                <div style="display:flex; flex-direction:column; gap:.5rem;">
                    <a href="<?= BASE_URL ?>/businesses" style="color:#c8e6cc; font-size:.875rem;" onmouseover="this.style.color='var(--ml-beige)'" onmouseout="this.style.color='#c8e6cc'">Explorar Comercios</a>
                    <a href="<?= BASE_URL ?>/register" style="color:#c8e6cc; font-size:.875rem;" onmouseover="this.style.color='var(--ml-beige)'" onmouseout="this.style.color='#c8e6cc'">Crear Cuenta</a>
                    <a href="<?= BASE_URL ?>/orders" style="color:#c8e6cc; font-size:.875rem;" onmouseover="this.style.color='var(--ml-beige)'" onmouseout="this.style.color='#c8e6cc'">Mis Pedidos</a>
                </div>
            </div>

            <div>
                <p style="font-size:.7rem; font-weight:700; color:#9dc9a8; text-transform:uppercase; letter-spacing:.08em; margin:0 0 .875rem;">Comercios</p>
                <div style="display:flex; flex-direction:column; gap:.5rem;">
                    <a href="<?= BASE_URL ?>/register" style="color:#c8e6cc; font-size:.875rem;" onmouseover="this.style.color='var(--ml-beige)'" onmouseout="this.style.color='#c8e6cc'">&Uacute;nete a Mercalocal</a>
                    <a href="<?= BASE_URL ?>/business/dashboard" style="color:#c8e6cc; font-size:.875rem;" onmouseover="this.style.color='var(--ml-beige)'" onmouseout="this.style.color='#c8e6cc'">Mi Panel</a>
                </div>
            </div>

            <div>
                <p style="font-size:.7rem; font-weight:700; color:#9dc9a8; text-transform:uppercase; letter-spacing:.08em; margin:0 0 .875rem;">Legal</p>
                <div style="display:flex; flex-direction:column; gap:.5rem;">
                    <a href="#" style="color:#c8e6cc; font-size:.875rem;" onmouseover="this.style.color='var(--ml-beige)'" onmouseout="this.style.color='#c8e6cc'">Privacidad</a>
                    <a href="#" style="color:#c8e6cc; font-size:.875rem;" onmouseover="this.style.color='var(--ml-beige)'" onmouseout="this.style.color='#c8e6cc'">T&eacute;rminos de Uso</a>
                    <a href="#" style="color:#c8e6cc; font-size:.875rem;" onmouseover="this.style.color='var(--ml-beige)'" onmouseout="this.style.color='#c8e6cc'">Cookies</a>
                </div>
            </div>
        </div>

        <div style="border-top:1px solid #1e4d2b; padding-top:1.5rem; display:flex; justify-content:space-between; flex-wrap:wrap; gap:.75rem; align-items:center;">
            <p style="color:#5a8f6a; font-size:.825rem; margin:0;">&copy; <?= date('Y') ?> Mercalocal. Todos los derechos reservados.</p>
            <p style="color:#5a8f6a; font-size:.825rem; margin:0;">Hecho con <i class="fa-solid fa-heart" style="color:#e05050;"></i> para el comercio local</p>
        </div>
    </div>
</footer>

<script>
    window.BASE_URL = "<?= BASE_URL ?>";
</script>
<script src="<?= BASE_URL ?>/js/main.js"></script>

</body>

</html>