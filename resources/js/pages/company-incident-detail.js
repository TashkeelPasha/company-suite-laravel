import api from '../api.js';
import { toast, escapeHtml, statusPill, spinner, avatarHtml, fmtDay } from '../ui.js';
import { mountTopbar } from '../topbar.js';
import { guardPage } from '../auth-gate.js';
import * as XLSX from 'xlsx';
import { contactPersonHtml, routeText } from '../erp.js';
import { initPassengerForm } from '../passenger-form.js';

export async function initCompanyIncidentDetail() {
    const user = await guardPage('company');
    if (!user) return;
    mountTopbar(user);

    const root = document.querySelector('[data-cs-incident-id]');
    const incidentId = Number(root.dataset.csIncidentId);

    let currentIncident = null;
    let query = '';
    const openPassenger = initPassengerForm({ onSaved: () => refresh() });

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
        document.getElementById('cs-inc-title').textContent = inc.flightNumber;
        document.title = `${inc.flightNumber} · Incident · Company Suite`;
        const airport = (label, place) => `<div><div class="cs-airport-label">${label}</div><div class="cs-airport">${escapeHtml(place)}</div></div>`;
        const path = '<span class="cs-flight-path"><i class="bi bi-airplane-fill"></i></span>';
        document.getElementById('cs-inc-route').innerHTML = [
            airport('From', inc.fromLocation),
            inc.via ? airport('Via', inc.via) : null,
            airport('To', inc.toLocation),
        ].filter(Boolean).join(path);
        const other = document.getElementById('cs-inc-other');
        other.hidden = !inc.otherDetails;
        other.innerHTML = inc.otherDetails ? `<i class="bi bi-info-circle me-1"></i>${escapeHtml(inc.otherDetails)}` : '';
        document.getElementById('cs-inc-meta').innerHTML =
            `<i class="bi bi-calendar3 me-1"></i>${escapeHtml(fmtDay(inc.incidentDate))} at ${escapeHtml(inc.incidentTime || '')} &middot; <i class="bi bi-people me-1"></i>${inc.passengers.length} passengers`;
        const badge = document.getElementById('cs-inc-status-badge');
        badge.className = `cs-status-pill cs-status-${inc.status.replace(/[^A-Za-z0-9]/g, '-')}`;
        badge.textContent = inc.status;
        const toggle = document.getElementById('cs-inc-toggle-status');
        toggle.className = `btn ${inc.status === 'Ongoing' ? 'btn-warning' : 'btn-light border'}`;
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

    function renderPassengers(all) {
        const tbody = document.getElementById('cs-inc-passengers-tbody');
        document.getElementById('cs-pax-count').textContent = all.length;
        const q = query.toLowerCase();
        const passengers = !q ? all : all.filter(p => [p.name, p.seatNumber, p.cnic].some(v => (v || '').toLowerCase().includes(q)));
        if (!passengers.length) {
            tbody.innerHTML = q
                ? '<tr><td colspan="5" class="cs-empty"><i class="bi bi-search"></i><div class="cs-empty-title">No matching passengers</div>Try a name, seat or CNIC.</td></tr>'
                : '<tr><td colspan="5" class="cs-empty"><i class="bi bi-person-vcard"></i><div class="cs-empty-title">No passengers yet</div>Upload the Excel manifest when creating an incident.</td></tr>';
            return;
        }
        const team = (label, status) => `<span>${label}</span><span>${statusPill(status)}</span>`;
        tbody.innerHTML = passengers.map(p => `
            <tr>
                <td>
                    <div class="d-flex align-items-center gap-3">
                        ${avatarHtml(p.name)}
                        <div class="min-w-0">
                            <a href="/passengers/${p.id}" class="cs-cell-main d-block text-truncate" style="max-width:16rem" title="Open passenger file">${escapeHtml(p.name)}</a>
                            <div class="cs-cell-sub">Seat ${escapeHtml(p.seatNumber || '—')}${p.cnic ? ` &middot; ${escapeHtml(p.cnic)}` : ''}</div>
                        </div>
                    </div>
                </td>
                <td>${statusPill(p.latestStatus)}</td>
                <td><div class="cs-team-status">${team('Station', p.latestStationStatus)}${team('GO', p.latestGoStatus)}${team('SAT', p.latestSatStatus)}</div></td>
                <td class="small">${
                    p.latestPif
                        ? contactPersonHtml(p.latestPif)
                        : p.latestRelativeInfo
                            ? `${escapeHtml(p.latestRelativeInfo.contactName)} <span class="text-muted">(${escapeHtml(p.latestRelativeInfo.relationship)})</span>`
                            : '<span class="text-muted">&mdash;</span>'
                }</td>
                <td class="text-end">${formLinks(p)}</td>
            </tr>`).join('');
        tbody.querySelectorAll('button[data-edit-id]').forEach(btn => btn.addEventListener('click', () => openPassenger(Number(btn.dataset.editId))));
    }

    /** Company edits only its own part (manifest). PRC answers come from Station / SAT / GO, PIF from ERC — print both. */
    function formLinks(p) {
        const edit = `<button type="button" class="btn btn-soft btn-icon" data-edit-id="${p.id}" title="Edit passenger details (Company's part of ERP-001)" aria-label="Edit ${escapeHtml(p.name)}"><i class="bi bi-pencil"></i></button>`;
        const print = (form, count) => `<a class="btn btn-sm ${count ? 'btn-soft-primary' : 'btn-soft'}" target="_blank" rel="noopener"
            href="/print/${form}/passengers/${p.id}" title="${count ? `Print (${count} saved)` : 'Nothing answered yet — prints the company data only'}"><i class="bi bi-printer"></i> ${form.toUpperCase()}</a>`;
        return `<div class="cs-row-actions">${edit}${print('prc', p.prcCount)}${print('pif', p.pifCount)}</div>`;
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

    document.getElementById('cs-inc-export-xlsx').addEventListener('click', async () => {
        if (!currentIncident) return;
        // Full manifest fields come from /manifest; statuses from the incident payload.
        let manifest;
        try { manifest = await api.get(`/company/incidents/${incidentId}/manifest`); }
        catch (e) { toast(e.message, 'danger'); return; }
        const byId = new Map(manifest.passengers.map(m => [m.id, m]));
        const rows = currentIncident.passengers.map(p => ({ ...byId.get(p.id), ...p })).map(p => ({
            Name: p.name,
            'Last name': p.lastName || '',
            'First name': p.firstName || '',
            Seat: p.seatNumber,
            'M/F/C/I': p.paxType || '',
            DOB: p.dateOfBirth || '',
            Age: p.age ?? '',
            Nationality: p.nationality || '',
            CNIC: p.cnic || '',
            PNR: p.pnr || '',
            'Route': [p.effectiveRoute?.from, p.effectiveRoute?.via, p.effectiveRoute?.to].filter(Boolean).join(' > '),
            'Contact no(s)': (p.contactNumbers || []).join(', '),
            'Passport no': p.passportNumber || '',
            'Award Plus no': p.awardPlusNumber || '',
            'PRC count': p.prcCount || 0,
            'PIF count': p.pifCount || 0,
            'Contact person (PIF)': p.latestPif ? `${p.latestPif.informantName} (${p.latestPif.informantRelationship}) ${(p.latestPif.contactNumbers || []).join(', ')}`.trim() : '',
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

    document.getElementById('cs-pax-search').addEventListener('input', (e) => {
        query = e.target.value.trim();
        if (currentIncident) renderPassengers(currentIncident.passengers);
    });

    refresh();
}
