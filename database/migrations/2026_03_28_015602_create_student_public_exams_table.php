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
        Schema::create('student_public_exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->string('exam_name'); // e.g., JSC, SSC, HSC
            $table->string('board')->nullable();
            $table->string('roll_no')->nullable();
            $table->string('reg_no')->nullable();
            $table->string('exam_year', 10)->nullable();
            $table->string('session', 20)->nullable();
            $table->string('candidate_type')->nullable(); // Regular, Irregular
            $table->string('center_name')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_public_exams');
    }
};
