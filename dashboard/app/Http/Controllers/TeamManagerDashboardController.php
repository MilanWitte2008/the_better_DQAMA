<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TeamManagerDashboardController extends Controller
{
    public function index(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless(
            $user->dashboardRoleKey() === 'teamleider'
                && strtolower((string) $user->status) === 'actief',
            403,
        );

        $groupsAvailable = Schema::hasTable('groep') && Schema::hasTable('groep_has_lid');
        $groups = $groupsAvailable ? DB::table('groep as team_group')
            ->join('groep_has_lid as membership', 'membership.idgroep', '=', 'team_group.idgroep')
            ->where('membership.idlid', $user->getKey())
            ->select('team_group.idgroep', 'team_group.groepnaam', 'team_group.status')
            ->orderBy('team_group.groepnaam')
            ->get() : collect();

        $this->syncCoachAssignmentsForGroups($groups->pluck('idgroep')->map(fn ($groupId) => (int) $groupId)->all());

        $selectedGroup = $groups->firstWhere('idgroep', $request->query('groep'));
        if ($groupsAvailable && $request->filled('groep') && ! $selectedGroup) {
            abort(404);
        }
        $selectedGroup ??= $groups->first();

        $students = collect();
        $availableStudents = collect();
        $availableTeachers = collect();
        $groupTeachers = collect();
        $outcomeProgress = collect();
        $teamProgress = 0;

        if ($selectedGroup) {
            $availableStudents = DB::table('lid as member')
                ->join('rol', 'rol.idrol', '=', 'member.rol_idrol')
                ->whereRaw('LOWER(rol.naam) = ?', ['student'])
                ->where('member.status', 'actief')
                ->whereNotExists(function ($query) use ($selectedGroup) {
                    $query->selectRaw('1')
                        ->from('groep_has_lid as membership')
                        ->whereColumn('membership.idlid', 'member.idlid')
                        ->where('membership.idgroep', $selectedGroup->idgroep);
                })
                ->select('member.idlid', 'member.voornaam', 'member.achternaam', 'member.email')
                ->orderBy('member.achternaam')
                ->orderBy('member.voornaam')
                ->get();

            $availableTeachers = DB::table('lid as member')
                ->join('rol', 'rol.idrol', '=', 'member.rol_idrol')
                ->whereRaw('LOWER(rol.naam) IN (?, ?)', ['docent', 'coach'])
                ->where('member.status', 'actief')
                ->whereNotExists(function ($query) use ($selectedGroup) {
                    $query->selectRaw('1')
                        ->from('groep_has_lid as membership')
                        ->whereColumn('membership.idlid', 'member.idlid')
                        ->where('membership.idgroep', $selectedGroup->idgroep);
                })
                ->select('member.idlid', 'member.voornaam', 'member.achternaam', 'member.email', 'rol.naam as rolnaam')
                ->orderBy('member.achternaam')
                ->orderBy('member.voornaam')
                ->get();

            $groupTeachers = DB::table('groep_has_lid as membership')
                ->join('lid as member', 'member.idlid', '=', 'membership.idlid')
                ->join('rol', 'rol.idrol', '=', 'member.rol_idrol')
                ->where('membership.idgroep', $selectedGroup->idgroep)
                ->whereRaw('LOWER(rol.naam) IN (?, ?)', ['docent', 'coach'])
                ->select('member.idlid', 'rol.naam as rolnaam')
                ->selectRaw("TRIM(CONCAT(COALESCE(member.voornaam, ''), ' ', COALESCE(member.achternaam, ''))) AS naam")
                ->orderBy('member.achternaam')
                ->orderBy('member.voornaam')
                ->get();

            if (Schema::hasTable('leeruitkomsten_has_lid')) {
                $assignments = DB::table('leeruitkomsten_has_lid')->select('lid_idlid', 'leeruitkomsten_id', 'status');
            } else {
                $assignments = DB::table('student_leeruitkomsten as outcome_assignment')
                    ->join('student as profile', 'profile.idstudent', '=', 'outcome_assignment.student_idstudent')
                    ->select('profile.lid_idlid', 'outcome_assignment.leeruitkomsten_leeruitkomsten_id as leeruitkomsten_id', 'outcome_assignment.status');
            }
            $progressByStudent = DB::query()->fromSub(clone $assignments, 'assignment')
                ->select('lid_idlid')
                ->selectRaw('COUNT(*) AS total_outcomes')
                ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS completed_outcomes', ['Behaald'])
                ->groupBy('lid_idlid');

            if (Schema::hasColumn('reflectie', 'lid_idlid')) {
                $reflections = DB::table('reflectie')->select('lid_idlid', 'datum');
            } else {
                $reflections = DB::table('reflectie as reflection')
                    ->join('student_leeruitkomsten as outcome_assignment', 'outcome_assignment.id_leeruitkomsten', '=', 'reflection.student_leeruitkomsten_id_leeruitkomsten')
                    ->join('student as profile', 'profile.idstudent', '=', 'outcome_assignment.student_idstudent')
                    ->select('profile.lid_idlid', 'reflection.datum');
            }
            $reflectionUpdates = DB::query()->fromSub($reflections, 'reflection')
                ->select('lid_idlid')
                ->selectRaw('MAX(datum) AS last_reflection')
                ->groupBy('lid_idlid');

            if (Schema::hasColumn('urenregistratie', 'lid_id')) {
                $hours = DB::table('urenregistratie')->select('lid_id as lid_idlid', 'datum');
            } else {
                $hours = DB::table('urenregistratie as entry')
                    ->join('student as profile', 'profile.idstudent', '=', 'entry.student_id')
                    ->select('profile.lid_idlid', 'entry.datum');
            }
            $hourUpdates = DB::query()->fromSub($hours, 'entry')
                ->select('lid_idlid')
                ->selectRaw('MAX(datum) AS last_hour_entry')
                ->groupBy('lid_idlid');

            $students = DB::table('groep_has_lid as membership')
                ->join('lid as member', 'member.idlid', '=', 'membership.idlid')
                ->join('rol', 'rol.idrol', '=', 'member.rol_idrol')
                ->leftJoinSub($progressByStudent, 'progress', 'progress.lid_idlid', '=', 'member.idlid')
                ->leftJoinSub($reflectionUpdates, 'reflection_updates', 'reflection_updates.lid_idlid', '=', 'member.idlid')
                ->leftJoinSub($hourUpdates, 'hour_updates', 'hour_updates.lid_idlid', '=', 'member.idlid')
                ->where('membership.idgroep', $selectedGroup->idgroep)
                ->whereRaw('LOWER(rol.naam) = ?', ['student'])
                ->select('member.idlid')
                ->selectRaw("TRIM(CONCAT(COALESCE(member.voornaam, ''), ' ', COALESCE(member.achternaam, ''))) AS naam")
                ->selectRaw('COALESCE(progress.total_outcomes, 0) AS total_outcomes')
                ->selectRaw('COALESCE(progress.completed_outcomes, 0) AS completed_outcomes')
                ->selectRaw('CASE WHEN COALESCE(progress.total_outcomes, 0) > 0 THEN ROUND(progress.completed_outcomes * 100 / progress.total_outcomes) ELSE 0 END AS voortgang')
                ->selectRaw('CASE WHEN reflection_updates.last_reflection IS NULL THEN hour_updates.last_hour_entry WHEN hour_updates.last_hour_entry IS NULL THEN reflection_updates.last_reflection ELSE GREATEST(reflection_updates.last_reflection, hour_updates.last_hour_entry) END AS laatste_update')
                ->orderBy('member.achternaam')
                ->orderBy('member.voornaam')
                ->get();

            $groupOutcomeAssignments = DB::table('groep_has_lid as membership')
                ->join('lid as member', 'member.idlid', '=', 'membership.idlid')
                ->join('rol', 'rol.idrol', '=', 'member.rol_idrol')
                ->joinSub(clone $assignments, 'assignment', 'assignment.lid_idlid', '=', 'member.idlid')
                ->where('membership.idgroep', $selectedGroup->idgroep)
                ->whereRaw('LOWER(rol.naam) = ?', ['student'])
                ->select('assignment.leeruitkomsten_id')
                ->selectRaw('COUNT(*) AS total')
                ->selectRaw('SUM(CASE WHEN assignment.status = ? THEN 1 ELSE 0 END) AS completed', ['Behaald'])
                ->groupBy('assignment.leeruitkomsten_id');

            $outcomeProgress = DB::table('leeruitkomsten as outcome')
                ->leftJoinSub($groupOutcomeAssignments, 'progress', 'progress.leeruitkomsten_id', '=', 'outcome.leeruitkomsten_id')
                ->select('outcome.code', 'outcome.titel as label', 'outcome.omschrijving as description')
                ->selectRaw('COALESCE(progress.total, 0) AS total')
                ->selectRaw('COALESCE(progress.completed, 0) AS completed')
                ->orderBy('outcome.titel')
                ->get()
                ->map(function ($outcome) {
                    $outcome->progress = $outcome->total > 0
                        ? (int) round(($outcome->completed / $outcome->total) * 100)
                        : 0;

                    return $outcome;
                });

            $totalOutcomes = $students->sum('total_outcomes');
            $completedOutcomes = $students->sum('completed_outcomes');
            $teamProgress = $totalOutcomes > 0
                ? (int) round(($completedOutcomes / $totalOutcomes) * 100)
                : 0;
        }

        return view('dashboards.teammanager', [
            'user' => $user,
            'roleKey' => 'teamleider',
            'groups' => $groups,
            'groupsAvailable' => $groupsAvailable,
            'selectedGroup' => $selectedGroup,
            'students' => $students,
            'availableStudents' => $availableStudents,
            'availableTeachers' => $availableTeachers,
            'groupMembers' => $students->map(function ($student) {
                $student->rolnaam = 'Student';

                return $student;
            })->concat($groupTeachers)->sortBy(fn ($member) => mb_strtolower($member->naam))->values(),
            'outcomeProgress' => $outcomeProgress,
            'teamProgress' => $teamProgress,
            'attentionCount' => $outcomeProgress->where('progress', '<', 60)->count(),
        ]);
    }

    public function storeGroup(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless(
            $user->dashboardRoleKey() === 'teamleider'
                && strtolower((string) $user->status) === 'actief',
            403,
        );

        $data = $request->validate([
            'groepnaam' => ['required', 'string', 'max:45'],
        ]);
        if (! Schema::hasTable('groep') || ! Schema::hasTable('groep_has_lid')) {
            return back()->withErrors(['groepnaam' => 'Groepsbeheer is nog niet ingericht. Laat de database-migraties uitvoeren.'])->withInput();
        }

        $groupId = DB::transaction(function () use ($data, $user): int {
            $groupId = DB::table('groep')->insertGetId([
                'groepnaam' => $data['groepnaam'],
                'status' => 'actief',
            ], 'idgroep');

            DB::table('groep_has_lid')->insert([
                'idgroep' => $groupId,
                'idlid' => $user->getKey(),
            ]);

            return (int) $groupId;
        });

        return redirect()->route('dashboard', ['groep' => $groupId])
            ->with('success', 'De groep is aangemaakt.');
    }

    public function destroyGroup(Request $request, int $group): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless(
            $user->dashboardRoleKey() === 'teamleider'
                && strtolower((string) $user->status) === 'actief',
            403,
        );

        $isOwnGroup = DB::table('groep_has_lid')
            ->where('idgroep', $group)
            ->where('idlid', $user->getKey())
            ->exists();
        abort_unless($isOwnGroup, 404);

        DB::transaction(function () use ($group): void {
            DB::table('groep_has_lid')->where('idgroep', $group)->delete();
            DB::table('groep')->where('idgroep', $group)->delete();
        });

        return redirect()->route('dashboard')
            ->with('success', 'De groep is verwijderd. De leden en hun gegevens zijn behouden.');
    }

    public function storeGroupMember(Request $request, int $group): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless(
            $user->dashboardRoleKey() === 'teamleider'
                && strtolower((string) $user->status) === 'actief',
            403,
        );

        abort_unless(
            DB::table('groep_has_lid')
                ->where('idgroep', $group)
                ->where('idlid', $user->getKey())
                ->exists(),
            403,
        );

        $data = $request->validate([
            'lid_idlid' => ['required', 'integer'],
        ]);

        $isActiveGroupMember = DB::table('lid as member')
            ->join('rol', 'rol.idrol', '=', 'member.rol_idrol')
            ->where('member.idlid', $data['lid_idlid'])
            ->where('member.status', 'actief')
            ->whereRaw('LOWER(rol.naam) IN (?, ?, ?)', ['student', 'docent', 'coach'])
            ->exists();

        if (! $isActiveGroupMember) {
            return back()->withErrors([
                'lid_idlid' => 'Kies een actief lid met de rol Student, Docent of Coach.',
            ])->withInput();
        }

        $inserted = DB::transaction(function () use ($data, $group): int {
            $inserted = DB::table('groep_has_lid')->insertOrIgnore([
                'idgroep' => $group,
                'idlid' => $data['lid_idlid'],
            ]);

            $this->syncCoachAssignmentsForGroups([$group]);

            return $inserted;
        });

        $message = $inserted > 0
            ? 'Het lid is aan de groep toegevoegd.'
            : 'Dit lid staat al in deze groep.';

        return redirect()->route('dashboard', ['groep' => $group])->with('success', $message);
    }

    /**
     * Keep active coach assignments in sync with students who share the requested groups.
     *
     * @param  array<int, int>  $groupIds
     */
    private function syncCoachAssignmentsForGroups(array $groupIds): void
    {
        if ($groupIds === []
            || ! Schema::hasTable('student_coach')
            || ! Schema::hasTable('student')
            || ! Schema::hasTable('coach')) {
            return;
        }

        $activeStudents = DB::table('groep_has_lid as membership')
            ->join('lid as member', 'member.idlid', '=', 'membership.idlid')
            ->join('rol', 'rol.idrol', '=', 'member.rol_idrol')
            ->whereIn('membership.idgroep', $groupIds)
            ->where('member.status', 'actief')
            ->whereRaw('LOWER(rol.naam) = ?', ['student'])
            ->pluck('member.idlid')
            ->unique()
            ->values();

        foreach ($activeStudents as $memberId) {
            if (! DB::table('student')->where('lid_idlid', $memberId)->exists()) {
                DB::table('student')->insert(['lid_idlid' => $memberId]);
            }
        }

        $activeCoaches = DB::table('groep_has_lid as membership')
            ->join('lid as member', 'member.idlid', '=', 'membership.idlid')
            ->whereIn('membership.idgroep', $groupIds)
            ->where('member.status', 'actief')
            ->select('member.idlid');
        $this->whereMemberHasRole($activeCoaches, 'member', 'coach');
        $activeCoachIds = $activeCoaches
            ->pluck('member.idlid')
            ->unique()
            ->values();

        foreach ($activeCoachIds as $memberId) {
            if (! DB::table('coach')->where('lid_idlid', $memberId)->exists()) {
                DB::table('coach')->insert(['lid_idlid' => $memberId]);
            }
        }

        $assignments = DB::table('groep_has_lid as student_membership')
            ->join('groep_has_lid as coach_membership', 'coach_membership.idgroep', '=', 'student_membership.idgroep')
            ->join('lid as student_member', 'student_member.idlid', '=', 'student_membership.idlid')
            ->join('rol as student_role', 'student_role.idrol', '=', 'student_member.rol_idrol')
            ->join('student', 'student.lid_idlid', '=', 'student_member.idlid')
            ->join('lid as coach_member', 'coach_member.idlid', '=', 'coach_membership.idlid')
            ->join('coach', 'coach.lid_idlid', '=', 'coach_member.idlid')
            ->whereIn('student_membership.idgroep', $groupIds)
            ->where('student_member.status', 'actief')
            ->where('coach_member.status', 'actief')
            ->whereRaw('LOWER(student_role.naam) = ?', ['student'])
            ->select('student.idstudent as student_idstudent', 'coach.idcoach as coach_idcoach')
            ->distinct();
        $this->whereMemberHasRole($assignments, 'coach_member', 'coach');
        $assignments = $assignments->get()
            ->map(fn ($assignment) => [
                'student_idstudent' => $assignment->student_idstudent,
                'coach_idcoach' => $assignment->coach_idcoach,
                'status' => 'actief',
                'startdatum' => today()->toDateString(),
                'einddatum' => null,
            ])
            ->all();

        foreach ($assignments as $assignment) {
            $query = DB::table('student_coach')->where('student_idstudent', $assignment['student_idstudent'])
                ->where('coach_idcoach', $assignment['coach_idcoach']);
            if ($query->exists()) {
                $query->update(['status' => 'actief', 'einddatum' => null]);
            } else {
                DB::table('student_coach')->insert($assignment);
            }
        }
    }

    private function whereMemberHasRole(Builder $query, string $memberAlias, string $roleName): void
    {
        $query->whereExists(function (Builder $roleQuery) use ($memberAlias, $roleName): void {
            $roleQuery->selectRaw('1');
            if (Schema::hasTable('lid_rol')) {
                $roleQuery->from('lid_rol as role_assignment')
                    ->join('rol as assigned_role', 'assigned_role.idrol', '=', 'role_assignment.rol_idrol')
                    ->whereColumn('role_assignment.lid_idlid', $memberAlias.'.idlid');
            } else {
                $roleQuery->from('rol as assigned_role')
                    ->whereColumn('assigned_role.idrol', $memberAlias.'.rol_idrol');
            }
            $roleQuery->whereRaw('LOWER(assigned_role.naam) = ?', [$roleName]);
        });
    }

    public function destroyGroupMember(Request $request, int $group, int $member): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless(
            $user->dashboardRoleKey() === 'teamleider'
                && strtolower((string) $user->status) === 'actief',
            403,
        );

        abort_unless(
            DB::table('groep_has_lid')
                ->where('idgroep', $group)
                ->where('idlid', $user->getKey())
                ->exists(),
            403,
        );

        abort_unless(
            DB::table('groep_has_lid')
                ->where('idgroep', $group)
                ->where('idlid', $member)
                ->exists(),
            404,
        );

        $isRemovableMember = DB::table('lid as member')
            ->join('rol', 'rol.idrol', '=', 'member.rol_idrol')
            ->where('member.idlid', $member)
            ->whereRaw('LOWER(rol.naam) IN (?, ?, ?)', ['student', 'docent', 'coach'])
            ->exists();
        abort_unless($isRemovableMember, 403);

        DB::table('groep_has_lid')
            ->where('idgroep', $group)
            ->where('idlid', $member)
            ->delete();

        return redirect()->route('dashboard', ['groep' => $group])
            ->with('success', 'Het groepslid is uit deze groep verwijderd. Het account en de gegevens zijn behouden.');
    }
}
