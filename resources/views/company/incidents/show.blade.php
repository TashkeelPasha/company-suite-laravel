@extends('layouts.app')
@section('title', 'Incident · Company Suite')

@section('content')
<div data-cs-role="company" data-cs-incident-id="{{ $incidentId }}">
    <nav class="cs-breadcrumb" aria-label="Breadcrumb">
        <a href="/company"><i class="bi bi-grid-1x2"></i> Overview</a>
        <i class="bi bi-chevron-right small"></i>
        <span>Incident</span>
    </nav>

    <div class="cs-card cs-hero">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div class="d-flex gap-3 align-items-start min-w-0 cs-hero-main">
                <span class="cs-flight-tile" style="width:52px;height:52px;border-radius:14px;font-size:1.35rem"><i class="bi bi-airplane-fill"></i></span>
                <div class="min-w-0 flex-grow-1">
                    <div class="d-flex align-items-center flex-wrap gap-2">
                        <h1 class="cs-page-title mb-0">Flight <span id="cs-inc-title">&mdash;</span></h1>
                        <span id="cs-inc-status-badge" class="cs-status-pill"></span>
                    </div>
                    <div class="cs-page-subtitle mt-1" id="cs-inc-meta">Loading&hellip;</div>
                </div>
            </div>
            <div class="cs-page-actions">
                <div class="btn-group">
                    <a class="btn btn-light border" id="cs-inc-print-all" href="/print/prc/incidents/{{ $incidentId }}" target="_blank" rel="noopener"
                       title="ERP-001 for every passenger — latest saved card, or pre-filled from the manifest">
                        <i class="bi bi-printer"></i> Print all PRC
                    </a>
                    <button type="button" class="btn btn-light border dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="visually-hidden">More print options</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li><a class="dropdown-item" href="/print/prc/blank" target="_blank" rel="noopener"><i class="bi bi-file-earmark me-2"></i>Blank PRC (ERP-001)</a></li>
                        <li><a class="dropdown-item" href="/print/pif/blank" target="_blank" rel="noopener"><i class="bi bi-file-earmark me-2"></i>Blank PIF (ERP-002)</a></li>
                    </ul>
                </div>
                <button class="btn btn-light border" id="cs-inc-export-xlsx">
                    <i class="bi bi-file-earmark-spreadsheet"></i> Export Excel
                </button>
                <button class="btn btn-warning" id="cs-inc-toggle-status">
                    <i class="bi bi-flag"></i> <span id="cs-inc-toggle-label">Mark complete</span>
                </button>
            </div>
        </div>
        <div class="cs-hero-foot">
            <div class="cs-hero-route" id="cs-inc-route"></div>
            <p class="small text-muted mb-0" id="cs-inc-other" hidden></p>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3"><div class="cs-card cs-kpi cs-tone-blue"><div class="cs-kpi-icon"><i class="bi bi-people"></i></div><div><div class="cs-kpi-label">Passengers</div><div class="cs-kpi-value" id="cs-stat-total">&mdash;</div></div></div></div>
        <div class="col-6 col-xl-3"><div class="cs-card cs-kpi cs-tone-green"><div class="cs-kpi-icon"><i class="bi bi-shield-check"></i></div><div><div class="cs-kpi-label">Safe</div><div class="cs-kpi-value" id="cs-stat-safe">&mdash;</div></div></div></div>
        <div class="col-6 col-xl-3"><div class="cs-card cs-kpi cs-tone-amber"><div class="cs-kpi-icon"><i class="bi bi-bandaid"></i></div><div><div class="cs-kpi-label">Injured / critical</div><div class="cs-kpi-value" id="cs-stat-injured">&mdash;</div></div></div></div>
        <div class="col-6 col-xl-3"><div class="cs-card cs-kpi cs-tone-red"><div class="cs-kpi-icon"><i class="bi bi-question-octagon"></i></div><div><div class="cs-kpi-label">Deceased / unconfirmed</div><div class="cs-kpi-value" id="cs-stat-other">&mdash;</div></div></div></div>
    </div>

    <div class="cs-card overflow-hidden">
        <div class="cs-card-head">
            <h2>Passengers <span class="cs-count" id="cs-pax-count">0</span></h2>
            <label class="cs-search mb-0">
                <i class="bi bi-search"></i>
                <input id="cs-pax-search" type="search" class="form-control form-control-sm" placeholder="Search name, seat or CNIC…" aria-label="Search passengers">
            </label>
        </div>
        <div class="table-responsive">
            <table class="table cs-table align-middle">
                <thead>
                    <tr>
                        <th>Passenger</th>
                        <th>Latest status</th>
                        <th>By team</th>
                        <th>Contact person</th>
                        <th class="text-end">Forms</th>
                    </tr>
                </thead>
                <tbody id="cs-inc-passengers-tbody">
                    <tr><td colspan="5" class="cs-empty"><span class="spinner-border spinner-border-sm text-primary"></span> Loading passengers&hellip;</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

@include('erp.passenger-modal')
@endsection
