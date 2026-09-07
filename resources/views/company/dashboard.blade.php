@extends('layouts.app')
@section('title', 'Company Dashboard')

@section('content')
<div data-cs-role="company">
    <div class="d-flex justify-content-between align-items-start flex-wrap mb-3">
        <div>
            <h1 class="cs-page-title h3">Company Dashboard</h1>
            <p class="cs-page-subtitle small">Incidents and team members for <span id="cs-company-name">&mdash;</span>.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="/company/incidents/new" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> New incident
            </a>
        </div>
    </div>

    <ul class="nav nav-tabs mb-3" id="csCompanyTabs">
        <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-incidents" type="button">
                <i class="bi bi-exclamation-triangle"></i> Incidents
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-team" type="button">
                <i class="bi bi-people"></i> Team members
            </button>
        </li>
    </ul>

    <div class="tab-content">
        {{-- Incidents tab --}}
        <div class="tab-pane fade show active" id="tab-incidents">
            <div class="cs-stat-card p-0 overflow-hidden">
                <div class="table-responsive">
                    <table class="table mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Flight</th>
                                <th>Route</th>
                                <th>Date &amp; time</th>
                                <th>Status</th>
                                <th>Passengers</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="cs-incidents-tbody">
                            <tr><td colspan="6" class="cs-empty">Loading incidents&hellip;</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Team members tab --}}
        <div class="tab-pane fade" id="tab-team">
            <div class="d-flex justify-content-end mb-3">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTeamMemberModal">
                    <i class="bi bi-plus-lg"></i> Add team member
                </button>
            </div>
            <div class="cs-stat-card p-0 overflow-hidden">
                <div class="table-responsive">
                    <table class="table mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Full name</th>
                                <th>Email</th>
                                <th>Team</th>
                                <th>Created</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="cs-team-tbody">
                            <tr><td colspan="5" class="cs-empty">Loading team members&hellip;</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Add / Edit team member modal --}}
<div class="modal fade" id="addTeamMemberModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="cs-team-form">
                <div class="modal-header">
                    <h5 class="modal-title" id="cs-team-modal-title">Add team member</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="cs-team-id">
                    <div class="mb-3">
                        <label class="form-label">Full name</label>
                        <input id="cs-team-name" type="text" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input id="cs-team-email" type="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password (min 8 chars)</label>
                        <input id="cs-team-password" type="password" class="form-control" minlength="8">
                        <div class="form-text">Leave blank when editing to keep current password.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Team</label>
                        <select id="cs-team-type" class="form-select" required>
                            <option value="Station Team">Station Team</option>
                            <option value="ERC Team">ERC Team</option>
                            <option value="GO Team">GO Team</option>
                            <option value="SAT - Volunteers">SAT - Volunteers</option>
                        </select>
                    </div>
                    <div id="cs-team-error" class="alert alert-danger py-2 small" hidden></div>
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
