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
                    <div class="d-flex align-items-center gap-2">
                        <span id="cs-go-inc-status" class="cs-status-pill"></span>
                        <a id="cs-go-print-all" href="#" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary" title="ERP-001 for every passenger — latest saved card, or pre-filled from the manifest">
                            <i class="bi bi-printer"></i> Print all PRC
                        </a>
                    </div>
                </div>
                <div class="text-muted small mb-3" id="cs-go-inc-other" hidden></div>

                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr><th>Name</th><th>Seat</th><th>Latest</th><th>Station</th><th>SAT</th><th>Contact person</th><th>Print</th><th class="text-end">Actions</th></tr>
                        </thead>
                        <tbody id="cs-go-passengers-tbody"><tr><td colspan="8" class="cs-empty">Select an incident to load passengers.</td></tr></tbody>
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

@include('erp.update-modal')
@endsection
