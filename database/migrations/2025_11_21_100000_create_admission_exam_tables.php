<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('admission_exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('name',150);
            $table->enum('type',['subject','overall']);
            $table->unsignedInteger('overall_pass_mark')->nullable(); // required if type=overall
            $table->date('exam_date')->nullable();
            $table->enum('status',['draft','scheduled','completed'])->default('draft');
            $table->timestamps();
        });

        Schema::create('admission_exam_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained('admission_exams')->cascadeOnDelete();
            $table->string('subject_name',150);
            $table->unsignedInteger('full_mark');
            $table->unsignedInteger('pass_mark')->nullable(); // if type=subject; null for overall exam type
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->timestamps();
            $table->unique(['exam_id','subject_name']);
        });

        Schema::create('admission_exam_marks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained('admission_exams')->cascadeOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('admission_exam_subjects')->cascadeOnDelete();
            $table->foreignId('application_id')->constrained('admission_applications')->cascadeOnDelete();
            $table->unsignedInteger('obtained_mark')->nullable();
            $table->timestamps();
            $table->unique(['exam_id','subject_id','application_id'],'uniq_exam_mark_row');
            $table->index(['exam_id','application_id']);
        });

        Schema::create('admission_exam_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained('admission_exams')->cascadeOnDelete();
            $table->foreignId('application_id')->constrained('admission_applications')->cascadeOnDelete();
            $table->unsignedInteger('total_obtained')->nullable();
            $table->boolean('is_pass')->default(false);
            $table->unsignedInteger('failed_subjects_count')->default(0);
            $table->timestamps();
            $table->unique(['exam_id','application_id'],'uniq_exam_result');
            $table->index(['exam_id','is_pass']);
        });

        // Seat plan tables for admission exams
        Schema::create('admission_exam_seat_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained('admission_exams')->cascadeOnDelete();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('name',150);
            $table->string('shift',20)->default('Morning');
            $table->enum('status',['active','inactive','completed'])->default('active');
            $table->timestamps();
        });

        Schema::create('admission_exam_seat_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seat_plan_id')->constrained('admission_exam_seat_plans')->cascadeOnDelete();
            $table->string('room_no',50);
            $table->string('title',255)->nullable();
            $table->tinyInteger('columns_count')->default(3); // 1..3
            $table->integer('col1_benches')->default(0);
            $table->integer('col2_benches')->default(0);
            $table->integer('col3_benches')->default(0);
            $table->timestamps();
            $table->unique(['seat_plan_id','room_no']);
        });

        Schema::create('admission_exam_seat_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seat_plan_id')->constrained('admission_exam_seat_plans')->cascadeOnDelete();
            $table->foreignId('room_id')->constrained('admission_exam_seat_rooms')->cascadeOnDelete();
            $table->foreignId('application_id')->constrained('admission_applications')->cascadeOnDelete();
            $table->integer('col_no');
            $table->integer('bench_no');
            $table->enum('position',['L','R']);
            $table->timestamps();
            $table->unique(['room_id','col_no','bench_no','position'],'uniq_adm_exam_seat');
            $table->unique(['seat_plan_id','application_id'],'uniq_seat_plan_app');
            $table->index(['seat_plan_id','room_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_exam_seat_allocations');
        Schema::dropIfExists('admission_exam_seat_rooms');
        Schema::dropIfExists('admission_exam_seat_plans');
        Schema::dropIfExists('admission_exam_results');
        Schema::dropIfExists('admission_exam_marks');
        Schema::dropIfExists('admission_exam_subjects');
        Schema::dropIfExists('admission_exams');
    }
};