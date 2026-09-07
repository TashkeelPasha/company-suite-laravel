import api from '../api.js';
import { toast, escapeHtml, fmtDate, openModal, closeModal, confirmDialog, spinner } from '../ui.js';
import { mountTopbar } from '../topbar.js';
import { guardPage } from '../auth-gate.js';

export async function initCompanyDashboard() {
    const user = await guardPage('company');
    if (!user) return;
    mountTopbar(user);
    document.getElementById('cs-company-name').textContent = user.name || '';

    await Promise.all([loadIncidents(), loadTeam()]);
    wireTeamForm();
}

async function loadIncidents() {
    const tbody = document.getElementById('cs-incidents-tbody');
    try {
        const list = await api.get('/company/incidents');
        if (!list.length) {
            tbody.innerHTML = '<tr><td colspan="6" class="cs-empty">No incidents yet.</td></tr>';
            return;
        }
        tbody.innerHTML = list.map(i => `
            <tr>
                <td><strong>${escapeHtml(i.flightNumber)}</strong></td>
                <td>${escapeHtml(i.fromLocation)} &rarr; ${escapeHtml(i.toLocation)}</td>
                <td class="text-muted small">${escapeHtml(i.incidentDate)} ${escapeHtml(i.incidentTime)}</td>
                <td><span class="cs-status-pill cs-status-${i.status.replace(/[^A-Za-z0-9]/g, '-')}">${escapeHtml(i.status)}</span></td>
                <td>${i.passengerCount ?? 0}</td>
                <td class="text-end">
                    <a class="btn btn-sm btn-outline-primary" href="/company/incidents/${i.id}">Open</a>
                </td>
            </tr>`).join('');
    } catch (e) {
        tbody.innerHTML = `<tr><td colspan="6" class="cs-empty text-danger">${escapeHtml(e.message)}</td></tr>`;
    }
}

async function loadTeam() {
    const tbody = document.getElementById('cs-team-tbody');
    try {
        const list = await api.get('/company/team-members');
        if (!list.length) {
            tbody.innerHTML = '<tr><td colspan="5" class="cs-empty">No team members yet.</td></tr>';
            return;
        }
        tbody.innerHTML = list.map(m => `
            <tr>
                <td>${escapeHtml(m.fullName)}</td>
                <td>${escapeHtml(m.email)}</td>
                <td><span class="badge text-bg-light">${escapeHtml(m.teamType)}</span></td>
                <td class="text-muted small">${fmtDate(m.createdAt)}</td>
                <td class="text-end">
                    <button class="btn btn-sm btn-outline-secondary" data-action="edit"   data-id="${m.id}">Edit</button>
                    <button class="btn btn-sm btn-outline-danger"    data-action="delete" data-id="${m.id}" data-name="${escapeHtml(m.fullName)}">Delete</button>
                </td>
            </tr>`).join('');
        tbody.querySelectorAll('button[data-action]').forEach(btn => btn.addEventListener('click', () => handleTeamAction(btn, list)));
    } catch (e) {
        tbody.innerHTML = `<tr><td colspan="5" class="cs-empty text-danger">${escapeHtml(e.message)}</td></tr>`;
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
