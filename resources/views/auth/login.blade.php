@extends('layouts.app')
@section('title', 'Login &middot; Company Suite')

@section('content')
    <div class="row justify-content-center py-5">
        <div class="col-md-6 col-lg-5 col-xl-4">
            <div class="cs-stat-card">
                <h2 class="cs-page-title h4 mb-1">Sign in</h2>
                <p class="cs-page-subtitle small">Select your role, then enter your credentials.</p>

                <form id="cs-login-form" autocomplete="on" novalidate>
                    <div class="mb-3">
                        <label class="form-label small text-muted">Role</label>
                        <select id="cs-login-role" class="form-select" required>
                            <option value="superadmin">SuperAdmin</option>
                            <option value="company">Company Admin</option>
                            <option value="station">Station Team</option>
                            <option value="go">GO Team</option>
                            <option value="erc">ERC Team</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small text-muted">Email</label>
                        <input id="cs-login-email" type="email" class="form-control" autocomplete="email" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small text-muted">Password</label>
                        <input id="cs-login-password" type="password" class="form-control" autocomplete="current-password" required>
                    </div>

                    <div id="cs-login-error" class="alert alert-danger py-2 small" hidden></div>

                    <button type="submit" class="btn btn-primary w-100" id="cs-login-submit">
                        <span class="cs-btn-label">Sign in</span>
                        <span class="spinner-border spinner-border-sm cs-btn-spinner" hidden></span>
                    </button>
                </form>

                <p class="text-muted small text-center mt-3 mb-0">
                    <a href="/" class="text-decoration-none">&larr; Back to home</a>
                </p>
            </div>
        </div>
    </div>
@endsection
