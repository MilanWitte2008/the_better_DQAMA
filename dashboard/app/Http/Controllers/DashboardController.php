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
        $user = Auth::user()->load('rol');
        $roleKey = $user->dashboardRoleKey();

        abort_unless($roleKey && strtolower((string) $user->status) === 'actief', 403, 'Je account heeft geen geldige dashboardrol.');

        if ($roleKey === 'beheerder') {
            return app(ApplicationAdminController::class)->index($request);
        }

        $view = match ($roleKey) {
            'student' => 'dashboards.student',
            'docent' => 'dashboards.docent',
            'teamleider' => 'dashboards.teammanager',
        };

        return view($view, compact('user', 'roleKey'));
    }
}
