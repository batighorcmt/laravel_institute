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
        Schema::create('exam_room_invigilations', function (Blueprint $table) {
            $table->id();
            $table->date('duty_date');
            $table->foreignId('seat_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('seat_plan_room_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete(); // teacher users
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['duty_date', 'seat_plan_id', 'seat_plan_room_id'], 'exam_room_invig_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_room_invigilations');
    }
};
