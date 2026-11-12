<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('opdracht_resultaten')) {
            Schema::create('opdracht_resultaten', function (Blueprint $table): void {
                $table->id('idopdracht_resultaat');
                $table->unsignedBigInteger('lid_idlid')->index();
                $table->string('opdracht', 150);
                $table->decimal('cijfer', 3, 1);
                $table->date('datum');
                $table->text('opmerking')->nullable();
                $table->unsignedBigInteger('aangemaakt_door')->nullable();
                $table->timestamps();
                $table->index(['lid_idlid', 'datum']);
            });
        }

    }

    public function down(): void
    {
        Schema::dropIfExists('opdracht_resultaten');

    }
};
