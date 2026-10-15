import api from '../api.js';
import { whoami } from '../auth-gate.js';
import { apiPrefixFor, renderPrcSheet, renderPifSheet } from '../erp.js';
import { escapeHtml, fmtDate } from '../ui.js';

/**
 * /print/{prc|pif}/blank               — empty paper form
 * /print/{prc|pif}/passengers/{id}     — latest saved form (?card=ID for an older one),
 *                                        or a pre-filled sheet if none saved yet
 * /print/prc/incidents/{id}            — every passenger's latest PRC, or pre-filled from the manifest (Company / GO)
 */
export async function initPrintPage() {
    const root = document.body;
    const { form, scope, id } = root.dataset;
    const sheets  = document.getElementById('cs-print-sheets');
    const message = document.getElementById('cs-print-message');
    const render  = form === 'prc' ? renderPrcSheet : renderPifSheet;

    const say = (text, variant = 'info') => {
        message.className = `alert alert-${variant} mx-auto my-3 small`;
        message.textContent = text;
        message.hidden = false;
    };

    let user;
    try { user = await whoami(); } catch { window.location.replace('/login'); return; }
    const prefix = apiPrefixFor(user);

    document.getElementById('cs-print-btn').addEventListener('click', () => window.print());

    try {
        if (scope === 'blank') {
            sheets.innerHTML = render();
        } else if (!prefix) {
            throw Object.assign(new Error('Printing passenger forms is available to company and team accounts.'), { status: 403 });
        } else if (scope === 'passenger') {
            const d = await api.get(`${prefix}/passengers/${id}/erp`);
            const entries = form === 'prc' ? d.recordCards : d.informationForms;
            const show = (entryId) => {
                const entry = entries.find(e => String(e.id) === String(entryId)) || entries[0];
                sheets.innerHTML = form === 'prc'
                    ? renderPrcSheet({ incident: d.incident, passenger: d.passenger, card: entry })
                    : renderPifSheet({ incident: d.incident, passenger: d.passenger, entry });
            };
            if (!entries.length) {
                say(`No ${form.toUpperCase()} saved yet for ${d.passenger.name}. This sheet is pre-filled from the manifest — complete the rest by hand.`);
            } else if (entries.length > 1) {
                const select = document.getElementById('cs-print-version');
                select.innerHTML = entries.map((e, i) =>
                    `<option value="${e.id}">${i === 0 ? 'Latest' : `Version ${entries.length - i}`} — ${escapeHtml(fmtDate(e.createdAt))} · ${escapeHtml(e.submittedBy)}</option>`).join('');
                select.hidden = false;
                select.addEventListener('change', () => show(select.value));
            }
            const wanted = new URLSearchParams(window.location.search).get('card');
            if (wanted) document.getElementById('cs-print-version').value = wanted;
            show(wanted);
        } else if (scope === 'incident') {
            const m = await api.get(`${prefix}/incidents/${id}/manifest`);
            if (!m.passengers.length) say('This incident has no passengers yet.', 'warning');
            sheets.innerHTML = m.passengers.map(p => renderPrcSheet({ incident: m, passenger: p, card: p.latestRecordCard || undefined })).join('');
        }
        document.getElementById('cs-print-btn').disabled = false;
    } catch (e) {
        sheets.innerHTML = '';
        say(e.status === 404 || e.status === 403 ? 'Not found, or not available to your role.' : e.message, 'danger');
    }
}
