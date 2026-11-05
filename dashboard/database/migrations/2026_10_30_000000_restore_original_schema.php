<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('doelen', 'fase_idfase')) {
            Schema::table('doelen', function (Blueprint $table) {
                $table->dropForeign(['fase_idfase']);
                $table->dropColumn('fase_idfase');
            });
        }

    }

    public function down(): void
    {
        if (! Schema::hasColumn('doelen', 'fase_idfase')) {
            Schema::table('doelen', function (Blueprint $table) {
                $table->integer('fase_idfase')->nullable()->after('lid_idlid');
                $table->foreign('fase_idfase')
                    ->references('idfase')
                    ->on('fase')
                    ->nullOnDelete();
            });
        }
    }
};
