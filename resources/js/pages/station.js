import api from '../api.js';
import { toast, escapeHtml, statusPill, fmtDate } from '../ui.js';
import { initUpdateForm } from '../update-form.js';
import { mountTopbar } from '../topbar.js';
import { guardPage } from '../auth-gate.js';

export async function initStationDashboard() {
    const user = await guardPage('station');
    if (!user) return;
    mountTopbar(user);
    document.getElementById('cs-company-name').textContent = user.companyName || '';

    let all = [];
    const openUpdate = initUpdateForm({ prefix: '/station', onSaved: () => refresh() });

    async function refresh() {
        try {
            all = await api.get('/station/passengers');
            render(all);
        } catch (e) { toast(e.message, 'danger'); }
    }

    function render(rows) {
        const tbody = document.getElementById('cs-station-tbody');
        if (!rows.length) {
            tbody.innerHTML = '<tr><td colspan="7" class="cs-empty">No passengers.</td></tr>';
            return;
        }
        tbody.innerHTML = rows.map(p => `
            <tr>
                <td><a href="/passengers/${p.id}" class="fw-semibold text-decoration-none" title="Open passenger file">${escapeHtml(p.name)}</a></td>
                <td>${escapeHtml(p.seatNumber)}</td>
                <td class="text-muted small">${escapeHtml(p.flightNumber || '')}</td>
                <td>${escapeHtml(p.cnic)}</td>
                <td>${statusPill(p.latestStatus)}</td>
                <td class="text-muted small">${p.latestStatusAt ? fmtDate(p.latestStatusAt) : '—'}</td>
                <td class="text-end">
                    <button class="btn btn-sm btn-primary" data-id="${p.id}" title="Status update + PRC questions">
                        Update
                    </button>
                    <a class="btn btn-sm btn-outline-primary" href="/print/prc/passengers/${p.id}" target="_blank" rel="noopener"
                       title="View / print the Passenger Record Card (ERP-001) filled by the company">
                        <i class="bi bi-printer"></i> PRC
                    </a>
                </td>
            </tr>`).join('');
        tbody.querySelectorAll('button[data-id]').forEach(btn => btn.addEventListener('click', () => openUpdate(Number(btn.dataset.id))));
    }

    document.getElementById('cs-passenger-search').addEventListener('input', (e) => {
        const q = e.target.value.trim().toLowerCase();
        if (!q) return render(all);
        render(all.filter(p =>
            (p.name || '').toLowerCase().includes(q) ||
            (p.seatNumber || '').toLowerCase().includes(q) ||
            (p.cnic || '').toLowerCase().includes(q)
        ));
    });

    refresh();
}
