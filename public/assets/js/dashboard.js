(function () {
    const tableBody = document.getElementById('device-rows');
    if (!tableBody) {
        return;
    }

    let inFlight = false;

    function badge(className, label) {
        const span = document.createElement('span');
        span.className = className;
        span.textContent = label;
        return span;
    }

    function cell(text) {
        const td = document.createElement('td');
        td.textContent = text;
        return td;
    }

    function render(data) {
        const summary = data.summary || {};
        const map = {
            'stat-total': summary.total,
            'stat-online': summary.online,
            'stat-offline': summary.offline,
            'stat-warnings': summary.warnings,
            'updated-at': data.updated_at || ''
        };
        Object.keys(map).forEach((id) => {
            const el = document.getElementById(id);
            if (el) {
                el.textContent = id === 'updated-at' && map[id] ? 'Diperbarui ' + map[id] : String(map[id] ?? '');
            }
        });

        const status = document.getElementById('poll-status');
        if (status) {
            status.textContent = '';
        }

        tableBody.replaceChildren();
        const devices = Array.isArray(data.devices) ? data.devices : [];
        if (devices.length === 0) {
            const tr = document.createElement('tr');
            const td = document.createElement('td');
            td.colSpan = 9;
            td.className = 'empty-state';
            td.textContent = 'Belum ada perangkat. Daftarkan sensor dari menu Perangkat Sensor.';
            tr.append(td);
            tableBody.append(tr);
            return;
        }

        const tempClass = { normal: 'badge-normal', below_min: 'badge-low', above_max: 'badge-high' };
        const connClass = { online: 'badge-online', offline: 'badge-muted', inactive: 'badge-muted', never: 'badge-muted' };

        devices.forEach((device) => {
            const id = Number(device.id);
            const tr = document.createElement('tr');
            tr.append(
                cell(device.name || ''),
                cell(device.device_id || ''),
                cell(device.location || '')
            );

            const tempCell = document.createElement('td');
            tempCell.textContent = device.temperature === null ? '—' : device.temperature + ' °C';
            if (device.stale && device.temperature !== null) {
                const note = document.createElement('span');
                note.className = 'stale-note';
                note.textContent = 'Data terakhir, bukan kondisi saat ini';
                tempCell.append(note);
            }
            tr.append(tempCell);

            tr.append(cell(device.humidity === null ? '—' : device.humidity + ' %'));

            const tempStatus = document.createElement('td');
            if (device.temperature_status) {
                tempStatus.append(badge(tempClass[device.temperature_status] || 'badge-muted', device.temperature_label || ''));
            } else {
                tempStatus.textContent = '—';
            }
            tr.append(tempStatus);

            const conn = document.createElement('td');
            conn.append(badge(connClass[device.connection_status] || 'badge-muted', device.connection_label || ''));
            tr.append(conn);
            tr.append(cell(device.last_seen_label || '—'));

            if (tableBody.dataset.actions === '1' && Number.isInteger(id) && id > 0) {
                const actions = document.createElement('td');
                const wrap = document.createElement('div');
                wrap.className = 'd-flex gap-2';
                const detail = document.createElement('a');
                detail.className = 'btn btn-sm btn-outline-secondary';
                detail.href = '/devices/show.php?id=' + id;
                detail.textContent = 'Detail';
                const chart = document.createElement('a');
                chart.className = 'btn btn-sm btn-teal';
                chart.href = '/monitoring/detail.php?id=' + id;
                chart.textContent = 'Grafik';
                wrap.append(detail, chart);
                actions.append(wrap);
                tr.append(actions);
            }
            tableBody.append(tr);
        });
    }

    async function refresh() {
        if (inFlight) {
            return;
        }
        inFlight = true;
        const controller = new AbortController();
        const timer = window.setTimeout(() => controller.abort(), 8000);
        try {
            const response = await fetch('/ajax/dashboard.php', {
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
            render(await response.json());
        } catch (error) {
            const status = document.getElementById('poll-status');
            if (status) {
                status.textContent = 'Gagal memuat pembaruan. Mencoba lagi.';
            }
        } finally {
            window.clearTimeout(timer);
            inFlight = false;
        }
    }

    refresh();
    window.setInterval(refresh, 10000);
})();
