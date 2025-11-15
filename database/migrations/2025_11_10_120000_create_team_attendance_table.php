<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('team_attendance', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('team_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('class_id')->nullable();
            $table->unsignedBigInteger('section_id')->nullable();
            $table->date('date');
            $table->string('status', 16); // present | absent | late
            $table->string('remarks', 255)->nullable();
            $table->unsignedBigInteger('recorded_by')->nullable();
            $table->timestamps();

            $table->index(['school_id','team_id','date']);
            $table->unique(['team_id','student_id','date'], 'uniq_team_student_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_attendance');
    }
};
