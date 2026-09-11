<?php

use App\Http\Controllers\ApiController;
use App\Http\Controllers\MobileApiController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {
    Route::get('/healthz', [ApiController::class, 'health']);

    Route::post('/auth/login', [ApiController::class, 'superadminLogin']);
    Route::post('/auth/logout', [ApiController::class, 'superadminLogout']);
    Route::get('/auth/me', [ApiController::class, 'superadminMe']);
    Route::get('/auth/whoami', [ApiController::class, 'whoami']);
    Route::patch('/auth/password', [ApiController::class, 'changeSuperadminPassword']);

    Route::get('/companies', [ApiController::class, 'companies'])->middleware('role:superadmin');
    Route::post('/companies', [ApiController::class, 'createCompany'])->middleware('role:superadmin');
    Route::get('/companies/stats', [ApiController::class, 'companyStats'])->middleware('role:superadmin');
    Route::get('/companies/{id}', [ApiController::class, 'company'])->whereNumber('id')->middleware('role:superadmin');
    Route::patch('/companies/{id}', [ApiController::class, 'updateCompany'])->whereNumber('id')->middleware('role:superadmin');
    Route::delete('/companies/{id}', [ApiController::class, 'deleteCompany'])->whereNumber('id')->middleware('role:superadmin');
    Route::patch('/companies/{id}/password', [ApiController::class, 'resetCompanyPassword'])->whereNumber('id')->middleware('role:superadmin');

    Route::post('/company/auth/login', [ApiController::class, 'companyLogin']);
    Route::post('/company/auth/logout', [ApiController::class, 'companyLogout'])->middleware('role:company');
    Route::get('/company/auth/me', [ApiController::class, 'companyMe'])->middleware('role:company');
    Route::get('/company/team-members', [ApiController::class, 'teamMembers'])->middleware('role:company');
    Route::post('/company/team-members', [ApiController::class, 'createTeamMember'])->middleware('role:company');
    Route::patch('/company/team-members/{id}', [ApiController::class, 'updateTeamMember'])->whereNumber('id')->middleware('role:company');
    Route::delete('/company/team-members/{id}', [ApiController::class, 'deleteTeamMember'])->whereNumber('id')->middleware('role:company');
    Route::get('/company/passengers', [ApiController::class, 'legacyPassengers'])->middleware('role:company');
    Route::post('/company/passengers', [ApiController::class, 'createLegacyPassenger'])->middleware('role:company');
    Route::delete('/company/passengers/{id}', [ApiController::class, 'deletePassenger'])->whereNumber('id')->middleware('role:company');
    Route::get('/company/incidents', [ApiController::class, 'companyIncidents'])->middleware('role:company');
    Route::post('/company/incidents', [ApiController::class, 'createIncident'])->middleware('role:company');
    Route::get('/company/incidents/{id}', [ApiController::class, 'companyIncident'])->whereNumber('id')->middleware('role:company');
    Route::patch('/company/incidents/{id}/status', [ApiController::class, 'updateIncidentStatus'])->whereNumber('id')->middleware('role:company');
    Route::post('/company/incidents/{id}/passengers', [ApiController::class, 'bulkPassengers'])->whereNumber('id')->middleware('role:company');

    Route::post('/station/auth/login', [ApiController::class, 'stationLogin']);
    Route::post('/station/auth/logout', [ApiController::class, 'stationLogout'])->middleware('role:station');
    Route::get('/station/auth/me', [ApiController::class, 'stationMe'])->middleware('role:station');
    Route::get('/station/passengers', [ApiController::class, 'stationPassengers'])->middleware('role:station');
    Route::get('/station/passengers/{id}/updates', [ApiController::class, 'stationUpdates'])->whereNumber('id')->middleware('role:station');
    Route::post('/station/passengers/{id}/updates', [ApiController::class, 'createStationUpdate'])->whereNumber('id')->middleware('role:station');

    Route::post('/go/auth/login', [ApiController::class, 'goLogin']);
    Route::post('/go/auth/logout', [ApiController::class, 'goLogout'])->middleware('role:go');
    Route::get('/go/auth/me', [ApiController::class, 'goMe'])->middleware('role:go');
    Route::get('/go/incidents', [ApiController::class, 'goIncidents'])->middleware('role:go');
    Route::get('/go/incidents/{id}/passengers', [ApiController::class, 'goIncident'])->whereNumber('id')->middleware('role:go');
    Route::get('/go/passengers/{id}/updates', [ApiController::class, 'goUpdates'])->whereNumber('id')->middleware('role:go');
    Route::post('/go/passengers/{id}/updates', [ApiController::class, 'createGoUpdate'])->whereNumber('id')->middleware('role:go');

    Route::post('/erc/auth/login', [ApiController::class, 'ercLogin']);
    Route::post('/erc/auth/logout', [ApiController::class, 'ercLogout'])->middleware('role:erc');
    Route::get('/erc/auth/me', [ApiController::class, 'ercMe'])->middleware('role:erc');
    Route::get('/erc/incidents', [ApiController::class, 'ercIncidents'])->middleware('role:erc');
    Route::get('/erc/incidents/{id}/passengers', [ApiController::class, 'ercIncident'])->whereNumber('id')->middleware('role:erc');
    Route::get('/erc/passengers/{id}/relative-info', [ApiController::class, 'relativeInfo'])->whereNumber('id')->middleware('role:erc');
    Route::post('/erc/passengers/{id}/relative-info', [ApiController::class, 'createRelativeInfo'])->whereNumber('id')->middleware('role:erc');
    Route::get('/erc/passengers/{id}/updates', [ApiController::class, 'ercUpdates'])->whereNumber('id')->middleware('role:erc');

    Route::post('/uploads', [ApiController::class, 'upload'])->middleware('role:superadmin,company,station,go,erc');
    Route::get('/uploads/{filename}', [ApiController::class, 'serveUpload'])->where('filename', '[A-Za-z0-9._-]+');
});

// ---------------------------------------------------------------------------
// Mobile API — Sanctum token auth, no session/CSRF (mobile clients set Bearer).
// URL prefix is 'mobile' (Laravel auto-prefixes /api because this is routes/api.php).
// So final URLs are /api/mobile/*.
// ---------------------------------------------------------------------------
Route::prefix('mobile')->group(function () {

    // TODO(Bilal): implement these 4 methods in MobileApiController.
    // Uncomment when done. See MOBILE_API_SPEC.md for expected request/response shape.
    //   - superadminLogin(Request)  — POST /api/mobile/auth/superadmin/login
    //   - companyLogin(Request)     — POST /api/mobile/auth/company/login
    //   - companies()               — GET  /api/mobile/companies (SuperAdmin only)
    //   - companyIncidents()        — GET  /api/mobile/company/incidents (Company Admin only)
    // Route::post('/auth/superadmin/login', [MobileApiController::class, 'superadminLogin']);
    // Route::post('/auth/company/login',    [MobileApiController::class, 'companyLogin']);

    Route::post('/auth/station/login', [MobileApiController::class, 'stationLogin']);
    Route::post('/auth/go/login',      [MobileApiController::class, 'goLogin']);
    Route::post('/auth/erc/login',     [MobileApiController::class, 'ercLogin']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/me',      [MobileApiController::class, 'me']);
        Route::post('/auth/logout', [MobileApiController::class, 'logout']);

        // Route::get('/companies',        [MobileApiController::class, 'companies'])->middleware('mobile.role:superadmin');
        // Route::get('/company/incidents',[MobileApiController::class, 'companyIncidents'])->middleware('mobile.role:company');

        // Station
        Route::get('/station/passengers',                    [MobileApiController::class, 'stationPassengers'])->middleware('mobile.role:station');
        Route::get('/station/passengers/{id}/updates',       [MobileApiController::class, 'stationUpdates'])->whereNumber('id')->middleware('mobile.role:station');
        Route::post('/station/passengers/{id}/updates',      [MobileApiController::class, 'createStationUpdate'])->whereNumber('id')->middleware('mobile.role:station');

        // GO
        Route::get('/go/incidents',                          [MobileApiController::class, 'goIncidents'])->middleware('mobile.role:go');
        Route::get('/go/incidents/{id}/passengers',          [MobileApiController::class, 'goIncident'])->whereNumber('id')->middleware('mobile.role:go');
        Route::get('/go/passengers/{id}/updates',            [MobileApiController::class, 'goUpdates'])->whereNumber('id')->middleware('mobile.role:go');
        Route::post('/go/passengers/{id}/updates',           [MobileApiController::class, 'createGoUpdate'])->whereNumber('id')->middleware('mobile.role:go');

        // ERC
        Route::get('/erc/incidents',                         [MobileApiController::class, 'ercIncidents'])->middleware('mobile.role:erc');
        Route::get('/erc/incidents/{id}/passengers',         [MobileApiController::class, 'ercIncident'])->whereNumber('id')->middleware('mobile.role:erc');
        Route::get('/erc/passengers/{id}/relative-info',     [MobileApiController::class, 'relativeInfo'])->whereNumber('id')->middleware('mobile.role:erc');
        Route::post('/erc/passengers/{id}/relative-info',    [MobileApiController::class, 'createRelativeInfo'])->whereNumber('id')->middleware('mobile.role:erc');
        Route::get('/erc/passengers/{id}/updates',           [MobileApiController::class, 'ercUpdates'])->whereNumber('id')->middleware('mobile.role:erc');
    });
});
