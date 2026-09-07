@extends('layouts.app')
@section('title', 'ERC Team &middot; Dashboard')

@section('content')
<div data-cs-role="erc">
    <div class="d-flex justify-content-between align-items-start flex-wrap mb-3">
        <div>
            <h1 class="cs-page-title h3">ERC Team</h1>
            <p class="cs-page-subtitle small">Next-of-kin management for ongoing incidents at <span id="cs-company-name">&mdash;</span>.</p>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="cs-stat-card p-0 overflow-hidden">
                <div class="p-3 border-bottom">
                    <input id="cs-erc-incident-search" type="search" class="form-control form-control-sm" placeholder="Search ongoing incidents&hellip;">
                </div>
                <div id="cs-erc-incidents-list" style="max-height: 70vh; overflow-y: auto;">
                    <div class="cs-empty">Loading incidents&hellip;</div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="cs-stat-card" id="cs-erc-detail" hidden>
                <div class="d-flex justify-content-between align-items-start flex-wrap mb-3">
                    <div>
                        <h4 id="cs-erc-inc-title" class="mb-1">&mdash;</h4>
                        <div class="text-muted small" id="cs-erc-inc-meta"></div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th><th>Seat</th><th>CNIC</th>
                                <th>Latest status</th>
                                <th>Next-of-kin</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="cs-erc-passengers-tbody"><tr><td colspan="6" class="cs-empty">Select an incident.</td></tr></tbody>
                    </table>
                </div>
            </div>
            <div class="cs-stat-card cs-empty" id="cs-erc-detail-empty">
                <i class="bi bi-hand-index-thumb display-6 d-block mb-2"></i>
                Select an ongoing incident on the left.
            </div>
        </div>
    </div>
</div>

{{-- Relative-info modal --}}
<div class="modal fade" id="ercRelativeModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="cs-erc-relative-form">
                <div class="modal-header">
                    <h5 class="modal-title">Next-of-kin &mdash; <span id="cs-erc-relative-name"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="cs-erc-relative-passenger-id">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Contact name</label>
                            <input id="cs-erc-contact-name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Relationship</label>
                            <input id="cs-erc-relationship" class="form-control" required placeholder="e.g. Wife, Father">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Telephone numbers</label>
                            <textarea id="cs-erc-phones" class="form-control" rows="2" required
                                placeholder="+92 300 1234567, +92 21 3456789"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Address (optional)</label>
                            <textarea id="cs-erc-address" class="form-control" rows="2"></textarea>
                        </div>
                    </div>

                    <hr>
                    <h6 class="mb-2">All status updates (read-only)</h6>
                    <div class="table-responsive" style="max-height: 200px;">
                        <table class="table table-sm">
                            <thead class="table-light"><tr><th>When</th><th>Team</th><th>By</th><th>Status</th><th>Remarks</th></tr></thead>
                            <tbody id="cs-erc-updates-tbody"><tr><td colspan="5" class="text-center text-muted small">&mdash;</td></tr></tbody>
                        </table>
                    </div>

                    <h6 class="mb-2 mt-3">Relative-info history</h6>
                    <div class="table-responsive" style="max-height: 200px;">
                        <table class="table table-sm">
                            <thead class="table-light"><tr><th>When</th><th>By</th><th>Contact</th><th>Rel.</th><th>Phones</th></tr></thead>
                            <tbody id="cs-erc-relative-history-tbody"><tr><td colspan="5" class="text-center text-muted small">&mdash;</td></tr></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save relative info</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
