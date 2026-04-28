(() => {
    const doc = document.documentElement;
    const body = document.body;
    const toggleBtn = document.querySelector('[data-menu-toggle]');
    const menu = document.querySelector('[data-main-menu]');
    const themeBtn = document.querySelector('[data-theme-toggle]');

    const applyTheme = (theme) => {
        const nextTheme = theme === 'light' ? 'light' : 'dark';
        doc.setAttribute('data-theme', nextTheme);
        localStorage.setItem('ak_theme', nextTheme);
        if (themeBtn) {
            themeBtn.textContent = nextTheme === 'dark' ? '\u2600' : '\u263E';
        }
    };

    const bootTheme = () => {
        const saved = localStorage.getItem('ak_theme');
        if (saved) {
            applyTheme(saved);
            return;
        }
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        applyTheme(prefersDark ? 'dark' : 'light');
    };

    if (themeBtn) {
        themeBtn.addEventListener('click', () => {
            const current = doc.getAttribute('data-theme') || 'dark';
            applyTheme(current === 'dark' ? 'light' : 'dark');
        });
    }

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

    const revealNodes = document.querySelectorAll('.card, .book-card, .hero, .landing-v2, .v2-stat-card, .v2-step, .v2-cta');
    const reveal = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('show');
                reveal.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });

    revealNodes.forEach((node) => {
        node.classList.add('reveal');
        reveal.observe(node);
    });

    const animateCount = (node) => {
        const target = Number(node.dataset.count || 0);
        const duration = 900;
        const startTime = performance.now();

        const step = (now) => {
            const progress = Math.min((now - startTime) / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3);
            node.textContent = Math.floor(target * eased).toLocaleString('tr-TR');
            if (progress < 1) {
                requestAnimationFrame(step);
            } else {
                node.textContent = target.toLocaleString('tr-TR');
            }
        };
        requestAnimationFrame(step);
    };

    document.querySelectorAll('[data-count]').forEach((counter) => animateCount(counter));

    if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        const parallaxRoot = document.querySelector('[data-landing-parallax]');
        const parallaxNodes = parallaxRoot ? parallaxRoot.querySelectorAll('.v2-orb, .v2-floating, .v2-hero-visual') : [];

        body.addEventListener('mousemove', (event) => {
            const x = (event.clientX / window.innerWidth) - 0.5;
            const y = (event.clientY / window.innerHeight) - 0.5;
            body.style.setProperty('--px', `${x * 12}px`);
            body.style.setProperty('--py', `${y * 12}px`);

            if (parallaxNodes.length > 0) {
                parallaxNodes.forEach((node, idx) => {
                    const strength = (idx + 1) * 2.2;
                    node.style.transform = `translate3d(${x * strength}px, ${y * strength}px, 0)`;
                });
            }
        });
    }

    bootTheme();
})();
