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
        if (! Schema::hasTable('reflectie') || Schema::hasColumn('reflectie', 'lid_idlid')) {
            return;
        }

        Schema::table('reflectie', function (Blueprint $table) {
            $table->integer('lid_idlid')->nullable();
        });

        if (Schema::hasTable('student_leeruitkomsten')
            && Schema::hasTable('student')
            && Schema::hasColumn('reflectie', 'student_leeruitkomsten_id_leeruitkomsten')) {
            DB::statement('UPDATE reflectie AS reflection JOIN student_leeruitkomsten AS assignment ON assignment.id_leeruitkomsten = reflection.student_leeruitkomsten_id_leeruitkomsten JOIN student ON student.idstudent = assignment.student_idstudent SET reflection.lid_idlid = student.lid_idlid WHERE reflection.lid_idlid IS NULL AND student.lid_idlid IS NOT NULL');
        }

        Schema::table('reflectie', function (Blueprint $table) {
            $table->index('lid_idlid', 'reflectie_lid_idlid_idx');
            $table->foreign('lid_idlid')->references('idlid')->on('lid')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('reflectie') || ! Schema::hasColumn('reflectie', 'lid_idlid')) {
            return;
        }

        Schema::table('reflectie', function (Blueprint $table) {
            $table->dropForeign(['lid_idlid']);
            $table->dropIndex('reflectie_lid_idlid_idx');
            $table->dropColumn('lid_idlid');
        });
    }
};
