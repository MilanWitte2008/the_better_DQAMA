<?php

namespace App\Http\Controllers;

class DashboardController extends Controller
{
    public function index()
    {
        // Haal de ingelogde gebruiker en diens rol op.
        $user = auth()->user()->load('rol');
        $roleKey = $user->dashboardRoleKey();

        // Stop als de gebruiker geen bekende rol heeft.
        abort_unless($roleKey, 403, 'Je account heeft geen geldige dashboardrol.');

        // Kies de dashboardpagina die bij de rol hoort.
        $view = match ($roleKey) {
            'student' => 'dashboards.student',
            'docent' => 'dashboards.docent',
            'teamleider' => 'dashboards.teammanager',
            'beheerder' => 'dashboards.applicatiebeheerder',
        };

        // Stuur de pagina, gebruiker en rolcode naar de browser.
        return view($view, compact('user', 'roleKey'));
    }
}
