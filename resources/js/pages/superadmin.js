import api from '../api.js';
import { toast, escapeHtml, fmtDate, openModal, closeModal, confirmDialog, spinner } from '../ui.js';
import { mountTopbar } from '../topbar.js';
import { guardPage } from '../auth-gate.js';

export async function initSuperadminDashboard() {
    const user = await guardPage('superadmin');
    if (!user) return;
    mountTopbar(user);

    await Promise.all([loadStats(), loadCompanies()]);
    wireForms();
}

async function loadStats() {
    try {
        const s = await api.get('/companies/stats');
        document.getElementById('cs-stat-total').textContent = s.totalCompanies ?? 0;
    } catch (e) { toast(e.message, 'danger'); }
}

async function loadCompanies() {
    const tbody = document.getElementById('cs-companies-tbody');
    tbody.innerHTML = '<tr><td colspan="5" class="cs-empty">Loading&hellip;</td></tr>';
    try {
        const list = await api.get('/companies');
        if (!list.length) {
            tbody.innerHTML = '<tr><td colspan="5" class="cs-empty">No companies yet. Add one to get started.</td></tr>';
            return;
        }
        tbody.innerHTML = list.map(c => `
            <tr>
                <td>${c.logoUrl ? `<img src="${escapeHtml(c.logoUrl)}" class="cs-logo">` : '<span class="cs-logo"></span>'}</td>
                <td>${escapeHtml(c.name)}</td>
                <td>${escapeHtml(c.email)}</td>
                <td class="text-muted small">${fmtDate(c.createdAt)}</td>
                <td class="text-end">
                    <button class="btn btn-sm btn-outline-secondary" data-action="edit"   data-id="${c.id}">Edit</button>
                    <button class="btn btn-sm btn-outline-warning"   data-action="reset"  data-id="${c.id}" data-name="${escapeHtml(c.name)}">Reset pw</button>
                    <button class="btn btn-sm btn-outline-danger"    data-action="delete" data-id="${c.id}" data-name="${escapeHtml(c.name)}">Delete</button>
                </td>
            </tr>
        `).join('');

        tbody.querySelectorAll('button[data-action]').forEach(btn => {
            btn.addEventListener('click', () => handleRowAction(btn, list));
        });
    } catch (e) {
        tbody.innerHTML = `<tr><td colspan="5" class="cs-empty text-danger">${escapeHtml(e.message)}</td></tr>`;
    }
}

async function handleRowAction(btn, list) {
    const id = Number(btn.dataset.id);
    const action = btn.dataset.action;
    const c = list.find(x => x.id === id);
    if (action === 'edit') openEditModal(c);
    if (action === 'reset') openResetModal(c);
    if (action === 'delete') {
        if (!await confirmDialog(`Delete company "${btn.dataset.name}"? This cascades to all team members, incidents, and passengers.`)) return;
        spinner(true);
        try {
            await api.del(`/companies/${id}`);
            toast('Company deleted');
            await Promise.all([loadStats(), loadCompanies()]);
        } catch (e) { toast(e.message, 'danger'); }
        finally { spinner(false); }
    }
}

function openEditModal(c) {
    document.getElementById('cs-company-modal-title').textContent = 'Edit company';
    document.getElementById('cs-company-id').value = c.id;
    document.getElementById('cs-company-name').value = c.name;
    document.getElementById('cs-company-email').value = c.email;
    document.getElementById('cs-company-password-wrap').hidden = true;
    document.getElementById('cs-company-error').hidden = true;
    openModal('addCompanyModal');
}

function openResetModal(c) {
    document.getElementById('cs-resetpw-id').value = c.id;
    document.getElementById('cs-resetpw-name').textContent = c.name;
    document.getElementById('cs-resetpw-value').value = '';
    openModal('resetPwModal');
}

function wireForms() {
    const btnAdd = document.querySelector('[data-bs-target="#addCompanyModal"]');
    btnAdd?.addEventListener('click', () => {
        document.getElementById('cs-company-modal-title').textContent = 'Add company';
        document.getElementById('cs-company-id').value = '';
        document.getElementById('cs-company-name').value = '';
        document.getElementById('cs-company-email').value = '';
        document.getElementById('cs-company-password').value = '';
        document.getElementById('cs-company-password-wrap').hidden = false;
        document.getElementById('cs-company-error').hidden = true;
    });

    document.getElementById('cs-company-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const err = document.getElementById('cs-company-error');
        err.hidden = true;
        const id       = document.getElementById('cs-company-id').value;
        const name     = document.getElementById('cs-company-name').value.trim();
        const email    = document.getElementById('cs-company-email').value.trim();
        const password = document.getElementById('cs-company-password').value;
        const file     = document.getElementById('cs-company-logo').files[0];

        try {
            let logoUrl;
            if (file) {
                const fd = new FormData();
                fd.append('file', file);
                const r = await api.upload('/uploads', fd);
                logoUrl = r.url;
            }
            if (id) {
                const body = { name, email };
                if (logoUrl !== undefined) body.logoUrl = logoUrl;
                await api.patch(`/companies/${id}`, body);
                toast('Company updated');
            } else {
                await api.post('/companies', { name, email, password, logoUrl: logoUrl ?? null });
                toast('Company created');
            }
            closeModal('addCompanyModal');
            await Promise.all([loadStats(), loadCompanies()]);
        } catch (ex) {
            err.textContent = ex.message;
            err.hidden = false;
        }
    });

    document.getElementById('cs-resetpw-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const id = document.getElementById('cs-resetpw-id').value;
        const newPassword = document.getElementById('cs-resetpw-value').value;
        try {
            await api.patch(`/companies/${id}/password`, { newPassword });
            toast('Password updated');
            closeModal('resetPwModal');
        } catch (ex) { toast(ex.message, 'danger'); }
    });
}

/* ---- Settings page ---- */
export async function initSuperadminSettings() {
    const user = await guardPage('superadmin');
    if (!user) return;
    mountTopbar(user);

    document.getElementById('cs-change-pw-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const err = document.getElementById('cs-change-pw-error');
        err.hidden = true;
        const currentPassword = document.getElementById('cs-cur-pw').value;
        const newPassword     = document.getElementById('cs-new-pw').value;
        try {
            await api.patch('/auth/password', { currentPassword, newPassword });
            toast('Password updated');
            document.getElementById('cs-cur-pw').value = '';
            document.getElementById('cs-new-pw').value = '';
        } catch (ex) {
            err.textContent = ex.message;
            err.hidden = false;
        }
    });
}
