<?php

namespace App\Http\Controllers;

use App\Models\User;
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
        $outcomeProgress = collect();
        $teamProgress = 0;

        if ($selectedGroup) {
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

            $outcomeProgress = DB::table('groep_has_lid as membership')
                ->join('lid as member', 'member.idlid', '=', 'membership.idlid')
                ->join('rol', 'rol.idrol', '=', 'member.rol_idrol')
                ->join('leeruitkomsten_has_lid as assignment', 'assignment.lid_idlid', '=', 'member.idlid')
                ->join('leeruitkomsten', 'leeruitkomsten.leeruitkomsten_id', '=', 'assignment.leeruitkomsten_id')
                ->where('membership.idgroep', $selectedGroup->idgroep)
                ->whereRaw('LOWER(rol.naam) = ?', ['student'])
                ->select('leeruitkomsten.titel as label')
                ->selectRaw('COUNT(*) AS total')
                ->selectRaw('SUM(CASE WHEN assignment.status = ? THEN 1 ELSE 0 END) AS completed', ['Behaald'])
                ->groupBy('leeruitkomsten.titel')
                ->orderBy('leeruitkomsten.titel')
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
            'outcomeProgress' => $outcomeProgress,
            'teamProgress' => $teamProgress,
            'attentionCount' => $outcomeProgress->where('progress', '<', 60)->count(),
        ]);
    }
}
