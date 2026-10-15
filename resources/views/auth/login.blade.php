@extends('layouts.app')
@section('title', 'Sign in · Company Suite')

@section('content')
    <div class="cs-auth">
        <div class="cs-card cs-auth-card">
            <div class="cs-auth-side">
                <span class="cs-brand-mark" style="width:44px;height:44px;border-radius:12px;font-size:1.2rem"><i class="bi bi-airplane-fill"></i></span>
                <div>
                    <h2 class="mb-2">Airline incident response, in one place.</h2>
                    <p class="mb-0" style="color:#a9b4cc">Track every passenger from the manifest to the last update — for the company, the ground teams and the call centre.</p>
                </div>
                <ul class="cs-auth-points">
                    <li><i class="bi bi-file-earmark-spreadsheet"></i><span><strong class="text-white">Company</strong> uploads the manifest and follows every incident.</span></li>
                    <li><i class="bi bi-geo-alt"></i><span><strong class="text-white">Station, SAT &amp; GO</strong> send live status and record-card answers.</span></li>
                    <li><i class="bi bi-telephone"></i><span><strong class="text-white">ERC</strong> keeps one contact person per passenger.</span></li>
                    <li><i class="bi bi-printer"></i><span>ERP-001 / ERP-002 forms print exactly like the paper ones.</span></li>
                </ul>
            </div>

            <div class="cs-auth-form">
                <h1 class="cs-page-title h3 mb-1">Sign in</h1>
                <p class="cs-page-subtitle mb-4">Choose your role and enter your account details.</p>

                <form id="cs-login-form" autocomplete="on" novalidate>
                    <div class="mb-3">
                        <label class="form-label" for="cs-login-role">I am signing in as</label>
                        <select id="cs-login-role" class="form-select form-select-lg" required>
                            <option value="superadmin">SuperAdmin</option>
                            <option value="company">Company Admin</option>
                            <option value="station">Station Team / SAT</option>
                            <option value="go">GO Team</option>
                            <option value="erc">ERC Team</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="cs-login-email">Email</label>
                        <input id="cs-login-email" type="email" class="form-control form-control-lg" autocomplete="email" placeholder="you@airline.com" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label" for="cs-login-password">Password</label>
                        <input id="cs-login-password" type="password" class="form-control form-control-lg" autocomplete="current-password" required>
                    </div>

                    <div id="cs-login-error" class="alert alert-danger py-2 small" hidden></div>

                    <button type="submit" class="btn btn-primary btn-lg w-100" id="cs-login-submit">
                        <span class="cs-btn-label">Sign in</span>
                        <span class="spinner-border spinner-border-sm cs-btn-spinner" hidden></span>
                    </button>
                </form>

                <p class="text-muted small text-center mt-4 mb-0">
                    <a href="/" class="text-muted"><i class="bi bi-arrow-left"></i> Back to home</a>
                </p>
            </div>
        </div>
    </div>
@endsection
