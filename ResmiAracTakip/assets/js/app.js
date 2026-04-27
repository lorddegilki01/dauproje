document.addEventListener('DOMContentLoaded', () => {
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const csrfToken = document.body?.dataset.csrfToken || '';
    const sidebar = document.querySelector('[data-sidebar]');
    const menuToggle = document.querySelector('[data-menu-toggle]');

    initThemeSystem();

    if (menuToggle && sidebar) {
        const closeSidebar = () => {
            sidebar.classList.remove('open');
            document.body.classList.remove('sidebar-open');
        };

        menuToggle.addEventListener('click', () => {
            const open = sidebar.classList.toggle('open');
            document.body.classList.toggle('sidebar-open', open);
        });

        document.addEventListener('click', (event) => {
            if (window.innerWidth > 980 || !sidebar.classList.contains('open')) return;
            if (!sidebar.contains(event.target) && !menuToggle.contains(event.target)) {
                closeSidebar();
            }
        });

        window.addEventListener('resize', () => {
            if (window.innerWidth > 980) closeSidebar();
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') closeSidebar();
        });
    }

    document.querySelectorAll('[data-dismiss-alert]').forEach((button) => {
        button.addEventListener('click', () => {
            const alertBox = button.closest('[data-alert], .alert');
            if (alertBox) alertBox.remove();
        });
    });

    document.querySelectorAll('[data-confirm]').forEach((link) => {
        link.addEventListener('click', (event) => {
            const message = link.getAttribute('data-confirm') || 'İşlem onayı gerekiyor.';
            if (!window.confirm(message)) event.preventDefault();
        });
    });

    document.querySelectorAll('[data-table-search]').forEach((input) => {
        input.addEventListener('input', () => {
            const table = document.getElementById(input.dataset.tableSearch);
            if (!table) return;
            const query = input.value.trim().toLocaleLowerCase('tr-TR');
            table.querySelectorAll('tbody tr').forEach((row) => {
                const text = row.innerText.toLocaleLowerCase('tr-TR');
                row.style.display = text.includes(query) ? '' : 'none';
            });
        });
    });

    const scrollTopButton = document.querySelector('[data-scroll-top]');
    if (scrollTopButton) {
        const toggleVisibility = () => {
            const isVisible = (window.scrollY || document.documentElement.scrollTop) > 220;
            scrollTopButton.classList.toggle('visible', isVisible);
        };
        toggleVisibility();
        window.addEventListener('scroll', toggleVisibility, { passive: true });
        scrollTopButton.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: reduceMotion ? 'auto' : 'smooth' });
        });
    }

    initNotificationPanel(csrfToken);
    initQuickSearch();
    initDashboardCharts();
    initLoginBackgroundAnimation(reduceMotion);
});

function initThemeSystem() {
    const root = document.documentElement;
    const toggle = document.querySelector('[data-theme-toggle]');
    const icon = document.querySelector('[data-theme-icon]');
    const label = document.querySelector('[data-theme-label]');

    const getCurrentTheme = () => root.getAttribute('data-theme') === 'light' ? 'light' : 'dark';

    const applyTheme = (theme) => {
        const safeTheme = theme === 'light' ? 'light' : 'dark';
        root.setAttribute('data-theme', safeTheme);
        try {
            localStorage.setItem('rat_theme', safeTheme);
        } catch (err) {
            // sessiz geç
        }
        if (icon) icon.textContent = safeTheme === 'dark' ? '🌙' : '☀️';
        if (label) label.textContent = safeTheme === 'dark' ? 'Koyu' : 'Açık';
        window.dispatchEvent(new CustomEvent('themechange', { detail: { theme: safeTheme } }));
    };

    applyTheme(getCurrentTheme());

    if (toggle) {
        toggle.addEventListener('click', () => {
            const next = getCurrentTheme() === 'dark' ? 'light' : 'dark';
            applyTheme(next);
        });
    }
}

function initNotificationPanel(csrfToken) {
    const wrap = document.querySelector('[data-notification-wrap]');
    if (!wrap) return;

    const toggle = wrap.querySelector('[data-notification-toggle]');
    const dropdown = wrap.querySelector('[data-notification-dropdown]');
    const list = wrap.querySelector('[data-notification-list]');
    const badge = wrap.querySelector('[data-notification-badge]');
    const markAllBtn = wrap.querySelector('[data-mark-all-read]');

    const updateBadge = (count) => {
        if (!badge) return;
        badge.textContent = String(count);
        badge.classList.toggle('hidden', Number(count) <= 0);
    };

    const renderItems = (items) => {
        if (!list) return;
        if (!Array.isArray(items) || items.length === 0) {
            list.innerHTML = '<p class="empty-state">Bildirim bulunmuyor.</p>';
            return;
        }

        list.innerHTML = items.map((item) => {
            const unreadClass = Number(item.is_read) === 0 ? 'unread' : '';
            const colorClass = item.color_class || 'neutral';
            const icon = item.icon || '•';
            const url = item.url || '#';
            const id = Number(item.id) || 0;
            return `
                <a class="notification-item ${unreadClass}" href="${escapeAttribute(url)}" data-id="${id}">
                    <span class="notification-item-icon ${escapeAttribute(colorClass)}">${escapeHtml(icon)}</span>
                    <div class="notification-item-content">
                        <strong>${escapeHtml(item.title || '')}</strong>
                        <p>${escapeHtml(item.message || '')}</p>
                        <small>${escapeHtml(item.time_ago || '')}</small>
                    </div>
                </a>
            `;
        }).join('');

        list.querySelectorAll('.notification-item').forEach((el) => {
            el.addEventListener('click', () => {
                const id = Number(el.dataset.id || 0);
                if (id > 0) markSingleNotificationRead(id, csrfToken, updateBadge);
            });
        });
    };

    const fetchNotifications = async () => {
        try {
            const response = await fetch(getAppUrl('notifications/list.php'), { credentials: 'same-origin' });
            const data = await response.json();
            if (!data.ok) return;
            renderItems(data.items || []);
            updateBadge(data.unread_count || 0);
        } catch (err) {
            if (list) list.innerHTML = '<p class="empty-state">Bildirimler yüklenemedi.</p>';
        }
    };

    toggle?.addEventListener('click', (event) => {
        event.stopPropagation();
        dropdown?.classList.toggle('open');
        if (dropdown?.classList.contains('open')) fetchNotifications();
    });

    document.addEventListener('click', (event) => {
        if (!wrap.contains(event.target)) dropdown?.classList.remove('open');
    });

    markAllBtn?.addEventListener('click', async (event) => {
        event.preventDefault();
        try {
            const response = await fetch(getAppUrl('notifications/mark_all_read.php'), {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                body: new URLSearchParams({ csrf_token: csrfToken }).toString(),
            });
            const data = await response.json();
            if (data.ok) {
                updateBadge(data.unread_count || 0);
                fetchNotifications();
            }
        } catch (err) {
            // sessiz geç
        }
    });
}

function initQuickSearch() {
    const form = document.querySelector('[data-quick-search-form]');
    const input = document.querySelector('[data-quick-search-input]');
    const dropdown = document.querySelector('[data-quick-search-dropdown]');
    const list = document.querySelector('[data-quick-search-list]');
    const title = document.querySelector('[data-quick-search-title]');

    if (!form || !input || !dropdown || !list) return;

    const recentKey = 'rat_quick_search_recent';
    let highlightedIndex = -1;
    let currentItems = [];
    let debounceId = null;

    const readRecent = () => {
        try {
            const data = JSON.parse(localStorage.getItem(recentKey) || '[]');
            if (!Array.isArray(data)) return [];
            return data.filter((v) => typeof v === 'string' && v.trim() !== '').slice(0, 6);
        } catch (err) {
            return [];
        }
    };

    const saveRecent = (value) => {
        const normalized = value.trim();
        if (normalized.length < 2) return;
        const merged = [normalized, ...readRecent().filter((item) => item.toLocaleLowerCase('tr-TR') !== normalized.toLocaleLowerCase('tr-TR'))].slice(0, 6);
        localStorage.setItem(recentKey, JSON.stringify(merged));
    };

    const openDropdown = () => dropdown.classList.add('open');
    const closeDropdown = () => {
        dropdown.classList.remove('open');
        highlightedIndex = -1;
    };

    const setTitle = (text) => {
        if (title) title.textContent = text;
    };

    const highlightItem = (index) => {
        const entries = list.querySelectorAll('.quick-search-item');
        entries.forEach((el, i) => el.classList.toggle('active', i === index));
        highlightedIndex = index;
    };

    const renderRecent = () => {
        const recent = readRecent();
        currentItems = recent.map((q) => ({
            title: q,
            subtitle: 'Kayıtlı arama',
            meta: 'Geçmiş',
            url: getAppUrl(`search/index.php?q=${encodeURIComponent(q)}`),
        }));
        setTitle('Kaydedilen Aramalar');
        if (currentItems.length === 0) {
            list.innerHTML = '<p class="empty-state">Aramaya başlayın...</p>';
            return;
        }
        list.innerHTML = currentItems.map((item) => `
            <a class="quick-search-item" href="${escapeAttribute(item.url)}">
                <span class="quick-search-item-title">${escapeHtml(item.title)}</span>
                <span class="quick-search-item-sub">${escapeHtml(item.subtitle)}</span>
                <span class="quick-search-item-meta">${escapeHtml(item.meta)}</span>
            </a>
        `).join('');
        bindItemClicks();
    };

    const groupItems = (items) => {
        const map = new Map();
        items.forEach((item) => {
            const group = item.group || 'Diğer';
            if (!map.has(group)) map.set(group, []);
            map.get(group).push(item);
        });
        return map;
    };

    const renderSuggestions = (items) => {
        currentItems = items;
        if (!items.length) {
            setTitle('Arama Önerileri');
            list.innerHTML = '<p class="empty-state">Eşleşen sonuç bulunamadı.</p>';
            return;
        }
        setTitle('Canlı Sonuçlar');

        const grouped = groupItems(items);
        let html = '';
        grouped.forEach((rows, group) => {
            html += `<div class="quick-search-group"><h4>${escapeHtml(group)}</h4>`;
            html += rows.map((item) => `
                <a class="quick-search-item" href="${escapeAttribute(item.url || '#')}">
                    <span class="quick-search-item-title">${escapeHtml(item.title || '')}</span>
                    <span class="quick-search-item-sub">${escapeHtml(item.subtitle || '')}</span>
                    <span class="quick-search-item-meta">${escapeHtml(item.meta || '')}</span>
                </a>
            `).join('');
            html += '</div>';
        });
        list.innerHTML = html;
        bindItemClicks();
    };

    const bindItemClicks = () => {
        list.querySelectorAll('.quick-search-item').forEach((el) => {
            el.addEventListener('click', () => {
                const value = input.value.trim();
                if (value) saveRecent(value);
                closeDropdown();
            });
        });
    };

    const fetchSuggestions = async (value) => {
        try {
            const response = await fetch(getAppUrl(`search/suggest.php?q=${encodeURIComponent(value)}`), { credentials: 'same-origin' });
            const data = await response.json();
            if (!data.ok) return;
            renderSuggestions(Array.isArray(data.items) ? data.items : []);
        } catch (err) {
            list.innerHTML = '<p class="empty-state">Öneriler yüklenemedi.</p>';
        }
    };

    input.addEventListener('focus', () => {
        if (input.value.trim().length < 2) {
            renderRecent();
        }
        openDropdown();
    });

    input.addEventListener('input', () => {
        const value = input.value.trim();
        openDropdown();
        highlightedIndex = -1;
        if (debounceId) window.clearTimeout(debounceId);
        debounceId = window.setTimeout(() => {
            if (value.length < 2) {
                renderRecent();
                return;
            }
            fetchSuggestions(value);
        }, 180);
    });

    input.addEventListener('keydown', (event) => {
        const items = list.querySelectorAll('.quick-search-item');
        if (!dropdown.classList.contains('open') || items.length === 0) return;

        if (event.key === 'ArrowDown') {
            event.preventDefault();
            const next = Math.min(items.length - 1, highlightedIndex + 1);
            highlightItem(next);
            return;
        }
        if (event.key === 'ArrowUp') {
            event.preventDefault();
            const next = Math.max(0, highlightedIndex - 1);
            highlightItem(next);
            return;
        }
        if (event.key === 'Enter' && highlightedIndex >= 0) {
            event.preventDefault();
            const active = items[highlightedIndex];
            if (active) {
                saveRecent(input.value.trim());
                window.location.href = active.getAttribute('href') || '#';
            }
            return;
        }
        if (event.key === 'Escape') {
            closeDropdown();
        }
    });

    document.addEventListener('keydown', (event) => {
        if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
            event.preventDefault();
            input.focus();
            input.select();
            openDropdown();
        }
    });

    document.addEventListener('click', (event) => {
        if (!form.contains(event.target)) closeDropdown();
    });

    form.addEventListener('submit', () => {
        saveRecent(input.value.trim());
        closeDropdown();
    });
}

async function markSingleNotificationRead(id, csrfToken, updateBadge) {
    try {
        const response = await fetch(getAppUrl('notifications/mark_read.php'), {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: new URLSearchParams({ id: String(id), csrf_token: csrfToken }).toString(),
        });
        const data = await response.json();
        if (data.ok) updateBadge(data.unread_count || 0);
    } catch (err) {
        // sessiz geç
    }
}

let chartInstances = [];

function cssVar(name, fallback = '') {
    const value = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
    return value || fallback;
}

function getChartPalette(theme) {
    return {
        blue: cssVar('--chart-blue', theme === 'light' ? '#3d77d6' : '#67a8ff'),
        green: cssVar('--chart-green', theme === 'light' ? '#2ea86d' : '#59d59a'),
        orange: cssVar('--chart-orange', theme === 'light' ? '#e39b45' : '#ffb35f'),
        red: cssVar('--chart-red', theme === 'light' ? '#d6506a' : '#ff6f87'),
        line: cssVar('--chart-grid', theme === 'light' ? 'rgba(42, 87, 145, 0.18)' : 'rgba(116, 154, 208, 0.25)'),
        text: cssVar('--chart-text', theme === 'light' ? '#20324f' : '#dbe8ff'),
        statusBorder: cssVar('--chart-border', theme === 'light' ? '#f4f8fd' : '#0f1a2f'),
        fuelBar: cssVar('--chart-fuel-bar', theme === 'light' ? 'rgba(61, 119, 214, 0.72)' : 'rgba(103, 168, 255, 0.72)'),
        tooltipBg: cssVar('--chart-tooltip-bg', theme === 'light' ? '#172b47' : '#0a1528'),
        tooltipText: cssVar('--chart-tooltip-text', '#ffffff'),
        legend: cssVar('--chart-legend', theme === 'light' ? '#2a3f5f' : '#dbe8ff'),
    };
}

function renderDashboardCharts() {
    if (typeof window.Chart === 'undefined' || typeof window.dashboardCharts === 'undefined') return;

    chartInstances.forEach((chart) => chart.destroy());
    chartInstances = [];

    const theme = document.documentElement.getAttribute('data-theme') === 'light' ? 'light' : 'dark';
    const palette = getChartPalette(theme);

    Chart.defaults.color = palette.text;
    Chart.defaults.borderColor = palette.line;
    Chart.defaults.font.family = '"Bahnschrift", "Segoe UI", sans-serif';

    const commonTooltip = {
        backgroundColor: palette.tooltipBg,
        titleColor: palette.tooltipText,
        bodyColor: palette.tooltipText,
        borderColor: palette.line,
        borderWidth: 1,
    };

    const commonScales = {
        x: {
            grid: { color: palette.line },
            ticks: { color: palette.text },
        },
        y: {
            grid: { color: palette.line },
            ticks: { color: palette.text },
        },
    };

    const usageCanvas = document.getElementById('chart-usage');
    if (usageCanvas) {
        chartInstances.push(new Chart(usageCanvas, {
            type: 'line',
            data: {
                labels: window.dashboardCharts.usage.labels,
                datasets: [
                    {
                        label: 'Kullanımda',
                        data: window.dashboardCharts.usage.active,
                        borderColor: palette.blue,
                        backgroundColor: theme === 'light' ? 'rgba(61,119,214,0.18)' : 'rgba(103,168,255,0.18)',
                        tension: 0.35,
                        fill: true,
                    },
                    {
                        label: 'Müsait',
                        data: window.dashboardCharts.usage.available,
                        borderColor: palette.green,
                        backgroundColor: theme === 'light' ? 'rgba(46,168,109,0.14)' : 'rgba(89,213,154,0.12)',
                        tension: 0.35,
                        fill: true,
                    },
                    {
                        label: 'Bakımda',
                        data: window.dashboardCharts.usage.maintenance,
                        borderColor: palette.orange,
                        backgroundColor: theme === 'light' ? 'rgba(227,155,69,0.12)' : 'rgba(255,179,95,0.10)',
                        tension: 0.35,
                        fill: true,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top', labels: { color: palette.legend } },
                    tooltip: commonTooltip,
                },
                scales: commonScales,
            },
        }));
    }

    const statusCanvas = document.getElementById('chart-status');
    if (statusCanvas) {
        chartInstances.push(new Chart(statusCanvas, {
            type: 'doughnut',
            data: {
                labels: window.dashboardCharts.status.labels,
                datasets: [{
                    data: window.dashboardCharts.status.values,
                    backgroundColor: [palette.blue, palette.green, palette.orange, '#7b8ba8'],
                    borderColor: palette.statusBorder,
                    borderWidth: 2,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { color: palette.legend } },
                    tooltip: commonTooltip,
                },
            },
        }));
    }

    const fuelCanvas = document.getElementById('chart-fuel');
    if (fuelCanvas) {
        chartInstances.push(new Chart(fuelCanvas, {
            type: 'bar',
            data: {
                labels: window.dashboardCharts.fuel.labels,
                datasets: [{
                    label: 'Aylık Yakıt Gideri (TL)',
                    data: window.dashboardCharts.fuel.values,
                    backgroundColor: palette.fuelBar,
                    borderRadius: 8,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: commonTooltip,
                },
                scales: commonScales,
            },
        }));
    }
}

function renderPersonnelCharts() {
    if (typeof window.Chart === 'undefined' || typeof window.personnelCharts === 'undefined') return;

    chartInstances.forEach((chart) => chart.destroy());
    chartInstances = [];

    const theme = document.documentElement.getAttribute('data-theme') === 'light' ? 'light' : 'dark';
    const palette = getChartPalette(theme);

    Chart.defaults.color = palette.text;
    Chart.defaults.borderColor = palette.line;
    Chart.defaults.font.family = '"Bahnschrift", "Segoe UI", sans-serif';

    const commonTooltip = {
        backgroundColor: palette.tooltipBg,
        titleColor: palette.tooltipText,
        bodyColor: palette.tooltipText,
        borderColor: palette.line,
        borderWidth: 1,
    };

    const commonScales = {
        x: {
            grid: { color: palette.line },
            ticks: { color: palette.text },
        },
        y: {
            grid: { color: palette.line },
            ticks: { color: palette.text },
        },
    };

    const usageCanvas = document.getElementById('personnel-chart-usage');
    if (usageCanvas) {
        chartInstances.push(new Chart(usageCanvas, {
            type: 'line',
            data: {
                labels: window.personnelCharts.usage.labels,
                datasets: [{
                    label: 'Kullanım Kaydı',
                    data: window.personnelCharts.usage.values,
                    borderColor: palette.blue,
                    backgroundColor: theme === 'light' ? 'rgba(61,119,214,0.18)' : 'rgba(103,168,255,0.16)',
                    fill: true,
                    tension: 0.35,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: commonTooltip,
                },
                scales: commonScales,
            },
        }));
    }

    const requestCanvas = document.getElementById('personnel-chart-requests');
    if (requestCanvas) {
        chartInstances.push(new Chart(requestCanvas, {
            type: 'doughnut',
            data: {
                labels: window.personnelCharts.requests.labels,
                datasets: [{
                    data: window.personnelCharts.requests.values,
                    backgroundColor: [palette.orange, palette.green, palette.red],
                    borderColor: palette.statusBorder,
                    borderWidth: 2,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { color: palette.legend } },
                    tooltip: commonTooltip,
                },
            },
        }));
    }

    const fuelCanvas = document.getElementById('personnel-chart-fuel');
    if (fuelCanvas) {
        chartInstances.push(new Chart(fuelCanvas, {
            type: 'bar',
            data: {
                labels: window.personnelCharts.fuel.labels,
                datasets: [{
                    label: 'Yakıt Giderim (TL)',
                    data: window.personnelCharts.fuel.values,
                    backgroundColor: palette.fuelBar,
                    borderRadius: 8,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: commonTooltip,
                },
                scales: commonScales,
            },
        }));
    }
}

function initDashboardCharts() {
    const renderAllCharts = () => {
        if (typeof window.dashboardCharts !== 'undefined') {
            renderDashboardCharts();
        } else if (typeof window.personnelCharts !== 'undefined') {
            renderPersonnelCharts();
        }
    };

    renderAllCharts();
    window.addEventListener('themechange', renderAllCharts);
}

function initLoginBackgroundAnimation(reduceMotion) {
    if (reduceMotion) return;

    const loginPage = document.querySelector('.login-page-animated');
    const loginBg = document.querySelector('.login-bg');
    const blobs = Array.from(document.querySelectorAll('.login-bg .blob'));
    if (!loginPage || !loginBg || blobs.length === 0) return;

    let rafId = null;
    let targetX = 0;
    let targetY = 0;
    let currentX = 0;
    let currentY = 0;

    const animate = () => {
        currentX += (targetX - currentX) * 0.08;
        currentY += (targetY - currentY) * 0.08;
        loginBg.style.transform = `translate3d(${currentX * 0.18}px, ${currentY * 0.18}px, 0)`;
        blobs.forEach((blob, index) => {
            const factor = (index + 1) * 0.55;
            blob.style.transform = `translate3d(${currentX * factor}px, ${currentY * factor}px, 0)`;
        });
        rafId = requestAnimationFrame(animate);
    };

    loginPage.addEventListener('mousemove', (event) => {
        const x = (event.clientX / window.innerWidth - 0.5) * 2;
        const y = (event.clientY / window.innerHeight - 0.5) * 2;
        targetX = x * 8;
        targetY = y * 8;
    });

    loginPage.addEventListener('mouseleave', () => {
        targetX = 0;
        targetY = 0;
    });

    rafId = requestAnimationFrame(animate);
    window.addEventListener('beforeunload', () => {
        if (rafId !== null) cancelAnimationFrame(rafId);
    });
}

function escapeHtml(value) {
    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#39;');
}

function escapeAttribute(value) {
    return escapeHtml(value);
}

function getAppUrl(path) {
    const base = document.body?.dataset.baseUrl || '';
    return `${base}/${path}`.replace(/([^:]\/)\/+/g, '$1');
}
