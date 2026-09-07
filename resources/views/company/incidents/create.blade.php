@extends('layouts.app')
@section('title', 'New Incident')

@section('content')
<div data-cs-role="company">
    <div class="d-flex justify-content-between align-items-start flex-wrap mb-3">
        <div>
            <h1 class="cs-page-title h3">New incident</h1>
            <p class="cs-page-subtitle small">Create an incident and optionally upload a passenger manifest.</p>
        </div>
        <a href="/company" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="cs-stat-card">
                <h5 class="mb-3">Step 1 &mdash; Incident details</h5>
                <form id="cs-incident-form">
                    <div class="mb-3">
                        <label class="form-label">Flight number</label>
                        <input id="cs-inc-flight" type="text" class="form-control" required>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">From</label>
                            <input id="cs-inc-from" type="text" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">To</label>
                            <input id="cs-inc-to" type="text" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date</label>
                            <input id="cs-inc-date" type="date" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Time</label>
                            <input id="cs-inc-time" type="time" class="form-control" required>
                        </div>
                    </div>
                    <div id="cs-inc-error" class="alert alert-danger py-2 small mt-3" hidden></div>
                    <button class="btn btn-primary w-100 mt-3" type="submit" id="cs-inc-submit">
                        Create incident
                    </button>
                </form>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="cs-stat-card" id="cs-manifest-card" style="opacity: 0.5; pointer-events: none;">
                <h5 class="mb-3">Step 2 &mdash; Upload manifest (optional)</h5>
                <p class="text-muted small">
                    Excel file with columns: <code>name</code>, <code>seatNumber</code>, <code>cnic</code> (optional).
                    Parsed in-browser and bulk-uploaded to the incident.
                </p>
                <input id="cs-manifest-file" type="file" class="form-control mb-3" accept=".xlsx,.xls,.csv" disabled>

                <div id="cs-manifest-preview" hidden>
                    <div class="text-muted small mb-2">Preview (first 10 rows):</div>
                    <div class="table-responsive" style="max-height: 240px;">
                        <table class="table table-sm">
                            <thead class="table-light"><tr><th>Name</th><th>Seat</th><th>CNIC</th></tr></thead>
                            <tbody id="cs-manifest-tbody"></tbody>
                        </table>
                    </div>
                    <button class="btn btn-primary w-100 mt-2" id="cs-manifest-upload" disabled>
                        Upload passengers
                    </button>
                </div>

                <div class="text-center mt-3">
                    <a href="#" id="cs-skip-manifest" class="text-muted small">Skip &mdash; add passengers later &rarr;</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
