<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('lid') && Schema::hasColumn('lid', 'school_idschool')) {
            if (DB::getDriverName() === 'mysql') {
                $constraints = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'lid' AND COLUMN_NAME = 'school_idschool' AND REFERENCED_TABLE_NAME IS NOT NULL");
                foreach ($constraints as $constraint) {
                    $name = str_replace('`', '``', $constraint->CONSTRAINT_NAME);
                    DB::statement("ALTER TABLE `lid` DROP FOREIGN KEY `{$name}`");
                }

                $indexes = DB::select("SELECT DISTINCT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'lid' AND COLUMN_NAME = 'school_idschool' AND INDEX_NAME <> 'PRIMARY'");
                foreach ($indexes as $index) {
                    $name = str_replace('`', '``', $index->INDEX_NAME);
                    DB::statement("ALTER TABLE `lid` DROP INDEX `{$name}`");
                }
            }

            Schema::table('lid', fn (Blueprint $table) => $table->dropColumn('school_idschool'));
        }

        Schema::dropIfExists('school');
    }

    public function down(): void
    {
        if (! Schema::hasTable('school')) {
            Schema::create('school', function (Blueprint $table) {
                $table->integer('idschool')->autoIncrement();
                $table->string('naam', 45)->nullable();
                $table->string('adres', 45)->nullable();
                $table->string('email', 45)->nullable();
            });
        }

        if (Schema::hasTable('lid') && ! Schema::hasColumn('lid', 'school_idschool')) {
            Schema::table('lid', fn (Blueprint $table) => $table->integer('school_idschool')->nullable());
        }
    }
};
