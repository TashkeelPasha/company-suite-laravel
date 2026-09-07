/**
 * Client-side role gate. Every dashboard page calls guardPage() on load.
 * If the user isn't logged in, redirect to /login.
 * If the user's role doesn't match the page, redirect to the correct dashboard.
 *
 * Consumes GET /api/auth/whoami — see API_DOCUMENTATION.md §2.
 */
import api from './api.js';

let cached = null;

export async function whoami() {
    if (cached) return cached;
    cached = await api.get('/auth/whoami');
    return cached;
}

export function dashboardPathFor(user) {
    if (!user) return '/login';
    if (user.role === 'superadmin') return '/dashboard';
    if (user.role === 'company')    return '/company';
    if (user.role === 'team_member') {
        if (user.teamType === 'GO Team')            return '/go';
        if (user.teamType === 'ERC Team')           return '/erc';
        return '/station';   // Station Team or SAT - Volunteers
    }
    return '/login';
}

function roleMatches(user, pageRole) {
    if (pageRole === 'superadmin') return user.role === 'superadmin';
    if (pageRole === 'company')    return user.role === 'company';
    if (pageRole === 'station')    return user.role === 'team_member' && (user.teamType === 'Station Team' || user.teamType === 'SAT - Volunteers');
    if (pageRole === 'go')         return user.role === 'team_member' && user.teamType === 'GO Team';
    if (pageRole === 'erc')        return user.role === 'team_member' && user.teamType === 'ERC Team';
    return false;
}

/**
 * Call from any protected page. Returns the user object; redirects on failure.
 * `pageRole` matches the `data-cs-role` attribute set on the page container.
 */
export async function guardPage(pageRole) {
    let user;
    try {
        user = await whoami();
    } catch (e) {
        window.location.replace('/login');
        return null;
    }
    if (!roleMatches(user, pageRole)) {
        window.location.replace(dashboardPathFor(user));
        return null;
    }
    return user;
}

/**
 * Used on public pages (home, login). If a session already exists, bounce
 * straight to the role's dashboard.
 */
export async function redirectIfLoggedIn() {
    try {
        const user = await whoami();
        window.location.replace(dashboardPathFor(user));
    } catch {
        // not logged in — stay on public page
    }
}

export function clearWhoAmICache() { cached = null; }
