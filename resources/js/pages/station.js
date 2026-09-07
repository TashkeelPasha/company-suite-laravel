import api from '../api.js';
import { toast, escapeHtml, statusPill, fmtDate, openModal, closeModal } from '../ui.js';
import { mountTopbar } from '../topbar.js';
import { guardPage } from '../auth-gate.js';

export async function initStationDashboard() {
    const user = await guardPage('station');
    if (!user) return;
    mountTopbar(user);
    document.getElementById('cs-company-name').textContent = user.companyName || '';

    let all = [];

    async function refresh() {
        try {
            all = await api.get('/station/passengers');
            render(all);
        } catch (e) { toast(e.message, 'danger'); }
    }

    function render(rows) {
        const tbody = document.getElementById('cs-station-tbody');
        if (!rows.length) {
            tbody.innerHTML = '<tr><td colspan="6" class="cs-empty">No passengers.</td></tr>';
            return;
        }
        tbody.innerHTML = rows.map(p => `
            <tr>
                <td>${escapeHtml(p.name)}</td>
                <td>${escapeHtml(p.seatNumber)}</td>
                <td class="text-muted small">${escapeHtml(p.flightNumber || '')}</td>
                <td>${statusPill(p.latestStatus)}</td>
                <td class="text-muted small">${p.latestStatusAt ? fmtDate(p.latestStatusAt) : '—'}</td>
                <td class="text-end">
                    <button class="btn btn-sm btn-primary" data-id="${p.id}" data-name="${escapeHtml(p.name)}" data-seat="${escapeHtml(p.seatNumber)}">
                        Update
                    </button>
                </td>
            </tr>`).join('');
        tbody.querySelectorAll('button[data-id]').forEach(btn => btn.addEventListener('click', () => openUpdate(btn)));
    }

    async function openUpdate(btn) {
        const id = Number(btn.dataset.id);
        document.getElementById('cs-update-passenger-id').value = id;
        document.getElementById('cs-update-passenger-name').textContent = btn.dataset.name;
        document.getElementById('cs-update-passenger-seat').textContent = btn.dataset.seat;
        document.getElementById('cs-update-status').value = 'Safe';
        document.getElementById('cs-update-remarks').value = '';
        openModal('updateStatusModal');
        // Preload history
        try {
            const hist = await api.get(`/station/passengers/${id}/updates`);
            const hb = document.getElementById('cs-update-history-tbody');
            hb.innerHTML = hist.length
                ? hist.map(u => `<tr><td class="small text-muted">${fmtDate(u.createdAt)}</td><td>${escapeHtml(u.submittedBy)}</td><td>${statusPill(u.status)}</td><td class="small">${escapeHtml(u.remarks || '')}</td></tr>`).join('')
                : '<tr><td colspan="4" class="text-center text-muted small">No history.</td></tr>';
        } catch {}
    }

    document.getElementById('cs-update-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const id       = Number(document.getElementById('cs-update-passenger-id').value);
        const status   = document.getElementById('cs-update-status').value;
        const remarks  = document.getElementById('cs-update-remarks').value.trim();
        try {
            await api.post(`/station/passengers/${id}/updates`, { status, remarks: remarks || undefined });
            toast('Status updated');
            closeModal('updateStatusModal');
            await refresh();
        } catch (ex) { toast(ex.message, 'danger'); }
    });

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
