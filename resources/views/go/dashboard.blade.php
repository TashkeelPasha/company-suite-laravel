@extends('layouts.app')
@section('title', 'GO Team &middot; Dashboard')

@section('content')
<div data-cs-role="go">
    <div class="d-flex justify-content-between align-items-start flex-wrap mb-3">
        <div>
            <h1 class="cs-page-title h3">GO Team</h1>
            <p class="cs-page-subtitle small">Full incident visibility for <span id="cs-company-name">&mdash;</span>.</p>
        </div>
    </div>

    <div class="row g-4">
        {{-- Left: incident list --}}
        <div class="col-lg-4">
            <div class="cs-stat-card p-0 overflow-hidden">
                <div class="p-3 border-bottom">
                    <input id="cs-go-incident-search" type="search" class="form-control form-control-sm" placeholder="Search incidents&hellip;">
                </div>
                <div id="cs-go-incidents-list" style="max-height: 70vh; overflow-y: auto;">
                    <div class="cs-empty">Loading incidents&hellip;</div>
                </div>
            </div>
        </div>

        {{-- Right: selected incident detail --}}
        <div class="col-lg-8">
            <div class="cs-stat-card" id="cs-go-detail" hidden>
                <div class="d-flex justify-content-between align-items-start flex-wrap mb-3">
                    <div>
                        <h4 id="cs-go-inc-title" class="mb-1">&mdash;</h4>
                        <div class="text-muted small" id="cs-go-inc-meta"></div>
                    </div>
                    <span id="cs-go-inc-status" class="cs-status-pill"></span>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr><th>Name</th><th>Seat</th><th>Latest</th><th>Station</th><th>SAT</th><th class="text-end">Actions</th></tr>
                        </thead>
                        <tbody id="cs-go-passengers-tbody"><tr><td colspan="6" class="cs-empty">Select an incident to load passengers.</td></tr></tbody>
                    </table>
                </div>
            </div>
            <div class="cs-stat-card cs-empty" id="cs-go-detail-empty">
                <i class="bi bi-hand-index-thumb display-6 d-block mb-2"></i>
                Select an incident on the left to view its passengers.
            </div>
        </div>
    </div>
</div>

{{-- Update status modal (shared markup with Station) --}}
<div class="modal fade" id="goUpdateStatusModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="cs-go-update-form">
                <div class="modal-header">
                    <h5 class="modal-title">Update passenger &mdash; <span id="cs-go-update-name"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="cs-go-update-passenger-id">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select id="cs-go-update-status" class="form-select" required>
                                <option>Safe</option><option>Injured</option><option>Critical</option>
                                <option>Deceased</option><option>Unconfirmed</option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Remarks (optional)</label>
                            <input id="cs-go-update-remarks" class="form-control" type="text">
                        </div>
                    </div>

                    <hr>
                    <h6 class="mb-2">Full update history</h6>
                    <div class="table-responsive" style="max-height: 300px;">
                        <table class="table table-sm">
                            <thead class="table-light"><tr><th>When</th><th>By</th><th>Status</th><th>Remarks</th></tr></thead>
                            <tbody id="cs-go-history-tbody"><tr><td colspan="4" class="text-center text-muted small">&mdash;</td></tr></tbody>
                        </table>
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
