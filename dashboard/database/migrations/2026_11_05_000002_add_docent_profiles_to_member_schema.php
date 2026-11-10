<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('lid') || ! Schema::hasTable('rol')) {
            return;
        }

        if (! Schema::hasTable('coach')) {
            Schema::create('coach', function (Blueprint $table): void {
                $table->increments('idcoach');
                $table->integer('lid_idlid')->unique();
            });
        }

        if (! Schema::hasTable('student')) {
            Schema::create('student', function (Blueprint $table): void {
                $table->increments('idstudent');
                $table->integer('lid_idlid')->unique();
                $table->string('studentnummer', 20)->nullable()->unique();
                $table->string('klas', 20)->nullable();
                $table->string('opleiding', 50)->nullable();
                $table->date('startdatum')->nullable();
            });
        }

        if (! Schema::hasTable('student_coach')) {
            Schema::create('student_coach', function (Blueprint $table): void {
                $table->integer('student_idstudent');
                $table->integer('coach_idcoach');
                $table->string('status', 50)->default('actief');
                $table->date('startdatum')->nullable();
                $table->date('einddatum')->nullable();
                $table->primary(['student_idstudent', 'coach_idcoach']);
            });
        }

        if (! Schema::hasTable('student_leeruitkomsten')) {
            Schema::create('student_leeruitkomsten', function (Blueprint $table): void {
                $table->increments('id_leeruitkomsten');
                $table->text('opmerking')->nullable();
                $table->integer('student_idstudent');
                $table->integer('leeruitkomsten_leeruitkomsten_id');
                $table->string('status', 50)->default('Te doen');
                $table->integer('vooruitgang')->nullable();
                $table->dateTime('laatste_update')->nullable();
                $table->unique(['student_idstudent', 'leeruitkomsten_leeruitkomsten_id'], 'student_outcome_unique');
            });
        }

        if (Schema::hasTable('begeleidingsnotitie')) {
            Schema::table('begeleidingsnotitie', function (Blueprint $table): void {
                if (! Schema::hasColumn('begeleidingsnotitie', 'idstudent')) {
                    $table->integer('idstudent')->nullable();
                }
                if (! Schema::hasColumn('begeleidingsnotitie', 'idcoach')) {
                    $table->integer('idcoach')->nullable();
                }
            });
        }

        if (Schema::hasTable('gesprekken') && ! Schema::hasColumn('gesprekken', 'student_idstudent')) {
            Schema::table('gesprekken', function (Blueprint $table): void {
                $table->integer('student_idstudent')->nullable();
            });
        }

        $coachMembers = DB::table('lid as member')
            ->join('rol', 'rol.idrol', '=', 'member.rol_idrol')
            ->whereRaw('LOWER(rol.naam) IN (?, ?)', ['coach', 'docent'])
            ->pluck('member.idlid');

        foreach ($coachMembers as $memberId) {
            DB::table('coach')->insertOrIgnore(['lid_idlid' => $memberId]);
        }

        $studentMembers = DB::table('lid as member')
            ->join('rol', 'rol.idrol', '=', 'member.rol_idrol')
            ->whereRaw('LOWER(rol.naam) = ?', ['student'])
            ->get(['member.idlid', 'member.aangemaakt_datum']);

        foreach ($studentMembers as $member) {
            DB::table('student')->insertOrIgnore([
                'lid_idlid' => $member->idlid,
                'startdatum' => $member->aangemaakt_datum,
            ]);
        }

        if (Schema::hasTable('leeruitkomsten_has_lid') && Schema::hasTable('leeruitkomsten')) {
            $assignments = DB::table('leeruitkomsten_has_lid as assignment')
                ->join('student', 'student.lid_idlid', '=', 'assignment.lid_idlid')
                ->whereExists(function ($query): void {
                    $query->selectRaw('1')
                        ->from('leeruitkomsten')
                        ->whereColumn('leeruitkomsten.leeruitkomsten_id', 'assignment.leeruitkomsten_id');
                })
                ->get([
                    'student.idstudent as student_idstudent',
                    'assignment.leeruitkomsten_id as leeruitkomsten_leeruitkomsten_id',
                    'assignment.status',
                ]);

            foreach ($assignments as $assignment) {
                DB::table('student_leeruitkomsten')->insertOrIgnore([
                    'student_idstudent' => $assignment->student_idstudent,
                    'leeruitkomsten_leeruitkomsten_id' => $assignment->leeruitkomsten_leeruitkomsten_id,
                    'status' => $assignment->status,
                ]);
            }
        }

        if (Schema::hasTable('groep_has_lid')) {
            $groupAssignments = DB::table('groep_has_lid as student_membership')
                ->join('groep_has_lid as coach_membership', 'coach_membership.idgroep', '=', 'student_membership.idgroep')
                ->join('lid as student_member', 'student_member.idlid', '=', 'student_membership.idlid')
                ->join('rol as student_role', 'student_role.idrol', '=', 'student_member.rol_idrol')
                ->join('student', 'student.lid_idlid', '=', 'student_member.idlid')
                ->join('lid as coach_member', 'coach_member.idlid', '=', 'coach_membership.idlid')
                ->join('rol as coach_role', 'coach_role.idrol', '=', 'coach_member.rol_idrol')
                ->join('coach', 'coach.lid_idlid', '=', 'coach_member.idlid')
                ->where('student_member.status', 'actief')
                ->where('coach_member.status', 'actief')
                ->whereRaw('LOWER(student_role.naam) = ?', ['student'])
                ->whereRaw('LOWER(coach_role.naam) IN (?, ?)', ['docent', 'coach'])
                ->select('student.idstudent as student_idstudent', 'coach.idcoach as coach_idcoach')
                ->distinct()
                ->get()
                ->map(fn ($assignment) => [
                    'student_idstudent' => $assignment->student_idstudent,
                    'coach_idcoach' => $assignment->coach_idcoach,
                    'status' => 'actief',
                    'startdatum' => today()->toDateString(),
                    'einddatum' => null,
                ])
                ->all();

            foreach (array_chunk($groupAssignments, 500) as $chunk) {
                DB::table('student_coach')->upsert(
                    $chunk,
                    ['student_idstudent', 'coach_idcoach'],
                    ['status', 'einddatum'],
                );
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('student_leeruitkomsten');
        Schema::dropIfExists('student_coach');
        Schema::dropIfExists('student');
        Schema::dropIfExists('coach');

        if (Schema::hasTable('begeleidingsnotitie')) {
            Schema::table('begeleidingsnotitie', function (Blueprint $table): void {
                $columns = array_filter(['idstudent', 'idcoach'], fn (string $column): bool => Schema::hasColumn('begeleidingsnotitie', $column));
                if ($columns !== []) {
                    $table->dropColumn($columns);
                }
            });
        }

        if (Schema::hasTable('gesprekken') && Schema::hasColumn('gesprekken', 'student_idstudent')) {
            Schema::table('gesprekken', function (Blueprint $table): void {
                $table->dropColumn('student_idstudent');
            });
        }
    }
};
