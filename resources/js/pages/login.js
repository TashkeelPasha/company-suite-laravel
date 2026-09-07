import api from '../api.js';
import { redirectIfLoggedIn, dashboardPathFor } from '../auth-gate.js';

const LOGIN_PATHS = {
    superadmin: '/auth/login',
    company:    '/company/auth/login',
    station:    '/station/auth/login',
    go:         '/go/auth/login',
    erc:        '/erc/auth/login',
};

export function initLoginPage() {
    // If already logged in, jump to dashboard immediately.
    redirectIfLoggedIn().catch(() => {});

    const form = document.getElementById('cs-login-form');
    if (!form) return;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const errorBox = document.getElementById('cs-login-error');
        errorBox.hidden = true;

        const role     = document.getElementById('cs-login-role').value;
        const email    = document.getElementById('cs-login-email').value.trim();
        const password = document.getElementById('cs-login-password').value;

        toggleBusy(true);
        try {
            const user = await api.post(LOGIN_PATHS[role], { email, password });
            // Backend returns different shapes per role — normalise a role hint
            // so dashboardPathFor works even before /auth/whoami is called.
            const normalised = {
                ...user,
                role: role === 'superadmin' ? 'superadmin'
                    : role === 'company'    ? 'company'
                    : 'team_member',
                teamType: user.teamType || (role === 'go' ? 'GO Team' : role === 'erc' ? 'ERC Team' : 'Station Team'),
            };
            window.location.replace(dashboardPathFor(normalised));
        } catch (err) {
            errorBox.textContent = err.message || 'Login failed';
            errorBox.hidden = false;
        } finally {
            toggleBusy(false);
        }
    });
}

function toggleBusy(busy) {
    document.getElementById('cs-login-submit').disabled = busy;
    document.querySelector('.cs-btn-spinner').hidden = !busy;
    document.querySelector('.cs-btn-label').textContent = busy ? 'Signing in…' : 'Sign in';
}
