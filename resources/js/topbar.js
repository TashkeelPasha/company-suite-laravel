import api from './api.js';
import { clearWhoAmICache } from './auth-gate.js';

export function mountTopbar(user) {
    if (!user) return;
    document.getElementById('cs-topbar-user').hidden = false;
    document.getElementById('cs-topbar-name').textContent =
        user.fullName || user.name || user.email || '';
    if (user.logoUrl) {
        const img = document.getElementById('cs-topbar-logo');
        img.src = user.logoUrl;
        img.hidden = false;
    }

    document.getElementById('cs-topbar-logout').addEventListener('click', async () => {
        try {
            const path = logoutPathFor(user);
            await api.post(path);
        } catch {}
        clearWhoAmICache();
        window.location.replace('/login');
    });
}

function logoutPathFor(user) {
    if (user.role === 'superadmin') return '/auth/logout';
    if (user.role === 'company')    return '/company/auth/logout';
    if (user.teamType === 'GO Team')  return '/go/auth/logout';
    if (user.teamType === 'ERC Team') return '/erc/auth/logout';
    return '/station/auth/logout';
}
