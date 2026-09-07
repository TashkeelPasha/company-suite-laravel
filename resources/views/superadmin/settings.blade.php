@extends('layouts.app')
@section('title', 'SuperAdmin &middot; Settings')

@section('content')
<div data-cs-role="superadmin">
    <div class="d-flex justify-content-between align-items-start flex-wrap mb-3">
        <div>
            <h1 class="cs-page-title h3">Settings</h1>
            <p class="cs-page-subtitle small">Manage your SuperAdmin account.</p>
        </div>
        <a href="/dashboard" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to dashboard
        </a>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="cs-stat-card">
                <h5 class="mb-3">Change password</h5>
                <form id="cs-change-pw-form">
                    <div class="mb-3">
                        <label class="form-label">Current password</label>
                        <input id="cs-cur-pw" type="password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New password (min 8 chars)</label>
                        <input id="cs-new-pw" type="password" class="form-control" minlength="8" required>
                    </div>
                    <div id="cs-change-pw-error" class="alert alert-danger py-2 small" hidden></div>
                    <button class="btn btn-primary" type="submit">Update password</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
