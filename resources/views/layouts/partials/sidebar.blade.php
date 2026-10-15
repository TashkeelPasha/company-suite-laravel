{{-- Left navigation. Links depend on the role — built by resources/js/topbar.js. --}}
<aside class="cs-sidebar" id="cs-sidebar" aria-label="Main navigation">
    <a class="cs-sidebar-brand" href="/">
        <span class="cs-brand-mark"><i class="bi bi-airplane-fill"></i></span>
        <span>Company Suite</span>
    </a>

    <div class="cs-sidebar-org">
        <span class="cs-avatar cs-avatar-sm" id="cs-sidebar-avatar"></span>
        <div class="min-w-0">
            <div class="cs-sidebar-org-name" id="cs-sidebar-org-name">&nbsp;</div>
            <div class="cs-sidebar-org-role" id="cs-sidebar-org-role">&nbsp;</div>
        </div>
    </div>

    <div class="cs-nav-label">Menu</div>
    <nav class="cs-nav" id="cs-nav"></nav>

    <div class="cs-sidebar-foot">
        <button id="cs-topbar-logout" type="button" class="btn btn-sm">
            <i class="bi bi-box-arrow-right"></i> Sign out
        </button>
    </div>
</aside>
<div class="cs-sidebar-backdrop" id="cs-sidebar-backdrop"></div>
