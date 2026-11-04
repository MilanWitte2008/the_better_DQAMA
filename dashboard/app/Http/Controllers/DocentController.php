<?php

namespace App\Http\Controllers;

use App\DocentDashboard;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class DocentController extends Controller
{
    /** Elke schrijfactie controleert de actuele begeleiding opnieuw op de server. */
    private function student(Request $request, int $student): void
    {
        abort_unless(app(DocentDashboard::class)->studentIds($request->user())->contains($student), 403);
    }

    private function done(string $section, array $query = []): RedirectResponse
    {
        return redirect()->to(route('dashboard', $query).'#'.$section)->with('docent_success', 'Wijzigingen opgeslagen.');
    }

    /** Maak account, studentprofiel en begeleiding samen aan, uitsluitend met de studentrol. */
    public function storeStudent(Request $request): RedirectResponse
    {
        app(DocentDashboard::class)->studentIds($request->user());
        $coachId = DB::table('coach')->where('lid_idlid', $request->user()->getKey())->orderBy('idcoach')->value('idcoach');
        abort_unless($coachId, 403, 'Een coachprofiel is nodig om studenten toe te voegen.');
        $data = $request->validate([
            'voornaam' => ['required', 'string', 'max:50'],
            'achternaam' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:100', Rule::unique('lid', 'email')],
            'password' => ['required', 'string', 'min:8', 'max:72', 'confirmed'],
            'studentnummer' => ['nullable', 'string', 'max:20', Rule::unique('student', 'studentnummer')],
            'klas' => ['nullable', 'string', 'max:20'],
            'opleiding' => ['nullable', 'string', 'max:50'],
        ]);
        $roleId = DB::table('rol')->whereRaw('LOWER(naam) = ?', ['student'])->value('idrol');
        abort_unless($roleId, 422, 'De studentrol ontbreekt. Vraag de beheerder deze toe te voegen.');
        $studentId = DB::transaction(function () use ($data, $coachId, $roleId): int {
            $memberId = DB::table('lid')->insertGetId([
                'voornaam' => $data['voornaam'], 'achternaam' => $data['achternaam'],
                'email' => $data['email'], 'wachtwoord' => Hash::make($data['password']),
                'status' => 'actief', 'rol_idrol' => $roleId, 'aangemaakt_datum' => now(),
            ], 'idlid');
            $studentId = DB::table('student')->insertGetId([
                'lid_idlid' => $memberId, 'studentnummer' => $data['studentnummer'] ?? null,
                'klas' => $data['klas'] ?? null, 'opleiding' => $data['opleiding'] ?? null,
                'startdatum' => today()->toDateString(),
            ], 'idstudent');
            DB::table('student_coach')->insert([
                'student_idstudent' => $studentId, 'coach_idcoach' => $coachId,
                'status' => 'actief', 'startdatum' => today()->toDateString(),
            ]);

            return $studentId;
        });

        return redirect()->to(route('dashboard', ['student' => $studentId]).'#student-detail')
            ->with('docent_success', 'Studentaccount aangemaakt en aan jouw begeleiding gekoppeld.');
    }

    public function updateStudent(Request $request, int $student): RedirectResponse
    {
        $this->student($request, $student);
        $data = $request->validate([
            'klas' => ['nullable', 'string', 'max:20'],
            'opleiding' => ['nullable', 'string', 'max:50'],
        ]);
        DB::table('student')->where('idstudent', $student)->update($data);

        return $this->done('student-detail', ['student' => $student]);
    }

    /** Verwijderen uit Mijn studenten beëindigt alleen de eigen begeleiding. */
    public function detachStudent(Request $request, int $student): RedirectResponse
    {
        $this->student($request, $student);
        DB::table('student_coach')->where('student_idstudent', $student)
            ->whereIn('coach_idcoach', DB::table('coach')->where('lid_idlid', $request->user()->getKey())->select('idcoach'))
            ->whereRaw('LOWER(status) = ?', ['actief'])
            ->where(fn ($q) => $q->whereNull('startdatum')->orWhereDate('startdatum', '<=', today()))
            ->where(fn ($q) => $q->whereNull('einddatum')->orWhereDate('einddatum', '>=', today()))
            ->update(['status' => 'inactief', 'einddatum' => today()->toDateString()]);

        return redirect()->to(route('dashboard').'#studenten')->with('docent_success', 'Student uit jouw begeleiding verwijderd. Het account en dossier zijn bewaard.');
    }

    public function storeOutcome(Request $request, int $student): RedirectResponse
    {
        $this->student($request, $student);
        $data = $request->validate([
            'leeruitkomsten_leeruitkomsten_id' => ['required', 'integer', Rule::exists('leeruitkomsten', 'leeruitkomsten_id')],
        ]);
        DB::transaction(function () use ($student, $data): void {
            DB::table('student')->where('idstudent', $student)->lockForUpdate()->first();
            $query = DB::table('student_leeruitkomsten')->where('student_idstudent', $student)
                ->where('leeruitkomsten_leeruitkomsten_id', $data['leeruitkomsten_leeruitkomsten_id']);
            if (! $query->exists()) {
                DB::table('student_leeruitkomsten')->insert($data + ['student_idstudent' => $student, 'status' => 'Te doen', 'vooruitgang' => 0, 'laatste_update' => now()]);
            }
        });

        return $this->done('student-detail', ['student' => $student]);
    }

    /** Notities blijven eigendom van hun auteur; een andere coach kan ze niet wijzigen. */
    private function note(Request $request, int $note): Builder
    {
        $ids = app(DocentDashboard::class)->studentIds($request->user());
        $query = DB::table('begeleidingsnotitie')->where('id_notitie', $note)
            ->whereIn('idstudent', $ids)
            ->whereIn('idcoach', DB::table('coach')->where('lid_idlid', $request->user()->getKey())->select('idcoach'));
        abort_unless((clone $query)->exists(), 404);

        return $query;
    }

    public function storeNote(Request $request): RedirectResponse
    {
        $data = $request->validate(['idstudent' => ['required', 'integer'], 'inhoud' => ['required', 'string', 'max:10000'], 'type' => ['required', Rule::in(['Notitie', 'Voortgang', 'Afspraak'])]]);
        $this->student($request, (int) $data['idstudent']);
        $coachId = DB::table('coach')->where('lid_idlid', $request->user()->getKey())->orderBy('idcoach')->value('idcoach');
        DB::table('begeleidingsnotitie')->insert($data + ['idcoach' => $coachId, 'datum' => now()]);

        return $this->done('notities');
    }

    public function updateNote(Request $request, int $note): RedirectResponse
    {
        $query = $this->note($request, $note);
        $data = $request->validate(['inhoud' => ['required', 'string', 'max:10000'], 'type' => ['required', 'string', 'max:50']]);
        $query->update($data);

        return $this->done('notities');
    }

    public function deleteNote(Request $request, int $note): RedirectResponse
    {
        $this->note($request, $note)->delete();

        return $this->done('notities');
    }

    private function meeting(Request $request, int $meeting): Builder
    {
        $query = DB::table('gesprekken')->where('idgesprek', $meeting)
            ->whereIn('student_idstudent', app(DocentDashboard::class)->studentIds($request->user()));
        abort_unless((clone $query)->exists(), 404);

        return $query;
    }

    private function meetingData(Request $request): array
    {
        $data = $request->validate([
            'onderwerp' => ['required', 'string', 'max:255'],
            'datum' => ['required', 'date_format:Y-m-d\TH:i,Y-m-d\TH:i:s'],
            'inhoud' => ['nullable', 'string', 'max:10000'],
            'afspraken' => ['nullable', 'string', 'max:10000'],
        ]);
        $data['datum'] = Carbon::parse($data['datum'])->format('Y-m-d H:i:s');

        return $data;
    }

    public function storeMeeting(Request $request): RedirectResponse
    {
        $student = $request->validate(['student_idstudent' => ['required', 'integer']]);
        $this->student($request, (int) $student['student_idstudent']);
        $data = $this->meetingData($request);
        DB::table('gesprekken')->insert($data + $student);

        return $this->done('gesprekken');
    }

    public function updateMeeting(Request $request, int $meeting): RedirectResponse
    {
        $query = $this->meeting($request, $meeting);
        $query->update($this->meetingData($request));

        return $this->done('gesprekken');
    }

    public function deleteMeeting(Request $request, int $meeting): RedirectResponse
    {
        $this->meeting($request, $meeting)->delete();

        return $this->done('gesprekken');
    }

    public function updateOutcome(Request $request, int $outcome): RedirectResponse
    {
        $ids = app(DocentDashboard::class)->studentIds($request->user());
        $query = DB::table('student_leeruitkomsten')->where('id_leeruitkomsten', $outcome)->whereIn('student_idstudent', $ids);
        $record = (clone $query)->first();
        abort_unless($record, 404);
        $data = $request->validate(['status' => ['required', Rule::in(['Te doen', 'Bezig', 'Behaald'])], 'opmerking' => ['nullable', 'string', 'max:10000']]);
        $query->update($data + ['laatste_update' => now(), 'vooruitgang' => $data['status'] === 'Behaald' ? 100 : null]);

        return $this->done('student-detail', ['student' => $record->student_idstudent]);
    }
}
