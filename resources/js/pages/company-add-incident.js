import api from '../api.js';
import { toast, escapeHtml, spinner } from '../ui.js';
import { mountTopbar } from '../topbar.js';
import { guardPage } from '../auth-gate.js';
import * as XLSX from 'xlsx';
import { parseManifestRows, buildTemplateWorkbook } from '../manifest.js';

export async function initCompanyAddIncident() {
    const user = await guardPage('company');
    if (!user) return;
    mountTopbar(user);

    let createdIncidentId = null;
    let parsedRows = [];

    document.getElementById('cs-incident-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const err = document.getElementById('cs-inc-error');
        err.hidden = true;
        const body = {
            flightNumber: document.getElementById('cs-inc-flight').value.trim(),
            fromLocation: document.getElementById('cs-inc-from').value.trim(),
            toLocation:   document.getElementById('cs-inc-to').value.trim(),
            via:          document.getElementById('cs-inc-via').value.trim() || undefined,
            otherDetails: document.getElementById('cs-inc-other').value.trim() || undefined,
            incidentDate: document.getElementById('cs-inc-date').value,
            incidentTime: document.getElementById('cs-inc-time').value,
        };
        spinner(true);
        try {
            const inc = await api.post('/company/incidents', body);
            createdIncidentId = inc.id;
            toast('Incident created');
            // Unlock manifest step
            document.getElementById('cs-manifest-card').classList.remove('is-locked');
            document.getElementById('cs-manifest-file').disabled = false;
            document.getElementById('cs-step-1').classList.replace('is-active', 'is-done');
            document.getElementById('cs-step-1').querySelector('.cs-step-num').innerHTML = '<i class="bi bi-check-lg"></i>';
            document.getElementById('cs-step-2').classList.add('is-active');
            const submit = document.getElementById('cs-inc-submit');
            submit.disabled = true;
            submit.innerHTML = '<i class="bi bi-check2-circle"></i> Incident created';
            document.querySelectorAll('#cs-incident-form input, #cs-incident-form textarea').forEach(el => { el.readOnly = true; });
        } catch (ex) {
            err.textContent = ex.message;
            err.hidden = false;
        } finally { spinner(false); }
    });

    document.getElementById('cs-manifest-template').addEventListener('click', () => {
        XLSX.writeFile(buildTemplateWorkbook(), 'passenger-manifest-template.xlsx');
    });

    document.getElementById('cs-manifest-file').addEventListener('change', async (e) => {
        const file = e.target.files[0];
        if (!file) return;
        document.getElementById('cs-dropzone-title').textContent = file.name;
        document.getElementById('cs-dropzone-hint').textContent = `${(file.size / 1024).toFixed(0)} KB · click to choose another file`;
        const uploadBtn = document.getElementById('cs-manifest-upload');
        try {
            const buf = await file.arrayBuffer();
            // raw: CSV cells stay text — otherwise SheetJS reads 12/03/1985 as US M/D and drops the + from phones
            const wb  = XLSX.read(buf, { type: 'array', raw: true });
            const ws  = wb.Sheets[wb.SheetNames[0]];
            // raw: keep Excel dates as serial numbers (parsed in manifest.js without timezone drift)
            const checked = parseManifestRows(XLSX.utils.sheet_to_json(ws, { defval: '', raw: true }));

            if (!checked.length) {
                toast('No passenger rows found — download the template to see the expected columns', 'warning');
                return;
            }

            const bad = checked.filter(r => r.errors.length);
            const warned = checked.filter(r => !r.errors.length && r.warnings.length);
            parsedRows = bad.length ? [] : checked.map(r => r.data);
            if (bad.length) e.target.value = '';   // lets the corrected file (same name) be chosen again

            const preview = document.getElementById('cs-manifest-preview');
            preview.hidden = false;
            document.getElementById('cs-manifest-summary').innerHTML = bad.length
                ? `<span class="text-danger fw-semibold">${bad.length} row(s) have errors</span> — fix them in Excel and choose the file again.`
                : `<span class="text-success fw-semibold">${checked.length} passengers ready.</span>${warned.length ? ` <span class="text-warning-emphasis">${warned.length} row(s) have missing PRC details (can be completed later with the edit button on the incident page).</span>` : ''}`;
            // Problem rows first so they're visible without scrolling.
            const ordered = [...bad, ...warned, ...checked.filter(r => !r.errors.length && !r.warnings.length)];
            document.getElementById('cs-manifest-tbody').innerHTML = ordered.slice(0, 300).map(({ row, data: d, errors, warnings }) => `
                <tr class="${errors.length ? 'table-danger' : warnings.length ? 'table-warning' : ''}">
                    <td class="text-muted small">${row}</td>
                    <td>${escapeHtml(d.name || '')}</td>
                    <td>${escapeHtml(d.seatNumber || '')}</td>
                    <td>${escapeHtml(d.paxType || '')}</td>
                    <td class="small">${escapeHtml(d.dateOfBirth || '')}</td>
                    <td class="small">${escapeHtml(d.nationality || '')}</td>
                    <td class="small">${escapeHtml(d.pnr || '')}</td>
                    <td class="small">${[...errors, ...warnings].map(escapeHtml).join('<br>') || '<i class="bi bi-check-lg text-success"></i>'}</td>
                </tr>`).join('');
            uploadBtn.disabled = !parsedRows.length;
            uploadBtn.textContent = parsedRows.length ? `Upload ${parsedRows.length} passengers` : 'Fix errors to upload';
        } catch (ex) { toast(ex.message, 'danger'); }
    });

    document.getElementById('cs-manifest-upload').addEventListener('click', async () => {
        if (!createdIncidentId || !parsedRows.length) return;
        spinner(true);
        try {
            const r = await api.post(`/company/incidents/${createdIncidentId}/passengers`, { passengers: parsedRows });
            toast(`Uploaded ${r.count} passengers`);
            window.location.href = `/company/incidents/${createdIncidentId}`;
        } catch (ex) { toast(ex.message, 'danger'); }
        finally { spinner(false); }
    });

    document.getElementById('cs-skip-manifest').addEventListener('click', (e) => {
        e.preventDefault();
        if (createdIncidentId) window.location.href = `/company/incidents/${createdIncidentId}`;
        else window.location.href = '/company';
    });
}
