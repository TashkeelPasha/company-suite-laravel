/**
 * Entry point. Loads Bootstrap once, then dispatches to a page-specific
 * initialiser based on the current URL path.
 */
import 'bootstrap';

async function boot() {
    const path = window.location.pathname;

    // Public pages
    if (path === '/' || path === '/home') {
        return;   // home has inline module that triggers redirectIfLoggedIn
    }
    if (path === '/login') {
        const { initLoginPage } = await import('./pages/login.js');
        return initLoginPage();
    }

    // SuperAdmin
    if (path === '/dashboard') {
        const { initSuperadminDashboard } = await import('./pages/superadmin.js');
        return initSuperadminDashboard();
    }
    if (path === '/settings') {
        const { initSuperadminSettings } = await import('./pages/superadmin.js');
        return initSuperadminSettings();
    }

    // Company
    if (path === '/company') {
        const { initCompanyDashboard } = await import('./pages/company-dashboard.js');
        return initCompanyDashboard();
    }
    if (path === '/company/incidents/new') {
        const { initCompanyAddIncident } = await import('./pages/company-add-incident.js');
        return initCompanyAddIncident();
    }
    if (/^\/company\/incidents\/\d+$/.test(path)) {
        const { initCompanyIncidentDetail } = await import('./pages/company-incident-detail.js');
        return initCompanyIncidentDetail();
    }

    // Team dashboards
    if (path.startsWith('/station')) {
        const { initStationDashboard } = await import('./pages/station.js');
        return initStationDashboard();
    }
    if (path.startsWith('/go')) {
        const { initGoDashboard } = await import('./pages/go.js');
        return initGoDashboard();
    }
    if (path.startsWith('/erc')) {
        const { initErcDashboard } = await import('./pages/erc.js');
        return initErcDashboard();
    }
}

document.addEventListener('DOMContentLoaded', () => { boot(); });
