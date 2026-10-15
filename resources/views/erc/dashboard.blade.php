@extends('layouts.app')
@section('title', 'Calls & contacts · ERC · Company Suite')

@section('content')
<div data-cs-role="erc">
    <div class="cs-page-head">
        <div>
            <div class="cs-eyebrow">Emergency Response Centre</div>
            <h1 class="cs-page-title">Calls &amp; contact persons</h1>
            <p class="cs-page-subtitle">Ongoing incidents at <span id="cs-company-name">&mdash;</span>. Set one contact person per passenger &mdash; other callers are referred to them.</p>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <div class="cs-card cs-kpi cs-tone-blue">
                <div class="cs-kpi-icon"><i class="bi bi-broadcast"></i></div>
                <div><div class="cs-kpi-label">Ongoing incidents</div><div class="cs-kpi-value" id="cs-erc-kpi-incidents">&mdash;</div></div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="cs-card cs-kpi cs-tone-violet">
                <div class="cs-kpi-icon"><i class="bi bi-people"></i></div>
                <div><div class="cs-kpi-label">Passengers</div><div class="cs-kpi-value" id="cs-erc-kpi-pax">&mdash;</div></div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="cs-card cs-kpi cs-tone-green">
                <div class="cs-kpi-icon"><i class="bi bi-telephone-inbound"></i></div>
                <div><div class="cs-kpi-label">Contact person set</div><div class="cs-kpi-value" id="cs-erc-kpi-set">&mdash;</div></div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="cs-card cs-kpi cs-tone-amber">
                <div class="cs-kpi-icon"><i class="bi bi-hourglass-split"></i></div>
                <div><div class="cs-kpi-label">Awaiting contact</div><div class="cs-kpi-value" id="cs-erc-kpi-pending">&mdash;</div></div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4 col-xl-3">
            <div class="cs-card overflow-hidden">
                <div class="cs-card-head">
                    <h2>Ongoing incidents</h2>
                </div>
                <div class="p-3 border-bottom">
                    <label class="cs-search mb-0 w-100">
                        <i class="bi bi-search"></i>
                        <input id="cs-erc-incident-search" type="search" class="form-control form-control-sm" placeholder="Search flight or city…" aria-label="Search incidents">
                    </label>
                </div>
                <div id="cs-erc-incidents-list" class="cs-inc-list">
                    <div class="cs-empty"><span class="spinner-border spinner-border-sm text-primary"></span> Loading incidents&hellip;</div>
                </div>
            </div>
        </div>

        <div class="col-lg-8 col-xl-9">
            <div id="cs-erc-detail" hidden>
                <div class="cs-card cs-hero cs-hero-compact">
                    <div class="d-flex gap-3 align-items-start">
                        <span class="cs-flight-tile" style="width:46px;height:46px;border-radius:12px;font-size:1.2rem"><i class="bi bi-airplane-fill"></i></span>
                        <div class="min-w-0 flex-grow-1">
                            <h2 class="cs-page-title h4 mb-1" id="cs-erc-inc-title">&mdash;</h2>
                            <div class="cs-page-subtitle" id="cs-erc-inc-meta"></div>
                        </div>
                    </div>
                </div>

                <div class="alert cs-callout mb-3">
                    <i class="bi bi-pin-angle"></i>
                    <div>When a relative calls, open the passenger and <strong>set the contact person</strong>. If one is already set, give updates only to them and ask other callers to contact that person.</div>
                </div>

                <div class="cs-card overflow-hidden">
                    <div class="cs-card-head">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <h2 class="me-2">Passengers <span class="cs-count" id="cs-erc-pax-count">0</span></h2>
                            <div class="cs-filter" role="group" aria-label="Filter passengers">
                                <button type="button" class="is-active" data-filter="all">All</button>
                                <button type="button" data-filter="pending">Awaiting contact</button>
                                <button type="button" data-filter="set">Contact set</button>
                            </div>
                        </div>
                        <label class="cs-search mb-0">
                            <i class="bi bi-search"></i>
                            <input id="cs-erc-pax-search" type="search" class="form-control form-control-sm" placeholder="Search name, seat or CNIC…" aria-label="Search passengers">
                        </label>
                    </div>
                    <div class="table-responsive">
                        <table class="table cs-table align-middle">
                            <thead>
                                <tr>
                                    <th>Passenger</th>
                                    <th>Latest status</th>
                                    <th>Contact person</th>
                                    <th class="text-end"></th>
                                </tr>
                            </thead>
                            <tbody id="cs-erc-passengers-tbody"><tr><td colspan="4" class="cs-empty">Select an incident.</td></tr></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="cs-card cs-empty" id="cs-erc-detail-empty">
                <i class="bi bi-hand-index-thumb"></i>
                <div class="cs-empty-title">Choose an incident</div>
                Select an ongoing incident on the left to see its passengers.
            </div>
        </div>
    </div>
</div>

@include('erp.pif-modal')
@endsection
