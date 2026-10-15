{{--
    Company Admin — edit the passenger's booking / manifest data (the Company's part of ERP-001).
    Same fields as the Excel manifest. Driven by resources/js/passenger-form.js.
--}}
<div class="modal fade" id="passengerModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <form id="cs-pax-form" class="d-flex flex-column overflow-hidden">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-0">Passenger details &mdash; <span id="cs-pax-name"></span></h5>
                        <div class="small text-muted">Company's part of ERP-001 &middot; <span id="cs-pax-incident">&mdash;</span></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="cs-pax-passenger-id">
                    <div class="alert alert-light border small py-2">
                        Injury, intentions, family assistance, language and next-of-kin answers are added by
                        Station / SAT / GO with their updates. The contact person is set by ERC.
                    </div>
                    @include('erp.partials.pax-fields', ['p' => 'pax'])
                </div>
                <div class="modal-footer">
                    <a href="#" id="cs-pax-print" target="_blank" rel="noopener" class="btn btn-outline-secondary me-auto">
                        <i class="bi bi-printer"></i> Print PRC
                    </a>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save details</button>
                </div>
            </form>
        </div>
    </div>
</div>
