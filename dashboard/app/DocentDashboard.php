<?php

namespace App;

use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DocentDashboard
{
    public const MEETING_REQUEST_TOPIC = '__gesprekaanvraag__';

    public function coachIds(User $user): Collection
    {
        abort_unless($user->dashboardRoleKey() === 'docent' && strtolower((string) $user->status) === 'actief', 403);

        return Schema::hasTable('coach')
            ? DB::table('coach')->where('lid_idlid', $user->getKey())->pluck('idcoach')
            : collect();
    }

    /** Dezelfde rol- en begeleidingscontrole geldt voor lezen en wijzigen. */
    public function studentIds(User $user): Collection
    {
        $coachIds = $this->coachIds($user);
        $studentIds = $coachIds->isNotEmpty() && Schema::hasTable('student_coach')
            ? DB::table('student_coach')->whereIn('coach_idcoach', $coachIds)
                ->whereRaw('LOWER(status) = ?', ['actief'])
                ->where(fn ($q) => $q->whereNull('startdatum')->orWhereDate('startdatum', '<=', today()))
                ->where(fn ($q) => $q->whereNull('einddatum')->orWhereDate('einddatum', '>=', today()))
                ->pluck('student_idstudent')
            : collect();

        $groupIds = $this->teacherGroupIds($user);
        if ($groupIds->isNotEmpty()
            && Schema::hasTable('lid')
            && Schema::hasTable('rol')
            && Schema::hasTable('groep_has_lid')) {
            $groupStudents = DB::table('groep_has_lid as membership')
                ->join('lid as student_member', 'student_member.idlid', '=', 'membership.idlid')
                ->join('rol as student_role', 'student_role.idrol', '=', 'student_member.rol_idrol')
                ->whereIn('membership.idgroep', $groupIds)
                ->where('student_member.status', 'actief')
                ->whereRaw('LOWER(student_role.naam) = ?', ['student']);

            if (Schema::hasTable('student') && Schema::hasColumn('student', 'lid_idlid')) {
                $groupStudentIds = (clone $groupStudents)
                    ->join('student', 'student.lid_idlid', '=', 'membership.idlid')
                    ->distinct()
                    ->pluck('student.idstudent');
            } else {
                $groupStudentIds = (clone $groupStudents)->distinct()->pluck('membership.idlid');
            }

            $studentIds = $studentIds->merge($groupStudentIds);
        }

        return $studentIds->unique()->values();
    }

    public function studentMemberIds(User $user): Collection
    {
        $studentIds = $this->studentIds($user);
        $memberIds = collect();

        if (Schema::hasTable('student')
            && Schema::hasColumn('student', 'idstudent')
            && Schema::hasColumn('student', 'lid_idlid')) {
            $memberIds = DB::table('student')->whereIn('idstudent', $studentIds)->pluck('lid_idlid');
        } else {
            $memberIds = $studentIds;
        }

        $groupIds = $this->teacherGroupIds($user);
        if ($groupIds->isNotEmpty()) {
            $memberIds = $memberIds->merge($this->teacherGroupStudents($groupIds)->pluck('idlid'));
        }

        return $memberIds->unique()->values();
    }

    private function teacherGroupIds(User $user): Collection
    {
        if (! Schema::hasTable('groep')
            || ! Schema::hasTable('groep_has_lid')
            || ! Schema::hasColumn('groep', 'idgroep')
            || ! Schema::hasColumn('groep', 'groepnaam')
            || ! Schema::hasColumn('groep_has_lid', 'idgroep')
            || ! Schema::hasColumn('groep_has_lid', 'idlid')) {
            return collect();
        }

        $groups = DB::table('groep as teacher_group')
            ->join('groep_has_lid as membership', 'membership.idgroep', '=', 'teacher_group.idgroep')
            ->where('membership.idlid', $user->getKey())
            ->whereRaw('LOWER(COALESCE(teacher_group.groepnaam, ?)) <> ?', ['', 'alle leden']);

        if (Schema::hasColumn('groep', 'status')) {
            $groups->whereRaw('LOWER(COALESCE(teacher_group.status, ?)) = ?', ['', 'actief']);
        }

        return $groups->pluck('teacher_group.idgroep')->unique()->values();
    }

    private function teacherGroupStudents(Collection $groupIds): Collection
    {
        if ($groupIds->isEmpty() || ! Schema::hasTable('lid') || ! Schema::hasTable('rol')) {
            return collect();
        }

        $students = DB::table('groep_has_lid as membership')
            ->join('groep as teacher_group', 'teacher_group.idgroep', '=', 'membership.idgroep')
            ->join('lid as student_member', 'student_member.idlid', '=', 'membership.idlid')
            ->join('rol as student_role', 'student_role.idrol', '=', 'student_member.rol_idrol')
            ->whereIn('membership.idgroep', $groupIds)
            ->whereRaw('LOWER(student_role.naam) = ?', ['student']);

        if (Schema::hasColumn('lid', 'status')) {
            $students->whereRaw('LOWER(student_member.status) = ?', ['actief']);
        }

        $columns = [
            'teacher_group.idgroep',
            'teacher_group.groepnaam',
            'student_member.idlid',
            'student_member.voornaam',
            'student_member.achternaam',
            Schema::hasColumn('lid', 'email')
                ? 'student_member.email'
                : DB::raw('NULL AS email'),
        ];

        return $students
            ->select($columns)
            ->orderBy('teacher_group.groepnaam')
            ->orderBy('student_member.achternaam')
            ->orderBy('student_member.voornaam')
            ->distinct()
            ->get();
    }

    private function conversationRequests(User $user): Collection
    {
        if (! Schema::hasTable('gesprekken')
            || ! Schema::hasColumn('gesprekken', 'idgesprek')
            || ! Schema::hasColumn('gesprekken', 'onderwerp')
            || ! Schema::hasColumn('gesprekken', 'inhoud')
            || ! Schema::hasColumn('gesprekken', 'afspraken')
            || ! Schema::hasTable('gesprekken_has_lid')
            || ! Schema::hasTable('lid')
            || ! Schema::hasColumn('lid', 'idlid')
            || ! Schema::hasColumn('lid', 'voornaam')
            || ! Schema::hasColumn('lid', 'achternaam')
            || ! Schema::hasColumn('lid', 'rol_idrol')
            || ! Schema::hasColumn('lid', 'status')
            || ! Schema::hasTable('rol')
            || ! Schema::hasColumn('rol', 'idrol')
            || ! Schema::hasColumn('rol', 'naam')) {
            return collect();
        }

        return DB::table('gesprekken as g')
            ->join('gesprekken_has_lid as student_link', 'student_link.gesprekken_idgesprek', '=', 'g.idgesprek')
            ->join('lid as l', 'l.idlid', '=', 'student_link.lid_idlid')
            ->join('rol as student_role', 'student_role.idrol', '=', 'l.rol_idrol')
            ->whereIn('g.idgesprek', $this->visibleConversationRequestIds($user))
            ->whereRaw('LOWER(student_role.naam) = ?', ['student'])
            ->whereRaw('LOWER(l.status) = ?', ['actief'])
            ->select('g.idgesprek', 'student_link.lid_idlid as student_idstudent', 'g.datum', 'g.inhoud', 'g.afspraken', 'l.voornaam', 'l.achternaam')
            ->selectRaw('? AS onderwerp', ['Gespreksaanvraag'])
            ->distinct()
            ->orderByRaw('CASE WHEN g.datum IS NULL THEN 1 ELSE 0 END')
            ->orderBy('g.datum')
            ->orderBy('g.idgesprek')
            ->get();
    }

    public function visibleConversationRequestIds(User $user): Builder
    {
        $requests = DB::table('gesprekken as g')->select('g.idgesprek');

        if (! Schema::hasTable('gesprekken_has_lid')
            || ! Schema::hasColumn('gesprekken_has_lid', 'gesprekken_idgesprek')
            || ! Schema::hasColumn('gesprekken_has_lid', 'lid_idlid')
            || ! Schema::hasTable('lid')
            || ! Schema::hasColumn('lid', 'idlid')
            || ! Schema::hasColumn('lid', 'rol_idrol')
            || ! Schema::hasColumn('lid', 'status')
            || ! Schema::hasTable('rol')
            || ! Schema::hasColumn('rol', 'idrol')
            || ! Schema::hasColumn('rol', 'naam')) {
            return $requests->whereRaw('1 = 0');
        }

        return $requests
            ->where('g.onderwerp', self::MEETING_REQUEST_TOPIC)
            ->where(fn ($query) => $query->whereNull('g.afspraken')->orWhere('g.afspraken', '<>', 'rejected'))
            ->whereExists(fn ($query) => $query->selectRaw('1')
                ->from('gesprekken_has_lid as teacher_link')
                ->whereColumn('teacher_link.gesprekken_idgesprek', 'g.idgesprek')
                ->where('teacher_link.lid_idlid', $user->getKey()))
            ->whereExists(fn ($query) => $query->selectRaw('1')
                ->from('gesprekken_has_lid as student_link')
                ->join('lid as request_student', 'request_student.idlid', '=', 'student_link.lid_idlid')
                ->join('rol as request_student_role', 'request_student_role.idrol', '=', 'request_student.rol_idrol')
                ->whereColumn('student_link.gesprekken_idgesprek', 'g.idgesprek')
                ->whereRaw('LOWER(request_student_role.naam) = ?', ['student'])
                ->whereRaw('LOWER(request_student.status) = ?', ['actief']));
    }

    /**
     * Lees uitsluitend studenten met een actuele koppeling aan deze docent.
     * Alle vervolglijsten gebruiken dezelfde selectie, ook bij handmatige URL-parameters.
     */
    public function data(User $user, Request $request): array
    {
        $coachIds = $this->coachIds($user);
        $groupIds = $this->teacherGroupIds($user);
        $teacherGroups = $groupIds->isNotEmpty()
            ? DB::table('groep')
                ->whereIn('idgroep', $groupIds)
                ->select('idgroep', 'groepnaam')
                ->orderBy('groepnaam')
                ->get()
            : collect();
        $teacherGroupStudents = $this->teacherGroupStudents($groupIds);

        if (! Schema::hasTable('coach')) {
            $search = is_string($request->query('q')) ? mb_substr(trim($request->query('q')), 0, 100) : '';
            $class = is_string($request->query('klas')) ? mb_substr(trim($request->query('klas')), 0, 100) : '';
            $visibleGroupStudents = $teacherGroupStudents
                ->filter(function ($groupStudent) use ($search, $class): bool {
                    $name = mb_strtolower(trim(($groupStudent->voornaam ?? '').' '.($groupStudent->achternaam ?? '')));
                    $groupName = (string) $groupStudent->groepnaam;

                    return ($class === '' || $class === $groupName)
                        && ($search === '' || str_contains($name, mb_strtolower($search)));
                })
                ->values();
            $studentIds = $this->studentMemberIds($user);
            $notes = $this->legacyNotes($studentIds);
            $meetings = $this->legacyMeetings($user, $studentIds);
            $studentCount = $teacherGroupStudents->pluck('idlid')->unique()->count();
            $outcomes = collect();
            if (Schema::hasTable('leeruitkomsten_has_lid')
                && Schema::hasTable('leeruitkomsten')
                && Schema::hasColumn('leeruitkomsten_has_lid', 'lid_idlid')
                && Schema::hasColumn('leeruitkomsten_has_lid', 'leeruitkomsten_id')
                && Schema::hasColumn('leeruitkomsten', 'leeruitkomsten_id')
                && Schema::hasColumn('leeruitkomsten', 'titel')) {
                $outcomeColumns = [
                    'assignment.lid_idlid as student_idstudent',
                    'outcome.titel',
                    Schema::hasColumn('leeruitkomsten', 'code')
                        ? 'outcome.code'
                        : DB::raw('NULL AS code'),
                    Schema::hasColumn('leeruitkomsten_has_lid', 'status')
                        ? 'assignment.status'
                        : DB::raw('NULL AS status'),
                    Schema::hasColumn('leeruitkomsten', 'omschrijving')
                        ? 'outcome.omschrijving'
                        : DB::raw('NULL AS omschrijving'),
                    Schema::hasColumn('leeruitkomsten_has_lid', 'opmerking')
                        ? 'assignment.opmerking'
                        : DB::raw('NULL AS opmerking'),
                    Schema::hasColumn('leeruitkomsten_has_lid', 'laatste_update')
                        ? 'assignment.laatste_update'
                        : DB::raw('NULL AS laatste_update'),
                ];
                $outcomeQuery = DB::table('leeruitkomsten_has_lid as assignment')
                    ->join('leeruitkomsten as outcome', 'outcome.leeruitkomsten_id', '=', 'assignment.leeruitkomsten_id')
                    ->whereIn('assignment.lid_idlid', $teacherGroupStudents->pluck('idlid'));

                $phaseColumn = Schema::hasColumn('leeruitkomsten', 'fase_idfase1')
                    ? 'fase_idfase1'
                    : (Schema::hasColumn('leeruitkomsten', 'fase_idfase') ? 'fase_idfase' : null);
                if ($phaseColumn
                    && Schema::hasTable('fase')
                    && Schema::hasColumn('fase', 'idfase')
                    && Schema::hasColumn('fase', 'naam')) {
                    $outcomeQuery->leftJoin('fase', 'fase.idfase', '=', "outcome.$phaseColumn");
                    $outcomeColumns[] = 'fase.naam as fase_naam';
                } else {
                    $outcomeColumns[] = DB::raw('NULL AS fase_naam');
                }

                $outcomes = $outcomeQuery
                    ->orderBy('outcome.titel')
                    ->select($outcomeColumns)
                    ->get();
            }
            foreach ($teacherGroupStudents as $groupStudent) {
                $studentOutcomes = $outcomes->where('student_idstudent', $groupStudent->idlid);
                $groupStudent->total = $studentOutcomes->count();
                $groupStudent->achieved = $studentOutcomes
                    ->filter(fn ($outcome) => strtolower((string) $outcome->status) === 'behaald')
                    ->count();
                $groupStudent->progress = $groupStudent->total > 0
                    ? (int) round($groupStudent->achieved / $groupStudent->total * 100)
                    : null;
                $groupStudent->lastUpdate = $studentOutcomes
                    ->pluck('laatste_update')
                    ->filter()
                    ->sortDesc()
                    ->first();
            }
            $total = $outcomes->count();
            $achieved = $outcomes->filter(fn ($outcome) => strtolower((string) $outcome->status) === 'behaald')->count();
            $requestedStudent = $request->query('student');
            $selectedGroupStudent = is_string($requestedStudent) && ctype_digit($requestedStudent)
                ? $teacherGroupStudents->firstWhere('idlid', (int) $requestedStudent)
                : null;
            $selected = $selectedGroupStudent ? (object) [
                'idstudent' => $selectedGroupStudent->idlid,
                'studentnummer' => null,
                'klas' => $selectedGroupStudent->groepnaam,
                'opleiding' => null,
                'email' => $selectedGroupStudent->email,
                'voornaam' => $selectedGroupStudent->voornaam,
                'achternaam' => $selectedGroupStudent->achternaam,
            ] : null;

            return [
                'students' => collect(),
                'studentCount' => $studentCount,
                'filtered' => collect(),
                'selected' => $selected,
                'outcomes' => $outcomes,
                'search' => $search,
                'class' => $class,
                'attention' => false,
                'total' => $total,
                'achieved' => $achieved,
                'teacherGroups' => $teacherGroups,
                'teacherGroupStudents' => $teacherGroupStudents,
                'visibleGroupStudents' => $visibleGroupStudents,
                'legacyMode' => true,
                'availableOutcomes' => collect(),
                'classes' => $teacherGroups->pluck('groepnaam')->filter()->values(),
                'attentionCount' => 0,
                'coachLinked' => $coachIds->isNotEmpty() || $groupIds->isNotEmpty(),
                'meetingRequests' => $this->conversationRequests($user),
                'notesCount' => (clone $notes)->count(),
                'notes' => $notes->limit(8)->get(),
                'upcomingCount' => (clone $meetings)->where('g.datum', '>=', now())->count(),
                'upcoming' => (clone $meetings)->where('g.datum', '>=', now())->orderBy('g.datum')->limit(5)->get(),
                'recent' => (clone $meetings)->where('g.datum', '<', now())->orderByDesc('g.datum')->limit(5)->get(),
            ];
        }

        $assigned = $this->studentIds($user);
        $students = DB::table('student as s')->join('lid as l', 'l.idlid', '=', 's.lid_idlid')
            ->whereIn('s.idstudent', $assigned)
            ->select('s.idstudent', 's.lid_idlid', 's.klas', 's.opleiding', 's.studentnummer', 'l.voornaam', 'l.achternaam', 'l.email', 'l.status')
            ->orderBy('l.achternaam')->orderBy('l.voornaam')->get();
        $studentCount = $students->pluck('lid_idlid')
            ->merge($teacherGroupStudents->pluck('idlid'))
            ->unique()
            ->count();
        $ids = $students->pluck('idstudent');
        $outcomes = DB::table('student_leeruitkomsten as a')
            ->join('leeruitkomsten as o', 'o.leeruitkomsten_id', '=', 'a.leeruitkomsten_leeruitkomsten_id')
            ->whereIn('a.student_idstudent', $ids)
            ->select(
                'a.id_leeruitkomsten',
                'a.opmerking',
                'a.student_idstudent',
                'a.status',
                'a.vooruitgang',
                'a.laatste_update',
                'o.titel',
                'o.code',
                Schema::hasColumn('leeruitkomsten', 'omschrijving')
                    ? 'o.omschrijving'
                    : DB::raw('NULL AS omschrijving'),
                DB::raw('NULL AS fase_naam'),
            )
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
            ->join('lid as l', 'l.idlid', '=', 's.lid_idlid')            ->whereIn('g.student_idstudent', $ids)
            ->where('g.onderwerp', '<>', self::MEETING_REQUEST_TOPIC)
            ->select('g.idgesprek', 'g.student_idstudent', 'g.datum', 'g.inhoud', 'g.afspraken', 'l.voornaam', 'l.achternaam')
            ->selectRaw(
                'CASE WHEN g.onderwerp = ? THEN ? ELSE g.onderwerp END AS onderwerp',
                [self::MEETING_REQUEST_TOPIC, 'Gespreksaanvraag'],
            );
        $total = $outcomes->count();
        $achieved = $outcomes->filter(fn ($item) => strtolower((string) $item->status) === 'behaald')->count();

        $visibleGroupStudents = $teacherGroupStudents
            ->filter(fn ($groupStudent) => ! $filtered->contains('lid_idlid', $groupStudent->idlid))
            ->filter(function ($groupStudent) use ($search, $class): bool {
                $name = mb_strtolower(trim(($groupStudent->voornaam ?? '').' '.($groupStudent->achternaam ?? '')));
                $groupName = (string) $groupStudent->groepnaam;

                return ($class === '' || $class === $groupName)
                    && ($search === '' || str_contains($name, mb_strtolower($search)));
            })
            ->values();
        $classes = $students->pluck('klas')
            ->merge($teacherGroups->pluck('groepnaam'))
            ->filter()
            ->unique()
            ->sort()
            ->values();

        return compact('students', 'filtered', 'selected', 'outcomes', 'search', 'class', 'attention', 'total', 'achieved', 'teacherGroups', 'teacherGroupStudents', 'visibleGroupStudents') + [
            'availableOutcomes' => $selected ? DB::table('leeruitkomsten')->whereNotIn('leeruitkomsten_id', DB::table('student_leeruitkomsten')->where('student_idstudent', $selected->idstudent)->select('leeruitkomsten_leeruitkomsten_id'))->orderBy('code')->get(['leeruitkomsten_id', 'code', 'titel']) : collect(),
            'classes' => $classes,
            'attentionCount' => $students->where('attention', true)->count(),
            'coachLinked' => $coachIds->isNotEmpty(),
            'studentCount' => $studentCount,
            'legacyMode' => false,
            'meetingRequests' => $this->conversationRequests($user),
            'notesCount' => (clone $notes)->count(),
            'notes' => $notes->limit(8)->get(),
            'upcomingCount' => (clone $meetings)->where('g.datum', '>=', now())->count(),
            'upcoming' => (clone $meetings)->where('g.datum', '>=', now())->orderBy('g.datum')->limit(5)->get(),
            'recent' => (clone $meetings)->where('g.datum', '<', now())->orderByDesc('g.datum')->limit(5)->get(),
        ];
    }

    private function legacyNotes(Collection $studentIds): \Illuminate\Database\Query\Builder
    {
        if (! Schema::hasColumn('begeleidingsnotitie', 'lid_idlid')) {
            return DB::table('begeleidingsnotitie as n')->whereRaw('1 = 0');
        }

        return DB::table('begeleidingsnotitie as n')
            ->join('lid as l', 'l.idlid', '=', 'n.lid_idlid')
            ->whereIn('n.lid_idlid', $studentIds)
            ->select('n.id_notitie', 'n.lid_idlid as idstudent', 'n.inhoud', 'n.datum', 'n.type', 'l.voornaam', 'l.achternaam')
            ->orderByDesc('n.datum');
    }

    private function legacyMeetings(User $user, Collection $studentIds, bool $includePendingRequests = false): \Illuminate\Database\Query\Builder
    {
        if (! Schema::hasTable('gesprekken_has_lid')
            || ! Schema::hasColumn('gesprekken_has_lid', 'gesprekken_idgesprek')
            || ! Schema::hasColumn('gesprekken_has_lid', 'lid_idlid')) {
            return DB::table('gesprekken as g')->whereRaw('1 = 0');
        }

        $meetings = DB::table('gesprekken as g')
            ->join('gesprekken_has_lid as student_link', 'student_link.gesprekken_idgesprek', '=', 'g.idgesprek')
            ->join('lid as l', 'l.idlid', '=', 'student_link.lid_idlid')
            ->whereIn('student_link.lid_idlid', $studentIds)
            ->whereExists(fn ($query) => $query->selectRaw('1')
                ->from('gesprekken_has_lid as teacher_link')
                ->whereColumn('teacher_link.gesprekken_idgesprek', 'g.idgesprek')
                ->where('teacher_link.lid_idlid', $user->getKey()))
            ->select('g.idgesprek', 'student_link.lid_idlid as student_idstudent', 'g.datum', 'g.inhoud', 'g.afspraken', 'l.voornaam', 'l.achternaam')
            ->selectRaw(
                'CASE WHEN g.onderwerp = ? THEN ? ELSE g.onderwerp END AS onderwerp',
                [self::MEETING_REQUEST_TOPIC, 'Gespreksaanvraag'],
            );

        if (! $includePendingRequests) {
            $meetings->where('g.onderwerp', '<>', self::MEETING_REQUEST_TOPIC);
        }

        return $meetings->distinct();
    }
}
