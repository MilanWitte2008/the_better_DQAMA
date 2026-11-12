<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Kies het juiste dashboard voor de ingelogde gebruiker.
     */
    public function index(Request $request)
    {
        // Haal de ingelogde gebruiker en diens rol op.
        $user = $request->user()->load('rol');
        $roleKey = $user->dashboardRoleKey();
        $requestedRole = $request->route('dashboard_role');
        abort_if($requestedRole !== null && $requestedRole !== $roleKey, 403, 'Deze pagina hoort bij een andere rol.');

        // Stop als het account inactief is of geen bekende rol heeft.
        abort_unless($roleKey && strtolower((string) $user->status) === 'actief', 403, 'Je account heeft geen geldige dashboardrol.');

        if ($roleKey === 'beheerder') {
            return app(ApplicationAdminController::class)->index($request);
        }

        if ($roleKey === 'teamleider') {
            return app(TeamManagerDashboardController::class)->index($request);
        }

        // Kies voor elke andere rol de juiste pagina.
        $view = match ($roleKey) {
            'student' => 'dashboards.student',
            'docent' => 'dashboards.docent',
        };

        // Stuur de gebruiker en de rol naar de pagina.
        return view($view, compact('user', 'roleKey'));
    }
}
