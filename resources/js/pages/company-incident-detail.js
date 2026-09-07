import api from '../api.js';
import { toast, escapeHtml, statusPill, spinner } from '../ui.js';
import { mountTopbar } from '../topbar.js';
import { guardPage } from '../auth-gate.js';
import * as XLSX from 'xlsx';

export async function initCompanyIncidentDetail() {
    const user = await guardPage('company');
    if (!user) return;
    mountTopbar(user);

    const root = document.querySelector('[data-cs-incident-id]');
    const incidentId = Number(root.dataset.csIncidentId);

    let currentIncident = null;

    async function refresh() {
        try {
            const inc = await api.get(`/company/incidents/${incidentId}`);
            currentIncident = inc;
            renderHeader(inc);
            renderStats(inc.passengers);
            renderPassengers(inc.passengers);
        } catch (e) { toast(e.message, 'danger'); }
    }

    function renderHeader(inc) {
        document.getElementById('cs-inc-title').textContent = `${inc.flightNumber} — ${inc.fromLocation} → ${inc.toLocation}`;
        document.getElementById('cs-inc-meta').textContent = `${inc.incidentDate} ${inc.incidentTime} · ${inc.passengers.length} passengers`;
        const badge = document.getElementById('cs-inc-status-badge');
        badge.className = `cs-status-pill cs-status-${inc.status.replace(/[^A-Za-z0-9]/g, '-')}`;
        badge.textContent = inc.status;
        document.getElementById('cs-inc-toggle-label').textContent =
            inc.status === 'Ongoing' ? 'Mark complete' : 'Reopen';
    }

    function renderStats(passengers) {
        const counts = { Safe: 0, Injured: 0, Critical: 0, Deceased: 0, Unconfirmed: 0, None: 0 };
        passengers.forEach(p => counts[p.latestStatus || 'None']++);
        document.getElementById('cs-stat-total').textContent   = passengers.length;
        document.getElementById('cs-stat-safe').textContent    = counts.Safe;
        document.getElementById('cs-stat-injured').textContent = counts.Injured + counts.Critical;
        document.getElementById('cs-stat-other').textContent   = counts.Deceased + counts.Unconfirmed;
    }

    function renderPassengers(passengers) {
        const tbody = document.getElementById('cs-inc-passengers-tbody');
        if (!passengers.length) {
            tbody.innerHTML = '<tr><td colspan="8" class="cs-empty">No passengers yet.</td></tr>';
            return;
        }
        tbody.innerHTML = passengers.map(p => `
            <tr>
                <td>${escapeHtml(p.name)}</td>
                <td>${escapeHtml(p.seatNumber)}</td>
                <td class="small text-muted">${escapeHtml(p.cnic || '')}</td>
                <td>${statusPill(p.latestStatus)}</td>
                <td>${statusPill(p.latestStationStatus)}</td>
                <td>${statusPill(p.latestGoStatus)}</td>
                <td>${statusPill(p.latestSatStatus)}</td>
                <td class="small">${
                    p.latestRelativeInfo
                        ? `${escapeHtml(p.latestRelativeInfo.contactName)} <span class="text-muted">(${escapeHtml(p.latestRelativeInfo.relationship)})</span>`
                        : '<span class="text-muted">&mdash;</span>'
                }</td>
            </tr>`).join('');
    }

    document.getElementById('cs-inc-toggle-status').addEventListener('click', async () => {
        if (!currentIncident) return;
        const next = currentIncident.status === 'Ongoing' ? 'Operation Completed' : 'Ongoing';
        spinner(true);
        try {
            await api.patch(`/company/incidents/${incidentId}/status`, { status: next });
            toast(`Status set to "${next}"`);
            await refresh();
        } catch (e) { toast(e.message, 'danger'); }
        finally { spinner(false); }
    });

    document.getElementById('cs-inc-export-xlsx').addEventListener('click', () => {
        if (!currentIncident) return;
        const rows = currentIncident.passengers.map(p => ({
            Name: p.name,
            Seat: p.seatNumber,
            CNIC: p.cnic || '',
            'Latest status': p.latestStatus || '',
            'Station status': p.latestStationStatus || '',
            'GO status': p.latestGoStatus || '',
            'SAT status': p.latestSatStatus || '',
            'Next-of-kin contact': p.latestRelativeInfo?.contactName || '',
            'Relationship': p.latestRelativeInfo?.relationship || '',
            'Phone(s)': p.latestRelativeInfo?.telephoneNumbers || '',
        }));
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, XLSX.utils.json_to_sheet(rows), 'Passengers');
        XLSX.writeFile(wb, `incident-${currentIncident.flightNumber}-${currentIncident.incidentDate}.xlsx`);
    });

    refresh();
}
