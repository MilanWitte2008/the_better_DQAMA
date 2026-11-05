<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Restore the school data used by the administration dashboard.
     */
    public function up(): void
    {
        if (! Schema::hasTable('school')) {
            Schema::create('school', function (Blueprint $table): void {
                $table->integer('idschool')->autoIncrement()->primary();
                $table->string('naam', 45)->nullable();
                $table->string('adres', 45)->nullable();
                $table->string('email', 45)->nullable();
            });
        }

        if (Schema::hasTable('lid') && ! Schema::hasColumn('lid', 'school_idschool')) {
            Schema::table('lid', function (Blueprint $table): void {
                $table->integer('school_idschool')->nullable();
            });
        }
    }

    /**
     * Remove the school relationship restored by this migration.
     */
    public function down(): void
    {
        if (Schema::hasTable('lid') && Schema::hasColumn('lid', 'school_idschool')) {
            Schema::table('lid', function (Blueprint $table): void {
                $table->dropColumn('school_idschool');
            });
        }

        Schema::dropIfExists('school');
    }
};
