{{--
    Station / SAT / GO part of ERP-001: what is learnt from the passenger after the incident
    (passenger_record_cards, one row per save). Shown inside erp.update-modal ($p = "upd").
    The Company's part (name, DOB, passport…) is read-only here — see erp.partials.pax-fields.
--}}
@php($card = ['attr' => 'data-card', 'idp' => $p.'-'])
<div class="cs-erp-modal-section {{ $firstSectionClass ?? 'mt-0' }}">Passenger information <span class="text-lowercase fw-normal">(filled by the company)</span></div>
<div class="small" id="cs-{{ $p }}-summary"></div>
<div class="small mt-1"><span class="text-muted">Travelling with (same PNR):</span> <span id="cs-{{ $p }}-party"></span></div>
<div class="row g-2 mt-1">
    @include('erp.partials.field', $card + ['name' => 'travellingWith', 'label' => 'Others travelling with pax (not on the same booking) — name, DOB, nationality, sex, relationship', 'type' => 'textarea', 'col' => 'col-12'])
    @include('erp.partials.field', $card + ['name' => 'localAddress', 'label' => 'Local address at incident location (if different from primary)', 'type' => 'textarea', 'col' => 'col-12'])
</div>

<div class="cs-erp-modal-section">Next of kin, relatives &amp; meeters</div>
<div class="row g-2">
    @include('erp.partials.field', $card + ['name' => 'nokDetails', 'label' => 'Next of kin / closest relative(s) — names, addresses, contacts, relationship', 'type' => 'textarea', 'rows' => 3, 'req' => true, 'col' => 'col-12'])
    @include('erp.partials.field', $card + ['name' => 'relativesMeeters', 'label' => 'Relatives & meeters / details', 'type' => 'textarea'])
    @include('erp.partials.field', $card + ['name' => 'otherContacts', 'label' => 'Other contacts', 'type' => 'textarea'])
    @include('erp.partials.field', $card + ['name' => 'contactMade', 'label' => 'Already made contact with relatives / friends?', 'type' => 'yesno', 'req' => true, 'col' => 'col-md-4'])
    @include('erp.partials.field', $card + ['name' => 'contactDetails', 'label' => 'If so, details', 'col' => 'col-md-8'])
</div>

<div class="cs-erp-modal-section">Injury</div>
<div class="row g-2">
    @include('erp.partials.field', $card + ['name' => 'lifeThreatening', 'label' => 'Injuries life threatening? (hospitalised / injured pax)', 'type' => 'yesno', 'req' => true, 'col' => 'col-md-4'])
    @include('erp.partials.field', $card + ['name' => 'injuryDetails', 'label' => 'If yes, details', 'col' => 'col-md-8'])
</div>

<div class="cs-erp-modal-section">Pax intentions</div>
<div class="row g-2">
    @include('erp.partials.field', $card + ['name' => 'intentionLocalAddress', 'label' => 'Travel to local address', 'type' => 'yesno', 'col' => 'col-md-4'])
    @include('erp.partials.field', $card + ['name' => 'intentionLocalAddressDetails', 'label' => 'Details', 'col' => 'col-md-8'])
    @include('erp.partials.field', $card + ['name' => 'intentionContinueJourney', 'label' => 'Continue journey', 'type' => 'yesno', 'col' => 'col-md-4'])
    @include('erp.partials.field', $card + ['name' => 'intentionContinueJourneyDetails', 'label' => 'Details', 'col' => 'col-md-8'])
    @include('erp.partials.field', $card + ['name' => 'intentionSrc', 'label' => 'Proceed to SRC(L)', 'type' => 'yesno', 'col' => 'col-md-4'])
    @include('erp.partials.field', $card + ['name' => 'intentionOther', 'label' => 'Other — details', 'col' => 'col-md-8'])
</div>

<div class="cs-erp-modal-section">Family assistance &amp; language</div>
<div class="row g-2">
    @include('erp.partials.field', $card + ['name' => 'familyAssistOffered', 'label' => 'Family assistance offered', 'type' => 'yesno', 'req' => true, 'col' => 'col-md-4'])
    @include('erp.partials.field', $card + ['name' => 'familyAssistAccepted', 'label' => 'Accepted', 'type' => 'yesno', 'col' => 'col-md-4'])
    @include('erp.partials.field', $card + ['name' => 'familyAssistTeamAdvised', 'label' => 'Family Assist Team advised', 'type' => 'yesno', 'col' => 'col-md-4'])
    @include('erp.partials.field', $card + ['name' => 'speaksEnglish', 'label' => 'Can converse in English', 'type' => 'yesno', 'col' => 'col-md-4'])
    @include('erp.partials.field', $card + ['name' => 'primaryLanguage', 'label' => 'Primary language', 'col' => 'col-md-4'])
    @include('erp.partials.field', $card + ['name' => 'secondaryLanguage', 'label' => 'Secondary language', 'col' => 'col-md-4'])
</div>

<div class="cs-erp-modal-section">Customs / immigration &amp; other</div>
<div class="row g-2">
    @include('erp.partials.field', $card + ['name' => 'occupation', 'label' => 'Occupation', 'req' => true, 'col' => 'col-md-4'])
    @include('erp.partials.field', $card + ['name' => 'otherInformation', 'label' => 'Other relevant information / continuity notes', 'type' => 'textarea', 'rows' => 3, 'col' => 'col-md-8'])
</div>
