<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user()->load('rol');
        $roleKey = $user->dashboardRoleKey();

        abort_unless($roleKey, 403, 'Je account heeft geen geldige dashboardrol.');

        $view = match ($roleKey) {
            'student' => 'dashboards.student',
            'docent' => 'dashboards.docent',
            'teamleider' => 'dashboards.teammanager',
            'beheerder' => 'dashboards.applicatiebeheerder',
        };

        return view($view, compact('user', 'roleKey'));
    }
}
