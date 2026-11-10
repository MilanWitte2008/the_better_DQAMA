<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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

        // Stop als het account inactief is of geen bekende rol heeft.
        abort_unless($roleKey && strtolower((string) $user->status) === 'actief', 403, 'Je account heeft geen geldige dashboardrol.');

        // Vul ontbrekende koppelingen aan wanneer een student het dashboard opent.
        if ($roleKey === 'student'
            && Schema::hasTable('leeruitkomsten_has_lid')
            && Schema::hasTable('leeruitkomsten')) {
            $assignments = DB::table('leeruitkomsten')
                ->select('leeruitkomsten_id')
                ->get()
                ->map(fn ($outcome) => [
                    'leeruitkomsten_id' => $outcome->leeruitkomsten_id,
                    'lid_idlid' => $user->getKey(),
                    'status' => 'Te doen',
                ])
                ->all();

            if ($assignments !== []) {
                DB::table('leeruitkomsten_has_lid')->insertOrIgnore($assignments);
            }
        }

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
