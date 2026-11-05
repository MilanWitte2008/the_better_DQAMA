<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        $groups = DB::table('groep as team_group')
            ->join('groep_has_lid as membership', 'membership.idgroep', '=', 'team_group.idgroep')
            ->where('membership.idlid', $user->getKey())
            ->select('team_group.idgroep', 'team_group.groepnaam', 'team_group.status')
            ->orderBy('team_group.groepnaam')
            ->get();

        $selectedGroup = $groups->firstWhere('idgroep', $request->query('groep'));
        if ($request->filled('groep') && ! $selectedGroup) {
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

            $progressByStudent = DB::table('leeruitkomsten_has_lid')
                ->select('lid_idlid')
                ->selectRaw('COUNT(*) AS total_outcomes')
                ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS completed_outcomes', ['Behaald'])
                ->groupBy('lid_idlid');

            $reflectionUpdates = DB::table('reflectie')
                ->select('lid_idlid')
                ->selectRaw('MAX(datum) AS last_reflection')
                ->groupBy('lid_idlid');

            $hourUpdates = DB::table('urenregistratie')
                ->select('lid_id AS lid_idlid')
                ->selectRaw('MAX(datum) AS last_hour_entry')
                ->groupBy('lid_id');

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
                ->selectRaw("CASE WHEN reflection_updates.last_reflection IS NULL THEN hour_updates.last_hour_entry WHEN hour_updates.last_hour_entry IS NULL THEN reflection_updates.last_reflection ELSE GREATEST(reflection_updates.last_reflection, hour_updates.last_hour_entry) END AS laatste_update")
                ->orderBy('member.achternaam')
                ->orderBy('member.voornaam')
                ->get();

            $groupOutcomeAssignments = DB::table('groep_has_lid as membership')
                ->join('lid as member', 'member.idlid', '=', 'membership.idlid')
                ->join('rol', 'rol.idrol', '=', 'member.rol_idrol')
                ->join('leeruitkomsten_has_lid as assignment', 'assignment.lid_idlid', '=', 'member.idlid')
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

        $inserted = DB::table('groep_has_lid')->insertOrIgnore([
            'idgroep' => $group,
            'idlid' => $data['lid_idlid'],
        ]);

        $message = $inserted > 0
            ? 'Het lid is aan de groep toegevoegd.'
            : 'Dit lid staat al in deze groep.';

        return redirect()->route('dashboard', ['groep' => $group])->with('success', $message);
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
