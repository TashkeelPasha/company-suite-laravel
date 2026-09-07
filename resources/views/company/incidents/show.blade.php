@extends('layouts.app')
@section('title', 'Incident detail')

@section('content')
<div data-cs-role="company" data-cs-incident-id="{{ $incidentId }}">
    <div class="d-flex justify-content-between align-items-start flex-wrap mb-3">
        <div>
            <h1 class="cs-page-title h3">
                Incident <span id="cs-inc-title">&mdash;</span>
                <span id="cs-inc-status-badge" class="cs-status-pill ms-2"></span>
            </h1>
            <p class="cs-page-subtitle small" id="cs-inc-meta">Loading&hellip;</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-secondary" id="cs-inc-export-xlsx">
                <i class="bi bi-download"></i> Export XLSX
            </button>
            <button class="btn btn-outline-warning" id="cs-inc-toggle-status">
                <i class="bi bi-flag"></i> <span id="cs-inc-toggle-label">Mark complete</span>
            </button>
            <a href="/company" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-3"><div class="cs-stat-card"><div class="text-muted small">Passengers</div><div class="h4 mb-0" id="cs-stat-total">&mdash;</div></div></div>
        <div class="col-md-3"><div class="cs-stat-card"><div class="text-muted small">Safe</div><div class="h4 mb-0 text-success" id="cs-stat-safe">&mdash;</div></div></div>
        <div class="col-md-3"><div class="cs-stat-card"><div class="text-muted small">Injured / Critical</div><div class="h4 mb-0 text-warning" id="cs-stat-injured">&mdash;</div></div></div>
        <div class="col-md-3"><div class="cs-stat-card"><div class="text-muted small">Deceased / Unconfirmed</div><div class="h4 mb-0 text-danger" id="cs-stat-other">&mdash;</div></div></div>
    </div>

    <div class="cs-stat-card p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Seat</th>
                        <th>CNIC</th>
                        <th>Latest status</th>
                        <th>Station</th>
                        <th>GO</th>
                        <th>SAT</th>
                        <th>Next-of-kin</th>
                    </tr>
                </thead>
                <tbody id="cs-inc-passengers-tbody">
                    <tr><td colspan="8" class="cs-empty">Loading passengers&hellip;</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
