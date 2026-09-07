import api from '../api.js';
import { toast, escapeHtml, spinner } from '../ui.js';
import { mountTopbar } from '../topbar.js';
import { guardPage } from '../auth-gate.js';
import * as XLSX from 'xlsx';

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
            incidentDate: document.getElementById('cs-inc-date').value,
            incidentTime: document.getElementById('cs-inc-time').value,
        };
        spinner(true);
        try {
            const inc = await api.post('/company/incidents', body);
            createdIncidentId = inc.id;
            toast('Incident created');
            // Unlock manifest step
            const card = document.getElementById('cs-manifest-card');
            card.style.opacity = '1';
            card.style.pointerEvents = 'auto';
            document.getElementById('cs-manifest-file').disabled = false;
        } catch (ex) {
            err.textContent = ex.message;
            err.hidden = false;
        } finally { spinner(false); }
    });

    document.getElementById('cs-manifest-file').addEventListener('change', async (e) => {
        const file = e.target.files[0];
        if (!file) return;
        try {
            const buf = await file.arrayBuffer();
            const wb  = XLSX.read(buf, { type: 'array' });
            const ws  = wb.Sheets[wb.SheetNames[0]];
            const raw = XLSX.utils.sheet_to_json(ws, { defval: '' });
            parsedRows = raw.map(r => ({
                name:       String(r.name       ?? r.Name       ?? '').trim(),
                seatNumber: String(r.seatNumber ?? r.seat       ?? r.Seat ?? '').trim(),
                cnic:       String(r.cnic       ?? r.CNIC       ?? '').trim() || undefined,
            })).filter(r => r.name && r.seatNumber);

            if (!parsedRows.length) {
                toast('No valid rows found — check columns "name" and "seatNumber"', 'warning');
                return;
            }

            const preview = document.getElementById('cs-manifest-preview');
            preview.hidden = false;
            const tbody = document.getElementById('cs-manifest-tbody');
            tbody.innerHTML = parsedRows.slice(0, 10).map(r => `
                <tr><td>${escapeHtml(r.name)}</td><td>${escapeHtml(r.seatNumber)}</td><td>${escapeHtml(r.cnic || '')}</td></tr>`).join('');
            document.getElementById('cs-manifest-upload').disabled = false;
            document.getElementById('cs-manifest-upload').textContent = `Upload ${parsedRows.length} passengers`;
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
