@extends('layouts.app')
@section('title', 'SuperAdmin &middot; Dashboard')

@section('content')
<div data-cs-role="superadmin">
    <div class="d-flex justify-content-between align-items-start flex-wrap mb-3">
        <div>
            <h1 class="cs-page-title h3">Companies</h1>
            <p class="cs-page-subtitle small">All companies registered on the platform.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="/settings" class="btn btn-outline-secondary">
                <i class="bi bi-gear"></i> Settings
            </a>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCompanyModal">
                <i class="bi bi-plus-lg"></i> Add company
            </button>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="cs-stat-card">
                <div class="text-muted small">Total companies</div>
                <div class="h3 mb-0" id="cs-stat-total">&mdash;</div>
            </div>
        </div>
    </div>

    <div class="cs-stat-card p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Logo</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Created</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody id="cs-companies-tbody">
                    <tr><td colspan="5" class="cs-empty">Loading companies&hellip;</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Add / Edit company modal --}}
<div class="modal fade" id="addCompanyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="cs-company-form">
                <div class="modal-header">
                    <h5 class="modal-title" id="cs-company-modal-title">Add company</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="cs-company-id">
                    <div class="mb-3">
                        <label class="form-label">Company name</label>
                        <input id="cs-company-name" type="text" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Admin email</label>
                        <input id="cs-company-email" type="email" class="form-control" required>
                    </div>
                    <div class="mb-3" id="cs-company-password-wrap">
                        <label class="form-label">Password (min 8 chars)</label>
                        <input id="cs-company-password" type="password" class="form-control" minlength="8">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Logo (optional)</label>
                        <input id="cs-company-logo" type="file" class="form-control" accept="image/*">
                        <div class="form-text">Uploaded to <code>/api/uploads</code>. The URL is saved on the company.</div>
                    </div>
                    <div id="cs-company-error" class="alert alert-danger py-2 small" hidden></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Reset password modal --}}
<div class="modal fade" id="resetPwModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="cs-resetpw-form">
                <div class="modal-header">
                    <h5 class="modal-title">Reset company password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="cs-resetpw-id">
                    <p class="text-muted small mb-3">Setting a new password for <strong id="cs-resetpw-name"></strong>.</p>
                    <div class="mb-3">
                        <label class="form-label">New password (min 8 chars)</label>
                        <input id="cs-resetpw-value" type="password" class="form-control" minlength="8" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
