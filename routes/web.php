<?php

use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

/*
| These routes render Blade shells only. Every dynamic bit — data, forms,
| session checks — is done client-side via the JSON API documented in
| API_DOCUMENTATION.md. Role gating happens in JS on page load (calls
| /api/auth/whoami and redirects). See resources/js/auth-gate.js.
|
| That means these routes DO NOT need Laravel's auth middleware — the API
| enforces auth, and the shell simply asks "who am I?" on load.
*/

// Public
Route::get('/',         [PageController::class, 'home'])->name('home');
Route::get('/login',    [PageController::class, 'login'])->name('login');

// SuperAdmin
Route::get('/dashboard', [PageController::class, 'superadminDashboard'])->name('superadmin.dashboard');
Route::get('/settings',  [PageController::class, 'superadminSettings'])->name('superadmin.settings');

// Company Admin
Route::get('/company',                       [PageController::class, 'companyDashboard'])->name('company.dashboard');
Route::get('/company/incidents/new',         [PageController::class, 'companyAddIncident'])->name('company.incidents.create');
Route::get('/company/incidents/{id}',        [PageController::class, 'companyIncidentDetail'])
    ->whereNumber('id')
    ->name('company.incidents.show');

// Station Team
Route::get('/station',        [PageController::class, 'stationDashboard'])->name('station.dashboard');
Route::get('/station/{any?}', [PageController::class, 'stationDashboard'])->where('any', '.*');

// GO Team
Route::get('/go',        [PageController::class, 'goDashboard'])->name('go.dashboard');
Route::get('/go/{any?}', [PageController::class, 'goDashboard'])->where('any', '.*');

// ERC Team
Route::get('/erc',        [PageController::class, 'ercDashboard'])->name('erc.dashboard');
Route::get('/erc/{any?}', [PageController::class, 'ercDashboard'])->where('any', '.*');

// Fallback → 404
Route::fallback([PageController::class, 'notFound']);
