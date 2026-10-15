import api from '../api.js';
import { toast, escapeHtml, fmtDate, fmtDay, openModal, closeModal, confirmDialog, spinner, avatarHtml, TEAM_TONES } from '../ui.js';
import { mountTopbar } from '../topbar.js';
import { guardPage } from '../auth-gate.js';
import { routeText } from '../erp.js';
import { Tab } from 'bootstrap';

const empty = (cols, icon, title, text) =>
    `<tr><td colspan="${cols}" class="cs-empty"><i class="bi ${icon}"></i><div class="cs-empty-title">${title}</div>${text}</td></tr>`;

export async function initCompanyDashboard() {
    const user = await guardPage('company');
    if (!user) return;
    mountTopbar(user);
    document.getElementById('cs-company-name').textContent = user.name || '';
    document.getElementById('cs-today').textContent = new Date().toLocaleDateString(undefined, { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });

    // /company#team opens the Team tab (sidebar link), and the tab keeps the hash in sync.
    const showTabFromHash = () => Tab.getOrCreateInstance(document.getElementById(location.hash === '#team' ? 'cs-tab-team-btn' : 'cs-tab-incidents-btn')).show();
    showTabFromHash();
    window.addEventListener('hashchange', showTabFromHash);
    document.getElementById('cs-tab-team-btn').addEventListener('shown.bs.tab', () => history.replaceState(null, '', '#team'));
    document.getElementById('cs-tab-incidents-btn').addEventListener('shown.bs.tab', () => history.replaceState(null, '', location.pathname));

    await Promise.all([loadIncidents(), loadTeam()]);
    wireTeamForm();
}

let incidents = [];

async function loadIncidents() {
    try {
        incidents = await api.get('/company/incidents');
        document.getElementById('cs-kpi-active').textContent = incidents.filter(i => i.status === 'Ongoing').length;
        document.getElementById('cs-kpi-closed').textContent = incidents.filter(i => i.status !== 'Ongoing').length;
        document.getElementById('cs-kpi-pax').textContent = incidents.reduce((n, i) => n + (i.passengerCount || 0), 0);
        document.getElementById('cs-incidents-count').textContent = incidents.length;
        renderIncidents(incidents);
        document.getElementById('cs-incident-search').addEventListener('input', (e) => {
            const q = e.target.value.trim().toLowerCase();
            renderIncidents(!q ? incidents : incidents.filter(i =>
                [i.flightNumber, i.fromLocation, i.toLocation, i.via].some(v => (v || '').toLowerCase().includes(q))), !!q);
        });
    } catch (e) {
        document.getElementById('cs-incidents-tbody').innerHTML = `<tr><td colspan="6" class="cs-empty text-danger">${escapeHtml(e.message)}</td></tr>`;
    }
}

function renderIncidents(list, filtered = false) {
    const tbody = document.getElementById('cs-incidents-tbody');
    if (!list.length) {
        tbody.innerHTML = filtered
            ? empty(6, 'bi-search', 'No matching incidents', 'Try another flight number or city.')
            : empty(6, 'bi-airplane', 'No incidents yet', 'Create an incident to start tracking passengers. <div class="mt-3"><a href="/company/incidents/new" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> New incident</a></div>');
        return;
    }
    tbody.innerHTML = list.map(i => `
        <tr class="cs-row-link" data-href="/company/incidents/${i.id}">
            <td>
                <div class="d-flex align-items-center gap-3">
                    <span class="cs-flight-tile"><i class="bi bi-airplane-fill"></i></span>
                    <div><div class="cs-cell-main">${escapeHtml(i.flightNumber)}</div><div class="cs-cell-sub">Incident #${i.id}</div></div>
                </div>
            </td>
            <td><span class="cs-route">${routeText(i.fromLocation, i.toLocation, i.via)}</span></td>
            <td><div class="cs-cell-main fw-medium">${escapeHtml(fmtDay(i.incidentDate))}</div><div class="cs-cell-sub">${escapeHtml(i.incidentTime || '')}</div></td>
            <td><span class="cs-status-pill cs-status-${i.status.replace(/[^A-Za-z0-9]/g, '-')}">${escapeHtml(i.status)}</span></td>
            <td><span class="fw-semibold">${i.passengerCount ?? 0}</span> <span class="cs-cell-sub">pax</span></td>
            <td class="text-end">
                <a class="btn btn-sm btn-soft-primary" href="/company/incidents/${i.id}">Open <i class="bi bi-arrow-right"></i></a>
            </td>
        </tr>`).join('');
    tbody.querySelectorAll('tr[data-href]').forEach(tr => tr.addEventListener('click', (e) => {
        if (!e.target.closest('a, button')) window.location.href = tr.dataset.href;
    }));
}

async function loadTeam() {
    const tbody = document.getElementById('cs-team-tbody');
    try {
        const list = await api.get('/company/team-members');
        document.getElementById('cs-kpi-team').textContent = list.length;
        document.getElementById('cs-team-count').textContent = list.length;
        if (!list.length) {
            tbody.innerHTML = empty(4, 'bi-people', 'No team members yet', 'Add Station, GO, ERC and SAT members so they can sign in.');
            return;
        }
        tbody.innerHTML = list.map(m => `
            <tr>
                <td>
                    <div class="d-flex align-items-center gap-3">
                        ${avatarHtml(m.fullName)}
                        <div class="min-w-0"><div class="cs-cell-main">${escapeHtml(m.fullName)}</div><div class="cs-cell-sub">${escapeHtml(m.email)}</div></div>
                    </div>
                </td>
                <td><span class="cs-team-chip ${TEAM_TONES[m.teamType] || 'cs-tone-slate'}">${escapeHtml(m.teamType)}</span></td>
                <td class="cs-cell-sub">${fmtDate(m.createdAt)}</td>
                <td class="text-end">
                    <div class="cs-row-actions">
                        <button class="btn btn-soft btn-icon" data-action="edit" data-id="${m.id}" title="Edit" aria-label="Edit ${escapeHtml(m.fullName)}"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-soft btn-icon text-danger" data-action="delete" data-id="${m.id}" data-name="${escapeHtml(m.fullName)}" title="Delete" aria-label="Delete ${escapeHtml(m.fullName)}"><i class="bi bi-trash3"></i></button>
                    </div>
                </td>
            </tr>`).join('');
        tbody.querySelectorAll('button[data-action]').forEach(btn => btn.addEventListener('click', () => handleTeamAction(btn, list)));
    } catch (e) {
        tbody.innerHTML = `<tr><td colspan="4" class="cs-empty text-danger">${escapeHtml(e.message)}</td></tr>`;
    }
}

async function handleTeamAction(btn, list) {
    const id = Number(btn.dataset.id);
    if (btn.dataset.action === 'edit') {
        const m = list.find(x => x.id === id);
        document.getElementById('cs-team-modal-title').textContent = 'Edit team member';
        document.getElementById('cs-team-id').value = m.id;
        document.getElementById('cs-team-name').value = m.fullName;
        document.getElementById('cs-team-email').value = m.email;
        document.getElementById('cs-team-password').value = '';
        document.getElementById('cs-team-type').value = m.teamType;
        document.getElementById('cs-team-error').hidden = true;
        openModal('addTeamMemberModal');
    } else if (btn.dataset.action === 'delete') {
        if (!await confirmDialog(`Delete team member "${btn.dataset.name}"?`)) return;
        spinner(true);
        try {
            await api.del(`/company/team-members/${id}`);
            toast('Team member deleted');
            await loadTeam();
        } catch (e) { toast(e.message, 'danger'); }
        finally { spinner(false); }
    }
}

function wireTeamForm() {
    document.querySelector('[data-bs-target="#addTeamMemberModal"]')?.addEventListener('click', () => {
        document.getElementById('cs-team-modal-title').textContent = 'Add team member';
        ['cs-team-id', 'cs-team-name', 'cs-team-email', 'cs-team-password'].forEach(id => document.getElementById(id).value = '');
        document.getElementById('cs-team-error').hidden = true;
    });

    document.getElementById('cs-team-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const err = document.getElementById('cs-team-error');
        err.hidden = true;
        const id       = document.getElementById('cs-team-id').value;
        const fullName = document.getElementById('cs-team-name').value.trim();
        const email    = document.getElementById('cs-team-email').value.trim();
        const password = document.getElementById('cs-team-password').value;
        const teamType = document.getElementById('cs-team-type').value;
        try {
            if (id) {
                const body = { fullName, email, teamType };
                if (password) body.password = password;
                await api.patch(`/company/team-members/${id}`, body);
                toast('Team member updated');
            } else {
                await api.post('/company/team-members', { fullName, email, password, teamType });
                toast('Team member created');
            }
            closeModal('addTeamMemberModal');
            await loadTeam();
        } catch (ex) {
            err.textContent = ex.message;
            err.hidden = false;
        }
    });
}
