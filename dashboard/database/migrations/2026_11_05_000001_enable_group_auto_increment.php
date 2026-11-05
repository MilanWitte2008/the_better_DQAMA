<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('groep') || DB::getDriverName() !== 'mysql') {
            return;
        }

        $column = DB::selectOne(
            'SELECT EXTRA AS extra FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            ['groep', 'idgroep'],
        );

        if ($column && ! str_contains(strtolower((string) $column->extra), 'auto_increment')) {
            DB::statement('ALTER TABLE `groep` MODIFY `idgroep` INT(11) NOT NULL AUTO_INCREMENT');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $column = DB::selectOne(
            'SELECT EXTRA AS extra FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            ['groep', 'idgroep'],
        );

        if ($column
            && str_contains(strtolower((string) $column->extra), 'auto_increment')
            && ! DB::table('groep')->exists()) {
            DB::statement('ALTER TABLE `groep` MODIFY `idgroep` INT(11) NOT NULL');
        }
    }
};
