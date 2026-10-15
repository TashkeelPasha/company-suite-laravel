<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $form === 'prc' ? 'ERP-001 Passenger Record Card' : 'ERP-002 Passenger Information Form' }}</title>

    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
{{-- Shell only — resources/js/pages/print.js fetches the data with the viewer's role and renders the sheets. --}}
<body class="cs-print-page" data-cs-print data-form="{{ $form }}" data-scope="{{ $scope }}" data-id="{{ $id }}">

    <div class="cs-print-toolbar d-flex align-items-center gap-2 px-3 py-2">
        <strong class="me-auto">{{ $form === 'prc' ? 'ERP-001 · Passenger Record Card' : 'ERP-002 · Passenger Information Form' }}</strong>
        <select id="cs-print-version" class="form-select form-select-sm w-auto" hidden></select>
        <button type="button" class="btn btn-primary btn-sm" id="cs-print-btn" disabled>
            <i class="bi bi-printer"></i> Print / Save as PDF
        </button>
    </div>

    <div id="cs-print-message" class="alert alert-info mx-auto my-3 small" style="max-width: 210mm;" hidden></div>
    <div id="cs-print-sheets">
        <div class="cs-empty">Loading&hellip;</div>
    </div>

    @include('layouts.partials.toast')
</body>
</html>
