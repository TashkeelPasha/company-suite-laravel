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
