<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('rol') || ! Schema::hasColumn('rol', 'naam')) {
            return;
        }

        $docentId = DB::table('rol')->whereRaw('LOWER(naam) = ?', ['docent'])->orderBy('idrol')->value('idrol');
        $coachIds = DB::table('rol')->whereRaw('LOWER(naam) = ?', ['coach'])->orderBy('idrol')->pluck('idrol');
        if ($coachIds->isEmpty()) {
            return;
        }

        if ($docentId === null) {
            $docentId = $coachIds->shift();
            DB::table('rol')->where('idrol', $docentId)->update(['naam' => 'Docent']);
        }

        foreach ($coachIds as $coachId) {
            if (Schema::hasTable('lid') && Schema::hasColumn('lid', 'rol_idrol')) {
                DB::table('lid')->where('rol_idrol', $coachId)->update(['rol_idrol' => $docentId]);
            }

            if (Schema::hasTable('lid_rol')) {
                DB::table('lid_rol')->where('rol_idrol', $coachId)
                    ->orderBy('lid_idlid')->chunk(500, function ($assignments) use ($docentId): void {
                        DB::table('lid_rol')->insertOrIgnore($assignments->map(fn ($assignment): array => [
                            'lid_idlid' => $assignment->lid_idlid,
                            'rol_idrol' => $docentId,
                        ])->all());
                    });
                DB::table('lid_rol')->where('rol_idrol', $coachId)->delete();
            }

            DB::table('rol')->where('idrol', $coachId)->delete();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // The removed Coach role cannot be reconstructed without guessing the old assignments.
    }
};
