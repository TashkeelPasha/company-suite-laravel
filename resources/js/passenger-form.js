/**
 * Company Admin — edit a passenger's booking / manifest data (resources/views/erp/passenger-modal.blade.php).
 * This is the Company's part of ERP-001; the PRC answers come from Station / SAT / GO updates.
 */
import api from './api.js';
import { toast, escapeHtml, openModal, closeModal } from './ui.js';
import { ageOn, collectFields, fillFields, routeText } from './erp.js';

/** Wires the modal once; returns openPassenger(passengerId). `onSaved` runs after a successful save. */
export function initPassengerForm({ onSaved } = {}) {
    const form = document.getElementById('cs-pax-form');
    let incidentDate = null;   // age on ERP-001 is age at the time of the incident

    const updateAge = (dob) => { document.getElementById('cs-pax-age').value = ageOn(dob, incidentDate); };
    form.querySelector('[data-pax="dateOfBirth"]').addEventListener('change', (e) => updateAge(e.target.value));

    async function openPassenger(id) {
        let d;
        try { d = await api.get(`/company/passengers/${id}/erp`); }
        catch (e) { toast(e.message, 'danger'); return; }
        const { passenger: p, incident: inc } = d;

        document.getElementById('cs-pax-passenger-id').value = id;
        document.getElementById('cs-pax-name').textContent = p.name;
        document.getElementById('cs-pax-incident').innerHTML = inc
            ? `${escapeHtml(inc.flightNumber)} · ${routeText(inc.fromLocation, inc.toLocation, inc.via)} · ${escapeHtml(inc.incidentDate)}`
            : 'No incident';
        document.getElementById('cs-pax-incident-route').innerHTML = inc ? routeText(inc.fromLocation, inc.toLocation, inc.via) : '—';
        document.getElementById('cs-pax-print').href = `/print/prc/passengers/${id}`;

        // Old single-name rows: put the whole name in "last name" so nothing is lost.
        fillFields(form, 'data-pax', p.firstName || p.lastName ? p : { ...p, lastName: p.name });
        incidentDate = inc?.incidentDate || null;
        updateAge(p.dateOfBirth);
        openModal('passengerModal');
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const id = Number(document.getElementById('cs-pax-passenger-id').value);
        try {
            await api.patch(`/company/passengers/${id}`, collectFields(form, 'data-pax'));
            toast('Passenger details saved');
            closeModal('passengerModal');
            await onSaved?.();
        } catch (ex) { toast(ex.message, 'danger'); }
    });

    return openPassenger;
}
