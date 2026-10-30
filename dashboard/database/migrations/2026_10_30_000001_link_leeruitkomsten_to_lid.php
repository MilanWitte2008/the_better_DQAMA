<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('leeruitkomsten_has_lid')) {
            Schema::create('leeruitkomsten_has_lid', function (Blueprint $table) {
                $table->integer('leeruitkomsten_id');
                $table->integer('lid_idlid');
                $table->enum('status', ['Te doen', 'Bezig', 'Behaald'])->default('Te doen');
                $table->primary(['leeruitkomsten_id', 'lid_idlid']);
                $table->foreign('leeruitkomsten_id', 'fk_leeruitkomsten_has_lid_outcome')
                    ->references('leeruitkomsten_id')->on('leeruitkomsten')->cascadeOnDelete();
                $table->foreign('lid_idlid', 'fk_leeruitkomsten_has_lid_member')
                    ->references('idlid')->on('lid')->cascadeOnDelete();
            });
        }

        if (Schema::hasTable('fase_has_lid') && Schema::hasTable('leeruitkomsten')) {
            $phaseColumn = Schema::hasColumn('leeruitkomsten', 'fase_idfase1') ? 'fase_idfase1' : 'fase_idfase';

            $assignments = DB::table('fase_has_lid as assignment')
                ->join('leeruitkomsten as outcome', "outcome.$phaseColumn", '=', 'assignment.fase_idfase')
                ->select('outcome.leeruitkomsten_id', 'assignment.lid_idlid')
                ->distinct()
                ->get();

            foreach ($assignments as $assignment) {
                DB::table('leeruitkomsten_has_lid')->insertOrIgnore([
                    'leeruitkomsten_id' => $assignment->leeruitkomsten_id,
                    'lid_idlid' => $assignment->lid_idlid,
                    'status' => 'Te doen',
                ]);
            }
        }

        // Bewaar de fase en het lid bij bestaande reflecties voordat de oude koppeltabel verdwijnt.
        if (Schema::hasTable('reflectie') && Schema::hasColumn('reflectie', 'fase_has_lid_fase_idfase')) {
            Schema::table('reflectie', function (Blueprint $table) {
                $table->integer('fase_idfase')->nullable();
                $table->integer('lid_idlid')->nullable();
            });

            DB::statement('UPDATE reflectie AS reflection LEFT JOIN fase ON fase.idfase = reflection.fase_has_lid_fase_idfase LEFT JOIN lid ON lid.idlid = reflection.fase_has_lid_lid_idlid SET reflection.fase_idfase = fase.idfase, reflection.lid_idlid = lid.idlid');

            $foreignKey = DB::selectOne("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'reflectie' AND COLUMN_NAME = 'fase_has_lid_fase_idfase' AND REFERENCED_TABLE_NAME = 'fase_has_lid'");
            $index = DB::selectOne("SELECT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'reflectie' AND COLUMN_NAME = 'fase_has_lid_fase_idfase' AND SEQ_IN_INDEX = 1");

            if ($foreignKey) {
                DB::statement('ALTER TABLE reflectie DROP FOREIGN KEY `' . $foreignKey->CONSTRAINT_NAME . '`');
            }

            if ($index) {
                DB::statement('ALTER TABLE reflectie DROP INDEX `' . $index->INDEX_NAME . '`');
            }

            Schema::table('reflectie', function (Blueprint $table) {
                $table->dropColumn(['fase_has_lid_fase_idfase', 'fase_has_lid_lid_idlid']);
                $table->index('lid_idlid', 'reflectie_lid_idlid_idx');
                $table->foreign('fase_idfase')->references('idfase')->on('fase')->nullOnDelete();
                $table->foreign('lid_idlid')->references('idlid')->on('lid')->nullOnDelete();
            });
        }

        if (Schema::hasTable('fase_has_lid')) {
            Schema::drop('fase_has_lid');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('fase_has_lid')) {
            Schema::create('fase_has_lid', function (Blueprint $table) {
                $table->integer('fase_idfase');
                $table->integer('lid_idlid');
                $table->primary(['fase_idfase', 'lid_idlid']);
                $table->foreign('fase_idfase')->references('idfase')->on('fase')->cascadeOnDelete();
                $table->foreign('lid_idlid')->references('idlid')->on('lid')->cascadeOnDelete();
            });

            if (Schema::hasTable('leeruitkomsten_has_lid')) {
                $phaseColumn = Schema::hasColumn('leeruitkomsten', 'fase_idfase1') ? 'fase_idfase1' : 'fase_idfase';
                $assignments = DB::table('leeruitkomsten_has_lid as assignment')
                    ->join('leeruitkomsten as outcome', 'outcome.leeruitkomsten_id', '=', 'assignment.leeruitkomsten_id')
                    ->select('outcome.' . $phaseColumn . ' as fase_idfase', 'assignment.lid_idlid')
                    ->distinct()
                    ->get();

                foreach ($assignments as $assignment) {
                    DB::table('fase_has_lid')->insertOrIgnore((array) $assignment);
                }
            }
        }

        if (Schema::hasTable('reflectie') && Schema::hasColumn('reflectie', 'fase_idfase')) {
            Schema::table('reflectie', function (Blueprint $table) {
                $table->dropForeign(['fase_idfase']);
                $table->dropForeign(['lid_idlid']);
                $table->dropIndex('reflectie_lid_idlid_idx');
                $table->integer('fase_has_lid_fase_idfase')->nullable();
                $table->integer('fase_has_lid_lid_idlid')->nullable();
            });

            DB::statement('UPDATE reflectie SET fase_has_lid_fase_idfase = fase_idfase, fase_has_lid_lid_idlid = lid_idlid');

            Schema::table('reflectie', function (Blueprint $table) {
                $table->dropColumn(['fase_idfase', 'lid_idlid']);
                $table->index(['fase_has_lid_fase_idfase', 'fase_has_lid_lid_idlid'], 'fk_reflectie_fase_has_lid1_idx');
                $table->foreign(['fase_has_lid_fase_idfase', 'fase_has_lid_lid_idlid'], 'fk_reflectie_fase_has_lid1')
                    ->references(['fase_idfase', 'lid_idlid'])->on('fase_has_lid');
            });
        }

        Schema::dropIfExists('leeruitkomsten_has_lid');
    }
};
