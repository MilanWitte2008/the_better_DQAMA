<?php

namespace App;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DocentDashboard
{
    /** Dezelfde rol- en begeleidingscontrole geldt voor lezen en wijzigen. */
    public function studentIds(User $user): Collection
    {
        abort_unless($user->dashboardRoleKey() === 'docent' && strtolower((string) $user->status) === 'actief', 403);

        $coachIds = DB::table('coach')->where('lid_idlid', $user->getKey())->pluck('idcoach');

        return DB::table('student_coach')->whereIn('coach_idcoach', $coachIds)
            ->whereRaw('LOWER(status) = ?', ['actief'])
            ->where(fn ($q) => $q->whereNull('startdatum')->orWhereDate('startdatum', '<=', today()))
            ->where(fn ($q) => $q->whereNull('einddatum')->orWhereDate('einddatum', '>=', today()))
            ->pluck('student_idstudent');
    }

    /**
     * Lees uitsluitend studenten met een actuele koppeling aan deze docent.
     * Alle vervolglijsten gebruiken dezelfde selectie, ook bij handmatige URL-parameters.
     */
    public function data(User $user, Request $request): array
    {
        $assigned = $this->studentIds($user);
        $coachIds = DB::table('coach')->where('lid_idlid', $user->getKey())->pluck('idcoach');
        $students = DB::table('student as s')->join('lid as l', 'l.idlid', '=', 's.lid_idlid')
            ->whereIn('s.idstudent', $assigned)
            ->select('s.idstudent', 's.klas', 's.opleiding', 's.studentnummer', 'l.voornaam', 'l.achternaam', 'l.email', 'l.status')
            ->orderBy('l.achternaam')->orderBy('l.voornaam')->get();
        $ids = $students->pluck('idstudent');
        $outcomes = DB::table('student_leeruitkomsten as a')
            ->join('leeruitkomsten as o', 'o.leeruitkomsten_id', '=', 'a.leeruitkomsten_leeruitkomsten_id')
            ->whereIn('a.student_idstudent', $ids)
            ->select('a.id_leeruitkomsten', 'a.opmerking', 'a.student_idstudent', 'a.status', 'a.vooruitgang', 'a.laatste_update', 'o.titel', 'o.code')
            ->orderBy('o.code')->get();
        foreach ($students as $student) {
            $items = $outcomes->where('student_idstudent', $student->idstudent);
            $student->total = $items->count();
            $student->achieved = $items->filter(fn ($item) => strtolower((string) $item->status) === 'behaald')->count();
            $student->progress = $student->total ? (int) round($student->achieved / $student->total * 100) : null;
            $student->lastUpdate = $items->pluck('laatste_update')->filter()->sortDesc()->first();
            $student->attention = ! $student->lastUpdate || $student->lastUpdate < now()->subDays(30)->toDateTimeString();
        }
        $text = fn (string $key): string => is_string($request->query($key)) ? mb_substr(trim($request->query($key)), 0, 100) : '';
        $search = $text('q');
        $class = $text('klas');
        $attention = $text('filter') === 'aandacht';
        $filtered = $students->filter(function ($student) use ($search, $class, $attention) {
            $haystack = mb_strtolower($student->voornaam.' '.$student->achternaam.' '.$student->email.' '.$student->studentnummer);

            return ($search === '' || str_contains($haystack, mb_strtolower($search)))
                && ($class === '' || $class === $student->klas)
                && (! $attention || $student->attention);
        });
        $selected = $students->firstWhere('idstudent', $text('student'));
        $notes = DB::table('begeleidingsnotitie as n')->join('student as s', 's.idstudent', '=', 'n.idstudent')
            ->join('lid as l', 'l.idlid', '=', 's.lid_idlid')->whereIn('n.idstudent', $ids)->whereIn('n.idcoach', $coachIds)
            ->select('n.id_notitie', 'n.idstudent', 'n.inhoud', 'n.datum', 'n.type', 'l.voornaam', 'l.achternaam')->orderByDesc('n.datum');
        $meetings = DB::table('gesprekken as g')->join('student as s', 's.idstudent', '=', 'g.student_idstudent')
            ->join('lid as l', 'l.idlid', '=', 's.lid_idlid')->whereIn('g.student_idstudent', $ids)
            ->select('g.idgesprek', 'g.student_idstudent', 'g.datum', 'g.onderwerp', 'g.inhoud', 'g.afspraken', 'l.voornaam', 'l.achternaam');
        $total = $outcomes->count();
        $achieved = $outcomes->filter(fn ($item) => strtolower((string) $item->status) === 'behaald')->count();

        return compact('students', 'filtered', 'selected', 'outcomes', 'search', 'class', 'attention', 'total', 'achieved') + [
            'availableOutcomes' => $selected ? DB::table('leeruitkomsten')->whereNotIn('leeruitkomsten_id', DB::table('student_leeruitkomsten')->where('student_idstudent', $selected->idstudent)->select('leeruitkomsten_leeruitkomsten_id'))->orderBy('code')->get(['leeruitkomsten_id', 'code', 'titel']) : collect(),
            'classes' => $students->pluck('klas')->filter()->unique()->sort(),
            'attentionCount' => $students->where('attention', true)->count(),
            'coachLinked' => $coachIds->isNotEmpty(),
            'notesCount' => (clone $notes)->count(),
            'notes' => $notes->limit(8)->get(),
            'upcomingCount' => (clone $meetings)->where('g.datum', '>=', now())->count(),
            'upcoming' => (clone $meetings)->where('g.datum', '>=', now())->orderBy('g.datum')->limit(5)->get(),
            'recent' => (clone $meetings)->where('g.datum', '<', now())->orderByDesc('g.datum')->limit(5)->get(),
        ];
    }
}
