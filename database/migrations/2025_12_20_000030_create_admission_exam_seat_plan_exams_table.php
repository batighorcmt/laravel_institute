<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('admission_exam_seat_plan_exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seat_plan_id')->constrained('admission_exam_seat_plans')->cascadeOnDelete();
            $table->foreignId('exam_id')->constrained('admission_exams')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['seat_plan_id','exam_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_exam_seat_plan_exams');
    }
};
