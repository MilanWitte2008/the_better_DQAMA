<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Haal de ingelogde gebruiker en diens rol op.
        $user = $request->user()->load('rol');
        $roleKey = $user->dashboardRoleKey();

        // Stop als het account inactief is of geen bekende rol heeft.
        abort_unless($roleKey && strtolower((string) $user->status) === 'actief', 403, 'Je account heeft geen geldige dashboardrol.');

        // Het beheerdersoverzicht haalt zelf filters en statistieken op.
        if ($roleKey === 'beheerder') {
            return app(ApplicationAdminController::class)->index($request);
        }

        $view = match ($roleKey) {
            'student' => 'dashboards.student',
            'docent' => 'dashboards.docent',
            'teamleider' => 'dashboards.teammanager',
        };

        // Stuur de gebruiker en de rol naar de pagina.
        return view($view, compact('user', 'roleKey'));
    }
}
