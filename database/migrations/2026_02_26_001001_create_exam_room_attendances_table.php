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
        Schema::create('exam_room_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->date('duty_date');
            $table->foreignId('plan_id')->constrained('seat_plans')->onDelete('cascade');
            $table->foreignId('room_id')->constrained('seat_plan_rooms')->onDelete('cascade');
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['present', 'absent']);
            $table->timestamps();

            $table->unique(['duty_date', 'plan_id', 'room_id', 'student_id'], 'unique_exam_room_attendance');
            $table->index(['duty_date', 'plan_id', 'room_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_room_attendances');
    }
};
