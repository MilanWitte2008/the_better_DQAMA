<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('lid') || ! Schema::hasTable('rol')) {
            return;
        }

        Schema::create('lid_rol', function (Blueprint $table) {
            $table->unsignedInteger('lid_idlid');
            $table->unsignedInteger('rol_idrol');
            $table->primary(['lid_idlid', 'rol_idrol']);
            $table->index('rol_idrol');
        });

        if (Schema::hasColumn('lid', 'rol_idrol')) {
            DB::table('lid')->whereNotNull('rol_idrol')->orderBy('idlid')->chunk(500, function ($members): void {
                DB::table('lid_rol')->insertOrIgnore($members->map(fn ($member): array => [
                    'lid_idlid' => $member->idlid,
                    'rol_idrol' => $member->rol_idrol,
                ])->all());
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lid_rol');
    }
};
