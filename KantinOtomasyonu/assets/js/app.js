document.addEventListener('DOMContentLoaded', () => {
    initTheme();
    initSidebar();
    initNotificationPanel();
    initQuickSearch();
    initParallax();
    initLoginParallax();
    initConfirmButtons();
    initSalesCart();
    initSparklines();
    initDashboardCharts();
    initReportsCharts();
});

function initTheme() {
    const html = document.documentElement;
    const toggle = document.getElementById('themeToggleBtn');
    const key = 'kantin_theme';
    const stored = localStorage.getItem(key);
    const fallback = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    const theme = stored || fallback;

    applyTheme(theme);

    if (toggle) {
        toggle.addEventListener('click', () => {
            const next = html.dataset.theme === 'dark' ? 'light' : 'dark';
            applyTheme(next);
            localStorage.setItem(key, next);
            refreshCharts();
        });
    }

    function applyTheme(name) {
        html.dataset.theme = name;
        const icon = document.querySelector('.theme-icon');
        const label = document.querySelector('.theme-label');
        if (icon) icon.textContent = name === 'dark' ? '🌙' : '☀️';
        if (label) label.textContent = name === 'dark' ? 'Koyu' : 'Açık';
    }
}

function initSidebar() {
    const sidebar = document.getElementById('sidebarPanel');
    const backdrop = document.getElementById('sidebarBackdrop');
    const openBtn = document.getElementById('openSidebarBtn');
    const closeBtn = document.getElementById('closeSidebarBtn');
    if (!sidebar || !backdrop || !openBtn) return;

    const open = () => {
        sidebar.classList.add('show');
        backdrop.classList.add('show');
        document.body.style.overflow = 'hidden';
    };
    const close = () => {
        sidebar.classList.remove('show');
        backdrop.classList.remove('show');
        document.body.style.overflow = '';
    };

    openBtn.addEventListener('click', open);
    backdrop.addEventListener('click', close);
    if (closeBtn) closeBtn.addEventListener('click', close);
}

function initNotificationPanel() {
    const btn = document.getElementById('notificationBtn');
    const wrap = btn ? btn.closest('.notif-wrap') : null;
    if (!btn || !wrap) return;

    btn.addEventListener('click', (event) => {
        event.stopPropagation();
        wrap.classList.toggle('open');
    });

    document.addEventListener('click', (event) => {
        if (!wrap.contains(event.target)) {
            wrap.classList.remove('open');
        }
    });
}

function initQuickSearch() {
    const input = document.getElementById('globalQuickSearch');
    if (!input) return;

    const tableRows = Array.from(document.querySelectorAll('.table tbody tr'));
    const cardSections = Array.from(document.querySelectorAll('.content .card'));

    input.addEventListener('input', () => {
        const query = normalize(input.value);

        if (!query) {
            tableRows.forEach((row) => { row.style.display = ''; });
            cardSections.forEach((card) => card.classList.remove('hidden-by-search'));
            return;
        }

        tableRows.forEach((row) => {
            const text = normalize(row.textContent || '');
            row.style.display = text.includes(query) ? '' : 'none';
        });

        cardSections.forEach((card) => {
            const text = normalize(card.textContent || '');
            card.classList.toggle('hidden-by-search', !text.includes(query));
        });
    });
}

function initParallax() {
    const bg = document.querySelector('.ambient-bg');
    if (!bg || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    const orbs = Array.from(bg.querySelectorAll('.orb'));
    if (orbs.length === 0) return;

    window.addEventListener('mousemove', (event) => {
        const x = (event.clientX / window.innerWidth - 0.5) * 10;
        const y = (event.clientY / window.innerHeight - 0.5) * 10;
        orbs.forEach((orb, index) => {
            const factor = (index + 1) * 0.3;
            orb.style.transform = `translate(${x * factor}px, ${y * factor}px)`;
        });
    });
}

function initLoginParallax() {
    const ambient = document.querySelector('.login-ambient');
    if (!ambient || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    const layers = Array.from(ambient.querySelectorAll('[data-depth]'));
    if (layers.length === 0) return;

    let targetX = 0;
    let targetY = 0;
    let currentX = 0;
    let currentY = 0;
    let rafId = null;

    const animate = () => {
        currentX += (targetX - currentX) * 0.08;
        currentY += (targetY - currentY) * 0.08;

        layers.forEach((layer) => {
            const depth = Number(layer.getAttribute('data-depth') || 0.15);
            layer.style.translate = `${(currentX * depth).toFixed(2)}px ${(currentY * depth).toFixed(2)}px`;
        });

        rafId = requestAnimationFrame(animate);
    };

    window.addEventListener('mousemove', (event) => {
        const x = event.clientX / window.innerWidth - 0.5;
        const y = event.clientY / window.innerHeight - 0.5;
        targetX = x * 24;
        targetY = y * 16;
    });

    window.addEventListener('mouseleave', () => {
        targetX = 0;
        targetY = 0;
    });

    rafId = requestAnimationFrame(animate);
    window.addEventListener('beforeunload', () => {
        if (rafId) cancelAnimationFrame(rafId);
    });
}

function initConfirmButtons() {
    document.querySelectorAll('[data-confirm]').forEach((element) => {
        element.addEventListener('click', (event) => {
            const message = element.getAttribute('data-confirm') || 'İşlem onaylanıyor, devam edilsin mi?';
            if (!window.confirm(message)) {
                event.preventDefault();
            }
        });
    });
}

function initSalesCart() {
    const form = document.getElementById('saleForm');
    if (!form) return;

    const picker = document.getElementById('productPicker');
    const qtyInput = document.getElementById('quantityPicker');
    const addBtn = document.getElementById('addToCartBtn');
    const tableBody = document.querySelector('#cartTable tbody');
    const totalNode = document.getElementById('cartTotal');
    const hiddenCart = document.getElementById('cartJson');
    const cart = [];

    const money = (n) => new Intl.NumberFormat('tr-TR', { style: 'currency', currency: 'TRY' }).format(Number(n || 0));

    const render = () => {
        if (cart.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="5">Sepet boş.</td></tr>';
            totalNode.textContent = money(0);
            hiddenCart.value = '[]';
            return;
        }

        let total = 0;
        tableBody.innerHTML = cart.map((item, index) => {
            const lineTotal = item.quantity * item.unit_price;
            total += lineTotal;
            return `
                <tr>
                    <td>${escapeHtml(item.product_name)}</td>
                    <td>${item.quantity}</td>
                    <td>${money(item.unit_price)}</td>
                    <td>${money(lineTotal)}</td>
                    <td><button type="button" class="btn-link danger" data-remove="${index}">Sil</button></td>
                </tr>
            `;
        }).join('');

        totalNode.textContent = money(total);
        hiddenCart.value = JSON.stringify(cart);
        tableBody.querySelectorAll('[data-remove]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const i = Number(btn.getAttribute('data-remove'));
                cart.splice(i, 1);
                render();
            });
        });
    };

    addBtn.addEventListener('click', () => {
        const option = picker.options[picker.selectedIndex];
        const productId = Number(option.value || 0);
        const quantity = Number(qtyInput.value || 0);
        const stock = Number(option.dataset.stock || 0);

        if (!productId || quantity <= 0) return;
        if (quantity > stock) {
            alert('Girilen adet stok miktarından büyük olamaz.');
            return;
        }

        const existing = cart.find((x) => x.product_id === productId);
        if (existing) {
            if ((existing.quantity + quantity) > stock) {
                alert('Toplam adet stok miktarını aşamaz.');
                return;
            }
            existing.quantity += quantity;
        } else {
            cart.push({
                product_id: productId,
                product_name: option.dataset.name || option.textContent,
                quantity,
                unit_price: Number(option.dataset.price || 0),
            });
        }

        qtyInput.value = '1';
        render();
    });

    form.addEventListener('submit', (event) => {
        if (cart.length === 0) {
            event.preventDefault();
            alert('Sepet boş, önce ürün ekleyin.');
        }
    });

    render();
}

function initSparklines() {
    document.querySelectorAll('.sparkline').forEach((canvas) => {
        const raw = canvas.getAttribute('data-points') || '6,9,7,11,10,14';
        const points = raw.split(',').map((x) => Number(x.trim())).filter((x) => !Number.isNaN(x));
        if (points.length < 2) return;

        const ctx = canvas.getContext('2d');
        const width = canvas.clientWidth || 200;
        const height = canvas.clientHeight || 38;
        canvas.width = width;
        canvas.height = height;

        const max = Math.max(...points);
        const min = Math.min(...points);
        const span = max - min || 1;
        const pad = 4;

        ctx.clearRect(0, 0, width, height);
        ctx.lineWidth = 2;
        ctx.strokeStyle = getComputedStyle(document.documentElement).getPropertyValue('--accent').trim();
        ctx.beginPath();

        points.forEach((value, index) => {
            const x = pad + (index * ((width - (pad * 2)) / (points.length - 1)));
            const y = height - pad - ((value - min) / span) * (height - (pad * 2));
            if (index === 0) ctx.moveTo(x, y);
            else ctx.lineTo(x, y);
        });
        ctx.stroke();
    });
}

const _charts = [];

function initDashboardCharts() {
    const trendCanvas = document.getElementById('salesTrendChart');
    const distCanvas = document.getElementById('stockDistChart');
    if (!window.Chart || (!trendCanvas && !distCanvas)) return;

    const theme = getThemeColors();

    if (trendCanvas) {
        if (trendCanvas.dataset.empty === '1') {
            trendCanvas.style.opacity = '0';
            return;
        }
        const labels = JSON.parse(trendCanvas.dataset.labels || '[]');
        const data = JSON.parse(trendCanvas.dataset.values || '[]');
        _charts.push(new Chart(trendCanvas, {
            type: 'line',
            data: {
                labels,
                datasets: [{
                    label: 'Günlük Ciro',
                    data,
                    borderColor: theme.accent,
                    backgroundColor: theme.accentSoft,
                    pointBackgroundColor: theme.accent,
                    fill: true,
                    tension: 0.35
                }]
            },
            options: chartOptions(theme, true)
        }));
    }

    if (distCanvas) {
        if (distCanvas.dataset.empty === '1') {
            distCanvas.style.opacity = '0';
            return;
        }
        const labels = JSON.parse(distCanvas.dataset.labels || '[]');
        const data = JSON.parse(distCanvas.dataset.values || '[]');
        _charts.push(new Chart(distCanvas, {
            type: 'doughnut',
            data: {
                labels,
                datasets: [{
                    data,
                    borderWidth: 0,
                    backgroundColor: [theme.accent, theme.info, theme.warning, theme.danger]
                }]
            },
            options: {
                ...chartOptions(theme, false),
                cutout: '62%'
            }
        }));
    }
}

function initReportsCharts() {
    const reportCanvas = document.getElementById('reportChart');
    if (!window.Chart || !reportCanvas) return;

    const theme = getThemeColors();
    const labels = JSON.parse(reportCanvas.dataset.labels || '[]');
    const sales = JSON.parse(reportCanvas.dataset.sales || '[]');
    const profit = JSON.parse(reportCanvas.dataset.profit || '[]');

    _charts.push(new Chart(reportCanvas, {
        type: 'bar',
        data: {
            labels,
            datasets: [
                {
                    label: 'Satış',
                    data: sales,
                    backgroundColor: theme.accentSoft,
                    borderColor: theme.accent,
                    borderWidth: 1.4
                },
                {
                    label: 'Brüt Kâr',
                    data: profit,
                    backgroundColor: 'rgba(34,197,94,.22)',
                    borderColor: '#22c55e',
                    borderWidth: 1.4
                }
            ]
        },
        options: chartOptions(theme)
    }));
}

function refreshCharts() {
    _charts.forEach((chart) => chart.destroy());
    _charts.length = 0;
    initSparklines();
    initDashboardCharts();
    initReportsCharts();
}

function chartOptions(theme, withScales = true) {
    const options = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { labels: { color: theme.text } },
            tooltip: {
                backgroundColor: theme.tooltipBg,
                titleColor: theme.text,
                bodyColor: theme.text
            }
        }
    };

    if (withScales) {
        options.scales = {
            x: { ticks: { color: theme.muted, maxTicksLimit: 7 }, grid: { color: theme.grid } },
            y: { beginAtZero: true, ticks: { color: theme.muted, maxTicksLimit: 6 }, grid: { color: theme.grid } }
        };
    }

    return options;
}

function getThemeColors() {
    const css = getComputedStyle(document.documentElement);
    return {
        accent: css.getPropertyValue('--accent').trim() || '#facc15',
        accentSoft: css.getPropertyValue('--accent-soft').trim() || 'rgba(250,204,21,.2)',
        text: css.getPropertyValue('--text-primary').trim() || '#fff',
        muted: css.getPropertyValue('--text-muted').trim() || '#94a3b8',
        grid: css.getPropertyValue('--border-color').trim() || 'rgba(255,255,255,.1)',
        info: css.getPropertyValue('--info').trim() || '#38bdf8',
        warning: css.getPropertyValue('--warning').trim() || '#f59e0b',
        danger: css.getPropertyValue('--danger').trim() || '#ef4444',
        tooltipBg: css.getPropertyValue('--card-solid').trim() || '#111827'
    };
}

function normalize(value) {
    return String(value || '')
        .toLocaleLowerCase('tr-TR')
        .replace(/\s+/g, ' ')
        .trim();
}

function escapeHtml(value) {
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };
    return String(value).replace(/[&<>"']/g, (char) => map[char]);
}
