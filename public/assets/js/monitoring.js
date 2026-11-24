(function () {
    const root = document.getElementById('chart-root');
    if (!root || typeof Chart === 'undefined') {
        return;
    }

    const deviceId = root.dataset.deviceId;
    const tempCanvas = document.getElementById('chart-temperature');
    const humCanvas = document.getElementById('chart-humidity');
    const empty = document.getElementById('chart-empty');
    const updated = document.getElementById('chart-updated');
    const status = document.getElementById('chart-status');
    let period = root.dataset.period || '1h';
    let inFlight = false;
    let tempChart = null;
    let humChart = null;

    function baseOptions(label, color) {
        return {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: label,
                    data: [],
                    borderColor: color,
                    backgroundColor: color,
                    pointRadius: 2,
                    tension: 0.2,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { ticks: { maxTicksLimit: 8 } },
                    y: { beginAtZero: false }
                },
                plugins: { legend: { display: true } }
            }
        };
    }

    function upsert(existing, canvas, config, labels, values) {
        if (!existing) {
            existing = new Chart(canvas, config);
        }
        existing.data.labels = labels;
        existing.data.datasets[0].data = values;
        existing.update();
        return existing;
    }

    async function refresh() {
        if (inFlight) {
            return;
        }
        inFlight = true;
        const controller = new AbortController();
        const timer = window.setTimeout(() => controller.abort(), 8000);
        try {
            const url = '/ajax/chart.php?id=' + encodeURIComponent(deviceId) + '&period=' + encodeURIComponent(period);
            const response = await fetch(url, {
                headers: { Accept: 'application/json' },
                signal: controller.signal
            });
            if (response.status === 401) {
                window.location.href = '/login.php';
                return;
            }
            if (!response.ok) {
                throw new Error('status');
            }
            const data = await response.json();
            const labels = data.labels || [];
            if (updated) {
                updated.textContent = data.updated_at ? 'Diperbarui ' + data.updated_at : '';
            }
            if (status) {
                status.textContent = '';
            }
            if (labels.length === 0) {
                empty.hidden = false;
                tempCanvas.hidden = true;
                humCanvas.hidden = true;
                return;
            }
            empty.hidden = true;
            tempCanvas.hidden = false;
            humCanvas.hidden = false;
            tempChart = upsert(tempChart, tempCanvas, baseOptions('Suhu (°C)', '#0b1f3a'), labels, data.temperature || []);
            humChart = upsert(humChart, humCanvas, baseOptions('Kelembapan (%)', '#0f766e'), labels, data.humidity || []);
        } catch (error) {
            if (status) {
                status.textContent = 'Gagal memuat grafik. Periksa jaringan, pembaruan dicoba lagi.';
            }
        } finally {
            window.clearTimeout(timer);
            inFlight = false;
        }
    }

    document.querySelectorAll('button[data-period]').forEach((button) => {
        button.addEventListener('click', () => {
            period = button.getAttribute('data-period') || '1h';
            document.querySelectorAll('button[data-period]').forEach((item) => {
                item.classList.toggle('active', item === button);
            });
            const next = new URL(window.location.href);
            next.searchParams.set('period', period);
            window.history.replaceState({}, '', next);
            refresh();
        });
    });

    refresh();
    window.setInterval(refresh, 10000);
})();
