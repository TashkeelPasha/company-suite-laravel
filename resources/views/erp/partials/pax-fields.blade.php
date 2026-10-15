{{--
    Company's part of ERP-001: the passenger's booking / manifest data (passengers table).
    Same columns as the Excel manifest. Used by erp.passenger-modal ($p = "pax").
--}}
@php($pax = ['attr' => 'data-pax', 'idp' => $p.'-'])
<div class="cs-erp-modal-section mt-0">Passenger information</div>
<div class="row g-2">
    @include('erp.partials.field', $pax + ['name' => 'lastName', 'label' => 'Last (family) name', 'req' => true, 'col' => 'col-md-4'])
    @include('erp.partials.field', $pax + ['name' => 'firstName', 'label' => 'First name(s)', 'req' => true, 'col' => 'col-md-4'])
    @include('erp.partials.field', $pax + ['name' => 'seatNumber', 'label' => 'Seat', 'col' => 'col-md-2'])
    @include('erp.partials.field', $pax + ['name' => 'paxType', 'label' => 'M/F/C/I', 'type' => 'paxType', 'req' => true, 'col' => 'col-md-2'])
    @include('erp.partials.field', $pax + ['name' => 'dateOfBirth', 'label' => 'Date of birth', 'type' => 'date', 'req' => true, 'col' => 'col-md-3'])
    <div class="col-md-1">
        <label class="form-label small mb-1">Age</label>
        <input id="cs-{{ $p }}-age" class="form-control form-control-sm" readonly tabindex="-1">
    </div>
    @include('erp.partials.field', $pax + ['name' => 'nationality', 'label' => 'Nationality', 'req' => true, 'col' => 'col-md-4'])
    @include('erp.partials.field', $pax + ['name' => 'cnic', 'label' => 'CNIC', 'col' => 'col-md-4'])
    @include('erp.partials.field', $pax + ['name' => 'pnr', 'label' => 'PNR (same PNR = travelling together)', 'col' => 'col-md-4'])
    @include('erp.partials.field', $pax + ['name' => 'awardPlusNumber', 'label' => 'Award Plus number', 'col' => 'col-md-4'])
</div>

<div class="cs-erp-modal-section">Pax flight route</div>
<div class="row g-2">
    @include('erp.partials.field', $pax + ['name' => 'routeFrom', 'label' => 'From', 'col' => 'col-md-4'])
    @include('erp.partials.field', $pax + ['name' => 'routeTo', 'label' => 'To', 'col' => 'col-md-4'])
    @include('erp.partials.field', $pax + ['name' => 'routeVia', 'label' => 'Via', 'col' => 'col-md-4'])
    <div class="col-12 small text-muted">Leave empty if the passenger flew the incident route (<span id="cs-{{ $p }}-incident-route"></span>).</div>
</div>

<div class="cs-erp-modal-section">Contact &amp; address</div>
<div class="row g-2">
    @include('erp.partials.field', $pax + ['name' => 'contactNumbers', 'index' => 0, 'label' => 'Pax contact no. (1)', 'type' => 'tel', 'col' => 'col-md-4'])
    @include('erp.partials.field', $pax + ['name' => 'contactNumbers', 'index' => 1, 'label' => '(2)', 'type' => 'tel', 'col' => 'col-md-4'])
    @include('erp.partials.field', $pax + ['name' => 'contactNumbers', 'index' => 2, 'label' => '(3)', 'type' => 'tel', 'col' => 'col-md-4'])
    @include('erp.partials.field', $pax + ['name' => 'primaryAddress', 'label' => 'Pax primary address', 'type' => 'textarea', 'col' => 'col-12'])
</div>

<div class="cs-erp-modal-section">Customs / immigration</div>
<div class="row g-2">
    @include('erp.partials.field', $pax + ['name' => 'passportNumber', 'label' => 'Passport no.', 'col' => 'col-md-4'])
    @include('erp.partials.field', $pax + ['name' => 'passportIssueDate', 'label' => 'Issue date', 'type' => 'date', 'col' => 'col-md-4'])
    @include('erp.partials.field', $pax + ['name' => 'passportIssuePlace', 'label' => 'Issue place', 'col' => 'col-md-4'])
    @include('erp.partials.field', $pax + ['name' => 'birthPlace', 'label' => 'Birth place', 'col' => 'col-md-4'])
</div>

<div class="cs-erp-modal-section">Emergency contact from the booking</div>
<div class="row g-2">
    @include('erp.partials.field', $pax + ['name' => 'nokName', 'label' => 'Name', 'col' => 'col-md-4'])
    @include('erp.partials.field', $pax + ['name' => 'nokRelationship', 'label' => 'Relationship', 'col' => 'col-md-4'])
    @include('erp.partials.field', $pax + ['name' => 'nokPhone', 'label' => 'Phone', 'type' => 'tel', 'col' => 'col-md-4'])
</div>
