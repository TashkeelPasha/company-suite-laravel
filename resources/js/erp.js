/**
 * ERP-001 (Passenger Record Card) / ERP-002 (Passenger Information Form) helpers:
 * - generic form <-> JSON mapping for inputs tagged with data-pax / data-card
 * - print renderers that mirror the paper forms (blank, pre-filled or completed)
 */
import { escapeHtml } from './ui.js';

/** API prefix for the logged-in role; null for roles that don't handle passengers. */
export function apiPrefixFor(user) {
    if (user?.role === 'company') return '/company';
    if (user?.role !== 'team_member') return null;
    if (user.teamType === 'GO Team')  return '/go';
    if (user.teamType === 'ERC Team') return '/erc';
    return '/station';
}

/** "LHE → KHI → JED" — the incident route with the optional intermediate stop. */
export function routeText(from, to, via) {
    return [from, via, to].filter(Boolean).map(escapeHtml).join(' &rarr; ');
}

export const PAX_TYPE_LABELS = { M: 'Male', F: 'Female', C: 'Child', I: 'Infant' };

/**
 * Reads every element carrying `attr` (e.g. "data-card") under `root` into an object.
 * data-type="bool" selects map ""/"1"/"0" to null/true/false; data-index groups into an array.
 */
export function collectFields(root, attr) {
    const out = {};
    root.querySelectorAll(`[${attr}]`).forEach(el => {
        const key = el.getAttribute(attr);
        let v = el.value.trim();
        if (el.dataset.type === 'bool') v = v === '' ? null : v === '1';
        else if (v === '') v = null;
        if (el.dataset.index !== undefined) {
            out[key] ??= [];
            if (v !== null) out[key].push(v);
        } else {
            out[key] = v;
        }
    });
    return out;
}

/** Inverse of collectFields(). Missing keys clear the input. */
export function fillFields(root, attr, data) {
    root.querySelectorAll(`[${attr}]`).forEach(el => {
        const key = el.getAttribute(attr);
        let v = data?.[key];
        if (el.dataset.index !== undefined) v = Array.isArray(v) ? v[Number(el.dataset.index)] : undefined;
        if (el.dataset.type === 'bool') el.value = v === true ? '1' : v === false ? '0' : '';
        else el.value = v ?? '';
    });
}

/** Read-only "Travelling with (same PNR)" list for a passenger detail. */
export function partyHtml(passenger) {
    const party = passenger?.travellingWith || [];
    if (!party.length) return '<span class="text-muted">No one else on this booking (PNR).</span>';
    return party.map(t => `${escapeHtml(t.name)} <span class="text-muted">(${[t.paxType, t.dateOfBirth, t.nationality, t.seatNumber && `seat ${t.seatNumber}`].filter(Boolean).map(escapeHtml).join(', ')})</span>`).join('<br>');
}

/** Whole years between two "YYYY-MM-DD" dates ('' if unknown). Parts compared directly — no UTC drift. */
export function ageOn(dob, onDate) {
    if (!dob) return '';
    const [by, bm, bd] = dob.split('-').map(Number);
    const [oy, om, od] = (onDate || new Date().toISOString().slice(0, 10)).split('-').map(Number);
    let age = oy - by;
    if (om < bm || (om === bm && od < bd)) age--;
    return Number.isFinite(age) && age >= 0 ? age : '';
}

/** One-line contact person (ERP-002): "Bilal Khan (Brother) · +92…". */
export function contactPersonHtml(c, { empty = '<span class="text-muted">&mdash;</span>' } = {}) {
    if (!c) return empty;
    const name = c.name ?? c.informantName;
    const rel = c.relationship ?? c.informantRelationship;
    const phones = (c.contactNumbers || []).map(n => `<a href="tel:${escapeHtml(n.replace(/[^+\d]/g, ''))}">${escapeHtml(n)}</a>`).join(', ');
    return `${escapeHtml(name)}${rel ? ` <span class="text-muted">(${escapeHtml(rel)})</span>` : ''}${phones ? ` &middot; ${phones}` : ''}`;
}

/* ---------------------------------------------------------------------------
 * Print renderers. Each returns one A4 sheet. Empty values print as dotted
 * lines so a pre-filled or blank sheet can be completed by hand.
 * ------------------------------------------------------------------------- */

function val(v) {
    return v === null || v === undefined || v === '' ? '' : escapeHtml(v);
}

function field(label, value, { req = false, cls = '' } = {}) {
    return `<div class="cs-erp-field ${cls}"><span class="cs-erp-label">${escapeHtml(label)}${req ? ' *' : ''}</span><span class="cs-erp-value">${val(value)}</span></div>`;
}

function block(label, value, { req = false, lines = 3, html = false } = {}) {
    const body = html ? value : val(value);
    return `<div class="cs-erp-block"><div class="cs-erp-label">${escapeHtml(label)}${req ? ' *' : ''}</div>
        <div class="cs-erp-lines" style="--lines:${lines}">${body || ''}</div></div>`;
}

function yesNo(label, v, { req = false, cls = '' } = {}) {
    const mark = (on, text) => `<span class="cs-erp-choice${on ? ' is-on' : ''}">${text}</span>`;
    return `<div class="cs-erp-field ${cls}"><span class="cs-erp-label">${escapeHtml(label)}${req ? ' *' : ''}</span>
        <span class="cs-erp-value cs-erp-yesno">${mark(v === true, 'Yes')} / ${mark(v === false, 'No')}</span></div>`;
}

function section(title) {
    return `<h3 class="cs-erp-section">${escapeHtml(title)}</h3>`;
}

function incidentRows(inc) {
    return `<div class="cs-erp-row">
            ${field('Flt No', inc?.flightNumber, { req: true })}
            ${field('Date of Flt', inc?.incidentDate, { req: true })}
            ${field('From', inc?.fromLocation, { req: true })}
            ${field('To', inc?.toLocation, { req: true })}
            ${field('Via', inc?.via, { req: true })}
        </div>
        ${block('Other Details', inc?.otherDetails, { lines: 1 })}`;
}

function travellingWithText(passenger, extra) {
    const party = (passenger?.travellingWith || []).map(t =>
        [t.name, PAX_TYPE_LABELS[t.paxType] || t.paxType, t.dateOfBirth, t.nationality].filter(Boolean).join(', '));
    return [...party, extra].filter(Boolean).join('\n');
}

export function nokFromManifest(p) {
    if (!p?.nokName) return '';
    return `${p.nokName}${p.nokRelationship ? ` (${p.nokRelationship})` : ''}${p.nokPhone ? ` — ${p.nokPhone}` : ''}`;
}

function footer(entry) {
    const at = entry?.createdAt ? new Date(entry.createdAt) : null;
    return `<div class="cs-erp-footer">
        ${field('This form completed by me (name)', entry?.submittedBy, { req: true, cls: 'cs-erp-wide' })}
        <div class="cs-erp-row">
            ${field('at (time)', at ? at.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '', { req: true })}
            ${field('on (date)', at ? at.toLocaleDateString() : '', { req: true })}
        </div>
        ${field('My contact number(s)', '', { req: true, cls: 'cs-erp-wide' })}
    </div>`;
}

/** ERP-001. `card` is a saved PassengerRecordCard, or undefined for a pre-filled / blank sheet. */
export function renderPrcSheet({ incident, passenger: p, card: c } = {}) {
    const route = p?.effectiveRoute || {};
    const phones = p?.contactNumbers || [];
    return `<section class="cs-erp-sheet">
        <div class="cs-erp-formno">Form No: ERP-001</div>
        <h2 class="cs-erp-title">Passenger Record Card (PRC)</h2>
        ${section('Incident')}
        ${incidentRows(incident)}

        ${section('Passenger Information')}
        <div class="cs-erp-row">
            ${field('Last (Family) Name', p?.lastName ?? (p && !p.firstName ? p.name : ''), { req: true, cls: 'cs-erp-grow' })}
            ${field('First Name(s)', p?.firstName, { req: true, cls: 'cs-erp-grow' })}
        </div>
        <div class="cs-erp-row">
            ${field('M/F/C/I', p?.paxType, { req: true })}
            ${field('DOB', p?.dateOfBirth, { req: true })}
            ${field('Age', p?.age, { req: true })}
            ${field('Nationality', p?.nationality, { req: true })}
            ${field('Seat', p?.seatNumber)}
        </div>
        <div class="cs-erp-row">
            <span class="cs-erp-label">Pax Flight Route Details:</span>
            ${field('From', route.from, { req: true })}
            ${field('To', route.to, { req: true })}
            ${field('Via', route.via, { req: true })}
        </div>
        ${block('Travelling With (Last Name, First Name(s), DOB, Nationality, Sex, Relationship)', travellingWithText(p, c?.travellingWith), { req: true, lines: 3 })}
        ${block('Next of Kin / Closest Relative(s) Information', c?.nokDetails || nokFromManifest(p), { req: true, lines: 3 })}
        ${block('Relatives & Meeters / Details', c?.relativesMeeters, { lines: 2 })}
        ${yesNo('Have you already made contact with any Relatives & Meeters or any other friend?', c?.contactMade, { req: true, cls: 'cs-erp-wide' })}
        ${block('If so, details', c?.contactDetails, { lines: 2 })}

        <div class="cs-erp-row">
            ${field('Pax Contact No (1)', phones[0], { req: true })}
            ${field('(2)', phones[1])}
            ${field('(3)', phones[2])}
        </div>
        <div class="cs-erp-note">(Country Code, then Area Code, then Number)</div>
        ${block('Pax Primary Address', p?.primaryAddress, { lines: 2 })}
        ${block('Pax Local Address at Incident Location (if different from primary address)', c?.localAddress, { lines: 2 })}
        ${block('Other Contacts (last name, first name(s), contact information, address and relationship to pax)', c?.otherContacts, { lines: 2 })}

        ${yesNo('For Hospitalised/Injured Pax — are injuries classified as "life threatening"?', c?.lifeThreatening, { req: true, cls: 'cs-erp-wide' })}
        ${block('If yes, details', c?.injuryDetails, { lines: 2 })}
        ${field('Award Plus Number', p?.awardPlusNumber, { req: true, cls: 'cs-erp-wide' })}

        ${section('Pax Intentions')}
        ${yesNo('(1) Travel to Local Address', c?.intentionLocalAddress, { cls: 'cs-erp-wide' })}
        ${block('Details', c?.intentionLocalAddressDetails, { lines: 1 })}
        ${yesNo('(2) Continue Journey', c?.intentionContinueJourney, { cls: 'cs-erp-wide' })}
        ${block('Details', c?.intentionContinueJourneyDetails, { lines: 1 })}
        ${yesNo('(3) Proceed to SRC(L)', c?.intentionSrc, { cls: 'cs-erp-wide' })}
        ${block('(4) Other — details', c?.intentionOther, { lines: 1 })}

        ${section('Offer of Family Assistance made to Pax *')}
        <div class="cs-erp-row">
            ${yesNo('Made', c?.familyAssistOffered, { req: true })}
            ${yesNo('Accepted', c?.familyAssistAccepted)}
            ${yesNo('Family Assist Team Advised', c?.familyAssistTeamAdvised)}
        </div>

        ${section('Pax Language')}
        ${yesNo('Can pax converse in English?', c?.speaksEnglish, { cls: 'cs-erp-wide' })}
        <div class="cs-erp-row">
            ${field('Primary Language', c?.primaryLanguage, { cls: 'cs-erp-grow' })}
            ${field('Secondary Language', c?.secondaryLanguage, { cls: 'cs-erp-grow' })}
        </div>

        ${section('Information for Customs/Immigration')}
        <div class="cs-erp-row">
            ${field('Passport No', p?.passportNumber, { req: true })}
            ${field('Issue Date', p?.passportIssueDate, { req: true })}
            ${field('Issue Place', p?.passportIssuePlace, { req: true })}
        </div>
        <div class="cs-erp-row">
            ${field('Birth Place', p?.birthPlace, { req: true, cls: 'cs-erp-grow' })}
            ${field('Occupation', c?.occupation, { req: true, cls: 'cs-erp-grow' })}
            ${field('Sex', p?.paxType && PAX_TYPE_LABELS[p.paxType])}
        </div>
        ${p?.cnic ? field('CNIC', p.cnic, { cls: 'cs-erp-wide' }) : ''}

        ${block('Other Relevant Information / Continuity Sheet', c?.otherInformation, { lines: 3 })}
        ${footer(c)}
    </section>`;
}

/** ERP-002. `entry` is a saved PassengerInformationForm, or undefined for a pre-filled / blank sheet. */
export function renderPifSheet({ incident, passenger: p, entry: f } = {}) {
    const phones = f?.informantContactNumbers || [];
    return `<section class="cs-erp-sheet">
        <div class="cs-erp-formno">Form No: ERP-002</div>
        <h2 class="cs-erp-title">Passenger Information Form (PIF)</h2>
        ${section('Accident / Incident')}
        ${incidentRows(incident)}

        ${section('Details of Person Providing Information about Passenger(s)')}
        <div class="cs-erp-row">
            ${field('Last (Family) Name', f?.informantLastName, { req: true, cls: 'cs-erp-grow' })}
            ${field('First Name(s)', f?.informantFirstName, { req: true, cls: 'cs-erp-grow' })}
        </div>
        <div class="cs-erp-row">
            ${field('Contact No(s) (1)', phones[0], { req: true })}
            ${field('(2)', phones[1])}
            ${field('(3)', phones[2])}
        </div>
        <div class="cs-erp-note">(Country Code, then Area Code, then Number)</div>
        ${block('Address', f?.informantAddress, { lines: 2 })}
        ${field('E-mail', f?.informantEmail, { cls: 'cs-erp-wide' })}
        <div class="cs-erp-row">
            ${field('Relationship to Pax', f?.informantRelationship, { req: true, cls: 'cs-erp-grow' })}
            ${yesNo('Is the person a Relative / Meeter?', f?.informantIsRelativeOrMeeter, { req: true })}
        </div>
        ${block('Pax (Victim) Flight Details (as known to the person)', f?.paxFlightDetails, { req: true, lines: 1 })}
        ${field("The Person's Primary/Secondary Languages", f?.informantLanguages, { cls: 'cs-erp-wide' })}
        ${yesNo('Has the person giving this information already had contact with the passenger (e.g. mobile phone, text message)?', f?.informantContactedPax, { req: true, cls: 'cs-erp-wide' })}
        ${block('Details', f?.informantContactDetails, { lines: 1 })}

        ${section('Passenger (Victim) Information')}
        <div class="cs-erp-row">
            ${field('Last (Family) Name', p?.lastName ?? (p && !p.firstName ? p.name : ''), { req: true, cls: 'cs-erp-grow' })}
            ${field('First Name(s)', p?.firstName, { req: true, cls: 'cs-erp-grow' })}
        </div>
        <div class="cs-erp-row">
            ${field('M/F/C/I', p?.paxType, { req: true })}
            ${field('Nationality', p?.nationality, { req: true })}
            ${field('DOB', p?.dateOfBirth)}
            ${field('Age', p?.age)}
            ${field('Seat', p?.seatNumber)}
        </div>
        ${block('Travelling With (Last Name, First Name(s), DOB, Nationality, Sex)', travellingWithText(p, f?.travellingWith), { req: true, lines: 3 })}
        ${block('Known Next of Kin (NOK) or Closest Relative(s)', f?.nokDetails || nokFromManifest(p), { lines: 3 })}
        ${yesNo('Any Existing Medical Problem?', f?.hasMedicalProblem, { cls: 'cs-erp-wide' })}
        ${block('Details', f?.medicalDetails, { lines: 1 })}
        ${block('Other Information e.g. skin/hair/eye colour, distinguishing features/marks (scars, tattoos etc.)', f?.otherInformation, { lines: 3 })}
        ${footer(f)}
    </section>`;
}
