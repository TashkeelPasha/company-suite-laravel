/**
 * Excel passenger manifest → API rows for POST /company/incidents/{id}/passengers.
 * Only the Company's part of ERP-001 (booking data). The PRC answers come later
 * from Station / SAT / GO, the contact person (PIF) from ERC.
 *
 * Headers are matched loosely ("Last Name", "last_name", "LASTNAME" all work).
 * Dates may be real Excel dates, YYYY-MM-DD, DD/MM/YYYY or DD-Mon-YYYY.
 */
import * as XLSX from 'xlsx';

/**
 * key:     API field        aliases: extra accepted headers (normalised)
 * sample:  template example  note: template "How to fill" text
 */
export const MANIFEST_COLUMNS = [
    { key: 'lastName',           aliases: ['surname', 'familyname'],                 sample: 'Khan',            note: 'Required (or use a single "name" column)' },
    { key: 'firstName',          aliases: ['givenname', 'firstnames'],               sample: 'Ahmed',           note: 'Required (or use a single "name" column)' },
    { key: 'seatNumber',         aliases: ['seat', 'seatno'],                        sample: '12A',             note: 'Required, except infants (I)' },
    { key: 'paxType',            aliases: ['mfci', 'type', 'sex', 'gender'],         sample: 'M',               note: 'M = Male, F = Female, C = Child, I = Infant' },
    { key: 'dateOfBirth',        aliases: ['dob', 'birthdate'],                      sample: '1985-03-12',      note: 'YYYY-MM-DD or DD/MM/YYYY', date: true },
    { key: 'nationality',        aliases: [],                                        sample: 'Pakistani',       note: '' },
    { key: 'cnic',               aliases: [],                                        sample: '35202-1234567-1', note: 'Pakistani passengers' },
    { key: 'pnr',                aliases: ['bookingreference', 'bookingref'],        sample: 'X7K2LM',          note: 'Same PNR = travelling together' },
    { key: 'routeFrom',          aliases: [],                                        sample: '',                note: 'Only if different from the incident route' },
    { key: 'routeTo',            aliases: [],                                        sample: '',                note: 'Only if different from the incident route' },
    { key: 'routeVia',           aliases: [],                                        sample: '',                note: 'Only if different from the incident route' },
    { key: 'contactNumber1',     aliases: ['contactnumber', 'phone', 'phone1', 'mobile', 'contactno1'], sample: '+923001234567', note: 'With country code. Format the column as Text' },
    { key: 'contactNumber2',     aliases: ['phone2', 'contactno2'],                  sample: '',                note: 'With country code' },
    { key: 'contactNumber3',     aliases: ['phone3', 'contactno3'],                  sample: '',                note: 'With country code' },
    { key: 'primaryAddress',     aliases: ['address'],                               sample: 'House 1, Street 2, Lahore', note: '' },
    { key: 'passportNumber',     aliases: ['passportno', 'passport'],                sample: 'AB1234567',       note: '' },
    { key: 'passportIssueDate',  aliases: ['issuedate'],                             sample: '2020-01-15',      note: 'YYYY-MM-DD or DD/MM/YYYY', date: true },
    { key: 'passportIssuePlace', aliases: ['issueplace'],                            sample: 'Lahore',          note: '' },
    { key: 'birthPlace',         aliases: ['placeofbirth'],                          sample: 'Lahore',          note: '' },
    { key: 'awardPlusNumber',    aliases: ['awardplus', 'awardplusno', 'frequentflyernumber', 'ffn'], sample: '', note: 'Loyalty / frequent-flyer number' },
    { key: 'nokName',            aliases: ['emergencycontactname'],                  sample: '',                note: 'Emergency contact from the booking, if known' },
    { key: 'nokRelationship',    aliases: ['emergencycontactrelationship'],          sample: '',                note: '' },
    { key: 'nokPhone',           aliases: ['emergencycontactphone'],                 sample: '',                note: 'With country code' },
];

// Older manifests used a single name column.
const LEGACY_NAME = { key: 'name', aliases: ['fullname', 'passengername'] };

const norm = (h) => String(h).toLowerCase().replace(/[^a-z0-9]/g, '');

const HEADER_TO_KEY = new Map();
for (const c of [...MANIFEST_COLUMNS, LEGACY_NAME]) {
    HEADER_TO_KEY.set(norm(c.key), c.key);
    c.aliases.forEach(a => HEADER_TO_KEY.set(norm(a), c.key));
}

const PAX_TYPES = { m: 'M', male: 'M', f: 'F', female: 'F', c: 'C', chd: 'C', child: 'C', i: 'I', inf: 'I', infant: 'I' };
const MONTHS = { jan: 1, feb: 2, mar: 3, apr: 4, may: 5, jun: 6, jul: 7, aug: 8, sep: 9, oct: 10, nov: 11, dec: 12 };

const pad = (n) => String(n).padStart(2, '0');

function ymd(y, m, d) {
    const dt = new Date(Date.UTC(y, m - 1, d));
    if (dt.getUTCFullYear() !== y || dt.getUTCMonth() !== m - 1 || dt.getUTCDate() !== d) return null;
    return `${y}-${pad(m)}-${pad(d)}`;
}

/** Excel serial number, Date, or text → "YYYY-MM-DD"; undefined if empty, null if unreadable. */
export function toIsoDate(v) {
    if (v === '' || v === null || v === undefined) return undefined;
    if (typeof v === 'number') {
        const p = XLSX.SSF.parse_date_code(v);
        return p ? ymd(p.y, p.m, p.d) : null;
    }
    if (v instanceof Date) return ymd(v.getFullYear(), v.getMonth() + 1, v.getDate());
    const s = String(v).trim();
    let m;
    if ((m = s.match(/^(\d{4})-(\d{1,2})-(\d{1,2})$/))) return ymd(+m[1], +m[2], +m[3]);
    if ((m = s.match(/^(\d{1,2})[/.-](\d{1,2})[/.-](\d{4})$/))) return ymd(+m[3], +m[2], +m[1]);          // DD/MM/YYYY
    if ((m = s.match(/^(\d{1,2})[\s/.-]?([A-Za-z]{3})[a-z]*[\s/.-]?(\d{4})$/)) && MONTHS[m[2].toLowerCase()])
        return ymd(+m[3], MONTHS[m[2].toLowerCase()], +m[1]);                                                // 12-Mar-1985, 12MAR1985
    return null;
}

/**
 * Raw sheet rows (sheet_to_json with raw values) → [{ row, data, errors, warnings }].
 * `row` is the Excel row number (header is row 1). Blank rows are skipped.
 */
export function parseManifestRows(rawRows) {
    const out = [];
    rawRows.forEach((raw, i) => {
        const v = {};
        const numericPhones = [];
        for (const [header, value] of Object.entries(raw)) {
            const key = HEADER_TO_KEY.get(norm(header));
            if (!key || value === '' || value === null || value === undefined) continue;
            if (/^contactNumber/.test(key) && typeof value === 'number') numericPhones.push(key);
            v[key] = value;
        }
        if (!Object.keys(v).length) return;

        const errors = [], warnings = [];
        const text = (k) => (v[k] === undefined ? undefined : String(v[k]).trim() || undefined);
        const data = {};
        for (const c of MANIFEST_COLUMNS) {
            if (c.date || /^contactNumber/.test(c.key)) continue;
            data[c.key] = text(c.key);
        }
        data.name = text('name');

        for (const c of MANIFEST_COLUMNS.filter(c => c.date)) {
            const iso = toIsoDate(v[c.key]);
            if (iso === null) errors.push(`${c.key} "${v[c.key]}" is not a date`);
            data[c.key] = iso ?? undefined;
        }

        if (data.paxType !== undefined) {
            const t = PAX_TYPES[data.paxType.toLowerCase()];
            if (!t) errors.push(`paxType "${data.paxType}" must be M, F, C or I`);
            data.paxType = t;
        }

        const phones = ['contactNumber1', 'contactNumber2', 'contactNumber3'].map(text).filter(Boolean);
        delete data.contactNumber1; delete data.contactNumber2; delete data.contactNumber3;
        if (phones.length) data.contactNumbers = phones;
        if (numericPhones.length) warnings.push('contact number saved as a number in Excel — check the country code / leading 0');

        if (!data.firstName && !data.lastName && !data.name) errors.push('name is missing');
        if (!data.name) data.name = [data.firstName, data.lastName].filter(Boolean).join(' ') || undefined;
        if (!data.seatNumber && data.paxType !== 'I') errors.push('seatNumber is missing');
        const missing = ['paxType', 'dateOfBirth', 'nationality'].filter(k => !data[k] && !errors.some(e => e.startsWith(k)));
        if (missing.length) warnings.push(`missing for ERP forms: ${missing.join(', ')}`);

        out.push({ row: i + 2, data, errors, warnings });
    });
    return out;
}

/** Workbook with the header row + one example, plus a "How to fill" sheet. All cells are Text. */
export function buildTemplateWorkbook() {
    const asText = (ws) => {
        Object.keys(ws).filter(k => !k.startsWith('!')).forEach(k => { ws[k].t = 's'; ws[k].z = '@'; ws[k].v = String(ws[k].v ?? ''); });
        return ws;
    };
    const data = asText(XLSX.utils.aoa_to_sheet([
        MANIFEST_COLUMNS.map(c => c.key),
        MANIFEST_COLUMNS.map(c => c.sample),
    ]));
    data['!cols'] = MANIFEST_COLUMNS.map(c => ({ wch: Math.max(c.key.length, c.sample.length) + 2 }));

    const help = XLSX.utils.aoa_to_sheet([
        ['Column', 'How to fill'],
        ...MANIFEST_COLUMNS.map(c => [c.key, c.note]),
        [],
        ['Tip', 'Delete the example row. Keep the header row as it is. Format phone, CNIC and passport columns as Text.'],
        ['Note', 'Injury, intentions, family assistance, language and next-of-kin answers are added by Station / SAT / GO. The contact person is set by ERC.'],
    ]);
    help['!cols'] = [{ wch: 22 }, { wch: 70 }];

    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, data, 'Passengers');
    XLSX.utils.book_append_sheet(wb, help, 'How to fill');
    return wb;
}
