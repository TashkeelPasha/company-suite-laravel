<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Company Suite')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<body>

    {{-- Sidebar + header are filled by resources/js/topbar.js once /api/auth/whoami resolves.
         Public pages (home, login) never call it, so they keep the plain header only. --}}
    @include('layouts.partials.sidebar')

    <div class="cs-main">
        @include('layouts.partials.topbar')

        <main class="cs-content">
            @yield('content')
        </main>
    </div>

    @include('layouts.partials.toast')

    <div id="cs-spinner" class="cs-spinner-overlay" hidden>
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading…</span>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
