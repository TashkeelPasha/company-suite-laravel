import api from '../api.js';
import { toast, escapeHtml, statusPill, fmtDate, openModal, closeModal } from '../ui.js';
import { mountTopbar } from '../topbar.js';
import { guardPage } from '../auth-gate.js';

export async function initErcDashboard() {
    const user = await guardPage('erc');
    if (!user) return;
    mountTopbar(user);
    document.getElementById('cs-company-name').textContent = user.companyName || '';

    let incidents = [];
    let selectedInc = null;

    async function loadIncidents() {
        try {
            incidents = await api.get('/erc/incidents');
            renderIncidentList(incidents);
        } catch (e) { toast(e.message, 'danger'); }
    }

    function renderIncidentList(list) {
        const container = document.getElementById('cs-erc-incidents-list');
        if (!list.length) {
            container.innerHTML = '<div class="cs-empty">No ongoing incidents.</div>';
            return;
        }
        container.innerHTML = list.map(i => `
            <button type="button" class="list-group-item list-group-item-action d-block w-100 border-0 border-bottom text-start"
                    data-id="${i.id}" style="background: transparent;">
                <div class="fw-semibold">${escapeHtml(i.flightNumber)}</div>
                <div class="small text-muted">${escapeHtml(i.fromLocation)} &rarr; ${escapeHtml(i.toLocation)}<br>${escapeHtml(i.incidentDate)} ${escapeHtml(i.incidentTime)}</div>
            </button>`).join('');
        container.querySelectorAll('button[data-id]').forEach(btn => btn.addEventListener('click', () => loadIncident(Number(btn.dataset.id))));
    }

    async function loadIncident(id) {
        try {
            const inc = await api.get(`/erc/incidents/${id}/passengers`);
            selectedInc = inc;
            document.getElementById('cs-erc-detail-empty').hidden = true;
            document.getElementById('cs-erc-detail').hidden = false;
            document.getElementById('cs-erc-inc-title').textContent = `${inc.flightNumber} — ${inc.fromLocation} → ${inc.toLocation}`;
            document.getElementById('cs-erc-inc-meta').textContent = `${inc.incidentDate} ${inc.incidentTime} · ${inc.passengers.length} passengers`;

            const tbody = document.getElementById('cs-erc-passengers-tbody');
            tbody.innerHTML = inc.passengers.map(p => `
                <tr>
                    <td>${escapeHtml(p.name)}</td>
                    <td>${escapeHtml(p.seatNumber)}</td>
                    <td class="text-muted small">${escapeHtml(p.cnic || '')}</td>
                    <td>${statusPill(p.latestStatus)}</td>
                    <td class="small">${
                        p.latestRelativeInfo
                            ? `${escapeHtml(p.latestRelativeInfo.contactName)} <span class="text-muted">(${escapeHtml(p.latestRelativeInfo.relationship)})</span>`
                            : '<span class="text-muted">&mdash;</span>'
                    }</td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-primary" data-id="${p.id}" data-name="${escapeHtml(p.name)}">Manage</button>
                    </td>
                </tr>`).join('');
            tbody.querySelectorAll('button[data-id]').forEach(btn => btn.addEventListener('click', () => openManage(btn)));
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

    async function openManage(btn) {
        const id = Number(btn.dataset.id);
        document.getElementById('cs-erc-relative-passenger-id').value = id;
        document.getElementById('cs-erc-relative-name').textContent = btn.dataset.name;
        // Reset form
        ['cs-erc-contact-name', 'cs-erc-relationship', 'cs-erc-phones', 'cs-erc-address'].forEach(id => document.getElementById(id).value = '');
        openModal('ercRelativeModal');

        try {
            const [updates, relHist] = await Promise.all([
                api.get(`/erc/passengers/${id}/updates`),
                api.get(`/erc/passengers/${id}/relative-info`),
            ]);
            document.getElementById('cs-erc-updates-tbody').innerHTML = updates.length
                ? updates.map(u => `<tr><td class="small text-muted">${fmtDate(u.createdAt)}</td><td>${escapeHtml(u.teamType || '')}</td><td>${escapeHtml(u.submittedBy)}</td><td>${statusPill(u.status)}</td><td class="small">${escapeHtml(u.remarks || '')}</td></tr>`).join('')
                : '<tr><td colspan="5" class="text-center text-muted small">No updates.</td></tr>';
            document.getElementById('cs-erc-relative-history-tbody').innerHTML = relHist.length
                ? relHist.map(r => `<tr><td class="small text-muted">${fmtDate(r.createdAt)}</td><td>${escapeHtml(r.updatedByName)}</td><td>${escapeHtml(r.contactName)}</td><td>${escapeHtml(r.relationship)}</td><td class="small">${escapeHtml(r.telephoneNumbers)}</td></tr>`).join('')
                : '<tr><td colspan="5" class="text-center text-muted small">No entries.</td></tr>';
            // Prefill form with newest relative info if available
            if (relHist.length) {
                const r = relHist[0];
                document.getElementById('cs-erc-contact-name').value = r.contactName || '';
                document.getElementById('cs-erc-relationship').value = r.relationship || '';
                document.getElementById('cs-erc-phones').value       = r.telephoneNumbers || '';
                document.getElementById('cs-erc-address').value      = r.address || '';
            }
        } catch {}
    }

    document.getElementById('cs-erc-relative-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const id   = Number(document.getElementById('cs-erc-relative-passenger-id').value);
        const body = {
            contactName:      document.getElementById('cs-erc-contact-name').value.trim(),
            relationship:     document.getElementById('cs-erc-relationship').value.trim(),
            telephoneNumbers: document.getElementById('cs-erc-phones').value.trim(),
            address:          document.getElementById('cs-erc-address').value.trim() || undefined,
        };
        try {
            await api.post(`/erc/passengers/${id}/relative-info`, body);
            toast('Relative info saved');
            closeModal('ercRelativeModal');
            if (selectedInc) await loadIncident(selectedInc.id);
        } catch (ex) { toast(ex.message, 'danger'); }
    });

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
