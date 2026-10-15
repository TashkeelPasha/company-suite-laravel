import api from './api.js';
import { clearWhoAmICache, dashboardPathFor } from './auth-gate.js';
import { avatarHtml, roleLabel } from './ui.js';

/** Sidebar links per role: [href, icon, label]. The first one is the role's home. */
const NAV = {
    company:    [['/company', 'bi-grid-1x2', 'Overview'], ['/company#team', 'bi-people', 'Team members'], ['/company/incidents/new', 'bi-plus-circle', 'New incident']],
    superadmin: [['/dashboard', 'bi-buildings', 'Companies'], ['/settings', 'bi-gear', 'Settings']],
    station:    [['/station', 'bi-geo-alt', 'Passengers']],
    go:         [['/go', 'bi-broadcast', 'Incidents']],
    erc:        [['/erc', 'bi-telephone', 'Calls & contacts']],
};

function navKey(user) {
    if (user.role === 'company' || user.role === 'superadmin') return user.role;
    return dashboardPathFor(user).slice(1);   // station | go | erc
}

/** Highlights the link for the current page; deeper pages (incident, passenger file) fall back to the role's home. */
function markActive(links) {
    const here = window.location.pathname + window.location.hash;
    let best = null;
    links.forEach(a => {
        const href = a.getAttribute('href');
        if (here === href || (!href.includes('#') && window.location.pathname === href && !window.location.hash)) best = a;
    });
    if (!best) best = links.find(a => window.location.pathname.startsWith(a.getAttribute('href')) && !a.getAttribute('href').includes('#'))
        || links[0];
    links.forEach(a => a.classList.toggle('is-active', a === best));
}

export function mountTopbar(user) {
    if (!user) return;
    const name = user.fullName || user.name || user.email || '';
    const org = user.role === 'company' ? user.name : (user.companyName || 'Company Suite');

    document.body.classList.add('cs-has-nav');
    document.getElementById('cs-topbar-user').hidden = false;
    document.getElementById('cs-topbar-name').textContent = name;
    document.getElementById('cs-topbar-role').textContent = roleLabel(user);
    document.getElementById('cs-topbar-avatar').outerHTML = avatarHtml(name, { cls: 'cs-avatar-sm', id: 'cs-topbar-avatar' });
    document.getElementById('cs-sidebar-org-name').textContent = org;
    document.getElementById('cs-sidebar-org-role').textContent = roleLabel(user);
    document.getElementById('cs-sidebar-avatar').outerHTML = avatarHtml(org, { cls: 'cs-avatar-sm', id: 'cs-sidebar-avatar' });
    if (user.logoUrl) {
        const img = document.getElementById('cs-topbar-logo');
        img.src = user.logoUrl;
        img.hidden = false;
        document.getElementById('cs-topbar-avatar').hidden = true;
    }
    const title = document.querySelector('.cs-page-title');
    document.getElementById('cs-header-title').textContent = title ? '' : document.title.split('·')[0].trim();

    const nav = document.getElementById('cs-nav');
    nav.innerHTML = (NAV[navKey(user)] || []).map(([href, icon, label]) =>
        `<a class="cs-nav-link" href="${href}"><i class="bi ${icon}"></i><span>${label}</span></a>`).join('');
    const links = [...nav.querySelectorAll('a')];
    markActive(links);
    window.addEventListener('hashchange', () => markActive(links));

    // Mobile: slide-in menu
    const close = () => document.body.classList.remove('cs-nav-open');
    document.getElementById('cs-nav-toggle').addEventListener('click', () => document.body.classList.toggle('cs-nav-open'));
    document.getElementById('cs-sidebar-backdrop').addEventListener('click', close);
    links.forEach(a => a.addEventListener('click', close));

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
