<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StudentDashboardController extends Controller
{
    // Valideer en sla een persoonlijk leerdoel op voor de ingelogde student.
    public function storeGoal(Request $request): RedirectResponse
    {
        $this->authorizeStudent($request);
        $data = $request->validate(['titel' => ['required', 'string', 'max:255']]);

        abort_unless(Schema::hasTable('doelen') && Schema::hasColumn('doelen', 'lid_idlid'), 404);

        $titleColumn = Schema::hasColumn('doelen', 'titel') ? 'titel' : 'naam';
        abort_unless(Schema::hasColumn('doelen', $titleColumn), 404);

        $goal = [$titleColumn => $data['titel'], 'lid_idlid' => $request->user()->getKey()];
        if (Schema::hasColumn('doelen', 'status')) {
            $goal['status'] = 'Te doen';
        }
        if (Schema::hasColumn('doelen', 'omschrijving')) {
            $goal['omschrijving'] = '';
        }

        DB::table('doelen')->insert($goal);

        return redirect()->to(route('dashboard') . '#leerdoelen')->with('success', 'Leerdoel opgeslagen.');
    }

    // Bewaar een reflectie met de datum en het lid dat haar heeft geschreven.
    public function storeReflection(Request $request): RedirectResponse
    {
        $this->authorizeStudent($request);
        $data = $request->validate(['inhoud' => ['required', 'string', 'max:5000']]);

        abort_unless(Schema::hasTable('reflectie'), 404);

        DB::table('reflectie')->insert([
            'lid_idlid' => $request->user()->getKey(),
            'inhoud' => $data['inhoud'],
            'datum' => now()->toDateString(),
        ]);

        return redirect()->to(route('dashboard') . '#reflecties')->with('success', 'Reflectie opgeslagen.');
    }

    // Alleen actieve accounts met de studentrol mogen deze formulieren gebruiken.
    private function authorizeStudent(Request $request): void
    {
        abort_unless(
            $request->user()?->dashboardRoleKey() === 'student'
                && strtolower((string) $request->user()->status) === 'actief',
            403,
        );
    }
}
