<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Bepaalt op basis van de rol van de ingelogde gebruiker welk dashboard wordt getoond.
     */
    public function index(Request $request)
    {
        // Laad de rol meteen mee, zodat de naam hieronder beschikbaar is.
        $user = Auth::user()->load('rol');
        $roleKey = $user->dashboardRoleKey();

        // Een account zonder herkende rol mag geen dashboard openen.
        abort_unless($roleKey && strtolower((string) $user->status) === 'actief', 403, 'Je account heeft geen geldige dashboardrol.');

        if ($roleKey === 'beheerder') {
            return app(ApplicationAdminController::class)->index($request);
        }

        // Koppel elke interne rolcode aan de bijbehorende Blade-pagina.
        $view = match ($roleKey) {
            'student' => 'dashboards.student',
            'docent' => 'dashboards.docent',
            'teamleider' => 'dashboards.teammanager',
            'beheerder' => 'dashboards.applicatiebeheerder',
        };

        // Geef de gebruiker en rolcode door aan zowel layout als dashboardpagina.
        return view($view, compact('user', 'roleKey'));
    }
}
