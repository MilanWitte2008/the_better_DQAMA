<?php

namespace App\Http\Controllers;

class DashboardController extends Controller
{
<<<<<<< HEAD
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
=======
    public function index()
    {
        // Haal de ingelogde gebruiker en diens rol op.
        $user = auth()->user()->load('rol');
        $roleKey = $user->dashboardRoleKey();

        // Stop als de gebruiker geen bekende rol heeft.
        abort_unless($roleKey, 403, 'Je account heeft geen geldige dashboardrol.');

        // Kies de dashboardpagina die bij de rol hoort.
>>>>>>> 7a300777fb9ba14cda7d81cfd932a4f21b65c3f0
        $view = match ($roleKey) {
            'student' => 'dashboards.student',
            'docent' => 'dashboards.docent',
            'teamleider' => 'dashboards.teammanager',
            'beheerder' => 'dashboards.applicatiebeheerder',
        };

<<<<<<< HEAD
        // Stuur de gebruiker en de rol naar de pagina.
=======
        // Stuur de pagina, gebruiker en rolcode naar de browser.
>>>>>>> 7a300777fb9ba14cda7d81cfd932a4f21b65c3f0
        return view($view, compact('user', 'roleKey'));
    }
}
