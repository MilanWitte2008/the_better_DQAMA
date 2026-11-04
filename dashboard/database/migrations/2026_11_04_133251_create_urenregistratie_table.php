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
        Schema::create('urenregistratie', function (Blueprint $table) {
            $table->id();
            $table->integer('lid_id');
            $table->date('datum');
            $table->decimal('uren', 6, 2);
            $table->enum('status', ['In afwachting', 'Goedgekeurd', 'Afgekeurd'])->default('In afwachting');
            $table->foreign('lid_id')->references('idlid')->on('lid')->cascadeOnDelete();
            $table->index(['lid_id', 'datum']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('urenregistratie');
    }
};
