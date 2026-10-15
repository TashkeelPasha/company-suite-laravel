{{--
    Station / SAT / GO status update. The ERP-001 (PRC) questions sit in the same window,
    so whatever the team learns while updating is added to the card — no separate form.
    Driven by resources/js/update-form.js.
--}}
<div class="modal fade" id="statusUpdateModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <form id="cs-upd-form" class="d-flex flex-column overflow-hidden" novalidate>
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-0">Update passenger &mdash; <span id="cs-upd-name"></span></h5>
                        <div class="small text-muted"><span id="cs-upd-incident">&mdash;</span></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="cs-upd-passenger-id">
                    <div id="cs-upd-contact" class="alert alert-primary py-2 small" hidden></div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" for="cs-upd-status">Status <span class="text-danger">*</span></label>
                            <select id="cs-upd-status" class="form-select">
                                <option>Safe</option><option>Injured</option><option>Critical</option>
                                <option>Deceased</option><option>Unconfirmed</option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label" for="cs-upd-remarks">Remarks (optional)</label>
                            <input id="cs-upd-remarks" class="form-control" type="text" placeholder="e.g. taken to Services Hospital, ward 4">
                        </div>
                    </div>

                    <details id="cs-upd-prc" class="cs-upd-prc mt-3">
                        <summary class="fw-semibold">
                            Passenger Record Card (ERP-001) questions
                            <span class="small fw-normal text-muted ms-1" id="cs-upd-prc-hint">&mdash; answer what you learnt from the passenger; empty answers are fine</span>
                        </summary>
                        <div id="cs-upd-last-saved" class="alert alert-light border small py-2 mt-2" hidden></div>
                        @include('erp.partials.prc-fields', ['p' => 'upd', 'firstSectionClass' => 'mt-2'])
                    </details>

                    <hr>
                    <h6 class="mb-2">Update history</h6>
                    <div class="table-responsive" style="max-height: 220px;">
                        <table class="table table-sm">
                            <thead class="table-light"><tr><th>When</th><th>Team</th><th>By</th><th>Status</th><th>Remarks</th></tr></thead>
                            <tbody id="cs-upd-history-tbody"><tr><td colspan="5" class="text-center text-muted small">&mdash;</td></tr></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" id="cs-upd-print" target="_blank" rel="noopener" class="btn btn-outline-secondary me-auto">
                        <i class="bi bi-printer"></i> Print PRC
                    </a>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save update</button>
                </div>
            </form>
        </div>
    </div>
</div>
