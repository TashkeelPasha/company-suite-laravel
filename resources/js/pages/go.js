import api from '../api.js';
import { toast, escapeHtml, statusPill, fmtDate, openModal, closeModal } from '../ui.js';
import { mountTopbar } from '../topbar.js';
import { guardPage } from '../auth-gate.js';

export async function initGoDashboard() {
    const user = await guardPage('go');
    if (!user) return;
    mountTopbar(user);
    document.getElementById('cs-company-name').textContent = user.companyName || '';

    let incidents = [];
    let selectedInc = null;

    async function loadIncidents() {
        try {
            incidents = await api.get('/go/incidents');
            renderIncidentList(incidents);
        } catch (e) { toast(e.message, 'danger'); }
    }

    function renderIncidentList(list) {
        const container = document.getElementById('cs-go-incidents-list');
        if (!list.length) {
            container.innerHTML = '<div class="cs-empty">No incidents.</div>';
            return;
        }
        container.innerHTML = list.map(i => `
            <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-start w-100 border-0 border-bottom text-start"
                    data-id="${i.id}" style="background: transparent;">
                <div>
                    <div class="fw-semibold">${escapeHtml(i.flightNumber)}</div>
                    <div class="small text-muted">${escapeHtml(i.fromLocation)} &rarr; ${escapeHtml(i.toLocation)}<br>${escapeHtml(i.incidentDate)} ${escapeHtml(i.incidentTime)}</div>
                </div>
                <span class="cs-status-pill cs-status-${i.status.replace(/[^A-Za-z0-9]/g, '-')}">${escapeHtml(i.status)}</span>
            </button>`).join('');
        container.querySelectorAll('button[data-id]').forEach(btn => btn.addEventListener('click', () => loadIncident(Number(btn.dataset.id))));
    }

    async function loadIncident(id) {
        try {
            const inc = await api.get(`/go/incidents/${id}/passengers`);
            selectedInc = inc;
            document.getElementById('cs-go-detail-empty').hidden = true;
            document.getElementById('cs-go-detail').hidden = false;
            document.getElementById('cs-go-inc-title').textContent = `${inc.flightNumber} — ${inc.fromLocation} → ${inc.toLocation}`;
            document.getElementById('cs-go-inc-meta').textContent = `${inc.incidentDate} ${inc.incidentTime} · ${inc.passengers.length} passengers`;
            const badge = document.getElementById('cs-go-inc-status');
            badge.className = `cs-status-pill cs-status-${inc.status.replace(/[^A-Za-z0-9]/g, '-')}`;
            badge.textContent = inc.status;

            const tbody = document.getElementById('cs-go-passengers-tbody');
            tbody.innerHTML = inc.passengers.map(p => `
                <tr>
                    <td>${escapeHtml(p.name)}</td>
                    <td>${escapeHtml(p.seatNumber)}</td>
                    <td>${statusPill(p.latestStatus)}</td>
                    <td>${statusPill(p.latestStationStatus)}</td>
                    <td>${statusPill(p.latestSatStatus)}</td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-primary" data-id="${p.id}" data-name="${escapeHtml(p.name)}">Update</button>
                    </td>
                </tr>`).join('');
            tbody.querySelectorAll('button[data-id]').forEach(btn => btn.addEventListener('click', () => openUpdate(btn)));
        } catch (e) { toast(e.message, 'danger'); }
    }

    async function openUpdate(btn) {
        const id = Number(btn.dataset.id);
        document.getElementById('cs-go-update-passenger-id').value = id;
        document.getElementById('cs-go-update-name').textContent = btn.dataset.name;
        document.getElementById('cs-go-update-status').value = 'Safe';
        document.getElementById('cs-go-update-remarks').value = '';
        openModal('goUpdateStatusModal');
        try {
            const hist = await api.get(`/go/passengers/${id}/updates`);
            const hb = document.getElementById('cs-go-history-tbody');
            hb.innerHTML = hist.length
                ? hist.map(u => `<tr><td class="small text-muted">${fmtDate(u.createdAt)}</td><td>${escapeHtml(u.submittedBy)}</td><td>${statusPill(u.status)}</td><td class="small">${escapeHtml(u.remarks || '')}</td></tr>`).join('')
                : '<tr><td colspan="4" class="text-center text-muted small">No history.</td></tr>';
        } catch {}
    }

    document.getElementById('cs-go-update-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const id      = Number(document.getElementById('cs-go-update-passenger-id').value);
        const status  = document.getElementById('cs-go-update-status').value;
        const remarks = document.getElementById('cs-go-update-remarks').value.trim();
        try {
            await api.post(`/go/passengers/${id}/updates`, { status, remarks: remarks || undefined });
            toast('Update recorded');
            closeModal('goUpdateStatusModal');
            if (selectedInc) await loadIncident(selectedInc.id);
        } catch (ex) { toast(ex.message, 'danger'); }
    });

    document.getElementById('cs-go-incident-search').addEventListener('input', (e) => {
        const q = e.target.value.trim().toLowerCase();
        if (!q) return renderIncidentList(incidents);
        renderIncidentList(incidents.filter(i =>
            (i.flightNumber || '').toLowerCase().includes(q) ||
            (i.fromLocation || '').toLowerCase().includes(q) ||
            (i.toLocation || '').toLowerCase().includes(q)));
    });

    loadIncidents();
}
