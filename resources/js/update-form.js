/**
 * Station / SAT / GO status update (resources/views/erp/update-modal.blade.php).
 * Their part of ERP-001 — the PRC questions — sits in the same window: if any answer is
 * touched, the update also saves a new PRC version (partial answers allowed). The
 * Company's part (name, DOB, passport…) is shown read-only.
 */
import api from './api.js';
import { toast, escapeHtml, statusPill, fmtDate, openModal, closeModal } from './ui.js';
import { collectFields, contactPersonHtml, fillFields, nokFromManifest, partyHtml, routeText, PAX_TYPE_LABELS } from './erp.js';

/** Read-only line of the Company's manifest data. */
function passengerSummaryHtml(p) {
    const route = p.effectiveRoute || {};
    return [
        ['M/F/C/I', p.paxType && `${p.paxType} — ${PAX_TYPE_LABELS[p.paxType]}`],
        ['DOB', p.dateOfBirth && `${p.dateOfBirth}${p.age !== null && p.age !== '' ? ` (age ${p.age})` : ''}`],
        ['Nationality', p.nationality], ['Passport', p.passportNumber], ['CNIC', p.cnic],
        ['Route', routeText(route.from, route.to, route.via), true],
        ['Phone', (p.contactNumbers || []).join(', ')],
        ['Booking NOK', nokFromManifest(p)],
    ].filter(([, v]) => v)
     .map(([k, v, raw]) => `<span class="me-3"><span class="text-muted">${k}:</span> ${raw ? v : escapeHtml(v)}</span>`).join('')
     || '<span class="text-muted">No manifest details yet.</span>';
}

/** `prefix` is the role's API prefix ('/station' or '/go'). Returns openUpdate(passengerId). */
export function initUpdateForm({ prefix, onSaved } = {}) {
    const form = document.getElementById('cs-upd-form');
    const prc = document.getElementById('cs-upd-prc');
    let prcTouched = false;

    prc.addEventListener('input', () => { prcTouched = true; });
    prc.addEventListener('change', () => { prcTouched = true; });

    async function openUpdate(id) {
        let d;
        try { d = await api.get(`${prefix}/passengers/${id}/erp`); }
        catch (e) { toast(e.message, 'danger'); return; }
        const { passenger: p, incident: inc, recordCards: cards, updates, contactPerson } = d;

        document.getElementById('cs-upd-passenger-id').value = id;
        document.getElementById('cs-upd-name').textContent = `${p.name} (seat ${p.seatNumber || '—'})`;
        document.getElementById('cs-upd-incident').innerHTML = inc
            ? `${escapeHtml(inc.flightNumber)} · ${routeText(inc.fromLocation, inc.toLocation, inc.via)} · ${escapeHtml(inc.incidentDate)}`
            : 'No incident';
        const contact = document.getElementById('cs-upd-contact');
        contact.hidden = !contactPerson;
        if (contactPerson) contact.innerHTML = `<i class="bi bi-telephone"></i> <strong>Contact person:</strong> ${contactPersonHtml(contactPerson)}`;

        document.getElementById('cs-upd-status').value = updates[0]?.status || 'Safe';
        document.getElementById('cs-upd-remarks').value = '';

        // PRC answers start from the newest card; NOK from the booking if no card yet.
        document.getElementById('cs-upd-summary').innerHTML = passengerSummaryHtml(p);
        document.getElementById('cs-upd-party').innerHTML = partyHtml(p);
        const last = cards[0];
        fillFields(form, 'data-card', last || { nokDetails: nokFromManifest(p) });
        const lastSaved = document.getElementById('cs-upd-last-saved');
        lastSaved.hidden = !last;
        if (last) lastSaved.textContent = `Pre-filled from the answers saved ${fmtDate(last.createdAt)} by ${last.submittedBy}. Changing any answer saves a new version.`;
        document.getElementById('cs-upd-print').href = `/print/prc/passengers/${id}`;
        prc.open = false;
        prcTouched = false;

        document.getElementById('cs-upd-history-tbody').innerHTML = updates.length
            ? updates.map(u => `<tr><td class="small text-muted">${fmtDate(u.createdAt)}</td><td class="small">${escapeHtml(u.teamType || '')}</td><td>${escapeHtml(u.submittedBy)}</td><td>${statusPill(u.status)}</td><td class="small">${escapeHtml(u.remarks || '')}</td></tr>`).join('')
            : '<tr><td colspan="5" class="text-center text-muted small">No updates yet.</td></tr>';
        openModal('statusUpdateModal');
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const id = Number(document.getElementById('cs-upd-passenger-id').value);
        const body = {
            status: document.getElementById('cs-upd-status').value,
            remarks: document.getElementById('cs-upd-remarks').value.trim() || undefined,
        };
        if (prcTouched) body.prc = collectFields(form, 'data-card');
        try {
            const r = await api.post(`${prefix}/passengers/${id}/updates`, body);
            toast(r.recordCard ? 'Update and PRC answers saved' : 'Update saved');
            closeModal('statusUpdateModal');
            await onSaved?.();
        } catch (ex) { toast(ex.message, 'danger'); }
    });

    return openUpdate;
}
