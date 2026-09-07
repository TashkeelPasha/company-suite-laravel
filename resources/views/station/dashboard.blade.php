@extends('layouts.app')
@section('title', 'Station Team &middot; Dashboard')

@section('content')
<div data-cs-role="station">
    <div class="d-flex justify-content-between align-items-start flex-wrap mb-3">
        <div>
            <h1 class="cs-page-title h3">Station Team</h1>
            <p class="cs-page-subtitle small">Update passenger status for <span id="cs-company-name">&mdash;</span>.</p>
        </div>
        <div class="input-group" style="max-width: 320px;">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input id="cs-passenger-search" type="search" class="form-control" placeholder="Search name, seat, CNIC">
        </div>
    </div>

    <div class="cs-stat-card p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Seat</th>
                        <th>Flight</th>
                        <th>Latest status</th>
                        <th>Last update</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody id="cs-station-tbody">
                    <tr><td colspan="6" class="cs-empty">Loading passengers&hellip;</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Update status modal --}}
<div class="modal fade" id="updateStatusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="cs-update-form">
                <div class="modal-header">
                    <h5 class="modal-title">Update status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="cs-update-passenger-id">
                    <p class="text-muted small">Passenger: <strong id="cs-update-passenger-name"></strong> (seat <span id="cs-update-passenger-seat"></span>)</p>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select id="cs-update-status" class="form-select" required>
                            <option value="Safe">Safe</option>
                            <option value="Injured">Injured</option>
                            <option value="Critical">Critical</option>
                            <option value="Deceased">Deceased</option>
                            <option value="Unconfirmed">Unconfirmed</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Remarks (optional)</label>
                        <textarea id="cs-update-remarks" class="form-control" rows="3" placeholder="Additional context&hellip;"></textarea>
                    </div>
                    <div class="mt-3">
                        <details>
                            <summary class="small text-muted mb-2" style="cursor: pointer;">Show past updates</summary>
                            <div class="table-responsive" style="max-height: 200px;">
                                <table class="table table-sm">
                                    <thead class="table-light"><tr><th>When</th><th>By</th><th>Status</th><th>Remarks</th></tr></thead>
                                    <tbody id="cs-update-history-tbody"><tr><td colspan="4" class="text-center text-muted small">&mdash;</td></tr></tbody>
                                </table>
                            </div>
                        </details>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save update</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
