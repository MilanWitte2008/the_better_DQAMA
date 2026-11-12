<?php

namespace App\Http\Controllers;

use App\DocentDashboard;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
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

    /** Oude formulieren mogen de centrale accountadministratie niet omzeilen. */
    public function storeStudent(Request $request): RedirectResponse
    {
        abort(403, 'Studentaccounts worden beheerd door de applicatiebeheerder.');
    }

    public function updateStudent(Request $request, int $student): RedirectResponse
    {
        abort(403, 'Studentgegevens worden beheerd door de applicatiebeheerder.');
    }

    /** Verwijderen uit Mijn studenten beëindigt alleen de eigen begeleiding. */
    public function detachStudent(Request $request, int $student): RedirectResponse
    {
        $this->student($request, $student);
        DB::table('student_coach')->where('student_idstudent', $student)
            ->whereIn('coach_idcoach', app(DocentDashboard::class)->coachIds($request->user()))
            ->whereRaw('LOWER(status) = ?', ['actief'])
            ->where(fn ($q) => $q->whereNull('startdatum')->orWhereDate('startdatum', '<=', today()))
            ->where(fn ($q) => $q->whereNull('einddatum')->orWhereDate('einddatum', '>=', today()))
            ->update(['status' => 'inactief', 'einddatum' => today()->toDateString()]);

        return redirect()->to(route('dashboard').'#studenten')->with('docent_success', 'Student uit jouw begeleiding verwijderd. Het account en dossier zijn bewaard.');
    }

    /** Maak een echte leeruitkomst aan en wijs deze zonder voltooiing toe aan de student. */
    public function createOutcome(Request $request, int $student): RedirectResponse
    {
        $this->student($request, $student);
        $data = $request->validate([
            'titel' => ['required', 'string', 'max:255'],
            'omschrijving' => ['nullable', 'string', 'max:10000'],
        ]);
        DB::transaction(function () use ($data, $student): void {
            $id = DB::table('leeruitkomsten')->insertGetId($data + [
                'code' => 'LO-'.Str::upper(Str::random(12)),
            ], 'leeruitkomsten_id');
            DB::table('student_leeruitkomsten')->insert([
                'student_idstudent' => $student, 'leeruitkomsten_leeruitkomsten_id' => $id,
                'status' => 'Te doen', 'vooruitgang' => 0, 'laatste_update' => now(),
            ]);
        });

        return $this->done('student-detail', ['student' => $student]);
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

    /** Controleer het record opnieuw binnen de actuele leerlingkoppelingen. */
    private function note(Request $request, int $note): Builder
    {
        $ids = app(DocentDashboard::class)->studentIds($request->user());
        $query = DB::table('begeleidingsnotitie')->where('id_notitie', $note);
        if (Schema::hasColumn('begeleidingsnotitie', 'idstudent')
            && Schema::hasColumn('begeleidingsnotitie', 'idcoach')) {
            $query->whereIn('idstudent', $ids)
                ->whereIn('idcoach', app(DocentDashboard::class)->coachIds($request->user()));
        } elseif (Schema::hasColumn('begeleidingsnotitie', 'lid_idlid')) {
            $query->whereIn('lid_idlid', $ids);
        } else {
            abort(500, 'Het notitieschema wordt niet ondersteund.');
        }
        abort_unless((clone $query)->exists(), 404);

        return $query;
    }

    public function storeNote(Request $request): RedirectResponse
    {
        $data = $request->validate(['idstudent' => ['required', 'integer'], 'inhoud' => ['required', 'string', 'max:10000'], 'type' => ['required', Rule::in(['Notitie', 'Voortgang', 'Afspraak'])]]);
        $this->student($request, (int) $data['idstudent']);
        if (Schema::hasColumn('begeleidingsnotitie', 'idstudent')
            && Schema::hasColumn('begeleidingsnotitie', 'idcoach')) {
            $coachId = app(DocentDashboard::class)->coachIds($request->user())->first();
            DB::table('begeleidingsnotitie')->insert($data + ['idcoach' => $coachId, 'datum' => now()]);
        } elseif (Schema::hasColumn('begeleidingsnotitie', 'lid_idlid')) {
            DB::table('begeleidingsnotitie')->insert([
                'lid_idlid' => (int) $data['idstudent'],
                'inhoud' => $data['inhoud'],
                'type' => $data['type'],
                'datum' => now(),
            ]);
        } else {
            abort(500, 'Het notitieschema wordt niet ondersteund.');
        }

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
        $query = DB::table('gesprekken')->where('idgesprek', $meeting);
        if (Schema::hasColumn('gesprekken', 'student_idstudent')) {
            $query->whereIn('student_idstudent', app(DocentDashboard::class)->studentIds($request->user()));
        } elseif ($this->hasLegacyMeetingParticipants()) {
            $studentIds = app(DocentDashboard::class)->studentIds($request->user());
            $query->whereExists(fn ($link) => $link->selectRaw('1')
                ->from('gesprekken_has_lid as teacher_link')
                ->whereColumn('teacher_link.gesprekken_idgesprek', 'gesprekken.idgesprek')
                ->where('teacher_link.lid_idlid', $request->user()->getKey()))
                ->whereExists(fn ($link) => $link->selectRaw('1')
                    ->from('gesprekken_has_lid as student_link')
                    ->whereColumn('student_link.gesprekken_idgesprek', 'gesprekken.idgesprek')
                    ->whereIn('student_link.lid_idlid', $studentIds));
        } else {
            abort(500, 'Het gespreksschema wordt niet ondersteund.');
        }
        abort_unless((clone $query)->exists(), 404);

        return $query;
    }

    private function hasLegacyMeetingParticipants(): bool
    {
        return Schema::hasTable('gesprekken_has_lid')
            && Schema::hasColumn('gesprekken_has_lid', 'gesprekken_idgesprek')
            && Schema::hasColumn('gesprekken_has_lid', 'lid_idlid');
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
        if (Schema::hasColumn('gesprekken', 'student_idstudent')) {
            DB::table('gesprekken')->insert($data + $student);
        } elseif ($this->hasLegacyMeetingParticipants()) {
            DB::transaction(function () use ($data, $student, $request): void {
                $meeting = DB::table('gesprekken')->insertGetId($data, 'idgesprek');
                DB::table('gesprekken_has_lid')->insert([
                    ['gesprekken_idgesprek' => $meeting, 'lid_idlid' => $request->user()->getKey()],
                    ['gesprekken_idgesprek' => $meeting, 'lid_idlid' => (int) $student['student_idstudent']],
                ]);
            });
        } else {
            abort(500, 'Het gespreksschema wordt niet ondersteund.');
        }

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
        $query = $this->meeting($request, $meeting);
        if (! Schema::hasColumn('gesprekken', 'student_idstudent')) {
            DB::transaction(function () use ($meeting): void {
                DB::table('gesprekken_has_lid')->where('gesprekken_idgesprek', $meeting)->delete();
                DB::table('gesprekken')->where('idgesprek', $meeting)->delete();
            });
        } else {
            $query->delete();
        }

        return $this->done('gesprekken');
    }

    public function approveMeetingRequest(Request $request, int $aanvraag): RedirectResponse
    {
        return $this->updateMeetingRequestStatus($request, $aanvraag, 'approved');
    }

    public function rejectMeetingRequest(Request $request, int $aanvraag): RedirectResponse
    {
        return $this->updateMeetingRequestStatus($request, $aanvraag, 'rejected');
    }

    private function updateMeetingRequestStatus(Request $request, int $aanvraag, string $status): RedirectResponse
    {
        abort_unless(
            $request->user()?->dashboardRoleKey() === 'docent'
                && strtolower((string) $request->user()->status) === 'actief',
            403,
        );
        abort_unless(Schema::hasTable('gesprekken')
            && Schema::hasColumn('gesprekken', 'idgesprek')
            && Schema::hasColumn('gesprekken', 'onderwerp')
            && Schema::hasColumn('gesprekken', 'afspraken')
            && $this->hasLegacyMeetingParticipants(), 404);

        $requestIds = app(DocentDashboard::class)->visibleConversationRequestIds($request->user());
        $query = DB::table('gesprekken')
            ->where('idgesprek', $aanvraag)
            ->whereIn('idgesprek', $requestIds)
            ->where('afspraken', 'pending');
        $updated = $query->update(['afspraken' => $status]);
        abort_unless($updated === 1, 404);

        return $this->done('gesprekken', ['aanvragen' => 'verwerkt']);
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
