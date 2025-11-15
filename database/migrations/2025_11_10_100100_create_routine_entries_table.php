<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('routine_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('section_id');
            $table->string('day_of_week', 16); // monday, tuesday, etc.
            $table->unsignedInteger('period_number');
            // allow multiple subjects per period cell
            $table->unsignedBigInteger('subject_id');
            $table->unsignedBigInteger('teacher_id');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('room', 64)->nullable();
            $table->string('remarks', 191)->nullable();
            $table->timestamps();

            $table->index(['school_id','class_id','section_id']);
            $table->index(['class_id','section_id','day_of_week']);
            $table->index(['teacher_id']);
            $table->index(['subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('routine_entries');
    }
};
