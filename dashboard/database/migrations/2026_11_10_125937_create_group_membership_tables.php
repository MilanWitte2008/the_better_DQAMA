<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('groep')) {
            Schema::create('groep', function (Blueprint $table): void {
                $table->increments('idgroep');
                $table->string('groepnaam', 45);
                $table->string('status', 50)->default('actief');
            });
        }
        if (! Schema::hasTable('groep_has_lid')) {
            Schema::create('groep_has_lid', function (Blueprint $table): void {
                $table->unsignedInteger('idgroep');
                $table->integer('idlid');
                $table->primary(['idgroep', 'idlid']);
                $table->index('idlid');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Groepen kunnen al voor deze compatibiliteitsmigratie bestaan; behoud de gegevens.
    }
};
