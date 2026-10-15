import api from '../api.js';
import { toast, escapeHtml, statusPill, fmtDate } from '../ui.js';
import { mountTopbar } from '../topbar.js';
import { whoami, dashboardPathFor } from '../auth-gate.js';
import { apiPrefixFor, contactPersonHtml, routeText, PAX_TYPE_LABELS } from '../erp.js';
import { initPassengerForm } from '../passenger-form.js';
import { initUpdateForm } from '../update-form.js';
import { initPifForm } from '../pif-form.js';

const REFRESH_MS = 30000;
const TEAMS = [['Station Team', 'Station'], ['SAT - Volunteers', 'SAT'], ['GO Team', 'GO']];

const yn = (v) => (v === true ? 'Yes' : v === false ? 'No' : null);

/** <dt>/<dd> pairs, skipping empty values. Values are escaped unless the pair says html. */
function dl(rows) {
    const html = rows.filter(([, v]) => v !== null && v !== undefined && v !== '')
        .map(([k, v, raw]) => `<dt>${escapeHtml(k)}</dt><dd>${raw ? v : escapeHtml(v)}</dd>`).join('');
    return html || '<span class="text-muted">Nothing recorded yet.</span>';
}

/**
 * /passengers/{id} — everything about one passenger on one page, for every role:
 * status by team, contact person, manifest details, latest PRC and a single timeline.
 */
export async function initPassengerFile() {
    const root = document.querySelector('[data-cs-passenger-file]');
    const id = Number(root.dataset.id);

    let user;
    try { user = await whoami(); } catch { window.location.replace('/login'); return; }
    const prefix = apiPrefixFor(user);
    if (!prefix) { window.location.replace(dashboardPathFor(user)); return; }
    mountTopbar(user);
    document.getElementById('cs-pf-back').href = dashboardPathFor(user);

    const role = prefix.slice(1);   // company | station | go | erc
    // Only the viewer's own form is wired — each init attaches a submit handler.
    const actions = {
        company: { label: 'Edit passenger details', icon: 'bi-pencil', init: () => initPassengerForm({ onSaved: () => load() }) },
        station: { label: 'Update', icon: 'bi-pencil-square', init: () => initUpdateForm({ prefix, onSaved: () => load() }) },
        go:      { label: 'Update', icon: 'bi-pencil-square', init: () => initUpdateForm({ prefix, onSaved: () => load() }) },
        erc:     { label: 'Contact person', icon: 'bi-telephone', init: () => initPifForm({ onSaved: () => load() }) },
    }[role];
    actions.open = actions.init();

    function renderActions(d) {
        const canPif = role !== 'station';   // Station sees the contact person, not the full PIF
        const erc = role === 'erc' && !d.contactPerson ? 'Set contact person' : actions.label;
        document.getElementById('cs-pf-actions').innerHTML = `
            <button type="button" class="btn btn-primary" id="cs-pf-act"><i class="bi ${actions.icon}"></i> ${escapeHtml(erc)}</button>
            <a class="btn btn-outline-secondary" target="_blank" rel="noopener" href="/print/prc/passengers/${id}"><i class="bi bi-printer"></i> PRC</a>
            ${canPif ? `<a class="btn btn-outline-secondary" target="_blank" rel="noopener" href="/print/pif/passengers/${id}"><i class="bi bi-printer"></i> PIF</a>` : ''}`;
        document.getElementById('cs-pf-act').addEventListener('click', () => actions.open(id));
    }

    function renderHeader({ passenger: p, incident: inc, updates }) {
        document.getElementById('cs-pf-name').textContent = p.name;
        document.getElementById('cs-pf-status').innerHTML = statusPill(updates[0]?.status);
        document.getElementById('cs-pf-sub').innerHTML = [
            p.seatNumber && `Seat ${escapeHtml(p.seatNumber)}`,
            inc && `${escapeHtml(inc.flightNumber)} · ${routeText(inc.fromLocation, inc.toLocation, inc.via)} · ${escapeHtml(inc.incidentDate)}`,
            inc && `<span class="cs-status-pill cs-status-${inc.status.replace(/[^A-Za-z0-9]/g, '-')}">${escapeHtml(inc.status)}</span>`,
        ].filter(Boolean).join(' &middot; ');
        document.title = `${p.name} · Passenger file`;
    }

    function renderSide({ passenger: p, updates, contactPerson, recordCards }) {
        document.getElementById('cs-pf-team-status').innerHTML = TEAMS.map(([type, label]) => {
            const u = updates.find(x => x.teamType === type);
            return `<div class="d-flex justify-content-between align-items-center py-1 border-bottom">
                <span>${label}</span><span>${statusPill(u?.status)} ${u ? `<span class="text-muted ms-1">${escapeHtml(fmtDate(u.createdAt))}</span>` : ''}</span></div>`;
        }).join('');

        document.getElementById('cs-pf-contact').innerHTML = contactPerson
            ? `<div class="fs-6">${contactPersonHtml(contactPerson)}</div>
               ${contactPerson.email ? `<div>${escapeHtml(contactPerson.email)}</div>` : ''}
               <div class="text-muted mt-1">Updates about this passenger go to this person only. Set by ERC &mdash; last change ${escapeHtml(fmtDate(contactPerson.updatedAt))} (${escapeHtml(contactPerson.updatedBy)}).</div>`
            : '<span class="text-muted">Not set yet &mdash; ERC sets it on the first call from a relative.</span>';

        const route = p.effectiveRoute || {};
        document.getElementById('cs-pf-details').innerHTML = dl([
            ['M/F/C/I', p.paxType && `${p.paxType} — ${PAX_TYPE_LABELS[p.paxType]}`],
            ['DOB / age', p.dateOfBirth && `${p.dateOfBirth}${p.age !== null && p.age !== '' ? ` (${p.age})` : ''}`],
            ['Nationality', p.nationality], ['CNIC', p.cnic], ['PNR', p.pnr],
            ['Route', routeText(route.from, route.to, route.via), true],
            ['Phone(s)', (p.contactNumbers || []).join(', ')],
            ['Address', p.primaryAddress],
            ['Passport', p.passportNumber && `${p.passportNumber}${p.passportIssuePlace ? `, ${p.passportIssuePlace}` : ''}`],
            ['Travelling with', (p.travellingWith || []).map(t => t.name).join(', ')],
        ]);

        const c = recordCards[0];
        document.getElementById('cs-pf-prc').innerHTML = c ? dl([
            ['Saved', `${fmtDate(c.createdAt)} · ${c.submittedBy}`],
            ['Next of kin', c.nokDetails],
            ['Life threatening', yn(c.lifeThreatening)], ['Injury', c.injuryDetails],
            ['Intentions', [c.intentionLocalAddress && `Local address${c.intentionLocalAddressDetails ? `: ${c.intentionLocalAddressDetails}` : ''}`,
                            c.intentionContinueJourney && `Continue journey${c.intentionContinueJourneyDetails ? `: ${c.intentionContinueJourneyDetails}` : ''}`,
                            c.intentionSrc && 'Proceed to SRC(L)', c.intentionOther].filter(Boolean).join(' · ')],
            ['Family assistance', [yn(c.familyAssistOffered) && `offered: ${yn(c.familyAssistOffered)}`, yn(c.familyAssistAccepted) && `accepted: ${yn(c.familyAssistAccepted)}`].filter(Boolean).join(', ')],
            ['Language', [c.speaksEnglish === true && 'English', c.primaryLanguage, c.secondaryLanguage].filter(Boolean).join(', ')],
            ['Local address', c.localAddress], ['Occupation', c.occupation],
        ]) : '<span class="text-muted">No answers yet &mdash; Station / SAT / GO add them with their updates.</span>';
    }

    /** Status updates, PRC versions and contact-person changes, newest first. */
    function renderTimeline({ updates, recordCards, informationForms }) {
        const items = [
            ...updates.map(u => ({ at: u.createdAt, icon: 'bi-activity', html:
                `${statusPill(u.status)} <strong>${escapeHtml(u.teamType || 'Team')}</strong> &middot; ${escapeHtml(u.submittedBy)}${u.remarks ? `<div class="text-body-secondary">${escapeHtml(u.remarks)}</div>` : ''}` })),
            ...recordCards.map((c, i) => ({ at: c.createdAt, icon: 'bi-card-checklist', html:
                `<strong>PRC ${i === recordCards.length - 1 ? 'created' : 'updated'}</strong> &middot; ${escapeHtml(c.submittedBy)}
                 <a class="ms-1 small" target="_blank" rel="noopener" href="/print/prc/passengers/${id}?card=${c.id}"><i class="bi bi-printer"></i> view</a>` })),
            ...informationForms.map((f, i) => {
                const first = i === informationForms.length - 1;
                const what = first ? 'Contact person set' : f.changeReason ? 'Contact person replaced' : 'Contact person details updated';
                return { at: f.createdAt, icon: 'bi-telephone', html:
                    `<strong>${what}</strong> &middot; ${escapeHtml(`${f.informantFirstName} ${f.informantLastName}`)} (${escapeHtml(f.informantRelationship)}) &middot; by ${escapeHtml(f.submittedBy)}
                     ${f.changeReason ? `<div class="text-body-secondary">Reason: ${escapeHtml(f.changeReason)}</div>` : ''}
                     <a class="small" target="_blank" rel="noopener" href="/print/pif/passengers/${id}?card=${f.id}"><i class="bi bi-printer"></i> view</a>` };
            }),
        ].sort((a, b) => new Date(b.at) - new Date(a.at));

        document.getElementById('cs-pf-timeline').innerHTML = items.length
            ? items.map(t => `<li class="cs-pf-item"><i class="bi ${t.icon} cs-pf-icon"></i>
                <div class="cs-pf-when small text-muted">${escapeHtml(fmtDate(t.at))}</div><div>${t.html}</div></li>`).join('')
            : '<li class="text-muted">Nothing yet.</li>';
    }

    let first = true;
    async function load() {
        let d;
        try { d = await api.get(`${prefix}/passengers/${id}/erp`); }
        catch (e) {
            if (first) {
                const err = document.getElementById('cs-pf-error');
                err.hidden = false;
                err.textContent = e.status === 404 || e.status === 403 ? 'Passenger not found, or not available to your role.' : e.message;
                document.getElementById('cs-pf-sub').textContent = '';
            } else toast(e.message, 'danger');
            return;
        }
        renderHeader(d);
        renderSide(d);
        renderTimeline(d);
        renderActions(d);
        document.getElementById('cs-pf-body').hidden = false;
        document.getElementById('cs-pf-refreshed').textContent = `Updated ${new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' })} · refreshes every 30 s`;
        first = false;
    }

    await load();
    // Live view during an incident — skip while a form is open so nothing is overwritten.
    setInterval(() => { if (!document.querySelector('.modal.show') && !document.hidden) load(); }, REFRESH_MS);
}
