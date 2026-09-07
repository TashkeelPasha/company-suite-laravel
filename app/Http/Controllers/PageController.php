<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Thin controller. Every method returns a Blade shell that loads its data
 * client-side from the JSON API. Route params are passed to the view so the
 * JS knows what id to fetch.
 */
class PageController
{
    public function home(): View
    {
        return view('home');
    }

    public function login(): View
    {
        return view('auth.login');
    }

    /* SuperAdmin ---------------------------------------------------------- */

    public function superadminDashboard(): View
    {
        return view('superadmin.dashboard');
    }

    public function superadminSettings(): View
    {
        return view('superadmin.settings');
    }

    /* Company Admin ------------------------------------------------------- */

    public function companyDashboard(): View
    {
        return view('company.dashboard');
    }

    public function companyAddIncident(): View
    {
        return view('company.incidents.create');
    }

    public function companyIncidentDetail(int $id): View
    {
        return view('company.incidents.show', ['incidentId' => $id]);
    }

    /* Team dashboards ----------------------------------------------------- */

    public function stationDashboard(): View
    {
        return view('station.dashboard');
    }

    public function goDashboard(): View
    {
        return view('go.dashboard');
    }

    public function ercDashboard(): View
    {
        return view('erc.dashboard');
    }

    /* 404 ----------------------------------------------------------------- */

    public function notFound(): Response
    {
        return response()->view('errors.404', [], 404);
    }
}
