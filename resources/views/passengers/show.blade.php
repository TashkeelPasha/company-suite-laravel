@extends('layouts.app')
@section('title', 'Passenger file')

{{--
    Passenger File — one page per passenger for every role (resources/js/pages/passenger-file.js).
    The API decides what each role sees; the action buttons depend on the role:
    Company → passenger details · Station / SAT / GO → status update (+ PRC questions) · ERC → contact person.
--}}
@section('content')
<div data-cs-passenger-file data-id="{{ $passengerId }}">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
        <div>
            <a href="#" id="cs-pf-back" class="small text-decoration-none"><i class="bi bi-arrow-left"></i> Back</a>
            <h1 class="cs-page-title h3 mb-1 mt-1">
                <span id="cs-pf-name">Passenger</span>
                <span id="cs-pf-status" class="ms-2"></span>
            </h1>
            <div class="cs-page-subtitle small" id="cs-pf-sub">Loading&hellip;</div>
        </div>
        <div class="d-flex flex-wrap gap-2" id="cs-pf-actions"></div>
    </div>

    <div id="cs-pf-error" class="alert alert-danger" hidden></div>

    <div class="row g-3" id="cs-pf-body" hidden>
        <div class="col-lg-4">
            <div class="cs-stat-card mb-3">
                <h6 class="text-muted text-uppercase small mb-2">Status by team</h6>
                <div id="cs-pf-team-status" class="small"></div>
            </div>
            <div class="cs-stat-card mb-3" id="cs-pf-contact-card">
                <h6 class="text-muted text-uppercase small mb-2"><i class="bi bi-telephone"></i> Contact person</h6>
                <div id="cs-pf-contact" class="small"></div>
            </div>
            <div class="cs-stat-card mb-3">
                <h6 class="text-muted text-uppercase small mb-2">Passenger</h6>
                <dl class="cs-pf-dl small mb-0" id="cs-pf-details"></dl>
            </div>
            <div class="cs-stat-card">
                <h6 class="text-muted text-uppercase small mb-2">Record card (PRC) &mdash; latest</h6>
                <dl class="cs-pf-dl small mb-0" id="cs-pf-prc"></dl>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="cs-stat-card">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="mb-0">Timeline</h5>
                    <span class="small text-muted" id="cs-pf-refreshed"></span>
                </div>
                <ul class="cs-pf-timeline list-unstyled mb-0" id="cs-pf-timeline"></ul>
            </div>
        </div>
    </div>
</div>

@include('erp.passenger-modal')
@include('erp.update-modal')
@include('erp.pif-modal')
@endsection
