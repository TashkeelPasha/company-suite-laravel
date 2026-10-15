/**
 * ERP-002 = the passenger's ONE contact person (resources/views/erp/pif-modal.blade.php).
 * ERC sets them on the first call; later saves edit them (new version). Other callers
 * are referred to that person. Replacing the person needs a reason.
 */
import api from './api.js';
import { toast, escapeHtml, statusPill, fmtDate, openModal, closeModal } from './ui.js';
import { collectFields, contactPersonHtml, fillFields, nokFromManifest, partyHtml, routeText, PAX_TYPE_LABELS } from './erp.js';

const norm = (s) => String(s ?? '').trim().replace(/\s+/g, ' ').toLowerCase();

/** Returns openPif(passengerId). `onSaved` runs after a successful save. */
export function initPifForm({ onSaved } = {}) {
    const pifForm = document.getElementById('cs-pif-form');
    const reasonWrap = document.getElementById('cs-pif-reason-wrap');
    const reason = document.getElementById('cs-pif-reason');
    let current = null;   // newest saved form = the contact person

    const isNewPerson = () => current && (
        norm(pifForm.querySelector('[data-card="informantFirstName"]').value) !== norm(current.informantFirstName) ||
        norm(pifForm.querySelector('[data-card="informantLastName"]').value) !== norm(current.informantLastName));
    const syncReason = () => { const show = !!isNewPerson(); reasonWrap.hidden = !show; reason.required = show; };
    pifForm.querySelectorAll('[data-card="informantFirstName"], [data-card="informantLastName"]').forEach(el => el.addEventListener('input', syncReason));

    async function openPif(id) {
        let d, legacy;
        try {
            [d, legacy] = await Promise.all([
                api.get(`/erc/passengers/${id}/erp`),
                api.get(`/erc/passengers/${id}/relative-info`),
            ]);
        } catch (e) { toast(e.message, 'danger'); return; }
        const { passenger: p, incident: inc, informationForms: forms, updates } = d;
        current = forms[0] || null;

        document.getElementById('cs-pif-passenger-id').value = id;
        document.getElementById('cs-pif-name').textContent = p.name;
        document.getElementById('cs-pif-incident').innerHTML = inc
            ? `${escapeHtml(inc.flightNumber)} · ${routeText(inc.fromLocation, inc.toLocation, inc.via)} · ${escapeHtml(inc.incidentDate)}`
            : 'No incident';
        document.getElementById('cs-pif-passenger').innerHTML = [
            ['Name', p.name], ['Seat', p.seatNumber], ['M/F/C/I', p.paxType && `${p.paxType} — ${PAX_TYPE_LABELS[p.paxType]}`],
            ['DOB', p.dateOfBirth], ['Age', p.age], ['Nationality', p.nationality], ['CNIC', p.cnic],
            ['Route', routeText(p.effectiveRoute.from, p.effectiveRoute.to, p.effectiveRoute.via)],
        ].filter(([, v]) => v !== null && v !== undefined && v !== '')
         .map(([k, v]) => `<span class="me-3"><span class="text-muted">${k}:</span> ${k === 'Route' ? v : escapeHtml(v)}</span>`).join('');
        document.getElementById('cs-pif-party').innerHTML = partyHtml(p);
        document.getElementById('cs-pif-print').href = `/print/pif/passengers/${id}`;

        const banner = document.getElementById('cs-pif-current');
        banner.hidden = !current;
        if (current) {
            banner.innerHTML = `<i class="bi bi-pin-angle"></i> <strong>Contact person:</strong> ${contactPersonHtml({ name: `${current.informantFirstName} ${current.informantLastName}`, relationship: current.informantRelationship, contactNumbers: current.informantContactNumbers })}
                <div class="mt-1">Information about this passenger is given <strong>only to this person</strong>. Ask any other caller to get updates from them.
                Edit below to correct their details; type a different name only to replace them.</div>`;
        }
        // Existing contact person → edit it. None yet → start from the manifest NOK.
        fillFields(pifForm, 'data-card', current || { nokDetails: nokFromManifest(p) });
        reason.value = '';
        syncReason();
        document.getElementById('cs-pif-submit').textContent = current ? 'Save changes' : 'Set contact person';

        document.getElementById('cs-erc-updates-tbody').innerHTML = updates.length
            ? updates.map(u => `<tr><td class="small text-muted">${fmtDate(u.createdAt)}</td><td>${escapeHtml(u.teamType || '')}</td><td>${escapeHtml(u.submittedBy)}</td><td>${statusPill(u.status)}</td><td class="small">${escapeHtml(u.remarks || '')}</td></tr>`).join('')
            : '<tr><td colspan="5" class="text-center text-muted small">No updates.</td></tr>';
        document.getElementById('cs-pif-history-tbody').innerHTML = forms.length
            ? forms.map(f => `<tr><td class="small text-muted">${fmtDate(f.createdAt)}</td><td>${escapeHtml(f.submittedBy)}</td>
                <td>${escapeHtml(`${f.informantFirstName} ${f.informantLastName}`)}</td><td>${escapeHtml(f.informantRelationship)}</td>
                <td class="small">${escapeHtml((f.informantContactNumbers || []).join(', '))}</td>
                <td class="small">${escapeHtml(f.changeReason || '')}</td>
                <td class="text-end"><a class="btn btn-sm btn-link py-0" target="_blank" rel="noopener" href="/print/pif/passengers/${id}?card=${f.id}"><i class="bi bi-printer"></i></a></td></tr>`).join('')
            : '<tr><td colspan="7" class="text-center text-muted small">Not set yet.</td></tr>';
        // Pre-PIF next-of-kin entries stay visible (read-only) so nothing is lost.
        document.getElementById('cs-erc-legacy-relative').hidden = !legacy.length;
        document.getElementById('cs-erc-relative-history-tbody').innerHTML = legacy.map(r =>
            `<tr><td class="small text-muted">${fmtDate(r.createdAt)}</td><td>${escapeHtml(r.updatedByName)}</td><td>${escapeHtml(r.contactName)}</td><td>${escapeHtml(r.relationship)}</td><td class="small">${escapeHtml(r.telephoneNumbers)}</td></tr>`).join('');
        openModal('pifModal');
    }

    pifForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const id = Number(document.getElementById('cs-pif-passenger-id').value);
        const body = collectFields(pifForm, 'data-card');
        if (isNewPerson()) body.changeReason = reason.value.trim();
        try {
            await api.post(`/erc/passengers/${id}/pif`, body);
            toast(current ? (isNewPerson() ? 'Contact person replaced' : 'Contact person updated') : 'Contact person set');
            closeModal('pifModal');
            await onSaved?.();
        } catch (ex) { toast(ex.message, 'danger'); }
    });

    return openPif;
}
