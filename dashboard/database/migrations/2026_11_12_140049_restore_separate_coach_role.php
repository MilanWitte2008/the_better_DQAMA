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

        if (! DB::table('rol')->whereRaw('LOWER(naam) = ?', ['coach'])->exists()) {
            $role = ['naam' => 'Coach'];
            if (Schema::hasColumn('rol', 'beschrijving')) {
                $role['beschrijving'] = 'Begeleidt studenten en voert coachgesprekken.';
            }
            DB::table('rol')->insert($role);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Assignments may exist after this role is used, so rolling it back would orphan users.
    }
};
