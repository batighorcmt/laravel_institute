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
        Schema::create('school_attendance_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->unique();
            
            // Student Settings
            $table->time('student_entry_start')->default('07:00:00'); // When to start taking entry
            $table->time('student_entry_end')->default('08:45:00'); // End of on-time entry
            $table->time('student_late_threshold')->default('09:30:00'); // After this, student is absent
            $table->time('student_exit_start')->default('13:00:00'); // When to start taking exit
            $table->time('student_exit_end')->default('15:00:00'); // End of exit
            
            // Teacher Settings
            $table->time('teacher_check_in_start')->default('08:00:00'); 
            $table->time('teacher_check_in_end')->default('09:00:00');
            $table->time('teacher_late_threshold')->default('09:30:00');
            $table->time('teacher_check_out_start')->default('14:00:00');
            $table->time('teacher_check_out_end')->default('17:00:00');
            
            $table->timestamps();
            
            $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_attendance_settings');
    }
};
