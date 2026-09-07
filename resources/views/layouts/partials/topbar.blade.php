{{-- Topbar populated by JS after /api/auth/whoami resolves. See resources/js/topbar.js --}}
<nav class="navbar cs-topbar navbar-expand-lg px-3 px-lg-4">
    <div class="container-fluid">
        <a class="navbar-brand cs-brand" href="/">Company&nbsp;Suite</a>

        <div class="d-flex align-items-center ms-auto" id="cs-topbar-user" hidden>
            <img id="cs-topbar-logo" class="cs-logo me-2" alt="" hidden>
            <span id="cs-topbar-name" class="me-3 text-muted small"></span>
            <button id="cs-topbar-logout" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-box-arrow-right"></i> Logout
            </button>
        </div>
    </div>
</nav>
