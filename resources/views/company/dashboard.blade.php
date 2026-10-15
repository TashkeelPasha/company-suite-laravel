@extends('layouts.app')
@section('title', 'Overview · Company Suite')

@section('content')
<div data-cs-role="company">
    <div class="cs-page-head">
        <div>
            <div class="cs-eyebrow" id="cs-today">&nbsp;</div>
            <h1 class="cs-page-title">Welcome back, <span id="cs-company-name">&mdash;</span></h1>
            <p class="cs-page-subtitle">Your incidents, passengers and response teams at a glance.</p>
        </div>
        <div class="cs-page-actions">
            <a href="/company/incidents/new" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> New incident
            </a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="cs-card cs-kpi cs-tone-blue">
                <div class="cs-kpi-icon"><i class="bi bi-broadcast"></i></div>
                <div><div class="cs-kpi-label">Active incidents</div><div class="cs-kpi-value" id="cs-kpi-active">&mdash;</div></div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="cs-card cs-kpi cs-tone-violet">
                <div class="cs-kpi-icon"><i class="bi bi-person-vcard"></i></div>
                <div><div class="cs-kpi-label">Passengers on record</div><div class="cs-kpi-value" id="cs-kpi-pax">&mdash;</div></div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="cs-card cs-kpi cs-tone-green">
                <div class="cs-kpi-icon"><i class="bi bi-check2-circle"></i></div>
                <div><div class="cs-kpi-label">Operations completed</div><div class="cs-kpi-value" id="cs-kpi-closed">&mdash;</div></div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="cs-card cs-kpi cs-tone-amber">
                <div class="cs-kpi-icon"><i class="bi bi-people"></i></div>
                <div><div class="cs-kpi-label">Team members</div><div class="cs-kpi-value" id="cs-kpi-team">&mdash;</div></div>
            </div>
        </div>
    </div>

    <ul class="nav cs-tabs" id="csCompanyTabs" role="tablist">
        <li class="nav-item">
            <button class="nav-link active" id="cs-tab-incidents-btn" data-bs-toggle="tab" data-bs-target="#tab-incidents" type="button" role="tab">
                <i class="bi bi-exclamation-triangle"></i> Incidents
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" id="cs-tab-team-btn" data-bs-toggle="tab" data-bs-target="#tab-team" type="button" role="tab">
                <i class="bi bi-people"></i> Team members
            </button>
        </li>
    </ul>

    <div class="tab-content">
        {{-- Incidents tab --}}
        <div class="tab-pane fade show active" id="tab-incidents" role="tabpanel">
            <div class="cs-card overflow-hidden">
                <div class="cs-card-head">
                    <h2>All incidents <span class="cs-count" id="cs-incidents-count">0</span></h2>
                    <label class="cs-search mb-0">
                        <i class="bi bi-search"></i>
                        <input id="cs-incident-search" type="search" class="form-control form-control-sm" placeholder="Search flight or city…" aria-label="Search incidents">
                    </label>
                </div>
                <div class="table-responsive">
                    <table class="table cs-table align-middle">
                        <thead>
                            <tr>
                                <th>Flight</th>
                                <th>Route</th>
                                <th>Date &amp; time</th>
                                <th>Status</th>
                                <th>Passengers</th>
                                <th class="text-end"></th>
                            </tr>
                        </thead>
                        <tbody id="cs-incidents-tbody">
                            <tr><td colspan="6" class="cs-empty"><span class="spinner-border spinner-border-sm text-primary"></span> Loading incidents&hellip;</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Team members tab --}}
        <div class="tab-pane fade" id="tab-team" role="tabpanel">
            <div class="cs-card overflow-hidden">
                <div class="cs-card-head">
                    <h2>Response teams <span class="cs-count" id="cs-team-count">0</span></h2>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addTeamMemberModal">
                        <i class="bi bi-person-plus"></i> Add team member
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table cs-table align-middle">
                        <thead>
                            <tr>
                                <th>Member</th>
                                <th>Team</th>
                                <th>Added</th>
                                <th class="text-end"></th>
                            </tr>
                        </thead>
                        <tbody id="cs-team-tbody">
                            <tr><td colspan="4" class="cs-empty"><span class="spinner-border spinner-border-sm text-primary"></span> Loading team members&hellip;</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Add / Edit team member modal --}}
<div class="modal fade" id="addTeamMemberModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="cs-team-form">
                <div class="modal-header">
                    <h5 class="modal-title" id="cs-team-modal-title">Add team member</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="cs-team-id">
                    <div class="mb-3">
                        <label class="form-label" for="cs-team-name">Full name</label>
                        <input id="cs-team-name" type="text" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="cs-team-email">Email</label>
                        <input id="cs-team-email" type="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="cs-team-password">Password</label>
                        <input id="cs-team-password" type="password" class="form-control" minlength="8">
                        <div class="form-text">At least 8 characters. Leave blank when editing to keep the current password.</div>
                    </div>
                    <div class="mb-1">
                        <label class="form-label" for="cs-team-type">Team</label>
                        <select id="cs-team-type" class="form-select" required>
                            <option value="Station Team">Station Team</option>
                            <option value="ERC Team">ERC Team</option>
                            <option value="GO Team">GO Team</option>
                            <option value="SAT - Volunteers">SAT - Volunteers</option>
                        </select>
                    </div>
                    <div id="cs-team-error" class="alert alert-danger py-2 small mt-3 mb-0" hidden></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save member</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
