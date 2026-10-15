/**
 * Small UI helpers: toast, spinner, confirm dialog, status pill renderer.
 */
import { Toast, Modal } from 'bootstrap';

export function toast(message, variant = 'success') {
    const container = document.getElementById('cs-toast-container');
    if (!container) return;
    const el = document.createElement('div');
    el.className = `toast align-items-center text-bg-${variant} border-0`;
    el.setAttribute('role', 'alert');
    el.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${escapeHtml(message)}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>`;
    container.appendChild(el);
    const t = new Toast(el, { delay: 4000 });
    t.show();
    el.addEventListener('hidden.bs.toast', () => el.remove());
}

export function spinner(show) {
    const el = document.getElementById('cs-spinner');
    if (el) el.hidden = !show;
}

export function confirmDialog(message) {
    return new Promise(resolve => resolve(window.confirm(message)));
}

export function statusPill(status) {
    if (!status) return '<span class="text-muted small">&mdash;</span>';
    const safe = String(status).replace(/[^A-Za-z0-9]/g, '-');
    return `<span class="cs-status-pill cs-status-${safe}">${escapeHtml(status)}</span>`;
}

const AVATAR_COLORS = ['#2457d6', '#0e7490', '#7c3aed', '#be185d', '#b45309', '#15803d', '#4338ca', '#0f766e'];

/** Initials avatar; the colour is stable per name. */
export function avatarHtml(name, { cls = '', id = '' } = {}) {
    // Letters only, so "Emma ERC (Acme)" gives "EE", not "E(".
    const words = String(name || '?').trim().split(/\s+/).map(w => w.replace(/[^\p{L}\p{N}]/gu, '')).filter(Boolean);
    if (!words.length) words.push('?');
    const initials = ((words[0]?.[0] || '?') + (words.length > 1 ? words[words.length - 1][0] : '')).toUpperCase();
    let h = 0;
    for (const ch of String(name || '')) h = (h * 31 + ch.charCodeAt(0)) >>> 0;
    return `<span class="cs-avatar ${cls}"${id ? ` id="${id}"` : ''} style="--cs-avatar:${AVATAR_COLORS[h % AVATAR_COLORS.length]}" aria-hidden="true">${escapeHtml(initials)}</span>`;
}

/** "Company admin", "Station Team"… for the header and sidebar. */
export function roleLabel(user) {
    if (user?.role === 'superadmin') return 'Super admin';
    if (user?.role === 'company') return 'Company admin';
    return user?.teamType || 'Team member';
}

/** Tone class for a team type chip / avatar. */
export const TEAM_TONES = { 'Station Team': 'cs-tone-blue', 'GO Team': 'cs-tone-violet', 'ERC Team': 'cs-tone-amber', 'SAT - Volunteers': 'cs-tone-green' };

/** "14 Oct 2026" from "YYYY-MM-DD" without timezone drift. */
export function fmtDay(ymd) {
    if (!ymd) return '';
    const [y, m, d] = String(ymd).split('-').map(Number);
    if (!y || !m || !d) return ymd;
    return new Date(y, m - 1, d).toLocaleDateString(undefined, { day: 'numeric', month: 'short', year: 'numeric' });
}

export function fmtDate(iso) {
    if (!iso) return '';
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) return iso;
    return d.toLocaleString();
}

export function escapeHtml(s) {
    if (s == null) return '';
    return String(s)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}

export function openModal(id) {
    const el = document.getElementById(id);
    if (!el) return null;
    const m = Modal.getOrCreateInstance(el);
    m.show();
    return m;
}

export function closeModal(id) {
    const el = document.getElementById(id);
    if (!el) return;
    Modal.getInstance(el)?.hide();
}
