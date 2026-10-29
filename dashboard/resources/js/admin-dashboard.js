const dashboard = document.querySelector('[data-admin-dashboard]');
const navLinks = [...document.querySelectorAll('[data-admin-target]')];

function showSection() {
    if (!dashboard) {
        navLinks.forEach(link => {
            link.classList.remove('active');
            link.removeAttribute('aria-current');
        });
        return;
    }
    const requested = window.location.hash.slice(1);
    const validSections = ['all', 'gebruikers', 'rollen', 'scholen', 'systeem'];
    const filters = new URLSearchParams(window.location.search);
    const hasFilters = ['q', 'role', 'school', 'status', 'page'].some(key => filters.get(key));
    const section = validSections.includes(requested) ? requested : (hasFilters ? 'gebruikers' : 'all');
    document.querySelectorAll('[data-dashboard-section]').forEach(element => {
        element.hidden = section === 'all'
            ? !['overzicht', 'gebruikers'].includes(element.dataset.dashboardSection)
            : element.dataset.dashboardSection !== section;
    });
    navLinks.forEach(link => {
        const isActive = link.dataset.adminTarget === section;
        link.classList.toggle('active', isActive);
        if (isActive) link.setAttribute('aria-current', 'page');
        else link.removeAttribute('aria-current');
    });
}
showSection();
window.addEventListener('hashchange', () => {
    showSection();
    if (dashboard) window.scrollTo({ top: 0, behavior: 'instant' });
});

const dataElement = document.getElementById('admin-chart-data');
if (dataElement) {
    const analytics = JSON.parse(dataElement.textContent);
    const months = ['Jan', 'Feb', 'Mrt', 'Apr', 'Mei', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dec'];
    const palette = ['#155c9e', '#58afe0', '#f0ae72', '#858ad3', '#64bec5', '#9cb2cd'];
    const chart = document.getElementById('registrations-chart');
    const donut = document.getElementById('roles-chart');
    const tooltip = document.getElementById('chart-tooltip');
    let highlighted = -1;
    let chartBounds;

    function prepare(canvas) {
        const box = canvas.getBoundingClientRect();
        if (box.width < 1 || box.height < 1) return null;
        const ratio = Math.min(window.devicePixelRatio || 1, 2);
        canvas.width = Math.round(box.width * ratio);
        canvas.height = Math.round(box.height * ratio);
        const ctx = canvas.getContext('2d');
        if (!ctx) return null;
        ctx.scale(ratio, ratio);
        ctx.font = '10px "Instrument Sans", sans-serif';
        return { ctx, width: box.width, height: box.height };
    }

    function drawRegistrations() {
        const drawing = prepare(chart);
        if (!drawing) return;
        const { ctx, width, height } = drawing;
        const margin = { left: 25, right: 8, top: 20, bottom: 28 };
        const plotWidth = width - margin.left - margin.right;
        const plotHeight = height - margin.top - margin.bottom;
        const max = Math.max(4, Math.ceil(Math.max(...analytics.monthly) / 4) * 4);
        const step = plotWidth / 12;
        chartBounds = { margin, step, width };
        ctx.fillStyle = '#8aa0a8';
        ctx.textAlign = 'right';
        ctx.textBaseline = 'middle';
        for (let tick = 0; tick <= 4; tick++) {
            const y = margin.top + plotHeight * (1 - tick / 4);
            ctx.fillText(String(max * tick / 4), margin.left - 9, y);
            ctx.beginPath();
            ctx.setLineDash([3, 5]);
            ctx.moveTo(margin.left, y);
            ctx.lineTo(width - margin.right, y);
            ctx.strokeStyle = '#e1ebee';
            ctx.lineWidth = 1;
            ctx.stroke();
        }
        ctx.setLineDash([]);
        const points = analytics.monthly.map((value, index) => ({
            x: margin.left + step * (index + .5),
            y: margin.top + plotHeight * (1 - value / max),
        }));
        const fill = ctx.createLinearGradient(0, margin.top, 0, margin.top + plotHeight);
        fill.addColorStop(0, '#2b88cf26');
        fill.addColorStop(1, '#2b88cf02');
        ctx.beginPath();
        ctx.moveTo(points[0].x, margin.top + plotHeight);
        points.forEach(point => ctx.lineTo(point.x, point.y));
        ctx.lineTo(points.at(-1).x, margin.top + plotHeight);
        ctx.closePath();
        ctx.fillStyle = fill;
        ctx.fill();
        analytics.monthly.forEach((value, index) => {
            const point = points[index];
            const barWidth = Math.min(24, step * .45);
            const barHeight = value / max * plotHeight;
            const gradient = ctx.createLinearGradient(0, point.y, 0, margin.top + plotHeight);
            gradient.addColorStop(0, highlighted === index ? '#104e8b' : '#4194d0');
            gradient.addColorStop(1, '#155b9d');
            ctx.fillStyle = gradient;
            if (value > 0) {
                ctx.beginPath();
                ctx.roundRect(point.x - barWidth / 2, point.y, barWidth, barHeight, [4, 4, 0, 0]);
                ctx.fill();
            }
            ctx.textAlign = 'center';
            ctx.fillStyle = highlighted === index ? '#155c9e' : '#8aa0a8';
            ctx.fillText(months[index], point.x, height - 10);
        });
        ctx.beginPath();
        points.forEach((point, index) => index === 0 ? ctx.moveTo(point.x, point.y) : ctx.lineTo(point.x, point.y));
        ctx.strokeStyle = '#2b7abb';
        ctx.lineJoin = 'round';
        ctx.lineWidth = 2;
        ctx.stroke();
        points.forEach((point, index) => {
            if (analytics.monthly[index] > 0 || highlighted === index) {
                ctx.beginPath();
                ctx.arc(point.x, point.y, 3.5, 0, 2 * Math.PI);
                ctx.fillStyle = '#fff';
                ctx.fill();
                ctx.strokeStyle = '#2b7abb';
                ctx.lineWidth = 2;
                ctx.stroke();
            }
        });
    }

    function drawRoles() {
        const drawing = prepare(donut);
        if (!drawing) return;
        const { ctx, width, height } = drawing;
        const radius = Math.min(width, height) / 2 - 10;
        const stroke = Math.max(16, radius * .31);
        const centerX = width / 2;
        const centerY = height / 2;
        const ringRadius = radius - stroke / 2;
        const total = analytics.roles.reduce((sum, role) => sum + role.value, 0);
        ctx.lineWidth = stroke;
        ctx.beginPath();
        ctx.arc(centerX, centerY, ringRadius, 0, Math.PI * 2);
        ctx.strokeStyle = '#e4edef';
        ctx.stroke();
        if (!total) return;
        let angle = -Math.PI / 2;
        const segments = analytics.roles.filter(role => role.value > 0).length;
        analytics.roles.forEach((role, index) => {
            if (!role.value) return;
            const extent = role.value / total * Math.PI * 2;
            const gap = segments > 1 ? Math.min(.035, extent * .15) : 0;
            ctx.beginPath();
            ctx.arc(centerX, centerY, ringRadius, angle + gap / 2, angle + extent - gap / 2);
            ctx.strokeStyle = palette[index % palette.length];
            ctx.stroke();
            angle += extent;
        });
    }
    chart.addEventListener('pointermove', event => {
        if (!chartBounds) return;
        const { margin, step } = chartBounds;
        const x = event.clientX - chart.getBoundingClientRect().left;
        const index = Math.max(0, Math.min(11, Math.floor((x - margin.left) / step)));
        if (highlighted !== index) {
            highlighted = index;
            drawRegistrations();
        }
        tooltip.textContent = `${months[index]} ${analytics.year} · ${analytics.monthly[index]} nieuwe accounts`;
        tooltip.hidden = false;
        tooltip.style.left = `${Math.max(0, Math.min(x - tooltip.offsetWidth / 2, chart.clientWidth - tooltip.offsetWidth))}px`;
    });
    chart.addEventListener('pointerleave', () => {
        highlighted = -1;
        tooltip.hidden = true;
        drawRegistrations();
    });
    const observer = new ResizeObserver(() => { drawRegistrations(); drawRoles(); });
    observer.observe(chart.parentElement);
    observer.observe(donut.parentElement);
    document.fonts.ready.then(() => { drawRegistrations(); drawRoles(); });
}
