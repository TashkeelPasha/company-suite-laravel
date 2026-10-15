import api from '../api.js';
import { toast, escapeHtml, statusPill, avatarHtml, fmtDay } from '../ui.js';
import { initPifForm } from '../pif-form.js';
import { mountTopbar } from '../topbar.js';
import { guardPage } from '../auth-gate.js';
import { contactPersonHtml, routeText } from '../erp.js';

export async function initErcDashboard() {
    const user = await guardPage('erc');
    if (!user) return;
    mountTopbar(user);
    document.getElementById('cs-company-name').textContent = user.companyName || '';

    let incidents = [];
    let selectedInc = null;
    let filter = 'all';
    let query = '';
    const openPif = initPifForm({ onSaved: () => selectedInc && loadIncident(selectedInc.id) });

    async function loadIncidents() {
        try {
            incidents = await api.get('/erc/incidents');
            document.getElementById('cs-erc-kpi-incidents').textContent = incidents.length;
            renderIncidentList(incidents);
            // Most recent incident is open by default — ERC usually works on one at a time.
            if (incidents.length) loadIncident(incidents[0].id);
        } catch (e) { toast(e.message, 'danger'); }
    }

    function renderIncidentList(list) {
        const container = document.getElementById('cs-erc-incidents-list');
        if (!list.length) {
            container.innerHTML = incidents.length
                ? '<div class="cs-empty"><i class="bi bi-search"></i><div class="cs-empty-title">No match</div>Try another flight or city.</div>'
                : '<div class="cs-empty"><i class="bi bi-check2-circle"></i><div class="cs-empty-title">No ongoing incidents</div>Nothing needs the call centre right now.</div>';
            return;
        }
        container.innerHTML = list.map(i => `
            <button type="button" class="cs-inc-item${selectedInc?.id === i.id ? ' is-active' : ''}" data-id="${i.id}">
                <span class="cs-flight-tile"><i class="bi bi-airplane-fill"></i></span>
                <span class="min-w-0 flex-grow-1">
                    <span class="d-flex justify-content-between align-items-center gap-2">
                        <span class="cs-cell-main">${escapeHtml(i.flightNumber)}</span>
                        <span class="cs-count m-0">${i.passengerCount ?? 0} pax</span>
                    </span>
                    <span class="d-block small text-body-secondary text-truncate">${routeText(i.fromLocation, i.toLocation, i.via)}</span>
                    <span class="cs-cell-sub d-block">${escapeHtml(fmtDay(i.incidentDate))} &middot; ${escapeHtml(i.incidentTime || '')}</span>
                </span>
            </button>`).join('');
        container.querySelectorAll('button[data-id]').forEach(btn => btn.addEventListener('click', () => loadIncident(Number(btn.dataset.id))));
    }

    function renderPassengers() {
        const all = selectedInc?.passengers || [];
        const set = all.filter(p => p.latestPif);
        document.getElementById('cs-erc-kpi-pax').textContent = all.length;
        document.getElementById('cs-erc-kpi-set').textContent = set.length;
        document.getElementById('cs-erc-kpi-pending').textContent = all.length - set.length;
        document.getElementById('cs-erc-pax-count').textContent = all.length;

        const q = query.toLowerCase();
        const rows = all
            .filter(p => filter === 'all' || (filter === 'set') === !!p.latestPif)
            .filter(p => !q || [p.name, p.seatNumber, p.cnic].some(v => (v || '').toLowerCase().includes(q)));
        const tbody = document.getElementById('cs-erc-passengers-tbody');
        if (!rows.length) {
            tbody.innerHTML = filter === 'pending' && !q
                ? '<tr><td colspan="4" class="cs-empty"><i class="bi bi-check2-all"></i><div class="cs-empty-title">Every passenger has a contact person</div>Nice work.</td></tr>'
                : '<tr><td colspan="4" class="cs-empty"><i class="bi bi-search"></i><div class="cs-empty-title">No matching passengers</div>Change the filter or search.</td></tr>';
            return;
        }
        tbody.innerHTML = rows.map(p => `
            <tr>
                <td>
                    <div class="d-flex align-items-center gap-3">
                        ${avatarHtml(p.name)}
                        <div class="min-w-0">
                            <a href="/passengers/${p.id}" class="cs-cell-main d-block text-truncate" style="max-width:13rem" title="Open passenger file">${escapeHtml(p.name)}</a>
                            <div class="cs-cell-sub">Seat ${escapeHtml(p.seatNumber || '—')}${p.cnic ? ` &middot; ${escapeHtml(p.cnic)}` : ''}</div>
                        </div>
                    </div>
                </td>
                <td>${statusPill(p.latestStatus)}</td>
                <td class="small">${
                    p.latestPif
                        ? `<span class="d-inline-flex align-items-start gap-2"><i class="bi bi-person-check text-success mt-1"></i><span>${contactPersonHtml(p.latestPif)}</span></span>`
                        : p.latestRelativeInfo
                            ? `${escapeHtml(p.latestRelativeInfo.contactName)} <span class="text-muted">(${escapeHtml(p.latestRelativeInfo.relationship)}, earlier entry)</span>`
                            : '<span class="cs-team-chip cs-tone-amber"><i class="bi bi-hourglass-split"></i> Awaiting</span>'
                }</td>
                <td class="text-end">
                    <button class="btn btn-sm ${p.latestPif ? 'btn-soft-primary' : 'btn-primary'}" data-id="${p.id}" title="${p.latestPif ? 'View or edit the contact person' : 'Set the contact person'}">
                        <i class="bi ${p.latestPif ? 'bi-pencil' : 'bi-telephone-plus'}"></i> ${p.latestPif ? 'Edit' : 'Set contact'}
                    </button>
                </td>
            </tr>`).join('');
        tbody.querySelectorAll('button[data-id]').forEach(btn => btn.addEventListener('click', () => openPif(Number(btn.dataset.id))));
    }

    async function loadIncident(id) {
        try {
            const inc = await api.get(`/erc/incidents/${id}/passengers`);
            selectedInc = inc;
            document.querySelectorAll('#cs-erc-incidents-list .cs-inc-item').forEach(b => b.classList.toggle('is-active', Number(b.dataset.id) === id));
            document.getElementById('cs-erc-detail-empty').hidden = true;
            document.getElementById('cs-erc-detail').hidden = false;
            document.getElementById('cs-erc-inc-title').innerHTML = `Flight ${escapeHtml(inc.flightNumber)} <span class="cs-status-pill cs-status-Ongoing ms-1 align-middle">Ongoing</span>`;
            document.getElementById('cs-erc-inc-meta').innerHTML =
                `<span class="cs-route">${routeText(inc.fromLocation, inc.toLocation, inc.via)}</span> &middot; <i class="bi bi-calendar3"></i> ${escapeHtml(fmtDay(inc.incidentDate))} ${escapeHtml(inc.incidentTime || '')}`
                + (inc.otherDetails ? `<div class="small mt-1"><i class="bi bi-info-circle me-1"></i>${escapeHtml(inc.otherDetails)}</div>` : '');
            renderPassengers();
        } catch (e) {
            if (e.status === 403) {
                toast('Incident is completed — ERC no longer has access.', 'warning');
                document.getElementById('cs-erc-detail').hidden = true;
                document.getElementById('cs-erc-detail-empty').hidden = false;
            } else {
                toast(e.message, 'danger');
            }
        }
    }

    document.querySelectorAll('.cs-filter [data-filter]').forEach(btn => btn.addEventListener('click', () => {
        filter = btn.dataset.filter;
        document.querySelectorAll('.cs-filter [data-filter]').forEach(b => b.classList.toggle('is-active', b === btn));
        renderPassengers();
    }));
    document.getElementById('cs-erc-pax-search').addEventListener('input', (e) => { query = e.target.value.trim(); renderPassengers(); });

    document.getElementById('cs-erc-incident-search').addEventListener('input', (e) => {
        const q = e.target.value.trim().toLowerCase();
        if (!q) return renderIncidentList(incidents);
        renderIncidentList(incidents.filter(i =>
            (i.flightNumber || '').toLowerCase().includes(q) ||
            (i.fromLocation || '').toLowerCase().includes(q) ||
            (i.toLocation || '').toLowerCase().includes(q)));
    });

    loadIncidents();
}
