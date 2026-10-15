import api from '../api.js';
import { toast, escapeHtml, statusPill } from '../ui.js';
import { initUpdateForm } from '../update-form.js';
import { mountTopbar } from '../topbar.js';
import { guardPage } from '../auth-gate.js';
import { contactPersonHtml, routeText } from '../erp.js';

export async function initGoDashboard() {
    const user = await guardPage('go');
    if (!user) return;
    mountTopbar(user);
    document.getElementById('cs-company-name').textContent = user.companyName || '';

    let incidents = [];
    let selectedInc = null;
    const openUpdate = initUpdateForm({ prefix: '/go', onSaved: () => selectedInc && loadIncident(selectedInc.id) });

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
                    <div class="small text-muted">${routeText(i.fromLocation, i.toLocation, i.via)}<br>${escapeHtml(i.incidentDate)} ${escapeHtml(i.incidentTime)}</div>
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
            document.getElementById('cs-go-inc-title').innerHTML = `${escapeHtml(inc.flightNumber)} — ${routeText(inc.fromLocation, inc.toLocation, inc.via)}`;
            document.getElementById('cs-go-print-all').href = `/print/prc/incidents/${inc.id}`;
            const other = document.getElementById('cs-go-inc-other');
            other.hidden = !inc.otherDetails;
            other.textContent = inc.otherDetails || '';
            document.getElementById('cs-go-inc-meta').textContent = `${inc.incidentDate} ${inc.incidentTime} · ${inc.passengers.length} passengers`;
            const badge = document.getElementById('cs-go-inc-status');
            badge.className = `cs-status-pill cs-status-${inc.status.replace(/[^A-Za-z0-9]/g, '-')}`;
            badge.textContent = inc.status;

            const tbody = document.getElementById('cs-go-passengers-tbody');
            tbody.innerHTML = inc.passengers.map(p => `
                <tr>
                    <td><a href="/passengers/${p.id}" class="fw-semibold text-decoration-none" title="Open passenger file">${escapeHtml(p.name)}</a></td>
                    <td>${escapeHtml(p.seatNumber)}</td>
                    <td>${statusPill(p.latestStatus)}</td>
                    <td>${statusPill(p.latestStationStatus)}</td>
                    <td>${statusPill(p.latestSatStatus)}</td>
                    <td class="small">${contactPersonHtml(p.latestPif)}</td>
                    <td class="text-nowrap">${formLinks(p)}</td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-primary" data-id="${p.id}" title="Status update + PRC questions">Update</button>
                    </td>
                </tr>`).join('');
            tbody.querySelectorAll('button[data-id]').forEach(btn => btn.addEventListener('click', () => openUpdate(Number(btn.dataset.id))));
        } catch (e) { toast(e.message, 'danger'); }
    }

    /** Print PRC / PIF (PRC answers are given through Update). */
    function formLinks(p) {
        const link = (form, count) => `<a class="btn btn-sm ${count ? 'btn-outline-primary' : 'btn-outline-secondary'} py-0" target="_blank" rel="noopener"
            href="/print/${form}/passengers/${p.id}" title="${count ? `${count} saved` : 'None saved — opens a pre-filled sheet'}">${form.toUpperCase()}${count ? ` ${count}` : ''}</a>`;
        return `${link('prc', p.prcCount)} ${link('pif', p.pifCount)}`;
    }

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
