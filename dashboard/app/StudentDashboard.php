<?php

namespace App;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StudentDashboard
{
    public function coachesFor(User $student): Collection
    {
        if (! Schema::hasTable('lid')
            || ! Schema::hasColumn('lid', 'idlid')
            || ! Schema::hasColumn('lid', 'rol_idrol')
            || ! Schema::hasColumn('lid', 'voornaam')
            || ! Schema::hasColumn('lid', 'achternaam')
            || ! Schema::hasColumn('lid', 'status')
            || ! Schema::hasTable('rol')
            || ! Schema::hasColumn('rol', 'idrol')
            || ! Schema::hasColumn('rol', 'naam')) {
            return collect();
        }

        $coaches = collect();
        if (Schema::hasTable('student')
            && Schema::hasColumn('student', 'lid_idlid')
            && Schema::hasColumn('student', 'idstudent')) {
            $studentId = DB::table('student')->where('lid_idlid', $student->getKey())->value('idstudent');
        } else {
            $studentId = null;
        }

        if ($studentId
            && Schema::hasTable('student_coach')
            && Schema::hasColumn('student_coach', 'student_idstudent')
            && Schema::hasColumn('student_coach', 'coach_idcoach')
            && Schema::hasColumn('student_coach', 'status')
            && Schema::hasTable('coach')
            && Schema::hasColumn('coach', 'idcoach')
            && Schema::hasColumn('coach', 'lid_idlid')) {
            $assignments = DB::table('student_coach as assignment')
                ->join('coach', 'coach.idcoach', '=', 'assignment.coach_idcoach')
                ->join('lid as coach_member', 'coach_member.idlid', '=', 'coach.lid_idlid')
                ->join('rol', 'rol.idrol', '=', 'coach_member.rol_idrol')
                ->where('assignment.student_idstudent', $studentId)
                ->whereRaw('LOWER(assignment.status) = ?', ['actief'])
                ->whereRaw('LOWER(rol.naam) IN (?, ?)', ['coach', 'docent'])
                ->whereRaw('LOWER(coach_member.status) = ?', ['actief']);

            if (Schema::hasColumn('student_coach', 'startdatum')) {
                $assignments->where(fn ($query) => $query->whereNull('assignment.startdatum')->orWhereDate('assignment.startdatum', '<=', today()));
            }
            if (Schema::hasColumn('student_coach', 'einddatum')) {
                $assignments->where(fn ($query) => $query->whereNull('assignment.einddatum')->orWhereDate('assignment.einddatum', '>=', today()));
            }

            $coaches = $assignments
                ->select('coach_member.idlid as docent_id', 'coach_member.voornaam', 'coach_member.achternaam')
                ->distinct()
                ->orderBy('coach_member.achternaam')
                ->orderBy('coach_member.voornaam')
                ->get();
        }

        if (Schema::hasTable('groep_has_lid')
            && Schema::hasColumn('groep_has_lid', 'idgroep')
            && Schema::hasColumn('groep_has_lid', 'idlid')) {
            $groupCoaches = DB::table('groep_has_lid as student_membership')
                ->join('groep_has_lid as docent_membership', 'docent_membership.idgroep', '=', 'student_membership.idgroep')
                ->join('lid as docent', 'docent.idlid', '=', 'docent_membership.idlid')
                ->join('rol', 'rol.idrol', '=', 'docent.rol_idrol')
                ->where('student_membership.idlid', $student->getKey())
                ->whereRaw('LOWER(rol.naam) IN (?, ?)', ['coach', 'docent'])
                ->whereRaw('LOWER(docent.status) = ?', ['actief']);

            $coaches = $coaches->concat(
                $groupCoaches
                    ->select('docent.idlid as docent_id', 'docent.voornaam', 'docent.achternaam')
                    ->distinct()
                    ->get(),
            )->unique('docent_id')
                ->sortBy(fn ($coach) => mb_strtolower($coach->achternaam.' '.$coach->voornaam))
                ->values();
        }

        return $coaches;
    }
}
