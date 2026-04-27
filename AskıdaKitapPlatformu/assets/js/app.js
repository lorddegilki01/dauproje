(() => {
    const toggleBtn = document.querySelector('[data-menu-toggle]');
    const menu = document.querySelector('[data-main-menu]');

    if (toggleBtn && menu) {
        toggleBtn.addEventListener('click', () => {
            menu.classList.toggle('open');
        });

        document.addEventListener('click', (event) => {
            if (!menu.contains(event.target) && !toggleBtn.contains(event.target)) {
                menu.classList.remove('open');
            }
        });
    }
})();

