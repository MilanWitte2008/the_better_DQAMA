<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('leeruitkomsten_has_lid')
            || ! Schema::hasTable('leeruitkomsten')
            || ! Schema::hasTable('lid')
            || ! Schema::hasTable('rol')) {
            return;
        }

        $assignments = DB::table('leeruitkomsten as outcome')
            ->crossJoin('lid as member')
            ->join('rol', 'rol.idrol', '=', 'member.rol_idrol')
            ->whereRaw('LOWER(rol.naam) = ?', ['student'])
            ->select('outcome.leeruitkomsten_id')
            ->selectRaw('member.idlid AS lid_idlid')
            ->selectRaw('? AS status', ['Te doen'])
            ->get()
            ->map(fn ($assignment) => (array) $assignment)
            ->all();

        foreach (array_chunk($assignments, 500) as $chunk) {
            DB::table('leeruitkomsten_has_lid')->insertOrIgnore($chunk);
        }
    }

    public function down(): void
    {
        // Bestaande koppelingen kunnen ook handmatig zijn toegevoegd; die blijven behouden.
    }
};
