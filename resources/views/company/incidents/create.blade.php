@extends('layouts.app')
@section('title', 'New incident · Company Suite')

@section('content')
<div data-cs-role="company">
    <nav class="cs-breadcrumb" aria-label="Breadcrumb">
        <a href="/company"><i class="bi bi-grid-1x2"></i> Overview</a>
        <i class="bi bi-chevron-right small"></i>
        <span>New incident</span>
    </nav>

    <div class="cs-page-head">
        <div>
            <h1 class="cs-page-title">Report a new incident</h1>
            <p class="cs-page-subtitle">Enter the flight details, then upload the passenger manifest from Excel.</p>
        </div>
    </div>

    <div class="cs-steps" aria-label="Progress">
        <div class="cs-step is-active" id="cs-step-1"><span class="cs-step-num">1</span> Incident details</div>
        <span class="cs-step-line"></span>
        <div class="cs-step" id="cs-step-2"><span class="cs-step-num">2</span> Passenger manifest</div>
    </div>

    <div class="row g-4">
        <div class="col-xl-5">
            <div class="cs-card cs-card-body">
                <div class="cs-step-title"><i class="bi bi-airplane text-primary"></i> Flight &amp; incident</div>
                <p class="text-muted small mb-4">These details appear at the top of every PRC and PIF form.</p>
                <form id="cs-incident-form">
                    <div class="mb-3">
                        <label class="form-label" for="cs-inc-flight">Flight number</label>
                        <input id="cs-inc-flight" type="text" class="form-control" placeholder="e.g. PK-301" required>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="cs-inc-from">From</label>
                            <input id="cs-inc-from" type="text" class="form-control" placeholder="Karachi (KHI)" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="cs-inc-to">To</label>
                            <input id="cs-inc-to" type="text" class="form-control" placeholder="Lahore (LHE)" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="cs-inc-via">Via <span class="text-muted fw-normal">(optional)</span></label>
                            <input id="cs-inc-via" type="text" class="form-control" placeholder="Intermediate stop — leave empty for a direct flight">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="cs-inc-date">Date</label>
                            <input id="cs-inc-date" type="date" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="cs-inc-time">Time</label>
                            <input id="cs-inc-time" type="time" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="cs-inc-other">Other details <span class="text-muted fw-normal">(optional)</span></label>
                            <textarea id="cs-inc-other" class="form-control" rows="3" placeholder="What happened, where, anything the teams should know"></textarea>
                        </div>
                    </div>
                    <div id="cs-inc-error" class="alert alert-danger py-2 small mt-3 mb-0" hidden></div>
                    <button class="btn btn-primary btn-lg w-100 mt-4" type="submit" id="cs-inc-submit">
                        Create incident <i class="bi bi-arrow-right"></i>
                    </button>
                </form>
            </div>
        </div>

        <div class="col-xl-7">
            <div class="cs-card cs-card-body cs-step-card is-locked" id="cs-manifest-card">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-1">
                    <div class="cs-step-title"><i class="bi bi-file-earmark-spreadsheet text-primary"></i> Passenger manifest</div>
                    <button type="button" class="btn btn-sm btn-soft-primary" id="cs-manifest-template">
                        <i class="bi bi-download"></i> Excel template
                    </button>
                </div>
                <p class="text-muted small mb-3">
                    One row per passenger. Required: <code>lastName</code> + <code>firstName</code> (or <code>name</code>) and
                    <code>seatNumber</code> (not for infants). Recommended: <code>paxType</code>, <code>dateOfBirth</code>,
                    <code>nationality</code>, <code>pnr</code>. Only booking data goes here &mdash; injury, intentions and
                    next-of-kin answers come from Station / SAT / GO, the contact person from ERC.
                </p>

                <label class="cs-dropzone mb-3" for="cs-manifest-file">
                    <i class="bi bi-cloud-arrow-up"></i>
                    <span class="cs-dropzone-title" id="cs-dropzone-title">Choose an Excel or CSV file</span>
                    <span class="small" id="cs-dropzone-hint">.xlsx, .xls or .csv &middot; checked in your browser before upload</span>
                    <input id="cs-manifest-file" type="file" class="visually-hidden" accept=".xlsx,.xls,.csv" disabled>
                </label>

                <div id="cs-manifest-preview" hidden>
                    <div class="small mb-2" id="cs-manifest-summary"></div>
                    <div class="table-responsive border rounded-3" style="max-height: 340px;">
                        <table class="table table-sm cs-table align-middle">
                            <thead><tr><th>Row</th><th>Name</th><th>Seat</th><th>Type</th><th>DOB</th><th>Nationality</th><th>PNR</th><th style="min-width: 10rem;">Check</th></tr></thead>
                            <tbody id="cs-manifest-tbody"></tbody>
                        </table>
                    </div>
                    <button class="btn btn-primary btn-lg w-100 mt-3" id="cs-manifest-upload" disabled>
                        Upload passengers
                    </button>
                </div>

                <div class="text-center mt-3">
                    <a href="#" id="cs-skip-manifest" class="text-muted small">Skip &mdash; add passengers later <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
