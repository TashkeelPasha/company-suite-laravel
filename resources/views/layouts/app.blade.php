<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Company Suite')</title>

    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<body>

    @include('layouts.partials.topbar')

    <main class="container-fluid py-4 px-4 px-lg-5">
        @yield('content')
    </main>

    @include('layouts.partials.toast')

    <div id="cs-spinner" class="cs-spinner-overlay" hidden>
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading…</span>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
