@extends('layouts.app')
@section('title', 'Station Team &middot; Dashboard')

@section('content')
<div data-cs-role="station">
    <div class="d-flex justify-content-between align-items-start flex-wrap mb-3">
        <div>
            <h1 class="cs-page-title h3">Station Team</h1>
            <p class="cs-page-subtitle small">Update passenger status and record card (PRC) answers for <span id="cs-company-name">&mdash;</span>.</p>
        </div>
        <div class="input-group" style="max-width: 320px;">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input id="cs-passenger-search" type="search" class="form-control" placeholder="Search name, seat, CNIC">
        </div>
    </div>

    <div class="cs-stat-card p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Seat</th>
                        <th>Flight</th>
                        <th>Cnic</th>
                        <th>Latest status</th>
                        <th>Last update</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody id="cs-station-tbody">
                    <tr><td colspan="7" class="cs-empty">Loading passengers&hellip;</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

@include('erp.update-modal')
@endsection
