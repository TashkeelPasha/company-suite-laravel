@extends('layouts.app')
@section('title', 'Company Suite')

@section('content')
    <div class="row justify-content-center py-5">
        <div class="col-lg-8 text-center">
            <h1 class="display-5 fw-bold mb-3">Company Suite</h1>
            <p class="lead text-muted mb-4">
                Airline incident response &mdash; passenger tracking, team coordination,
                and next-of-kin management for every role in your organisation.
            </p>
            <a href="/login" class="btn btn-primary btn-lg px-4">
                <i class="bi bi-box-arrow-in-right"></i> Login
            </a>
        </div>
    </div>

    <div class="row g-4 mt-3">
        @php
            $roles = [
                ['SuperAdmin',   'bi-shield-lock',    'Manage companies and platform settings'],
                ['Company Admin','bi-buildings',     'Manage team members, create incidents, oversee response'],
                ['Station Team', 'bi-geo-alt',       'Update passenger status on the ground'],
                ['GO Team',      'bi-people',        'Coordinate operations, full passenger visibility'],
                ['ERC Team',     'bi-telephone',     'Collect and manage next-of-kin information'],
            ];
        @endphp
        @foreach ($roles as [$name, $icon, $desc])
            <div class="col-md-6 col-lg-4">
                <div class="cs-stat-card h-100">
                    <div class="d-flex align-items-center mb-2">
                        <i class="bi {{ $icon }} fs-3 text-primary me-2"></i>
                        <h5 class="mb-0">{{ $name }}</h5>
                    </div>
                    <p class="text-muted small mb-0">{{ $desc }}</p>
                </div>
            </div>
        @endforeach
    </div>
@endsection

@push('scripts')
<script type="module">
    import { redirectIfLoggedIn } from '/build/assets/auth-gate.js';
    // On the landing page, if user has an active session, jump straight to their dashboard.
    redirectIfLoggedIn().catch(() => {});
</script>
@endpush
