<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\ConversationRequestNotification;
use App\StudentDashboard;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class StudentDashboardController extends Controller
{
    public function storeGoal(Request $request): RedirectResponse
    {
        $this->authorizeStudent($request);
        $data = $request->validate(['titel' => ['required', 'string', 'max:255']]);

        abort_unless(Schema::hasTable('doelen') && Schema::hasColumn('doelen', 'lid_idlid'), 404);

        $titleColumn = $this->goalTitleColumn();
        $goal = [$titleColumn => $data['titel'], 'lid_idlid' => $request->user()->getKey()];
        if (Schema::hasColumn('doelen', 'status')) {
            $goal['status'] = 'Te doen';
        }
        if (Schema::hasColumn('doelen', 'omschrijving')) {
            $goal['omschrijving'] = '';
        }

        DB::table('doelen')->insert($goal);

        return redirect()->to(route('dashboard').'#leerdoelen')->with('success', 'Leerdoel opgeslagen.');
    }

    public function updateGoal(Request $request, int $goal): RedirectResponse
    {
        $this->authorizeStudent($request);
        $data = $request->validate(['titel' => ['required', 'string', 'max:255']]);

        $this->ownedGoal($request, $goal)->update([$this->goalTitleColumn() => $data['titel']]);

        return redirect()->to(route('dashboard').'#leerdoelen')->with('success', 'Leerdoel aangepast.');
    }

    public function destroyGoal(Request $request, int $goal): RedirectResponse
    {
        $this->authorizeStudent($request);
        $this->ownedGoal($request, $goal)->delete();

        return redirect()->to(route('dashboard').'#leerdoelen')->with('success', 'Leerdoel verwijderd.');
    }

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

        return redirect()->to(route('dashboard').'#reflecties')->with('success', 'Reflectie opgeslagen.');
    }

    public function updateReflection(Request $request, int $reflection): RedirectResponse
    {
        $this->authorizeStudent($request);
        $data = $request->validate(['inhoud' => ['required', 'string', 'max:5000']]);

        $this->ownedReflection($request, $reflection)->update([
            'inhoud' => $data['inhoud'],
            'datum' => now()->toDateString(),
        ]);

        return redirect()->to(route('dashboard').'#reflecties')->with('success', 'Reflectie aangepast.');
    }

    public function destroyReflection(Request $request, int $reflection): RedirectResponse
    {
        $this->authorizeStudent($request);
        $this->ownedReflection($request, $reflection)->delete();

        return redirect()->to(route('dashboard').'#reflecties')->with('success', 'Reflectie verwijderd.');
    }

    public function requestMeeting(Request $request, StudentDashboard $studentDashboard): RedirectResponse
    {
        $this->authorizeStudent($request);
        if (! Schema::hasTable('gesprekken')
            || ! Schema::hasTable('gesprekken_has_lid')
            || ! Schema::hasColumn('gesprekken', 'idgesprek')
            || ! Schema::hasColumn('gesprekken', 'datum')
            || ! Schema::hasColumn('gesprekken', 'onderwerp')
            || ! Schema::hasColumn('gesprekken', 'inhoud')
            || ! Schema::hasColumn('gesprekken', 'afspraken')
            || ! Schema::hasColumn('gesprekken_has_lid', 'gesprekken_idgesprek')
            || ! Schema::hasColumn('gesprekken_has_lid', 'lid_idlid')) {
            return back()->withErrors(['gesprekaanvraag' => 'Gespreksaanvragen kunnen niet worden opgeslagen met het huidige gespreksschema.']);
        }

        $coaches = $studentDashboard->coachesFor($request->user());
        if ($coaches->isEmpty()) {
            return back()->withErrors(['docent_id' => 'Er is geen actieve docent aan jouw account gekoppeld.']);
        }

        $data = $request->validate([
            'docent_id' => ['required', 'integer', Rule::in($coaches->pluck('docent_id')->all())],
            'datum' => ['required', 'date_format:Y-m-d\TH:i', 'after:now'],
            'bericht' => ['nullable', 'string', 'max:1000'],
        ]);
        $selectedCoach = $coaches->firstWhere('docent_id', (int) $data['docent_id']);
        $coach = User::query()
            ->whereKey($selectedCoach->docent_id)
            ->whereRaw('LOWER(status) = ?', ['actief'])
            ->whereHas('rol', fn ($query) => $query->whereRaw('LOWER(naam) IN (?, ?)', ['coach', 'docent']))
            ->firstOrFail();

        DB::transaction(function () use ($request, $data, $coach): void {
            $conversation = [
                'onderwerp' => \App\DocentDashboard::MEETING_REQUEST_TOPIC,
                'inhoud' => $data['bericht'] ?? null,
                'afspraken' => 'pending',
                'datum' => \Illuminate\Support\Carbon::createFromFormat('Y-m-d\TH:i', $data['datum'], 'Europe/Amsterdam')->format('Y-m-d H:i:s'),
            ];
            if (Schema::hasColumn('gesprekken', 'student_idstudent')) {
                $studentId = Schema::hasTable('student') && Schema::hasColumn('student', 'idstudent')
                    ? DB::table('student')->where('lid_idlid', $request->user()->getKey())->value('idstudent')
                    : null;
                if ($studentId !== null) {
                    $conversation['student_idstudent'] = $studentId;
                }
            }

            $conversationId = DB::table('gesprekken')->insertGetId($conversation, 'idgesprek');
            DB::table('gesprekken_has_lid')->insert([
                ['gesprekken_idgesprek' => $conversationId, 'lid_idlid' => $request->user()->getKey()],
                ['gesprekken_idgesprek' => $conversationId, 'lid_idlid' => $coach->getKey()],
            ]);

            if (Schema::hasTable('notifications')) {
                $studentName = trim(($request->user()->voornaam ?? '').' '.($request->user()->achternaam ?? '')) ?: 'Een student';
                $coach->notify(new ConversationRequestNotification($studentName, $data['bericht'] ?? null));
            }
        });

        return redirect()->to(route('dashboard').'#gesprekken')->with('success', 'Je gespreksaanvraag is naar je coach verstuurd.');
    }

    private function ownedGoal(Request $request, int $goal): Builder
    {
        abort_unless(Schema::hasTable('doelen') && Schema::hasColumn('doelen', 'lid_idlid'), 404);
        $primary = collect(Schema::getIndexes('doelen'))->first(fn (array $index) => $index['primary'] ?? false);
        $idColumn = $primary['columns'][0] ?? null;
        abort_unless(is_string($idColumn), 500, 'Het leerdoelschema heeft geen primaire sleutel.');

        $query = DB::table('doelen')->where($idColumn, $goal)->where('lid_idlid', $request->user()->getKey());
        abort_unless((clone $query)->exists(), 404);

        return $query;
    }

    private function ownedReflection(Request $request, int $reflection): Builder
    {
        abort_unless(Schema::hasTable('reflectie')
            && Schema::hasColumn('reflectie', 'idreflectie')
            && Schema::hasColumn('reflectie', 'lid_idlid'), 404);

        $query = DB::table('reflectie')
            ->where('idreflectie', $reflection)
            ->where('lid_idlid', $request->user()->getKey());
        abort_unless((clone $query)->exists(), 404);

        return $query;
    }

    private function goalTitleColumn(): string
    {
        $titleColumn = Schema::hasColumn('doelen', 'titel') ? 'titel' : 'naam';
        abort_unless(Schema::hasColumn('doelen', $titleColumn), 404);

        return $titleColumn;
    }

    private function authorizeStudent(Request $request): void
    {
        abort_unless(
            $request->user()?->dashboardRoleKey() === 'student'
                && strtolower((string) $request->user()->status) === 'actief',
            403,
        );
    }
}
