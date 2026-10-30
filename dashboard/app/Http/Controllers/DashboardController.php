<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Kies het juiste dashboard voor de ingelogde gebruiker.
     */
    public function index(Request $request)
    {
        // Haal de gebruiker en de rol op.
        $user = Auth::user()->load('rol');
        $roleKey = $user->dashboardRoleKey();

        // Stop met fout 403 als de rol onbekend is of het account niet actief is.
        abort_unless($roleKey && strtolower((string) $user->status) === 'actief', 403, 'Je account heeft geen geldige dashboardrol.');

        // De beheerder krijgt een overzicht met gegevens uit de database.
        if ($roleKey === 'beheerder') {
            return app(ApplicationAdminController::class)->index($request);
        }

        // Kies voor elke andere rol de juiste pagina.
        $view = match ($roleKey) {
            'student' => 'dashboards.student',
            'docent' => 'dashboards.docent',
            'teamleider' => 'dashboards.teammanager',
            'beheerder' => 'dashboards.applicatiebeheerder',
        };

        // Stuur de gebruiker en de rol naar de pagina.
        return view($view, compact('user', 'roleKey'));
    }
}
