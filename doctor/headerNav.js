(function () {
    const menu = document.getElementById('mobileMenu');
    const toggle = document.getElementById('mobileToggle');
    const overlay = document.getElementById('mobileOverlay');
    const body = document.body;

    if (!menu || !toggle || !overlay) return;

    const openMenu = () => {
        menu.classList.add('active');
        overlay.classList.add('active');
        toggle.classList.add('active');
        body.classList.add('menu-open');
    };

    const closeMenu = () => {
        menu.classList.remove('active');
        overlay.classList.remove('active');
        toggle.classList.remove('active');
        body.classList.remove('menu-open');
    };

    toggle.addEventListener('click', () => {
        menu.classList.contains('active') ? closeMenu() : openMenu();
    });

    overlay.addEventListener('click', closeMenu);

    document.querySelectorAll('.nav-link:not(.logout-link)').forEach(link => {
        link.addEventListener('click', () => setTimeout(closeMenu, 200));
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth > 968) closeMenu();
    });

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') closeMenu();
    });
})();
