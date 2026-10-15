{{--
    ERP-002 Passenger Information Form = the passenger's ONE contact person (set by ERC).
    Driven by resources/js/pif-form.js. Passenger details are read-only here (manifest).
--}}
@php($card = ['attr' => 'data-card', 'idp' => 'pif-'])
<div class="modal fade" id="pifModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <form id="cs-pif-form" class="d-flex flex-column overflow-hidden">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-0">Contact person &mdash; <span id="cs-pif-name"></span></h5>
                        <div class="small text-muted">Form ERP-002 &middot; <span id="cs-pif-incident">&mdash;</span></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="cs-pif-passenger-id">
                    <div id="cs-pif-current" class="alert alert-primary py-2 small" hidden></div>

                    <div class="cs-erp-modal-section mt-0">Passenger (from manifest)</div>
                    <div id="cs-pif-passenger" class="small"></div>
                    <div class="small mt-2"><strong>Travelling with (same PNR):</strong><br><span id="cs-pif-party"></span></div>

                    <div class="cs-erp-modal-section">Contact person (person providing information)</div>
                    <div class="row g-2">
                        @include('erp.partials.field', $card + ['name' => 'informantLastName', 'label' => 'Last (family) name', 'req' => true])
                        @include('erp.partials.field', $card + ['name' => 'informantFirstName', 'label' => 'First name(s)', 'req' => true])
                        @include('erp.partials.field', $card + ['name' => 'informantContactNumbers', 'index' => 0, 'label' => 'Contact no. (1)', 'type' => 'tel', 'req' => true, 'col' => 'col-md-4'])
                        @include('erp.partials.field', $card + ['name' => 'informantContactNumbers', 'index' => 1, 'label' => '(2)', 'type' => 'tel', 'col' => 'col-md-4'])
                        @include('erp.partials.field', $card + ['name' => 'informantContactNumbers', 'index' => 2, 'label' => '(3)', 'type' => 'tel', 'col' => 'col-md-4'])
                        @include('erp.partials.field', $card + ['name' => 'informantAddress', 'label' => 'Address', 'type' => 'textarea'])
                        @include('erp.partials.field', $card + ['name' => 'informantEmail', 'label' => 'E-mail', 'type' => 'email'])
                        @include('erp.partials.field', $card + ['name' => 'informantRelationship', 'label' => 'Relationship to pax', 'req' => true, 'col' => 'col-md-4'])
                        @include('erp.partials.field', $card + ['name' => 'informantIsRelativeOrMeeter', 'label' => 'Is the person a relative / meeter?', 'type' => 'yesno', 'req' => true, 'col' => 'col-md-4'])
                        @include('erp.partials.field', $card + ['name' => 'informantLanguages', 'label' => 'Primary / secondary languages', 'col' => 'col-md-4'])
                        @include('erp.partials.field', $card + ['name' => 'paxFlightDetails', 'label' => 'Pax flight details (as known to the person)', 'req' => true, 'col' => 'col-12'])
                        @include('erp.partials.field', $card + ['name' => 'informantContactedPax', 'label' => 'Already had contact with the pax?', 'type' => 'yesno', 'req' => true, 'col' => 'col-md-4'])
                        @include('erp.partials.field', $card + ['name' => 'informantContactDetails', 'label' => 'Details (mobile, text message, …)', 'col' => 'col-md-8'])
                    </div>
                    <div id="cs-pif-reason-wrap" class="mt-2" hidden>
                        <label class="form-label small mb-1" for="cs-pif-reason">Reason for changing the contact person <span class="text-danger">*</span></label>
                        <input id="cs-pif-reason" class="form-control form-control-sm" placeholder="e.g. number switched off — wife is now the contact">
                    </div>

                    <div class="cs-erp-modal-section">Passenger details given by the caller</div>
                    <div class="row g-2">
                        @include('erp.partials.field', $card + ['name' => 'travellingWith', 'label' => 'Travelling with (anyone not listed above)', 'type' => 'textarea'])
                        @include('erp.partials.field', $card + ['name' => 'nokDetails', 'label' => 'Known next of kin / closest relative(s)', 'type' => 'textarea'])
                        @include('erp.partials.field', $card + ['name' => 'hasMedicalProblem', 'label' => 'Existing medical problem?', 'type' => 'yesno', 'col' => 'col-md-4'])
                        @include('erp.partials.field', $card + ['name' => 'medicalDetails', 'label' => 'Details', 'col' => 'col-md-8'])
                        @include('erp.partials.field', $card + ['name' => 'otherInformation', 'label' => 'Other information — skin / hair / eye colour, distinguishing marks (scars, tattoos …)', 'type' => 'textarea', 'rows' => 3, 'col' => 'col-12'])
                    </div>

                    <hr>
                    <h6 class="mb-2">All status updates (read-only)</h6>
                    <div class="table-responsive" style="max-height: 200px;">
                        <table class="table table-sm">
                            <thead class="table-light"><tr><th>When</th><th>Team</th><th>By</th><th>Status</th><th>Remarks</th></tr></thead>
                            <tbody id="cs-erc-updates-tbody"><tr><td colspan="5" class="text-center text-muted small">&mdash;</td></tr></tbody>
                        </table>
                    </div>

                    <h6 class="mb-2 mt-3">Contact person history</h6>
                    <div class="table-responsive" style="max-height: 200px;">
                        <table class="table table-sm">
                            <thead class="table-light"><tr><th>When</th><th>By</th><th>Contact</th><th>Rel.</th><th>Phones</th><th>Change reason</th><th class="text-end">Print</th></tr></thead>
                            <tbody id="cs-pif-history-tbody"><tr><td colspan="7" class="text-center text-muted small">&mdash;</td></tr></tbody>
                        </table>
                    </div>

                    <div id="cs-erc-legacy-relative" hidden>
                        <h6 class="mb-2 mt-3">Earlier next-of-kin entries</h6>
                        <div class="table-responsive" style="max-height: 200px;">
                            <table class="table table-sm">
                                <thead class="table-light"><tr><th>When</th><th>By</th><th>Contact</th><th>Rel.</th><th>Phones</th></tr></thead>
                                <tbody id="cs-erc-relative-history-tbody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" id="cs-pif-print" target="_blank" rel="noopener" class="btn btn-outline-secondary me-auto">
                        <i class="bi bi-printer"></i> Print
                    </a>
                    <a href="/print/pif/blank" target="_blank" rel="noopener" class="btn btn-outline-secondary">Blank form</a>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="cs-pif-submit">Save contact person</button>
                </div>
            </form>
        </div>
    </div>
</div>
