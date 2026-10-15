{{-- Header. The user chip is filled by resources/js/topbar.js after /api/auth/whoami resolves. --}}
<header class="cs-header">
    <button type="button" class="btn btn-soft btn-icon cs-nav-toggle" id="cs-nav-toggle" aria-label="Open menu">
        <i class="bi bi-list fs-5"></i>
    </button>

    <a class="cs-header-brand" href="/">
        <span class="cs-brand-mark"><i class="bi bi-airplane-fill"></i></span>
        <span>Company Suite</span>
    </a>
    <div class="cs-header-title" id="cs-header-title"></div>

    <div class="cs-user" id="cs-topbar-user" hidden>
        <img id="cs-topbar-logo" class="cs-logo" alt="" hidden>
        <span class="cs-avatar cs-avatar-sm" id="cs-topbar-avatar"></span>
        <div class="cs-user-text">
            <div class="cs-user-name" id="cs-topbar-name"></div>
            <div class="cs-user-role" id="cs-topbar-role"></div>
        </div>
    </div>
</header>
