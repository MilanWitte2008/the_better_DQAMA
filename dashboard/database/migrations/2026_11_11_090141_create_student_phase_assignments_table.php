<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_phase_assignments', function (Blueprint $table) {
            $table->unsignedInteger('member_id');
            $table->unsignedInteger('phase_id');
            $table->primary(['member_id', 'phase_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_phase_assignments');
    }
};
