/**
 * Thin fetch wrapper for the JSON API described in API_DOCUMENTATION.md.
 * - Sends cookies (session auth).
 * - Attaches CSRF token from the <meta name="csrf-token"> tag on every state-changing request.
 * - Throws an Error whose `.status` and `.body` fields carry the API response.
 */

const BASE = import.meta.env.VITE_API_BASE_URL || '/api';

function csrfToken() {
    const m = document.querySelector('meta[name="csrf-token"]');
    return m ? m.getAttribute('content') : '';
}

async function request(method, path, body, { multipart = false } = {}) {
    const headers = { 'Accept': 'application/json' };
    if (!multipart) headers['Content-Type'] = 'application/json';
    if (method !== 'GET' && method !== 'HEAD') headers['X-XSRF-TOKEN'] = csrfToken();

    const opts = {
        method,
        credentials: 'include',
        headers,
    };
    if (body !== undefined) {
        opts.body = multipart ? body : JSON.stringify(body);
    }

    const res = await fetch(`${BASE}${path}`, opts);

    if (res.status === 204) return null;

    let payload = null;
    const text = await res.text();
    if (text) {
        try { payload = JSON.parse(text); } catch { payload = { error: text }; }
    }

    if (!res.ok) {
        const err = new Error(payload?.error || `Request failed (${res.status})`);
        err.status = res.status;
        err.body = payload;
        throw err;
    }
    return payload;
}

export const api = {
    get:    (p)       => request('GET',    p),
    post:   (p, body) => request('POST',   p, body),
    patch:  (p, body) => request('PATCH',  p, body),
    del:    (p)       => request('DELETE', p),
    upload: (p, formData) => request('POST', p, formData, { multipart: true }),
};

export default api;
